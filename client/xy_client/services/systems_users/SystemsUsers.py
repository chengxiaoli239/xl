# encoding: utf-8
# module systems_users
# from (built-in)
# by generator 1.147
import datetime
import os
import re
import threading
import time
import traceback
from urllib import parse

from selenium.common.exceptions import NoSuchWindowException, NoSuchElementException, TimeoutException
from selenium.webdriver.support.wait import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

from xy_client.services.systems_users.LogService import p

import json
from base64 import b64encode

import requests
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.wait import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from xy_client.services.tools import tools

# display = Display(visible=0, size=(800, 800))
# display.start()
import configparser

from .common import getAccountByToken, pushErrorLog
from ..tools.Configs import Configs
from ..tools.GlobalSession import GlobalSession

from selenium import webdriver
from xy_client.services.MyThreading import MyThreadingTimer
from xy_client.models import users

# 推送缓存类，避免重复推送开奖数据
class PushCache:
    def __init__(self):
        self.success_cache = {}  # 成功推送的缓存
        self.failed_cache = {}   # 失败推送的缓存
        self.last_push_time = {} # 最后推送时间
        self.min_interval = 8    # 最小推送间隔(秒)
        self.lock = threading.RLock()
    
    def is_successful(self, qihao):
        """检查是否已经成功推送过"""
        return qihao in self.success_cache
    
    def can_push(self, qihao):
        """检查是否可以推送（包含时间间隔控制）"""
        current_time = time.time()
        last_time = self.last_push_time.get(qihao, 0)
        
        if current_time - last_time >= self.min_interval:
            self.last_push_time[qihao] = current_time
            # 清理旧数据
            self._cleanup()
            return True
        return False
    
    def mark_success(self, qihao, kj_data):
        """标记推送成功"""
        with self.lock:
            self.success_cache[qihao] = {
                'kj_data': kj_data,
                'timestamp': time.time()
            }
    
    def mark_failed(self, qihao, error_code):
        """标记推送失败"""
        with self.lock:
            self.failed_cache[qihao] = {
                'error_code': error_code,
                'timestamp': time.time()
            }
    
    def _cleanup(self):
        """清理旧数据"""
        current_time = time.time()
        # 清理超过1小时的缓存
        self.success_cache = {
            k: v for k, v in self.success_cache.items() 
            if current_time - v['timestamp'] < 3600
        }
        self.failed_cache = {
            k: v for k, v in self.failed_cache.items() 
            if current_time - v['timestamp'] < 3600
        }

# 创建全局推送缓存实例和锁
_global_push_cache = PushCache()
_push_cache_lock = _global_push_cache.lock

# 添加通用的网络请求处理函数
def safe_request(method='POST', url='', data=None, headers=None, timeout=(5, 15), max_retries=2, api_type=None):
    """
    安全的网络请求函数，使用连接稳定性管理器处理10054错误
    
    Args:
        method: 请求方法 ('GET', 'POST', etc.)
        url: 请求URL
        data: 请求数据
        headers: 请求头
        timeout: 超时设置 (连接超时, 读取超时)
        max_retries: 最大重试次数
        api_type: API类型，用于获取特定的超时和重试配置
    
    Returns:
        dict: 响应数据或错误信息
    """
    try:
        # 使用新的连接稳定性管理器
        from ..tools.ConnectionStabilityManager import safe_request as stable_request
        
        # 准备请求参数
        request_kwargs = {
            'timeout': timeout,
            'headers': headers or {}
        }
        
        if data:
            if method.upper() == 'POST':
                request_kwargs['data'] = data
            else:
                request_kwargs['params'] = data
        
        # 使用连接稳定性管理器执行请求
        response = stable_request(method, url, **request_kwargs)
        
        if response:
            try:
                return response.json()
            except:
                return {'status': 200, 'data': response.text, 'raw_response': True}
        else:
            return {'status': 300, 'data': {}, 'error': 'connection_failed', 'url': url}
            
    except ImportError:
        print("⚠️ 连接稳定性管理器不可用，使用备用方案")
        # 备用方案：使用原有的GlobalSession
        return _fallback_safe_request(method, url, data, headers, timeout, max_retries, api_type)
    except Exception as e:
        print(f"❌ 连接稳定性管理器异常: {e}")
        # 异常时使用备用方案
        return _fallback_safe_request(method, url, data, headers, timeout, max_retries, api_type)


def _fallback_safe_request(method='POST', url='', data=None, headers=None, timeout=(5, 15), max_retries=2, api_type=None):
    """备用安全请求函数"""
    # 导入网络配置
    try:
        from .network_config import get_timeout_for_api, get_retry_config_for_api, ERROR_HANDLING_CONFIG
    except ImportError:
        pass
    
    # 如果指定了API类型，使用对应的配置
    if api_type:
        try:
            timeout = get_timeout_for_api(api_type, timeout)
            retry_config = get_retry_config_for_api(api_type)
            max_retries = retry_config['max_retries']
            retry_delay = retry_config['retry_delay']
            connection_retry_delay = retry_config['connection_retry_delay']
        except:
            retry_delay = 1
            connection_retry_delay = 2
    else:
        retry_delay = 1
        connection_retry_delay = 2
    
    # 检查熔断器状态
    if GlobalSession.should_backoff():
        backoff_delay = GlobalSession.get_backoff_delay()
        print(f'🚨 熔断器激活，退避 {backoff_delay:.1f} 秒: {url}')
        time.sleep(backoff_delay)
    
    for attempt in range(max_retries + 1):
        try:
            if method.upper() == 'POST':
                response = globalSession.post(url, data=data, headers=headers, timeout=timeout)
            elif method.upper() == 'GET':
                response = globalSession.get(url, headers=headers, timeout=timeout)
            else:
                response = globalSession.request(method, url, data=data, headers=headers, timeout=timeout)
            
            # 请求成功，记录成功并返回数据
            GlobalSession.record_success()
            return response.json()
            
        except requests.exceptions.Timeout as e:
            print(f'请求超时 (尝试 {attempt + 1}/{max_retries + 1}): {url} - {e}')
            GlobalSession.record_error('timeout')
            if attempt == max_retries:
                try:
                    if ERROR_HANDLING_CONFIG.get('RESET_SESSION_ON_TIMEOUT', True):
                        GlobalSession.reset_session()
                except:
                    GlobalSession.reset_session()
                return {'status': 300, 'data': {}, 'error': 'timeout', 'url': url}
            time.sleep(retry_delay)
            
        except requests.exceptions.ConnectionError as e:
            print(f'连接错误 (尝试 {attempt + 1}/{max_retries + 1}): {url} - {e}')
            GlobalSession.record_error('connection')
            
            # 特殊处理连接重置错误10054
            if "10054" in str(e) or "ConnectionResetError" in str(e):
                print(f'🔄 检测到连接重置错误，增加等待时间...')
                import random
                extended_delay = connection_retry_delay + random.uniform(1, 3)
                time.sleep(extended_delay)
            else:
                time.sleep(connection_retry_delay)
            
            if attempt == max_retries:
                return {'status': 300, 'data': {}, 'error': 'connection_error', 'url': url}
            
        except Exception as e:
            print(f'请求异常 (尝试 {attempt + 1}/{max_retries + 1}): {url} - {e}')
            GlobalSession.record_error('general')
            if attempt == max_retries:
                return {'status': 300, 'data': {}, 'error': str(e), 'url': url}
            time.sleep(retry_delay)
    
    return {'status': 300, 'data': {}, 'error': 'max_retries_exceeded', 'url': url}

# 安全的页面刷新工具函数（增强版：支持超时控制和自动恢复）
def safe_refresh_page(mainWindow, reason="页面刷新", timeout=15, max_retry=2):
    """
    安全地刷新页面，包含WebDriver连接状态检查、超时控制和自动恢复
    
    Args:
        mainWindow: 主窗口实例
        reason: 刷新原因描述
        timeout: 页面加载超时时间（秒），默认15秒
        max_retry: 最大重试次数，默认2次（避免频繁刷新影响盘口）
    
    Returns:
        bool: 刷新是否成功
    """
    try:
        # 检查WebDriver连接是否有效
        if not (hasattr(mainWindow, 'driver') and mainWindow.driver and 
                hasattr(mainWindow.driver, 'current_url')):
            print(f"⚠️ WebDriver未初始化，跳过{reason}")
            return False
        
        # 尝试获取当前URL来验证连接
        try:
            current_url = mainWindow.driver.current_url
            if not current_url or 'http' not in current_url:
                print(f"⚠️ WebDriver连接异常，跳过{reason}")
                return False
        except Exception as url_error:
            print(f"⚠️ 无法获取当前URL，WebDriver连接可能已断开: {url_error}")
            return False
        
        # 使用页面刷新管理器执行刷新
        try:
            from xy_client.services.tools.PageRefreshManager import get_refresh_manager
            refresh_manager = get_refresh_manager(page_load_timeout=timeout, max_retry=max_retry)
            return refresh_manager.safe_refresh(mainWindow.driver, reason=reason, 
                                               check_loading=True, timeout=timeout)
        except ImportError:
            # 如果导入失败，使用原有的简单刷新方式
            print(f"⚠️ 页面刷新管理器不可用，使用简单刷新方式")
            print(f"🔄 {reason}...")
            mainWindow.driver.refresh()
            print(f"✅ {reason}成功")
            return True
        
    except Exception as refresh_error:
        print(f"⚠️ {reason}失败: {refresh_error}")
        # 刷新失败不影响主流程，继续执行
        return False

