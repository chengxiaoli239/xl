import requests


class ClientAuthError(RuntimeError):
    def __init__(self, message, status=0):
        super().__init__(message)
        self.status = int(status or 0)


class ClientAuth:
    def __init__(self, robot_domain, timeout=12):
        self.robot_domain = robot_domain.strip().rstrip("/")
        self.timeout = timeout
        if not self.robot_domain.startswith(("http://", "https://")):
            raise ClientAuthError("后台地址必须以 http:// 或 https:// 开头")

    def login(self, username, password):
        return self._post("/api/client-auth/login", {
            "username": username.strip(),
            "password": password,
        })

    def validate(self, access_token):
        return self._post("/api/client-auth/validate", {"access_token": access_token})

    def _post(self, path, payload):
        try:
            response = requests.post(
                self.robot_domain + path,
                json=payload,
                timeout=(4, self.timeout),
            )
            response.raise_for_status()
            data = response.json()
        except requests.RequestException as exc:
            raise ClientAuthError("无法连接后台：" + str(exc)) from exc
        except ValueError as exc:
            raise ClientAuthError("后台返回了无法识别的数据") from exc

        if int(data.get("status", 0)) != 200:
            raise ClientAuthError(data.get("msg", "登录失败"), data.get("status", 0))
        return data.get("data", {})
