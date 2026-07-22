import logging
import threading
import time
import concurrent.futures
import os

import json

import requests
from requests.adapters import HTTPAdapter
from selenium.common.exceptions import NoSuchWindowException
from selenium.webdriver.support.wait import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys

from xy_client.services.systems_users import SystemsUsers
from urllib import parse

from xy_client.services.systems_users.LogService import p
from xy_client.services.systems_users.SystemsUsers import checkUserLoginJob, user_login_job, checkAgentLoginJob, \
    agent_login_job, pushTasksBetRst, updateRobotId, getBetPlanTasks
from xy_client.services.systems_users.common import getAccountByToken, pushErrorLog
from xy_client.services.tools import tools
from xy_client.services.tools.Configs import Configs
from xy_client.services.tools.GlobalSession import GlobalSession
from xy_client.services.tools.tools import getHostNameData

from urllib3.poolmanager import PoolManager
from urllib3.util.retry import Retry
globalSession = GlobalSession().get_session()

# 在文件开头添加清空Cookie的方法
def clear_cookies_and_stop_tasks(mainWindow, error_msg="Cookie无效"):
    """
    清空浏览器Cookie并停止所有任务
    
    Args:
        mainWindow: 主窗口实例
        error_msg: 错误消息
        
    Returns:
        bool: 是否成功清空Cookie
    """
    try:
        print(f"🚨 检测到{error_msg}，开始清空Cookie并停止任务")
        
        # 清空浏览器Cookie
        if hasattr(mainWindow, 'driver') and mainWindow.driver:
            # 清空所有Cookie
            mainWindow.driver.delete_all_cookies()
            print("✅ 已清空浏览器所有Cookie")
            
            # 清空内存中的Cookie
            mainWindow.browser_cookies = ""
            print("✅ 已清空内存中的Cookie")
            
            # 设置登录状态为False，让登录检测脚本自动处理
            if hasattr(mainWindow, 'updateLoginStatus'):
                mainWindow.updateLoginStatus(False)
            else:
                mainWindow.login_status = False
                print("✅ 已设置登录状态为False，登录检测脚本将自动处理重新登录")
            
            print("🔄 Cookie已清空，登录检测脚本将自动检测并重新登录")
            print("🔄 当前任务已停止，等待重新登录后继续")
            return True
            
        else:
            print("⚠️ 浏览器驱动未初始化，无法清空Cookie")
            return False
            
    except Exception as e:
        print(f"❌ 清空Cookie时发生异常: {e}")
        return False

# 详细日志总开关（避免频繁打印造成控制台阻塞）
try:
    from xy_client.LuckyClientOP import ENABLE_DETAILED_LOGS  # 从主窗口配置读取
except Exception:
    ENABLE_DETAILED_LOGS = False

# 配置日志系统，将详细日志输出到文件而不是控制台
def setup_logging():
    try:
        # 创建logs目录
        log_dir = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), 'logs')
        os.makedirs(log_dir, exist_ok=True)
        
        # 配置文件日志
        file_handler = logging.FileHandler(os.path.join(log_dir, 'lucky_detailed.log'), encoding='utf-8')
        file_handler.setLevel(logging.DEBUG)
        
        # 配置控制台日志（只显示重要信息）
        console_handler = logging.StreamHandler()
        console_handler.setLevel(logging.WARNING)  # 只显示警告和错误
        
        # 设置格式
        formatter = logging.Formatter('%(asctime)s - %(levelname)s - %(message)s')
        file_handler.setFormatter(formatter)
        console_handler.setFormatter(formatter)
        
        # 配置根日志器
        logger = logging.getLogger()
        logger.setLevel(logging.DEBUG)
        
        # 清除现有的处理器
        for handler in logger.handlers[:]:
            logger.removeHandler(handler)
        
        logger.addHandler(file_handler)
        logger.addHandler(console_handler)
        
        return logger
    except Exception as e:
        print(f"日志配置失败: {e}")
        return None

# 初始化日志
logger = setup_logging()

def handle_popup_dialogs(driver, silent=False):
    """
    处理登录成功后的弹框对话框
    返回: True=处理成功, False=处理失败
    """
    try:
        if not driver:
            if not silent:
                print("⚠️ 浏览器驱动未初始化，跳过弹框处理")
            return True
            
        if not silent:
            print("🔍 开始检测和处理弹框...")
        
        # 等待页面稳定
        time.sleep(1)
        
        # 检查是否有弹框遮罩层
        try:
            overlays = driver.find_elements(By.XPATH, "//div[contains(@class,'modal') or contains(@class,'popup') or contains(@class,'overlay') or contains(@class,'dialog') or contains(@class,'alert')]")
            if overlays:
                if not silent:
                    print(f"⚠️ 检测到 {len(overlays)} 个弹框遮罩层")
                
                # 尝试按ESC键关闭弹框
                try:
                    from selenium.webdriver.common.keys import Keys
                    body = driver.find_element(By.TAG_NAME, 'body')
                    body.send_keys(Keys.ESCAPE)
                    if not silent:
                        print("✅ 尝试按ESC键关闭弹框")
                    time.sleep(1)
                except Exception as e:
                    if not silent:
                        print(f"⚠️ 按ESC键失败: {e}")
        except Exception as e:
            if not silent:
                print(f"⚠️ 检查弹框遮罩层失败: {e}")
        
        # 查找并点击常见的弹框按钮
        popup_buttons = [
            "//button[contains(text(),'确认')]",
            "//button[contains(text(),'确定')]", 
            "//button[contains(text(),'进入')]",
            "//button[contains(text(),'开始')]",
            "//button[contains(text(),'OK')]",
            "//button[contains(text(),'Confirm')]",
            "//button[contains(text(),'Enter')]",
            "//button[contains(text(),'Start')]",
            "//button[contains(text(),'继续')]",
            "//button[contains(text(),'Continue')]",
            "//input[@type='button' and contains(@value,'确认')]",
            "//input[@type='button' and contains(@value,'确定')]",
            "//input[@type='submit' and contains(@value,'确认')]",
            "//input[@type='submit' and contains(@value,'确定')]"
        ]
        
        for button_xpath in popup_buttons:
            try:
                buttons = driver.find_elements(By.XPATH, button_xpath)
                for btn in buttons:
                    try:
                        if btn.is_displayed() and btn.is_enabled():
                            btn_text = btn.text or btn.get_attribute('value') or '未知'
                            if not silent:
                                print(f"✅ 找到弹框按钮: '{btn_text}'，准备点击")
                            btn.click()
                            if not silent:
                                print(f"✅ 弹框按钮 '{btn_text}' 点击成功")
                            time.sleep(1)
                            return True
                    except Exception as e:
                        if not silent:
                            print(f"⚠️ 点击按钮失败: {e}")
                        continue
            except Exception as e:
                if not silent:
                    print(f"⚠️ 查找按钮失败: {e}")
                continue
        
        # 优先查找右上角的关闭按钮
        try:
            # 查找右上角的关闭按钮（用户提到的具体选择器）
            close_buttons = driver.find_elements(By.CSS_SELECTOR, ".btn-close.fn-close")
            if close_buttons:
                for btn in close_buttons:
                    try:
                        if btn.is_displayed() and btn.is_enabled():
                            if not silent:
                                print(f"✅ 找到右上角关闭按钮，准备点击")
                            btn.click()
                            if not silent:
                                print(f"✅ 右上角关闭按钮点击成功")
                            time.sleep(1)
                            return True
                    except Exception as e:
                        if not silent:
                            print(f"⚠️ 点击右上角关闭按钮失败: {e}")
                        continue
            
            # 备用方案：查找其他关闭按钮
            close_buttons = driver.find_elements(By.XPATH, "//button[contains(@class,'close') or contains(@class,'cancel') or contains(text(),'关闭') or contains(text(),'取消') or contains(text(),'×') or contains(text(),'✕')]")
            if close_buttons:
                for btn in close_buttons:
                    try:
                        if btn.is_displayed() and btn.is_enabled():
                            if not silent:
                                print(f"✅ 找到关闭按钮，准备点击")
                            btn.click()
                            if not silent:
                                print("✅ 找到并点击关闭按钮")
                            time.sleep(1)
                            return True
                    except Exception as e:
                        if not silent:
                            print(f"⚠️ 点击关闭按钮失败: {e}")
                        continue
        except Exception as e:
            if not silent:
                print(f"⚠️ 查找关闭按钮失败: {e}")
        
        if not silent:
            print("✅ 弹框检测完成，未发现需要处理的弹框")
        return True
        
    except Exception as e:
        if not silent:
            print(f"❌ 处理弹框时发生异常: {e}")
            import traceback
            traceback.print_exc()
        return False

def check_login_status(driver):
    """
    检查当前登录状态
    返回: True=已登录, False=未登录
    """
    try:
        if not driver:
            print("❌ 浏览器驱动未初始化")
            return False
        
        current_url = driver.current_url
        #print(f"🔍 检查登录状态，当前URL: {current_url}")
        
        # 检查是否在登录页面
        if any(keyword in current_url.lower() for keyword in ['login', '登录', 'agreement', '协议']):
            print("⚠️ 当前在登录/协议页面，未登录")
            return False
        
        # 优先检查登录后的特征元素（更可靠）
        try:
            # 查找余额显示元素
            balance_element = driver.find_element(By.ID, "CreditBalance")
            if balance_element:
                balance_text = balance_element.get_attribute('textContent') or balance_element.text
                if balance_text and balance_text.strip():
                    current_time = time.strftime('%H:%M:%S', time.localtime())
                    print(f"[{current_time}] ✅ 余额: {balance_text.strip()}，登录状态正常")
                    return True
        except Exception as e:
            print(f"🔍 查找余额元素失败: {e}")
        
        try:
            # 查找用户信息元素
            user_element = driver.find_element(By.XPATH, "//*[contains(text(),'欢迎') or contains(text(),'Welcome') or contains(text(),'用户') or contains(text(),'User')]")
            if user_element:
                user_text = user_element.get_attribute('textContent') or user_element.text
                if user_text and user_text.strip():
                    print(f"✅ 找到用户信息元素: {user_text.strip()}，已登录")
                    return True
        except Exception as e:
            print(f"🔍 查找用户信息元素失败: {e}")
        
        try:
            # 查找其他可能的登录后元素
            logout_element = driver.find_element(By.XPATH, "//*[contains(text(),'退出') or contains(text(),'Logout') or contains(text(),'登出')]")
            if logout_element:
                print("✅ 找到退出按钮，已登录")
                return True
        except Exception as e:
            print(f"🔍 查找退出按钮失败: {e}")
        
        try:
            # 查找导航菜单或侧边栏
            nav_element = driver.find_element(By.XPATH, "//nav | //*[@class*='nav'] | //*[@class*='menu'] | //*[@class*='sidebar']")
            if nav_element:
                print("✅ 找到导航菜单，可能已登录")
                # 进一步检查是否包含游戏相关链接
                try:
                    game_links = nav_element.find_elements(By.XPATH, ".//*[contains(text(),'游戏') or contains(text(),'Game') or contains(text(),'下注') or contains(text(),'Bet')]")
                    if game_links:
                        print("✅ 找到游戏相关链接，已登录")
                        return True
                except:
                    pass
        except Exception as e:
            print(f"🔍 查找导航菜单失败: {e}")
        
        # 检查是否在主页或游戏页面（URL检查作为辅助）
        if any(keyword in current_url.lower() for keyword in ['member', 'game', 'bet', '下注', '游戏', 'home', 'index', 'main']):
            print(f"✅ 当前URL包含游戏相关关键词: {current_url}，已登录")
            return True
        
        # 如果URL不包含登录关键词，且能找到一些页面内容，可能已登录
        if not any(keyword in current_url.lower() for keyword in ['login', '登录', 'agreement', '协议']):
            try:
                # 检查页面是否有实际内容（不是登录页面）
                body_content = driver.find_element(By.TAG_NAME, "body")
                if body_content:
                    body_text = body_content.get_attribute('textContent') or body_content.text
                    if body_text and len(body_text.strip()) > 100:  # 页面有足够内容
                        print("✅ 页面有足够内容且不在登录页面，可能已登录")
                        return True
            except:
                pass
        
        print("⚠️ 无法确定登录状态，假设未登录")
        return False
        
    except Exception as e:
        print(f"❌ 检查登录状态时发生异常: {e}")
        return False

def ensure_login_status(driver, max_retries=3):
    """
    确保登录状态，如果未登录则尝试重新登录
    返回: True=登录成功, False=登录失败
    """
    for attempt in range(max_retries):
        if check_login_status(driver):
            print("✅ 登录状态正常")
            return True
        
        print(f"⚠️ 第 {attempt + 1} 次检查：未登录状态")
        if attempt < max_retries - 1:
            print("🔄 等待5秒后重新检查...")
            time.sleep(5)
        else:
            print("❌ 达到最大重试次数，登录状态异常")
            return False
    
    return False

class CustomHTTPAdapter(HTTPAdapter):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # 优化连接池配置，增加连接池数量和大小
        self.poolmanager = PoolManager(
            num_pools=50,   # 连接池数量（从100减少到50，避免过多连接池）
            maxsize=200,    # 每个连接池的最大连接数（从100增加到200）
            retries=Retry(
                total=3,
                backoff_factor=0.5,
                status_forcelist=[500, 502, 503, 504]
            )
        )

# Update the globalSession configuration
def configure_session():
    """配置全局Session连接池，确保连接池足够大"""
    # 使用更大的连接池，避免"Connection pool is full"错误
    globalSession.mount('http://', CustomHTTPAdapter(
        pool_connections=50,   # 连接池数量（每个host）
        pool_maxsize=200,      # 每个连接池的最大连接数（从100增加到200）
        max_retries=3,
        pool_block=False       # 不阻塞，连接池满时创建新连接而不是等待
    ))
    globalSession.mount('https://', CustomHTTPAdapter(
        pool_connections=50,   # 连接池数量（每个host）
        pool_maxsize=200,      # 每个连接池的最大连接数（从100增加到200）
        max_retries=3,
        pool_block=False       # 不阻塞，连接池满时创建新连接而不是等待
    ))

configure_session()


driver_type = 2

# 创建一个 Session 对象
# session = requests.Session()

config = Configs()
robot_domain = config.get_config('robot_domain')
lottery_type = int(config.get_config('lottery_type'))
access_token = config.get_config('access_token')
driver_name = config.get_config('driver_name')
print('driver_name', driver_name)


