import datetime
import os
import re
import threading
import time
import traceback
import urllib
import zlib
from concurrent.futures import ThreadPoolExecutor

import brotli
import urllib3
from PyQt5.QtCore import QTimer
from selenium.common.exceptions import NoSuchWindowException
# from selenium.webdriver.chrome.webdriver import WebDriver
from selenium.webdriver.firefox.webdriver import WebDriver
from urllib.parse import urlsplit
import json

import requests
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.wait import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from xy_client.services.tools import tools
from .common import getAccountByToken, pushErrorLog
from ..tools.Configs import Configs
from ..tools.GlobalSession import GlobalSession
from ..tools.tools import getHostNameData

globalSession = GlobalSession().get_session()

# from .SystemsUsers import getAccountByToken, pushErrorLog
from ..Lucky5 import Lucky
from xy_client.services.MyThreading import MyThreadingTimer
from xy_client.models import users

config = Configs()
robot_domain = config.get_config('robot_domain')
lottery_type = int(config.get_config('lottery_type'))
access_token = config.get_config('access_token')
auto_login_times = 0


# 获取用户日志 - 定时器
def getUserBetDesc(inc, mainWindow):
    try:
        now_time = str(time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()))
        minutes = int(now_time[-5:-3])
        print('分钟数：', minutes)
        minutes_d_1 = minutes % 5 - 1  # 分钟数减1 : 6分
        if minutes_d_1 == 0:
            pass
        print('agent_browser：', mainWindow.agent_browser)
        if isinstance(mainWindow.agent_browser, WebDriver):

            log_btn = mainWindow.browser.find_element(By.NAME, 'log')
            class_value = log_btn.get_attribute("class")
            if 'on' not in class_value:
                log_btn.click()

            print('日志按钮点击开始...')
            mainWindow.agent_browser.find_element(By.LINK_TEXT, '日志').click()

            time.sleep(3)
            print('会员日志按钮点击开始...')
            mainWindow.agent_browser.find_element(By.LINK_TEXT, '会员快选日志').click()

            # 搜集日志...
            print('搜集日志')

            time.sleep(3)
            mainWindow.agent_browser.find_element(By.LINK_TEXT, '会员快译日志').click()
            print('搜集日志')

    except Exception as e:
        print('获取用户日志异常1：', e.args)
        try:
            pushErrorLog('获取日bet志异常码异常3：', mainWindow.access_token, lottery_type, e.args)
        except Exception as e:
            pass
    MyThreadingTimer.myTimer(inc, getUserBetDesc, mainWindow)