auto_login_times = 0
globalSession = GlobalSession().get_session()
config = Configs()
robot_domain = config.get_config('robot_domain')
lottery_type = int(config.get_config('lottery_type'))
access_token = config.get_config('access_token')


# 为所有剩余的接口添加超时保护
def syncBalance():
    url = robot_domain + '/api/tz-system-users/syn-balance'
    post_data = {'access_token': access_token}

    headers = {'content-type': 'application/json'}

    # 使用安全的网络请求函数，包含超时和重试机制
    data = safe_request(
        method='POST',
        url=url,
        data=json.dumps(post_data),
        headers=headers,
        api_type='balance'  # 使用余额接口的特定配置
    )
    
    return data


def isLogin():
    # 是否是登陆状态
    flag = False
    data = syncBalance()
    balance = float(data['balance'])
    if balance > 0.00:
        flag = True
    return flag, data


def getHeaderData():
    url = robot_domain + '/api/tz-system-users/get-cookies'

    headers = {'content-type': 'application/json'}
    post_data = {'access_token': access_token}

    headerData = {}
    # 使用安全的网络请求函数，包含超时和重试机制
    data = safe_request(
        method='POST',
        url=url,
        data=json.dumps(post_data),
        headers=headers,
        api_type='user_info'  # 使用用户信息接口的特定配置
    )
    
    if data.get('status') == 200:
        headerData = data.get('data', {})
    else:
        print(f'getHeaderData_err: {data.get("error", "未知错误")}')

    return headerData


exec_count = 0


# 定时器 - 示例
def heart_beat():
    global exec_count
    exec_count += 1
    # 15秒后停止定时器
    if exec_count < 15:
        threading.Timer(10, heart_beat).start()  # 每隔x秒执行一次


def actLogin(access_token='', ssc_domain='', account='', pwd=''):
    # ssc_domain = self.ui.domain_val.text()
    # username = self.ui.username_val.text()
    # access_token = self.ui.token_val.text()
    # pwd = self.ui.pwd_val.text()

    account_status = 1
    if account_status == 0:
        return {'status': 300, 'msg': '账号禁用状态'}  # 弹框
    else:
        # ssc_domain = 'http://f1.wg7s5297.xyz'
        # username = 'Qxe121312'
        # pwd = 'Ws112233'

        loginRst = loginA(account, pwd, ssc_domain, access_token)  # 谷歌
        # loginRst = loginB(account, pwd, ssc_domain, access_token) # 火狐

        if loginRst['status'] == 200:
            print('✅ 登录成功！域名:' + ssc_domain + '，账号：' + account + '，access_token:' + access_token)

        return loginRst


# A1、谷歌浏览器 - 正常
def loginA(account='', pwd='', ssc_domain='', access_token=''):
    account = account.strip()
    pwd = pwd.strip()
    now_time = str(int(float(time.time()) * 1000))
    # 实例化一个启动参数对象
    chrome_options = Options()
    # 无界面运行(无窗口)
    chrome_options.add_argument('--headless')

    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    # chrome_options.add_argument("window-size=1000,800")
    # chrome_options.add_argument("--proxy-server=http://150.255.20.122:21621")
    chrome_options.add_argument('blink-settings=imagesEnabled=false')  # 不加载图片, 提升速度
    # chrome_options.add_experimental_option("detach", True)  # 不关闭浏览器

    prefs = {'profile.managed_default_content_settings.images': 2}
    chrome_options.add_experimental_option('prefs', prefs)
    WebDriverWait(webdriver, 20)  # 设置等待时间20秒

    # 忽略证书错误
    chrome_options.add_argument('--ignore-certificate-errors')
    chrome_options.add_argument('--ignore-ssl-errors')

    ##加入这个防止ubuntu服务器打开网页403
    user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.71 Safari/537.36'
    chrome_options.add_argument('user-agent=%s' % user_agent)
    chrome_options.add_argument("--start-maximized")
    proxy = getProxyIp()

    '''
    proxyauth_plugin_path = create_proxyauth_extension(
        proxy_host=proxy['ip'],
        proxy_port=proxy['port'],
        proxy_username=proxy['username'],
        proxy_password=proxy['password']
    )
    print('proxyauth_plugin_path:', proxyauth_plugin_path)
    '''

    proxies = {
        "http": "http://%(user)s:%(pwd)s@%(proxy)s/" % {"user": proxy['username'], "pwd": proxy['password'],
                                                        "proxy": proxy['data']},
        "https": "http://%(user)s:%(pwd)s@%(proxy)s/" % {"user": proxy['username'], "pwd": proxy['password'],
                                                         "proxy": proxy['data']}
    }
    # chrome_options.add_argument("--disable-extensions")
    # chrome_options.add_extension(proxyauth_plugin_path)  # 代理设置
    # chrome_options.add_argument('--proxy-server=http://' + proxy['username'] + ':' + proxy['password'] + proxy['data'])

    # browser_path = os.path.join(os.getcwd(), 'chromedriver')
    # browser_path = '/usr/bin/chromedriver'
    # print('browser_path', browser_path)

    # 启动浏览器
    # browser = webdriver.Firefox(options=chrome_options)
    browser = webdriver.Chrome(chrome_options=chrome_options)
    # 请求百度首页
    url = ssc_domain + '/Member/Login?_=' + now_time
    # url = 'http://www.baidu.com'
    browser.get(url)
    time.sleep(10)

    input = browser.find_element(By.ID, "Account")
    # input.send_keys('Qxe121312')
    input.send_keys(account)
    time.sleep(1)
    # 密码
    input2 = browser.find_element(By.ID, "Password")
    # input.send_keys('As112233')
    input2.send_keys(pwd)

    # browser.get_screenshot_as_file("./登陆成功后页面" + str(random()) + ".png")
    # print('=============截图完成==================')

    input_icon = browser.find_elements(By.CLASS_NAME, 'hdd')
    input_icon[0].click()

    # flag = isElementExist(browser, 'btn-submit')
    # print('btn-submit', flag)
    input3 = browser.find_element(By.ID, 'btn-submit')
    # input3.send_keys(Keys.ENTER)
    input3.click()
    time.sleep(3)

    # flag = isElementExist(browser, 'agree')
    # print('agress：', flag)
    input4 = browser.find_element(By.ID, "agree")
    input4.click()

    cookies = browser.get_cookies()
    url = robot_domain + '/api/index/update-user-cookies'
    post_data = {'url': url, 'account': account, 'password': pwd, 'ssc_domain': ssc_domain, 'cookies': cookies,
                 'access_token': access_token}

    headers = {'content-type': 'application/json'}

    rst = globalSession.post(url, data=json.dumps(post_data), headers=headers)
    time.sleep(3)

    rstData = rst.json()

    WebDriverWait(browser, 5).until(
        EC.presence_of_element_located((By.XPATH, '//*[@id="CreditBalance"]'))
    )
    balance = browser.find_element(By.ID, "CreditBalance").get_attribute('textContent')
    rstData['balance'] = balance

    return rstData


def getHttpStatus(browser):
    for responseReceived in browser.get_log('performance'):
        try:
            response = json.loads(responseReceived[u'message'])[u'message'][u'params'][u'response']
            if response[u'url'] == browser.current_url:
                return response[u'status'], response[u'statusText']
        except:
            pass
    return None


# 获取用户信息数据
def getUserData(mainWindow=None, cookies_str=None):
    global headerData
    try:
        headerData = getHeaderData()
        if not headerData.get('cookies'):
            raise Exception('cookies为空')
        if cookies_str is None:
            driver = getattr(mainWindow, 'driver', None)
            if driver is not None:
                cookies_str = getCookiesStr(driver.get_cookies())
            else:
                cookies_str = getattr(mainWindow, 'browser_cookies', None)
        if not cookies_str:
            cookies_str = headerData.get('cookies', '')
        if isinstance(cookies_str, list):
            cookies_str = getCookiesStr(cookies_str)
        if not cookies_str:
            raise Exception('cookies为空')

        now_time = str(int(float(time.time()) * 1000))

        v1 = headerData['v1']  # 浏览器版本号
        v2 = headerData['v2']  # 浏览器版本号
        headers = {
            'Accept': 'application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding': 'gzip, deflate, br',
            'Accept-Language': 'zh-CN,zh;q=0.9',
            'Connection': 'close',
            'Cookie': cookies_str,
            'Referer': headerData['Referer'] + now_time,
            'sec-ch-ua': '"Chromium";v="' + v2 + '", " Not A;Brand";v="' + v1 + '", "Google Chrome";v="' + v2 + '"',
            # 'sec-ch-ua-mobile': '?0',
            # 'sec-ch-ua-platform': '"Windows"',
            # 'Sec-Fetch-Dest': 'empty',
            # 'Sec-Fetch-Mode': 'cors',
            # 'Sec-Fetch-Site': 'same-origin',
            'Host': headerData['Host'],
            'User-Agent': headerData['user_agent'],
            'X-Requested-With': 'XMLHttpRequest',
        }
        url = mainWindow.wp_domain + '/Member/GetMemberPrint?_=' + now_time
        rst = globalSession.get(url, headers=headers, timeout=15)
        rst.encoding = rst.apparent_encoding  # 乱码问题
        #print('sync rst:', rst.text)
    except Exception as e:
        print('e.args 获取余额、开奖数据异常', e.args)
        return None, headerData, e.args[0]

    return rst, headerData, '获取成功'