def getActiveQiHao(ssc_domain='https://f1.wf7865rf.xyz', cookies='', headerData={}):
    now_time = str(int(float(time.time()) * 1000))
    # print('====================headerData_start=========================')
    if not bool(headerData):  # 第一次调用robot7切换处理
        headerData = SystemsUsers.getHeaderData()
    # print('====================headerData_end===========================\r\n')
    url = ssc_domain + '/drawno/GetCurrentPeriodStatus?_=' + now_time
    v1 = headerData['v1']  # 浏览器版本号
    v2 = headerData['v2']  # 浏览器版本号
    headers = {
        'Accept': 'application/json, text/javascript, */*; q=0.01',
        'Accept-Encoding': 'gzip, deflate, br',
        'Accept-Language': 'zh-CN,zh;q=0.9',
        'Connection': 'keep-alive',
        'Cookie': cookies,
        'Referer': headerData['Referer'] + now_time,
        'Host': (ssc_domain.replace('https', 'http')).replace("http://", ''),
        'sec-ch-ua': '" Not;A Brand";v="' + v1 + '", "Google Chrome";v="' + v2 + '", "Chromium";v="' + v2 + '"',
        'sec-ch-ua-mobile': '?0',
        'sec-ch-ua-platform': '"Windows"',
        'Sec-Fetch-Dest': 'empty',
        'Sec-Fetch-Mode': 'cors',
        'Sec-Fetch-Site': 'same-origin',
        'User-Agent': headerData['user_agent'],
        # 'X-Requested-With': 'XMLHttpRequest',
    }
    print('getActiveQihao url:', url)
    print('getActiveQihao headers:', headers)

    requests.adapters.DEFAULT_RETRIES = 5  # 增加重连次数
    # s = requests.session()
    # session.headers = headers
    rst = globalSession.request('GET', url, headers=headers)
    # rst.encoding = rst.apparent_encoding  # 解决乱码问题

    # print('======================text_start============================')
    rstData = rst.text
    print('text:', rstData)
    # if isinstance(rstData, str) and ('robot7=' in rstData):
    #    print('old_header', headerData)
    #    searchObj1 = re.search(r'robot7=(.*?);', rstData, re.M | re.I) # 新robot7
    #    searchObj2 = re.search(r'robot7=(.*?);', headerData['cookies'], re.M | re.I) # 旧
    #    old_cookies = headerData['cookies']
    #    headerData['cookies'] = old_cookies.replace(searchObj2.group(1), searchObj1.group(1))  # 替换robot7
    #    print('new headerData:', headerData)
    #    return 'xxx'
    #    return getActiveQihao(access_token, ssc_domain, headerData)
    # print('======================text_end============================\r\n')

    print('=======================activeQihao rstData start===========================')
    print('url', url)
    print('headers', headers)
    print('=======================activeQihao rstData end=========================\r\n')
    #    return rstData['Data']['real_period_no']
    if 'html' in rst.text:
        return rst.text

    return json.loads(rstData)


def isElementExist(browser, ele, type='id'):
    flag = True
    try:
        if type == 'css':
            browser.find_element(By.CSS_SELECTOR, ele)
        else:
            browser.find_element(By.ID, ele)
        return flag
    except:
        flag = False
        return flag


def loginAWithCdp(account='', pwd='', mainWindow=None):
    """Log in through Chrome DevTools when no matching ChromeDriver is available."""
    if mainWindow is None:
        return {'status': 500, 'msg': 'Main window is unavailable', 'balance': '0.00'}

    try:
        from xy_client.services.tools.chrome_cdp import (
            login_platform_via_cdp,
            platform_base_url,
        )

        domain = ''
        if hasattr(mainWindow, 'user_info') and isinstance(mainWindow.user_info, dict):
            domain = mainWindow.user_info.get('ssc_domain', '')
        if not domain and hasattr(mainWindow, 'domain_val'):
            domain = mainWindow.domain_val.text().strip()
        if not domain:
            domain = getattr(mainWindow, 'wp_domain', '')

        print(f"[CDP] Starting platform login on Chrome debug port {mainWindow.port}")
        result = login_platform_via_cdp(
            mainWindow.port,
            domain,
            account,
            pwd,
            timeout=30,
        )
        if result.get('status') != 200:
            print(f"[CDP] Platform login failed: {result.get('msg', 'unknown error')}")
            return result

        cookies = result.get('cookies', [])
        user_agent = result.get('user_agent', '')
        current_url = result.get('current_url', '') or domain
        try:
            ssc_domain = platform_base_url(current_url)
        except Exception:
            ssc_domain = platform_base_url(domain)

        if hasattr(mainWindow, 'domain_val'):
            mainWindow.domain_val.setText(ssc_domain)
        mainWindow.wp_domain = ssc_domain
        mainWindow.browser_cookies = ''.join(
            f"{cookie.get('name', '')}={cookie.get('value', '')};"
            for cookie in cookies
            if cookie.get('name')
        )

        update_url = robot_domain + '/api/index/update-user-cookies'
        post_data = {
            'url': update_url,
            'account': account,
            'password': pwd,
            'ssc_domain': ssc_domain,
            'cookies': cookies,
            'user_agent': user_agent,
            'access_token': getattr(mainWindow, 'access_token', '') or access_token,
        }
        response = globalSession.post(
            update_url,
            data=json.dumps(post_data),
            headers={'content-type': 'application/json'},
            timeout=12,
        )
        backend_result = response.json()
        if backend_result.get('status') != 200:
            print(f"[CDP] Failed to save platform cookies: {backend_result}")
            return {
                'status': 500,
                'msg': backend_result.get('msg', 'Failed to save platform cookies'),
                'balance': result.get('balance', '0.00'),
            }

        backend_result['balance'] = result.get('balance', '0.00') or '0.00'
        print(f"[CDP] Platform login succeeded: {ssc_domain}")
        return backend_result
    except Exception as exc:
        print(f"[CDP] Platform login exception: {exc}")
        import traceback
        traceback.print_exc()
        return {'status': 500, 'msg': str(exc), 'balance': '0.00'}


