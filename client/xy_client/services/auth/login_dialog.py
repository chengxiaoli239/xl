import os

from PyQt5 import QtCore, QtWidgets

from xy_client.services.auth import ClientAuth, ClientAuthError, CredentialCache


def _auth_data(profile, from_cache=True):
    return {
        "account_key": profile.get("account_key", ""),
        "robot_domain": profile.get("robot_domain", "").rstrip("/"),
        "username": profile.get("username", ""),
        "access_token": profile.get("access_token", ""),
        "account": profile.get("account") or {},
        "display_name": profile.get("display_name", ""),
        "from_cache": from_cache,
    }


class LoginDialog(QtWidgets.QDialog):
    def __init__(self, robot_domain, cache=None, config=None, parent=None):
        super().__init__(parent)
        self.cache = cache or CredentialCache()
        self.config = config
        self.result_data = None
        self.setWindowTitle("添加 Lucky5 账号")
        self.setMinimumWidth(410)
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
        buttons.button(QtWidgets.QDialogButtonBox.Ok).setText("登录并保存")
        buttons.button(QtWidgets.QDialogButtonBox.Cancel).setText("取消")
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
        domain = self.domain_edit.text().strip().rstrip("/")
        if not username or not password:
            self.error_label.setText("请输入后台账号和密码")
            return

        self.error_label.setText("正在登录...")
        QtWidgets.QApplication.processEvents()
        try:
            data = ClientAuth(domain).login(username, password)
            profiles = self.cache.save_login_result(domain, username, data)
            selected = self._select_profile(profiles)
            if not selected:
                self.error_label.setText("请选择要运行的下注账号")
                return

            self.cache.set_active(selected["account_key"])
            self.result_data = _auth_data(selected, from_cache=False)
            if self.config:
                self.config.set_config("robot_domain", domain)
            self.accept()
        except (ClientAuthError, ValueError) as exc:
            self.error_label.setText(str(exc))

    def _select_profile(self, profiles):
        if len(profiles) == 1:
            return profiles[0]
        labels = []
        for profile in profiles:
            account = profile.get("account") or {}
            identity = account.get("id") or profile.get("account_key", "")[:8]
            labels.append(
                f"{profile.get('display_name', '未命名账号')}  (ID: {identity})"
            )
        selected_label, accepted = QtWidgets.QInputDialog.getItem(
            self,
            "选择运行账号",
            "下注账号",
            labels,
            0,
            False,
        )
        if not accepted:
            return None
        return profiles[labels.index(selected_label)]