# 余额同步操作
def actSyncBalance(mainWindow, cookies_str=None):
    try:
        rst, headerData, err_msg = getUserData(mainWindow)
        if rst is None:
            return '开奖数据异常None'
        if isinstance(rst.text, str) and ('robot7=' in rst.text):
            new_matches = re.search(r'robot7=(.*?);', rst.text, re.M | re.I)
            old_matches = re.search(r'robot7=(.*?);', headerData['cookies'] + ';', re.M | re.I)

            if new_matches and old_matches:
                new_robot7 = new_matches.group(1)  # 新robot7
                old_robot7 = old_matches.group(1)  # 旧robot7

                old_cookies = headerData['cookies']
                headerData['cookies'] = old_cookies.replace(old_robot7, new_robot7)  # 替换robot7
                updateNewRobot7(new_robot7, old_robot7)

                time.sleep(5)
                # return actSyncBalance(obj, access_token)
                return 'update_new_robot7'

        rst = json.loads(rst.text)
        # actRst = syncBalance(access_token)
        if rst['Status'] == 1:
            rstData = rst['Data']
            # mainWindow.ms.text_print.emit(mainWindow.ui.tip_msg, "账号：" + rstData['member_account'] + ' 余额同步成功，余额：' + rstData['credit_balance'])
            # mainWindow.ui.balance.setText(rstData['credit_balance'])
            # pushActiveQihao(rstData['period_no'], access_token, obj.lottery_type)
            return True
            # self.close()
        else:
            # mainWindow.ms.text_print.emit(mainWindow.ui.tip_msg, rst['msg'])
            return False

    except Exception as e:
        print('e::::::', e.args, access_token)
        # QMessageBox.about(self.ui, '登陆结果：', 'xxxxxx')  # 弹框
        mainWindow.ms.text_print.emit('同步余额异常1：' + mainWindow.wp_account, e.args[0] + '[' + access_token + ']')
        pushErrorLog('同步余额异常2：' + mainWindow.current_qihao, access_token, lottery_type, e.args)
        return e.args


def getProxyIp():
    url = robot_domain + '/api/index/get-proxy-ip'
    post_data = {}

    headers = {'content-type': 'application/json'}

    rst = globalSession.post(url, data=json.dumps(post_data), headers=headers)
    data = rst.json()
    # print(data)
    now_time = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())
    # print(now_time)

    proxyData = data.split(':')
    # print('proxyData:', proxyData[0])

    rstData = {'data': data, 'ip': proxyData[0], 'port': proxyData[1], 'username': '379879537', 'password': '14wmcx7y'}

    return rstData


# 获取激活的计划id
def getBetPlanTasks(access_token='', current_qihao='', direct=1):
    data = {'status': 200, 'data': {}}
    try:
        url = robot_domain + '/api/tz-system-users/get-active-plan-tasks'
        post_data = {'access_token': access_token, 'current_qihao': current_qihao, 'direct': direct, 'lottery_type': lottery_type}

        headers = {'content-type': 'application/json'}

        # 使用安全的网络请求函数，包含超时和重试机制
        # 使用 bet_plan 类型，超时时间更短（2秒连接，3秒读取），确保快速响应
        data = safe_request(
            method='POST',
            url=url,
            data=json.dumps(post_data),
            headers=headers,
            api_type='bet_plan'  # 使用获取计划接口的特定配置（超时更短）
        )
        
        # 如果请求失败，记录错误信息
        if 'error' in data:
            print(f'getBetPlanTasks_err: {data["error"]} - URL: {data.get("url", url)}')
    except requests.exceptions.RequestException as req_err:
        print('Request Exception:', str(req_err))
        return {'status': 500, 'error': str(req_err)}
    except Exception as e:
        print('获取计划异常', str(e))
        return {'status': 500, 'error': str(e)}

    return data


def getCodesByPlanIds(plan_ids, access_token):
    data = {}
    try:
        url = robot_domain + '/api/tz-system-users/get-codes-by-plan-ids'
        post_data = {'access_token': access_token, 'plan_ids': plan_ids}

        headers = {'content-type': 'application/json'}

        # 使用安全的网络请求函数，包含超时和重试机制
        data = safe_request(
            method='POST',
            url=url,
            data=json.dumps(post_data),
            headers=headers,
            api_type='bet'  # 使用下注接口的特定配置
        )
        
        p('getCodesByPlanIds:', str(data))
    except Exception as e:
        print(f'getCodesByPlanIds_err: {e}')
        data = {'status': 300, 'error': str(e)}

    return data


def pushActiveQihao(qihao='', access_token='', lottery_type=8, activeQihaoData={}):
    data = {}
    try:
        url = robot_domain + '/api/tz-system-users/push-active-qihao'
        post_data = {'access_token': access_token, 'qihao': qihao, 'activeQihaoData': activeQihaoData,
                     'lottery_type': lottery_type}

        headers = {'content-type': 'application/json'}

        # 使用安全的网络请求函数，包含超时和重试机制
        data = safe_request(
            method='POST',
            url=url,
            data=json.dumps(post_data),
            headers=headers,
            api_type='qihao'  # 使用期号接口的特定配置
        )
        
    except Exception as e:
        print(f'pushActiveQihao_err: {e}')
        data = {'status': 300, 'error': str(e)}

    return data


def pushSyncBalance(access_token='', lottery_type=8, balance=0.00):
    data = {}
    try:
        url = robot_domain + '/api/tz-system-users/push-sync-balance'
        post_data = {'access_token': access_token, 'balance': balance,
                     'lottery_type': lottery_type}

        headers = {'content-type': 'application/json'}

        # 使用安全的网络请求函数，包含超时和重试机制
        data = safe_request(
            method='POST',
            url=url,
            data=json.dumps(post_data),
            headers=headers,
            api_type='balance'  # 使用余额接口的特定配置
        )
        
        # 如果请求失败，记录错误信息
        if 'error' in data:
            print(f'pushSyncBalance_err: {data["error"]} - URL: {data.get("url", url)}')
            
    except Exception as e:
        print(f'pushSyncBalance_err: {e}')
        data = {'status': 300, 'data': {}, 'error': str(e)}

    return data


# 获取激活的期号
def getRotActiveQiHao(access_token='', lottery_type=8):
    try:
        data = {'status': 300, 'data': {}}
        url = robot_domain + '/api/tz-system-users/get-active-qihao'
        post_data = {'access_token': access_token, 'lottery_type': lottery_type}

        headers = {'content-type': 'application/json'}

        # 使用安全的网络请求函数，包含超时和重试机制
        data = safe_request(
            method='POST',
            url=url,
            data=json.dumps(post_data),
            headers=headers,
            api_type='qihao'  # 使用期号接口的特定配置
        )
        
        # 如果请求失败，记录错误信息
        if 'error' in data:
            print(f'getRotActiveQiHao_err: {data["error"]} - URL: {data.get("url", url)}')
            
    except Exception as e:
        print('getRotActiveQiHao_err', e.args)
        data = {'status': 300, 'data': {}, 'error': str(e)}

    return data


# 推送开奖号码
def pushSyncKjData(access_token='', kj_datas=None, lottery_type=8, fromId='api'):
    if kj_datas is None:
        kj_datas = {}
    try:
        url = robot_domain + '/api/tz-system-users/push-sync-kj-datas'
        post_data = {'access_token': access_token, 'lottery_type': lottery_type, 'kj_datas': kj_datas, 'from': fromId}

        headers = {'content-type': 'application/json'}

        # 使用安全的网络请求函数，包含超时和重试机制
        data = safe_request(
            method='POST',
            url=url,
            data=json.dumps(post_data),
            headers=headers,
            api_type='kj_data'  # 使用开奖数据接口的特定配置
        )
        
    except Exception as e:
        print(f'pushSyncKjData_err: {e}')
        data = {'status': 300, 'error': str(e)}

    return data


# 推送错误日志
def getGfCode():
    try:

        now_timestamp = str(int(float(time.time()) * 1000))
        url = 'https://web01.cc138008.com/kaijiang/ygxy5.json?v=' + now_timestamp

        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/112.0',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Encoding': 'gzip, deflate, br',
            'Accept-Language': 'zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2',
            'Connection': 'keep-alive',
            'Host': 'web01.cc138008.com',
            'If-Modified-Since': 'Mon, 08 May 2023 14:50:15 GMT',
            'If-None-Match': 'W/"64590c27-209"',
            'Sec-Fetch-Dest': 'document',
            'Sec-Fetch-Mode': 'navigate',
            'Sec-Fetch-Site': 'none',
            'Sec-Fetch-User': '?1',
            'Upgrade-Insecure-Requests': '1',
        }

        # 使用安全的网络请求函数，包含超时和重试机制
        data = safe_request(
            method='GET',
            url=url,
            headers=headers,
            api_type='external'  # 使用外部接口的特定配置
        )
        
        if data.get('status') == 200:
            return data.get('data')
        else:
            return None

    except Exception as e:
        print('eeeGetGfCode：', e.args)
        return None


def updateNewRobot7(new_robot7='', old_robot7=''):
    try:
        url = robot_domain + '/api/tz-system-users/update-new-robot7'
        post_data = {'access_token': access_token, 'new_robot7': new_robot7, 'old_robot7': old_robot7}

        headers = {'content-type': 'application/json'}

        # 使用安全的网络请求函数，包含超时和重试机制
        data = safe_request(
            method='POST',
            url=url,
            data=json.dumps(post_data),
            headers=headers,
            api_type='user_info'  # 使用用户信息接口的特定配置
        )
        
    except Exception as e:
        print(f'updateNewRobot7_err: {e}')
        data = {'status': 300, 'error': str(e)}

    return data


