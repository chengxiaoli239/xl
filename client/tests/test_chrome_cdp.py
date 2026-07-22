import unittest

from xy_client.services.tools.chrome_cdp import (
    build_login_url,
    login_platform_via_cdp,
    platform_base_url,
)


class FakeCdpClient:
    instances = []
    login_state_result = {
        "url": "https://platform.example.com/App/Index",
        "loginFormVisible": False,
        "balanceVisible": True,
        "errorText": "",
    }

    def __init__(self, port):
        self.port = port
        self.calls = []
        self.__class__.instances.append(self)

    def __enter__(self):
        return self

    def __exit__(self, exc_type, exc_value, traceback):
        return False

    def navigate(self, url, timeout=20):
        self.calls.append(("navigate", url, timeout))
        return True

    def wait_for_selector(self, selector, timeout=15):
        self.calls.append(("wait", selector, timeout))
        return True

    def fill_input(self, selector, value):
        self.calls.append(("fill", selector, value))

    def click(self, selector):
        self.calls.append(("click", selector))

    def wait_for_login_result(self, timeout=30):
        self.calls.append(("login_result", timeout))
        return dict(self.login_state_result)

    def handle_interstitials(self):
        self.calls.append(("interstitials",))
        return "https://platform.example.com/App/Index"

    def get_cookies(self, urls=None):
        self.calls.append(("cookies", tuple(urls or [])))
        return [{"name": "session", "value": "cookie-value"}]

    def get_user_agent(self):
        return "Fake Chrome"

    def get_balance(self):
        return "123.45"


class ChromeCdpLoginTest(unittest.TestCase):
    def setUp(self):
        FakeCdpClient.instances.clear()
        FakeCdpClient.login_state_result = {
            "url": "https://platform.example.com/App/Index",
            "loginFormVisible": False,
            "balanceVisible": True,
            "errorText": "",
        }

    def test_platform_base_url_removes_existing_path(self):
        self.assertEqual(
            platform_base_url("platform.example.com/Member/Login"),
            "https://platform.example.com",
        )

    def test_build_login_url_targets_member_login(self):
        self.assertEqual(
            build_login_url("https://platform.example.com/App/Index", 123),
            "https://platform.example.com/Member/Login?_=123",
        )

    def test_login_fills_form_and_returns_browser_session(self):
        result = login_platform_via_cdp(
            9407,
            "https://platform.example.com",
            "platform-account",
            "platform-password",
            client_factory=FakeCdpClient,
        )

        self.assertEqual(result["status"], 200)
        self.assertEqual(result["balance"], "123.45")
        self.assertEqual(result["cookies"][0]["name"], "session")
        self.assertEqual(result["user_agent"], "Fake Chrome")

        client = FakeCdpClient.instances[0]
        self.assertEqual(client.port, 9407)
        self.assertIn(("fill", "#Account", "platform-account"), client.calls)
        self.assertIn(("fill", "#Password", "platform-password"), client.calls)
        self.assertIn(("click", "#btn-submit"), client.calls)

    def test_login_page_error_is_returned(self):
        FakeCdpClient.login_state_result = {
            "url": "https://platform.example.com/Member/Login",
            "loginFormVisible": True,
            "balanceVisible": False,
            "errorText": "Invalid account",
        }

        result = login_platform_via_cdp(
            9407,
            "https://platform.example.com",
            "bad-account",
            "bad-password",
            client_factory=FakeCdpClient,
        )

        self.assertEqual(result["status"], 401)
        self.assertEqual(result["msg"], "Invalid account")


if __name__ == "__main__":
    unittest.main()
