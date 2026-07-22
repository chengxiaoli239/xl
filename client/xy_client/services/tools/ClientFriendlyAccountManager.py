#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
客户友好的多账号目录管理方案
基于目录隔离，自动识别账号，无需命令行操作
"""

import os
import sys
import time
import psutil
import threading
import hashlib
import json
import ctypes
from typing import Dict, List, Optional, Set
from pathlib import Path


class ClientFriendlyAccountManager:
    """客户友好的账号管理器"""
    
    def __init__(self):
        self._lock = threading.Lock()
        self._running_accounts: Dict[str, Dict] = {}
        self._account_config_file = self._get_account_config_file()
        self._current_account_id = None
        self._instance_mutex = None
        self._instance_lock_file = None
        
        # 加载现有账号配置
        self._load_account_configs()
        
        # 自动识别当前账号
        self._current_account_id = self._auto_detect_account()
        
        print(f"✅ 客户友好账号管理器初始化完成，当前账号: {self._current_account_id}")
    
    def _get_account_config_file(self) -> str:
        """获取账号配置文件路径"""
        if sys.platform == "win32":
            return os.path.join(os.environ.get('TEMP', 'C:\\temp'), 'lucky_client_accounts.json')
        else:
            return '/tmp/lucky_client_accounts.json'
    
    def _load_account_configs(self):
        """加载账号配置"""
        try:
            if os.path.exists(self._account_config_file):
                with open(self._account_config_file, 'r', encoding='utf-8') as f:
                    self._running_accounts = json.load(f)
                print(f"✅ 加载了 {len(self._running_accounts)} 个账号配置")
        except Exception as e:
            print(f"⚠️ 加载账号配置失败: {e}")
            self._running_accounts = {}
    
    def _save_account_configs(self):
        """保存账号配置"""
        try:
            with open(self._account_config_file, 'w', encoding='utf-8') as f:
                json.dump(self._running_accounts, f, ensure_ascii=False, indent=2)
        except Exception as e:
            print(f"⚠️ 保存账号配置失败: {e}")
    
    def _auto_detect_account(self) -> str:
        """自动检测当前账号"""
        try:
            # The authenticated account key is authoritative. Directory-based
            # detection cannot distinguish multiple accounts using one EXE.
            env_account = (
                os.environ.get('LUCKY5_ACCOUNT_ID')
                or os.environ.get('LUCKY5_ACCOUNT_KEY')
                or os.environ.get('LUCKY_ACCOUNT_ID')
            )
            if env_account:
                print(f"🔍 从登录信息检测到账号: {env_account}")
                return env_account

            # 方法1: 从当前目录名检测
            current_dir = os.path.basename(os.getcwd())
            if current_dir and current_dir not in ['python-tools', 'xy_client', '']:
                print(f"🔍 从目录名检测到账号: {current_dir}")
                return current_dir
            
            # 方法2: 从父目录名检测
            parent_dir = os.path.basename(os.path.dirname(os.getcwd()))
            if parent_dir and parent_dir not in ['python-tools', 'xy_client', '']:
                print(f"🔍 从父目录名检测到账号: {parent_dir}")
                return parent_dir
            
            # 方法3: 从可执行文件所在目录检测
            if getattr(sys, 'frozen', False):
                exe_dir = os.path.dirname(sys.executable)
                exe_dir_name = os.path.basename(exe_dir)
                if exe_dir_name and exe_dir_name not in ['python-tools', 'xy_client', '']:
                    print(f"🔍 从exe目录检测到账号: {exe_dir_name}")
                    return exe_dir_name
            
            # 方法4: 从配置文件检测
            config_account = self._detect_from_config_files()
            if config_account:
                print(f"🔍 从配置文件检测到账号: {config_account}")
                return config_account
            
            # 默认账号
            default_account = "default_account"
            print(f"⚠️ 无法自动检测账号，使用默认账号: {default_account}")
            return default_account
            
        except Exception as e:
            print(f"❌ 自动检测账号异常: {e}")
            return "default_account"
    
    def _detect_from_config_files(self) -> Optional[str]:
        """从配置文件检测账号"""
        try:
            # 查找配置文件
            config_files = [
                'config.json',
                'account.json',
                'settings.json',
                'lucky_config.json'
            ]
            
            for config_file in config_files:
                if os.path.exists(config_file):
                    try:
                        with open(config_file, 'r', encoding='utf-8') as f:
                            config = json.load(f)
                        
                        # 从配置中提取账号ID
                        account_id = config.get('account_id') or config.get('account') or config.get('username')
                        if account_id:
                            return account_id
                    except Exception:
                        continue
            
            return None
            
        except Exception:
            return None
    
    def _generate_account_fingerprint(self, account_id: str) -> str:
        """生成账号指纹"""
        try:
            # 基于账号ID、可执行文件路径、工作目录生成指纹
            fingerprint_data = f"{account_id}_{sys.executable}_{os.getcwd()}"
            return hashlib.md5(fingerprint_data.encode()).hexdigest()[:8]
        except Exception:
            return account_id[:8]
    
    def check_and_register_account(self) -> Optional[str]:
        """检查并注册当前账号"""
        try:
            with self._lock:
                account_id = self._current_account_id
                if not account_id:
                    print("❌ 无法确定账号ID")
                    return None

                if not self._acquire_instance_lock(account_id):
                    self._show_duplicate_account_message(account_id)
                    return None
                
                # 生成账号指纹
                fingerprint = self._generate_account_fingerprint(account_id)
                
                # 获取当前进程信息
                current_pid = os.getpid()
                current_process = psutil.Process(current_pid)
                
                # 检查是否已有相同账号在运行
                if account_id in self._running_accounts:
                    existing_info = self._running_accounts[account_id]
                    existing_pid = existing_info.get('pid')
                    
                    # 检查现有进程是否还在运行
                    if existing_pid:
                        try:
                            existing_process = psutil.Process(existing_pid)
                            if existing_process.is_running():
                                # 进程还在运行，检查是否是同一个程序
                                if existing_process.name() == current_process.name():
                                    print(f"⚠️ 账号 {account_id} 已在运行 (PID: {existing_pid})")
                                    
                                    # 客户友好处理：自动关闭旧进程
                                    choice = self._handle_existing_process_client_friendly(account_id, existing_pid, current_pid)
                                    if choice == "close_old":
                                        self._close_existing_process(existing_pid)
                                        # 注册新进程
                                        self._register_current_process(account_id, fingerprint, current_pid)
                                        return account_id
                                    elif choice == "exit":
                                        return None
                                    else:  # "continue_old"
                                        return None
                        except psutil.NoSuchProcess:
                            # 进程不存在了，清理记录
                            del self._running_accounts[account_id]
                
                # 注册当前进程
                self._register_current_process(account_id, fingerprint, current_pid)
                return account_id
                
        except Exception as e:
            print(f"❌ 检查账号注册异常: {e}")
            return None

    def _acquire_instance_lock(self, account_id: str) -> bool:
        """Keep one process per account while allowing different accounts."""
        if self._instance_mutex is not None or self._instance_lock_file is not None:
            return True

        lock_name = hashlib.sha256(account_id.encode('utf-8')).hexdigest()
        if sys.platform == 'win32':
            kernel32 = ctypes.WinDLL('kernel32', use_last_error=True)
            kernel32.CreateMutexW.argtypes = [ctypes.c_void_p, ctypes.c_bool, ctypes.c_wchar_p]
            kernel32.CreateMutexW.restype = ctypes.c_void_p
            kernel32.CloseHandle.argtypes = [ctypes.c_void_p]
            kernel32.CloseHandle.restype = ctypes.c_bool
            ctypes.set_last_error(0)
            handle = kernel32.CreateMutexW(None, False, 'Local\\Lucky5_' + lock_name)
            if not handle:
                raise ctypes.WinError()
            if ctypes.get_last_error() == 183:  # ERROR_ALREADY_EXISTS
                kernel32.CloseHandle(handle)
                return False
            self._instance_mutex = handle
            return True

        try:
            import fcntl
            lock_path = os.path.join('/tmp', 'lucky5_' + lock_name + '.lock')
            lock_file = open(lock_path, 'a+', encoding='utf-8')
            fcntl.flock(lock_file.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
            self._instance_lock_file = lock_file
            return True
        except (ImportError, OSError):
            return False

    @staticmethod
    def _show_duplicate_account_message(account_id: str):
        print(f"⚠️ 账号 {account_id} 已在运行，拒绝重复启动")
        try:
            from PyQt5.QtWidgets import QMessageBox
            QMessageBox.warning(None, '账号已运行', '所选账号已经在本机运行。')
        except Exception:
            pass

    def _release_instance_lock(self):
        if self._instance_mutex is not None and sys.platform == 'win32':
            try:
                kernel32 = ctypes.WinDLL('kernel32', use_last_error=True)
                kernel32.CloseHandle.argtypes = [ctypes.c_void_p]
                kernel32.CloseHandle.restype = ctypes.c_bool
                kernel32.CloseHandle(ctypes.c_void_p(self._instance_mutex))
            except Exception:
                pass
            self._instance_mutex = None
        if self._instance_lock_file is not None:
            try:
                self._instance_lock_file.close()
            except Exception:
                pass
            self._instance_lock_file = None
    
    def _handle_existing_process_client_friendly(self, account_id: str, existing_pid: int, current_pid: int) -> str:
        """客户友好的进程冲突处理"""
        try:
            print(f"\n🔍 检测到账号 {account_id} 已有程序在运行")
            print(f"   现有进程: PID {existing_pid}")
            print(f"   当前进程: PID {current_pid}")
            
            # 在GUI环境中，显示友好的消息框
            try:
                from PyQt5.QtWidgets import QMessageBox, QApplication
                app = QApplication.instance()
                if app:
                    msg = QMessageBox()
                    msg.setWindowTitle("程序启动提示")
                    msg.setText(f"账号 {account_id} 已有程序在运行")
                    msg.setInformativeText("是否要关闭旧程序并启动新程序？")
                    msg.setStandardButtons(QMessageBox.Yes | QMessageBox.No)
                    msg.setDefaultButton(QMessageBox.Yes)
                    
                    # 设置按钮文本
                    msg.button(QMessageBox.Yes).setText("关闭旧程序")
                    msg.button(QMessageBox.No).setText("退出当前程序")
                    
                    result = msg.exec_()
                    
                    if result == QMessageBox.Yes:
                        return "close_old"
                    else:
                        return "exit"
            except ImportError:
                pass
            
            # 非GUI环境，使用默认选择
            print("⚠️ 无法显示GUI，自动选择: 关闭旧程序")
            return "close_old"
            
        except Exception as e:
            print(f"⚠️ 处理现有进程异常: {e}")
            return "close_old"
    
    def _close_existing_process(self, pid: int):
        """关闭现有进程"""
        try:
            process = psutil.Process(pid)
            print(f"🔄 正在关闭旧程序 PID {pid}...")
            
            # 尝试优雅关闭
            process.terminate()
            
            # 等待进程关闭
            try:
                process.wait(timeout=5)
                print(f"✅ 旧程序 PID {pid} 已关闭")
            except psutil.TimeoutExpired:
                # 强制关闭
                process.kill()
                print(f"⚠️ 强制关闭旧程序 PID {pid}")
                
        except psutil.NoSuchProcess:
            print(f"ℹ️ 旧程序 PID {pid} 已不存在")
        except Exception as e:
            print(f"❌ 关闭旧程序异常: {e}")
    
    def _register_current_process(self, account_id: str, fingerprint: str, pid: int):
        """注册当前进程"""
        try:
            self._running_accounts[account_id] = {
                'pid': pid,
                'fingerprint': fingerprint,
                'start_time': time.time(),
                'executable': self._get_executable_name(),
                'working_dir': os.getcwd(),
                'account_id': account_id,
                'auto_detected': True
            }
            
            self._save_account_configs()
            print(f"✅ 账号 {account_id} 已注册 (PID: {pid}, 指纹: {fingerprint})")
            
        except Exception as e:
            print(f"❌ 注册进程异常: {e}")
    
    def _get_executable_name(self) -> str:
        """获取可执行文件名"""
        try:
            if getattr(sys, 'frozen', False):
                # 打包后的可执行文件
                return os.path.basename(sys.executable)
            else:
                # Python脚本
                return os.path.basename(sys.argv[0])
        except Exception:
            return "lucky_client.exe"
    
    def unregister_account(self, account_id: str):
        """注销账号"""
        try:
            with self._lock:
                if account_id in self._running_accounts:
                    del self._running_accounts[account_id]
                    self._save_account_configs()
                    print(f"✅ 账号 {account_id} 已注销")
                if account_id == self._current_account_id:
                    self._release_instance_lock()
                    
        except Exception as e:
            print(f"❌ 注销账号异常: {e}")
    
    def get_running_accounts(self) -> Dict[str, Dict]:
        """获取正在运行的账号"""
        try:
            with self._lock:
                # 清理已不存在的进程
                active_accounts = {}
                for account_id, info in self._running_accounts.items():
                    try:
                        pid = info.get('pid')
                        if pid and psutil.Process(pid).is_running():
                            active_accounts[account_id] = info
                        else:
                            print(f"ℹ️ 清理已停止的账号: {account_id}")
                    except psutil.NoSuchProcess:
                        print(f"ℹ️ 清理已停止的账号: {account_id}")
                
                # 更新配置
                if len(active_accounts) != len(self._running_accounts):
                    self._running_accounts = active_accounts
                    self._save_account_configs()
                
                return active_accounts
                
        except Exception as e:
            print(f"❌ 获取运行账号异常: {e}")
            return {}
    
    def get_account_status(self) -> Dict:
        """获取账号状态"""
        try:
            running_accounts = self.get_running_accounts()
            
            status = {
                'current_account': self._current_account_id,
                'is_registered': self._current_account_id in running_accounts,
                'total_accounts': len(running_accounts),
                'running_accounts': list(running_accounts.keys()),
                'current_pid': os.getpid(),
                'auto_detected': True
            }
            
            if self._current_account_id in running_accounts:
                status['account_info'] = running_accounts[self._current_account_id]
            
            return status
            
        except Exception as e:
            return {
                'current_account': 'unknown',
                'error': str(e)
            }


# 全局账号管理器
_global_account_manager = None

def get_account_manager() -> ClientFriendlyAccountManager:
    """获取全局账号管理器"""
    global _global_account_manager
    if _global_account_manager is None:
        _global_account_manager = ClientFriendlyAccountManager()
    return _global_account_manager

def check_account_registration() -> Optional[str]:
    """检查并注册当前账号"""
    manager = get_account_manager()
    return manager.check_and_register_account()

def unregister_current_account():
    """注销当前账号"""
    manager = get_account_manager()
    if manager._current_account_id:
        manager.unregister_account(manager._current_account_id)

def get_current_account_status():
    """获取当前账号状态"""
    manager = get_account_manager()
    return manager.get_account_status()


if __name__ == "__main__":
    # 测试代码
    print("🧪 测试客户友好账号管理器...")
    
    # 创建账号管理器
    manager = ClientFriendlyAccountManager()
    
    # 检查并注册账号
    account_id = manager.check_and_register_account()
    if account_id:
        print(f"✅ 账号注册成功: {account_id}")
        
        # 获取状态
        status = manager.get_account_status()
        print(f"账号状态: {status}")
        
        # 获取所有运行账号
        running_accounts = manager.get_running_accounts()
        print(f"运行中的账号: {list(running_accounts.keys())}")
        
        # 注销账号
        manager.unregister_account(account_id)
        print(f"✅ 账号注销完成: {account_id}")
    else:
        print("❌ 账号注册失败")
    
    print("✅ 测试完成")
