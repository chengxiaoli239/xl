#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
多账号管理界面设计
单程序管理多个账号，每个账号独立运行定时任务
"""

import json
import os
import sys
import time
import threading
from typing import Dict, List, Optional
from PyQt5.QtWidgets import *
from PyQt5.QtCore import *
from PyQt5.QtGui import *

from xy_client.services.tools.Configs import Configs


class AccountManager:
    """账号管理器"""
    
    def __init__(self):
        self.accounts: Dict[str, Dict] = {}
        self.config_file = "accounts_config.json"
        self.load_accounts()
    
    def load_accounts(self):
        """加载账号配置"""
        try:
            if os.path.exists(self.config_file):
                with open(self.config_file, 'r', encoding='utf-8') as f:
                    self.accounts = json.load(f)
        except Exception as e:
            print(f"加载账号配置失败: {e}")
            self.accounts = {}
    
    def save_accounts(self):
        """保存账号配置"""
        try:
            with open(self.config_file, 'w', encoding='utf-8') as f:
                json.dump(self.accounts, f, ensure_ascii=False, indent=2)
        except Exception as e:
            print(f"保存账号配置失败: {e}")
    
    def add_account(self, account_id: str, account_info: Dict):
        """添加账号"""
        self.accounts[account_id] = {
            **account_info,
            'status': 'offline',
            'last_login': None,
            'created_time': time.time()
        }
        self.save_accounts()
    
    def update_account(self, account_id: str, account_info: Dict):
        """更新账号信息"""
        if account_id in self.accounts:
            self.accounts[account_id].update(account_info)
            self.save_accounts()
    
    def delete_account(self, account_id: str):
        """删除账号"""
        if account_id in self.accounts:
            del self.accounts[account_id]
            self.save_accounts()
    
    def get_account(self, account_id: str) -> Optional[Dict]:
        """获取账号信息"""
        return self.accounts.get(account_id)
    
    def get_all_accounts(self) -> Dict[str, Dict]:
        """获取所有账号"""
        return self.accounts


class AccountItemWidget(QWidget):
    """账号列表项组件"""
    
    status_changed = pyqtSignal(str, str)  # account_id, status
    login_requested = pyqtSignal(str)      # account_id
    logout_requested = pyqtSignal(str)     # account_id
    edit_requested = pyqtSignal(str)       # account_id
    delete_requested = pyqtSignal(str)     # account_id
    
    def __init__(self, account_id: str, account_info: Dict):
        super().__init__()
        self.account_id = account_id
        self.account_info = account_info
        self.init_ui()
    
    def init_ui(self):
        """初始化界面"""
        # 设置整体样式
        self.setStyleSheet("""
            QFrame#accountFrame {
                background-color: white;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                margin: 4px;
            }
            QFrame#accountFrame:hover {
                border-color: #2196F3;
                box-shadow: 0 2px 8px rgba(33, 150, 243, 0.1);
            }
        """)
        
        # 创建主框架
        main_frame = QFrame()
        main_frame.setObjectName("accountFrame")
        main_frame.setFrameStyle(QFrame.StyledPanel)
        
        layout = QHBoxLayout(main_frame)
        layout.setContentsMargins(16, 12, 16, 12)
        layout.setSpacing(16)
        
        # 账号信息区域
        info_layout = QVBoxLayout()
        info_layout.setSpacing(4)
        
        # 账号名称和状态
        name_layout = QHBoxLayout()
        self.name_label = QLabel(self.account_info.get('account_name', self.account_id))
        self.name_label.setStyleSheet("""
            font-weight: 600; 
            font-size: 16px; 
            color: #333333;
        """)
        name_layout.addWidget(self.name_label)
        
        # 状态标签
        self.status_label = QLabel(self.account_info.get('status', 'offline'))
        status_style = """
            color: #f44336; 
            font-size: 12px; 
            font-weight: 500;
            background-color: #ffebee;
            padding: 2px 8px;
            border-radius: 12px;
        """
        self.status_label.setStyleSheet(status_style)
        name_layout.addWidget(self.status_label)
        name_layout.addStretch()
        
        info_layout.addLayout(name_layout)
        
        # 账号详情
        details_layout = QHBoxLayout()
        self.details_label = QLabel(f"Token: {self.account_info.get('access_token', 'N/A')[:20]}...")
        self.details_label.setStyleSheet("""
            color: #666666; 
            font-size: 12px;
            background-color: #f5f5f5;
            padding: 4px 8px;
            border-radius: 4px;
        """)
        details_layout.addWidget(self.details_label)
        details_layout.addStretch()
        
        info_layout.addLayout(details_layout)
        
        layout.addLayout(info_layout)
        
        # 操作按钮区域
        button_layout = QHBoxLayout()
        
        # 登录/登出按钮
        self.login_btn = QPushButton("登录")
        self.login_btn.setStyleSheet("""
            QPushButton {
                background-color: #4CAF50;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                font-weight: bold;
            }
            QPushButton:hover {
                background-color: #45a049;
            }
            QPushButton:pressed {
                background-color: #3d8b40;
            }
        """)
        self.login_btn.clicked.connect(self.on_login_clicked)
        
        self.logout_btn = QPushButton("登出")
        self.logout_btn.setStyleSheet("""
            QPushButton {
                background-color: #f44336;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                font-weight: bold;
            }
            QPushButton:hover {
                background-color: #da190b;
            }
            QPushButton:pressed {
                background-color: #c1170b;
            }
        """)
        self.logout_btn.clicked.connect(self.on_logout_clicked)
        self.logout_btn.setVisible(False)
        
        # 编辑按钮
        self.edit_btn = QPushButton("编辑")
        self.edit_btn.setStyleSheet("""
            QPushButton {
                background-color: #2196F3;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                font-weight: bold;
            }
            QPushButton:hover {
                background-color: #1976D2;
            }
            QPushButton:pressed {
                background-color: #1565C0;
            }
        """)
        self.edit_btn.clicked.connect(self.on_edit_clicked)
        
        # 删除按钮
        self.delete_btn = QPushButton("删除")
        self.delete_btn.setStyleSheet("""
            QPushButton {
                background-color: #FF9800;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                font-weight: bold;
            }
            QPushButton:hover {
                background-color: #F57C00;
            }
            QPushButton:pressed {
                background-color: #EF6C00;
            }
        """)
        self.delete_btn.clicked.connect(self.on_delete_clicked)
        
        button_layout.addWidget(self.login_btn)
        button_layout.addWidget(self.logout_btn)
        button_layout.addWidget(self.edit_btn)
        button_layout.addWidget(self.delete_btn)
        
        layout.addLayout(button_layout)
        
        self.setLayout(layout)
        
        # 设置背景色
        self.setStyleSheet("""
            AccountItemWidget {
                background-color: white;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                margin: 2px;
            }
            AccountItemWidget:hover {
                background-color: #f5f5f5;
                border-color: #2196F3;
            }
        """)
    
    def update_status(self, status: str):
        """更新状态"""
        self.account_info['status'] = status
        self.status_label.setText(status)
        
        if status == 'online':
            self.status_label.setStyleSheet("color: green; font-size: 12px;")
            self.login_btn.setVisible(False)
            self.logout_btn.setVisible(True)
        else:
            self.status_label.setStyleSheet("color: red; font-size: 12px;")
            self.login_btn.setVisible(True)
            self.logout_btn.setVisible(False)
    
    def on_login_clicked(self):
        """登录按钮点击"""
        self.login_requested.emit(self.account_id)
    
    def on_logout_clicked(self):
        """登出按钮点击"""
        self.logout_requested.emit(self.account_id)
    
    def on_edit_clicked(self):
        """编辑按钮点击"""
        self.edit_requested.emit(self.account_id)
    
    def on_delete_clicked(self):
        """删除按钮点击"""
        self.delete_requested.emit(self.account_id)


class AddAccountDialog(QDialog):
    """添加账号对话框"""
    
    def __init__(self, parent=None, account_info: Dict = None):
        super().__init__(parent)
        self.account_info = account_info
        self.init_ui()
    
    def init_ui(self):
        """初始化界面"""
        self.setWindowTitle("添加账号" if not self.account_info else "编辑账号")
        self.setModal(True)
        self.resize(500, 400)
        
        layout = QVBoxLayout()
        
        # 表单区域
        form_layout = QFormLayout()
        
        # 账号名称
        self.name_edit = QLineEdit()
        self.name_edit.setPlaceholderText("请输入账号名称")
        if self.account_info:
            self.name_edit.setText(self.account_info.get('account_name', ''))
        form_layout.addRow("账号名称:", self.name_edit)
        
        # Access Token
        self.token_edit = QLineEdit()
        self.token_edit.setPlaceholderText("请输入Access Token")
        if self.account_info:
            self.token_edit.setText(self.account_info.get('access_token', ''))
        form_layout.addRow("Access Token:", self.token_edit)
        
        # 浏览器类型
        self.browser_combo = QComboBox()
        self.browser_combo.addItems(['chrome', 'firefox'])
        if self.account_info:
            browser_type = self.account_info.get('browser_type', 'chrome')
            self.browser_combo.setCurrentText(browser_type)
        form_layout.addRow("浏览器类型:", self.browser_combo)
        
        # 机器人域名
        default_robot_domain = Configs().get_config('robot_domain').rstrip('/')
        self.robot_domain_edit = QLineEdit()
        self.robot_domain_edit.setPlaceholderText("请输入机器人域名")
        self.robot_domain_edit.setText(default_robot_domain)
        if self.account_info:
            self.robot_domain_edit.setText(self.account_info.get('robot_domain', default_robot_domain))
        form_layout.addRow("机器人域名:", self.robot_domain_edit)
        
        # 网盘域名
        self.wp_domain_edit = QLineEdit()
        self.wp_domain_edit.setPlaceholderText("请输入网盘域名")
        if self.account_info:
            self.wp_domain_edit.setText(self.account_info.get('wp_domain', ''))
        form_layout.addRow("网盘域名:", self.wp_domain_edit)
        
        # 网盘账号
        self.wp_account_edit = QLineEdit()
        self.wp_account_edit.setPlaceholderText("请输入网盘账号")
        if self.account_info:
            self.wp_account_edit.setText(self.account_info.get('wp_account', ''))
        form_layout.addRow("网盘账号:", self.wp_account_edit)
        
        # 网盘密码
        self.wp_password_edit = QLineEdit()
        self.wp_password_edit.setPlaceholderText("请输入网盘密码")
        self.wp_password_edit.setEchoMode(QLineEdit.Password)
        if self.account_info:
            self.wp_password_edit.setText(self.account_info.get('wp_password', ''))
        form_layout.addRow("网盘密码:", self.wp_password_edit)
        
        layout.addLayout(form_layout)
        
        # 按钮区域
        button_layout = QHBoxLayout()
        
        self.save_btn = QPushButton("保存")
        self.save_btn.setStyleSheet("""
            QPushButton {
                background-color: #4CAF50;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                font-weight: bold;
            }
            QPushButton:hover {
                background-color: #45a049;
            }
        """)
        self.save_btn.clicked.connect(self.accept)
        
        self.cancel_btn = QPushButton("取消")
        self.cancel_btn.setStyleSheet("""
            QPushButton {
                background-color: #f44336;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                font-weight: bold;
            }
            QPushButton:hover {
                background-color: #da190b;
            }
        """)
        self.cancel_btn.clicked.connect(self.reject)
        
        button_layout.addStretch()
        button_layout.addWidget(self.save_btn)
        button_layout.addWidget(self.cancel_btn)
        
        layout.addLayout(button_layout)
        
        self.setLayout(layout)
    
    def get_account_info(self) -> Dict:
        """获取账号信息"""
        return {
            'account_name': self.name_edit.text().strip(),
            'access_token': self.token_edit.text().strip(),
            'browser_type': self.browser_combo.currentText(),
            'robot_domain': self.robot_domain_edit.text().strip(),
            'wp_domain': self.wp_domain_edit.text().strip(),
            'wp_account': self.wp_account_edit.text().strip(),
            'wp_password': self.wp_password_edit.text().strip()
        }


class MultiAccountMainWindow(QMainWindow):
    """多账号管理主窗口"""
    
    def __init__(self):
        super().__init__()
        self.account_manager = AccountManager()
        self.account_widgets: Dict[str, AccountItemWidget] = {}
        self.account_instances: Dict[str, Any] = {}  # 存储每个账号的实例
        self.init_ui()
        self.load_accounts()
    
    def init_ui(self):
        """初始化界面"""
        self.setWindowTitle("LuckyClient 多账号管理")
        self.setGeometry(100, 100, 1000, 700)
        
        # 设置现代化Material Design样式
        self.setStyleSheet("""
            QMainWindow {
                background-color: #fafafa;
                font-family: 'Microsoft YaHei', 'Segoe UI', Arial, sans-serif;
            }
            QLabel {
                color: #333333;
                font-size: 14px;
            }
            QPushButton {
                background-color: #2196F3;
                color: white;
                border: none;
                border-radius: 6px;
                padding: 8px 16px;
                font-size: 14px;
                font-weight: 500;
                min-width: 80px;
            }
            QPushButton:hover {
                background-color: #1976D2;
            }
            QPushButton:pressed {
                background-color: #0D47A1;
            }
            QPushButton#addButton {
                background-color: #4CAF50;
                font-size: 16px;
                padding: 12px 24px;
                min-width: 120px;
            }
            QPushButton#addButton:hover {
                background-color: #45a049;
            }
            QPushButton#loginButton {
                background-color: #4CAF50;
            }
            QPushButton#loginButton:hover {
                background-color: #45a049;
            }
            QPushButton#logoutButton {
                background-color: #f44336;
            }
            QPushButton#logoutButton:hover {
                background-color: #d32f2f;
            }
            QPushButton#editButton {
                background-color: #2196F3;
            }
            QPushButton#editButton:hover {
                background-color: #1976D2;
            }
            QPushButton#deleteButton {
                background-color: #ff9800;
            }
            QPushButton#deleteButton:hover {
                background-color: #f57c00;
            }
            QFrame#accountFrame {
                background-color: white;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                margin: 4px;
                padding: 16px;
            }
            QFrame#accountFrame:hover {
                border-color: #2196F3;
            }
            QStatusBar {
                background-color: #f5f5f5;
                border-top: 1px solid #e0e0e0;
                color: #666666;
                font-size: 12px;
            }
            QScrollArea {
                border: none;
                background-color: transparent;
            }
            QScrollArea > QWidget > QWidget {
                background-color: transparent;
            }
        """)
        
        # 创建中央部件
        central_widget = QWidget()
        self.setCentralWidget(central_widget)
        
        # 主布局
        main_layout = QVBoxLayout()
        central_widget.setLayout(main_layout)
        
        # 标题区域
        title_layout = QHBoxLayout()
        
        title_label = QLabel("LuckyClient 多账号管理")
        title_label.setStyleSheet("""
            QLabel {
                font-size: 24px;
                font-weight: bold;
                color: #2196F3;
                padding: 20px;
            }
        """)
        title_layout.addWidget(title_label)
        title_layout.addStretch()
        
        # 添加账号按钮
        self.add_account_btn = QPushButton("添加账号")
        self.add_account_btn.setStyleSheet("""
            QPushButton {
                background-color: #4CAF50;
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 6px;
                font-weight: bold;
                font-size: 14px;
            }
            QPushButton:hover {
                background-color: #45a049;
            }
            QPushButton:pressed {
                background-color: #3d8b40;
            }
        """)
        self.add_account_btn.clicked.connect(self.add_account)
        title_layout.addWidget(self.add_account_btn)
        
        main_layout.addLayout(title_layout)
        
        # 账号列表区域
        list_layout = QVBoxLayout()
        
        # 列表标题
        list_title = QLabel("账号列表")
        list_title.setStyleSheet("""
            QLabel {
                font-size: 16px;
                font-weight: bold;
                color: #333;
                padding: 10px 0;
            }
        """)
        list_layout.addWidget(list_title)
        
        # 账号列表滚动区域
        self.scroll_area = QScrollArea()
        self.scroll_area.setWidgetResizable(True)
        self.scroll_area.setVerticalScrollBarPolicy(Qt.ScrollBarAsNeeded)
        self.scroll_area.setHorizontalScrollBarPolicy(Qt.ScrollBarAlwaysOff)
        
        # 账号列表容器
        self.accounts_container = QWidget()
        self.accounts_layout = QVBoxLayout()
        self.accounts_layout.setContentsMargins(0, 0, 0, 0)
        self.accounts_layout.setSpacing(5)
        self.accounts_container.setLayout(self.accounts_layout)
        
        self.scroll_area.setWidget(self.accounts_container)
        list_layout.addWidget(self.scroll_area)
        
        main_layout.addLayout(list_layout)
        
        # 状态栏
        self.status_bar = QStatusBar()
        self.setStatusBar(self.status_bar)
        self.status_bar.showMessage("就绪")
    
    def load_accounts(self):
        """加载账号列表"""
        # 清空现有组件
        for widget in self.account_widgets.values():
            widget.deleteLater()
        self.account_widgets.clear()
        
        # 加载账号
        accounts = self.account_manager.get_all_accounts()
        for account_id, account_info in accounts.items():
            self.add_account_widget(account_id, account_info)
        
        if not accounts:
            # 显示空状态
            empty_label = QLabel("暂无账号，点击\"添加账号\"开始使用")
            empty_label.setStyleSheet("""
                QLabel {
                    color: #999;
                    font-size: 16px;
                    text-align: center;
                    padding: 50px;
                }
            """)
            empty_label.setAlignment(Qt.AlignCenter)
            self.accounts_layout.addWidget(empty_label)
    
    def add_account_widget(self, account_id: str, account_info: Dict):
        """添加账号组件"""
        widget = AccountItemWidget(account_id, account_info)
        
        # 连接信号
        widget.login_requested.connect(self.login_account)
        widget.logout_requested.connect(self.logout_account)
        widget.edit_requested.connect(self.edit_account)
        widget.delete_requested.connect(self.delete_account)
        
        self.account_widgets[account_id] = widget
        self.accounts_layout.addWidget(widget)
    
    def add_account(self):
        """添加账号"""
        dialog = AddAccountDialog(self)
        if dialog.exec_() == QDialog.Accepted:
            account_info = dialog.get_account_info()
            
            # 验证必填字段
            if not account_info['account_name'] or not account_info['access_token']:
                QMessageBox.warning(self, "警告", "账号名称和Access Token不能为空")
                return
            
            # 生成账号ID
            account_id = f"account_{int(time.time())}"
            
            # 添加账号
            self.account_manager.add_account(account_id, account_info)
            
            # 刷新界面
            self.load_accounts()
            
            self.status_bar.showMessage(f"已添加账号: {account_info['account_name']}")
    
    def edit_account(self, account_id: str):
        """编辑账号"""
        account_info = self.account_manager.get_account(account_id)
        if not account_info:
            return
        
        dialog = AddAccountDialog(self, account_info)
        if dialog.exec_() == QDialog.Accepted:
            new_info = dialog.get_account_info()
            
            # 验证必填字段
            if not new_info['account_name'] or not new_info['access_token']:
                QMessageBox.warning(self, "警告", "账号名称和Access Token不能为空")
                return
            
            # 更新账号
            self.account_manager.update_account(account_id, new_info)
            
            # 刷新界面
            self.load_accounts()
            
            self.status_bar.showMessage(f"已更新账号: {new_info['account_name']}")
    
    def delete_account(self, account_id: str):
        """删除账号"""
        account_info = self.account_manager.get_account(account_id)
        if not account_info:
            return
        
        # 确认删除
        reply = QMessageBox.question(
            self, 
            "确认删除", 
            f"确定要删除账号\"{account_info.get('account_name', account_id)}\"吗？",
            QMessageBox.Yes | QMessageBox.No
        )
        
        if reply == QMessageBox.Yes:
            # 如果账号在线，先登出
            if account_info.get('status') == 'online':
                self.logout_account(account_id)
            
            # 删除账号
            self.account_manager.delete_account(account_id)
            
            # 刷新界面
            self.load_accounts()
            
            self.status_bar.showMessage(f"已删除账号: {account_info.get('account_name', account_id)}")
    
    def login_account(self, account_id: str):
        """登录账号"""
        account_info = self.account_manager.get_account(account_id)
        if not account_info:
            return
        
        try:
            self.status_bar.showMessage(f"正在登录账号: {account_info.get('account_name', account_id)}")
            
            # 这里应该调用实际的登录逻辑
            # 为了演示，我们模拟登录过程
            QTimer.singleShot(2000, lambda: self.on_login_success(account_id))
            
        except Exception as e:
            QMessageBox.critical(self, "登录失败", f"登录账号时发生错误: {e}")
            self.status_bar.showMessage("登录失败")
    
    def on_login_success(self, account_id: str):
        """登录成功回调"""
        account_info = self.account_manager.get_account(account_id)
        if account_info:
            # 更新状态
            self.account_manager.update_account(account_id, {'status': 'online', 'last_login': time.time()})
            
            # 更新界面
            if account_id in self.account_widgets:
                self.account_widgets[account_id].update_status('online')
            
            self.status_bar.showMessage(f"账号 {account_info.get('account_name', account_id)} 登录成功")
            
            # 启动该账号的定时任务
            self.start_account_tasks(account_id)
    
    def logout_account(self, account_id: str):
        """登出账号"""
        account_info = self.account_manager.get_account(account_id)
        if not account_info:
            return
        
        try:
            self.status_bar.showMessage(f"正在登出账号: {account_info.get('account_name', account_id)}")
            
            # 停止该账号的定时任务
            self.stop_account_tasks(account_id)
            
            # 更新状态
            self.account_manager.update_account(account_id, {'status': 'offline'})
            
            # 更新界面
            if account_id in self.account_widgets:
                self.account_widgets[account_id].update_status('offline')
            
            self.status_bar.showMessage(f"账号 {account_info.get('account_name', account_id)} 已登出")
            
        except Exception as e:
            QMessageBox.critical(self, "登出失败", f"登出账号时发生错误: {e}")
            self.status_bar.showMessage("登出失败")
    
    def start_account_tasks(self, account_id: str):
        """启动账号的定时任务"""
        # 这里应该启动该账号的定时任务
        # 每个账号独立运行，互不干扰
        print(f"启动账号 {account_id} 的定时任务")
        
        # 模拟启动任务
        # 实际实现中，这里应该创建独立的线程和任务管理器
        pass
    
    def stop_account_tasks(self, account_id: str):
        """停止账号的定时任务"""
        # 这里应该停止该账号的定时任务
        print(f"停止账号 {account_id} 的定时任务")
        
        # 模拟停止任务
        # 实际实现中，这里应该停止对应的线程和任务管理器
        pass


if __name__ == "__main__":
    app = QApplication(sys.argv)
    
    # 设置应用样式
    app.setStyle('Fusion')
    
    window = MultiAccountMainWindow()
    window.show()
    
    sys.exit(app.exec_())
