#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
多账号进程管理器
支持同一台电脑运行多个账号，智能识别和管理进程
"""

import os
import sys
import time
import psutil
import threading
import hashlib
import json
from typing import Dict, List, Optional, Set
from pathlib import Path


class MultiAccountProcessManager:
    """多账号进程管理器"""
    
    def __init__(self):
        self._lock = threading.Lock()
        self._running_accounts: Dict[str, Dict] = {}
        self._process_lock_file = self._get_process_lock_file()
        self._account_config_file = self._get_account_config_file()
        
        # 加载现有账号配置
        self._load_account_configs()
        
        print("✅ 多账号进程管理器初始化完成")
    
    def _get_process_lock_file(self) -> str:
        """获取进程锁文件路径"""
        if sys.platform == "win32":
            return os.path.join(os.environ.get('TEMP', 'C:\\temp'), 'lucky_client_process.lock')
        else:
            return '/tmp/lucky_client_process.lock'
    
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
    
    def _get_account_id_from_config(self) -> str:
        """从配置文件获取账号ID"""
        try:
            # 尝试从命令行参数获取
            if len(sys.argv) > 1:
                for arg in sys.argv[1:]:
                    if arg.startswith('--account='):
                        return arg.split('=')[1]
                    elif arg.startswith('--config='):
                        config_file = arg.split('=')[1]
                        # 从配置文件名提取账号ID
                        config_name = os.path.basename(config_file)
                        if config_name.endswith('.json'):
                            return config_name[:-5]  # 去掉.json后缀
                        return config_name
            
            # 尝试从环境变量获取
            account_id = os.environ.get('LUCKY_ACCOUNT_ID')
            if account_id:
                return account_id
            
            # 尝试从当前目录名获取
            current_dir = os.path.basename(os.getcwd())
            if current_dir and current_dir != 'python-tools':
                return current_dir
            
            # 默认账号ID
            return "default_account"
            
        except Exception as e:
            print(f"⚠️ 获取账号ID失败: {e}")
            return "default_account"
    
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
                # 获取当前账号ID
                account_id = self._get_account_id_from_config()
                
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
                                    
                                    # 询问用户如何处理
                                    choice = self._handle_existing_process(account_id, existing_pid, current_pid)
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
    
    def _handle_existing_process(self, account_id: str, existing_pid: int, current_pid: int) -> str:
        """处理已存在的进程"""
        try:
            print(f"\n🔍 检测到账号 {account_id} 已有进程在运行:")
            print(f"   现有进程: PID {existing_pid}")
            print(f"   当前进程: PID {current_pid}")
            print("\n请选择处理方式:")
            print("1. 关闭旧进程，启动新进程")
            print("2. 退出当前进程，继续使用旧进程")
            print("3. 强制退出所有进程")
            
            # 在GUI环境中，可以使用消息框
            try:
                from PyQt5.QtWidgets import QMessageBox, QApplication
                app = QApplication.instance()
                if app:
                    msg = QMessageBox()
                    msg.setWindowTitle("多账号进程冲突")
                    msg.setText(f"账号 {account_id} 已有程序在运行")
                    msg.setInformativeText("是否要关闭旧程序并启动新程序?")
                    
                    close_btn = msg.addButton("关闭旧程序", QMessageBox.ActionRole)
                    exit_btn = msg.addButton("退出当前程序", QMessageBox.ActionRole)
                    
                    msg.exec_()
                    
                    if msg.clickedButton() == close_btn:
                        return "close_old"
                    elif msg.clickedButton() == exit_btn:
                        return "exit"
                    elif msg.clickedButton() == force_btn:
                        return "force_exit"
            except ImportError:
                pass
            
            # 命令行环境，使用默认选择
            print("⚠️ 无法显示GUI，使用默认选择: 关闭旧进程")
            return "close_old"
            
        except Exception as e:
            print(f"⚠️ 处理现有进程异常: {e}")
            return "close_old"
    
    def _close_existing_process(self, pid: int):
        """关闭现有进程"""
        try:
            process = psutil.Process(pid)
            print(f"🔄 正在关闭进程 PID {pid}...")
            
            # 尝试优雅关闭
            process.terminate()
            
            # 等待进程关闭
            try:
                process.wait(timeout=5)
                print(f"✅ 进程 PID {pid} 已关闭")
            except psutil.TimeoutExpired:
                # 强制关闭
                process.kill()
                print(f"⚠️ 强制关闭进程 PID {pid}")
                
        except psutil.NoSuchProcess:
            print(f"ℹ️ 进程 PID {pid} 已不存在")
        except Exception as e:
            print(f"❌ 关闭进程异常: {e}")
    
    def _register_current_process(self, account_id: str, fingerprint: str, pid: int):
        """注册当前进程"""
        try:
            self._running_accounts[account_id] = {
                'pid': pid,
                'fingerprint': fingerprint,
                'start_time': time.time(),
                'executable': self._get_executable_name(),
                'working_dir': os.getcwd(),
                'account_id': account_id
            }
            
            self._save_account_configs()
            print(f"✅ 账号 {account_id} 已注册 (PID: {pid}, 指纹: {fingerprint})")
            
        except Exception as e:
            print(f"❌ 注册进程异常: {e}")
    
    def unregister_account(self, account_id: str):
        """注销账号"""
        try:
            with self._lock:
                if account_id in self._running_accounts:
                    del self._running_accounts[account_id]
                    self._save_account_configs()
                    print(f"✅ 账号 {account_id} 已注销")
                    
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
    
    def cleanup_all_accounts(self):
        """清理所有账号"""
        try:
            with self._lock:
                for account_id, info in self._running_accounts.items():
                    try:
                        pid = info.get('pid')
                        if pid:
                            process = psutil.Process(pid)
                            if process.is_running():
                                print(f"🔄 清理账号 {account_id} (PID: {pid})")
                                process.terminate()
                    except Exception as e:
                        print(f"⚠️ 清理账号 {account_id} 异常: {e}")
                
                self._running_accounts = {}
                self._save_account_configs()
                print("✅ 所有账号已清理")
                
        except Exception as e:
            print(f"❌ 清理所有账号异常: {e}")
    
    def get_account_status(self) -> Dict:
        """获取账号状态"""
        try:
            account_id = self._get_account_id_from_config()
            running_accounts = self.get_running_accounts()
            
            status = {
                'current_account': account_id,
                'is_registered': account_id in running_accounts,
                'total_accounts': len(running_accounts),
                'running_accounts': list(running_accounts.keys()),
                'current_pid': os.getpid()
            }
            
            if account_id in running_accounts:
                status['account_info'] = running_accounts[account_id]
            
            return status
            
        except Exception as e:
            return {
                'current_account': 'unknown',
                'error': str(e)
            }


# 全局进程管理器
_global_process_manager = None

def get_process_manager() -> MultiAccountProcessManager:
    """获取全局进程管理器"""
    global _global_process_manager
    if _global_process_manager is None:
        _global_process_manager = MultiAccountProcessManager()
    return _global_process_manager

def check_account_registration() -> Optional[str]:
    """检查并注册当前账号"""
    manager = get_process_manager()
    return manager.check_and_registration()

def unregister_current_account():
    """注销当前账号"""
    manager = get_process_manager()
    account_id = manager._get_account_id_from_config()
    manager.unregister_account(account_id)

def get_current_account_status():
    """获取当前账号状态"""
    manager = get_process_manager()
    return manager.get_account_status()


if __name__ == "__main__":
    # 测试代码
    print("🧪 测试多账号进程管理器...")
    
    # 创建进程管理器
    manager = MultiAccountProcessManager()
    
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
