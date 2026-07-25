import os
import subprocess
import sys
import time
from urllib.parse import urlparse, urlsplit
import platform

from selenium import webdriver
from selenium.webdriver.support import expected_conditions as EC
from selenium.common import WebDriverException, TimeoutException
from selenium.webdriver.support.wait import WebDriverWait
from selenium.webdriver.firefox.options import Options as FirefoxOptions
from selenium.webdriver.firefox.service import service as FirefoxService
# 谷歌
from selenium.webdriver.chrome.options import Options as ChromeOptions
from selenium.webdriver.chrome.service import Service as ChromeService

from xy_client.services.tools.Configs import Configs

# 关键修复：配置 Selenium WebDriver 的连接池，解决"Connection pool is full"问题
# Selenium 使用 urllib3 管理连接，需要配置连接池大小
import urllib3
from urllib3.poolmanager import PoolManager

# 创建自定义的 PoolManager，增加连接池大小
class CustomPoolManager(PoolManager):
    def __init__(self, *args, **kwargs):
        # 设置连接池参数：每个host最多50个连接池，每个连接池最多200个连接
        kwargs.setdefault('num_pools', 50)  # 连接池数量（从默认10增加到50）
        kwargs.setdefault('maxsize', 200)   # 每个连接池的最大连接数（从默认10增加到200）
        kwargs.setdefault('block', False)   # 连接池满时不阻塞，创建新连接
        super().__init__(*args, **kwargs)

# 配置 Selenium 的 RemoteConnection 使用自定义连接池
# 通过 monkey patch 的方式修改 Selenium 的连接池配置
try:
    from selenium.webdriver.remote.remote_connection import RemoteConnection
    from selenium.webdriver.remote import utils
    
    # 保存原始的 _get_connection_manager 方法
    _original_get_connection_manager = RemoteConnection._get_connection_manager
    
    def _patched_get_connection_manager(self):
        """修改后的连接管理器，使用自定义连接池"""
        if not hasattr(self, '_connection_manager') or self._connection_manager is None:
            # 使用自定义的 PoolManager
            self._connection_manager = CustomPoolManager()
        return self._connection_manager
    
    # 应用 monkey patch
    RemoteConnection._get_connection_manager = _patched_get_connection_manager
    print("✅ Selenium WebDriver 连接池已优化: pool_connections=50, pool_maxsize=200")
except Exception as e:
    # 如果 patch 失败，不影响程序运行，只记录警告
    print(f"⚠️ Selenium 连接池优化失败（不影响运行）: {e}")

# from webdriver_manager.chrome import ChromeDriverManager
config = Configs()


def getHostByUrl(url):
    parts = urlparse(url)
    host = parts.netloc
    scheme = parts.scheme
    return scheme + '://' + host


def getHostNameData(current_url=''):
    parsed_url = urlsplit(current_url)
    hostname = parsed_url.hostname
    port = parsed_url.port

    if port:
        server_url = f'{parsed_url.scheme}://{hostname}:{port}'
    else:
        server_url = f'{parsed_url.scheme}://{hostname}'

    return server_url, hostname



def open_url_with_retries(driver, url, max_retries=5, wait_time=2):
    '''
    打开魔王也重试次数
    '''
    attempts = 0
    while attempts < max_retries:
        try:
            driver.get(url)
            # 如果需要，等待页面完全加载
            time.sleep(wait_time)  # 可以使用显式等待替代
            return True
        except WebDriverException as e:
            print(f"打开网页失败: {e}. 正在重试 {attempts + 1}/{max_retries} ...")
            attempts += 1
            time.sleep(wait_time)
    return False


def wait_for_element(browser, by, value, timeout=30):
    try:
        element = WebDriverWait(browser, timeout).until(
            EC.presence_of_element_located((by, value))
        )
        return element
    except TimeoutException:
        print(f"元素 {value} 在 {timeout} 秒内未能加载。")
        return None


