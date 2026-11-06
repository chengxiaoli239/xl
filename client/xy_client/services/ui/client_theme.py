"""Shared visual treatment for the Lucky5 desktop client."""

from PyQt5 import QtCore, QtGui, QtWidgets


STYLESHEET = """
QMainWindow, QDialog { background: #e9eff4; color: #182433; }
QWidget#centralwidget { background: #e9eff4; }
QLabel { color: #263648; background: transparent; }
QGroupBox {
    background: #ffffff;
    border: 1px solid #d7e0e8;
    border-radius: 8px;
    margin-top: 18px;
    padding: 16px 12px 12px;
    font-size: 13px;
    font-weight: 600;
    color: #182433;
}
QGroupBox::title {
    subcontrol-origin: margin;
    subcontrol-position: top left;
    left: 16px;
    padding: 0 6px;
    background: #ffffff;
}
QLineEdit, QComboBox, QSpinBox, QPlainTextEdit, QListWidget {
    background: #ffffff;
    color: #182433;
    border: 1px solid #cbd7e1;
    border-radius: 5px;
    padding: 4px 8px;
    selection-background-color: #d9e9f5;
}
QLineEdit, QComboBox, QSpinBox { min-height: 22px; }
QLineEdit:focus, QComboBox:focus, QSpinBox:focus, QPlainTextEdit:focus {
    border: 1px solid #426996;
}
QLineEdit:read-only { background: #f4f7f9; color: #576a78; }
QPushButton {
    background: #f8fafc;
    color: #213247;
    border: 1px solid #cad6e1;
    border-radius: 5px;
    padding: 5px 10px;
    min-height: 24px;
    font-weight: 600;
}
QPushButton:hover { background: #edf3f8; border-color: #94abc2; }
QPushButton:pressed { background: #dce8f1; }
QPushButton:disabled { color: #96a4b0; background: #f3f5f7; }
QPushButton[role="primary"], QDialogButtonBox QPushButton:default {
    color: #ffffff;
    background: #294a79;
    border-color: #294a79;
}
QPushButton[role="primary"]:hover, QDialogButtonBox QPushButton:default:hover {
    background: #365d91;
}
QTableWidget {
    background: #ffffff;
    alternate-background-color: #f7f9fb;
    color: #182433;
    border: 1px solid #d7e0e8;
    border-radius: 6px;
    gridline-color: #e8edf1;
}
QTableWidget::item { padding: 5px; }
QTableWidget::item:selected { background: #dbeaf5; color: #182433; }
QHeaderView::section {
    background: #f1f5f8;
    color: #42566a;
    border: 0;
    border-bottom: 1px solid #d7e0e8;
    padding: 8px;
    font-weight: 600;
}
QMenuBar, QStatusBar { background: #ffffff; color: #42566a; }
QMenuBar::item:selected, QMenu::item:selected { background: #e6eef5; }
QMenu { background: #ffffff; border: 1px solid #d7e0e8; }
QCheckBox, QRadioButton { color: #40566b; spacing: 6px; }
QScrollBar:vertical { width: 9px; background: #edf2f6; border: 0; }
QScrollBar::handle:vertical { background: #b9c8d5; border-radius: 4px; min-height: 30px; }
QScrollBar:horizontal { height: 9px; background: #edf2f6; border: 0; }
QScrollBar::handle:horizontal { background: #b9c8d5; border-radius: 4px; min-width: 30px; }
QFrame#workbenchHeader { background: #ffffff; border: 1px solid #d7e0e8; border-radius: 8px; }
QLabel#workbenchBrand { color: #ffffff; background: #294a79; border-radius: 6px; font-size: 16px; font-weight: 700; }
QLabel#workbenchTitle { color: #182433; font-size: 18px; font-weight: 700; }
QLabel#workbenchSubtitle { color: #6c7c89; font-size: 11px; }
QLabel#workbenchStatus { color: #147664; background: #e5f3ef; border-radius: 4px; padding: 6px 10px; font-weight: 600; }
"""


