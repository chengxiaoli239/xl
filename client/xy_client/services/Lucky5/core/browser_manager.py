"""
浏览器管理器
统一管理浏览器的启动、连接、恢复
"""

import time
import socket
from typing import Optional
from selenium.webdriver.remote.webdriver import WebDriver
from xy_client.services.Lucky5.utils.log_optimizer import optimized_print


class BrowserManager:
    """浏览器管理器 - 统一管理浏览器相关操作"""
    
    def __init__(self, main_window, state_manager):
        """
        初始化浏览器管理器
        
        Args:
            main_window: 主窗口实例
            state_manager: 状态管理器实例
        """
        self.main_window = main_window
        self.state_manager = state_manager
    
    def is_browser_connected(self) -> bool:
        """
        检查浏览器是否已连接
        
        Returns:
            bool: 是否已连接
        """
        if not self.state_manager.has_browser_driver():
            return False
        
        try:
            driver = self.main_window.driver
            # 尝试获取当前URL来验证连接，添加超时保护避免阻塞
            import threading
            
            url_result = [None]
            url_timeout = [False]
            
            def get_url():
                try:
                    url_result[0] = driver.current_url
                except Exception:
                    url_timeout[0] = True
            
            url_thread = threading.Thread(target=get_url, daemon=True)
            url_thread.start()
            url_thread.join(timeout=3)  # 最多等待3秒
            
            if url_thread.is_alive() or url_timeout[0]:
                return False
            
            return url_result[0] is not None
        except Exception:
            return False
    
    def check_and_recover_browser_connection(self, silent: bool = False) -> bool:
        """
        检查并恢复浏览器连接
        
        Args:
            silent: 是否静默模式（不打印日志）
        
        Returns:
            bool: 是否成功恢复连接
        """
        if getattr(self.main_window, 'runtime_mode', 'browser') == 'background':
            return True

        # 如果已经连接，直接返回
        if self.is_browser_connected():
            if not silent:
                optimized_print("✅ [BrowserManager] 浏览器连接正常",
                               category='browser_check', level='DEBUG')
            return True
        
        # 尝试恢复连接
        if not silent:
            optimized_print("🔄 [BrowserManager] 浏览器连接异常，尝试恢复...",
                           category='browser_check', level='WARNING')
        
        # 获取端口号
        port = getattr(self.main_window, 'port', None)
        if not port:
            if not silent:
                print("❌ [BrowserManager] 无法获取浏览器端口")
            return False
        
        # 检查端口是否可用
        if not self._check_port_available(port, silent):
            return False
        
        # 重新连接浏览器
        return self._reconnect_to_browser(port, silent)
    
    def _check_port_available(self, port: int, silent: bool = False) -> bool:
        """
        检查端口是否可用
        
        Args:
            port: 端口号
            silent: 是否静默模式
        
        Returns:
            bool: 端口是否可用
        """
        # 检查端口是否可用（最多等待5秒）
        port_available = False
        for i in range(5):
            try:
                with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
                    s.settimeout(1)
                    result = s.connect_ex(('localhost', port))
                    if result == 0:
                        port_available = True
                        break
            except Exception:
                pass
            time.sleep(1)
        
        if not port_available:
            if not silent:
                print(f"❌ [BrowserManager] 端口 {port} 不可用，浏览器可能未启动")
            return False
        
        return True
    
    def _reconnect_to_browser(self, port: int, silent: bool = False) -> bool:
        """
        重新连接到浏览器
        关键优化：如果旧连接关不掉，不强制关闭，直接创建新连接
        
        Args:
            port: 端口号
            silent: 是否静默模式
        
        Returns:
            bool: 是否成功连接
        """
        try:
            from xy_client.services.tools import tools
            
            # 获取浏览器类型
            selected_browser = 'chrome'
            if hasattr(self.main_window, 'getPreferredBrowser'):
                selected_browser = self.main_window.getPreferredBrowser()
            
            # 关键优化：尝试清理旧连接，但如果失败也不影响（不强制）
            # 因为可能WebDriver连接不上，无法关闭旧窗口，但只要重新打开浏览器登录正常就可以
            if hasattr(self.main_window, 'driver') and self.main_window.driver is not None:
                try:
                    # 尝试关闭旧连接（设置短超时，避免长时间卡住）
                    import threading
                    close_result = [False]
                    
                    def try_close():
                        try:
                            self.main_window.driver.quit()
                            close_result[0] = True
                        except:
                            pass
                    
                    close_thread = threading.Thread(target=try_close, daemon=True)
                    close_thread.start()
                    close_thread.join(timeout=2)  # 最多等待2秒
                    
                    if close_result[0]:
                        if not silent:
                            optimized_print(f"✅ [BrowserManager] 已关闭旧浏览器连接",
                                           category='browser_check', level='DEBUG')
                    else:
                        if not silent:
                            optimized_print(f"⚠️ [BrowserManager] 无法关闭旧浏览器连接（可能已断开），将直接创建新连接",
                                           category='browser_check', level='WARNING')
                except Exception as close_e:
                    if not silent:
                        optimized_print(f"⚠️ [BrowserManager] 关闭旧连接异常: {close_e}，将直接创建新连接",
                                       category='browser_check', level='WARNING')
                finally:
                    # 无论是否成功关闭，都清空driver引用
                    self.main_window.driver = None
            
            # 关键修复：缩短等待时间，避免长时间卡住
            # 等待一下，确保浏览器进程稳定（从1秒缩短到0.3秒）
            time.sleep(0.3)
            
            # 重新连接（getDriver内部已经有超时保护，最多等待30秒）
            new_driver = tools.getDriver(selected_browser, port)
            
            if new_driver:
                self.main_window.driver = new_driver
                if not silent:
                    optimized_print(f"✅ [BrowserManager] 浏览器重新连接成功 (端口: {port})",
                                   category='browser_check', level='INFO', force=True)  # 连接恢复强制输出
                
                # 重新获取cookies
                self._refresh_cookies(silent)
                
                return True
            else:
                if not silent:
                    optimized_print(f"❌ [BrowserManager] 无法创建浏览器驱动 (端口: {port})",
                                   category='browser_check', level='ERROR', force=True)
                return False
                
        except Exception as e:
            if not silent:
                optimized_print(f"❌ [BrowserManager] 重新连接浏览器失败: {e}",
                               category='browser_check', level='ERROR', force=True)
                import traceback
                traceback.print_exc()
            return False
    
    def _refresh_cookies(self, silent: bool = False):
        """
        刷新cookies
        
        Args:
            silent: 是否静默模式
        """
        try:
            if not self.state_manager.has_browser_driver():
                return
            
            # 添加超时保护，避免 get_cookies() 阻塞
            import threading
            
            cookies_result = [None]
            cookies_timeout = [False]
            
            def get_cookies():
                try:
                    cookies_result[0] = self.main_window.driver.get_cookies()
                except Exception:
                    cookies_timeout[0] = True
            
            cookies_thread = threading.Thread(target=get_cookies, daemon=True)
            cookies_thread.start()
            cookies_thread.join(timeout=3)  # 最多等待3秒
            
            if cookies_thread.is_alive() or cookies_timeout[0]:
                if not silent:
                    optimized_print("⚠️ [BrowserManager] 获取cookies超时",
                                   category='browser_check', level='WARNING')
                return
            
            cookies = cookies_result[0]
            if cookies:
                # 转换为字符串格式
                cookies_str = ''
                for cookie in cookies:
                    cookies_str += cookie.get('name', '') + '=' + cookie.get('value', '') + ';'
                
                self.state_manager.set_browser_cookies(cookies_str)
                
                if not silent:
                    optimized_print("✅ [BrowserManager] 已重新获取浏览器cookies",
                                   category='browser_check', level='DEBUG')
        except Exception as e:
            if not silent:
                optimized_print(f"⚠️ [BrowserManager] 重新获取cookies失败: {e}",
                               category='browser_check', level='WARNING')
    
    def get_driver(self) -> Optional[WebDriver]:
        """
        获取浏览器驱动
        
        Returns:
            WebDriver: 浏览器驱动实例，如果不存在则返回None
        """
        if self.state_manager.has_browser_driver():
            return self.main_window.driver
        return None

