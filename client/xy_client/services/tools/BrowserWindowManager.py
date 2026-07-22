#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
浏览器窗口和进程管理器
确保每个账号只有一个浏览器窗口，支持多账号同时运行
"""

import os
import time
import psutil
import threading
import subprocess
import platform
from typing import Dict, List, Optional, Set
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from .BrowserPortManager import BrowserPortManager
from .SafeBrowserProcessManager import get_safe_process_manager, cleanup_safe_process_manager


class BrowserWindowManager:
    """浏览器窗口和进程管理器"""
    
    def __init__(self, account_id: str, browser_type: str = "chrome"):
        self.account_id = account_id
        self.browser_type = browser_type.lower()
        self.driver = None
        self._lock = threading.Lock()
        self._window_handles = set()
        self._browser_processes = set()
        self._user_data_dir = self._get_user_data_dir()
        self._debug_port = self._get_debug_port()
        
        # 浏览器进程名称映射
        self.browser_process_names = {
            'chrome': ['chrome.exe', 'chrome', 'chromedriver.exe', 'chromedriver'],
            'firefox': ['firefox.exe', 'firefox', 'geckodriver.exe', 'geckodriver']
        }
        
        print(f"✅ 浏览器窗口管理器初始化: 账号={account_id}, 浏览器={browser_type}")
        
        # 集成端口管理器
        self.port_manager = BrowserPortManager(account_id, browser_type)
        
        # 集成安全进程管理器
        self.safe_process_manager = get_safe_process_manager(account_id, browser_type)
    
    def _get_user_data_dir(self) -> str:
        """获取用户数据目录（兼容现有系统）"""
        if platform.system() == "Windows":
            return f"C:\\.temp\\9222\\{self.account_id}"
        else:
            return f"/tmp/9222/{self.account_id}"
    
    def _get_debug_port(self) -> int:
        """获取调试端口"""
        # 基于账号ID生成唯一端口，避免冲突
        base_port = 9000
        port_offset = hash(self.account_id) % 1000
        return base_port + port_offset
    
    def kill_existing_browser_processes(self):
        """终止现有的浏览器进程（只终止程序管理的进程）"""
        try:
            if self.safe_process_manager:
                # 使用安全进程管理器，只终止程序管理的进程
                return self.safe_process_manager.kill_managed_processes()
            else:
                print("⚠️ 安全进程管理器不可用，跳过进程终止")
                return False
        except Exception as e:
            print(f"❌ 终止浏览器进程异常: {e}")
            return False
    
    def ensure_single_window(self):
        """确保只有一个浏览器窗口（只操作属于当前账户端口的窗口，不操作其他账户的窗口）"""
        if not self.driver:
            return True
        
        try:
            with self._lock:
                # 关键修复1：严格验证 driver 是否连接到了当前账户的端口
                if self.port_manager:
                    current_port = self.port_manager.debug_port
                    # 严格验证 driver 是否连接到了当前账户的端口
                    driver_port = None
                    try:
                        command_executor = getattr(self.driver, 'command_executor', None)
                        if command_executor:
                            executor_url = str(command_executor._url) if hasattr(command_executor, '_url') else str(command_executor)
                            # 从URL中提取端口号（更精确的匹配）
                            import re
                            port_match = re.search(r':(\d+)(?:/|$)', executor_url)
                            if port_match:
                                driver_port = int(port_match.group(1))
                            
                            # 严格检查端口是否匹配
                            if driver_port != current_port:
                                # 端口不匹配，说明 driver 连接到了错误的端口（可能是其他账户的）
                                print(f"⚠️ [账户={self.account_id}] Driver端口不匹配: 期望端口={current_port}, 实际端口={driver_port}")
                                print(f"⚠️ [账户={self.account_id}] 跳过窗口清理，避免误关闭其他账户的窗口")
                                return False  # 不清理，避免误关闭其他账户的窗口
                            else:
                                # 端口验证通过
                                pass  # 继续执行清理
                        else:
                            print(f"⚠️ [账户={self.account_id}] 无法获取command_executor，跳过窗口清理（避免误关闭其他账户窗口）")
                            return False
                    except Exception as e:
                        print(f"⚠️ [账户={self.account_id}] 验证端口时异常: {e}，跳过窗口清理（避免误关闭其他账户窗口）")
                        return False
                    
                    # 关键修复2：再次验证 - 通过浏览器调试接口验证端口
                    # 确保窗口确实属于当前账户的端口
                    try:
                        import requests
                        debug_url = f'http://127.0.0.1:{current_port}/json'
                        response = requests.get(debug_url, timeout=2)
                        if response.status_code != 200:
                            print(f"⚠️ [账户={self.account_id}] 端口 {current_port} 的调试接口不可用，跳过窗口清理")
                            return False
                    except Exception as e:
                        print(f"⚠️ [账户={self.account_id}] 验证端口调试接口异常: {e}，跳过窗口清理")
                        return False
                    
                    # 使用端口管理器确保单标签页（已包含端口验证）
                    return self.port_manager.ensure_single_tab(self.driver)
                
                # 备用方案：原有的窗口管理逻辑（如果没有端口管理器）
                # 获取所有窗口句柄（这些窗口应该都属于当前账户的端口）
                window_handles = self.driver.window_handles
                if len(window_handles) <= 1:
                    return True
                
                print(f"🔄 [账户={self.account_id}, 端口={self._debug_port}] 检测到 {len(window_handles)} 个浏览器窗口，开始智能清理...")
                
                # 关键修复：优先保留已登录的窗口，避免关闭登录成功的窗口
                # 检查每个窗口的URL，找出已登录的窗口
                logged_in_window = None
                login_page_windows = []
                
                for handle in window_handles:
                    try:
                        self.driver.switch_to.window(handle)
                        # 使用超时保护获取URL
                        import threading
                        url_result = [None]
                        url_timeout = [False]
                        
                        def get_url():
                            try:
                                url_result[0] = self.driver.current_url.lower()
                            except Exception:
                                url_timeout[0] = True
                        
                        url_thread = threading.Thread(target=get_url, daemon=True)
                        url_thread.start()
                        url_thread.join(timeout=2)  # 最多等待2秒
                        
                        if not url_thread.is_alive() and not url_timeout[0] and url_result[0]:
                            current_url = url_result[0]
                            # 检查是否在登录页面
                            is_login_page = any(keyword in current_url for keyword in ['/member/login', '/login', '登录'])
                            if is_login_page:
                                login_page_windows.append(handle)
                                print(f"🔍 [账户={self.account_id}] 窗口 {handle} 在登录页面: {current_url}")
                            else:
                                # 不在登录页面，说明已登录
                                logged_in_window = handle
                                print(f"✅ [账户={self.account_id}] 窗口 {handle} 已登录: {current_url}")
                                break  # 找到已登录窗口，立即退出
                    except Exception as e:
                        print(f"⚠️ [账户={self.account_id}] 检查窗口 {handle} 的URL失败: {e}")
                        continue
                
                # 决定保留哪个窗口
                if logged_in_window:
                    # 优先保留已登录的窗口
                    main_window = logged_in_window
                    print(f"✅ [账户={self.account_id}] 优先保留已登录的窗口: {main_window}")
                else:
                    # 如果没有已登录的窗口，保留第一个窗口
                    main_window = window_handles[0]
                    print(f"⚠️ [账户={self.account_id}] 未找到已登录窗口，保留第一个窗口: {main_window}")
                
                # 关闭其他所有窗口（使用循环，每次重新获取窗口句柄，确保关闭成功）
                closed_count = 0
                max_attempts = 10  # 最多尝试10次，避免无限循环
                attempt = 0
                
                while attempt < max_attempts:
                    # 每次重新获取窗口句柄（因为关闭窗口后句柄会变化）
                    current_handles = self.driver.window_handles
                    if len(current_handles) <= 1:
                        # 只剩下一个窗口，清理完成
                        break
                    
                    # 找出需要关闭的窗口（除了主窗口外的所有窗口）
                    handles_to_close = [h for h in current_handles if h != main_window]
                    if not handles_to_close:
                        # 没有需要关闭的窗口，清理完成
                        break
                    
                    # 尝试关闭第一个需要关闭的窗口
                    handle_to_close = handles_to_close[0]
                    try:
                        # 先切换到要关闭的窗口
                        self.driver.switch_to.window(handle_to_close)
                        # 关闭窗口
                        self.driver.close()
                        closed_count += 1
                        print(f"✅ [账户={self.account_id}] 已关闭多余窗口: {handle_to_close}")
                        
                        # 验证窗口是否真的关闭了
                        import time
                        time.sleep(0.1)  # 短暂等待，让浏览器处理关闭操作
                        remaining_handles = self.driver.window_handles
                        if handle_to_close in remaining_handles:
                            print(f"⚠️ [账户={self.account_id}] 窗口 {handle_to_close} 关闭后仍在列表中，可能关闭失败")
                        else:
                            print(f"✅ [账户={self.account_id}] 窗口 {handle_to_close} 已成功关闭")
                    except Exception as e:
                        print(f"⚠️ [账户={self.account_id}] 关闭窗口 {handle_to_close} 失败: {e}")
                        # 即使关闭失败，也尝试继续关闭其他窗口
                        # 检查窗口是否还存在
                        try:
                            remaining_handles = self.driver.window_handles
                            if handle_to_close not in remaining_handles:
                                # 窗口已经不存在了（可能被其他方式关闭了）
                                closed_count += 1
                                print(f"✅ [账户={self.account_id}] 窗口 {handle_to_close} 已不存在（可能已被关闭）")
                        except:
                            pass
                    
                    attempt += 1
                
                # 切换回主窗口
                try:
                    # 重新获取窗口句柄，确保主窗口还在
                    final_handles = self.driver.window_handles
                    if main_window in final_handles:
                        self.driver.switch_to.window(main_window)
                        print(f"✅ [账户={self.account_id}] 已切换回主窗口: {main_window}")
                    elif final_handles:
                        # 主窗口不存在了，切换到第一个可用窗口
                        self.driver.switch_to.window(final_handles[0])
                        print(f"⚠️ [账户={self.account_id}] 主窗口不存在，已切换到第一个可用窗口: {final_handles[0]}")
                    else:
                        print(f"⚠️ [账户={self.account_id}] 没有可用窗口")
                except Exception as e:
                    print(f"⚠️ [账户={self.account_id}] 切换回主窗口失败: {e}")
                    # 如果切换失败，尝试切换到第一个可用窗口
                    try:
                        remaining_handles = self.driver.window_handles
                        if remaining_handles:
                            self.driver.switch_to.window(remaining_handles[0])
                            print(f"✅ [账户={self.account_id}] 已切换到第一个可用窗口: {remaining_handles[0]}")
                    except Exception as e2:
                        print(f"❌ [账户={self.account_id}] 无法切换到任何窗口: {e2}")
                
                # 最终验证：检查是否真的只剩下一个窗口
                try:
                    final_handles = self.driver.window_handles
                    if len(final_handles) > 1:
                        print(f"⚠️ [账户={self.account_id}] 清理后仍有 {len(final_handles)} 个窗口，清理可能未完全成功")
                        print(f"   [账户={self.account_id}] 剩余窗口: {final_handles}")
                    else:
                        print(f"✅ [账户={self.account_id}] 清理成功，当前只有 {len(final_handles)} 个窗口")
                except Exception as e:
                    print(f"⚠️ [账户={self.account_id}] 验证清理结果失败: {e}")
                
                # 清理后检查当前窗口状态
                try:
                    final_url = self.driver.current_url.lower()
                    is_login_page = any(keyword in final_url for keyword in ['/member/login', '/login', '登录'])
                    if is_login_page:
                        print(f"⚠️ [账户={self.account_id}] 清理后当前窗口仍在登录页面，可能需要重新登录")
                    else:
                        print(f"✅ [账户={self.account_id}] 清理后当前窗口已登录: {final_url}")
                except Exception as e:
                    print(f"⚠️ [账户={self.account_id}] 检查清理后窗口状态失败: {e}")
                
                if closed_count > 0:
                    print(f"✅ [账户={self.account_id}, 端口={self._debug_port}] 已关闭 {closed_count} 个多余窗口，确保只有一个窗口")
                
                return True
                
        except Exception as e:
            print(f"❌ [账户={self.account_id}] 确保单窗口异常: {e}")
            return False
    
    def connect_to_browser(self):
        """智能连接到浏览器"""
        try:
            with self._lock:
                # 使用端口管理器连接
                if self.port_manager:
                    self.driver = self.port_manager.connect_to_browser()
                    if self.driver:
                        print(f"✅ 成功连接到浏览器，端口: {self.port_manager.debug_port}")
                        return self.driver
                
                print(f"❌ 无法连接到浏览器")
                return None
                
        except Exception as e:
            print(f"❌ 连接浏览器异常: {e}")
            return None
    
    def get_connection_status(self):
        """获取连接状态"""
        try:
            status = {
                'account_id': self.account_id,
                'browser_type': self.browser_type,
                'debug_port': self._debug_port,
                'user_data_dir': self._user_data_dir,
                'driver_active': self.driver is not None,
                'window_count': len(self.driver.window_handles) if self.driver else 0
            }
            
            # 添加端口管理器状态
            if self.port_manager:
                port_status = self.port_manager.get_connection_status()
                status.update(port_status)
            
            # 添加进程信息
            process_info = self.monitor_browser_processes()
            status.update(process_info)
            
            return status
            
        except Exception as e:
            print(f"❌ 获取连接状态异常: {e}")
            return {
                'account_id': self.account_id,
                'browser_type': self.browser_type,
                'error': str(e)
            }
    
    def monitor_browser_processes(self):
        """监控浏览器进程数量（区分程序管理和用户进程）"""
        try:
            if self.safe_process_manager:
                # 获取程序管理的进程
                managed_processes = self.safe_process_manager.get_managed_processes()
                managed_count = len(managed_processes)
                
                # 获取用户浏览器进程（仅监控，不操作）
                user_processes = self.safe_process_manager.get_user_browser_processes()
                user_count = len(user_processes)
                
                # 计算内存使用
                total_memory = 0
                for proc_info in managed_processes:
                    try:
                        proc = psutil.Process(proc_info['pid'])
                        total_memory += proc.memory_info().rss
                    except (psutil.NoSuchProcess, psutil.AccessDenied):
                        continue
                
                browser_reachable = self.safe_process_manager.is_browser_running()
                
                return {
                    'managed_process_count': managed_count,
                    'user_process_count': user_count,
                    'total_process_count': managed_count + user_count,
                    'memory_usage': total_memory / 1024 / 1024,  # MB
                    'browser_reachable': browser_reachable,
                    'is_healthy': browser_reachable,
                    'managed_processes': managed_processes,
                    'user_processes': user_processes
                }
            else:
                # 备用方案：原有的监控逻辑
                browser_count = 0
                total_memory = 0
                
                for proc in psutil.process_iter(['pid', 'name', 'memory_info']):
                    try:
                        proc_name = proc.info['name'].lower()
                        if any(browser_name in proc_name for browser_name in self.browser_process_names[self.browser_type]):
                            browser_count += 1
                            total_memory += proc.info['memory_info'].rss
                    except (psutil.NoSuchProcess, psutil.AccessDenied):
                        continue
                
                return {
                    'managed_process_count': browser_count,
                    'user_process_count': 0,
                    'total_process_count': browser_count,
                    'memory_usage': total_memory / 1024 / 1024,  # MB
                    'browser_reachable': True,
                    'is_healthy': True
                }
            
        except Exception as e:
            print(f"❌ 监控浏览器进程异常: {e}")
            return {'managed_process_count': 0, 'user_process_count': 0, 'total_process_count': 0, 'memory_usage': 0, 'is_healthy': False}
    
    def cleanup_browser_resources(self):
        """清理浏览器资源"""
        try:
            # 关闭所有窗口
            if self.driver:
                try:
                    self.driver.quit()
                except:
                    pass
                self.driver = None
            
            # 清理端口管理器
            if self.port_manager:
                try:
                    self.port_manager.cleanup()
                    print(f"✅ 端口管理器清理完成: {self.account_id}")
                except Exception as e:
                    print(f"⚠️ 端口管理器清理失败: {e}")
            
            # 清理安全进程管理器（只终止程序管理的进程）
            if self.safe_process_manager:
                try:
                    self.safe_process_manager.cleanup()
                    print(f"✅ 安全进程管理器清理完成: {self.account_id}")
                except Exception as e:
                    print(f"⚠️ 安全进程管理器清理失败: {e}")
            
            # 清理用户数据目录（可选）
            try:
                if os.path.exists(self._user_data_dir):
                    import shutil
                    shutil.rmtree(self._user_data_dir, ignore_errors=True)
                    print(f"✅ 已清理用户数据目录: {self._user_data_dir}")
            except Exception as e:
                print(f"⚠️ 清理用户数据目录失败: {e}")
            
            print(f"✅ 浏览器资源清理完成: {self.account_id}")
            
        except Exception as e:
            print(f"❌ 清理浏览器资源异常: {e}")
    
    def get_browser_status(self):
        """获取浏览器状态"""
        try:
            status = {
                'account_id': self.account_id,
                'browser_type': self.browser_type,
                'debug_port': self._debug_port,
                'user_data_dir': self._user_data_dir,
                'driver_active': self.driver is not None,
                'window_count': len(self.driver.window_handles) if self.driver else 0
            }
            
            # 添加进程信息
            process_info = self.monitor_browser_processes()
            status.update(process_info)
            
            return status
            
        except Exception as e:
            print(f"❌ 获取浏览器状态异常: {e}")
            return {
                'account_id': self.account_id,
                'browser_type': self.browser_type,
                'error': str(e)
            }


class MultiAccountBrowserManager:
    """多账号浏览器管理器"""
    
    def __init__(self):
        self._managers: Dict[str, BrowserWindowManager] = {}
        self._lock = threading.Lock()
        self._monitoring = False
        self._monitor_thread = None
    
    def get_manager(self, account_id: str, browser_type: str = "chrome") -> BrowserWindowManager:
        """获取指定账号的浏览器管理器"""
        with self._lock:
            if account_id not in self._managers:
                self._managers[account_id] = BrowserWindowManager(account_id, browser_type)
            return self._managers[account_id]
    
    def cleanup_account(self, account_id: str):
        """清理指定账号的浏览器资源"""
        with self._lock:
            if account_id in self._managers:
                self._managers[account_id].cleanup_browser_resources()
                del self._managers[account_id]
                print(f"✅ 已清理账号 {account_id} 的浏览器资源")
    
    def cleanup_all_accounts(self):
        """清理所有账号的浏览器资源"""
        with self._lock:
            for account_id in list(self._managers.keys()):
                self.cleanup_account(account_id)
            print("✅ 已清理所有账号的浏览器资源")
    
    def start_monitoring(self):
        """开始监控所有账号的浏览器状态"""
        if self._monitoring:
            return
        
        self._monitoring = True
        self._monitor_thread = threading.Thread(target=self._monitor_loop, daemon=True)
        self._monitor_thread.start()
        print("✅ 多账号浏览器监控已启动")
    
    def stop_monitoring(self):
        """停止监控"""
        self._monitoring = False
        if self._monitor_thread:
            self._monitor_thread.join(timeout=5)
        print("✅ 多账号浏览器监控已停止")
    
    def _monitor_loop(self):
        """监控循环"""
        while self._monitoring:
            try:
                with self._lock:
                    for account_id, manager in self._managers.items():
                        try:
                            # 确保单窗口
                            manager.ensure_single_window()
                            
                            # 监控进程状态
                            status = manager.monitor_browser_processes()
                            if not status['is_healthy']:
                                print(f"⚠️ 账号 {account_id} 浏览器连接不可用，保留进程并等待重新登录")
                                
                        except Exception as e:
                            print(f"❌ 监控账号 {account_id} 异常: {e}")
                
                time.sleep(30)  # 每30秒检查一次
                
            except Exception as e:
                print(f"❌ 监控循环异常: {e}")
                time.sleep(60)  # 异常时等待更长时间
    
    def get_all_status(self):
        """获取所有账号的浏览器状态"""
        with self._lock:
            status = {}
            for account_id, manager in self._managers.items():
                status[account_id] = manager.get_browser_status()
            return status


# 全局多账号浏览器管理器
_global_browser_manager = None

def get_browser_manager() -> MultiAccountBrowserManager:
    """获取全局多账号浏览器管理器"""
    global _global_browser_manager
    if _global_browser_manager is None:
        _global_browser_manager = MultiAccountBrowserManager()
        _global_browser_manager.start_monitoring()
    return _global_browser_manager

def get_account_browser_manager(account_id: str, browser_type: str = "chrome") -> BrowserWindowManager:
    """获取指定账号的浏览器管理器"""
    manager = get_browser_manager()
    return manager.get_manager(account_id, browser_type)

def cleanup_account_browser(account_id: str):
    """清理指定账号的浏览器资源"""
    manager = get_browser_manager()
    manager.cleanup_account(account_id)

def cleanup_all_browsers():
    """清理所有浏览器资源"""
    manager = get_browser_manager()
    manager.cleanup_all_accounts()
    manager.stop_monitoring()

def get_all_browser_status():
    """获取所有浏览器状态"""
    manager = get_browser_manager()
    return manager.get_all_status()


if __name__ == "__main__":
    # 测试代码
    print("🧪 测试浏览器窗口管理器...")
    
    # 测试单账号管理器
    manager = BrowserWindowManager("test_account", "chrome")
    
    # 测试进程清理
    print("🔍 测试进程清理...")
    manager.kill_existing_browser_processes()
    
    # 测试状态获取
    print("🔍 测试状态获取...")
    status = manager.get_browser_status()
    print(f"浏览器状态: {status}")
    
    # 测试多账号管理器
    print("🔍 测试多账号管理器...")
    multi_manager = MultiAccountBrowserManager()
    
    # 获取不同账号的管理器
    manager1 = multi_manager.get_manager("account1", "chrome")
    manager2 = multi_manager.get_manager("account2", "firefox")
    
    # 获取所有状态
    all_status = multi_manager.get_all_status()
    print(f"所有账号状态: {all_status}")
    
    # 清理
    multi_manager.cleanup_all_accounts()
    
    print("✅ 测试完成")