def loginA(account='', pwd='', mainWindow=None):
    # 初始化标志变量
    already_handled_agreement = False
    pre_fetched_balance = None
    
    now_time = str(int(float(time.time()) * 1000))
    ssc_domain = mainWindow.domain_val.text()

    # 根据用户界面的浏览器选择来启动浏览器，而不是从配置文件读取
    # 获取用户选择的浏览器类型
    if hasattr(mainWindow, 'getPreferredBrowser'):
        selected_browser = mainWindow.getPreferredBrowser()
        print(f"🔍 用户选择的浏览器: {selected_browser}")
    else:
        # 如果没有浏览器选择功能，默认使用 Chrome
        selected_browser = "chrome"
        print(f"⚠️ 未找到浏览器选择功能，默认使用: {selected_browser}")
    
    # 关键优化：检查现有浏览器实例是否可用，如果连接不上则重新创建
    driver = None
    if hasattr(mainWindow, 'driver') and mainWindow.driver is not None:
        print("🔍 检测到现有浏览器实例，验证连接是否可用...")
        try:
            # 尝试获取当前URL来验证连接是否可用（设置短超时，避免长时间卡住）
            import threading
            url_result = [None]
            url_error = [None]
            
            def check_url():
                try:
                    url_result[0] = mainWindow.driver.current_url
                except Exception as e:
                    url_error[0] = e
            
            check_thread = threading.Thread(target=check_url, daemon=True)
            check_thread.start()
            check_thread.join(timeout=3)  # 最多等待3秒
            
            if url_error[0] is None and url_result[0] is not None:
                print("✅ 现有浏览器实例连接正常，使用现有实例")
                driver = mainWindow.driver
            else:
                print(f"⚠️ 现有浏览器实例连接异常: {url_error[0]}，将重新创建")
                # 尝试清理旧连接（但不强制，避免卡住）
                try:
                    mainWindow.driver.quit()
                except:
                    pass
                finally:
                    mainWindow.driver = None
        except Exception as check_e:
            print(f"⚠️ 检查现有浏览器实例异常: {check_e}，将重新创建")
            # 尝试清理旧连接（但不强制，避免卡住）
            try:
                if hasattr(mainWindow, 'driver') and mainWindow.driver:
                    mainWindow.driver.quit()
            except:
                pass
            finally:
                mainWindow.driver = None
    
    # 如果没有可用实例，创建新的浏览器实例
    if driver is None:
        # 关键修复：在创建新浏览器实例之前，检查WebDriver连接失败标志
        if hasattr(mainWindow, '_webdriver_connection_failed') and mainWindow._webdriver_connection_failed:
            failed_time = getattr(mainWindow, '_webdriver_connection_failed_time', 0)
            # 如果连接失败时间超过5分钟，允许再次尝试（可能浏览器已恢复）
            if time.time() - failed_time < 300:  # 5分钟内暂停
                print(f"⏸️ [loginA] WebDriver连接失败，暂停创建新浏览器实例（避免创建多个窗口），等待5分钟后自动恢复")
                return {'status': 500, 'msg': 'WebDriver连接失败，暂停创建新浏览器实例', 'balance': '0.00'}
            else:
                # 超过5分钟，清除失败标志，允许再次尝试
                mainWindow._webdriver_connection_failed = False
                print("🔄 [loginA] WebDriver连接失败已超过5分钟，清除失败标志，允许再次尝试")
        
        print("🆕 创建新的浏览器实例")
        try:
            driver = tools.getDriver(selected_browser, mainWindow.port)
        except Exception as get_driver_e:
            error_str = str(get_driver_e).lower()
            # 检测连接失败错误（10061、连接被拒绝等）
            if '10061' in error_str or 'refused' in error_str or '积极拒绝' in error_str or '无法连接' in error_str:
                print(f"❌ [loginA] WebDriver连接失败（{get_driver_e}），暂停重试，避免创建多个窗口")
                # 设置连接失败标志
                if hasattr(mainWindow, '_webdriver_connection_failed'):
                    mainWindow._webdriver_connection_failed = True
                    mainWindow._webdriver_connection_failed_time = time.time()
                return {'status': 500, 'msg': f'WebDriver连接失败: {get_driver_e}', 'balance': '0.00'}
            else:
                print(f"❌ [loginA] WebDriver创建异常: {get_driver_e}")
                import traceback
                traceback.print_exc()
                return {'status': 500, 'msg': f'WebDriver创建异常: {get_driver_e}', 'balance': '0.00'}
        
        # 重要：将新创建的WebDriver实例赋值给主窗口
        if driver:
            mainWindow.driver = driver
            print("✅ WebDriver实例已赋值给主窗口")
            # 关键修复：连接成功后，清除连接失败标志
            if hasattr(mainWindow, '_webdriver_connection_failed'):
                mainWindow._webdriver_connection_failed = False
                print("✅ [loginA] 已清除WebDriver连接失败标志")
        else:
            print("❌ WebDriver创建失败（返回None）")
            # 关键修复：如果getDriver返回None，设置连接失败标志
            if hasattr(mainWindow, '_webdriver_connection_failed'):
                mainWindow._webdriver_connection_failed = True
                mainWindow._webdriver_connection_failed_time = time.time()
            return {'status': 500, 'msg': 'WebDriver创建失败（返回None）', 'balance': '0.00'}
    driver.set_window_size(1300, 840)  # 设置浏览器大小分辨率 1300*840
    # 请求登录页（使用安全的页面打开方法，支持超时控制和自动恢复）
    # 如果失败，回退到简单的 driver.get() 方法
    login_url = ssc_domain + '/Member/Login?_=' + now_time
    success = False
    try:
        from xy_client.services.tools.PageRefreshManager import get_refresh_manager
        refresh_manager = get_refresh_manager(page_load_timeout=20, max_retry=2)
        success = refresh_manager.safe_get(driver, login_url, 
                                          reason="打开登录页", 
                                          check_loading=False, timeout=20)  # 打开登录页，不检查加载状态
        if success:
            print(f"✅ 已打开登录页: {login_url}")
        else:
            print(f"⚠️ 安全打开方式失败，回退到简单方式")
    except ImportError:
        print(f"⚠️ 页面刷新管理器不可用，使用简单方式")
    except Exception as e:
        print(f"⚠️ 安全打开方式异常: {e}，回退到简单方式")
    
    # 如果安全方式失败，使用简单的 driver.get() 方法
    if not success:
        try:
            driver.get(login_url)
            print(f"✅ 已使用简单方式打开登录页: {login_url}")
        except Exception as e:
            print(f"❌ 打开登录页失败: {e}")
            import traceback
            traceback.print_exc()

    STR_READY_STATE = ''
    # 而直接操作页面就需要类似于下面的代码等待页面加载完成
    while STR_READY_STATE != 'complete':
        STR_READY_STATE = driver.execute_script('return document.readyState')
        if ENABLE_DETAILED_LOGS:
            print(STR_READY_STATE)
            print(f'STR_READY_STATE : {STR_READY_STATE}')
    # ssc_domain = 'http://f1.wg7s5297.xyz'
    # time.sleep(5)

    print("-----------------------------finish user login - 1-----------------------------")

    # 添加超时保护和异常处理
    try:
        # 等待账号输入框出现
        from selenium.webdriver.support.ui import WebDriverWait
        from selenium.webdriver.support import expected_conditions as EC
        
        print("🔍 等待账号输入框出现...")
        account_input = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, "Account"))
        )
        print("✅ 账号输入框已找到")
        
        # 清空并输入账号
        account_input.clear()
        account_input.send_keys(account)
        print(f"✅ 账号输入完成: {account}")
        time.sleep(1)
        
        # 等待密码输入框出现
        print("🔍 等待密码输入框出现...")
        password_input = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, "Password"))
        )
        print("✅ 密码输入框已找到")
        
        # 清空并输入密码
        password_input.clear()
        password_input.send_keys(pwd)
        print("✅ 密码输入完成")
        
        # 等待登录按钮出现并点击
        print("🔍 等待登录按钮出现...")
        login_button = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.ID, 'btn-submit'))
        )
        print("✅ 登录按钮已找到")
        
        login_button.click()
        print("✅ 登录按钮点击完成")
        time.sleep(2)
        
        # 处理可能的弹框
        try:
            # 检查是否有弹框出现
            alert = driver.switch_to.alert
            alert_text = alert.text
            print(f"⚠️ 检测到弹框: {alert_text}")
            
            # 如果是用户名验证弹框，点击确定
            if "用户名" in alert_text or "账号" in alert_text or "密码" in alert_text:
                print("✅ 处理用户名验证弹框")
                alert.accept()
                time.sleep(1)
            else:
                print(f"⚠️ 未知弹框内容: {alert_text}")
                alert.accept()
                time.sleep(1)
                
        except Exception as alert_e:
            # 没有弹框，继续执行
            print("✅ 无弹框，继续执行")
        
    except Exception as e:
        print(f"❌ 登录输入过程异常: {e}")
        return {'status': 500, 'msg': f'登录输入失败: {e}', 'balance': '0.00'}

    # 检查是否真的需要处理责任声明页面
    try:
        current_url = driver.current_url
        print(f"🔍 当前页面URL: {current_url}")
        
        # 如果还在登录页面，说明登录失败
        if "login" in current_url.lower() or "登录" in current_url.lower():
            print("⚠️ 仍在登录页面，登录可能失败")
            return {'status': 500, 'msg': '登录失败，仍在登录页面', 'balance': '0.00'}
        
        # 如果已经进入主页面，跳过责任声明处理
        if "app" in current_url.lower() or "index" in current_url.lower():
            print("✅ 已进入主页面，跳过责任声明处理")
            return {'status': 200, 'msg': '登录成功', 'balance': '0.00'}
        
        # 只有在协议页面才处理责任声明
        if "agreement" in current_url.lower() or "协议" in current_url.lower():
            print("🔍 开始处理责任声明页面...")
            # 使用新的AgreementHandler处理责任声明页面
            try:
                from xy_client.services.tools.AgreementHandler import handle_agreement_page
                
                # 关键优化：在WebDriver连接断开时自动恢复连接
                max_retries = 3
                success = False
                for retry in range(max_retries):
                    try:
                        # 检查WebDriver连接是否可用
                        try:
                            test_url = driver.current_url
                        except Exception as conn_e:
                            error_str = str(conn_e).lower()
                            if 'invalid session' in error_str or 'session id' in error_str:
                                print(f"⚠️ WebDriver连接断开，尝试恢复连接（重试 {retry+1}/{max_retries}）...")
                                # 尝试恢复连接
                                if hasattr(mainWindow, 'browser_manager'):
                                    new_driver = mainWindow.browser_manager.check_and_recover_browser_connection(silent=False)
                                    if new_driver:
                                        driver = new_driver
                                        mainWindow.driver = driver
                                        print("✅ WebDriver连接已恢复，继续处理责任声明页面")
                                    else:
                                        print("❌ WebDriver连接恢复失败")
                                        if retry < max_retries - 1:
                                            time.sleep(2)
                                            continue
                                        else:
                                            break
                                else:
                                    if retry < max_retries - 1:
                                        time.sleep(2)
                                        continue
                                    else:
                                        break
                            else:
                                raise conn_e
                        
                        # 处理责任声明页面（传递mainWindow用于连接恢复）
                        success = handle_agreement_page(driver, timeout=10, main_window=mainWindow)  # 增加超时时间到10秒
                        if success:
                            print("✅ 责任声明页面处理成功")
                            break
                        else:
                            if retry < max_retries - 1:
                                print(f"⚠️ 责任声明页面处理失败，重试中... ({retry+1}/{max_retries})")
                                time.sleep(2)
                                continue
                    except Exception as retry_e:
                        error_str = str(retry_e).lower()
                        if 'invalid session' in error_str or 'session id' in error_str:
                            print(f"⚠️ 处理过程中WebDriver连接断开: {retry_e}")
                            if retry < max_retries - 1:
                                # 尝试恢复连接
                                if hasattr(mainWindow, 'browser_manager'):
                                    new_driver = mainWindow.browser_manager.check_and_recover_browser_connection(silent=False)
                                    if new_driver:
                                        driver = new_driver
                                        mainWindow.driver = driver
                                        print("✅ WebDriver连接已恢复，继续重试")
                                        time.sleep(2)
                                        continue
                            else:
                                print(f"❌ 责任声明页面处理失败（已重试{max_retries}次）: {retry_e}")
                        else:
                            print(f"❌ 责任声明页面处理异常: {retry_e}")
                            if retry < max_retries - 1:
                                time.sleep(2)
                                continue
                            else:
                                break
                
                if success:
                    # 责任声明处理成功后，更新登录状态
                    time.sleep(3)  # 等待页面跳转
                    try:
                        current_url_after = driver.current_url
                        print(f"🔍 责任声明处理后URL: {current_url_after}")
                    except Exception as url_e:
                        error_str = str(url_e).lower()
                        if 'invalid session' in error_str or 'session id' in error_str:
                            print(f"⚠️ 获取URL时WebDriver连接断开: {url_e}，尝试恢复连接...")
                            if hasattr(mainWindow, 'browser_manager'):
                                new_driver = mainWindow.browser_manager.check_and_recover_browser_connection(silent=False)
                                if new_driver:
                                    driver = new_driver
                                    mainWindow.driver = driver
                                    current_url_after = driver.current_url
                                    print(f"✅ WebDriver连接已恢复，责任声明处理后URL: {current_url_after}")
                                else:
                                    print("❌ WebDriver连接恢复失败，无法获取URL")
                                    current_url_after = ""
                            else:
                                current_url_after = ""
                        else:
                            print(f"⚠️ 获取URL异常: {url_e}")
                            current_url_after = ""
                    
                    # 如果已经离开协议页面，说明登录成功
                    # 关键优化：即使current_url_after为空（连接断开），也检查是否还在协议页面
                    if current_url_after and "agreement" not in current_url_after.lower() and "协议" not in current_url_after.lower():
                        print("✅ 已离开协议页面，登录成功")
                    elif not current_url_after:
                        # URL获取失败，但责任声明处理成功，假设已离开协议页面
                        print("⚠️ 无法获取URL（连接断开），但责任声明处理成功，假设已离开协议页面")
                        # 继续执行后续流程
                    else:
                        # 仍在协议页面，但责任声明处理成功，可能是页面跳转延迟
                        print("⚠️ 仍在协议页面，但责任声明处理成功，等待页面跳转...")
                        time.sleep(3)  # 额外等待3秒
                        try:
                            if hasattr(mainWindow, 'browser_manager'):
                                # 再次尝试恢复连接并检查URL
                                final_driver = mainWindow.browser_manager.check_and_recover_browser_connection(silent=False)
                                if final_driver:
                                    driver = final_driver
                                    mainWindow.driver = driver
                                    final_url = driver.current_url.lower()
                                    if "agreement" not in final_url and "协议" not in final_url:
                                        print("✅ 已离开协议页面，登录成功")
                                        current_url_after = final_url
                                    else:
                                        print("⚠️ 仍在协议页面，但继续执行后续流程（可能页面跳转延迟）")
                                        # 即使仍在协议页面，也继续执行，因为责任声明处理已成功
                                        current_url_after = final_url
                                else:
                                    print("⚠️ 无法恢复连接，但继续执行后续流程")
                                    # 即使无法恢复连接，也继续执行，因为责任声明处理已成功
                            else:
                                print("⚠️ 无browser_manager，但继续执行后续流程")
                        except Exception as final_check_e:
                            print(f"⚠️ 最终检查异常: {final_check_e}，但继续执行后续流程")
                    
                    # 如果已离开协议页面或责任声明处理成功，继续执行后续流程
                    # 关键优化：即使current_url_after为空或仍在协议页面，只要责任声明处理成功，就继续执行
                    should_continue = False
                    if not current_url_after:
                        # URL获取失败，但责任声明处理成功，继续执行
                        should_continue = True
                    elif "agreement" not in current_url_after.lower() and "协议" not in current_url_after.lower():
                        # 已离开协议页面，继续执行
                        should_continue = True
                    elif success:
                        # 仍在协议页面，但责任声明处理成功，继续执行（避免一直停留在协议页面）
                        print("⚠️ 仍在协议页面，但责任声明处理成功，继续执行后续流程（避免一直停留在协议页面）")
                        should_continue = True
                    
                    if should_continue:
                        print("✅ 继续执行后续流程（已离开协议页面或责任声明处理成功）")
                        
                        # 检查是否有系统通知页面（egis-notice.cshtml），如果有就点击"确认已阅读"按钮
                        # 关键优化：如果current_url_after为空，尝试恢复连接后获取URL
                        if not current_url_after:
                            try:
                                if hasattr(mainWindow, 'browser_manager'):
                                    final_driver = mainWindow.browser_manager.check_and_recover_browser_connection(silent=False)
                                    if final_driver:
                                        driver = final_driver
                                        mainWindow.driver = driver
                                        current_url_after = driver.current_url.lower()
                                        print(f"✅ WebDriver连接已恢复，当前URL: {current_url_after}")
                            except:
                                pass
                        
                        if current_url_after and "egis-notice" in current_url_after.lower():
                            print("🔍 检测到系统通知页面，准备点击'确认已阅读'按钮...")
                            try:
                                # 等待页面加载
                                time.sleep(2)
                                
                                # 查找"确认已阅读"按钮（只使用 id="btn_enter"）
                                confirm_button = None
                                try:
                                    confirm_button = WebDriverWait(driver, 5).until(
                                        EC.element_to_be_clickable((By.ID, "btn_enter"))
                                    )
                                    print("✅ 找到'确认已阅读'按钮（id=btn_enter）")
                                except Exception as find_e:
                                    print(f"⚠️ 未找到'确认已阅读'按钮（id=btn_enter）: {find_e}，可能页面已更新或已自动跳转")
                                
                                # 点击按钮
                                if confirm_button:
                                    try:
                                        # 优先使用JavaScript点击（更可靠）
                                        driver.execute_script("arguments[0].click();", confirm_button)
                                        print("✅ 已点击'确认已阅读'按钮（JavaScript方式）")
                                        time.sleep(2)  # 等待页面跳转
                                        
                                        # 检查是否已离开通知页面
                                        final_url = driver.current_url
                                        if "egis-notice" not in final_url.lower():
                                            print("✅ 已离开系统通知页面")
                                        else:
                                            print("⚠️ 仍在系统通知页面，可能需要手动处理")
                                    except Exception as click_e:
                                        print(f"⚠️ 点击'确认已阅读'按钮失败: {click_e}")
                                        # 尝试普通点击
                                        try:
                                            confirm_button.click()
                                            print("✅ 已点击'确认已阅读'按钮（普通方式）")
                                            time.sleep(2)
                                        except:
                                            print("❌ 所有点击方式都失败，但继续执行")
                            except Exception as notice_e:
                                print(f"⚠️ 处理系统通知页面异常: {notice_e}，但继续执行")
                        
                        # 登录成功后，等待页面完全加载，然后获取余额
                        print("🔍 等待主页面加载...")
                        time.sleep(3)  # 等待主页面完全加载
                        
                        # 处理可能出现的弹框
                        try:
                            close_buttons = driver.find_elements(By.CSS_SELECTOR, ".btn-close.fn-close")
                            if close_buttons:
                                for btn in close_buttons:
                                    try:
                                        if btn.is_displayed():
                                            btn.click()
                                            print("✅ 已关闭主页面弹框")
                                            time.sleep(1)
                                            break
                                    except:
                                        continue
                        except:
                            pass
                        
                        # 现在尝试获取余额
                        try:
                            print("🔍 开始获取余额信息...")
                            WebDriverWait(driver, 10).until(
                                EC.presence_of_element_located((By.XPATH, '//*[@id="CreditBalance"]'))
                            )
                            balance = driver.find_element(By.ID, "CreditBalance").get_attribute('textContent')
                            print(f"✅ 成功获取余额: {balance}")
                        except Exception as e:
                            print(f"❌ 无法获取余额信息: {e}")
                            balance = '0.00'  # 设置默认余额
                            print(f"⚠️ 使用默认余额: {balance}")
                        
                        # 重要：保存已获取的余额，继续执行后续的cookies更新和API请求
                        print("✅ 责任声明处理成功，余额已获取，继续更新cookies和API...")
                        already_handled_agreement = True
                        pre_fetched_balance = balance  # 保存已获取的余额
                        
                        # 关键修复：确保导航到盘口页面（App/Index）
                        try:
                            current_url_check = driver.current_url.lower()
                            # 如果不在盘口页面，导航到盘口页面
                            if "app/index" not in current_url_check:
                                print("🔍 当前不在盘口页面，准备导航到盘口页面...")
                                ssc_domain = mainWindow.domain_val.text() if hasattr(mainWindow, 'domain_val') else ''
                                if not ssc_domain:
                                    # 尝试从driver获取当前域名
                                    try:
                                        current_url_full = driver.current_url
                                        from urllib.parse import urlparse
                                        parsed = urlparse(current_url_full)
                                        ssc_domain = f"{parsed.scheme}://{parsed.netloc}"
                                    except:
                                        pass
                                
                                if ssc_domain:
                                    # 构建盘口地址
                                    if ssc_domain.endswith('/'):
                                        market_url = ssc_domain + 'App/Index'
                                    else:
                                        market_url = ssc_domain + '/App/Index'
                                    
                                    print(f"🌐 [loginA] 准备导航到盘口页面: {market_url}")
                                    try:
                                        # 使用安全的页面打开方法
                                        from xy_client.services.tools.PageRefreshManager import get_refresh_manager
                                        refresh_manager = get_refresh_manager(page_load_timeout=10, max_retry=2)
                                        nav_success = refresh_manager.safe_get(driver, market_url, 
                                                                              reason="登录后导航到盘口页面",
                                                                              check_loading=False, timeout=10)
                                        if nav_success:
                                            print(f"✅ [loginA] 已导航到盘口页面: {market_url}")
                                        else:
                                            print(f"⚠️ [loginA] 安全导航方式失败，使用简单方式")
                                            driver.get(market_url)
                                            time.sleep(2)
                                            print(f"✅ [loginA] 已使用简单方式导航到盘口页面")
                                    except ImportError:
                                        print(f"⚠️ [loginA] 页面刷新管理器不可用，使用简单方式")
                                        driver.get(market_url)
                                        time.sleep(2)
                                        print(f"✅ [loginA] 已使用简单方式导航到盘口页面")
                                    except Exception as nav_e:
                                        print(f"⚠️ [loginA] 导航到盘口页面异常: {nav_e}，但继续执行")
                                else:
                                    print("⚠️ [loginA] 无法获取域名，跳过导航到盘口页面")
                            else:
                                print("✅ [loginA] 已在盘口页面，无需导航")
                        except Exception as nav_check_e:
                            print(f"⚠️ [loginA] 检查并导航到盘口页面异常: {nav_check_e}，但继续执行")
                else:
                    print("⚠️ 责任声明页面处理失败，但继续执行")
            except ImportError as e:
                print(f"⚠️ 无法导入AgreementHandler，使用备用方案: {e}")
                # 备用方案：使用多种方式查找同意按钮
                try:
                    print("🔍 备用方案：查找同意按钮...")
                    
                    # 方式1：通过ID查找
                    try:
                        agreeBtn = WebDriverWait(driver, 5).until(
                            EC.element_to_be_clickable((By.ID, "agree"))
                        )
                        print("✅ 备用方案方式1：找到ID为agree的按钮")
                    except:
                        # 方式2：通过文本内容查找
                        try:
                            agreeBtn = WebDriverWait(driver, 5).until(
                                EC.element_to_be_clickable((By.XPATH, "//button[contains(text(),'同意')] | //input[@type='button' and contains(@value,'同意')] | //a[contains(text(),'同意')]"))
                            )
                            print("✅ 备用方案方式2：找到包含'同意'文本的按钮")
                        except:
                            # 方式3：查找所有按钮
                            buttons = driver.find_elements(By.TAG_NAME, "button")
                            buttons.extend(driver.find_elements(By.XPATH, "//input[@type='button']"))
                            buttons.extend(driver.find_elements(By.XPATH, "//input[@type='submit']"))
                            
                            agreeBtn = None
                            for btn in buttons:
                                try:
                                    text = btn.text or btn.get_attribute('value') or btn.get_attribute('title') or ''
                                    if '同意' in text:
                                        agreeBtn = btn
                                        print(f"✅ 备用方案方式3：找到包含'同意'的按钮: {text}")
                                        break
                                except:
                                    continue
                            
                            if not agreeBtn:
                                raise Exception("未找到同意按钮")
                    
                    # 点击按钮
                    agreeBtn.click()
                    print("✅ 备用方案：同意按钮点击成功")
                    time.sleep(1)  # 优化：减少等待时间
                    
                    # 检查是否已离开协议页面
                    current_url_after = driver.current_url
                    if "agreement" not in current_url_after.lower() and "协议" not in current_url_after.lower():
                        print("✅ 已离开协议页面，登录成功")
                        return {'status': 200, 'msg': '登录成功', 'balance': '0.00'}
                except Exception as e2:
                    print(f"❌ 备用方案也失败: {e2}")
        else:
            print("✅ 不在协议页面，跳过责任声明处理")
            
    except Exception as e:
        print(f"⚠️ 检查页面状态异常: {e}")
        
        # 增加等待时间，确保页面完全加载
        time.sleep(2)
        
        # 首先检查是否有登录障碍（如IP限制、账号错误等）
        print("🔍 检查登录页面状态...")
        try:
            # 检查是否有错误提示弹框 - 只在登录页面检查，不在协议页面检查
            current_url = driver.current_url
            if 'Login' in current_url or '登录' in current_url:
                # 只在登录页面检查错误提示
                error_alerts = driver.find_elements(By.XPATH, "//*[contains(text(),'IP') or contains(text(),'锁定') or contains(text(),'限制') or contains(text(),'错误') or contains(text(),'失败')]")
                if error_alerts:
                    for alert in error_alerts:
                        alert_text = alert.text.strip()
                        # 更精确的错误检测，避免误判
                        if any(keyword in alert_text for keyword in ['IP被限制', '账号被锁定', '登录失败', '密码错误', '账号不存在']):
                            print(f"❌ 检测到登录障碍: {alert_text}")
                            print("⚠️ 由于登录障碍，跳过寻找同意按钮")
                            # 记录错误日志
                            try:
                                SystemsUsers.pushErrorLog(f'登录遇到障碍: {alert_text}', access_token, 8, [alert_text])
                            except:
                                pass
                            return {'status': 400, 'msg': f'登录遇到障碍: {alert_text}', 'balance': '0.00'}
                    
                    # 检查是否有错误信息
                    try:
                        error_elements = driver.find_elements(By.XPATH, "//*[contains(@class,'error') or contains(@class,'alert') or contains(@style,'color: red')]")
                        for elem in error_elements:
                            error_text = elem.text.strip()
                            if error_text:
                                print(f"❌ 页面显示错误信息: {error_text}")
                                return {'status': 400, 'msg': f'登录失败: {error_text}', 'balance': '0.00'}
                    except:
                        pass
                    
                    # 重要：不要过早判断登录失败，给页面更多时间完成跳转
                    print("🔍 仍在登录页面，等待页面跳转...")
                    # 等待更长时间，让页面有机会跳转
                    time.sleep(2)
                    
                    # 再次检查页面状态
                    current_url = driver.current_url
                    if 'Login' in current_url or '登录' in current_url:
                        print("⚠️ 等待5秒后仍在登录页面，可能登录提交失败")
                        # 但不要立即返回，继续尝试后续流程
                        print("🔄 继续尝试后续流程...")
                    else:
                        print("✅ 页面已成功跳转")
                else:
                    print("✅ 已离开登录页面，跳过登录障碍检查")
                
        except Exception as e:
            print(f"检查登录状态时发生异常: {e}")
            # 继续执行，不中断流程
        
        print("✅ 登录状态检查通过，开始寻找同意按钮...")
        
        # 等待页面完全加载，特别是协议页面
        print("⏳ 等待协议页面完全加载...")
        time.sleep(2)
        
        # 尝试多种方式定位同意按钮
        agreeBtn = None
        
        # 首先尝试等待页面标题包含"协议"或"Agreement"
        try:
            WebDriverWait(driver, 10).until(
                lambda d: "协议" in d.title or "Agreement" in d.title
            )
            print("✅ 确认已进入协议页面")
        except:
            print("⚠️ 未检测到协议页面标题，继续尝试查找按钮")
        
        # 方式1：通过ID查找id="agree"的input button按钮
        try:
            print("🔍 方式1：通过ID查找id='agree'的input button按钮...")
            agreeBtn = WebDriverWait(driver, 8, 0.5).until(
                EC.element_to_be_clickable((By.ID, "agree"))
            )
            print("✅ 方式1成功：找到id='agree'的按钮")
        except Exception as e1:
            print(f"❌ 方式1失败：{e1}")
            
            # 方式2.1：若存在iframe/弹框容器，优先在容器内精准查找
            try:
                print("🔍 方式2.1：检测iframe/弹框容器并在局部范围内查找...")
                # 先尝试在各个 iframe 中查找
                found_in_iframe = False
                try:
                    frames = driver.find_elements(By.TAG_NAME, 'iframe')
                    for idx, frame in enumerate(frames):
                        try:
                            driver.switch_to.frame(frame)
                            # 在iframe内部查找弹框容器
                            modal_candidates = driver.find_elements(
                                By.XPATH,
                                "//*[@role='dialog' or contains(@class,'modal') or contains(@class,'dialog') or contains(@id,'agreement') or contains(@class,'agreement') or contains(@id,'protocol') or contains(@class,'protocol')]"
                            )
                            for c in modal_candidates:
                                try:
                                    if not c.is_displayed():
                                        continue
                                except:
                                    continue
                                try:
                                    agreeBtn = c.find_element(
                                        By.XPATH,
                                        ".//button[contains(normalize-space(.),'同意') or contains(normalize-space(.),'我已阅读') or contains(normalize-space(.),'继续') or contains(normalize-space(.),'同意并继续')] | .//input[@type='button' or @type='submit'][contains(@value,'同意') or contains(@value,'继续')] | .//a[contains(normalize-space(.),'同意') or contains(normalize-space(.),'继续')]"
                                    )
                                    print(f"✅ 方式2.1成功：在第{idx+1}个iframe的弹框内找到同意按钮")
                                    found_in_iframe = True
                                    break
                                except:
                                    continue
                            if found_in_iframe:
                                break
                        except:
                            # 某些iframe可能跨域或不可访问，忽略
                            continue
                finally:
                    try:
                        driver.switch_to.default_content()
                    except:
                        pass

                # 若未在iframe中找到，再在主文档中的弹框容器内查找
                if not agreeBtn and not found_in_iframe:
                    modal_candidates = driver.find_elements(
                        By.XPATH,
                        "//*[@role='dialog' or contains(@class,'modal') or contains(@class,'dialog') or contains(@id,'agreement') or contains(@class,'agreement') or contains(@id,'protocol') or contains(@class,'protocol')]"
                    )
                    for c in modal_candidates:
                        try:
                            if not c.is_displayed():
                                continue
                        except:
                            continue
                        try:
                            agreeBtn = c.find_element(
                                By.XPATH,
                                ".//button[contains(normalize-space(.),'同意') or contains(normalize-space(.),'我已阅读') or contains(normalize-space(.),'继续') or contains(normalize-space(.),'同意并继续')] | .//input[@type='button' or @type='submit'][contains(@value,'同意') or contains(@value,'继续')] | .//a[contains(normalize-space(.),'同意') or contains(normalize-space(.),'继续')]"
                            )
                            print("✅ 方式2.1成功：在弹框容器内找到同意按钮")
                            break
                        except:
                            continue
            except Exception as e21:
                print(f"❌ 方式2.1失败：{e21}")

            # 方式2：查找所有按钮，然后筛选包含"同意"的
            try:
                print("🔍 方式2：查找所有按钮元素...")
                all_buttons = driver.find_elements(By.TAG_NAME, "button")
                if ENABLE_DETAILED_LOGS:
                    print(f"找到 {len(all_buttons)} 个按钮:")
                for i, btn in enumerate(all_buttons):
                    try:
                        btn_text = btn.text.strip()
                        btn_id = btn.get_attribute('id') or '无ID'
                        btn_class = btn.get_attribute('class') or '无Class'
                        if ENABLE_DETAILED_LOGS:
                            print(f"  按钮{i+1}: '{btn_text}' (ID: {btn_id}, Class: {btn_class})")
                        if '同意' in btn_text:
                            agreeBtn = btn
                            print(f"✅ 方式2成功：找到包含'同意'的按钮")
                            break
                    except Exception as e5:
                        print(f"  按钮{i+1}: 无法获取文本 - {e5}")
            except Exception as e2:
                print(f"❌ 方式2失败：{e2}")
                
                # 方式3：通过更具体的XPath
                try:
                    print("🔍 方式3：通过具体XPath查找...")
                    xpath_patterns = [
                        "//button[contains(text(),'同意')]",
                        "//button[./span[contains(text(),'同意')]]",
                        "//*[@id='agree']",
                        "//*[contains(@class,'agree')]",
                        "//input[@value='同意']",
                        "//a[contains(text(),'同意')]"
                    ]
                    
                    for pattern in xpath_patterns:
                        try:
                            if ENABLE_DETAILED_LOGS:
                                print(f"  尝试XPath: {pattern}")
                            agreeBtn = WebDriverWait(driver, 3, 0.5).until(
                                EC.element_to_be_clickable((By.XPATH, pattern))
                            )
                            print(f"✅ 方式3成功：通过XPath '{pattern}' 找到按钮")
                            break
                        except:
                            continue
                            
                except Exception as e3:
                    print(f"❌ 方式3失败：{e3}")
                    
                    # 方式4：查找所有可点击元素
                    try:
                        print("🔍 方式4：查找所有可点击元素...")
                        clickable_elements = driver.find_elements(By.XPATH, "//button | //input[@type='button'] | //a[contains(@class,'btn')] | //*[@onclick]")
                        if ENABLE_DETAILED_LOGS:
                            print(f"找到 {len(clickable_elements)} 个可点击元素:")
                        for i, elem in enumerate(clickable_elements):
                            try:
                                elem_text = elem.text.strip()
                                elem_tag = elem.tag_name
                                elem_id = elem.get_attribute('id') or '无ID'
                                if ENABLE_DETAILED_LOGS:
                                    print(f"  元素{i+1} ({elem_tag}): '{elem_text}' (ID: {elem_id})")
                                if '同意' in elem_text:
                                    agreeBtn = elem
                                    print(f"✅ 方式4成功：找到包含'同意'的可点击元素")
                                    break
                            except Exception as e6:
                                print(f"  元素{i+1}: 无法获取信息 - {e6}")
                    except Exception as e4:
                        print(f"❌ 方式4失败：{e4}")
        
        if agreeBtn:
            print(f"✅ 找到同意按钮，准备点击。元素类型: {agreeBtn.tag_name}, 文本: '{agreeBtn.text}', ID: {agreeBtn.get_attribute('id')}")
            # 滚动到按钮位置
            driver.execute_script("arguments[0].scrollIntoView(true);", agreeBtn)
            time.sleep(1)
            
            # 尝试多种点击方式
            click_success = False
            try:
                # 方式1：直接点击
                agreeBtn.click()
                print("✅ 方式1：直接点击成功")
                click_success = True
            except Exception as e1:
                print(f"❌ 方式1失败：{e1}")
                try:
                    # 方式2：JavaScript点击
                    driver.execute_script("arguments[0].click();", agreeBtn)
                    print("✅ 方式2：JavaScript点击成功")
                    click_success = True
                except Exception as e2:
                    print(f"❌ 方式2失败：{e2}")
                    try:
                        # 方式3：Actions链点击
                        from selenium.webdriver.common.action_chains import ActionChains
                        actions = ActionChains(driver)
                        actions.move_to_element(agreeBtn).click().perform()
                        print("✅ 方式3：Actions链点击成功")
                        click_success = True
                    except Exception as e3:
                        print(f"❌ 方式3失败：{e3}")
                        try:
                            # 方式4：模拟回车键
                            agreeBtn.send_keys(Keys.RETURN)
                            print("✅ 方式4：回车键点击成功")
                            click_success = True
                        except Exception as e4:
                            print(f"❌ 方式4失败：{e4}")
            
            if click_success:
                print("✅ 同意按钮点击成功")
                time.sleep(1)  # 优化：减少等待时间到1秒
                
                # 验证是否成功进入下一页面（使用更智能的等待）
                try:
                    # 使用WebDriverWait等待页面跳转，最多等待3秒
                    from selenium.webdriver.support.ui import WebDriverWait
                    from selenium.webdriver.support import expected_conditions as EC
                    
                    wait = WebDriverWait(driver, 3, 0.3)
                    wait.until(lambda d: "Agreement" not in d.current_url and "协议" not in d.current_url)
                    print("✅ 成功离开协议页面")
                    
                except Exception:
                    # 如果等待超时，检查当前页面状态
                    try:
                        current_url = driver.current_url
                        page_title = driver.title
                        print(f"点击后当前URL: {current_url}")
                        print(f"点击后页面标题: {page_title}")
                        
                        # 检查是否仍在协议页面
                        if "Agreement" not in current_url and "协议" not in current_url:
                            print("✅ 成功离开协议页面")
                        else:
                            print("⚠️ 仍在协议页面，尝试查找其他需要点击的按钮...")
                            
                            # 查找可能需要的其他按钮
                            try:
                                # 查找确认按钮
                                confirm_buttons = driver.find_elements(By.XPATH, "//*[contains(text(),'确认') or contains(text(),'确定') or contains(text(),'Confirm') or contains(text(),'OK')]")
                                if confirm_buttons:
                                    for btn in confirm_buttons:
                                        try:
                                            btn_text = btn.text.strip()
                                            print(f"找到确认按钮: '{btn_text}'")
                                            if btn.is_enabled() and btn.is_displayed():
                                                print(f"点击确认按钮: '{btn_text}'")
                                                btn.click()
                                                time.sleep(1)  # 优化：减少到1秒
                                                break
                                        except Exception as e:
                                            print(f"点击确认按钮失败: {e}")
                                            continue
                                
                                # 查找进入按钮
                                enter_buttons = driver.find_elements(By.XPATH, "//*[contains(text(),'进入') or contains(text(),'Enter') or contains(text(),'开始') or contains(text(),'Start')]")
                                if enter_buttons:
                                    for btn in enter_buttons:
                                        try:
                                            btn_text = btn.text.strip()
                                            print(f"找到进入按钮: '{btn_text}'")
                                            if btn.is_enabled() and btn.is_displayed():
                                                print(f"点击进入按钮: '{btn_text}'")
                                                btn.click()
                                                time.sleep(1)  # 优化：减少到1秒
                                                break
                                        except Exception as e:
                                            print(f"点击进入按钮失败: {e}")
                                            continue
                                
                                # 再次检查页面状态（减少等待时间）
                                time.sleep(1)  # 优化：减少到1秒
                                current_url = driver.current_url
                                page_title = driver.title
                                print(f"再次检查 - 当前URL: {current_url}")
                                print(f"再次检查 - 页面标题: {page_title}")
                                
                                if "Agreement" not in current_url and "协议" not in current_url:
                                    print("✅ 成功离开协议页面")
                                else:
                                    print("⚠️ 仍然在协议页面，可能需要手动处理")
                                
                            except Exception as e:
                                print(f"查找其他按钮时发生异常: {e}")
                    
                    except Exception as e:
                        print(f"验证页面跳转失败: {e}")
            else:
                print("❌ 所有点击方式都失败")
        else:
            print("⚠️ 所有方式都未找到同意按钮")
            # 打印当前页面信息
            try:
                current_url = driver.current_url
                page_title = driver.title
                print(f"当前页面URL: {current_url}")
                
                # 打印页面源码中的关键信息
                if ENABLE_DETAILED_LOGS:
                    page_source = driver.page_source
                    if '同意' in page_source:
                        print("✅ 页面源码中包含'同意'文本")
                        # 查找包含"同意"的文本位置
                        lines = page_source.split('\n')
                        for i, line in enumerate(lines):
                            if '同意' in line:
                                print(f"  第{i+1}行: {line.strip()}")
                    else:
                        print("❌ 页面源码中未找到'同意'文本")
                
                if "Agreement" in page_title or "协议" in page_title:
                    print("⚠️ 检测到协议页面，但未找到同意按钮")
                    # 尝试刷新页面重新查找（使用安全的页面刷新方法，支持超时控制和自动恢复）
                    print("🔄 尝试刷新页面...")
                    try:
                        from xy_client.services.tools.PageRefreshManager import get_refresh_manager
                        refresh_manager = get_refresh_manager(page_load_timeout=10, max_retry=2)
                        success = refresh_manager.safe_refresh(driver, reason="刷新页面重新查找同意按钮",
                                                              check_loading=True, timeout=10)
                        if not success:
                            print(f"⚠️ 刷新页面失败（已重试），但继续执行")
                    except ImportError:
                        # 如果导入失败，使用原有的简单方式
                        driver.refresh()
                    time.sleep(1)  # 优化：减少等待时间
                    # 重新查找同意按钮
                    try:
                        agreeBtn = WebDriverWait(driver, 10, 0.5).until(
                            EC.element_to_be_clickable((By.XPATH, "//*[contains(text(),'同意')]"))
                        )
                        if agreeBtn:
                            print("✅ 刷新后找到同意按钮，准备点击")
                            agreeBtn.click()
                            time.sleep(1)  # 优化：减少等待时间
                    except Exception as e:
                        print(f"刷新后仍未找到同意按钮: {e}")
            except Exception as e:
                print(f"获取页面信息失败: {e}")
                import traceback
                traceback.print_exc()
        
        # 如果点击同意按钮失败，检查是否是因为登录障碍
        try:
            current_url = driver.current_url
            if 'Login' in current_url or '登录' in current_url:
                print("⚠️ 仍在登录页面，可能遇到登录障碍")
                return {'status': 400, 'msg': '登录遇到障碍，无法进入协议页面', 'balance': '0.00'}
        except:
            pass
        
        # 继续执行，尝试后续步骤
    time.sleep(3)

    print('xxxxxxxxxxxxxxxxxxxxxxxxxxx')

    # 处理登录成功后的弹框（只处理一次）
    # 如果已经在责任声明处理时处理过弹框和获取了余额，就跳过这部分
    if not already_handled_agreement:
        try:
            print("🔍 开始处理登录成功后的弹框...")
            
            # 等待页面完全加载
            time.sleep(3)
            
            # 优先查找右上角的关闭按钮
            try:
                closeBtn = WebDriverWait(driver, 5, 0.5).until(
                    EC.element_to_be_clickable((By.CSS_SELECTOR, ".btn-close.fn-close"))
                )
                if closeBtn and closeBtn.is_displayed():
                    print("✅ 找到右上角关闭按钮，准备点击")
                    
                    # 尝试多种点击方式
                    try:
                        # 方式1：直接点击
                        closeBtn.click()
                        print("✅ 直接点击成功")
                    except Exception as click_e:
                        print(f"⚠️ 直接点击失败: {click_e}")
                        try:
                            # 方式2：JavaScript点击
                            driver.execute_script("arguments[0].click();", closeBtn)
                            print("✅ JavaScript点击成功")
                        except Exception as js_e:
                            print(f"⚠️ JavaScript点击失败: {js_e}")
                            try:
                                # 方式3：移动到元素后点击
                                from selenium.webdriver.common.action_chains import ActionChains
                                ActionChains(driver).move_to_element(closeBtn).click().perform()
                                print("✅ ActionChains点击成功")
                            except Exception as action_e:
                                print(f"⚠️ ActionChains点击失败: {action_e}")
                                # 方式4：按ESC键关闭弹框
                                from selenium.webdriver.common.keys import Keys
                                driver.find_element(By.TAG_NAME, "body").send_keys(Keys.ESCAPE)
                                print("✅ 使用ESC键关闭弹框")
                    
                    time.sleep(2)  # 等待弹框关闭
                    # 注意：不要在这里return，需要继续执行后续的余额获取等操作
            except Exception as e:
                print(f"⚠️ 未找到关闭按钮: {e}")
            
            # 备用方案：查找确认按钮
            try:
                enterBtn = WebDriverWait(driver, 5, 0.5).until(
                    EC.element_to_be_clickable((By.ID, 'btn_enter'))
                )
                if enterBtn and enterBtn.is_displayed():
                    print("✅ 找到确认按钮，准备点击")
                    enterBtn.click()
                    print("✅ 确认按钮点击成功")
                    time.sleep(2)  # 等待弹框关闭
                else:
                    print("⚠️ 确认按钮不可见或不可点击")
            except Exception as e:
                print(f"⚠️ 未找到确认按钮: {e}")
                
                # 尝试查找其他可能的弹框按钮
                try:
                    # 查找常见的弹框按钮
                    popup_buttons = [
                        "//button[contains(text(),'确认')]",
                        "//button[contains(text(),'确定')]", 
                        "//button[contains(text(),'进入')]",
                        "//button[contains(text(),'开始')]",
                        "//button[contains(text(),'OK')]",
                        "//button[contains(text(),'Confirm')]",
                        "//button[contains(text(),'Enter')]",
                        "//button[contains(text(),'Start')]",
                        "//input[@type='button' and contains(@value,'确认')]",
                        "//input[@type='button' and contains(@value,'确定')]",
                        "//input[@type='submit' and contains(@value,'确认')]",
                        "//input[@type='submit' and contains(@value,'确定')]"
                    ]
                    
                    for button_xpath in popup_buttons:
                        try:
                            buttons = driver.find_elements(By.XPATH, button_xpath)
                            for btn in buttons:
                                if btn.is_displayed() and btn.is_enabled():
                                    btn_text = btn.text or btn.get_attribute('value') or '未知'
                                    print(f"✅ 找到弹框按钮: '{btn_text}'，准备点击")
                                    btn.click()
                                    print(f"✅ 弹框按钮 '{btn_text}' 点击成功")
                                    time.sleep(3)
                                    break
                            else:
                                continue
                            break
                        except Exception as e:
                            continue
                            
                except Exception as e:
                    print(f"⚠️ 查找弹框按钮失败: {e}")
            
            # 检查是否还有未关闭的弹框
            try:
                # 查找可能的弹框遮罩层
                overlays = driver.find_elements(By.XPATH, "//div[contains(@class,'modal') or contains(@class,'popup') or contains(@class,'overlay') or contains(@class,'dialog')]")
                if overlays:
                    print(f"⚠️ 检测到 {len(overlays)} 个可能的弹框遮罩层")
                    # 尝试按ESC键关闭弹框
                    try:
                        from selenium.webdriver.common.keys import Keys
                        driver.find_element(By.TAG_NAME, 'body').send_keys(Keys.ESCAPE)
                        print("✅ 尝试按ESC键关闭弹框")
                        time.sleep(2)
                    except Exception as e:
                        print(f"⚠️ 按ESC键失败: {e}")
                
                # 查找可能的关闭按钮
                close_buttons = driver.find_elements(By.XPATH, "//button[contains(@class,'close') or contains(@class,'cancel') or contains(text(),'关闭') or contains(text(),'取消') or contains(text(),'×')]")
                if close_buttons:
                    for btn in close_buttons:
                        if btn.is_displayed() and btn.is_enabled():
                            try:
                                btn.click()
                                print("✅ 找到并点击关闭按钮")
                                time.sleep(2)
                                break
                            except Exception as e:
                                continue
                                
            except Exception as e:
                print(f"⚠️ 检查弹框状态失败: {e}")
                
            print("✅ 弹框处理完成")
        
        except Exception as e:
            print(f"❌ 处理弹框时发生异常: {e}")
            SystemsUsers.pushErrorLog('弹框处理异常：', access_token, 8, e.args)
            # 不抛出异常，继续执行后续步骤
    else:
        print("✅ 已在责任声明处理时完成弹框处理，跳过重复处理")
    
    # 注意：不再重复调用handle_popup_dialogs函数，避免重复处理
    
    print("------------------------------end in-------------------------------")

    # 关键修复：在获取cookies前，确保WebDriver连接正常，如果连接断开则尝试恢复
    try:
        # 尝试获取当前URL来验证连接
        test_url = driver.current_url
    except Exception as conn_e:
        print(f"⚠️ WebDriver连接异常: {conn_e}，尝试恢复连接...")
        try:
            if hasattr(mainWindow, 'browser_manager'):
                recovered_driver = mainWindow.browser_manager.check_and_recover_browser_connection(silent=False)
                if recovered_driver:
                    driver = recovered_driver
                    mainWindow.driver = driver
                    print("✅ WebDriver连接已恢复")
                else:
                    print("❌ 无法恢复WebDriver连接")
                    return {'status': 500, 'msg': f'WebDriver连接失败: {conn_e}', 'balance': pre_fetched_balance if 'pre_fetched_balance' in locals() else '0.00'}
            else:
                print("❌ 无browser_manager，无法恢复连接")
                return {'status': 500, 'msg': f'WebDriver连接失败: {conn_e}', 'balance': pre_fetched_balance if 'pre_fetched_balance' in locals() else '0.00'}
        except Exception as recover_e:
            print(f"❌ 恢复连接失败: {recover_e}")
            return {'status': 500, 'msg': f'WebDriver连接失败: {conn_e}', 'balance': pre_fetched_balance if 'pre_fetched_balance' in locals() else '0.00'}

    # 获取cookies，如果失败则尝试恢复连接后重试
    try:
        cookies = driver.get_cookies()
    except Exception as cookie_e:
        print(f"⚠️ 获取cookies失败: {cookie_e}，尝试恢复连接后重试...")
        try:
            if hasattr(mainWindow, 'browser_manager'):
                recovered_driver = mainWindow.browser_manager.check_and_recover_browser_connection(silent=False)
                if recovered_driver:
                    driver = recovered_driver
                    mainWindow.driver = driver
                    cookies = driver.get_cookies()
                    print("✅ 恢复连接后成功获取cookies")
                else:
                    print("❌ 无法恢复连接，使用已保存的cookies（如果有）")
                    if hasattr(mainWindow, 'browser_cookies') and mainWindow.browser_cookies:
                        # 如果已有cookies，尝试使用（但需要转换为列表格式）
                        print("⚠️ 使用已保存的cookies，但可能不完整")
                        cookies = []  # 设置为空，后续会使用已保存的cookies
                    else:
                        return {'status': 500, 'msg': f'无法获取cookies: {cookie_e}', 'balance': pre_fetched_balance if 'pre_fetched_balance' in locals() else '0.00'}
            else:
                return {'status': 500, 'msg': f'无法获取cookies: {cookie_e}', 'balance': pre_fetched_balance if 'pre_fetched_balance' in locals() else '0.00'}
        except Exception as recover_e:
            print(f"❌ 恢复连接失败: {recover_e}")
            return {'status': 500, 'msg': f'无法获取cookies: {cookie_e}', 'balance': pre_fetched_balance if 'pre_fetched_balance' in locals() else '0.00'}

    # 获取user_agent，如果失败则使用默认值
    try:
        user_agent = driver.execute_script("return navigator.userAgent")
    except Exception as ua_e:
        print(f"⚠️ 获取User-Agent失败: {ua_e}，使用默认值")
        user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'
    
    print('User-Agent', user_agent)

    # 获取当前URL和域名
    try:
        current_url = driver.current_url
        ssc_domain, host = tools.getHostNameData(current_url)  # 重新赋值新域名
    except Exception as url_e:
        print(f"⚠️ 获取当前URL失败: {url_e}，尝试使用已保存的域名")
        ssc_domain = mainWindow.domain_val.text() if hasattr(mainWindow, 'domain_val') else ''
        if not ssc_domain:
            ssc_domain = getattr(mainWindow, 'wp_domain', '')
        host = ''
    mainWindow.domain_val.setText(ssc_domain)
    mainWindow.wp_domain = ssc_domain

    url = robot_domain + '/api/index/update-user-cookies'
    post_data = {'url': url, 'account': account, 'password': pwd, 'ssc_domain': ssc_domain, 'cookies': cookies,
                 'user_agent': user_agent,
                 'access_token': access_token}
    mainWindow.browser_cookies = cookies
    mainWindow.driver = driver
    print('browser_cookies', mainWindow.browser_cookies)

    cookies_str = ''
    for cookie in cookies:
        cookies_str += cookie['name'] + '=' + cookie['value'] + ';'
    mainWindow.browser_cookies = cookies_str

    headers = {'content-type': 'application/json'}  # 字典

    # rst = requests.post(url, data=json.dumps(post_data), headers=headers)
    rst = globalSession.post(url, data=json.dumps(post_data), headers=headers)
    time.sleep(3)

    if ENABLE_DETAILED_LOGS:
        print(cookies)
        print(post_data)
    rstData = rst.json()

    # 不在这里处理弹框，因为在责任声明处理时会出错
    # 如果有弹框，会在责任声明处理完成后处理
    print("🔍 跳过弹框处理，先获取余额信息...")
    
    # 如果已经在责任声明处理时获取了余额，直接使用
    if pre_fetched_balance is not None:
        print(f"✅ 使用责任声明处理时已获取的余额: {pre_fetched_balance}")
        balance = pre_fetched_balance
    else:
        # 关键优化：优先使用盘口API获取余额（不依赖WebDriver）
        balance = None
        try:
            from xy_client.services.Lucky5.core.platform_api import get_balance_by_api
            print("🔍 优先使用盘口API获取余额...")
            balance = get_balance_by_api(mainWindow)
            if balance is not None:
                print(f"✅ 通过API成功获取余额: {balance}")
        except ImportError:
            print("⚠️ platform_api模块不可用，使用浏览器获取余额")
        except Exception as api_e:
            print(f"⚠️ API获取余额失败: {api_e}，使用浏览器获取余额作为备用")
        
        # 如果API获取失败，使用浏览器获取作为备用
        if balance is None:
            try:
                print("🔍 开始从浏览器获取余额信息...")
                WebDriverWait(driver, 10).until(
                    EC.presence_of_element_located((By.XPATH, '//*[@id="CreditBalance"]'))
                )
                balance = driver.find_element(By.ID, "CreditBalance").get_attribute('textContent')
                print(f"✅ 从浏览器成功获取余额: {balance}")
            except Exception as e:
                error_msg = str(e)
                print(f"❌ 无法获取余额信息: {error_msg}")
                
                # 检查是否是连接断开错误
                is_connection_error = (
                    '10054' in error_msg or 
                    'ConnectionResetError' in error_msg or 
                    'Connection aborted' in error_msg or
                    'InvalidSessionIDException' in error_msg or
                    'Tried to run command without establishing a connection' in error_msg or
                    'NoSuchElementError' in error_msg
                )
                
                if is_connection_error:
                    print("⚠️ 余额获取失败：可能是连接断开或元素未加载，尝试检查页面状态...")
                # 如果是连接错误，不立即判断为登录失败，而是尝试检查当前URL
                try:
                    current_url = driver.current_url
                    print(f"🔍 当前URL: {current_url}")
                    
                    # 如果URL不在登录页面，说明登录可能成功，只是余额元素未找到
                    if 'Login' not in current_url and '登录' not in current_url:
                        print("⚠️ 不在登录页面，但余额元素未找到，可能页面未完全加载")
                        balance = '0.00'  # 使用默认余额，但不判定为登录失败
                        print(f"⚠️ 使用默认余额: {balance}")
                    else:
                        print("⚠️ 仍在登录页面，登录失败")
                        return {'status': 400, 'msg': '登录失败，无法获取余额信息', 'balance': '0.00'}
                except Exception as url_e:
                    # 如果连URL都获取不到，说明连接确实断开了
                    print(f"⚠️ 无法获取当前URL，连接可能已断开: {url_e}")
                    balance = '0.00'  # 使用默认余额
                    print(f"⚠️ 使用默认余额: {balance}")
            else:
                # 其他错误，检查是否仍在登录页面
                try:
                    current_url = driver.current_url
                    if 'Login' in current_url or '登录' in current_url:
                        print("⚠️ 仍在登录页面，登录失败")
                        return {'status': 400, 'msg': '登录失败，无法获取余额信息', 'balance': '0.00'}
                except:
                    pass
                balance = '0.00'  # 设置默认余额
                print(f"⚠️ 使用默认余额: {balance}")

    rstData['balance'] = balance
    print('余额:' + balance)

    return rstData


