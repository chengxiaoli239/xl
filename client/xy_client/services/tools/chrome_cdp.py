import json
import time
from urllib.parse import urlsplit, urlunsplit

import requests
import websocket


class ChromeCdpError(RuntimeError):
    pass


def platform_base_url(domain):
    value = str(domain or "").strip()
    if not value:
        raise ChromeCdpError("Platform URL is empty")
    if "://" not in value:
        value = "https://" + value

    parsed = urlsplit(value)
    if not parsed.scheme or not parsed.netloc:
        raise ChromeCdpError("Invalid platform URL: " + value)
    return urlunsplit((parsed.scheme, parsed.netloc, "", "", ""))


def build_login_url(domain, timestamp_ms=None):
    timestamp_ms = timestamp_ms or int(time.time() * 1000)
    return f"{platform_base_url(domain)}/Member/Login?_={timestamp_ms}"


class ChromeCdpClient:
    def __init__(self, port, timeout=5):
        self.port = int(port)
        self.timeout = timeout
        self.websocket = None
        self._command_id = 0

    def __enter__(self):
        self.connect()
        return self

    def __exit__(self, exc_type, exc_value, traceback):
        self.close()

    @property
    def endpoint(self):
        return f"http://127.0.0.1:{self.port}"

    def connect(self):
        targets = self._get_targets()
        page = next(
            (target for target in targets if target.get("type") == "page"),
            None,
        )
        if not page:
            response = requests.put(
                self.endpoint + "/json/new?about:blank",
                timeout=self.timeout,
            )
            response.raise_for_status()
            page = response.json()

        websocket_url = page.get("webSocketDebuggerUrl")
        if not websocket_url:
            raise ChromeCdpError("Chrome page has no debugger WebSocket URL")

        self.websocket = websocket.create_connection(
            websocket_url,
            timeout=self.timeout,
            suppress_origin=True,
        )
        self.send("Page.enable")
        self.send("Runtime.enable")
        self.send("Network.enable")
        return self

    def close(self):
        if self.websocket is not None:
            try:
                self.websocket.close()
            finally:
                self.websocket = None

    def _get_targets(self):
        response = requests.get(self.endpoint + "/json/list", timeout=self.timeout)
        response.raise_for_status()
        targets = response.json()
        if not isinstance(targets, list):
            raise ChromeCdpError("Unexpected Chrome target response")
        return targets

    def send(self, method, params=None, timeout=None):
        if self.websocket is None:
            raise ChromeCdpError("Chrome debugger is not connected")

        self._command_id += 1
        command_id = self._command_id
        payload = {"id": command_id, "method": method}
        if params:
            payload["params"] = params

        previous_timeout = self.websocket.gettimeout()
        self.websocket.settimeout(timeout or self.timeout)
        try:
            self.websocket.send(json.dumps(payload))
            while True:
                message = json.loads(self.websocket.recv())
                if message.get("id") != command_id:
                    continue
                if "error" in message:
                    error = message["error"]
                    raise ChromeCdpError(
                        f"CDP {method} failed: {error.get('message', error)}"
                    )
                return message.get("result", {})
        finally:
            self.websocket.settimeout(previous_timeout)

    def evaluate(self, expression):
        result = self.send(
            "Runtime.evaluate",
            {
                "expression": expression,
                "awaitPromise": True,
                "returnByValue": True,
            },
        )
        if result.get("exceptionDetails"):
            details = result["exceptionDetails"].get("text", "JavaScript error")
            raise ChromeCdpError(details)
        return result.get("result", {}).get("value")

    def navigate(self, url, timeout=20):
        result = self.send("Page.navigate", {"url": url}, timeout=timeout)
        if result.get("errorText"):
            raise ChromeCdpError(result["errorText"])

        deadline = time.time() + timeout
        while time.time() < deadline:
            try:
                ready_state = self.evaluate("document.readyState")
                if ready_state in ("interactive", "complete"):
                    return True
            except ChromeCdpError:
                pass
            time.sleep(0.25)
        return False

    def wait_for_selector(self, selector, timeout=15):
        selector_json = json.dumps(selector)
        deadline = time.time() + timeout
        while time.time() < deadline:
            if self.evaluate(f"Boolean(document.querySelector({selector_json}))"):
                return True
            time.sleep(0.25)
        return False

    def fill_input(self, selector, value):
        selector_json = json.dumps(selector)
        value_json = json.dumps(str(value))
        expression = f"""
            (() => {{
                const element = document.querySelector({selector_json});
                if (!element) return false;
                const descriptor = Object.getOwnPropertyDescriptor(
                    HTMLInputElement.prototype, 'value'
                );
                if (descriptor && descriptor.set) {{
                    descriptor.set.call(element, {value_json});
                }} else {{
                    element.value = {value_json};
                }}
                element.dispatchEvent(new Event('input', {{bubbles: true}}));
                element.dispatchEvent(new Event('change', {{bubbles: true}}));
                return true;
            }})()
        """
        if not self.evaluate(expression):
            raise ChromeCdpError("Input element not found: " + selector)

    def click(self, selector):
        selector_json = json.dumps(selector)
        expression = f"""
            (() => {{
                const element = document.querySelector({selector_json});
                if (!element) return false;
                element.scrollIntoView({{block: 'center'}});
                element.click();
                return true;
            }})()
        """
        if not self.evaluate(expression):
            raise ChromeCdpError("Clickable element not found: " + selector)

    def accept_dialog(self):
        try:
            self.send("Page.handleJavaScriptDialog", {"accept": True})
            return True
        except ChromeCdpError:
            return False

    def login_state(self):
        return self.evaluate(
            """
            (() => {
                const account = document.querySelector('#Account');
                const password = document.querySelector('#Password');
                const balance = document.querySelector('#CreditBalance');
                const error = document.querySelector(
                    '.validation-summary-errors, .field-validation-error, '
                    + '.alert-danger, .login-error, .error, .tips'
                );
                return {
                    url: location.href,
                    loginFormVisible: Boolean(account && password),
                    balanceVisible: Boolean(balance),
                    errorText: error ? (error.innerText || error.textContent || '').trim() : ''
                };
            })()
            """
        ) or {}

    def wait_for_login_result(self, timeout=30):
        deadline = time.time() + timeout
        state = {}
        while time.time() < deadline:
            self.accept_dialog()
            state = self.login_state()
            url = str(state.get("url", "")).lower()
            if state.get("balanceVisible"):
                return state
            if not state.get("loginFormVisible") and "/member/login" not in url:
                return state
            time.sleep(0.5)
        return state

    def handle_interstitials(self):
        for _ in range(4):
            url = str(self.evaluate("location.href") or "")
            lower_url = url.lower()
            has_agree_button = self.evaluate(
                "Boolean(document.querySelector('#agree'))"
            )
            if "agreement" in lower_url or has_agree_button:
                clicked = self.evaluate(
                    """
                    (() => {
                        const direct = document.querySelector('#agree');
                        const candidates = Array.from(document.querySelectorAll(
                            'button, input[type=button], input[type=submit], a'
                        ));
                        const button = direct || candidates.find((element) => {
                            const text = (element.innerText || element.value || '').trim();
                            return text.includes('同意') || /agree/i.test(text);
                        });
                        if (!button) return false;
                        button.click();
                        return true;
                    })()
                    """
                )
                if clicked:
                    time.sleep(1)
                    continue
            if "egis-notice" in lower_url and self.evaluate(
                "Boolean(document.querySelector('#btn_enter'))"
            ):
                self.click("#btn_enter")
                time.sleep(1)
                continue
            break

        self.evaluate(
            """
            (() => {
                const close = document.querySelector('.btn-close.fn-close');
                if (close) close.click();
                return true;
            })()
            """
        )
        return str(self.evaluate("location.href") or "")

    def get_cookies(self, urls=None):
        if urls:
            result = self.send("Network.getCookies", {"urls": list(urls)})
        else:
            result = self.send("Storage.getCookies")
        return result.get("cookies", [])

    def get_user_agent(self):
        return str(self.evaluate("navigator.userAgent") or "")

    def get_balance(self):
        value = self.evaluate(
            """
            (() => {
                const balance = document.querySelector('#CreditBalance');
                return balance ? (balance.textContent || '').trim() : '';
            })()
            """
        )
        return str(value or "0.00")


