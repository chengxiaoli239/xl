import time
import subprocess
import psutil
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.common.exceptions import WebDriverException, TimeoutException


class WebDriverRecovery:
    """WebDriver连接恢复工具 - 解决淘宝订单采集时的连接超时问题"""
    
    def __init__(self):
        self.chrome_processes = []
        self.driver_port = None
        self.last_recovery_time = 0
        self.recovery_cooldown = 60  # 恢复冷却时间（秒）
    
    def check_webdriver_health(self, driver):
        """
        检查WebDriver连接健康状态
        
        Args:
            driver: WebDriver实例
            
        Returns:
            bool: 连接是否健康
        """
        try:
            if driver is None:
                return False
            
            # 尝试获取当前URL，检查连接状态
            current_url = driver.current_url
            print(f"✅ WebDriver连接正常，当前URL: {current_url}")
            return True
            
        except (WebDriverException, TimeoutException) as e:
            print(f"⚠️ WebDriver连接异常: {e}")
            return False
        except Exception as e:
            print(f"❌ WebDriver健康检查失败: {e}")
            return False
    
    def force_kill_chrome_processes(self):
        """强制结束所有Chrome进程"""
        try:
            print("🔄 开始强制结束Chrome进程...")
            
            # 查找所有Chrome相关进程
            chrome_processes = []
            for proc in psutil.process_iter(['pid', 'name', 'cmdline']):
                try:
                    if proc.info['name'] and 'chrome' in proc.info['name'].lower():
                        chrome_processes.append(proc)
                except (psutil.NoSuchProcess, psutil.AccessDenied):
                    continue
            
            # 强制结束进程
            killed_count = 0
            for proc in chrome_processes:
                try:
                    proc.kill()
                    killed_count += 1
                    print(f"✅ 已结束Chrome进程: PID {proc.pid}")
                except (psutil.NoSuchProcess, psutil.AccessDenied) as e:
                    print(f"⚠️ 无法结束进程 {proc.pid}: {e}")
            
            print(f"✅ 共结束 {killed_count} 个Chrome进程")
            
            # 等待进程完全结束
            time.sleep(3)
            
            return True
            
        except Exception as e:
            print(f"❌ 强制结束Chrome进程失败: {e}")
            return False
    
    def cleanup_chrome_driver(self):
        """清理ChromeDriver进程"""
        try:
            print("🔄 开始清理ChromeDriver进程...")
            
            # 查找ChromeDriver进程
            driver_processes = []
            for proc in psutil.process_iter(['pid', 'name', 'cmdline']):
                try:
                    if proc.info['name'] and 'chromedriver' in proc.info['name'].lower():
                        driver_processes.append(proc)
                except (psutil.NoSuchProcess, psutil.AccessDenied):
                    continue
            
            # 结束ChromeDriver进程
            killed_count = 0
            for proc in driver_processes:
                try:
                    proc.kill()
                    killed_count += 1
                    print(f"✅ 已结束ChromeDriver进程: PID {proc.pid}")
                except (psutil.NoSuchProcess, psutil.AccessDenied) as e:
                    print(f"⚠️ 无法结束ChromeDriver进程 {proc.pid}: {e}")
            
            print(f"✅ 共结束 {killed_count} 个ChromeDriver进程")
            
            # 等待进程完全结束
            time.sleep(2)
            
            return True
            
        except Exception as e:
            print(f"❌ 清理ChromeDriver进程失败: {e}")
            return False
    
    def reset_webdriver_session(self, mainWindow):
        """
        重置WebDriver会话
        
        Args:
            mainWindow: 主窗口实例
            
        Returns:
            bool: 重置是否成功
        """
        try:
            current_time = time.time()
            
            # 检查恢复冷却时间
            if current_time - self.last_recovery_time < self.recovery_cooldown:
                print(f"⚠️ 恢复冷却中，还需等待 {self.recovery_cooldown - (current_time - self.last_recovery_time):.0f} 秒")
                return False
            
            print("🔄 开始重置WebDriver会话...")
            
            # 关闭现有WebDriver
            if hasattr(mainWindow, 'driver') and mainWindow.driver:
                try:
                    mainWindow.driver.quit()
                    print("✅ 已关闭现有WebDriver")
                except Exception as e:
                    print(f"⚠️ 关闭WebDriver异常: {e}")
            
            # 强制清理Chrome进程
            self.force_kill_chrome_processes()
            
            # 清理ChromeDriver进程
            self.cleanup_chrome_driver()
            
            # 重新创建WebDriver
            if self._create_new_webdriver(mainWindow):
                self.last_recovery_time = current_time
                print("✅ WebDriver会话重置成功")
                return True
            else:
                print("❌ WebDriver会话重置失败")
                return False
                
        except Exception as e:
            print(f"❌ 重置WebDriver会话异常: {e}")
            return False
    
    def _create_new_webdriver(self, mainWindow):
        """创建新的WebDriver实例"""
        try:
            print("🔄 创建新的WebDriver实例...")
            
            # 设置Chrome选项
            chrome_options = Options()
            chrome_options.add_argument('--no-sandbox')
            chrome_options.add_argument('--disable-dev-shm-usage')
            chrome_options.add_argument('--disable-gpu')
            chrome_options.add_argument('--disable-extensions')
            chrome_options.add_argument('--disable-plugins')
            chrome_options.add_argument('--disable-images')
            chrome_options.add_argument('--disable-javascript')
            chrome_options.add_argument('--disable-web-security')
            chrome_options.add_argument('--allow-running-insecure-content')
            chrome_options.add_argument('--disable-blink-features=AutomationControlled')
            chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
            chrome_options.add_experimental_option('useAutomationExtension', False)
            
            # 设置页面加载策略
            chrome_options.page_load_strategy = 'eager'
            
            # 创建WebDriver
            driver = webdriver.Chrome(options=chrome_options)
            
            # 设置超时时间
            driver.set_page_load_timeout(30)
            driver.implicitly_wait(10)
            
            # 设置窗口大小
            driver.set_window_size(1920, 1080)
            
            # 更新主窗口的driver引用
            mainWindow.driver = driver
            
            print("✅ 新WebDriver实例创建成功")
            return True
            
        except Exception as e:
            print(f"❌ 创建WebDriver实例失败: {e}")
            return False
    
    def recover_taobao_session(self, mainWindow):
        """
        恢复淘宝订单采集会话
        
        Args:
            mainWindow: 主窗口实例
            
        Returns:
            bool: 恢复是否成功
        """
        try:
            print("🔄 开始恢复淘宝订单采集会话...")
            
            # 重置WebDriver会话
            if not self.reset_webdriver_session(mainWindow):
                return False
            
            # 导航到淘宝登录页面
            try:
                mainWindow.driver.get("https://login.taobao.com/")
                print("✅ 已导航到淘宝登录页面")
                
                # 等待页面加载
                time.sleep(5)
                
                # 检查是否需要登录
                if "login.taobao.com" in mainWindow.driver.current_url:
                    print("✅ 淘宝登录页面加载成功，等待用户登录")
                    mainWindow.is_need_login = 1
                    return True
                else:
                    print("⚠️ 淘宝登录页面加载异常")
                    return False
                    
            except Exception as e:
                print(f"❌ 导航到淘宝登录页面失败: {e}")
                return False
                
        except Exception as e:
            print(f"❌ 恢复淘宝订单采集会话异常: {e}")
            return False
    
    def monitor_webdriver_health(self, mainWindow, check_interval=30):
        """
        监控WebDriver健康状态
        
        Args:
            mainWindow: 主窗口实例
            check_interval: 检查间隔（秒）
        """
        try:
            if not hasattr(mainWindow, 'driver') or not mainWindow.driver:
                print("⚠️ WebDriver未初始化，跳过健康检查")
                return
            
            # 检查连接健康状态
            if not self.check_webdriver_health(mainWindow.driver):
                print("🚨 检测到WebDriver连接异常，尝试自动恢复...")
                
                # 尝试自动恢复
                if self.recover_taobao_session(mainWindow):
                    print("✅ WebDriver自动恢复成功")
                else:
                    print("❌ WebDriver自动恢复失败，需要手动处理")
            
        except Exception as e:
            print(f"❌ WebDriver健康监控异常: {e}")