def loginAgent(account='', pwd='', ssc_domain='', mainWindow=None):
    now_time = str(int(float(time.time()) * 1000))
    # 全局session对象 - 配置连接池，避免"Connection pool is full"错误
    mainWindow.agent_session = requests.Session()
    
    # 配置连接池，确保有足够的连接数
    adapter = HTTPAdapter(
        pool_connections=50,   # 连接池数量
        pool_maxsize=200,      # 每个连接池的最大连接数
        max_retries=3,
        pool_block=False       # 不阻塞，连接池满时创建新连接
    )
    mainWindow.agent_session.mount('http://', adapter)
    mainWindow.agent_session.mount('https://', adapter)
    
    # 禁用 SSL 证书校验
    mainWindow.agent_session.verify = False
    # 禁用 SSL 校验警告

    # 关键修复：代理登录强制使用Firefox浏览器，不使用Chrome
    # 不使用配置文件中的driver_name，避免误用Chrome
    # 注意：tools.getDriver('firefox') 会自动启动Firefox浏览器实例
    agent_driver_name = 'firefox'
    print(f"🦊 [代理登录] 强制使用Firefox浏览器，端口: {mainWindow.agent_port}")
    print(f"🦊 [代理登录] 注意：Firefox浏览器将由tools.getDriver自动启动，无需预先手动启动")
    browser = tools.getDriver(agent_driver_name, mainWindow.agent_port)
    browser.set_window_size(1300, 840)  # 设置浏览器大小分辨率 1300*840
    
    # 关键修复：启用性能日志，用于捕获网络请求错误（如500错误）
    # 注意：Firefox可能需要通过about:config启用devtools.console.stdout.enabled
    try:
        # 设置日志记录级别
        browser.set_page_load_timeout(30)  # 页面加载超时30秒
        if ENABLE_DETAILED_LOGS:
            print("✅ [代理登录] 已启用页面加载超时设置")
    except Exception as timeout_e:
        if ENABLE_DETAILED_LOGS:
            print(f"⚠️ [代理登录] 设置超时失败: {timeout_e}")
    
    print('agent_url:', ssc_domain + '/Member/Login?_=' + now_time)
    # 请求登录页面
    browser.get(ssc_domain + '/Member/Login?_=' + now_time)

    STR_READY_STATE = ''
    # 而直接操作页面就需要类似于下面的代码等待页面加载完成
    while STR_READY_STATE != 'complete':
        STR_READY_STATE = browser.execute_script('return document.readyState')
        if ENABLE_DETAILED_LOGS:
            print(STR_READY_STATE)
            print(f'STR_READY_STATE : {STR_READY_STATE}')
    # ssc_domain = 'http://f1.wg7s5297.xyz'
    # time.sleep(5)

    print("-----------------------------finish agent login - 1-----------------------------")

    # 关键修复：先处理可能存在的弹窗（如密码错误提示）
    try:
        browser.switch_to.alert.accept()
        print("✅ [代理登录] 已处理页面弹窗")
        time.sleep(1)
    except:
        pass  # 没有弹窗，继续执行

    # 等待账号输入框出现
    wait = WebDriverWait(browser, 10)
    input = wait.until(EC.presence_of_element_located((By.ID, "Account")))
    
    # 清空账号输入框并输入账号
    input.clear()
    input.send_keys(account)
    print(f"✅ [代理登录] 账号已输入: {account}")
    time.sleep(0.5)
    
    # 密码输入框
    input2 = wait.until(EC.presence_of_element_located((By.ID, "Password")))
    input2.clear()
    
    # 关键修复：确保密码正确输入，如果密码为空则报错
    if not pwd or not pwd.strip():
        raise ValueError("密码为空，请检查代理密码输入框")
    
    input2.send_keys(pwd)
    print(f"✅ [代理登录] 密码已输入（长度: {len(pwd)}）")
    time.sleep(0.5)

    # 提交按钮
    input3 = wait.until(EC.element_to_be_clickable((By.ID, 'btn-submit')))
    input3.click()
    print("✅ [代理登录] 已点击登录按钮")
    
    # 关键修复：等待页面响应，给服务器足够时间处理请求（远程环境可能需要更长时间）
    print("⏳ [代理登录] 等待登录响应...")
    time.sleep(5)  # 远程环境可能需要更长时间，增加到5秒
    
    # 关键修复：等待页面加载完成，确保服务器响应（包括错误响应）已完全加载
    try:
        # 等待页面状态变为complete
        WebDriverWait(browser, 8).until(lambda d: d.execute_script('return document.readyState') == 'complete')
        print("✅ [代理登录] 页面加载完成")
        # 额外等待，确保AJAX请求完成
        time.sleep(2)
    except:
        print("⚠️ [代理登录] 页面加载状态检查超时，继续检查错误信息")
    
    # 关键修复：检查页面响应，检测服务器错误（如500错误）
    # 服务器可能返回500错误（如数据库字段长度问题），需要检测并提示
    # 远程环境下可能需要多次检查，因为错误信息可能延迟加载或通过AJAX返回
    max_retries = 4
    for retry in range(max_retries):
        try:
            # 关键修复：通过JavaScript检查AJAX响应中的错误信息
            # 有些500错误通过AJAX返回，不会直接显示在页面源码中
            try:
                # 尝试通过JavaScript获取AJAX错误信息
                ajax_error = browser.execute_script("""
                    // 检查页面中是否有错误信息（可能在隐藏元素中）
                    var errorElements = document.querySelectorAll('[class*="error"], [id*="error"], [style*="color: red"]');
                    for (var i = 0; i < errorElements.length; i++) {
                        var text = errorElements[i].textContent || errorElements[i].innerText;
                        if (text && (text.indexOf('500') > -1 || text.indexOf('SQLSTATE') > -1 || text.indexOf('Data too long') > -1)) {
                            return text;
                        }
                    }
                    // 检查页面源码中是否有JSON错误
                    var bodyText = document.body ? document.body.innerText : '';
                    if (bodyText.indexOf('code":10501') > -1 || bodyText.indexOf('SQLSTATE') > -1) {
                        return bodyText;
                    }
                    return null;
                """)
                if ajax_error:
                    print(f"⚠️ [代理登录] JavaScript检测到错误信息: {ajax_error[:200]}")
                    if 'code":10501' in str(ajax_error) or 'SQLSTATE' in str(ajax_error) or 'Data too long' in str(ajax_error):
                        error_msg = "代理登录失败: 服务器数据库错误（服务器内部问题，请联系盘口客服）"
                        print(f"❌ [代理登录] {error_msg}")
                        return {
                            'status': 500,
                            'msg': error_msg,
                            'balance': '0.00'
                        }
            except Exception as js_e:
                if ENABLE_DETAILED_LOGS:
                    print(f"⚠️ [代理登录] JavaScript检查异常: {js_e}")
            
            page_source = browser.page_source
            
            # 检测数据库错误（如用户遇到的错误）- 改进匹配模式，覆盖更多情况
            if ('code":10501' in page_source or '"code":10501' in page_source or 
                'SQLSTATE' in page_source or 'Data too long' in page_source or
                'SQLSTATE[22001]' in page_source):
                import json
                import re
                error_msg = "代理登录失败: 服务器数据库错误（服务器内部问题，请联系盘口客服）"
                try:
                    # 尝试从页面源码中提取JSON格式的错误信息
                    json_match = re.search(r'\{[^{}]*"code"[^{}]*"message"[^{}]*\}', page_source, re.DOTALL)
                    if json_match:
                        error_data = json.loads(json_match.group())
                        if 'message' in error_data:
                            error_msg = f"代理登录失败: 服务器错误 - {error_data['message']}"
                            print(f"❌ [代理登录] 检测到服务器数据库错误: {error_data['message']}")
                except:
                    pass
                
                print(f"❌ [代理登录] {error_msg}")
                return {
                    'status': 500,
                    'msg': error_msg,
                    'balance': '0.00'
                }
            
            # 检测其他服务器500错误
            if ('500' in page_source or 'Internal Server Error' in page_source) and ('error' in page_source.lower() or '错误' in page_source):
                error_msg = "代理登录失败: 服务器返回500错误（服务器内部错误，请联系盘口客服）"
                print(f"❌ [代理登录] {error_msg}")
                return {
                    'status': 500,
                    'msg': error_msg,
                    'balance': '0.00'
                }
            
            # 如果页面还在登录页面且包含错误信息，可能登录失败
            current_url = browser.current_url
            if ('Login' in current_url or '登录' in current_url) and retry < max_retries - 1:
                # 还没有检查完所有重试次数，继续等待
                print(f"⏳ [代理登录] 仍在登录页面，等待错误信息加载（重试 {retry + 1}/{max_retries}）...")
                time.sleep(2)
                continue
            
            # 如果检查完所有重试都没有发现明显的错误信息，跳出循环继续后续流程
            break
            
        except Exception as check_e:
            if ENABLE_DETAILED_LOGS:
                print(f"⚠️ [代理登录] 检查页面错误信息时异常（重试 {retry + 1}/{max_retries}）: {check_e}")
            if retry < max_retries - 1:
                time.sleep(2)  # 等待后重试
            else:
                # 最后一次重试失败，记录但继续流程
                print(f"⚠️ [代理登录] 无法检查页面错误信息，继续后续流程")

    # 关键修复：处理登录后的弹窗（如密码错误等）
    # 如果检测到错误弹窗，立即返回错误状态，不继续执行后续操作
    try:
        alert = browser.switch_to.alert
        alert_text = alert.text.strip()
        print(f"⚠️ [代理登录] 检测到弹窗: {alert_text}")
        
        # 关闭弹窗
        alert.accept()
        time.sleep(0.5)
        
        # 判断是否为错误弹窗（登录失败）
        error_keywords = ['错误', '失败', '密码', '不正确', '无效', '不存在', '锁定', '限制']
        is_error = any(keyword in alert_text for keyword in error_keywords)
        
        if is_error:
            error_msg = f"代理登录失败: {alert_text}"
            print(f"❌ [代理登录] {error_msg}")
            # 返回错误状态，不继续执行
            return {
                'status': 400,
                'msg': error_msg,
                'balance': '0.00'
            }
    except:
        pass  # 没有弹窗，继续执行

    # 等待"agree"元素出现（只有登录成功才会进入这一步）
    try:
        print("⏳ [代理登录] 等待'agree'元素出现...")
        input4 = wait.until(EC.presence_of_element_located((By.ID, "agree")))
        if ENABLE_DETAILED_LOGS:
            print('✅ [代理登录] 找到agree元素:', input4)
        input4.click()
        print("✅ [代理登录] agree元素点击成功")
        time.sleep(0.5)  # 优化：减少等待时间到0.5秒
    except Exception as agree_e:
        # 再次检查是否有弹窗（可能在查找元素时出现）
        try:
            alert = browser.switch_to.alert
            alert_text = alert.text.strip()
            alert.accept()
            error_keywords = ['错误', '失败', '密码', '不正确', '无效', '不存在', '锁定', '限制']
            is_error = any(keyword in alert_text for keyword in error_keywords)
            if is_error:
                error_msg = f"代理登录失败: {alert_text}"
                print(f"❌ [代理登录] {error_msg}")
                return {
                    'status': 400,
                    'msg': error_msg,
                    'balance': '0.00'
                }
        except:
            pass
        
        # 如果确实找不到agree元素，检查是否仍在登录页面
        try:
            current_url = browser.current_url
            print(f"🔍 [代理登录] 当前页面URL: {current_url}")
            
            # 如果仍在登录页面，可能是登录失败
            if 'Login' in current_url or '登录' in current_url:
                error_msg = "代理登录失败: 登录后仍在登录页面，可能是账号或密码错误"
                print(f"❌ [代理登录] {error_msg}")
                return {
                    'status': 400,
                    'msg': error_msg,
                    'balance': '0.00'
                }
            
            # 检查页面是否包含错误信息
            page_source = browser.page_source
            
            # 优先检查是否是服务器错误（500错误等）
            if 'code":10501' in page_source or 'SQLSTATE' in page_source or 'Data too long' in page_source:
                import json
                import re
                error_msg = "代理登录失败: 服务器数据库错误（服务器内部问题，请联系盘口客服）"
                try:
                    json_match = re.search(r'\{[^{}]*"code"[^{}]*"message"[^{}]*\}', page_source, re.DOTALL)
                    if json_match:
                        error_data = json.loads(json_match.group())
                        if 'message' in error_data:
                            error_msg = f"代理登录失败: 服务器错误 - {error_data['message']}"
                            print(f"❌ [代理登录] 检测到服务器数据库错误: {error_data['message']}")
                except:
                    pass
                print(f"❌ [代理登录] {error_msg}")
                return {
                    'status': 500,
                    'msg': error_msg,
                    'balance': '0.00'
                }
            
            if 'agree' not in page_source.lower():
                error_msg = "代理登录失败: 未找到登录成功标识，可能登录失败"
                print(f"❌ [代理登录] {error_msg}")
                return {
                    'status': 400,
                    'msg': error_msg,
                    'balance': '0.00'
                }
        except Exception as debug_e:
            print(f"⚠️ [代理登录] 调试信息获取失败: {debug_e}")
        
        # 如果以上检查都通过，但仍然找不到元素，抛出异常
        error_msg = f"代理登录异常: 找不到agree元素 - {str(agree_e)}"
        print(f"❌ [代理登录] {error_msg}")
        return {
            'status': 500,
            'msg': error_msg,
            'balance': '0.00'
        }

    print('xxxxxxxxxxxxxxxxxxxxxxxxxxx')

    try:
        input5 = browser.find_element(By.ID, 'btn_enter')
        if ENABLE_DETAILED_LOGS:
            print('input52', input5)
        # input3.send_keys(Keys.ENTER)
        input5.click()  # 确认阅读
        time.sleep(1)
    except Exception as e:
        SystemsUsers.pushErrorLog('代理登录异常：', access_token, 8, e.args)

    print("------------------------------end in-------------------------------")

    user_agent = browser.execute_script("return navigator.userAgent")
    print('User-Agent', user_agent)

    mainWindow.agent_user_agent = user_agent

    # 登录成功后提取浏览器的会话信息
    agent_cookies = getAgentCookieStr(browser, mainWindow)
    mainWindow.agent_cookies = agent_cookies
    if ENABLE_DETAILED_LOGS:
        print('agent_cookies：', agent_cookies)

    # 浏览器对象
    mainWindow.agent_browser = browser
    # obj.agent_browser_cookies = cookies
    # print('browser_cookies', obj.browser_cookies)

    try:
        input5 = browser.find_element(By.CLASS_NAME, 'btn-close')
        if ENABLE_DETAILED_LOGS:
            print('input-close', input5)
        input5.click()  # 确认阅读
        time.sleep(1)
    except Exception as e:
        SystemsUsers.pushErrorLog('代理登录异常：', access_token, 8, e.args)

    # 代理浏览器的当前url
    try:
        if mainWindow.agent_browser and mainWindow.agent_browser.current_url:
            current_url = mainWindow.agent_browser.current_url
            print('login_xxx_current_url：', current_url)
        else:
            raise NoSuchWindowException('Target window is closed or not found')
        agent_domain, host = getHostNameData(current_url)
        mainWindow.agent_domain_val.setText(agent_domain)
        # 关键修复：确保 agent_domain 属性也被设置，供获取日志时使用
        mainWindow.agent_domain = agent_domain
        print(f"✅ [代理登录] agent_domain已设置: {agent_domain}")
    except (NoSuchWindowException, Exception) as e:
        # 如果浏览器不可用或出现其他异常，使用传入的域名或界面中的域名
        print(f'⚠️ [代理登录] 获取浏览器URL失败: {e}，使用备用域名')
        if hasattr(mainWindow, 'agent_domain_val') and mainWindow.agent_domain_val:
            current_url = mainWindow.agent_domain_val.text()
        else:
            current_url = ssc_domain
        # 确保agent_domain格式正确（包含协议）
        if current_url:
            current_url = current_url.rstrip('/')
            if not current_url.startswith(('http://', 'https://')):
                current_url = 'https://' + current_url
        mainWindow.agent_domain = current_url
        print(f'⚠️ [代理登录] 使用备用域名: {current_url}')

    # 关键修复：确保所有获取日志所需的变量都已正确设置
    if not hasattr(mainWindow, 'agent_cookies') or not mainWindow.agent_cookies:
        print("⚠️ [代理登录] 警告：agent_cookies未设置")
    if not hasattr(mainWindow, 'agent_user_agent') or not mainWindow.agent_user_agent:
        print("⚠️ [代理登录] 警告：agent_user_agent未设置")
    if not hasattr(mainWindow, 'agent_browser') or not mainWindow.agent_browser:
        print("⚠️ [代理登录] 警告：agent_browser未设置")
    if not hasattr(mainWindow, 'agent_domain') or not mainWindow.agent_domain:
        print("⚠️ [代理登录] 警告：agent_domain未设置")

    return {'status': 200, 'msg': '登陆成功', 'balance': '0.00'}