def getDriver(driver_name, port=9222):
    print('driver_name:::::::::::', driver_name)
    
    # 根据浏览器类型启动不同的浏览器
    if driver_name == "chrome":
        config_driver_path = str(
            config.get_config('chromedriver_path') or ''
        ).strip()
        if config_driver_path.lower() in ('', 'auto'):
            print(
                "ℹ️ ChromeDriver自动下载已禁用；当前使用CDP/HTTP模式，"
                "不创建WebDriver连接"
            )
            return None

        # Chrome 浏览器启动逻辑
        # 关键优化：检查Chrome调试服务是否就绪（不仅仅是端口开放）
        # 人为操作浏览器很顺畅，但程序连接WebDriver却经常超时
        # 原因：端口开放 ≠ Chrome调试服务就绪
        # 解决方案：检查Chrome调试接口（/json）是否响应，确保服务真的就绪
        import socket
        import requests
        
        def is_port_open(port):
            """检查端口是否开放（TCP连接）"""
            try:
                with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
                    s.settimeout(1)
                    result = s.connect_ex(('localhost', port))
                    return result == 0
            except:
                return False
        
        def is_chrome_debug_ready(port):
            """检查Chrome调试服务是否就绪（关键优化：确保服务真的可用）"""
            try:
                # 检查Chrome调试接口是否响应
                response = requests.get(f'http://localhost:{port}/json', timeout=2)
                if response.status_code == 200:
                    # 尝试解析JSON，确保服务真的就绪
                    data = response.json()
                    return True
            except:
                pass
            return False
        
        # 等待Chrome调试服务就绪（不仅仅是端口开放）
        max_wait = 10  # 最多等待10秒
        chrome_ready = False
        
        for i in range(max_wait):
            # 先检查端口是否开放
            if is_port_open(port):
                # 端口开放后，再检查Chrome调试服务是否就绪
                if is_chrome_debug_ready(port):
                    chrome_ready = True
                    print(f"✅ Chrome调试服务已就绪（等待时间: {i+1}秒）")
                    break
                else:
                    # 端口开放但服务未就绪，继续等待
                    if (i + 1) % 2 == 0:  # 每2秒输出一次
                        print(f"⏳ [getDriver] 端口 {port} 已开放，但Chrome调试服务未就绪，继续等待... ({i+1}/{max_wait}秒)")
            else:
                # 端口未开放，继续等待
                if (i + 1) % 3 == 0:  # 每3秒输出一次
                    print(f"⏳ [getDriver] 等待端口 {port} 就绪... ({i+1}/{max_wait}秒)")
            
            if i < max_wait - 1:
                time.sleep(1)
        
        if not chrome_ready:
            # 最后再检查一次
            if is_port_open(port):
                if is_chrome_debug_ready(port):
                    chrome_ready = True
                    print(f"✅ Chrome调试服务已就绪（最终检查）")
                else:
                    print(f"⚠️ 端口 {port} 已开放，但Chrome调试服务未就绪，可能Chrome正在启动中")
                    # 继续尝试连接，但会快速失败
            else:
                print(f"❌ 等待{max_wait}秒后端口 {port} 仍未开放，Chrome可能启动失败")
                raise Exception(f"等待{max_wait}秒后端口 {port} 仍不可用，Chrome调试端口可能未启动")
        
        # 固定 ChromeDriver 版本
        # driver_path = ChromeDriverManager(driver_version="125").install()

        # driver_path = r"D:\www\工具\chrome\125\chromedriver-win64\chromedriver.exe"
        # 优先使用配置文件中的chromedriver路径
        print(f"🔍 从配置文件读取的chromedriver_path: {config_driver_path}")
        print(f"🔍 配置文件路径类型: {type(config_driver_path)}")
        
        # 处理相对路径，转换为绝对路径
        driver_path = None
        if config_driver_path and config_driver_path.lower() != 'auto':
            if os.path.isabs(config_driver_path):
                # 如果是绝对路径，直接使用
                driver_path = config_driver_path
            else:
                # 如果是相对路径，需要转换为绝对路径
                # 相对路径相对于xy_client目录（与LuckyClientOP.py同级）
                # 当前文件路径: xy_client/services/tools/tools.py
                # 需要回到: xy_client/
                xy_client_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
                # 清理相对路径中的 ./ 前缀
                clean_path = config_driver_path.lstrip('./')
                driver_path = os.path.join(xy_client_dir, clean_path)
                print(f"🔍 相对路径转换为绝对路径: {driver_path}")
            
            if os.path.exists(driver_path):
                print(f"✅ 使用配置文件中的chromedriver路径: {driver_path}")
            else:
                print(f"⚠️ 配置文件路径不存在: {driver_path}")
                driver_path = None
        
        if not driver_path and config_driver_path.lower() != 'auto':
            print(f"⚠️ 配置文件路径不存在或为空，使用默认路径")
            # 如果配置文件中的路径不存在，使用默认路径
            application_path = getApplicationPath()  # 获取可执行文件所在的目录
            if platform.system() == 'Windows':
                # 尝试多个可能的路径
                possible_paths = [
                    os.path.join(application_path, 'xy_client', 'chromedriver-win64', 'chromedriver.exe'),
                    os.path.join(application_path, 'chromedriver-win64', 'chromedriver.exe'),
                    os.path.join(application_path, 'chromedriver.exe')
                ]
                
                for path in possible_paths:
                    if os.path.exists(path):
                        driver_path = path
                        print(f"✅ 找到ChromeDriver: {driver_path}")
                        break
                
                if not driver_path:
                    print(f"⚠️ 所有可能的路径都不存在:")
                    for path in possible_paths:
                        print(f"  - {path}")
                    raise FileNotFoundError(f"找不到chromedriver，请确保文件存在于以下路径之一: {possible_paths}")
                    
            elif platform.system() == 'Darwin':  # macOS
                driver_path = os.path.join(application_path, 'chromedriver-mac64', 'chromedriver')
            else:
                raise Exception(f"Unsupported OS: {platform.system()}")
            print('driver_path::::::::', driver_path)

        # 检查chromedriver是否存在
        if driver_path and not os.path.exists(driver_path):
            raise FileNotFoundError(f"找不到chromedriver，请确保文件存在于: {driver_path}")

        # Only an explicit local executable is allowed. Never invoke Selenium
        # Manager, because customer networks may not reach its download sites.
        service = ChromeService(executable_path=driver_path)

        # 创建 Chrome 浏览器选项
        chrome_options = ChromeOptions()
        
        # 检查Chrome浏览器路径
        chrome_binary = config.get_config('binary_location')
        if chrome_binary and os.path.exists(chrome_binary):
            chrome_options.binary_location = chrome_binary
            print(f"✅ 使用Chrome浏览器路径: {chrome_binary}")
        else:
            print(f"⚠️ Chrome浏览器路径不存在或未配置: {chrome_binary}")
            print("尝试自动检测Chrome路径...")
            
            # 自动检测Chrome路径
            from xy_client.services.tools.ChromePathDetector import auto_detect_chrome_path
            detected_path = auto_detect_chrome_path()
            if detected_path and os.path.exists(detected_path):
                chrome_options.binary_location = detected_path
                print(f"✅ 自动检测到Chrome路径: {detected_path}")
            else:
                print("⚠️ 自动检测Chrome路径失败，使用系统默认路径")

        chrome_options.add_argument("--ignore-certificate-errors")
        # 连接到已启动的Chrome调试端口
        chrome_options.debugger_address = "localhost:" + str(port)

        print(f'Chrome选项配置完成，连接到调试端口: localhost:{port}')
        
        # 再次确认端口可用性
        if not is_port_open(port):
            raise Exception(f"端口 {port} 不可用，无法创建WebDriver连接")
        
        # 关键优化：设置页面加载策略为eager（快速加载，不等待所有资源）
        # eager策略：DOM加载完成即可，不等待图片、CSS等资源，大幅提升加载速度
        # 注意：必须在创建WebDriver之前设置
        chrome_options.page_load_strategy = 'eager'
        
        try:
            # 关键修复：设置连接超时和页面加载超时，避免长时间卡住
            # 通过设置 command_executor 的超时参数来控制连接超时
            import urllib3
            from selenium.webdriver.remote.remote_connection import RemoteConnection
            
            # 关键优化：在Chrome调试服务就绪后，WebDriver连接应该很快（1-3秒）
            # 如果连接超时，说明服务可能还没完全就绪，需要重试
            # 人为操作浏览器很顺畅，因为不需要通过WebDriver协议
            # 程序操作需要等待Chrome调试服务完全就绪，然后连接应该很快
            driver = None
            max_retries = 2  # 最多重试2次
            connection_timeout = 5  # 关键优化：服务就绪后，连接应该很快（5秒足够）
            
            for retry in range(max_retries):
                # 每次重试前，再次确认Chrome调试服务就绪
                if retry > 0:
                    print(f"🔄 [重试 {retry}/{max_retries}] 再次检查Chrome调试服务...")
                    if not is_chrome_debug_ready(port):
                        print(f"⚠️ Chrome调试服务未就绪，等待1秒后重试...")
                        time.sleep(1)
                        # 再次检查
                        if not is_chrome_debug_ready(port):
                            print(f"❌ Chrome调试服务仍未就绪，无法连接")
                            raise Exception(f"Chrome调试服务未就绪，无法创建WebDriver连接")
                    else:
                        print(f"✅ Chrome调试服务已就绪，开始连接...")
                
                driver_result: list = [None]
                driver_error: list = [None]
                
                def create_driver():
                    try:
                        # 页面加载策略已在创建Chrome选项时设置
                        temp_driver = webdriver.Chrome(service=service, options=chrome_options)
                        
                        # 关键优化2：缩短超时时间，避免阻塞下注流程
                        # 设置页面加载超时（10秒，快速失败，避免阻塞下注）
                        temp_driver.set_page_load_timeout(10)
                        # 关键优化3：缩短隐式等待时间（从5秒缩短到2秒），使用显式等待替代
                        # 隐式等待时间过长会导致所有元素查找都等待，影响性能
                        temp_driver.implicitly_wait(2)
                        # 设置脚本超时（10秒，快速失败）
                        temp_driver.set_script_timeout(10)
                        driver_result[0] = temp_driver
                    except Exception as e:
                        driver_error[0] = e
                
                # 在独立线程中创建WebDriver，设置超时控制
                import threading
                create_thread = threading.Thread(target=create_driver, daemon=False)  # 改为非daemon线程，避免提前终止
                create_thread.start()
                create_thread.join(timeout=connection_timeout)  # 服务就绪后，5秒应该足够
                
                if create_thread.is_alive():
                    # 创建超时
                    if retry < max_retries - 1:
                        print(f"⚠️ WebDriver连接创建超时（{connection_timeout}秒），Chrome调试服务可能未完全就绪，重试中... ({retry+1}/{max_retries})")
                        connection_timeout = 8  # 重试时增加到8秒
                        time.sleep(1)  # 等待1秒后重试
                        continue
                    else:
                        print(f"❌ WebDriver连接创建超时（{connection_timeout}秒），Chrome调试服务可能未完全就绪")
                        raise Exception(f"WebDriver连接创建超时（{connection_timeout}秒），Chrome调试服务可能未完全就绪")
                elif driver_error[0]:
                    # 创建失败
                    if retry < max_retries - 1:
                        print(f"⚠️ WebDriver连接创建失败: {driver_error[0]}，重试中... ({retry+1}/{max_retries})")
                        time.sleep(1)  # 等待1秒后重试
                        continue
                    else:
                        print(f"❌ WebDriver连接创建失败: {driver_error[0]}")
                        raise driver_error[0]
                elif driver_result[0]:
                    driver = driver_result[0]
                    print(f"✅ Chrome driver创建成功{'（重试' + str(retry+1) + '次后）' if retry > 0 else ''}")
                    break
                else:
                    if retry < max_retries - 1:
                        print(f"⚠️ WebDriver连接创建返回None，重试中... ({retry+1}/{max_retries})")
                        time.sleep(1)  # 等待1秒后重试
                        continue
                    else:
                        raise Exception("WebDriver连接创建失败，返回None")
            
            if driver is None:
                raise Exception("WebDriver连接创建失败，返回None")
            
            # 验证连接是否正常（使用超时保护）
            try:
                import threading
                title_result: list = [None]
                title_error: list = [None]
                
                def get_title():
                    try:
                        title_result[0] = driver.title
                    except Exception as e:
                        title_error[0] = e
                
                title_thread = threading.Thread(target=get_title, daemon=True)
                title_thread.start()
                title_thread.join(timeout=3)  # 关键优化：缩短到3秒，快速失败，避免阻塞下注
                
                if title_thread.is_alive():
                    print("⚠️ WebDriver连接验证超时（3秒），但继续使用")
                elif title_error[0]:
                    print(f"⚠️ WebDriver连接验证失败: {title_error[0]}，但继续使用")
                else:
                    print("✅ WebDriver连接验证成功")
            except Exception as e:
                print(f"⚠️ WebDriver连接验证异常: {e}，但继续使用")
                
        except Exception as e:
            error_msg = str(e)
            print(f"❌ Chrome driver创建失败: {e}")
            
            # 检查是否是版本不匹配错误
            if "This version of ChromeDriver only supports Chrome version" in error_msg:
                # 提取版本信息
                import re
                supported_version = re.search(r'supports Chrome version (\d+)', error_msg)
                current_version = re.search(r'Current browser version is ([\d.]+)', error_msg)
                
                supported_ver = supported_version.group(1) if supported_version else "未知"
                current_ver = current_version.group(1) if current_version else "未知"
                
                # 弹出提示框
                try:
                    from PyQt5.QtWidgets import QApplication, QMessageBox
                    from PyQt5.QtCore import Qt
                    
                    # 确保有QApplication实例
                    app = QApplication.instance()
                    if app is None:
                        app = QApplication([])
                    
                    msg_box = QMessageBox()
                    msg_box.setIcon(QMessageBox.Warning)
                    msg_box.setWindowTitle("ChromeDriver版本不匹配")
                    msg_box.setText("ChromeDriver版本与Chrome浏览器版本不匹配")
                    msg_box.setDetailedText(f"""
Chrome浏览器版本: {current_ver}
ChromeDriver支持版本: {supported_ver}

解决方案：
1. 下载与Chrome {current_ver.split('.')[0]}匹配的ChromeDriver
2. 替换现有的ChromeDriver文件
3. 下载地址: https://chromedriver.chromium.org/downloads

当前ChromeDriver路径: {driver_path}
                    """.strip())
                    msg_box.setStandardButtons(QMessageBox.Ok)
                    msg_box.exec_()
                except Exception as gui_e:
                    print(f"弹框显示失败: {gui_e}")
                    print(f"版本不匹配：Chrome {current_ver} vs ChromeDriver支持版本 {supported_ver}")
            
            # 如果是版本不匹配，直接抛出异常，不再尝试其他方法
            if "This version of ChromeDriver only supports Chrome version" in error_msg:
                raise Exception(f"ChromeDriver版本不匹配：{error_msg}")
            
            # 尝试不指定binary_location（删除设置为None的行，因为会报错）
            print("尝试不指定Chrome路径...")
            try:
                # 创建新的Chrome选项，不设置binary_location
                fallback_options = ChromeOptions()
                fallback_options.add_argument("--ignore-certificate-errors")
                fallback_options.debugger_address = "localhost:" + str(port)
                
                driver = webdriver.Chrome(service=service, options=fallback_options)
                
                # 设置超时参数（快速失败，避免阻塞下注）
                driver.set_page_load_timeout(10)  # 缩短到10秒，快速失败
                driver.implicitly_wait(5)  # 缩短到5秒
                driver.set_script_timeout(10)  # 缩短到10秒
                
                if driver is None:
                    raise Exception("Chrome driver创建失败，返回None")
                print("✅ Chrome driver创建成功（不指定路径）")
                
                # 验证连接（使用超时保护）
                try:
                    import threading
                    title_result = [None]
                    title_error = [None]
                    
                    def get_title():
                        try:
                            title_result[0] = driver.title
                        except Exception as e:
                            title_error[0] = e
                    
                    title_thread = threading.Thread(target=get_title, daemon=True)
                    title_thread.start()
                    title_thread.join(timeout=3)  # 关键优化：缩短到3秒，快速失败，避免阻塞下注
                    
                    if title_thread.is_alive():
                        print("⚠️ WebDriver连接验证超时（3秒，备用方案），但继续使用")
                    elif title_error[0]:
                        print(f"⚠️ WebDriver连接验证失败（备用方案）: {title_error[0]}，但继续使用")
                    else:
                        print("✅ WebDriver连接验证成功（备用方案）")
                except Exception as e:
                    print(f"⚠️ WebDriver连接验证异常（备用方案）: {e}，但继续使用")
                    
            except Exception as e2:
                print(f"❌ Chrome driver创建最终失败: {e2}")
                raise e2
    elif driver_name == "firefox":
        # Firefox 浏览器启动逻辑
        # 设置geckodriver的路径
        geckodriver_path = os.path.join(os.getcwd(), 'geckodriver.exe')
        print('geckodriver_path', geckodriver_path)

        # 检查geckodriver是否存在
        if not os.path.exists(geckodriver_path):
            print(f"❌ geckodriver不存在: {geckodriver_path}")
            # 尝试从配置文件获取路径
            config_geckodriver = config.get_config('geckodriver_path')
            if config_geckodriver and os.path.exists(config_geckodriver):
                geckodriver_path = config_geckodriver
                print(f"✅ 使用配置文件中的geckodriver路径: {geckodriver_path}")
            else:
                raise FileNotFoundError(f"找不到geckodriver，请确保文件存在于: {geckodriver_path}")

        # 创建Firefox服务，明确指定geckodriver路径
        from selenium.webdriver.firefox.service import Service as FirefoxService
        service = FirefoxService(executable_path=geckodriver_path)
        
        options = FirefoxOptions()
        # options.binary_location = r'C:\Program Files\Mozilla Firefox\firefox.exe'
        # options.headless = True  # 无头模式
        options.set_preference("devtools.debugger.remote-port", port)  # 设置调试端口
        options.set_preference("network.stricttransportsecurity.preloadlist", False)
        options.set_preference("security.cert_pinning.enforcement_level", 0)
        options.set_preference("security.enterprise_roots.enabled", True)
        options.set_preference("webdriver_accept_untrusted_certs", True)
        options.set_preference("webdriver_assume_untrusted_issuer", False)

        # 使用Service创建Firefox驱动
        driver = webdriver.Firefox(service=service, options=options)
        
        # 关键修复：Firefox也需要设置页面加载超时和隐式等待
        try:
            driver.set_page_load_timeout(30)  # 页面加载超时30秒
            driver.implicitly_wait(5)  # 隐式等待5秒
            driver.set_script_timeout(30)  # 脚本超时30秒
            print('✅ Firefox浏览器超时设置完成')
        except Exception as timeout_e:
            print(f'⚠️ Firefox超时设置失败: {timeout_e}')

    return driver


