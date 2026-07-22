#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
浏览器端口连接管理器
确保WebDriver能正确连接到指定端口的浏览器实例
"""

import os
import time
import socket
import requests
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry
import threading
from typing import Optional, Dict, List
from selenium import webdriver
from selenium.webdriver.chrome.options import Options as ChromeOptions
from selenium.webdriver.chrome.service import Service as ChromeService
from selenium.webdriver.firefox.options import Options as FirefoxOptions
from selenium.webdriver.firefox.service import Service as FirefoxService

from xy_client.services.tools.account_runtime import debug_port_for_account


class BrowserPortManager:
    """浏览器端口连接管理器"""
    
    def __init__(self, account_id: str, browser_type: str = "chrome"):
        self.account_id = account_id
        self.browser_type = browser_type.lower()
        self.debug_port = self._get_debug_port()
        self.user_data_dir = self._get_user_data_dir()
        self.driver = None
        self._lock = threading.Lock()
        
        # 关键修复：将端口添加到全局端口集合中
        _add_port_to_account(account_id, self.debug_port)
        
        print(f"✅ 端口管理器初始化: 账号={account_id}, 端口={self.debug_port}, 浏览器={browser_type}")
    
    def _get_debug_port(self) -> int:
        """获取调试端口"""
        return debug_port_for_account(self.account_id)
    
    def _get_user_data_dir(self) -> str:
        """获取用户数据目录（兼容现有系统）"""
        import platform
        # 使用现有的用户目录结构，不改变现有用户的使用
        if platform.system() == "Windows":
            return f"C:\\.temp\\9222\\{self.account_id}"
        else:
            return f"/tmp/9222/{self.account_id}"
    
    def is_port_available(self, port: int) -> bool:
        """检查端口是否可用"""
        try:
            with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
                s.settimeout(1)
                result = s.connect_ex(('127.0.0.1', port))
                return result != 0  # 0表示连接成功，即端口被占用
        except Exception:
            return False
    
    def is_browser_running_on_port(self, port: int) -> bool:
        """检查指定端口是否有浏览器在运行"""
        try:
            # 尝试连接调试端口（使用本地复用Session，避免连接池满警告）
            response = _get_local_http().get(f'http://127.0.0.1:{port}/json', timeout=2)
            if response.status_code == 200:
                data = response.json()
                # 检查是否有浏览器标签页
                return len(data) > 0
        except Exception:
            pass
        return False
    
    def get_browser_tabs_info(self, port: int) -> List[Dict]:
        """获取浏览器标签页信息"""
        try:
            response = _get_local_http().get(f'http://127.0.0.1:{port}/json', timeout=2)
            if response.status_code == 200:
                return response.json()
        except Exception:
            pass
        return []
    
    def start_browser_with_debug_port(self) -> bool:
        """启动带调试端口的浏览器"""
        try:
            if self.browser_type == "chrome":
                return self._start_chrome_with_debug()
            elif self.browser_type == "firefox":
                return self._start_firefox_with_debug()
            else:
                print(f"❌ 不支持的浏览器类型: {self.browser_type}")
                return False
        except Exception as e:
            print(f"❌ 启动浏览器异常: {e}")
            return False
    
    def _start_chrome_with_debug(self) -> bool:
        """启动Chrome浏览器并开启调试端口"""
        try:
            import subprocess
            import platform
            
            # 获取Chrome路径
            chrome_paths = [
                r"C:\Program Files\Google\Chrome\Application\chrome.exe",
                r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
                r"C:\Users\{}\AppData\Local\Google\Chrome\Application\chrome.exe".format(os.getenv('USERNAME')),
                "/usr/bin/google-chrome",
                "/usr/bin/chromium-browser",
                "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
            ]
            
            chrome_path = None
            for path in chrome_paths:
                if os.path.exists(path):
                    chrome_path = path
                    break
            
            if not chrome_path:
                print("❌ 未找到Chrome浏览器路径")
                return False
            
            # 构建启动命令
            cmd = [
                chrome_path,
                f"--remote-debugging-port={self.debug_port}",
                f"--user-data-dir={self.user_data_dir}",
                "--no-first-run",
                "--no-default-browser-check",
                "--disable-extensions",
                "--disable-plugins",
                "--disable-web-security",
                "--disable-features=VizDisplayCompositor"
            ]
            
            # 启动浏览器
            subprocess.Popen(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            
            # 等待浏览器启动
            for i in range(10):
                time.sleep(1)
                if self.is_browser_running_on_port(self.debug_port):
                    print(f"✅ Chrome浏览器已启动，调试端口: {self.debug_port}")
                    return True
            
            print(f"❌ Chrome浏览器启动超时，端口: {self.debug_port}")
            return False
            
        except Exception as e:
            print(f"❌ 启动Chrome浏览器异常: {e}")
            return False
    
    def _start_firefox_with_debug(self) -> bool:
        """启动Firefox浏览器并开启调试端口"""
        try:
            import subprocess
            
            # 获取Firefox路径
            firefox_paths = [
                r"C:\Program Files\Mozilla Firefox\firefox.exe",
                r"C:\Program Files (x86)\Mozilla Firefox\firefox.exe",
                "/usr/bin/firefox",
                "/Applications/Firefox.app/Contents/MacOS/firefox"
            ]
            
            firefox_path = None
            for path in firefox_paths:
                if os.path.exists(path):
                    firefox_path = path
                    break
            
            if not firefox_path:
                print("❌ 未找到Firefox浏览器路径")
                return False
            
            # 构建启动命令
            cmd = [
                firefox_path,
                "--new-instance",
                "--profile", self.user_data_dir,
                "--remote-debugging-port", str(self.debug_port)
            ]
            
            # 启动浏览器
            subprocess.Popen(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            
            # 等待浏览器启动
            for i in range(10):
                time.sleep(1)
                if self.is_browser_running_on_port(self.debug_port):
                    print(f"✅ Firefox浏览器已启动，调试端口: {self.debug_port}")
                    return True
            
            print(f"❌ Firefox浏览器启动超时，端口: {self.debug_port}")
            return False
            
        except Exception as e:
            print(f"❌ 启动Firefox浏览器异常: {e}")
            return False
    
    def connect_to_browser(self):
        """连接到指定端口的浏览器"""
        try:
            with self._lock:
                # 检查端口是否有浏览器运行
                if not self.is_browser_running_on_port(self.debug_port):
                    print(f"⚠️ 端口 {self.debug_port} 没有浏览器运行，尝试启动...")
                    if not self.start_browser_with_debug_port():
                        return None
                
                # 获取标签页信息
                tabs = self.get_browser_tabs_info(self.debug_port)
                if not tabs:
                    print(f"❌ 端口 {self.debug_port} 没有可用的标签页")
                    return None
                
                # 选择第一个标签页
                target_tab = tabs[0]
                print(f"✅ 找到目标标签页: {target_tab.get('title', 'Unknown')}")
                
                # 连接到浏览器
                if self.browser_type == "chrome":
                    return self._connect_chrome()
                elif self.browser_type == "firefox":
                    return self._connect_firefox()
                else:
                    print(f"❌ 不支持的浏览器类型: {self.browser_type}")
                    return None
                    
        except Exception as e:
            print(f"❌ 连接浏览器异常: {e}")
            return None
    
    def _connect_chrome(self) -> Optional[webdriver.Chrome]:
        """连接到Chrome浏览器"""
        try:
            # 查找chromedriver
            chromedriver_path = self._find_chromedriver()
            if not chromedriver_path:
                print("❌ 未找到chromedriver")
                return None
            
            # 配置Chrome选项
            options = ChromeOptions()
            options.add_experimental_option('debuggerAddress', f'127.0.0.1:{self.debug_port}')
            options.add_argument('--no-sandbox')
            options.add_argument('--disable-dev-shm-usage')
            
            # 创建服务
            service = ChromeService(executable_path=chromedriver_path)
            
            # 创建WebDriver
            driver = webdriver.Chrome(service=service, options=options)
            
            print(f"✅ 成功连接到Chrome浏览器，端口: {self.debug_port}")
            return driver
            
        except Exception as e:
            print(f"❌ 连接Chrome浏览器异常: {e}")
            return None
    
    def _connect_firefox(self) -> Optional[webdriver.Firefox]:
        """连接到Firefox浏览器"""
        try:
            # 查找geckodriver
            geckodriver_path = self._find_geckodriver()
            if not geckodriver_path:
                print("❌ 未找到geckodriver")
                return None
            
            # 配置Firefox选项
            options = FirefoxOptions()
            options.add_argument(f'--remote-debugging-port={self.debug_port}')
            
            # 创建服务
            service = FirefoxService(executable_path=geckodriver_path)
            
            # 创建WebDriver
            driver = webdriver.Firefox(service=service, options=options)
            
            print(f"✅ 成功连接到Firefox浏览器，端口: {self.debug_port}")
            return driver
            
        except Exception as e:
            print(f"❌ 连接Firefox浏览器异常: {e}")
            return None
    
    def _find_chromedriver(self) -> Optional[str]:
        """查找chromedriver路径"""
        possible_paths = [
            os.path.join(os.path.dirname(__file__), 'chromedriver-win64', 'chromedriver.exe'),
            os.path.join(os.path.dirname(__file__), 'chromedriver.exe'),
            os.path.join(os.getcwd(), 'chromedriver.exe'),
            os.path.join(os.getcwd(), 'chromedriver-win64', 'chromedriver.exe'),
            './chromedriver.exe',
            './chromedriver-win64/chromedriver.exe',
            'chromedriver.exe'  # 在PATH中查找
        ]
        
        for path in possible_paths:
            if os.path.exists(path):
                return path
        
        return None
    
    def _find_geckodriver(self) -> Optional[str]:
        """查找geckodriver路径"""
        possible_paths = [
            os.path.join(os.path.dirname(__file__), 'geckodriver.exe'),
            os.path.join(os.getcwd(), 'geckodriver.exe'),
            './geckodriver.exe',
            'geckodriver.exe'  # 在PATH中查找
        ]
        
        for path in possible_paths:
            if os.path.exists(path):
                return path
        
        return None
    
    def ensure_single_tab(self, driver) -> bool:
        """确保只有一个标签页（智能清理：优先保留已登录的标签页）"""
        try:
            if not driver:
                return False
            
            # 获取所有窗口句柄
            window_handles = driver.window_handles
            if len(window_handles) <= 1:
                return True
            
            print(f"🔄 [端口={self.debug_port}] 检测到 {len(window_handles)} 个标签页，开始智能清理...")
            
            # 关键修复：优先保留已登录的标签页，避免关闭登录成功的标签页
            # 检查每个标签页的URL，找出已登录的标签页
            logged_in_window = None
            login_page_windows = []
            
            for handle in window_handles:
                try:
                    driver.switch_to.window(handle)
                    # 使用超时保护获取URL
                    import threading
                    url_result = [None]
                    url_timeout = [False]
                    
                    def get_url():
                        try:
                            url_result[0] = driver.current_url.lower()
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
                            print(f"🔍 [端口={self.debug_port}] 标签页 {handle} 在登录页面: {current_url}")
                        else:
                            # 不在登录页面，说明已登录
                            logged_in_window = handle
                            print(f"✅ [端口={self.debug_port}] 标签页 {handle} 已登录: {current_url}")
                            break  # 找到已登录标签页，立即退出
                except Exception as e:
                    print(f"⚠️ [端口={self.debug_port}] 检查标签页 {handle} 的URL失败: {e}")
                    continue
            
            # 决定保留哪个标签页
            if logged_in_window:
                # 优先保留已登录的标签页
                main_window = logged_in_window
                print(f"✅ [端口={self.debug_port}] 优先保留已登录的标签页: {main_window}")
            else:
                # 如果没有已登录的标签页，保留第一个标签页
                main_window = window_handles[0]
                print(f"⚠️ [端口={self.debug_port}] 未找到已登录标签页，保留第一个标签页: {main_window}")
            
            # 关闭其他所有标签页（使用循环，每次重新获取窗口句柄，确保关闭成功）
            closed_count = 0
            max_attempts = 10  # 最多尝试10次，避免无限循环
            attempt = 0
            
            while attempt < max_attempts:
                # 每次重新获取窗口句柄（因为关闭窗口后句柄会变化）
                current_handles = driver.window_handles
                if len(current_handles) <= 1:
                    # 只剩下一个标签页，清理完成
                    break
                
                # 找出需要关闭的标签页（除了主标签页外的所有标签页）
                handles_to_close = [h for h in current_handles if h != main_window]
                if not handles_to_close:
                    # 没有需要关闭的标签页，清理完成
                    break
                
                # 尝试关闭第一个需要关闭的标签页
                handle_to_close = handles_to_close[0]
                try:
                    # 先切换到要关闭的标签页
                    driver.switch_to.window(handle_to_close)
                    # 关闭标签页
                    driver.close()
                    closed_count += 1
                    print(f"✅ [端口={self.debug_port}] 已关闭多余标签页: {handle_to_close}")
                    
                    # 验证标签页是否真的关闭了
                    time.sleep(0.1)  # 短暂等待，让浏览器处理关闭操作
                    remaining_handles = driver.window_handles
                    if handle_to_close in remaining_handles:
                        print(f"⚠️ [端口={self.debug_port}] 标签页 {handle_to_close} 关闭后仍在列表中，可能关闭失败")
                    else:
                        print(f"✅ [端口={self.debug_port}] 标签页 {handle_to_close} 已成功关闭")
                except Exception as e:
                    print(f"⚠️ [端口={self.debug_port}] 关闭标签页 {handle_to_close} 失败: {e}")
                    # 即使关闭失败，也尝试继续关闭其他标签页
                    # 检查标签页是否还存在
                    try:
                        remaining_handles = driver.window_handles
                        if handle_to_close not in remaining_handles:
                            # 标签页已经不存在了（可能被其他方式关闭了）
                            closed_count += 1
                            print(f"✅ [端口={self.debug_port}] 标签页 {handle_to_close} 已不存在（可能已被关闭）")
                    except:
                        pass
                
                attempt += 1
            
            # 切换回主标签页
            try:
                # 重新获取窗口句柄，确保主标签页还在
                final_handles = driver.window_handles
                if main_window in final_handles:
                    driver.switch_to.window(main_window)
                    print(f"✅ [端口={self.debug_port}] 已切换回主标签页: {main_window}")
                elif final_handles:
                    # 主标签页不存在了，切换到第一个可用标签页
                    driver.switch_to.window(final_handles[0])
                    print(f"⚠️ [端口={self.debug_port}] 主标签页不存在，已切换到第一个可用标签页: {final_handles[0]}")
                else:
                    print(f"⚠️ [端口={self.debug_port}] 没有可用标签页")
            except Exception as e:
                print(f"⚠️ [端口={self.debug_port}] 切换回主标签页失败: {e}")
                # 如果切换失败，尝试切换到第一个可用标签页
                try:
                    remaining_handles = driver.window_handles
                    if remaining_handles:
                        driver.switch_to.window(remaining_handles[0])
                        print(f"✅ [端口={self.debug_port}] 已切换到第一个可用标签页: {remaining_handles[0]}")
                except Exception as e2:
                    print(f"❌ [端口={self.debug_port}] 无法切换到任何标签页: {e2}")
            
            # 最终验证：检查是否真的只剩下一个标签页
            try:
                final_handles = driver.window_handles
                if len(final_handles) > 1:
                    print(f"⚠️ [端口={self.debug_port}] 清理后仍有 {len(final_handles)} 个标签页，清理可能未完全成功")
                    print(f"   [端口={self.debug_port}] 剩余标签页: {final_handles}")
                else:
                    print(f"✅ [端口={self.debug_port}] 清理成功，当前只有 {len(final_handles)} 个标签页")
            except Exception as e:
                print(f"⚠️ [端口={self.debug_port}] 验证清理结果失败: {e}")
            
            # 清理后检查当前标签页状态
            try:
                final_url = driver.current_url.lower()
                is_login_page = any(keyword in final_url for keyword in ['/member/login', '/login', '登录'])
                if is_login_page:
                    print(f"⚠️ [端口={self.debug_port}] 清理后当前标签页仍在登录页面，可能需要重新登录")
                else:
                    print(f"✅ [端口={self.debug_port}] 清理后当前标签页已登录: {final_url}")
            except Exception as e:
                print(f"⚠️ [端口={self.debug_port}] 检查清理后标签页状态失败: {e}")
            
            if closed_count > 0:
                print(f"✅ [端口={self.debug_port}] 已关闭 {closed_count} 个多余标签页，确保只有一个标签页")
            
            return True
            
        except Exception as e:
            print(f"❌ [端口={self.debug_port}] 确保单标签页异常: {e}")
            return False
    
    def get_connection_status(self) -> Dict:
        """获取连接状态"""
        try:
            status = {
                'account_id': self.account_id,
                'browser_type': self.browser_type,
                'debug_port': self.debug_port,
                'user_data_dir': self.user_data_dir,
                'port_available': self.is_port_available(self.debug_port),
                'browser_running': self.is_browser_running_on_port(self.debug_port),
                'driver_connected': self.driver is not None
            }
            
            if status['browser_running']:
                tabs = self.get_browser_tabs_info(self.debug_port)
                status['tab_count'] = len(tabs)
                status['tabs'] = tabs
            
            return status
            
        except Exception as e:
            return {
                'account_id': self.account_id,
                'browser_type': self.browser_type,
                'error': str(e)
            }
    
    def cleanup(self):
        """清理资源"""
        try:
            if self.driver:
                try:
                    self.driver.quit()
                except:
                    pass
                self.driver = None
            
            print(f"✅ 端口管理器清理完成: {self.account_id}")
            
        except Exception as e:
            print(f"❌ 端口管理器清理异常: {e}")


# 全局端口管理器
_global_port_managers = {}

# 全局端口集合：记录每个账户使用过的所有端口（用于早盘开盘前关闭所有浏览器）
_account_ports = {}  # {account_id: set(port1, port2, ...)}

# ------------- 本地HTTP会话（用于localhost/127.0.0.1） -------------
_local_http = None

def _get_local_http():
    global _local_http
    if _local_http is None:
        session = requests.Session()
        retry_strategy = Retry(total=2, backoff_factor=0.3)
        adapter = HTTPAdapter(pool_connections=50, pool_maxsize=200, max_retries=retry_strategy, pool_block=False)
        session.mount('http://', adapter)
        session.mount('https://', adapter)
        _local_http = session
    return _local_http

def _add_port_to_account(account_id: str, port: int):
    """将端口添加到账户的端口集合中"""
    global _account_ports
    if account_id not in _account_ports:
        _account_ports[account_id] = set()
    _account_ports[account_id].add(port)

def get_port_manager(account_id: str, browser_type: str = "chrome") -> BrowserPortManager:
    """获取指定账号的端口管理器"""
    if account_id not in _global_port_managers:
        _global_port_managers[account_id] = BrowserPortManager(account_id, browser_type)
    return _global_port_managers[account_id]

def cleanup_port_manager(account_id: str):
    """清理指定账号的端口管理器"""
    if account_id in _global_port_managers:
        _global_port_managers[account_id].cleanup()
        del _global_port_managers[account_id]

def get_all_port_status():
    """获取所有端口状态"""
    status = {}
    for account_id, manager in _global_port_managers.items():
        status[account_id] = manager.get_connection_status()
    return status

def close_all_browsers_for_account(account_id: str) -> int:
    """
    关闭指定账户的所有浏览器进程（通过端口）
    
    Args:
        account_id: 账户ID
        
    Returns:
        int: 关闭的浏览器进程数量
    """
    global _account_ports
    closed_count = 0
    
    if account_id not in _account_ports:
        print(f"ℹ️ [关闭所有浏览器] 账户 {account_id} 没有记录的使用端口")
        return 0
    
    ports = list(_account_ports[account_id])
    print(f"🔄 [关闭所有浏览器] 账户 {account_id} 共使用 {len(ports)} 个端口: {ports}")
    
    import subprocess
    import psutil
    
    for port in ports:
        try:
            # 方法1：通过taskkill关闭指定端口的Chrome进程
            try:
                result = subprocess.run([
                    'taskkill', '/f', '/fi', f'COMMANDLINE eq *--remote-debugging-port={port}*'
                ], capture_output=True, text=True, timeout=5)
                
                if result.returncode == 0:
                    print(f"✅ [关闭所有浏览器] 已关闭端口 {port} 的Chrome进程（taskkill方式）")
                    closed_count += 1
                    continue  # 成功关闭，继续下一个端口
            except subprocess.TimeoutExpired:
                print(f"⚠️ [关闭所有浏览器] 关闭端口 {port} 的进程超时（taskkill方式）")
            except Exception as e:
                print(f"⚠️ [关闭所有浏览器] taskkill关闭端口 {port} 异常: {e}")
            
            # 方法2：通过psutil查找并关闭进程（支持Chrome和Firefox）
            found = False
            for proc in psutil.process_iter(['pid', 'name', 'cmdline']):
                try:
                    proc_name = proc.info['name'] or ''
                    cmdline = ' '.join(proc.info['cmdline'] or [])
                    
                    # 检查Chrome进程
                    if 'chrome' in proc_name.lower() and f'--remote-debugging-port={port}' in cmdline:
                        proc.terminate()
                        try:
                            proc.wait(timeout=3)
                        except psutil.TimeoutExpired:
                            proc.kill()
                        print(f"✅ [关闭所有浏览器] 已关闭端口 {port} 的Chrome进程（psutil方式，PID: {proc.pid}）")
                        closed_count += 1
                        found = True
                        break
                    
                    # 检查Firefox进程
                    if 'firefox' in proc_name.lower() and f'--remote-debugging-port={port}' in cmdline:
                        proc.terminate()
                        try:
                            proc.wait(timeout=3)
                        except psutil.TimeoutExpired:
                            proc.kill()
                        print(f"✅ [关闭所有浏览器] 已关闭端口 {port} 的Firefox进程（psutil方式，PID: {proc.pid}）")
                        closed_count += 1
                        found = True
                        break
                except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
                    continue
            
            if not found:
                print(f"ℹ️ [关闭所有浏览器] 端口 {port} 没有找到浏览器进程（可能已关闭）")
        except Exception as e:
            print(f"⚠️ [关闭所有浏览器] 处理端口 {port} 异常: {e}")
    
    # 等待进程完全关闭
    if closed_count > 0:
        time.sleep(2)
        print(f"✅ [关闭所有浏览器] 账户 {account_id} 共关闭 {closed_count} 个浏览器进程")
    
    return closed_count

def get_account_ports(account_id: str) -> set:
    """获取指定账户的所有端口"""
    global _account_ports
    return _account_ports.get(account_id, set())


if __name__ == "__main__":
    # 测试代码
    print("🧪 测试浏览器端口管理器...")
    
    # 创建端口管理器
    manager = BrowserPortManager("test_account", "chrome")
    
    # 检查端口状态
    status = manager.get_connection_status()
    print(f"端口状态: {status}")
    
    # 尝试连接浏览器
    driver = manager.connect_to_browser()
    if driver:
        print("✅ 浏览器连接成功")
        # 确保单标签页
        manager.ensure_single_tab(driver)
        driver.quit()
    else:
        print("❌ 浏览器连接失败")
    
    # 清理
    manager.cleanup()
    
    print("✅ 测试完成")