# 获取用户日志 from api - 定时器
def getUserBetDescByApi(mainWindow):
    try:
        print(f'[日志搜集] ============ 开始执行日志搜集 ============')
        
        # 优化：先检查代理浏览器，如果存在且可用，即使user_info为空也可以继续
        # 检查代理浏览器
        if not mainWindow.agent_browser:
            print('[日志搜集] ⚠️ agent_browser不存在，跳过日志搜集')
            return False
        
        # 检查代理登录所需的基础信息
        if not hasattr(mainWindow, 'agent_cookies') or not mainWindow.agent_cookies:
            print('[日志搜集] ⚠️ agent_cookies不存在，代理未登录，跳过日志搜集')
            return False
        
        # 检查用户信息（用于验证follow_status，但不是必需的）
        # 注意：user_info可能还没从接口加载完成，不影响日志搜集
        if hasattr(mainWindow, 'user_info') and mainWindow.user_info:
            follow_status = mainWindow.user_info.get('follow_status')
            if follow_status:
                print(f'[日志搜集] ✅ 用户信息检查通过，follow_status: {follow_status}')
            else:
                print('[日志搜集] ⚠️ follow_status为空或False，但继续尝试（仅依赖代理浏览器）')
        else:
            print('[日志搜集] ⚠️ user_info为空（可能尚未从接口加载），但继续尝试（仅依赖代理浏览器）')
        
        print('[日志搜集] ✅ 代理浏览器和cookies检查通过，开始请求接口...')
        
        # 请求日志数据
        try:
            kuaixuan_log, kuaiyi_log = agentUserBetLogByCookies(mainWindow)
            print(f'[日志搜集] ✅ 接口请求成功')
            print(f'[日志搜集] 快选日志 Status: {kuaixuan_log.get("Status")}, 是否有Data: {bool(kuaixuan_log.get("Data"))}')
            print(f'[日志搜集] 快译日志 Status: {kuaiyi_log.get("Status")}, 是否有Data: {bool(kuaiyi_log.get("Data"))}')
            
            # 检查快选日志 - 支持字符串和整数两种类型
            status = kuaixuan_log.get('Status')
            if status == 0 or status == '0':
                error_msg = kuaixuan_log.get('msg', '获取快选日志失败')
                print(f'[日志搜集] ❌ 快选日志获取失败: {error_msg}')
                raise Exception(error_msg)
            
            # 检查快选日志是否有数据 - 支持字符串"1"和整数1
            if status == 1 or status == '1':
                data = kuaixuan_log.get('Data', {})
                rows = data.get('Rows', [])
                record_count = data.get('RecordCount', 0)
                
                if rows:
                    kx_rows_count = len(rows)
                    print(f'[日志搜集] ✅ 快选日志获取成功，记录数: {kx_rows_count}, 总记录数: {record_count}')
                    
                    # 打印第一条记录的时间（用于确认是否获取到最新数据）
                    if kx_rows_count > 0:
                        first_row = rows[0]
                        operation_time = first_row.get('operation_datetime', 'N/A')
                        print(f'[日志搜集] 最新记录时间: {operation_time}')
                    
                    pushRst = syncMemberBetLogs(kuaixuan_log, lottery_type, 'kuaixuan')
                    print(f'[日志搜集] ✅ 快选日志推送结果: {pushRst}')
                else:
                    print(f'[日志搜集] ⚠️ 快选日志无Rows数据，Status={kuaixuan_log.get("Status")}, RecordCount={record_count}')
            else:
                print(f'[日志搜集] ⚠️ 快选日志Status异常: {kuaixuan_log.get("Status")}')

            # 检查快译日志是否有数据 - 支持字符串"1"和整数1
            ky_status = kuaiyi_log.get('Status')
            if ky_status == 1 or ky_status == '1':
                data = kuaiyi_log.get('Data', {})
                rows = data.get('Rows', [])
                record_count = data.get('RecordCount', 0)
                
                if rows:
                    ky_rows_count = len(rows)
                    print(f'[日志搜集] ✅ 快译日志获取成功，记录数: {ky_rows_count}, 总记录数: {record_count}')
                    
                    # 打印第一条记录的时间
                    if ky_rows_count > 0:
                        first_row = rows[0]
                        operation_time = first_row.get('operation_datetime', 'N/A')
                        print(f'[日志搜集] 最新记录时间: {operation_time}')
                    
                    pushRst = syncMemberBetLogs(kuaiyi_log, lottery_type, 'kuaiyi')
                    print(f'[日志搜集] ✅ 快译日志推送结果: {pushRst}')
                else:
                    print(f'[日志搜集] ⚠️ 快译日志无Rows数据，Status={kuaiyi_log.get("Status")}, RecordCount={record_count}')
            else:
                print(f'[日志搜集] ⚠️ 快译日志Status异常: {kuaiyi_log.get("Status")}')
            
        except Exception as api_e:
            print(f'[日志搜集] ❌ 接口请求异常: {api_e}')
            import traceback
            traceback.print_exc()
            raise

    except Exception as e:
        print(f'[日志搜集] ❌ 获取用户日志异常: {e}')
        import traceback
        traceback.print_exc()
        try:
            pushErrorLog('获取日bet志异常码异常3：', mainWindow.access_token, lottery_type, e.args)
            if mainWindow.agent_browser:
                agent_cookies = Lucky.getAgentCookieStr(mainWindow.agent_browser, mainWindow)
                mainWindow.agent_cookies = agent_cookies
        except Exception as e2:
            print(f'[日志搜集] ⚠️ 处理异常时出错: {e2}')
    
    print(f'[日志搜集] ============ 日志搜集执行结束 ============')