def apply_dialog_theme(dialog):
    dialog.setStyleSheet(STYLESHEET)
    dialog.setFont(QtGui.QFont("PingFang SC", 10))


def apply_main_window_theme(window):
    window.setStyleSheet(STYLESHEET)
    window.setFont(QtGui.QFont("PingFang SC", 10))
    window.resize(1360, 860)
    window.setMinimumSize(1024, 700)

    layout = window.verticalLayout_6
    layout.setContentsMargins(18, 16, 18, 18)
    layout.setSpacing(14)

    header = QtWidgets.QFrame(window.centralwidget)
    header.setObjectName("workbenchHeader")
    header.setFixedHeight(78)
    row = QtWidgets.QHBoxLayout(header)
    row.setContentsMargins(18, 12, 18, 12)
    row.setSpacing(14)

    brand = QtWidgets.QLabel("L5", header)
    brand.setObjectName("workbenchBrand")
    brand.setAlignment(QtCore.Qt.AlignCenter)
    brand.setFixedSize(42, 42)
    row.addWidget(brand)

    heading = QtWidgets.QVBoxLayout()
    heading.setSpacing(2)
    title = QtWidgets.QLabel("Lucky5 工作台", header)
    title.setObjectName("workbenchTitle")
    subtitle = QtWidgets.QLabel("账号管理  /  下注记录", header)
    subtitle.setObjectName("workbenchSubtitle")
    heading.addWidget(title)
    heading.addWidget(subtitle)
    row.addLayout(heading)
    row.addStretch(1)

    status = QtWidgets.QLabel("本地客户端", header)
    status.setObjectName("workbenchStatus")
    row.addWidget(status)
    layout.insertWidget(0, header)

    window.horizontalLayout_12.setStretch(0, 0)
    window.horizontalLayout_12.setStretch(1, 1)
    window.verticalLayout_5.setSpacing(10)
    window.mainRight.setSpacing(10)
    window.horizontalLayout_browser.removeWidget(window.open_browser_checkbox)
    browser_row = window.verticalLayout.indexOf(window.horizontalLayout_browser)
    window.verticalLayout.insertWidget(browser_row + 1, window.open_browser_checkbox)
    for group in (window.UserInfo, window.ProxyInfo, window.QueryGroup):
        group.setMinimumWidth(320)
        group.setMaximumWidth(360)
        group.setMinimumHeight(max(group.minimumHeight(), group.sizeHint().height()))
    left_layout = window.verticalLayout_5
    window.horizontalLayout_12.removeItem(left_layout)
    left_content = QtWidgets.QWidget(window.centralwidget)
    left_content.setLayout(left_layout)
    left_content.setMinimumHeight(left_content.sizeHint().height())
    left_scroll = QtWidgets.QScrollArea(window.centralwidget)
    left_scroll.setObjectName("accountScroll")
    left_scroll.setWidgetResizable(True)
    left_scroll.setFrameShape(QtWidgets.QFrame.NoFrame)
    left_scroll.setHorizontalScrollBarPolicy(QtCore.Qt.ScrollBarAlwaysOff)
    left_scroll.setMinimumWidth(340)
    left_scroll.setMaximumWidth(380)
    left_scroll.setWidget(left_content)
    window.horizontalLayout_12.insertWidget(0, left_scroll)
    for field in (window.username_val, window.pwd_val, window.balance,
                  window.token_val, window.expire_val,
                  window.agent_username_val, window.agent_password_val):
        field.setMaximumWidth(16777215)
        field.setSizePolicy(QtWidgets.QSizePolicy.Expanding, QtWidgets.QSizePolicy.Fixed)
    window.tip_msg.setMaximumHeight(116)
    window.tableWidget.setAlternatingRowColors(True)
    window.tableWidget.verticalHeader().setDefaultSectionSize(38)
    window.tableWidget.setShowGrid(False)
    for button in (window.UserLoginBtn, window.AgentLoginBtn, window.search_lists):
        button.setProperty("role", "primary")
        button.style().unpolish(button)
        button.style().polish(button)
    window.search_lists.setMaximumSize(90, 48)