class AccountSelectionDialog(QtWidgets.QDialog):
    def __init__(
        self,
        robot_domain,
        cache=None,
        config=None,
        parent=None,
        excluded_account_key="",
    ):
        super().__init__(parent)
        self.cache = cache or CredentialCache()
        self.config = config
        self.robot_domain = str(robot_domain or "").strip().rstrip("/")
        self.excluded_account_key = excluded_account_key
        self.result_data = None
        self.setWindowTitle("选择 Lucky5 账号")
        self.setMinimumSize(560, 340)
        self.setWindowFlag(QtCore.Qt.WindowContextHelpButtonHint, False)

        layout = QtWidgets.QVBoxLayout(self)
        layout.setContentsMargins(20, 18, 20, 18)
        layout.setSpacing(12)

        self.account_list = QtWidgets.QListWidget(self)
        self.account_list.setAlternatingRowColors(True)
        self.account_list.setSelectionMode(QtWidgets.QAbstractItemView.SingleSelection)
        self.account_list.setHorizontalScrollBarPolicy(QtCore.Qt.ScrollBarAlwaysOff)
        self.account_list.setTextElideMode(QtCore.Qt.ElideRight)
        self.account_list.setWordWrap(False)
        self.account_list.itemDoubleClicked.connect(lambda _item: self._start_selected())
        layout.addWidget(self.account_list, 1)

        self.error_label = QtWidgets.QLabel(self)
        self.error_label.setStyleSheet("color: #c62828;")
        self.error_label.setWordWrap(True)
        layout.addWidget(self.error_label)

        button_layout = QtWidgets.QHBoxLayout()
        self.add_button = QtWidgets.QPushButton("添加账号", self)
        self.add_button.setIcon(self.style().standardIcon(QtWidgets.QStyle.SP_FileDialogNewFolder))
        self.delete_button = QtWidgets.QPushButton("删除账号", self)
        self.delete_button.setIcon(self.style().standardIcon(QtWidgets.QStyle.SP_TrashIcon))
        self.start_button = QtWidgets.QPushButton("启动所选账号", self)
        self.start_button.setDefault(True)
        self.start_button.setIcon(self.style().standardIcon(QtWidgets.QStyle.SP_MediaPlay))
        self.cancel_button = QtWidgets.QPushButton("取消", self)
        button_layout.addWidget(self.add_button)
        button_layout.addWidget(self.delete_button)
        button_layout.addStretch(1)
        button_layout.addWidget(self.cancel_button)
        button_layout.addWidget(self.start_button)
        layout.addLayout(button_layout)

        self.add_button.clicked.connect(self._add_account)
        self.delete_button.clicked.connect(self._delete_selected)
        self.start_button.clicked.connect(self._start_selected)
        self.cancel_button.clicked.connect(self.reject)
        self.account_list.itemSelectionChanged.connect(self._update_buttons)
        self._refresh_accounts()

    def _refresh_accounts(self, selected_key=""):
        self.account_list.clear()
        for profile in self.cache.list_accounts():
            account_key = profile.get("account_key", "")
            if account_key == self.excluded_account_key:
                continue
            label = profile.get("display_name", "未命名账号")
            domain = self.robot_domain or profile.get("robot_domain", "")
            item = QtWidgets.QListWidgetItem(f"{label}\n{domain}")
            item.setData(QtCore.Qt.UserRole, account_key)
            item.setToolTip(f"{label}\n{domain}")
            self.account_list.addItem(item)
            if account_key == selected_key:
                self.account_list.setCurrentItem(item)

        if self.account_list.count() and not self.account_list.currentItem():
            self.account_list.setCurrentRow(0)
        self._update_buttons()

    def _update_buttons(self):
        has_selection = self.account_list.currentItem() is not None
        self.start_button.setEnabled(has_selection)
        self.delete_button.setEnabled(has_selection)

    def _add_account(self):
        dialog = LoginDialog(
            self.robot_domain,
            cache=self.cache,
            config=self.config,
            parent=self,
        )
        if dialog.exec_() == QtWidgets.QDialog.Accepted and dialog.result_data:
            selected_key = dialog.result_data.get("account_key", "")
            self.robot_domain = dialog.result_data.get("robot_domain", self.robot_domain)
            self._refresh_accounts(selected_key=selected_key)
            self.error_label.clear()

    def _delete_selected(self):
        item = self.account_list.currentItem()
        if not item:
            return
        account_key = item.data(QtCore.Qt.UserRole)
        answer = QtWidgets.QMessageBox.question(
            self,
            "删除账号",
            "确定删除所选账号的本地登录凭证吗？",
            QtWidgets.QMessageBox.Yes | QtWidgets.QMessageBox.No,
            QtWidgets.QMessageBox.No,
        )
        if answer == QtWidgets.QMessageBox.Yes:
            self.cache.remove(account_key)
            self._refresh_accounts()

    def _start_selected(self):
        item = self.account_list.currentItem()
        if not item:
            return
        account_key = item.data(QtCore.Qt.UserRole)
        profile = self.cache.get_account(account_key)
        if not profile:
            self.error_label.setText("账号凭证不存在，请重新添加")
            self._refresh_accounts()
            return

        self.error_label.setText("正在验证账号...")
        QtWidgets.QApplication.processEvents()
        try:
            domain = self.robot_domain or profile.get("robot_domain", "")
            validated = ClientAuth(domain).validate(profile["access_token"])
            profile = self.cache.update_account(
                account_key,
                robot_domain=domain,
                account=validated.get("account") or profile.get("account") or {},
            )
            self.cache.set_active(account_key)
            self.result_data = _auth_data(profile, from_cache=True)
            self.accept()
        except ClientAuthError as exc:
            if exc.status == 401:
                self.cache.remove(account_key)
                self._refresh_accounts()
                self.error_label.setText("该账号凭证已失效，请重新添加")
            else:
                self.error_label.setText(str(exc))


def _validate_profile(cache, profile, configured_domain=""):
    domain = profile.get("robot_domain", "").rstrip("/")
    if configured_domain:
        domain = configured_domain.rstrip("/")
    validated = ClientAuth(domain).validate(profile.get("access_token", ""))
    updated = cache.update_account(
        profile["account_key"],
        robot_domain=domain,
        account=validated.get("account") or profile.get("account") or {},
    )
    cache.set_active(profile["account_key"])
    return _auth_data(updated, from_cache=True)


def select_client_account(
    robot_domain,
    parent=None,
    config=None,
    excluded_account_key="",
):
    dialog = AccountSelectionDialog(
        robot_domain,
        cache=CredentialCache(),
        config=config,
        parent=parent,
        excluded_account_key=excluded_account_key,
    )
    if dialog.exec_() != QtWidgets.QDialog.Accepted:
        return None
    return dialog.result_data


def authenticate_client(robot_domain, parent=None, config=None):
    cache = CredentialCache()
    configured_domain = str(robot_domain or "").strip().rstrip("/")
    requested_key = os.environ.get("LUCKY5_ACCOUNT_KEY", "").strip()

    if requested_key:
        profile = cache.get_account(requested_key)
        if profile:
            try:
                return _validate_profile(cache, profile, configured_domain)
            except ClientAuthError as exc:
                if exc.status == 401:
                    cache.remove(requested_key)

    profiles = cache.list_accounts()
    if len(profiles) == 1:
        profile = profiles[0]
        try:
            return _validate_profile(cache, profile, configured_domain)
        except ClientAuthError as exc:
            if exc.status == 401:
                cache.remove(profile["account_key"])

    if cache.list_accounts():
        return select_client_account(configured_domain, parent=parent, config=config)

    dialog = LoginDialog(configured_domain, cache=cache, config=config, parent=parent)
    if dialog.exec_() != QtWidgets.QDialog.Accepted:
        return None
    return dialog.result_data