def pushTasksBetRst(plan_id, qihao, betRst):
    try:
        data = {}
        url = robot_domain + '/api/tz-system-users/push-tasks-bet-rst'
        post_data = {'access_token': access_token, 'plan_id': plan_id, 'qihao': qihao, 'betRst': betRst,
                     'lottery_type': lottery_type}

        headers = {'content-type': 'application/json'}

        # 使用安全的网络请求函数，包含超时和重试机制
        data = safe_request(
            method='POST',
            url=url,
            data=json.dumps(post_data),
            headers=headers,
            api_type='bet'  # 使用下注接口的特定配置
        )
        
        p('更新计划下注结果:', plan_id, qihao, str(data))
        
    except Exception as e:
        print(f'pushTasksBetRst_err: {e}')
        data = {'status': 300, 'error': str(e)}

    return data


def pushTasksBetRstNotice(order_id, qihao, access_token):
    try:
        data = {}
        url = robot_domain + '/api/tz-system-users/push-tasks-bet-rst-notice'
        post_data = {'access_token': access_token, 'order_id': order_id, 'qihao': qihao, 'lottery_type': lottery_type}

        headers = {'content-type': 'application/json'}

        # 使用安全的网络请求函数，包含超时和重试机制
        data = safe_request(
            method='POST',
            url=url,
            data=json.dumps(post_data),
            headers=headers,
            api_type='bet'  # 使用下注接口的特定配置
        )
        
        p('pushTasksBetRstNotice:', str(data))
        
    except Exception as e:
        print(f'pushTasksBetRstNotice_err: {e}')
        data = {'status': 300, 'error': str(e)}

    return data


# 更新客户端robot_id
def updateRobotId(mainWindow, err_msg=''):
    try:
        url = robot_domain + '/api/tz-system-users/update-robot-id'
        post_data = {'access_token': access_token, 'err_msg': err_msg, 'lottery_type': lottery_type}

        headers = {'content-type': 'application/json'}

        # 使用安全的网络请求函数，包含超时和重试机制
        data = safe_request(
            method='POST',
            url=url,
            data=json.dumps(post_data),
            headers=headers,
            api_type='user_info'  # 使用用户信息接口的特定配置
        )
        
        p('updateRobotId:', str(data))
        
        # 如果获取到新的robot_id，需要重新登录
        if 'new_robot_id' in data and data['new_robot_id']:
            print("🔄 检测到Robot ID更新，需要重新登录")
            
            # 更新全局登录状态为False
            if hasattr(mainWindow, 'updateLoginStatus'):
                mainWindow.updateLoginStatus(False)
            else:
                # 如果没有updateLoginStatus方法，直接设置属性
                mainWindow.login_status = False
                print("⚠️ 未找到updateLoginStatus方法，直接设置login_status为False")
            
            # 设置新的robot_id到browser_cookies
            mainWindow.browser_cookies = data['new_robot_id']
            
            # 添加cookie到浏览器
            robotIdCookie = {'name': 'robot7', 'value': data['new_robot_id'].lstrip('robot7=')}
            if hasattr(mainWindow, 'driver') and mainWindow.driver:
                try:
                    mainWindow.driver.add_cookie(robotIdCookie)
                    print("✅ 新的Robot ID已添加到浏览器cookies")
                except Exception as e:
                    print(f"⚠️ 添加Robot ID到浏览器cookies失败: {e}")
            
            # 重启浏览器进行登录
            if hasattr(mainWindow, 'restartBrowserForLogin'):
                print("🔄 调用重启浏览器方法...")
                mainWindow.restartBrowserForLogin()
            else:
                print("⚠️ 未找到restartBrowserForLogin方法，请手动重启浏览器")
            
            print("🔄 Robot ID更新完成，程序将重新登录")
        
        return data
    except Exception as e:
        p(f'updateRobotId异常: {str(e)}')
        # 返回一个默认的错误响应
        return {'new_robot_id': '', 'status': 'error', 'message': str(e)}


def getCookiesStr(cookiesDatas):
    try:
        cookie_string = '; '.join([f"{cookie['name']}={cookie['value']}" for cookie in cookiesDatas])
    except Exception as e:
        return ''
    return cookie_string


# 同步余额 - 定时器
def syncBalanceTimer(inc, mainWindow):
    try:
        access_token = mainWindow.access_token
        # print('mainWindow.browser_cookiesxxxxxxxxxxxxxxxxxxxxxxxxxx', mainWindow.browser_cookies)
        if mainWindow.browser_cookies and mainWindow.browser_cookies is not None:
            credit_balance = mainWindow.driver.find_element(By.ID, "CreditBalance").text
            member_account = mainWindow.driver.find_element(By.XPATH, '//*[@id="myinfo"]/tbody/tr[1]/td[2]').text
            current_qihao = mainWindow.driver.find_element(By.ID, "PreviousPeriodNo").text

            uInfo = "账号：" + member_account + ' 余额同步成功，余额：' + str(credit_balance) + ' 当前期号：' + str(
                current_qihao)
            try:
                # mainWindow.ms.text_print.emit(mainWindow.ui.tip_msg, uInfo)
                # mainWindow.ui.balance.setText(credit_balance)
                # cookies_str = getCookiesStr(mainWindow.browser.get_cookies())
                # syncBalanceRst = actSyncBalance(mainWindow, access_token, cookies_str)
                # print('syncBalanceRst:', syncBalanceRst)

                pushSyncBalance(access_token, lottery_type, credit_balance)
            except Exception as e:
                p('x2', str(e.args))
        else:
            print('未登陆成功，不能获取余额')
    except Exception as e1:
        try:
            pushErrorLog('同步余额异常3：' + str(mainWindow.current_qihao), mainWindow.access_token, lottery_type,
                         e1.args)
        except Exception as e2:
            # print('warning:', e2.args)
            pass
    # 统一定时器调用：参数需为元组
    MyThreadingTimer.myTimer(inc, syncBalanceTimer, (inc, mainWindow))


# 获取正在游戏期号 - 定时器
def getNowBetQihaoTimer(inc, mainWindow):
    try:
        nextQiHaoRst = getRotActiveQiHao(access_token, lottery_type)
        if nextQiHaoRst['status'] != 200 or 'data' not in nextQiHaoRst or 'next_qihao' not in nextQiHaoRst['data']:
            raise ValueError('期号错误', 40001)
        next_qihao = nextQiHaoRst['data']['next_qihao']
        mainWindow.current_qihao = next_qihao
        update_period_number(mainWindow, next_qihao)

    except Exception as e:
        try:
            if not e.args[1] or e.args[1] > 40000:
                pushErrorLog('机器人网页获取期号异常：', access_token, lottery_type, e.args)
        except Exception as e2:
            #print('eeee22222', str(e2))
            pass

def update_period_number(self, period_no):
    """Update the period number field with current period"""
    self.period_val.setText(period_no)