def login_platform_via_cdp(
    port,
    domain,
    account,
    password,
    timeout=30,
    client_factory=None,
):
    if not account or not password:
        return {"status": 422, "msg": "Platform account or password is empty"}

    factory = client_factory or ChromeCdpClient
    login_url = build_login_url(domain)
    try:
        with factory(port) as client:
            print("[CDP] Opening login page: " + login_url)
            client.navigate(login_url, timeout=20)
            if not client.wait_for_selector("#Account", timeout=15):
                return {"status": 500, "msg": "Platform account input was not found"}
            if not client.wait_for_selector("#Password", timeout=5):
                return {"status": 500, "msg": "Platform password input was not found"}
            if not client.wait_for_selector("#btn-submit", timeout=5):
                return {"status": 500, "msg": "Platform login button was not found"}

            client.fill_input("#Account", account)
            client.fill_input("#Password", password)
            client.click("#btn-submit")

            state = client.wait_for_login_result(timeout=timeout)
            current_url = str(state.get("url", ""))
            if state.get("loginFormVisible") or "/member/login" in current_url.lower():
                message = state.get("errorText") or "Platform login did not leave the login page"
                return {"status": 401, "msg": message, "balance": "0.00"}

            current_url = client.handle_interstitials() or current_url
            cookies = client.get_cookies(
                [current_url, platform_base_url(domain)]
            )
            if not cookies:
                return {"status": 500, "msg": "Platform login returned no cookies"}

            return {
                "status": 200,
                "msg": "Login successful",
                "balance": client.get_balance(),
                "cookies": cookies,
                "user_agent": client.get_user_agent(),
                "current_url": current_url,
                "login_url": login_url,
            }
    except Exception as exc:
        return {
            "status": 500,
            "msg": "Chrome DevTools login failed: " + str(exc),
            "balance": "0.00",
        }