def switchFirstWindow(driver, tabIndex=0):
    """
    切换到指定index窗口
    """
    window_handles = driver.window_handles
    driver.switch_to.window(window_handles[tabIndex])


def closeExceptWindow(driver, tabIndex=0):
    """
    关闭除了指定index的所有窗口
    """
    window_handles = driver.window_handles
    # 保留第一个窗口句柄
    index_window_handle = window_handles[tabIndex]
    # 遍历所有窗口句柄并关闭除第一个窗口外的所有窗口
    for handle in window_handles:
        if handle != index_window_handle:
            driver.switch_to.window(handle)
            driver.close()

    # 最后切换回第一个窗口
    driver.switch_to.window(index_window_handle)


def getApplicationPath():
    """
    获取可执行文件所在的目录
    """
    if getattr(sys, 'frozen', False):
        # 如果是打包后的可执行文件
        application_path = os.path.dirname(sys.executable)
    else:
        # 如果是开发环境中的脚本
        application_path = os.getcwd()

    return application_path


def cmdOpenDriver(username=None, port=9222):
    """
    打开浏览器
    """
    # 检测操作系统
    system = platform.system()

    if system == "Windows":
        chrome_path = config.get_config('binary_location')
        # 使用当前用户目录下的临时文件夹，避免权限问题
        import tempfile
        user_data_dir = os.path.join(tempfile.gettempdir(), f"chrome_debug_{port}", username or "default")
        
        # 确保目录存在
        os.makedirs(user_data_dir, exist_ok=True)
        
        # 定义Windows CMD命令
        cmd_command = f'"{chrome_path}" --remote-debugging-port={port} --user-data-dir="{user_data_dir}" --no-first-run --no-default-browser-check'
        print(f"启动Chrome命令: {cmd_command}")
    elif system == "Darwin":  # macOS
        chrome_path = config.get_config('binary_location_mac')
        # 定义macOS终端命令
        cmd_command = f'"{chrome_path}" --remote-debugging-port={port} --user-data-dir="/tmp/9222/{username}"'
    else:
        raise NotImplementedError("Unsupported operating system")

    # 执行CMD命令
    subprocess.Popen(cmd_command, shell=True)