# 获取已经开奖的号码 - 定时器
def getNowKjDataTimer(mainWindow):
    """
    从API接口获取开奖数据（优化版：根据时间段采集）
    参考PHP代码 LotteryBet.php 的逻辑，只在特定时间段请求接口
    """
    try:
        # 优化：根据时间段判断是否需要采集
        try:
            from xy_client.services.Lucky5.utils.lottery_time_helper import LotteryTimeHelper
            # 获取当前彩种类型（默认8=幸运五）
            current_lottery_type = int(config.get_config('lottery_type', 'system_configs') or 8)
            
            # 判断当前时间是否可以抓取开奖号码
            is_draw_time = LotteryTimeHelper.is_draw_time(current_lottery_type)
            if not is_draw_time:
                # 不在抓取时间段，添加调试日志（每5分钟输出一次，避免日志过多）
                import datetime
                now = datetime.datetime.now()
                # 只在每分钟的第0秒输出一次日志，避免日志过多
                if now.second < 5:
                    time_info = LotteryTimeHelper.get_time_info(current_lottery_type)
                    print(f"⏰ [开奖采集] 当前不在抓取时间段（{time_info.get('current_time', 'N/A')}），等待开奖时间窗口...")
                    print(f"   下次抓取时间: {time_info.get('draw_start', 'N/A')}")
                return
            else:
                # 在抓取时间段，输出调试信息（每30秒输出一次）
                import datetime
                now = datetime.datetime.now()
                if now.second % 30 == 0:  # 每30秒输出一次
                    print(f"✅ [开奖采集] 当前在抓取时间段，开始采集开奖号码...")
        except Exception as time_check_e:
            # 如果时间段判断失败，继续执行（保持原有逻辑）
            print(f"⚠️ [开奖采集] 时间段判断异常: {time_check_e}，继续执行采集")
            pass
        
        driver = getattr(mainWindow, 'driver', None)
        cookies_str = None
        if driver is not None:
            try:
                from xy_client.services.Lucky5.Lucky import check_login_status
                if not check_login_status(driver):
                    print("⚠️ 未登录状态，跳过开奖数据获取")
                    return
            except ImportError:
                pass

            try:
                current_url = driver.current_url
                if 'Login' in current_url or '登录' in current_url:
                    print("⚠️ 当前在登录页面，未登录")
                    return
                page_title = driver.title
                if '登录' in page_title or 'Login' in page_title:
                    print("⚠️ 页面标题显示为登录页面，未登录")
                    return
                isHasLoginCurrentUrl(parse.unquote(current_url))
                cookies_str = getCookiesStr(driver.get_cookies())
            except Exception as e:
                print(f"⚠️ 检查页面URL时发生异常: {e}")
                return
        else:
            cookies_str = getattr(mainWindow, 'browser_cookies', None)

        if not cookies_str:
            print("⚠️ 浏览器cookies无效，跳过开奖数据获取")
            return

        rst, headerData, err_msg = getUserData(mainWindow, cookies_str=cookies_str)
        if rst is None:
            print(f'❌ [开奖采集] API接口返回None，错误: {err_msg}')
            raise Exception('开奖数据异常None1:')
        rstData = json.loads(rst.text)
        if rstData['Status'] != 1:
            print(f'❌ [开奖采集] API接口状态异常: Status={rstData.get("Status")}, 响应: {rstData}')
            raise Exception('开奖数据异常1', 'Status：' + str(rstData['Status']))

        # 添加调试信息
        previous_period_status = rstData.get('Data', {}).get('previous_period_status')
        previous_period_no = rstData.get('Data', {}).get('previous_period_no')
        previous_draw_no = rstData.get('Data', {}).get('previous_draw_no')
        current_period_no = rstData.get('Data', {}).get('period_no')
        
        print(f'🔍 [开奖采集] API返回数据: 上期状态={previous_period_status}, 上期号={previous_period_no}, 上期号码={previous_draw_no}, 当前期号={current_period_no}')

        if str(previous_period_status) == '3':  # 1封盘 3开盘
            # previous_period_no 和 previous_draw_no 已在上面获取
            
            # 检查推送缓存，避免重复推送
            with _push_cache_lock:
                # 检查是否已经成功推送过
                if _global_push_cache.is_successful(previous_period_no):
                    # 已经成功推送过，添加调试日志（每5分钟输出一次）
                    import datetime
                    now = datetime.datetime.now()
                    if now.second < 5:  # 只在每分钟的前5秒输出一次
                        print(f"ℹ️ [开奖采集] 期号 {previous_period_no} 已成功推送过，跳过重复推送")
                    return
                
                # 检查是否可以推送（包含时间间隔控制）
                can_push = _global_push_cache.can_push(previous_period_no)
                if not can_push:
                    # 不能推送（时间间隔未到），添加调试日志
                    import datetime
                    now = datetime.datetime.now()
                    if now.second < 5:  # 只在每分钟的前5秒输出一次
                        print(f"⏰ [开奖采集] 期号 {previous_period_no} 推送间隔未到（最小间隔8秒），等待中...")
                    return
                
                if can_push:
                    try:
                        print(f'🔄 [开奖采集] 准备推送开奖号码: 期号 {previous_period_no}, 号码 {previous_draw_no}')
                        pushData = {'expect': previous_period_no, 'opencode': previous_draw_no}
                        pushRst = pushSyncKjData(access_token, pushData, lottery_type)
                        
                        # 更新推送缓存状态
                        if pushRst and pushRst.get('data'):
                            _global_push_cache.mark_success(previous_period_no, previous_draw_no)
                            print(f'✅ [开奖采集] 开奖号码推送成功1: 期号 {previous_period_no}, 号码 {previous_draw_no}')
                        else:
                            _global_push_cache.mark_failed(previous_period_no, 400)
                            print(f'⚠️ [开奖采集] 开奖号码推送失败: 期号 {previous_period_no}, 号码 {previous_draw_no}, 响应: {pushRst}')
                    except Exception as e:
                        _global_push_cache.mark_failed(previous_period_no, 500)
                        print(f'❌ [开奖采集] 推送开奖数据异常: 期号 {previous_period_no}, 错误: {e}')
                        import traceback
                        traceback.print_exc()
            
            # 检查是否需要刷新页面
            try:
                if pushRst and pushRst.get('data'):
                    if int(pushRst['data']['num']) > 15 or pushRst['data']['refresh']:
                        safe_refresh_page(mainWindow, "开奖数据推送成功后的页面刷新")
            except:
                pass

            # previous_period_no：已经开奖期号 period_no：当前正在进行期号
            mainWindow.current_qihao = rstData['Data']['period_no']
    except Exception as e:
        print('推送开奖数据异常：', e.args)
        if '未登录' not in str(e):
            pushErrorLog('机器人网页获取开奖数据异常1：', access_token, lottery_type, str(e))