# 获取用户日志 from api - 定时器
def getUserImportBetDescByApi(inc, mainWindow):
    try:
        # print('======================== 代理用户txt日志 抓取开始 ===============================')
        now_time = str(time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()))
        minutes = int(now_time[-5:-3])
        # print('分钟数：', minutes)
        minutes_d_1 = minutes % 5 - 1  # 分钟数减1 : 6分
        if minutes_d_1 == 0:
            pass
        if isinstance(mainWindow.agent_browser, WebDriver):
            # kuaixuan_log,kuaiyi_log = agentUserBetLogBySession(mainWindow)
            import_log = getImportTxtCodeByCookies(mainWindow)

            # pushRst = syncMemberBetLogs(mainWindow.access_token, kuaixuan_log, lottery_type, 'txt')
            # print('导入日志rst 推送结果 api', pushRst)

    except Exception as e:
        # traceback.print_exc()
        print('获取用户日志异常3：', e.args)
        try:
            pushErrorLog('获取日bet志异常码异常3：', mainWindow.access_token, lottery_type, e.args)
            agent_cookies = Lucky.getAgentCookieStr(mainWindow.agent_browser, mainWindow)
            mainWindow.agent_cookies = agent_cookies
        except Exception as e:
            pass
    # print('======================== 代理用户txt日志 抓取结束 ===============================')
    # MyThreadingTimer.myTimer(inc, getUserImportBetDescByApi, mainWindow)


def agentUserBetLogByCookies(mainWindow):
    # 禁用警告
    requests.packages.urllib3.disable_warnings()

    # 关键修复：验证代理登录所需的变量是否已设置
    if not hasattr(mainWindow, 'agent_cookies') or not mainWindow.agent_cookies:
        raise ValueError("代理登录未完成或cookies未设置，请先完成代理登录")
    if not hasattr(mainWindow, 'agent_user_agent') or not mainWindow.agent_user_agent:
        raise ValueError("代理登录未完成或user_agent未设置，请先完成代理登录")
    
    now_time = str(int(float(time.time()) * 1000))  #
    # 代理浏览器的当前url
    try:
        if mainWindow.agent_browser and mainWindow.agent_browser.current_url:
            current_url = mainWindow.agent_browser.current_url
        else:
            raise NoSuchWindowException('Target window is closed or not found')
    except NoSuchWindowException:
        # 关键修复：如果浏览器不可用，使用agent_domain作为备用
        if hasattr(mainWindow, 'agent_domain') and mainWindow.agent_domain:
            current_url = mainWindow.agent_domain
        elif hasattr(mainWindow, 'agent_domain_val') and mainWindow.agent_domain_val:
            current_url = mainWindow.agent_domain_val.text()
        else:
            raise ValueError("无法获取代理域名，请检查代理登录状态")

    Referer_domain, host = getHostNameData(current_url)
    headers = {
        'Accept': 'application/json, text/javascript, */*; q=0.01',
        'Accept-Encoding': 'gzip, deflate, br, zstd',
        'Accept-Language': 'zh-CN,zh;q=0.9',
        # 'Connection': 'keep-alive',
        'Cookie': mainWindow.agent_cookies,
        # 'Host': host,
        'Priority': 'u = 1, i',
        'Referer': Referer_domain + '/App/Index?_=' + str(now_time),
        # 'Sec-Ch-Ua': '"Google Chrome";v="113", "Chromium";v="113", "Not-A.Brand";v="24"',
        # 'Sec-Ch-Ua-Mobile': '?0',
        # 'Sec-Ch-Ua-Platform': '"Windows"',
        # 'Sec-Fetch-Dest': 'empty',
        # 'Sec-Fetch-Mode': 'cors',
        'Sec-Fetch-Site': 'same-origin',
        'User-Agent': mainWindow.agent_user_agent,
        'X-Requested-With': 'XMLHttpRequest'
    }
    headers = {
        "Accept": "application/json, text/javascript, */*; q=0.01",
        "Accept-Encoding": "gzip, deflate, br", # , zstd
        "Accept-Language": "zh-CN,zh;q=0.9",
        'Cookie': mainWindow.agent_cookies,
        'Referer': Referer_domain + '/App/Index?_=' + str(now_time),
        "Sec-Ch-Ua": "\"Not/A)Brand\";v=\"8\", \"Chromium\";v=\"126\", \"Google Chrome\";v=\"126\"",
        "Sec-Ch-Ua-Mobile": "?0",
        "Sec-Ch-Ua-Platform": "\"Windows\"",
        "Sec-Fetch-Dest": "empty",
        "Sec-Fetch-Mode": "cors",
        "Sec-Fetch-Site": "same-origin",
        'User-Agent': mainWindow.agent_user_agent,
        "X-Requested-With": "XMLHttpRequest"
    }

    params = {
        'link': 'select',
        '_': str(now_time),
        'PageSize': 40  # 与接口返回的PageSize保持一致
    }

    # 快选日志
    kuaixuan_log_url = Referer_domain + '/Log/GetMemberQuickSelectTable?link=select&_=' + str(now_time)
    print(f'[接口请求] 快选日志URL: {kuaixuan_log_url}')
    print(f'[接口请求] 请求参数: {params}')
    response = requests.get(kuaixuan_log_url, headers=headers, params=params, verify=False, timeout=10)
    response.encoding = response.apparent_encoding
    print(f'[接口请求] 快选日志响应状态码: {response.status_code}')
    kxData = Lucky.getPostRstByRstText(response.text)
    print(f'[接口请求] 快选日志解析结果 Status: {kxData.get("Status")}, RecordCount: {kxData.get("Data", {}).get("RecordCount", 0) if kxData.get("Data") else 0}')
    
    # 快译日志
    kuaiyi_log_url = Referer_domain + '/Log/GetMemberQuickSelectTable?link=kuaiyi&way=108&_=' + str(now_time)
    print(f'[接口请求] 快译日志URL: {kuaiyi_log_url}')
    response = requests.get(kuaiyi_log_url, headers=headers, verify=False, timeout=10)
    response.encoding = response.apparent_encoding
    print(f'[接口请求] 快译日志响应状态码: {response.status_code}')
    kyData = Lucky.getPostRstByRstText(response.text)
    print(f'[接口请求] 快译日志解析结果 Status: {kyData.get("Status")}, RecordCount: {kyData.get("Data", {}).get("RecordCount", 0) if kyData.get("Data") else 0}')

    return kxData, kyData