def getAgentCookieStr(browser, obj):
    cookies = browser.get_cookies()
    # 将会话信息设置到 requests.Session() 对象中
    agent_cookies = ''
    for cookie in cookies:
        obj.agent_session.cookies.set(cookie['name'], cookie['value'])
        agent_cookies += cookie['name'] + '=' + cookie['value'] + ';'

    return agent_cookies


def postBetRequest(url, post_data, headers):
    rst_text = ''
    try:
        # 使用GlobalSession，复用连接池，避免连接累积
        rst = globalSession.post(url, data=parse.urlencode(post_data), headers=headers, verify=False, timeout=(5, 50))
        rst.encoding = rst.apparent_encoding
        rst_text = rst.text
        postRst = getPostRstByRstText(rst_text)
    except Exception as e:
        p('下注之后异常', e.args)
        postRst = {'Status': 0, 'code': 317, 'msg': e.args}

    return postRst, rst_text


def getPostRstByRstText(rst_text=''):
    try:
        postRst = json.loads(rst_text)
    except json.JSONDecodeError as e:
        if 'CompletedStatus":1' in rst_text:
            postRst = {'Status': 1, 'Data': {'CompletedStatus': 1, 'LackStatus': 0}}
        elif '余额不足' in rst_text:
            postRst = {'Status': 0, 'code': 302, 'msg': '余额不足'}
        elif '登录' in rst_text:
            postRst = {'Status': 0, 'code': 303, 'msg': '请重新登录'}
        elif '短时间内重复提交' in rst_text:
            postRst = {'Status': 0, 'code': 304, 'msg': '短时间内重复提交'}
        elif '已关盘' in rst_text:
            postRst = {'Status': 0, 'code': 305, 'msg': '已关盘'}
        elif '维护中' in rst_text:
            postRst = {'Status': 0, 'code': 306, 'msg': '系统线路维护中'}
        elif '停押' in rst_text:
            postRst = {'Status': 0, 'code': 307, 'msg': '您的账号已被停押'}
        elif '您当前使用的浏览器不支持cookie' in rst_text:
            postRst = {'Status': 0, 'code': 310, 'msg': '您当前使用的浏览器不支持cookie'}
        elif 'Bad Gateway' in rst_text:
            postRst = {'Status': 0, 'code': 311, 'msg': '网络故障'}
        elif 'Too Many Request' in rst_text:
            postRst = {'Status': 0, 'code': 312, 'msg': '代理请求太频繁'}
        elif 'ClearSession' in rst_text:
            postRst = {'Status': 0, 'code': 313, 'msg': '请求重定向跳转'}
        elif '请不要尝试使用程序或外挂下注' in rst_text:
            postRst = {'Status': 0, 'code': 314, 'msg': '请不要尝试使用程序或外挂下注'}
        elif '请至少选择一个号码' in rst_text:
            postRst = {'Status': 0, 'code': 315, 'msg': '请至少选择一个号码'}
        elif '您当前使用的浏览器不支持' in rst_text:
            postRst = {'Status': 0, 'code': 316, 'msg': rst_text}
        else:
            try:
                postRst = json.loads(rst_text)
            except:
                # 如果所有解析都失败，返回一个默认的错误响应
                postRst = {'Status': 0, 'code': 999, 'msg': '解析响应失败'}
    
    # 确保返回的字典包含必要的键
    if 'Status' not in postRst:
        postRst['Status'] = 0
    if 'msg' not in postRst:
        # 如果状态是成功且没有消息，设置默认成功消息
        if postRst.get('Status') == 1:
            postRst['msg'] = '下注成功'
        else:
            postRst['msg'] = '未知错误'
    
    return postRst