# 获取已经开奖的号码2 - 定时器 界面上的数据
def getNowKjDataTimer2(mainWindow):
    """
    获取当前开奖号码2，获取页面上的开奖号码 - 网盘页面
    优化：
    1. 只在开奖时间段执行（参考 LotteryTimeHelper.is_draw_time）
    2. 如果页面刷新不出来，静默忽略（不报错）
    3. 去掉详细调试日志，只记录推送结果，集成推送缓存避免重复推送
    """
    try:
        # 关键优化：根据时间段判断是否需要采集（只在开奖时间段执行）
        try:
            from xy_client.services.Lucky5.utils.lottery_time_helper import LotteryTimeHelper
            # 获取当前彩种类型（默认8=幸运五）
            current_lottery_type = int(config.get_config('lottery_type', 'system_configs') or 8)
            
            # 判断当前时间是否可以抓取开奖号码
            is_draw_time = LotteryTimeHelper.is_draw_time(current_lottery_type)
            if not is_draw_time:
                # 不在抓取时间段，添加调试日志（每5分钟输出一次，避免日志过多）
                import datetime
                now = datetime.datetime.now()
                # 只在每分钟的第0秒输出一次日志，避免日志过多
                if now.second < 5:
                    time_info = LotteryTimeHelper.get_time_info(current_lottery_type)
                    print(f"⏰ [页面采集] 当前不在抓取时间段（{time_info.get('current_time', 'N/A')}），等待开奖时间窗口...")
                return False
            else:
                # 在抓取时间段，输出调试信息（每30秒输出一次）
                import datetime
                now = datetime.datetime.now()
                if now.second % 30 == 0:  # 每30秒输出一次
                    print(f"✅ [页面采集] 当前在抓取时间段，开始采集开奖号码...")
        except Exception as time_check_e:
            # 如果时间段判断失败，继续执行（保持原有逻辑）
            print(f"⚠️ [页面采集] 时间段判断异常: {time_check_e}，继续执行采集")
            pass
        
        if not mainWindow.driver:
            # 静默记录到日志文件，不输出到控制台
            return False
        
        isCanActionStatus = isCanAction(mainWindow)
        if isCanActionStatus:

            current_url = mainWindow.driver.current_url
            now_time = str(time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()))
            minutes = int(now_time[-5:-3])
            minutes_d_1 = minutes % 5 - 1  # 分钟数减1 : 6分

            # 安全地查找快选按钮
            try:
                kuaida_btn = mainWindow.driver.find_element(By.NAME, 'kuaida')
                if 'kuaida' not in current_url or 'on' not in kuaida_btn.get_attribute('class'):
                    if minutes_d_1 == 0:
                        kuaida_btn.click()
                        # 记录到日志文件，不输出到控制台
                        pushErrorLog('开奖之后，非快选页面，点击快选按钮...', mainWindow.access_token, lottery_type,
                                     {'msg': '快打按钮点击'})
                        time.sleep(2)
            except Exception as e:
                # 静默处理异常，不影响主流程
                pass

            # 首先检查是否有弹框需要处理（只在协议页面处理）
            try:
                current_url = mainWindow.driver.current_url.lower()
                
                # 如果已经在主页面，跳过协议页面处理
                if "app" in current_url or "index" in current_url:
                    # 已在主页面，不需要处理协议页面，静默跳过
                    pass
                elif "agreement" in current_url or "协议" in current_url:
                    # 只有在协议页面才处理
                    from xy_client.services.tools.AgreementHandler import handle_agreement_page
                    success = handle_agreement_page(mainWindow.driver, timeout=5)
                    if success:
                        print("✅ 责任声明弹框处理成功")
                    else:
                        print("ℹ️ 未检测到责任声明弹框，继续正常流程")
                else:
                    # 其他页面，静默跳过
                    pass
            except ImportError:
                # 如果无法导入AgreementHandler，使用简单的检查方式（只在协议页面）
                try:
                    current_url = mainWindow.driver.current_url.lower()
                    if "agreement" in current_url or "协议" in current_url:
                        agree_btn = mainWindow.driver.find_element(By.ID, 'agree')
                        if agree_btn and agree_btn.is_displayed():
                            print("检测到责任声明弹框，先处理弹框...")
                            agree_btn.click()
                            time.sleep(2)
                except:
                    pass  # 没有弹框，继续正常流程
            except Exception as e:
                # 静默处理异常，不影响主流程
                pass
            
            try:
                # 使用更安全的元素查找方式，增加等待时间
                # 关键优化：如果页面刷新不出来（找不到元素），静默忽略，不报错
                try:
                    kjdata_div = WebDriverWait(mainWindow.driver, 5, 0.5).until(
                        EC.presence_of_element_located((By.ID, 'period_draw'))
                    )
                except Exception as element_not_found:
                    # 页面刷新不出来，找不到元素，静默忽略（不报错）
                    return False
                
                # 安全地获取期号 - 使用多种查找策略
                kj_qihao = ''
                qihao_found = False
                
                # 策略1: 标准表格结构
                if not qihao_found:
                    try:
                        kj_qihao_element = kjdata_div.find_element(By.XPATH, './/table[1]//tbody//tr[1]/td[1]')
                        if kj_qihao_element and kj_qihao_element.text:
                            kj_qihao = kj_qihao_element.text.strip()
                            qihao_found = True
                    except Exception as e:
                        pass
                
                # 策略2: 查找包含年份或"期"字的元素
                if not qihao_found:
                    try:
                        qihao_elements = kjdata_div.find_elements(By.XPATH, './/td[contains(text(), "2025") or contains(text(), "期") or contains(text(), "2024")]')
                        if qihao_elements:
                            for elem in qihao_elements:
                                text = elem.text.strip()
                                if text and any(year in text for year in ["2025", "2024", "期"]):
                                    kj_qihao = text
                                    qihao_found = True
                                    break
                    except Exception as e:
                        pass
                
                # 策略3: 查找所有表格单元格，寻找期号模式
                if not qihao_found:
                    try:
                        all_cells = kjdata_div.find_elements(By.TAG_NAME, 'td')
                        for cell in all_cells:
                            text = cell.text.strip()
                            if text and (any(year in text for year in ["2025", "2024"]) or "期" in text):
                                kj_qihao = text
                                qihao_found = True
                                break
                    except Exception as e:
                        pass
                
                # 策略4: 查找span或其他标签中的期号
                if not qihao_found:
                    try:
                        span_elements = kjdata_div.find_elements(By.TAG_NAME, 'span')
                        for span in span_elements:
                            text = span.text.strip()
                            if text and (any(year in text for year in ["2025", "2024"]) or "期" in text):
                                kj_qihao = text
                                qihao_found = True
                                break
                    except Exception as e:
                        pass
                
                if not qihao_found:
                    # 静默返回，不记录调试信息
                    return False
                
                # 安全地获取开奖号码 - 使用多种查找策略
                kj_data = ''
                data_found = False
                
                # 策略1: 标准表格结构
                if not data_found:
                    try:
                        kj_data_element = kjdata_div.find_element(By.XPATH, './/table[1]//tbody//tr[1]/td[2]')
                        if kj_data_element and kj_data_element.text:
                            kj_data = kj_data_element.text.strip()
                            # 处理号码格式，移除多余空格并替换为逗号
                            kj_data = ','.join([num.strip() for num in kj_data.split() if num.strip()])
                            # 验证：开奖号码不应该与期号相同，且应该包含逗号分隔的数字
                            if kj_data and kj_data != kj_qihao and ',' in kj_data and len(kj_data) >= 5:
                                data_found = True
                            else:
                                print(f"⚠️ 策略1获取的号码无效: {kj_data} (期号: {kj_qihao})")
                    except Exception as e:
                        pass
                
                # 策略2: 查找包含逗号或长度足够的元素
                if not data_found:
                    try:
                        kj_elements = kjdata_div.find_elements(By.XPATH, './/td[contains(text(), ",") or string-length(text()) >= 5]')
                        if kj_elements:
                            for elem in kj_elements:
                                text = elem.text.strip()
                                # 验证：开奖号码不应该与期号相同，且应该包含逗号分隔的数字
                                if (text and len(text) >= 5 and text != kj_qihao and 
                                    ',' in text and any(char.isdigit() for char in text)):
                                    kj_data = text
                                    data_found = True
                                    break
                    except Exception as e:
                        pass
                
                # 策略3: 查找所有表格单元格，寻找开奖号码模式
                if not data_found:
                    try:
                        all_cells = kjdata_div.find_elements(By.TAG_NAME, 'td')
                        for cell in all_cells:
                            text = cell.text.strip()
                            # 验证：开奖号码不应该与期号相同，且应该包含逗号分隔的数字
                            if (text and len(text) >= 5 and text != kj_qihao and 
                                ',' in text and any(char.isdigit() for char in text)):
                                kj_data = text
                                data_found = True
                                break
                    except Exception as e:
                        pass
                
                # 策略4: 查找span或其他标签中的开奖号码
                if not data_found:
                    try:
                        span_elements = kjdata_div.find_elements(By.TAG_NAME, 'span')
                        for span in span_elements:
                            text = span.text.strip()
                            if text and len(text) >= 5 and any(char.isdigit() for char in text):
                                kj_data = text
                                data_found = True
                                break
                    except Exception as e:
                        pass
                
                if not data_found:
                    # 静默返回，不记录调试信息
                    return False
                
                # 检查数据有效性
                if not kj_qihao or not kj_data:
                    # 静默返回，不输出调试信息
                    return False
                
                # 调试：检查期号和号码是否相同（这是错误的）
                if kj_qihao == kj_data:
                    return False
                
                # 开奖时间到了但号码为空，尝试点击刷新按钮
                if minutes_d_1 == 0 and kj_data == '':
                    try:
                        # 查找并点击刷新按钮
                        refresh_btn = mainWindow.driver.find_element(By.XPATH, "//button[contains(text(), '刷新')]")
                        if refresh_btn:
                            refresh_btn.click()
                            time.sleep(1)  # 等待刷新
                            
                            # 重新获取开奖号码
                            try:
                                kj_data_element = kjdata_div.find_element(By.XPATH, './table[1]/tbody[1]/tr[1]/td[2]')
                                kj_data = kj_data_element.text.strip().replace(' ', ',') if kj_data_element else ''
                            except Exception as e:
                                pass
                        
                        # 如果仍然没有号码，尝试点击快选按钮
                        if not kj_data:
                            try:
                                kuaida_btn.click()
                            except:
                                pass
                    except Exception as e:
                        # 静默处理异常
                        pass
                    
                    # 如果仍然没有号码，静默返回
                    if not kj_data:
                        return False

                # 最终验证：确保期号和开奖号码不同
                if kj_qihao == kj_data:
                    print(f"❌ 最终验证失败：期号和开奖号码相同，跳过推送")
                    print(f"   期号: {kj_qihao}")
                    print(f"   号码: {kj_data}")
                    return False
                
                # 验证开奖号码格式：应该包含逗号分隔的数字
                if not kj_data or ',' not in kj_data or len(kj_data) < 5:
                    print(f"❌ 开奖号码格式无效: {kj_data}")
                    return False
                
                # 检查推送缓存，避免重复推送
                with _push_cache_lock:
                    # 检查是否已经成功推送过
                    if _global_push_cache.is_successful(kj_qihao):
                        # 已经成功推送过，静默返回
                        return False
                    
                    # 检查是否可以推送
                    if not _global_push_cache.can_push(kj_qihao):
                        # 达到最大尝试次数，静默返回
                        return False

                # 推送开奖数据
                try:
                    pushData = {'expect': kj_qihao, 'opencode': kj_data}
                    pushRst = pushSyncKjData(access_token=mainWindow.access_token, kj_datas=pushData,
                                             lottery_type=lottery_type, fromId='page2')
                    
                    # 更新推送缓存状态
                    if pushRst and pushRst.get('data'):
                        _global_push_cache.mark_success(kj_qihao, kj_data)  # 标记为成功
                        # 只记录推送成功的关键信息
                        print(f'✅ 开奖号码推送成功2: 期号 {kj_qihao}, 号码 {kj_data}')
                        
                        push_data = pushRst['data']
                        # 安全地转换num字段
                        try:
                            num_value = push_data.get('num', 0)
                            if isinstance(num_value, str):
                                num_value = int(num_value) if num_value.strip() else 0
                            elif num_value is None:
                                num_value = 0
                            else:
                                num_value = int(num_value)
                            
                            if num_value > 15 or push_data.get('refresh', False):
                                safe_refresh_page(mainWindow, "开奖数据推送成功后的页面刷新")
                        except (ValueError, TypeError) as e:
                            # 静默处理异常，不影响主流程
                            pass
                    else:
                        _global_push_cache.mark_failed(kj_qihao, 400)  # 标记为失败
                        # 只记录推送失败的关键信息
                        print(f'⚠️ 开奖号码推送失败: 期号 {kj_qihao}, 号码 {kj_data}')
                            
                except Exception as e:
                    _global_push_cache.mark_failed(kj_qihao, 500)  # 标记为异常
                    # 只记录推送异常的关键信息
                    print(f'❌ 推送开奖数据异常: 期号 {kj_qihao}')
                    # 不影响主流程，继续执行
                    
            except Exception as e:
                # 静默处理异常，不输出调试信息
                return False

    except Exception as e:
        # 记录异常到日志文件，不输出到控制台
        pushErrorLog('机器人网页获取开奖数据异常2：', mainWindow.access_token, lottery_type, str(e))
        return False


def isCanAction(mainWindow):
    # 获取当前时间
    current_time = datetime.datetime.now().time()
    try:
        if mainWindow.open_time and mainWindow.end_time:
            start_time = mainWindow.open_time
            end_time = mainWindow.end_time
        else:
            raise Exception('开奖时间没配置，使用默认值')
    except Exception as e:
        # 定义起始时间和结束时间
        start_time = datetime.time(5, 0, 0)  # 04:00:00
        end_time = datetime.time(7, 30, 0)  # 08:30:00

    # 判断当前时间是否在指定的时间范围内
    if start_time < current_time < end_time:
        status = False
    else:
        status = True

    return status


# 根据期号获取下一期
def getNextQihaoByQiHao(qihao):
    try:
        e_qh = qihao[-3:]
        if e_qh == '288':
            next_qihao = int(qihao[0:8]) + 1
        else:
            next_qihao = int(qihao) + 1
    except Exception as e:
        next_qihao = qihao

    return str(next_qihao)