# 导入方式号码搜集
def getImportTxtCodeByCookies(mainWindow):
    allCodes = []
    # 使用 ThreadPoolExecutor 创建线程池
    page_numbers = [1, 2, 3, 4, 5]
    onePageImportTxtLog = getImportTxtOnePageData(mainWindow, 1)
    if onePageImportTxtLog['Status'] == 1:
        all_page_nums = onePageImportTxtLog['Data']['PageCount']
        Rows = onePageImportTxtLog['Data']['Rows']
        for i in range(all_page_nums):
            allCodes.append(Rows[i]['bet_no'])
    with ThreadPoolExecutor() as executor:
        results = list(executor.map(lambda page: getImportTxtOnePageData(mainWindow, page, PageSize=10), page_numbers))

    betDatas = {}
    bet_nos = []
    next_serial_no = ''
    for result in results:
        for row in result['Data']['Rows']:
            current_serial_no = row['serial_no']
            if current_serial_no != next_serial_no:
                if next_serial_no != '':
                    betDatas[next_serial_no] = RstData
                serial_no = results[0]['Data']['Rows'][0]['serial_no']  # 注单编号
                RecordCount = results[0]['Data']['RecordCount']  # 注单编号
                member_account = results[0]['Data']['member_account']  # 账号
                PageCount = results[0]['Data']['PageCount']  # 总页码
                RstData = results[0]
                # del RstData['Data']['Rows']
                RstData['Data']['serial_no'] = serial_no
                RstData['Data']['member_account'] = member_account
                RstData['Data']['AllCodes'] = bet_nos
                RstData['Data']['RecordCount'] = RecordCount
                RstData['Data']['PageCount'] = PageCount
                bet_nos.append(row['bet_no'])
                # 使用列表推导式获取每个 result 中的 'bet_no' 字段
            else:
                # 继续搜集号码
                bet_nos.append(row['bet_no'])

            next_serial_no = current_serial_no

    # 所有号码
    # bet_nos = [row['bet_no'] for result in results for row in result['Data']['Rows']]

    return RstData


