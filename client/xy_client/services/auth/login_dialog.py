from PyQt5 import QtCore, QtWidgets

from xy_client.services.auth import ClientAuth, ClientAuthError, CredentialCache


class LoginDialog(QtWidgets.QDialog):
    def __init__(self, robot_domain, cache=None, config=None, parent=None):
        super().__init__(parent)
        self.cache = cache or CredentialCache()
        self.config = config
        self.result_data = None
        self.setWindowTitle("Lucky5 客户端登录")
        self.setMinimumWidth(390)
        self.setWindowFlag(QtCore.Qt.WindowContextHelpButtonHint, False)

        layout = QtWidgets.QFormLayout(self)
        layout.setContentsMargins(24, 22, 24, 22)
        layout.setSpacing(14)

        self.domain_edit = QtWidgets.QLineEdit(robot_domain)
        self.domain_edit.setPlaceholderText("https://后台地址")
        self.username_edit = QtWidgets.QLineEdit()
        self.username_edit.setPlaceholderText("后台账号")
        self.password_edit = QtWidgets.QLineEdit()
        self.password_edit.setEchoMode(QtWidgets.QLineEdit.Password)
        self.password_edit.setPlaceholderText("后台密码")
        layout.addRow("后台地址", self.domain_edit)
        layout.addRow("账号", self.username_edit)
        layout.addRow("密码", self.password_edit)

        self.error_label = QtWidgets.QLabel()
        self.error_label.setStyleSheet("color: #c62828;")
        self.error_label.setWordWrap(True)
        layout.addRow(self.error_label)

        buttons = QtWidgets.QDialogButtonBox(
            QtWidgets.QDialogButtonBox.Cancel | QtWidgets.QDialogButtonBox.Ok
        )
        buttons.button(QtWidgets.QDialogButtonBox.Ok).setText("登录")
        buttons.button(QtWidgets.QDialogButtonBox.Cancel).setText("退出")
        buttons.accepted.connect(self._login)
        buttons.rejected.connect(self.reject)
        layout.addRow(buttons)

        cached = self.cache.load()
        if cached.get("username"):
            self.username_edit.setText(cached["username"])
        self.password_edit.returnPressed.connect(self._login)

    def _login(self):
        username = self.username_edit.text().strip()
        password = self.password_edit.text()
        domain = self.domain_edit.text().strip()
        if not username or not password:
            self.error_label.setText("请输入后台账号和密码")
            return

        self.error_label.setText("正在登录...")
        QtWidgets.QApplication.processEvents()
        try:
            data = ClientAuth(domain).login(username, password)
            self.cache.save(domain, username, data["access_token"])
            self.result_data = data
            self.result_data["robot_domain"] = domain.rstrip("/")
            if self.config:
                self.config.set_config("robot_domain", self.result_data["robot_domain"])
            self.accept()
        except ClientAuthError as exc:
            self.error_label.setText(str(exc))


def authenticate_client(robot_domain, parent=None, config=None):
    cache = CredentialCache()
    cached = cache.load()
    configured_domain = robot_domain.strip().rstrip("/")
    cached_domain = configured_domain or cached.get("robot_domain", "").rstrip("/")
    token = cached.get("access_token", "")
    if token and cached_domain:
        try:
            data = ClientAuth(cached_domain).validate(token)
            if cached.get("robot_domain", "").rstrip("/") != cached_domain:
                cache.save(cached_domain, cached.get("username", ""), token)
            data.update({
                "access_token": token,
                "username": cached.get("username", ""),
                "robot_domain": cached_domain,
                "from_cache": True,
            })
            return data
        except ClientAuthError as exc:
            if exc.status == 401:
                cache.clear()

    dialog = LoginDialog(cached_domain, cache=cache, config=config, parent=parent)
    if dialog.exec_() != QtWidgets.QDialog.Accepted:
        return None
    return dialog.result_data