# 本地下注
def localBet(mWin, local_data):
    if ENABLE_DETAILED_LOGS:
        print('local_data1', local_data)
    bet_money = local_data['bet_money']
    if ENABLE_DETAILED_LOGS:
        print('bet_money', bet_money)
    bet_codes = local_data['local_codes']
    if ENABLE_DETAILED_LOGS:
        print('bet_codes', bet_codes)

    # 快译
    kuai_yi = mWin.browser.find_element(By.XPATH, '//*[@id="subnav"]/a[6]')
    kuai_yi.click()
    time.sleep(10)

    # 号码框
    textarea = mWin.browser.find_element(By.ID, 'textarea')
    textarea.send_keys(bet_codes)

    # 倍数框
    singles = mWin.browser.find_element(By.ID, 'tr_bets')
    singles.send_keys(bet_money)

    # 下注按钮
    bet_btn = mWin.browser.find_element(By.XPATH, '//*[@id="tr_bets"]/td/button[1]')

    postRst = {'Status': 1, 'Data': {'CompletedStatus': 1, 'LackStatus': 0}}
    print(mWin.browser)

    return postRst


def loginClient(mainWindow):
    try:
        print('==========================================')
        print('========= 登录检测开始 =========')
        print('==========================================')
        now_time = str(time.strftime("%H:%M", time.localtime()))
        print(f"🕐 检测时间: {now_time}")
        
        # 记录当前状态
        current_is_need_login = getattr(mainWindow, 'is_need_login', 'NOT_FOUND')
        print(f"📊 当前登录状态: is_need_login = {current_is_need_login}")
        
        # 检查浏览器状态
        has_driver = hasattr(mainWindow, 'driver') and mainWindow.driver is not None
        print(f"🌐 浏览器状态: driver={'存在' if has_driver else '不存在'}")
        
        if has_driver:
            try:
                current_url = mainWindow.driver.current_url
                print(f"🔗 当前URL: {current_url}")
            except Exception as url_e:
                print(f"⚠️ 无法获取URL: {url_e}")
        
        accountInfo = getAccountByToken()
        userInfo = accountInfo['data']
        server_status = userInfo.get('is_need_login', 'NOT_FOUND')
        print(f"☁️ 服务器登录状态: {server_status}")
        
        # 保护机制：如果当前已经登录成功，不允许服务器状态覆盖本地状态
        if hasattr(mainWindow, 'is_need_login') and mainWindow.is_need_login == 1:
            print(f"✅ 已登录，直接返回，不做任何操作")
            print(f"🔒 保护本地登录状态：拒绝服务器状态覆盖")
            print('==========================================')
            print('========= 登录检测结束（已登录）=========')
            print('==========================================')
            # 已登录，直接返回，不做任何操作
            return True
        elif hasattr(mainWindow, 'is_need_login') and mainWindow.is_need_login == 2:
            # 正在登录中，跳过服务器状态同步，继续检查浏览器状态
            if ENABLE_DETAILED_LOGS:
                print(f"🔄 正在登录中，跳过服务器状态同步")
        else:
            # 只有在本地未登录时（is_need_login == 0），才允许服务器状态覆盖
            server_status = userInfo.get('is_need_login', 0)
            mainWindow.is_need_login = server_status
            if ENABLE_DETAILED_LOGS:
                print(f"🔄 更新登录状态：服务器状态 {server_status} -> 本地状态 {mainWindow.is_need_login}")
            
            # 如果服务器状态为0但本地状态为1，说明服务器状态可能不准确，保持本地状态
            if server_status == 0 and hasattr(mainWindow, 'is_need_login') and mainWindow.is_need_login == 1:
                if ENABLE_DETAILED_LOGS:
                    print(f"⚠️ 服务器状态可能不准确，保持本地登录状态")
                mainWindow.is_need_login = 1
        
        print("🔄 开始执行 checkUserLoginJob...")
        is_need_login = checkUserLoginJob(mainWindow)  # 检测用户登陆
        print(f"📋 checkUserLoginJob 返回: {is_need_login}")
        
        if not is_need_login:
            print("⚠️ 检测结果：需要登录")
            print("🔄 将执行自动登录...")
            # 关键修复：检测到需要登录时，执行实际的登录操作，而不是只刷新页面
            try:
                # 优先使用智能登录管理（包含完整的登录流程）
                if hasattr(mainWindow, 'smart_login_management'):
                    print("🔄 [loginClient] 调用 smart_login_management 执行自动登录...")
                    login_success = mainWindow.smart_login_management()
                    if login_success:
                        print("✅ [loginClient] 自动登录成功")
                        # 更新登录状态
                        if hasattr(mainWindow, 'is_need_login'):
                            mainWindow.is_need_login = 1
                            print("✅ [loginClient] 已更新 is_need_login = 1")
                    else:
                        print("⚠️ [loginClient] 自动登录失败，将在下次检测时重试")
                else:
                    # 如果没有 smart_login_management，尝试使用 actLogin
                    print("⚠️ [loginClient] 未找到 smart_login_management，尝试使用 actLogin...")
                    if hasattr(mainWindow, 'actLogin'):
                        # 获取登录信息
                        account = getattr(mainWindow, 'wp_account', '') or getattr(mainWindow, 'username_val', None)
                        if account and hasattr(account, 'text'):
                            account = account.text()
                        pwd = getattr(mainWindow, 'wp_password', '') or getattr(mainWindow, 'pwd_val', None)
                        if pwd and hasattr(pwd, 'text'):
                            pwd = pwd.text()
                        ssc_domain = getattr(mainWindow, 'wp_domain', '') or getattr(mainWindow, 'domain_val', None)
                        if ssc_domain and hasattr(ssc_domain, 'text'):
                            ssc_domain = ssc_domain.text()
                        access_token = getattr(mainWindow, 'access_token', '')
                        
                        if account and pwd and ssc_domain:
                            print(f"🔄 [loginClient] 调用 actLogin 执行自动登录，账号: {account}")
                            mainWindow.actLogin(access_token, ssc_domain, account, pwd)
                        else:
                            print("❌ [loginClient] 缺少登录信息（账号、密码或域名），无法执行自动登录")
                    else:
                        print("❌ [loginClient] 未找到登录方法，只能刷新页面")
                        # 作为最后手段，刷新页面
                        user_login_job(mainWindow)
            except Exception as login_e:
                print(f"❌ [loginClient] 执行自动登录时异常: {login_e}")
                import traceback
                traceback.print_exc()
                # 登录异常时，尝试刷新页面作为备用
                try:
                    user_login_job(mainWindow)
                except Exception:
                    pass
        else:
            print("✅ 检测结果：不需要登录，跳过登录操作")

        if lottery_type == 8:
            is_need_login_agent = checkAgentLoginJob(mainWindow)  # 检测代理登陆
            if not is_need_login_agent:
                # 需要登录则刷新界面（只在确实需要登录时才执行）
                agent_login_job(mainWindow)

    except Exception as e:
        print(f"❌ 登录检测异常: {e}")
        traceback.print_exc()
        pushErrorLog('异常自动登陆3：' + str(mainWindow.current_qihao), mainWindow.access_token, lottery_type, str(e))
    finally:
        print('==========================================')
        print('========= 登录检测结束 =========')
        print('==========================================')