# 单页数据
def getImportTxtOnePageData(mainWindow, page=1, PageSize=100):
    current_qihao = mainWindow.current_qihao
    current_qihao = 20230719279  # 期号
    member_account = 'xasd123'  # 会员账号
    import_category = '102'  # 103 导入
    # 禁用警告
    requests.packages.urllib3.disable_warnings()

    # 代理浏览器的当前url
    try:
        if mainWindow.agent_browser and mainWindow.agent_browser.current_url:
            current_url = mainWindow.agent_browser.current_url
        else:
            raise NoSuchWindowException('Target window is closed or not found')
    except NoSuchWindowException:
        current_url = mainWindow.agent_domain

    AgentSession = mainWindow.agent_session
    adapter = requests.adapters.HTTPAdapter(pool_connections=100, pool_maxsize=100)
    AgentSession.mount('https://', adapter)

    Referer_domain, host = getHostNameData(current_url)
    now_time = str(int(float(time.time()) * 1000))  #
    headers = {
        'Accept': 'application/json, text/javascript, */*; q=0.01',
        'Accept-Encoding': 'gzip, deflate, br',
        'Accept-Language': 'zh-CN,zh;q=0.9',
        'Connection': 'keep-alive',
        'Cookie': mainWindow.agent_cookies,
        'Host': host,
        'Referer': Referer_domain + '/App/Index?_=' + str(now_time),
        'Sec-Ch-Ua': '"Google Chrome";v="113", "Chromium";v="113", "Not-A.Brand";v="24"',
        'Sec-Ch-Ua-Mobile': '?0',
        'Sec-Ch-Ua-Platform': '"Windows"',
        'Sec-Fetch-Dest': 'empty',
        'Sec-Fetch-Mode': 'cors',
        'Sec-Fetch-Site': 'same-origin',
        'User-Agent': mainWindow.agent_user_agent,
        'X-Requested-With': 'XMLHttpRequest'
    }
    # 导入日志
    import_log_url = Referer_domain + '/betDetail/GetTotalBetDetail?selectedId=&company_id=&period_no=' + str(
        current_qihao) + \
                     '&is_only_search_win=&member_account=' + member_account + '&bet_no=&range_type=1&range_min=&range_max=&pageindex=' + str(
        page) + \
                     '&category=' + str(import_category) + '&_=' + str(now_time) + '&PageSize=' + str(PageSize)

    # importTxtRst = requests.get(import_log_url, headers=headers, verify=False)
    importTxtRst = AgentSession.get(import_log_url, headers=headers, verify=False)
    importTxtRst.encoding = importTxtRst.apparent_encoding
    onePageImportTxtLog = Lucky.getPostRstByRstText(importTxtRst.text)

    return onePageImportTxtLog