def user_login_job(mainWindow):
    '''
    用户登陆检测
    '''
    print("🔷 [user_login_job] 开始执行...")
    try:
        now_time = str(time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()))
        print(f"🕐 [user_login_job] 当前时间: {now_time}")
        
        minutes = int(now_time[-5:-3])
        if lottery_type == 28:
            minutes_d = minutes % 5 - 4  # 分钟数减1 : 5分
        else:
            minutes_d = minutes % 5 - 1  # 分钟数减1 : 6分
        
        print(f"📅 [user_login_job] minutes_d = {minutes_d}")
        
        if minutes_d == 0:
            print("⏰ [user_login_job] 到达刷新时间点（每5分钟第1分钟），准备执行页面操作...")
            print(f"💡 [user_login_job] 刷新目的：辅助功能，让页面显示正常（非关键功能，失败不影响下注业务）")
            
            # 关键优化：即使刷新失败，也不影响下注业务，只记录日志并继续执行
            try:
                # 检查WebDriver是否可用，如果不可用则尝试恢复连接
                if not hasattr(mainWindow, 'driver') or not mainWindow.driver:
                    print("⚠️ [user_login_job] WebDriver不可用，尝试恢复连接...")
                    # 尝试恢复WebDriver连接
                    try:
                        from xy_client.services.Lucky5.Lucky import _reconnect_to_browser
                        if _reconnect_to_browser(mainWindow, silent=False):
                            print("✅ [user_login_job] WebDriver连接已恢复")
                        else:
                            print("⚠️ [user_login_job] WebDriver连接恢复失败，跳过刷新（不影响下注业务）")
                            return
                    except Exception as reconnect_e:
                        print(f"⚠️ [user_login_job] 恢复WebDriver连接异常: {reconnect_e}，跳过刷新（不影响下注业务）")
                        return
                
                # 验证WebDriver连接是否有效
                try:
                    current_url = mainWindow.driver.current_url
                    print(f"🔗 [user_login_job] 当前URL: {current_url}")
                except Exception as url_e:
                    print(f"⚠️ [user_login_job] 无法获取当前URL，WebDriver连接可能已断开: {url_e}")
                    # 尝试恢复连接
                    try:
                        from xy_client.services.Lucky5.Lucky import _reconnect_to_browser
                        if _reconnect_to_browser(mainWindow, silent=False):
                            print("✅ [user_login_job] WebDriver连接已恢复，继续刷新")
                            current_url = mainWindow.driver.current_url
                        else:
                            print("⚠️ [user_login_job] WebDriver连接恢复失败，跳过刷新（不影响下注业务）")
                            return
                    except Exception as reconnect_e2:
                        print(f"⚠️ [user_login_job] 恢复WebDriver连接异常: {reconnect_e2}，跳过刷新（不影响下注业务）")
                        return
                
                if 'xyz' in current_url:
                    flag_txt = '刷新当前页面'
                    print(f"🔄 [user_login_job] 将刷新当前页面（辅助功能：让页面显示正常）...")
                    # 使用安全的页面刷新方法（最多重试2次，避免频繁刷新影响盘口）
                    try:
                        # 超时时间15秒，重试2次，如果失败则等待下一个5分钟周期再尝试
                        success = safe_refresh_page(mainWindow, reason="6分自动刷新（辅助功能）", timeout=15, max_retry=2)
                        if success:
                            print(f"✅ [user_login_job] 页面已刷新（辅助功能完成）")
                        else:
                            print(f"⚠️ [user_login_job] 页面刷新失败（已重试2次），暂停刷新，等待下一个5分钟周期再尝试（不影响下注业务）")
                            # 刷新失败不影响业务，只是页面显示可能不正常，等待下一个周期再试
                    except Exception as refresh_e:
                        # 捕获所有异常，包括网络超时、WebDriver连接超时等
                        print(f"⚠️ [user_login_job] 页面刷新异常（已忽略，不影响下注业务）: {refresh_e}")
                        # 不输出详细堆栈，避免日志过多
                else:
                    flag_txt = '跳转页面'
                    wp_domain = mainWindow.agent_domain_val.text()
                    url = wp_domain + '/App/Index?_=' + str(now_time) + '#!kuaida'
                    print(f"🔄 [user_login_job] 将跳转到: {url}")
                    # 使用安全的页面打开方法（支持超时控制和自动恢复）
                    try:
                        from xy_client.services.tools.PageRefreshManager import get_refresh_manager
                        refresh_manager = get_refresh_manager(page_load_timeout=10, max_retry=2)
                        success = refresh_manager.safe_get(mainWindow.driver, url, 
                                                          reason="6分自动跳转", 
                                                          check_loading=True, timeout=10)
                        if success:
                            print(f"✅ [user_login_job] 已跳转到目标页面")
                        else:
                            print(f"⚠️ [user_login_job] 跳转失败（已重试），但继续执行（不影响下注业务）")
                    except ImportError:
                        # 如果导入失败，使用原有的简单方式
                        try:
                            mainWindow.driver.get(url)
                            print(f"✅ [user_login_job] 已跳转到目标页面")
                        except Exception as get_e:
                            print(f"⚠️ [user_login_job] 跳转异常（已忽略，不影响下注业务）: {get_e}")
                    except Exception as nav_e:
                        # 捕获所有异常，包括网络超时、WebDriver连接超时等
                        print(f"⚠️ [user_login_job] 页面跳转异常（已忽略，不影响下注业务）: {nav_e}")
                
                # 记录刷新日志（无论成功或失败）
                try:
                    pushErrorLog(str(now_time) + '，6分自动刷新（辅助功能）', mainWindow.access_token, lottery_type,
                                 {'msg': '自动刷新', 'flag_txt': flag_txt, 'purpose': '辅助功能：让页面显示正常'})
                    print(f"📝 [user_login_job] 已记录刷新日志（辅助功能）")
                except:
                    pass  # 日志记录失败也不影响业务
            except Exception as e:
                # 捕获所有异常，确保不影响下注业务
                print(f"⚠️ [user_login_job] 执行异常（已忽略，不影响下注业务）: {e}")
                # 不抛出异常，继续执行
        else:
            print(f"⏸ [user_login_job] 未到达刷新时间点，跳过")
    except Exception as e:
        print(f"❌ [user_login_job] 执行异常: {e}")
        traceback.print_exc()
        pass


def agent_login_job(mainWindow):
    '''
    用户登陆检测
    '''
    try:
        now_time = str(time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()))
        minutes = int(now_time[-5:-3])
        minutes_d_1 = minutes % 5 - 1  # 分钟数减1 : 6分
        if minutes_d_1 == 0:
            if 'xyz' in mainWindow.agent_browser.current_url:
                flag_txt = '刷新当前页面'
                mainWindow.agent_browser.refresh()
            else:
                flag_txt = '跳转页面'
                wp_domain = mainWindow.agent_domain_val.text()
                url = wp_domain + '/App/Index?_=' + str(now_time) + '#!log.select|select?link=select'
                mainWindow.agent_browser.get(url)
            pushErrorLog(str(now_time) + '，6分自动代理刷新', mainWindow.access_token, lottery_type,
                         {'msg': '自动刷新', 'flag_txt': flag_txt})
    except Exception as e:
        pass


def refreshTimer(mainWindow):
    """
    定时刷新页面（增强版：支持超时控制和自动恢复）
    关键优化：即使刷新失败，也不影响下注业务，只记录日志并继续执行
    """
    try:
        # 优先刷新主浏览器
        if hasattr(mainWindow, 'driver') and mainWindow.driver:
            try:
                # 使用安全的页面刷新方法（支持超时控制和自动恢复）
                success = safe_refresh_page(mainWindow, reason="定时刷新页面", timeout=10, max_retry=2)
                if success:
                    print(f"✅ [refreshTimer] 主浏览器页面刷新成功")
                else:
                    print(f"⚠️ [refreshTimer] 主浏览器页面刷新失败（已重试），但继续执行（不影响下注业务）")
            except Exception as refresh_e:
                # 捕获所有异常，包括网络超时、WebDriver连接超时等
                print(f"⚠️ [refreshTimer] 主浏览器刷新异常（已忽略，不影响下注业务）: {refresh_e}")
        
        # 刷新代理浏览器（如果存在）
        if hasattr(mainWindow, 'agent_browser') and mainWindow.agent_browser:
            try:
                # 代理浏览器也使用安全刷新
                from xy_client.services.tools.PageRefreshManager import get_refresh_manager
                refresh_manager = get_refresh_manager(page_load_timeout=10, max_retry=2)
                success = refresh_manager.safe_refresh(mainWindow.agent_browser, 
                                                      reason="代理浏览器定时刷新", 
                                                      check_loading=True, timeout=10)
                if success:
                    print(f"✅ [refreshTimer] 代理浏览器页面刷新成功")
                else:
                    print(f"⚠️ [refreshTimer] 代理浏览器页面刷新失败（已重试），但继续执行（不影响下注业务）")
            except ImportError:
                # 如果导入失败，使用原有的简单方式
                try:
                    mainWindow.agent_browser.refresh()
                    print(f"✅ [refreshTimer] 代理浏览器页面刷新成功（简单方式）")
                except Exception as simple_refresh_e:
                    print(f"⚠️ [refreshTimer] 代理浏览器刷新异常（已忽略，不影响下注业务）: {simple_refresh_e}")
            except Exception as agent_e:
                # 捕获所有异常，包括网络超时、WebDriver连接超时等
                print(f"⚠️ [refreshTimer] 代理浏览器刷新异常（已忽略，不影响下注业务）: {agent_e}")
    except Exception as e:
        # 捕获所有异常，确保不影响下注业务
        print(f"⚠️ [refreshTimer] 执行异常（已忽略，不影响下注业务）: {e}")
        # 记录错误日志（但不抛出异常）
        try:
            pushErrorLog('自动刷新3：' + str(mainWindow.current_qihao), access_token, lottery_type, e.args)
        except:
            pass  # 日志记录失败也不影响业务


