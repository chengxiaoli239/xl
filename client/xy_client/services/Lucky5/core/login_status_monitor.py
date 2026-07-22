"""
登录状态监控器
每隔1分钟请求一次用户信息接口，检查登录状态并保持登录状态
"""

import time
import threading
from typing import Optional
from xy_client.services.Lucky5.core.platform_api import check_login_status_by_api
from xy_client.services.Lucky5.utils.log_optimizer import optimized_print


class LoginStatusMonitor:
    """登录状态监控器 - 定期检查登录状态"""
    
    def __init__(self, main_window):
        """
        初始化登录状态监控器
        
        Args:
            main_window: 主窗口实例
        """
        self.main_window = main_window
        self._monitor_thread = None
        self._stop_event = threading.Event()
        self._check_interval = 60  # 1分钟检查一次
        self._last_check_time = 0
        self._last_login_status = None
        self._last_window_cleanup_time = 0
        self._window_cleanup_interval = 600  # 10分钟清理一次窗口
    
    def start(self):
        """启动监控线程"""
        if self._monitor_thread and self._monitor_thread.is_alive():
            optimized_print("⚠️ [LoginStatusMonitor] 监控线程已在运行",
                           category='login_monitor', level='WARNING')
            return
        
        self._stop_event.clear()
        self._monitor_thread = threading.Thread(target=self._monitor_loop, daemon=True)
        self._monitor_thread.start()
        optimized_print("✅ [LoginStatusMonitor] 登录状态监控已启动（每1分钟检查一次）",
                       category='login_monitor', level='INFO')
    
    def stop(self):
        """停止监控线程"""
        if self._monitor_thread:
            self._stop_event.set()
            self._monitor_thread.join(timeout=2)
            optimized_print("🛑 [LoginStatusMonitor] 登录状态监控已停止",
                           category='login_monitor', level='INFO')
    
    def _monitor_loop(self):
        """监控循环"""
        # 首次立即检查一次（不等待）
        try:
            self._check_login_status()
        except Exception as e:
            optimized_print(f"❌ [LoginStatusMonitor] 首次检查异常: {e}",
                           category='login_monitor', level='ERROR')
        
        while not self._stop_event.is_set():
            try:
                # 等待检查间隔
                self._stop_event.wait(self._check_interval)
                if self._stop_event.is_set():
                    break
                
                # 执行登录状态检查
                self._check_login_status()
                
            except Exception as e:
                optimized_print(f"❌ [LoginStatusMonitor] 监控循环异常: {e}",
                               category='login_monitor', level='ERROR')
                # 异常时等待一段时间再继续
                time.sleep(10)
    
    def check_now(self):
        """
        立即执行一次登录状态检查（不等待定时器）
        用于在检测到未登录时立即检查
        """
        try:
            optimized_print("🔄 [LoginStatusMonitor] 立即执行登录状态检查",
                           category='login_monitor', level='INFO', force=True)
            self._check_login_status()
        except Exception as e:
            optimized_print(f"❌ [LoginStatusMonitor] 立即检查异常: {e}",
                           category='login_monitor', level='ERROR')
    
    def _check_login_status(self):
        """检查登录状态"""
        try:
            current_time = time.time()
            
            # 检查是否有cookie
            cookies = getattr(self.main_window, 'browser_cookies', None)
            if not cookies:
                optimized_print("⚠️ [LoginStatusMonitor] 无cookie，跳过登录状态检查",
                               category='login_monitor', level='DEBUG')
                # 无cookie时，设置为未登录状态
                if hasattr(self.main_window, 'is_need_login'):
                    self.main_window.is_need_login = 0
                return
            
            # 使用API检查登录状态
            is_logged_in = check_login_status_by_api(self.main_window)
            
            # 更新登录状态（同时更新本地状态和全局状态，确保同步）
            if hasattr(self.main_window, 'is_need_login'):
                old_status = self.main_window.is_need_login
                new_status = 1 if is_logged_in else 0
                
                if old_status != new_status:
                    self.main_window.is_need_login = new_status
                    optimized_print(f"🔄 [LoginStatusMonitor] 登录状态已更新: {old_status} -> {new_status}",
                                   category='login_monitor', level='INFO', force=True)
                    
                    # 关键修复：同步更新全局登录状态，确保状态一致
                    try:
                        from xy_client.LuckyClientOP import set_global_login_status
                        set_global_login_status(is_logged_in)
                        optimized_print(f"🔄 [LoginStatusMonitor] 全局登录状态已同步: {is_logged_in}",
                                       category='login_monitor', level='DEBUG')
                    except ImportError:
                        optimized_print("⚠️ [LoginStatusMonitor] 无法导入 set_global_login_status，跳过全局状态同步",
                                       category='login_monitor', level='WARNING')
                    except Exception as sync_e:
                        optimized_print(f"⚠️ [LoginStatusMonitor] 同步全局状态异常: {sync_e}",
                                       category='login_monitor', level='WARNING')
                else:
                    optimized_print(f"✅ [LoginStatusMonitor] 登录状态检查: {'已登录' if is_logged_in else '未登录'}",
                                   category='login_monitor', level='DEBUG')
            
            # 记录最后检查时间和状态
            self._last_check_time = current_time
            self._last_login_status = is_logged_in
            
            # 如果未登录，触发自动登录
            if not is_logged_in:
                # 关键修复：检查WebDriver连接失败标志，如果连接失败，暂停触发登录
                if hasattr(self.main_window, '_webdriver_connection_failed') and self.main_window._webdriver_connection_failed:
                    failed_time = getattr(self.main_window, '_webdriver_connection_failed_time', 0)
                    # 如果连接失败时间超过5分钟，允许再次尝试
                    if time.time() - failed_time < 300:  # 5分钟内暂停
                        optimized_print("⏸️ [LoginStatusMonitor] WebDriver连接失败，暂停触发登录（避免创建多个窗口），等待5分钟后自动恢复",
                                       category='login_monitor', level='WARNING', force=True)
                        return
                    else:
                        # 超过5分钟，清除失败标志，允许再次尝试
                        self.main_window._webdriver_connection_failed = False
                        optimized_print("🔄 [LoginStatusMonitor] WebDriver连接失败已超过5分钟，清除失败标志，允许再次尝试",
                                       category='login_monitor', level='INFO', force=True)
                
                optimized_print("⚠️ [LoginStatusMonitor] 检测到未登录状态（API明确返回未登录），准备触发自动登录",
                               category='login_monitor', level='WARNING', force=True)
                # 关键优化：API明确检测到未登录时，使用强制登录模式，跳过冷却时间和时间间隔检查
                self._trigger_auto_login(force_login=True)
            else:
                # 已登录，确保状态正确
                if hasattr(self.main_window, 'is_need_login') and self.main_window.is_need_login != 1:
                    self.main_window.is_need_login = 1
                    optimized_print("✅ [LoginStatusMonitor] 已登录，更新状态为已登录",
                                   category='login_monitor', level='INFO', force=True)
                
        except Exception as e:
            optimized_print(f"❌ [LoginStatusMonitor] 检查登录状态异常: {e}",
                           category='login_monitor', level='ERROR')
    
    def _trigger_auto_login(self, force_login=False):
        """
        触发自动登录
        
        Args:
            force_login: 是否强制登录（跳过冷却时间和时间间隔检查），用于API明确检测到未登录时
        """
        try:
            # 检查是否正在登录中
            if hasattr(self.main_window, 'is_need_login') and self.main_window.is_need_login == 2:
                optimized_print("⏸️ [LoginStatusMonitor] 正在登录中，跳过重复触发",
                               category='login_monitor', level='DEBUG')
                return
            
            # 设置登录状态为需要登录
            if hasattr(self.main_window, 'is_need_login'):
                self.main_window.is_need_login = 0
                optimized_print("✅ [LoginStatusMonitor] 已设置 is_need_login=0，准备触发登录",
                               category='login_monitor', level='INFO', force=True)
            
            # 优先使用smart_login_management（包含完整的登录流程）
            if hasattr(self.main_window, 'smart_login_management'):
                if force_login:
                    optimized_print("🔄 [LoginStatusMonitor] 触发 smart_login_management 执行强制登录（跳过冷却时间限制）",
                                   category='login_monitor', level='INFO', force=True)
                else:
                    optimized_print("🔄 [LoginStatusMonitor] 触发 smart_login_management 执行自动登录",
                                   category='login_monitor', level='INFO', force=True)
                # 设置登录状态为正在登录中（防止重复触发）
                if hasattr(self.main_window, 'is_need_login'):
                    self.main_window.is_need_login = 2
                # 异步触发，不阻塞监控线程，传递force_login参数
                login_thread = threading.Thread(
                    target=lambda: self.main_window.smart_login_management(force_login=force_login), 
                    daemon=True
                )
                login_thread.start()
                optimized_print("✅ [LoginStatusMonitor] smart_login_management 线程已启动",
                               category='login_monitor', level='INFO', force=True)
            else:
                # 使用Lucky.loginClient（模块函数，不是mainWindow的方法）
                try:
                    from xy_client.services.Lucky5 import Lucky
                    optimized_print("🔄 [LoginStatusMonitor] 触发 Lucky.loginClient 执行自动登录",
                                   category='login_monitor', level='INFO', force=True)
                    # 设置登录状态为正在登录中（防止重复触发）
                    if hasattr(self.main_window, 'is_need_login'):
                        self.main_window.is_need_login = 2
                    # 异步触发，不阻塞监控线程
                    login_thread = threading.Thread(target=Lucky.loginClient, args=(self.main_window,), daemon=True)
                    login_thread.start()
                    optimized_print("✅ [LoginStatusMonitor] Lucky.loginClient 线程已启动",
                                   category='login_monitor', level='INFO', force=True)
                except ImportError:
                    optimized_print("⚠️ [LoginStatusMonitor] 无法导入 Lucky 模块",
                                   category='login_monitor', level='WARNING', force=True)
                except Exception as login_e:
                    optimized_print(f"❌ [LoginStatusMonitor] 启动 loginClient 异常: {login_e}",
                                   category='login_monitor', level='ERROR', force=True)
                
        except Exception as e:
            optimized_print(f"❌ [LoginStatusMonitor] 触发自动登录异常: {e}",
                           category='login_monitor', level='ERROR', force=True)
            import traceback
            traceback.print_exc()
    
    def get_last_status(self) -> Optional[bool]:
        """获取最后一次检查的登录状态"""
        return self._last_login_status
    
    def get_last_check_time(self) -> float:
        """获取最后一次检查的时间"""
        return self._last_check_time