# 创建全局WebDriver恢复工具实例
webdriver_recovery = WebDriverRecovery()


def auto_recover_webdriver(mainWindow):
    """
    自动恢复WebDriver连接
    
    Args:
        mainWindow: 主窗口实例
        
    Returns:
        bool: 恢复是否成功
    """
    return webdriver_recovery.recover_taobao_session(mainWindow)


def check_and_recover_webdriver(mainWindow):
    """
    检查并恢复WebDriver连接
    
    Args:
        mainWindow: 主窗口实例
        
    Returns:
        bool: 连接是否正常
    """
    if webdriver_recovery.check_webdriver_health(mainWindow.driver):
        return True
    else:
        print("🔄 WebDriver连接异常，开始自动恢复...")
        return webdriver_recovery.recover_taobao_session(mainWindow)


def force_cleanup_chrome(mainWindow):
    """
    强制清理Chrome进程和WebDriver
    
    Args:
        mainWindow: 主窗口实例
        
    Returns:
        bool: 清理是否成功
    """
    try:
        print("🔄 开始强制清理Chrome环境...")
        
        # 关闭WebDriver
        if hasattr(mainWindow, 'driver') and mainWindow.driver:
            try:
                mainWindow.driver.quit()
                mainWindow.driver = None
                print("✅ 已关闭WebDriver")
            except Exception as e:
                print(f"⚠️ 关闭WebDriver异常: {e}")
        
        # 强制清理Chrome进程
        webdriver_recovery.force_kill_chrome_processes()
        
        # 清理ChromeDriver进程
        webdriver_recovery.cleanup_chrome_driver()
        
        print("✅ Chrome环境清理完成")
        return True
        
    except Exception as e:
        print(f"❌ 强制清理Chrome环境失败: {e}")
        return False