# 自动同步用户信息 - 定时器
def syncUserInfoTimer(inc, mainWindow):
    try:
        now_time = str(time.strftime("%H:%M", time.localtime()))
        accountInfo = getAccountByToken()
        
        # 检查accountInfo是否为None或空
        if not accountInfo:
            print(f"⚠️ getAccountByToken返回空值，跳过本次同步 - {now_time}")
            # 继续执行定时器，下次再试
            MyThreadingTimer.myTimer(inc, syncUserInfoTimer, (inc, mainWindow))
            return
        userInfo = accountInfo['data']
        
        # 检查userInfo是否为None或空
        if not userInfo:
            print(f"⚠️ userInfo为空，跳过本次同步 - {now_time}")
            # 继续执行定时器，下次再试
            MyThreadingTimer.myTimer(inc, syncUserInfoTimer, (inc, mainWindow))
            return

        mainWindow.user_info = userInfo

        if userInfo is not None and userInfo.get('ssc_domain') is not None:
            mainWindow.wp_domain = userInfo['ssc_domain']
            mainWindow.wp_account = userInfo['account']
            mainWindow.wp_password = userInfo['password']

            mainWindow.setWindowTitle('用户界面：' + userInfo['username'])
            # 设置默认值
            mainWindow.domain_val.setText(mainWindow.wp_domain)
            mainWindow.username_val.setText(mainWindow.wp_account)
            mainWindow.pwd_val.setText(mainWindow.wp_password)
            try:
                mainWindow.secure_code = userInfo['secure_code']
                mainWindow.secure_code_val.setText(mainWindow.secure_code)
            except Exception as e:
                pass
            mainWindow.is_test = config.get_config('is_test')

            # 在需要更新界面的地方调用 QTimer.singleShot()，将更新操作放在后台线程中执行
            # QTimer.singleShot(0, lambda: mainWindow.update_token_val(mainWindow.token_val, userInfo, mainWindow.access_token))
            print('✅ 用户信息同步成功')
        else:
            print('⚠️ 用户信息不完整，跳过界面更新')
            # 不抛出异常，继续执行

        # 代理信息
        try:
            print(f"🔍 开始查询代理信息...")
            print(f"   - access_token: {mainWindow.access_token}")
            print(f"   - lottery_type: {lottery_type}")
            
            # 检查access_token是否有效
            if not mainWindow.access_token or mainWindow.access_token == '':
                print("⚠️ access_token为空，跳过代理信息查询")
            else:
                AgentInfo = users.selectUsers({'key': 'access_token', 'value': mainWindow.access_token})
                print(f"📊 代理信息查询结果: {AgentInfo}")
                
                if lottery_type == 8 and AgentInfo:
                    print("✅ 找到代理信息，开始设置界面...")
                    try:
                        id, user_domain, user_account, user_password, user_points, agent_domain, agent_account, agent_password, access_token, desc, updated_at, created_at = AgentInfo
                        
                        # 设置默认值
                        mainWindow.agent_domain = agent_domain
                        mainWindow.agent_domain_val.setText(agent_domain)

                        mainWindow.agent_username = agent_account
                        mainWindow.agent_username_val.setText(agent_account)

                        mainWindow.agent_password = agent_password
                        mainWindow.agent_password_val.setText(agent_password)
                        
                        print(f"✅ 代理信息设置成功:")
                        print(f"   - 代理域名: {agent_domain}")
                        print(f"   - 代理账号: {agent_account}")
                        print(f"   - 代理密码: {'*' * len(agent_password) if agent_password else 'None'}")
                        
                    except Exception as e:
                        print(f"❌ 解析代理信息异常: {e}")
                        print(f"   - AgentInfo结构: {type(AgentInfo)}")
                        if AgentInfo:
                            print(f"   - AgentInfo长度: {len(AgentInfo)}")
                            print(f"   - AgentInfo内容: {AgentInfo}")
                elif lottery_type != 8:
                    print(f"⚠️ 当前彩票类型为 {lottery_type}，不是8，跳过代理信息处理")
                elif not AgentInfo:
                    print("⚠️ 未找到代理信息，可能原因:")
                    print("   1. 数据库中没有对应的代理用户记录")
                    print("   2. access_token不匹配")
                    print("   3. 数据库连接失败")
                    print("   4. users表不存在或结构错误")
                else:
                    print(f"⚠️ 代理信息查询结果异常: {type(AgentInfo)}")
                    
        except Exception as e:
            print(f"❌ 代理信息处理异常: {e}")
            print(f"   异常类型: {type(e)}")
            import traceback
            traceback.print_exc()
        
        print('同步用户信息结束...')
    except Exception as e:
        print('同步用户信息异常00：', str(e.args))
        traceback.print_exc()
        try:
            pushErrorLog('同步用户信息异常3：' + str(mainWindow.current_qihao), mainWindow.access_token, lottery_type, e.args)
        except Exception as e2:
            print(f"⚠️ 推送错误日志失败: {e2}")
    finally:
        # 确保定时器继续运行
        MyThreadingTimer.myTimer(inc, syncUserInfoTimer, (inc, mainWindow))


# 推送用户bet日志
def syncMemberBetLogs(member_bet_logs='', lottery_type=8, from_type='kuaixuan', fromId='api'):
    try:
        url = robot_domain + '/api/agent-clients/sync-member-bet-logs'
        post_data = {'access_token': access_token, 'lottery_type': lottery_type, 'member_bet_logs': member_bet_logs,
                     'from_type': from_type, 'from': fromId}

        headers = {'content-type': 'application/json'}

        print(f'[推送日志] 开始推送 {from_type} 日志到服务器...')
        rst = globalSession.post(url, data=json.dumps(post_data), headers=headers, timeout=30)
        rst.encoding = rst.apparent_encoding
        rst_text = rst.text
        print(f'[推送日志] {from_type} 服务器响应: {rst_text[:200]}')  # 只打印前200字符
        data = Lucky.getPostRstByRstText(rst_text)
        return data

    except Exception as e:
        error_msg = str(e.args) if hasattr(e, 'args') and e.args else str(e)
        print(f'[推送日志] {from_type} 推送异常: {error_msg}')
        pushErrorLog('推送用户bet日志' + str(from_type) + '：', access_token, lottery_type, e.args)
        return {'Status': 0, 'msg': error_msg}