# 自动同步用户信息 - 定时器
def syncUserInfoTimer(inc, mainWindow):
    try:
        now_time = str(time.strftime("%H:%M", time.localtime()))
        print(f"🔄 开始同步用户信息... ({now_time})")
        
        accountInfo = getAccountByToken()
        
        # 检查 accountInfo 是否有效
        if accountInfo is None:
            print("❌ 获取用户信息失败：accountInfo 为 None")
            return
        
        if 'data' not in accountInfo:
            print(f"❌ 用户信息格式错误：缺少 'data' 字段，实际内容: {accountInfo}")
            return
            
        userInfo = accountInfo['data']
        
        # 检查 userInfo 是否有效
        if not userInfo or userInfo == {}:
            print("❌ 用户数据为空")
            return
            
        print(f"✅ 成功获取用户信息: {userInfo.get('username', 'Unknown')}")
        
        # 安全地设置用户信息
        try:
            mainWindow.wp_domain = userInfo.get('ssc_domain', '')
            mainWindow.wp_account = userInfo.get('account', '')
            mainWindow.wp_password = userInfo.get('password', '')
            mainWindow.user_info = userInfo
            
            # 设置界面显示
            if hasattr(mainWindow, 'domain_val') and mainWindow.domain_val:
                mainWindow.domain_val.setText(mainWindow.wp_domain)
            if hasattr(mainWindow, 'username_val') and mainWindow.username_val:
                mainWindow.username_val.setText(mainWindow.wp_account)
            if hasattr(mainWindow, 'pwd_val') and mainWindow.pwd_val:
                mainWindow.pwd_val.setText(mainWindow.wp_password)
            if hasattr(mainWindow, 'token_val') and mainWindow.token_val:
                mainWindow.token_val.setText(access_token)
            
            # 设置窗口标题
            username = userInfo.get('username', 'Unknown')
            mainWindow.setWindowTitle(f'用户界面：{username}')
            
            print(f"✅ 用户信息已填充到界面: 域名={mainWindow.wp_domain}, 账号={mainWindow.wp_account}")
            
        except Exception as ui_error:
            print(f"❌ 设置界面信息时发生异常: {ui_error}")
            traceback.print_exc()
            
    except Exception as e:
        print(f'❌ 同步用户信息异常: {e}')
        traceback.print_exc()
        try:
            pushErrorLog('同步用户信息异常：' + str(mainWindow.current_qihao), access_token, lottery_type, str(e))
        except Exception as e2:
            print(f'❌ 推送错误日志失败: {e2}')

    except Exception as e:
        print('同步用户信息异常11：', e.args)
        try:
            pushErrorLog('同步用户信息异常3：' + str(mainWindow.current_qihao), access_token, lottery_type,
                         e.args)
        except Exception as e:
            pass
        # t1 = threading.Timer(inc, syncUserInfoTimer, (inc, mainWindow))
        # t1.start()


# 自动登陆 - 定时器
def getDrawCodeClient(inc, mainWindow):
    try:
        now_time = str(time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()))
        minutes = int(now_time[-5:-3])
        minutes_d_1 = minutes % 5 - 1  # 分钟数减1 : 6分
        if minutes_d_1 == 0 or True:
            dataCode = getGfCode()
            pushErrorLog(str(now_time) + '，6分获取官方开奖号码', access_token, lottery_type,
                         {'msg': '官方开奖号码抓取'})
    except Exception as e:
        pushErrorLog('获取官网开奖号码异常3：', access_token, lottery_type, e.args)
    MyThreadingTimer.myTimer(inc, getDrawCodeClient, mainWindow)


def checkUserLoginJob(mainWindow):
    """
    检查用户登录任务
    关键优化：使用API检查登录状态，不依赖WebDriver
    """
    print("🔍 [checkUserLoginJob] 开始检查用户登录状态...")
    
    # 如果is_need_login == 1，表示已登录，不需要再登录
    if mainWindow.is_need_login == 1:
        print("✅ [checkUserLoginJob] is_need_login = 1，已登录，返回True")
        return True  # 已登录，返回True
    
    # 如果is_need_login == 2，表示正在登录中
    if mainWindow.is_need_login == 2:
        print("⚠️ [checkUserLoginJob] is_need_login = 2，正在登录中，返回False")
        return False  # 需要登录，返回False
    
    # 如果is_need_login == 0，使用API检查登录状态
    print("🔍 [checkUserLoginJob] is_need_login = 0，使用API检查登录状态...")
    
    # 检查是否有cookies
    has_cookies = hasattr(mainWindow, 'browser_cookies') and mainWindow.browser_cookies
    print(f"🍪 [checkUserLoginJob] cookies状态: {'存在' if has_cookies else '不存在'}")
    
    if not has_cookies:
        print("❌ [checkUserLoginJob] 没有cookies，需要登录，返回False")
        return False
    
    # 有cookies，使用API检查登录状态
    try:
        from xy_client.services.Lucky5.core.platform_api import check_login_status_by_api
        
        print("🔄 [checkUserLoginJob] 调用API检查登录状态...")
        is_logged_in = check_login_status_by_api(mainWindow)
        
        if is_logged_in:
            # API检查成功，更新登录状态
            mainWindow.is_need_login = 1
            print("✅ [checkUserLoginJob] API检查：已登录，更新is_need_login = 1，返回True")
            return True
        else:
            # API检查失败，需要登录
            print("❌ [checkUserLoginJob] API检查：未登录，返回False")
            return False
    except ImportError:
        print("⚠️ [checkUserLoginJob] platform_api模块不可用，使用cookies判断")
        # 如果API模块不可用，使用cookies判断（保守处理）
        mainWindow.is_need_login = 1
        print("✅ [checkUserLoginJob] 有cookies，更新is_need_login = 1，返回True")
        return True
    except Exception as api_e:
        print(f"❌ [checkUserLoginJob] API检查异常: {api_e}，使用cookies判断")
        # API检查异常时，使用cookies判断（保守处理）
        mainWindow.is_need_login = 1
        print("✅ [checkUserLoginJob] 有cookies，更新is_need_login = 1，返回True")
        return True


def checkAgentLoginJob(mainWindow):
    """
    用户登陆检测操作
    :param mainWindow:
    :return:
    """
    # print('检测代理登陆开始.........')
    if mainWindow.agent_browser is None:
        if mainWindow.agent_domain is not None:
            current_url = mainWindow.agent_domain.strip()
        else:
            # Handle the case when agent_domain is None
            current_url = None
    else:
        try:
            current_url = parse.unquote(mainWindow.agent_browser.current_url)
        except NoSuchWindowException:
            current_url = None  # Handle the case when agent_browser.current_url is not available
    # print('代理检测登陆开始....', current_url)
    if current_url is not None and ('Login' in current_url or '登录' in current_url):
        try:
            tk_login_btn = mainWindow.agent_browser.find_element(By.ID, 'tip_btn')
            tk_login_btn.click()
            time.sleep(5)
            agent_domain = tools.getHostByUrl(current_url)

            try:
                mainWindow.agent_browser.close()
            except Exception as e1:
                pass
            try:
                mainWindow.agent_browser.quit()
            except Exception as e2:
                pass
            # mainWindow.chrome_service.stop()
        except Exception as e:
            agent_domain = mainWindow.agent_domain_val.text()

        mainWindow.agent_domain_val.setText(agent_domain)  # 修改窗口的域名

        time.sleep(5)
        account = mainWindow.agent_username_val.text()
        agent_password = mainWindow.agent_password_val.text()

        pushErrorLog('代理自动登陆时间：', access_token, lottery_type,
                     {'msg': '自动登录', 'login_time': time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()),
                      'current_url': current_url})

        if 'http' in agent_domain and account and agent_password:
            thread = threading.Thread(target=mainWindow.newAgentThreadLogin,
                                      args=(agent_domain, account, agent_password),
                                      daemon=True)
            thread.start()
        else:
            print('网盘信息不全不自动登陆')
        is_act_login = True
    else:
        is_act_login = False
        # print('登陆成功状态，不能重新登陆......')
    # print('检测登陆结束....')

    return is_act_login


def judeIsMin(minute=1):
    '''
    判断是否为某一分钟，用于特定时间刷新页面
    '''
    now_time = str(time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()))
    minutes = int(now_time[-5:-3])
    minutes_d = minutes % 5 - minute  # 分钟数减1 : 6分
    if minutes_d == 0:
        return True
    return False


def isHasLoginCurrentUrl(current_url):
    '''
    检测是否已登录
    :param current_url:
    :return:
    '''

    if 'Login' in current_url or '登录' in current_url:  # and
        raise Exception('未登录状态：登录页面')

    return True


def updateClientLoginFlag(flag=2):
    """
    :param flag: 0无需登录1需登录2登录中
    更新客户端登录标识
    """
    data = {}
    try:
        url = robot_domain + '/api/index/update-client-login-flag'
        post_data = {'access_token': access_token, 'flag': flag}
        headers = {'content-type': 'application/json'}

        # 使用安全的网络请求函数，包含超时和重试机制
        data = safe_request(
            method='POST',
            url=url,
            data=json.dumps(post_data),
            headers=headers,
            api_type='user_info'  # 使用用户信息接口的特定配置
        )
        
        now_time = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())

    except Exception as e:
        traceback.print_exc()
        print('更新客户端登录标识-err', e.args)
        data = {'status': 300, 'error': str(e)}

    return data