class KjDataCache:
    def __init__(self):
        self.last_push_time = {}
        self.min_interval = 8  # 最小推送间隔(秒)
        
    def can_push(self, qihao):
        current_time = time.time()
        last_time = self.last_push_time.get(qihao, 0)
        
        if current_time - last_time >= self.min_interval:
            self.last_push_time[qihao] = current_time
            # 清理旧数据
            self._cleanup()
            return True
        return False
        
    def _cleanup(self):
        current_time = time.time()
        # 清理超过1小时的缓存
        self.last_push_time = {
            k: v for k, v in self.last_push_time.items() 
            if current_time - v < 3600
        }

# 创建全局缓存实例
kj_data_cache = KjDataCache()

def check_and_recover_browser_connection(mainWindow, silent=False):
    """检查并恢复浏览器连接（参考拼多多爬虫的稳定机制）
    
    Args:
        mainWindow: 主窗口实例
        silent: 是否静默模式（不输出详细日志）
    
    Returns:
        bool: 连接是否正常
    """
    try:
        # 第一步：检查driver是否存在
        if not hasattr(mainWindow, 'driver') or not mainWindow.driver:
            if not silent:
                print("⚠️ 浏览器driver不存在，尝试重新连接...")
            return _reconnect_to_browser(mainWindow, silent)
        
        # 第二步：验证端口连通性（参考拼多多爬虫的方式）
        port_valid = False
        if hasattr(mainWindow, 'port') and mainWindow.port:
            import socket
            try:
                with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
                    s.settimeout(2)
                    result = s.connect_ex(('localhost', int(mainWindow.port)))
                    port_valid = (result == 0)
            except:
                port_valid = False
        
        if not port_valid:
            if not silent:
                print(f"⚠️ 调试端口 {mainWindow.port} 不可用，浏览器可能已关闭")
            # 清理driver引用
            try:
                mainWindow.driver.quit()
            except:
                pass
            mainWindow.driver = None
            return _reconnect_to_browser(mainWindow, silent)
        
        # 第三步：尝试检查连接是否有效（轻量级检查）
        try:
            # 尝试获取当前URL，如果失败说明连接断开
            current_url = mainWindow.driver.current_url
            if not silent:
                print(f"✅ 浏览器连接正常: {current_url}")
            return True
        except Exception as e:
            error_msg = str(e)
            error_type = type(e).__name__
            
            # 检查是否是连接断开错误或会话失效
            is_connection_error = (
                '10054' in error_msg or 
                'ConnectionResetError' in error_msg or 
                'Connection aborted' in error_msg or
                '远程主机强迫关闭' in error_msg or
                'InvalidSessionIDException' in error_type or
                'InvalidSessionIdException' in error_type or
                'Tried to run command without establishing a connection' in error_msg or
                'session deleted' in error_msg.lower() or
                'no such session' in error_msg.lower()
            )
            
            if is_connection_error:
                if not silent:
                    print(f"❌ 检测到浏览器连接断开 ({error_type}): {error_msg[:100]}")
            else:
                if not silent:
                    print(f"⚠️ 浏览器连接检查异常 ({error_type}): {error_msg[:100]}")
            
            # 尝试重新连接
            return _reconnect_to_browser(mainWindow, silent)
        
    except Exception as e:
        if not silent:
            print(f"❌ 检查浏览器连接异常: {e}")
            import traceback
            traceback.print_exc()
        return False


