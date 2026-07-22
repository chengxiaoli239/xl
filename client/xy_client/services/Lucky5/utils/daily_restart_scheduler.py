"""
每日定时重启调度器
每天早上7:45关闭浏览器并重新登录（早盘重启，如果配置文件未设置则使用此默认时间）
"""

import time
import threading
import os
from typing import Optional
from xy_client.services.MyThreading import MyThreadingTimer


class DailyRestartScheduler:
    """每日定时重启调度器"""
    
    def __init__(self, main_window):
        """
        初始化每日重启调度器
        
        Args:
            main_window: 主窗口实例
        """
        self.main_window = main_window
        self._last_execution_date = None
        self._is_executing = False
        self._lock = threading.Lock()
        self._current_timer = None  # 当前定时器实例
        self._target_hour = 7  # 当前目标小时
        self._target_minute = 45  # 当前目标分钟（默认7:45早盘重启）
        self._check_interval = 60  # 当前检查间隔
        self._test_mode = False  # 当前是否为测试模式
        self._config_file_path = None  # 配置文件路径
        self._config_file_mtime = None  # 配置文件最后修改时间
        self._init_config_file_path()  # 初始化配置文件路径
    
    def is_target_time(self, target_hour: int = 7, target_minute: int = 45) -> bool:
        """
        检查是否到达目标时间
        
        Args:
            target_hour: 目标小时（默认7）
            target_minute: 目标分钟（默认45）
        
        Returns:
            bool: 是否到达目标时间
        """
        current_time = time.localtime()
        current_hour = current_time.tm_hour
        current_minute = current_time.tm_min
        current_date = current_time.tm_mday
        
        # 检查是否在目标时间（默认7:45）
        if current_hour == target_hour and current_minute == target_minute:
            # 检查今天是否已经执行过
            if self._last_execution_date != current_date:
                return True
        
        return False
    
    def execute_daily_restart(self) -> bool:
        """
        执行每日重启：关闭浏览器并重新登录
        
        Returns:
            bool: 是否成功执行
        """
        with self._lock:
            # 防止重复执行
            if self._is_executing:
                print("⏰ [DailyRestart] 每日重启任务正在执行中，跳过本次")
                return False
            
            self._is_executing = True
        
        try:
            current_time_str = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())
            print(f"⏰ [DailyRestart] 开始执行每日重启任务 ({current_time_str})")
            
            # 步骤1: 关闭浏览器
            print("🔄 [DailyRestart] 步骤1: 关闭浏览器...")
            self._close_browser()
            
            # 等待一下，确保浏览器完全关闭
            time.sleep(3)
            
            # 步骤2: 清理状态
            print("🔄 [DailyRestart] 步骤2: 清理登录状态...")
            self._clear_login_state()
            
            # 步骤3: 重新登录
            print("🔄 [DailyRestart] 步骤3: 重新启动浏览器并登录...")
            success = self._restart_and_login()
            
            if success:
                current_date = time.localtime().tm_mday
                self._last_execution_date = current_date
                print(f"✅ [DailyRestart] 每日重启任务完成 ({current_time_str})")
            else:
                print(f"❌ [DailyRestart] 每日重启任务失败 ({current_time_str})")
            
            return success
            
        except Exception as e:
            print(f"❌ [DailyRestart] 每日重启任务异常: {e}")
            import traceback
            traceback.print_exc()
            return False
        finally:
            with self._lock:
                self._is_executing = False
    
    def _close_browser(self):
        """关闭浏览器（关键修复：关闭账户的所有端口对应的浏览器进程）"""
        try:
            # 关键修复：先关闭账户的所有端口对应的浏览器进程
            account_id = getattr(self.main_window, 'access_token', None) or 'default_account'
            if hasattr(self.main_window, 'port_manager') and self.main_window.port_manager:
                account_id = self.main_window.port_manager.account_id
            
            try:
                from xy_client.services.tools.BrowserPortManager import close_all_browsers_for_account
                closed_count = close_all_browsers_for_account(account_id)
                if closed_count > 0:
                    print(f"✅ [DailyRestart] 已关闭账户 {account_id} 的 {closed_count} 个浏览器进程")
            except ImportError:
                print("⚠️ [DailyRestart] 无法导入 close_all_browsers_for_account，使用备用方案")
            except Exception as e:
                print(f"⚠️ [DailyRestart] 关闭所有端口浏览器异常: {e}")
            
            # 使用主窗口的强制关闭浏览器方法（备用方案）
            if hasattr(self.main_window, 'force_close_all_browsers'):
                try:
                    self.main_window.force_close_all_browsers()
                except Exception as e:
                    print(f"⚠️ [DailyRestart] force_close_all_browsers 异常: {e}")
            
            # 备用方案：直接关闭driver
            if hasattr(self.main_window, 'driver') and self.main_window.driver:
                try:
                    self.main_window.driver.quit()
                    print("✅ [DailyRestart] 浏览器driver已关闭")
                except Exception as e:
                    print(f"⚠️ [DailyRestart] 关闭driver异常: {e}")
                finally:
                    self.main_window.driver = None
        except Exception as e:
            print(f"❌ [DailyRestart] 关闭浏览器异常: {e}")
    
    def _clear_login_state(self):
        """清理登录状态（强制清理，用于重启任务）"""
        try:
            # 强制清理登录状态（绕过updateLoginStatus的保护机制）
            # 在重启任务中，即使当前显示已登录，也需要强制清理，因为浏览器已经关闭
            print("🔄 [DailyRestart] 强制清理登录状态（重启任务）...")
            
            # 直接设置登录状态为未登录，绕过保护机制
            self.main_window.is_need_login = 0
            
            # 同步更新全局登录状态
            try:
                from xy_client.LuckyClientOP import set_global_login_status
                set_global_login_status(False)
                print("✅ [DailyRestart] 全局登录状态已同步为未登录")
            except Exception as sync_e:
                print(f"⚠️ [DailyRestart] 同步全局登录状态异常: {sync_e}")
            
            # 清理cookies
            if hasattr(self.main_window, 'browser_cookies'):
                self.main_window.browser_cookies = None
            
            # 清理driver引用
            if hasattr(self.main_window, 'driver'):
                self.main_window.driver = None
            
            print("✅ [DailyRestart] 登录状态已强制清理")
        except Exception as e:
            print(f"⚠️ [DailyRestart] 清理登录状态异常: {e}")
            import traceback
            traceback.print_exc()
    
    def _restart_and_login(self) -> bool:
        """
        重新启动浏览器并登录
        
        Returns:
            bool: 是否成功
        """
        try:
            # 检查是否有用户信息
            if not hasattr(self.main_window, 'user_info') or not self.main_window.user_info:
                print("❌ [DailyRestart] 未找到用户信息，无法重新登录")
                return False
            
            # 确保登录状态已清理（防止智能登录判断为已登录而跳过）
            self.main_window.is_need_login = 0
            try:
                from xy_client.LuckyClientOP import set_global_login_status
                set_global_login_status(False)
            except:
                pass
            
            # 使用智能登录方法
            if hasattr(self.main_window, 'smart_login_management'):
                print("🔄 [DailyRestart] 使用智能登录方法重新登录（强制登录模式）...")
                # 使用force_login=True强制登录，跳过冷却时间和时间间隔检查
                result = self.main_window.smart_login_management(force_login=True)
                
                # 检查登录结果
                login_success = False
                if result:
                    # 等待登录完成
                    time.sleep(5)
                    
                    # 再次检查登录状态
                    if hasattr(self.main_window, 'is_need_login') and self.main_window.is_need_login == 1:
                        login_success = True
                        print("✅ [DailyRestart] 智能登录成功（登录状态已确认）")
                    else:
                        print("⚠️ [DailyRestart] 智能登录返回成功，但登录状态未更新，可能需要重试")
                        # 如果登录状态仍然是0，尝试再次登录
                        if hasattr(self.main_window, 'execute_smart_login'):
                            print("🔄 [DailyRestart] 尝试再次执行登录...")
                            retry_result = self.main_window.execute_smart_login()
                            if retry_result:
                                time.sleep(5)
                                if hasattr(self.main_window, 'is_need_login') and self.main_window.is_need_login == 1:
                                    login_success = True
                                    print("✅ [DailyRestart] 重试登录成功")
                
                if login_success:
                    # 等待登录完成并验证导航到盘口页面
                    time.sleep(2)
                    
                    # 验证是否成功导航到盘口页面
                    try:
                        if hasattr(self.main_window, 'browser_manager'):
                            driver = self.main_window.browser_manager.check_and_recover_browser_connection(silent=False)
                            if driver:
                                current_url = driver.current_url.lower()
                                print(f"🔍 [DailyRestart] 登录后当前URL: {current_url}")
                                
                                # 检查是否在盘口页面
                                if "app/index" in current_url or "app/index#" in current_url:
                                    print("✅ [DailyRestart] 已成功导航到盘口页面")
                                    return True
                                else:
                                    print(f"⚠️ [DailyRestart] 未在盘口页面（当前: {current_url}），尝试导航...")
                                    # 尝试导航到盘口页面
                                    try:
                                        from xy_client.services.Lucky5 import Lucky
                                        if hasattr(self.main_window, 'domain') or hasattr(self.main_window, 'ssc_domain'):
                                            domain = getattr(self.main_window, 'ssc_domain', None) or getattr(self.main_window, 'domain', None)
                                            if domain:
                                                market_url = domain.rstrip('/') + '/App/Index'
                                                print(f"🌐 [DailyRestart] 尝试导航到盘口页面: {market_url}")
                                                driver.get(market_url)
                                                time.sleep(3)
                                                final_url = driver.current_url.lower()
                                                if "app/index" in final_url:
                                                    print("✅ [DailyRestart] 已成功导航到盘口页面")
                                                    return True
                                                else:
                                                    print(f"⚠️ [DailyRestart] 导航后仍在: {final_url}")
                                    except Exception as nav_e:
                                        print(f"⚠️ [DailyRestart] 导航到盘口页面异常: {nav_e}")
                                    
                                    # 即使导航失败，如果登录状态正常，也认为成功
                                    if hasattr(self.main_window, 'is_need_login') and self.main_window.is_need_login == 1:
                                        print("✅ [DailyRestart] 登录状态正常，认为登录成功（即使未在盘口页面）")
                                        return True
                            else:
                                print("⚠️ [DailyRestart] 无法获取WebDriver连接，但登录状态可能正常")
                                # 如果登录状态正常，也认为成功
                                if hasattr(self.main_window, 'is_need_login') and self.main_window.is_need_login == 1:
                                    print("✅ [DailyRestart] 登录状态正常，认为登录成功")
                                    return True
                    except Exception as verify_e:
                        print(f"⚠️ [DailyRestart] 验证登录状态异常: {verify_e}")
                        # 如果登录状态正常，也认为成功
                        if hasattr(self.main_window, 'is_need_login') and self.main_window.is_need_login == 1:
                            print("✅ [DailyRestart] 登录状态正常，认为登录成功")
                            return True
                    
                    return True
                else:
                    print("❌ [DailyRestart] 智能登录失败")
                    return False
            elif hasattr(self.main_window, 'execute_smart_login'):
                result = self.main_window.execute_smart_login()
                if result:
                    print("✅ [DailyRestart] 重新登录成功")
                    return True
                else:
                    print("❌ [DailyRestart] 重新登录失败")
                    return False
            else:
                # 备用方案：使用doLogin方法
                if hasattr(self.main_window, 'doLogin'):
                    self.main_window.doLogin()
                    # 等待登录完成
                    time.sleep(5)
                    if hasattr(self.main_window, 'is_need_login') and self.main_window.is_need_login == 1:
                        print("✅ [DailyRestart] 重新登录成功")
                        return True
                    else:
                        print("❌ [DailyRestart] 重新登录失败")
                        return False
                else:
                    print("❌ [DailyRestart] 未找到登录方法")
                    return False
        except Exception as e:
            print(f"❌ [DailyRestart] 重新登录异常: {e}")
            import traceback
            traceback.print_exc()
            return False
    
    def start_daily_check(self, check_interval: int = 60, test_mode: bool = False, 
                         target_hour: int = None, target_minute: int = None):
        """
        启动每日检查定时器（每分钟检查一次）
        
        Args:
            check_interval: 检查间隔（秒），默认60秒（每分钟检查一次）
            test_mode: 测试模式，如果为True，则每10秒检查一次（用于快速测试）
            target_hour: 目标小时（0-23），如果为None则使用默认值7
            target_minute: 目标分钟（0-59），如果为None则使用默认值50
        """
        # 停止旧的定时器（如果存在）
        self.stop_daily_check()
        
        # 使用默认值或自定义值
        final_hour = target_hour if target_hour is not None else 7
        final_minute = target_minute if target_minute is not None else 50
        
        # 保存当前设置
        self._target_hour = final_hour
        self._target_minute = final_minute
        self._test_mode = test_mode
        self._check_interval = 10 if test_mode else check_interval
        
        def check_and_restart():
            try:
                # 如果正在执行重启任务，跳过配置文件检查和时间检查，避免打断重启流程
                if self._is_executing:
                    # 继续启动下一次检查，但不更新配置
                    self._current_timer = MyThreadingTimer.myTimer(self._check_interval, check_and_restart, ())
                    return
                
                # 检查配置文件是否有更新（用于运行时修改重启时间）
                # 注意：只有在非执行状态时才检查配置文件更新
                self._check_config_file_update()
                
                # 添加调试日志（每分钟输出一次当前时间和目标时间，方便调试）
                current_time = time.localtime()
                current_hour = current_time.tm_hour
                current_minute = current_time.tm_min
                # 只在整分钟时输出日志，避免日志过多
                if current_minute % 5 == 0:  # 每5分钟输出一次
                    print(f"🔍 [DailyRestart] 当前时间: {current_hour:02d}:{current_minute:02d}，目标时间: {self._target_hour:02d}:{self._target_minute:02d}，最后执行日期: {self._last_execution_date}")
                
                # 检查是否到达目标时间
                if self.is_target_time(target_hour=self._target_hour, target_minute=self._target_minute):
                    print(f"⏰ [DailyRestart] 检测到{self._target_hour:02d}:{self._target_minute:02d}，开始执行每日重启任务")
                    self.execute_daily_restart()
                
                # 继续启动下一次检查
                self._current_timer = MyThreadingTimer.myTimer(self._check_interval, check_and_restart, ())
            except Exception as e:
                print(f"❌ [DailyRestart] 每日检查异常: {e}")
                import traceback
                traceback.print_exc()
                # 异常后继续启动下一次检查
                self._current_timer = MyThreadingTimer.myTimer(self._check_interval, check_and_restart, ())
        
        if test_mode:
            print(f"🧪 [DailyRestart] 测试模式：每日重启检查定时器已启动（每{self._check_interval}秒检查一次，目标时间：{final_hour:02d}:{final_minute:02d}）")
        else:
            print(f"✅ [DailyRestart] 每日重启检查定时器已启动（每{self._check_interval}秒检查一次，目标时间：{final_hour:02d}:{final_minute:02d}）")
        
        # 启动首次检查
        self._current_timer = MyThreadingTimer.myTimer(self._check_interval, check_and_restart, ())
    
    def stop_daily_check(self):
        """
        停止每日检查定时器
        
        Returns:
            bool: 是否成功停止
        """
        if self._current_timer is not None:
            try:
                self._current_timer.cancel()
                self._current_timer = None
                print("✅ [DailyRestart] 每日重启检查定时器已停止")
                return True
            except Exception as e:
                print(f"⚠️ [DailyRestart] 停止定时器异常: {e}")
                self._current_timer = None
                return False
        return True
    
    def update_restart_time(self, target_hour: int, target_minute: int, 
                           check_interval: int = None, test_mode: bool = None):
        """
        动态更新重启时间（运行时设置）
        
        Args:
            target_hour: 目标小时（0-23）
            target_minute: 目标分钟（0-59）
            check_interval: 检查间隔（秒），如果为None则保持当前设置
            test_mode: 测试模式，如果为None则保持当前设置
        
        Returns:
            bool: 是否成功更新
        """
        # 验证时间有效性
        if not (0 <= target_hour <= 23 and 0 <= target_minute <= 59):
            print(f"❌ [DailyRestart] 无效的时间：{target_hour:02d}:{target_minute:02d}")
            return False
        
        print(f"🔄 [DailyRestart] 更新重启时间: {target_hour:02d}:{target_minute:02d}")
        
        # 使用当前设置或新设置
        final_check_interval = check_interval if check_interval is not None else self._check_interval
        final_test_mode = test_mode if test_mode is not None else self._test_mode
        
        # 重新启动定时器
        self.start_daily_check(
            check_interval=final_check_interval,
            test_mode=final_test_mode,
            target_hour=target_hour,
            target_minute=target_minute
        )
        
        print(f"✅ [DailyRestart] 重启时间已更新为: {target_hour:02d}:{target_minute:02d}")
        return True
    
    def _init_config_file_path(self):
        """初始化配置文件路径"""
        try:
            # 获取当前文件所在目录
            current_dir = os.path.dirname(os.path.abspath(__file__))
            self._config_file_path = os.path.join(current_dir, 'restart_time_config.txt')
            # 如果文件不存在，创建默认文件
            if not os.path.exists(self._config_file_path):
                try:
                    with open(self._config_file_path, 'w', encoding='utf-8') as f:
                        f.write("# 每日重启时间配置文件\n")
                        f.write("# 格式：hour:minute\n")
                        f.write("# 例如：8:58 表示每天早上8:58重启\n")
                        f.write("# 修改此文件后，程序会在1分钟内自动读取并应用新设置\n")
                        f.write("# 如果格式错误或未设置，将使用默认值或配置文件中的值\n")
                        f.write("7:45\n")
                    print(f"📝 [DailyRestart] 已创建配置文件: {self._config_file_path}")
                except Exception as e:
                    print(f"⚠️ [DailyRestart] 创建配置文件失败: {e}")
        except Exception as e:
            print(f"⚠️ [DailyRestart] 初始化配置文件路径失败: {e}")
    
    def _check_config_file_update(self):
        """检查配置文件是否有更新，如果有更新则读取并应用新设置"""
        if not self._config_file_path or not os.path.exists(self._config_file_path):
            return
        
        try:
            # 检查文件修改时间
            current_mtime = os.path.getmtime(self._config_file_path)
            if self._config_file_mtime is None or current_mtime > self._config_file_mtime:
                # 文件已更新，读取新配置
                self._config_file_mtime = current_mtime
                self._read_config_file()
        except Exception as e:
            # 忽略文件读取错误，不影响主流程
            pass
    
    def _read_config_file(self):
        """从配置文件读取重启时间"""
        if not self._config_file_path or not os.path.exists(self._config_file_path):
            return
        
        try:
            with open(self._config_file_path, 'r', encoding='utf-8') as f:
                lines = f.readlines()
            
            # 查找非注释行（不以#开头）
            for line in lines:
                line = line.strip()
                if line and not line.startswith('#'):
                    # 尝试解析时间格式 hour:minute
                    if ':' in line:
                        parts = line.split(':')
                        if len(parts) == 2:
                            try:
                                new_hour = int(parts[0].strip())
                                new_minute = int(parts[1].strip())
                                
                                # 验证时间有效性
                                if 0 <= new_hour <= 23 and 0 <= new_minute <= 59:
                                    # 如果时间有变化，更新设置
                                    if new_hour != self._target_hour or new_minute != self._target_minute:
                                        print(f"📝 [DailyRestart] 检测到配置文件更新，新时间: {new_hour:02d}:{new_minute:02d}")
                                        self.update_restart_time(new_hour, new_minute)
                                    return
                                else:
                                    print(f"⚠️ [DailyRestart] 配置文件时间无效: {new_hour:02d}:{new_minute:02d}，忽略")
                            except ValueError:
                                print(f"⚠️ [DailyRestart] 配置文件时间格式错误: {line}，忽略")
        except Exception as e:
            print(f"⚠️ [DailyRestart] 读取配置文件异常: {e}")
    
    def get_restart_time(self):
        """
        获取当前设置的重启时间
        
        Returns:
            tuple: (hour, minute) 当前设置的重启时间
        """
        return (self._target_hour, self._target_minute)
    
    def get_restart_time_str(self):
        """
        获取当前设置的重启时间（字符串格式）
        
        Returns:
            str: 当前设置的重启时间，格式为 "HH:MM"
        """
        return f"{self._target_hour:02d}:{self._target_minute:02d}"
    
    def test_restart_now(self):
        """
        测试方法：立即执行重启任务（用于测试）
        
        Returns:
            bool: 是否成功执行
        """
        print("🧪 [DailyRestart] 测试模式：立即执行每日重启任务")
        print("⚠️  注意：这将关闭浏览器并重新登录，请确保在测试环境中运行")
        
        # 重置执行日期，允许立即执行
        self._last_execution_date = None
        
        return self.execute_daily_restart()
    
    def test_restart_at_time(self, test_hour: int, test_minute: int, check_interval: int = 10):
        """
        测试方法：设置测试时间并启动检查（用于测试）
        
        Args:
            test_hour: 测试小时（0-23）
            test_minute: 测试分钟（0-59）
            check_interval: 检查间隔（秒），默认10秒（用于快速测试）
        
        Returns:
            bool: 是否成功启动
        """
        print(f"🧪 [DailyRestart] 测试模式：设置测试时间为 {test_hour:02d}:{test_minute:02d}")
        
        # 验证时间有效性
        if not (0 <= test_hour <= 23 and 0 <= test_minute <= 59):
            print(f"❌ [DailyRestart] 无效的时间：{test_hour:02d}:{test_minute:02d}")
            return False
        
        def check_and_restart_test():
            try:
                # 检查是否到达测试时间
                if self.is_target_time(target_hour=test_hour, target_minute=test_minute):
                    print(f"⏰ [DailyRestart] 检测到测试时间 {test_hour:02d}:{test_minute:02d}，开始执行每日重启任务")
                    self.execute_daily_restart()
                    # 测试完成后停止检查
                    return
                
                # 继续检查
                MyThreadingTimer.myTimer(check_interval, check_and_restart_test, ())
            except Exception as e:
                print(f"❌ [DailyRestart] 测试检查异常: {e}")
                # 异常后继续检查
                MyThreadingTimer.myTimer(check_interval, check_and_restart_test, ())
        
        # 启动测试检查
        print(f"🧪 [DailyRestart] 测试检查已启动（每{check_interval}秒检查一次，目标时间：{test_hour:02d}:{test_minute:02d}）")
        MyThreadingTimer.myTimer(check_interval, check_and_restart_test, ())
        return True
    
    def test_restart_in_minutes(self, minutes: int):
        """
        测试方法：在指定分钟数后执行重启（用于快速测试）
        
        Args:
            minutes: 多少分钟后执行（例如：3表示3分钟后执行）
        
        Returns:
            bool: 是否成功启动
        """
        import datetime
        now = datetime.datetime.now()
        target_time = now + datetime.timedelta(minutes=minutes)
        test_hour = target_time.hour
        test_minute = target_time.minute
        
        print(f"🧪 [DailyRestart] 将在 {minutes} 分钟后（{test_hour:02d}:{test_minute:02d}）执行重启任务")
        return self.test_restart_at_time(test_hour, test_minute, check_interval=10)