def _reconnect_to_browser(mainWindow, silent=False):
    """重新连接到浏览器（参考拼多多爬虫的稳定机制）"""
    try:
        if not silent:
            print("🔄 尝试重新连接浏览器...")
        
        # 清理旧连接
        try:
            if hasattr(mainWindow, 'driver') and mainWindow.driver:
                mainWindow.driver.quit()
        except:
            pass
        finally:
            mainWindow.driver = None
        
        # 验证端口是否可用（参考拼多多爬虫的验证方式）
        if not hasattr(mainWindow, 'port') or not mainWindow.port:
            if not silent:
                print("❌ 无法获取浏览器端口，无法重新连接")
            return False
        
        import socket
        port = int(mainWindow.port)
        
        # 等待端口可用（最多等待10秒）
        max_wait = 10
        port_available = False
        for i in range(max_wait):
            try:
                with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
                    s.settimeout(1)
                    result = s.connect_ex(('localhost', port))
                    if result == 0:
                        port_available = True
                        break
            except:
                pass
            time.sleep(1)
        
        if not port_available:
            if not silent:
                print(f"❌ 端口 {port} 不可用，浏览器可能未启动（连接失败，暂停重试）")
            # 关键修复：连接失败时，设置标志，暂停后续重试
            if hasattr(mainWindow, '_webdriver_connection_failed'):
                mainWindow._webdriver_connection_failed = True
                mainWindow._webdriver_connection_failed_time = time.time()
            return False
        
        # 重新连接到调试端口（参考拼多多爬虫的方式）
        try:
            from xy_client.services.tools import tools
            selected_browser = mainWindow.getPreferredBrowser() if hasattr(mainWindow, 'getPreferredBrowser') else 'chrome'
            
            # 等待一下，确保浏览器进程稳定
            time.sleep(1)
            
            # 重新连接（添加连接失败检测）
            try:
                new_driver = tools.getDriver(selected_browser, port)
            except Exception as conn_e:
                error_str = str(conn_e).lower()
                # 检测连接失败错误（10061、连接被拒绝等）
                if '10061' in error_str or 'refused' in error_str or '积极拒绝' in error_str or '无法连接' in error_str:
                    if not silent:
                        print(f"❌ WebDriver连接失败（{conn_e}），暂停重试，避免创建多个窗口")
                    # 关键修复：连接失败时，设置标志，暂停后续重试
                    if hasattr(mainWindow, '_webdriver_connection_failed'):
                        mainWindow._webdriver_connection_failed = True
                        mainWindow._webdriver_connection_failed_time = time.time()
                    return False
                else:
                    # 其他错误，继续抛出
                    raise
            
            if new_driver:
                mainWindow.driver = new_driver
                if not silent:
                    print(f"✅ 浏览器重新连接成功 (端口: {port})")
                
                # 关键修复：连接成功后，清除连接失败标志
                if hasattr(mainWindow, '_webdriver_connection_failed'):
                    mainWindow._webdriver_connection_failed = False
                    if not silent:
                        print("✅ 已清除WebDriver连接失败标志")
                
                # 重新获取cookies（如果连接恢复）
                try:
                    cookies = new_driver.get_cookies()
                    if cookies:
                        # 转换为字符串格式，与loginA保持一致
                        cookies_str = ''
                        for cookie in cookies:
                            cookies_str += cookie.get('name', '') + '=' + cookie.get('value', '') + ';'
                        mainWindow.browser_cookies = cookies_str
                        if not silent:
                            print("✅ 已重新获取浏览器cookies")
                except Exception as cookie_e:
                    if not silent:
                        print(f"⚠️ 重新获取cookies失败: {cookie_e}")
                
                # 检查是否仍在登录页面
                try:
                    current_url = new_driver.current_url
                    if 'Login' in current_url or '登录' in current_url:
                        if not silent:
                            print("⚠️ 浏览器已重新连接，但在登录页面")
                        return False
                    else:
                        if not silent:
                            print("✅ 浏览器已重新连接，页面正常")
                        return True
                except:
                    return True
            else:
                if not silent:
                    print("❌ 浏览器重新连接失败（getDriver返回None）")
                # 关键修复：连接失败时，设置标志，暂停后续重试
                if hasattr(mainWindow, '_webdriver_connection_failed'):
                    mainWindow._webdriver_connection_failed = True
                    mainWindow._webdriver_connection_failed_time = time.time()
                return False
        except Exception as reconnect_e:
            error_str = str(reconnect_e).lower()
            # 检测连接失败错误（10061、连接被拒绝等）
            if '10061' in error_str or 'refused' in error_str or '积极拒绝' in error_str or '无法连接' in error_str:
                if not silent:
                    print(f"❌ WebDriver连接失败（{reconnect_e}），暂停重试，避免创建多个窗口")
                # 关键修复：连接失败时，设置标志，暂停后续重试
                if hasattr(mainWindow, '_webdriver_connection_failed'):
                    mainWindow._webdriver_connection_failed = True
                    mainWindow._webdriver_connection_failed_time = time.time()
            else:
                if not silent:
                    print(f"❌ 重新连接浏览器异常: {reconnect_e}")
                    import traceback
                    traceback.print_exc()
            return False
        
    except Exception as e:
        if not silent:
            print(f"❌ 重新连接浏览器异常: {e}")
            import traceback
            traceback.print_exc()
        return False


def betTasksTimer(mainWindow, direct=1, session=None):
    """
    下注任务（统一使用新架构）
    """
    try:
        from xy_client.services.Lucky5.betting.bet_task_manager import BetTaskManager
        # 复用同一管理器实例，避免每轮重置导致节流/心跳丢失
        if not hasattr(mainWindow, '_bet_manager') or mainWindow._bet_manager is None:
            mainWindow._bet_manager = BetTaskManager(mainWindow)
        manager = mainWindow._bet_manager
        result = manager.execute(direct=direct, session=session)
        return result
    except Exception as e:
        print(f"❌ [betTasksTimer] 执行异常: {e}")
        import traceback
        traceback.print_exc()
        # 异常后通过TimerManager重启定时器
        try:
            if hasattr(mainWindow, 'timer_manager'):
                mainWindow.timer_manager.start_bet_task_timer(interval=3, direct=direct, force_restart=True)
        except Exception:
            pass
        return False


# 下注任务 - 定时器
def cancelBet(mainWindow):
    """
    取消下注任务
    Args:
        mainWindow: 主窗口实例
    """
    try:
        if not mainWindow.browser_cookies:
            raise Exception('未登录状态,不能撤单任务...', 333333)

        print('mainWindow.is_test：', mainWindow.is_test)
        if mainWindow.is_test == 0:
            raise Exception('不是撤单开启状态')
            
        print('测试账号取消下注开始.......')
        
        # 查找表格中的行
        rows = mainWindow.driver.find_elements(By.CSS_SELECTOR, '#tbody tr')
        if not rows:
            print("无记录可撤单")
            return False
            
        # 检查是否有复选框
        has_checkbox = False
        for row in rows:
            try:
                if row.find_element(By.CSS_SELECTOR, 'input[type="checkbox"]'):
                    has_checkbox = True
                    break
            except Exception:
                continue
                
        if has_checkbox:
            try:
                 # raise Exception('无记录')
                # 1、全选
                mainWindow.driver.find_element(By.ID, "selectAll").click()
                # 2、退码
                mainWindow.driver.find_element(By.XPATH,
                                               '//*[@id="kuaida"]/div[2]/div/table/thead/tr[2]/td[7]/input').click()

                # 3、确定
                time.sleep(2)
                mainWindow.driver.find_element(By.XPATH, '//input[@type="button" and @value="确定"]').click()

                # 4、取消完成关闭弹框
                time.sleep(1)
                mainWindow.driver.find_element(By.XPATH, '//input[@type="button" and @value="确定"]').click()
            except Exception as e:
                print(f"撤单操作执行失败: {str(e)}")
                return False

    except Exception as e:
        print(f"取消下注任务异常: {str(e)}")
        if len(e.args) > 1:  # 安全地访问异常参数
            error_code = e.args[1] if isinstance(e.args, tuple) and len(e.args) > 1 else None
            if error_code == 333333:
                print("未登录状态，跳过撤单")
                return False
        raise

def cancelBetTimer(mainWindow, inc):
    """
    取消下注定时任务
    Args:
        mainWindow: 主窗口实例
        inc: 执行间隔(秒)
    """
    try:
        print("执行撤单任务...")
        cancelBet(mainWindow)
    except Exception as e:
        print(f"撤单任务异常: {str(e)}")

def auto_login_recovery(mainWindow, max_attempts=3):
    """
    自动登录恢复机制
    当检测到未登录状态时，自动尝试重新登录
    """
    try:
        print("🔄 开始自动登录恢复...")
        
        # 检查是否有必要的登录信息
        if not hasattr(mainWindow, 'user_info') or not mainWindow.user_info:
            print("❌ 缺少用户信息，无法自动登录")
            return False
        
        # 获取登录信息
        account = mainWindow.user_info.get('account', '')
        password = mainWindow.user_info.get('password', '')
        
        if not account or not password:
            print("❌ 缺少账号或密码信息")
            return False
        
        print(f"🔄 尝试自动登录账号: {account}")
        
        # 尝试重新登录
        for attempt in range(max_attempts):
            try:
                print(f"🔄 第 {attempt + 1} 次尝试自动登录...")
                
                # 关闭现有浏览器
                if hasattr(mainWindow, 'driver') and mainWindow.driver:
                    try:
                        mainWindow.driver.quit()
                        print("✅ 已关闭现有浏览器")
                    except:
                        pass
                
                # 重新登录
                login_result = loginA(account, password, mainWindow)
                
                if login_result and login_result.get('status') == 200:
                    print("✅ 自动登录成功")
                    return True
                else:
                    print(f"❌ 第 {attempt + 1} 次自动登录失败")
                    if attempt < max_attempts - 1:
                        print("🔄 等待10秒后重试...")
                        time.sleep(10)
                
            except Exception as e:
                print(f"❌ 第 {attempt + 1} 次自动登录异常: {e}")
                if attempt < max_attempts - 1:
                    print("🔄 等待10秒后重试...")
                    time.sleep(10)
        
        print("❌ 自动登录恢复失败，已达到最大尝试次数")
        return False
        
    except Exception as e:
        print(f"❌ 自动登录恢复过程中发生异常: {e}")
        return False

def monitor_login_status(mainWindow, check_interval=30):
    """
    登录状态监控函数
    定期检查登录状态，如果未登录则尝试恢复
    """
    try:
        if not hasattr(mainWindow, 'driver') or mainWindow.driver is None:
            print("⚠️ 浏览器驱动未初始化，跳过登录状态检查")
            return False
        
        # 检查登录状态
        if check_login_status(mainWindow.driver):
            print("✅ 登录状态正常")
            return True
        else:
            print("⚠️ 检测到未登录状态，尝试自动恢复...")
            return auto_login_recovery(mainWindow)
            
    except Exception as e:
        print(f"❌ 登录状态监控异常: {e}")
        return False
