import os
import sys
import time
import sqlite3
import re

import requests
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

from xy_client.services.tools import tools
from xy_client.services.systems_users.SystemsUsers import pushSyncKjData, judeIsMin
from xy_client.services.tools.Configs import Configs

config = Configs()
robot_domain = config.get_config('robot_domain')
lt_type = config.get_config('lt_type')
official_site = config.get_config('official_site')
# 关键修复：添加默认值处理，避免空字符串导致转换错误
lottery_type_config = config.get_config('lottery_type')
if lottery_type_config and str(lottery_type_config).strip():
    try:
        lottery_type = int(lottery_type_config)
    except (ValueError, TypeError):
        lottery_type = 8  # 默认值
        print(f'⚠️ lottery_type配置值无效: {lottery_type_config}，使用默认值: {lottery_type}')
else:
    lottery_type = 8  # 默认值
    print(f'⚠️ lottery_type配置为空，使用默认值: {lottery_type}')
driver_name = config.get_config('driver_name')
access_token = config.get_config('access_token')

def dbConnect():
    # 数据库
    # 获取应用路径（支持打包后的exe）
    if getattr(sys, 'frozen', False):
        # 打包后的exe
        application_path = os.path.dirname(sys.executable)
    else:
        # 开发环境
        application_path = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
    
    # 确保data目录存在
    data_dir = os.path.join(application_path, 'data')
    if not os.path.exists(data_dir):
        os.makedirs(data_dir)
        print(f'✅ 创建data目录: {data_dir}')
    
    db_path = os.path.join(data_dir, 'lucky.db')
    print(f'数据库路径: {db_path}')
    
    # 如果数据库不存在，创建它
    if not os.path.exists(db_path):
        conn = sqlite3.connect(db_path)
        cursor = conn.cursor()
        # 创建configs表
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS configs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key TEXT UNIQUE NOT NULL,
                value TEXT,
                desc TEXT
            )
        ''')
        # 插入初始数据
        cursor.execute("INSERT OR IGNORE INTO configs (key, value, desc) VALUES ('grab_status', '0', '初始化开关...')")
        cursor.execute("INSERT OR IGNORE INTO configs (key, value, desc) VALUES ('push_enabled', '1', '推送开关：开启')")
        conn.commit()
        conn.close()
        print(f'✅ 创建数据库和初始数据: {db_path}')
    
    conn = sqlite3.connect(db_path)
    return conn


def updateByKey(key, value, desc=''):
    # 数据库
    conn = dbConnect()
    # 创建一个游标
    cursor = conn.cursor()
    # 更新数据
    update_sql = "UPDATE configs set value='" + str(value) + "', desc='" + desc + "' WHERE key='" + key + "'"
    print('update_sql：', update_sql)
    cursor.execute(update_sql)
    conn.commit()
    conn.close()


def selectByKey(key):
    # 数据库
    conn = dbConnect()
    # 创建一个游标
    cursor = conn.cursor()
    # 查找数据
    select_sql = "SELECT * FROM configs WHERE key='" + key + "'"
    print('select_sql：', select_sql)
    cursor.execute(select_sql)
    result = cursor.fetchone()
    conn.close()
    return result


def grabOfficialData(mainWindow):
    """
    从官网抓取开奖数据并推送给后端
    使用多种策略查找开奖数据，提高成功率
    """
    import datetime
    current_time = datetime.datetime.now().strftime('%H:%M:%S')
    
    try:
        # 检查数据库配置
        grab_status_result = selectByKey('grab_status')
        grab_status = grab_status_result[2] if grab_status_result else '0'
        print(f'🔍 [{current_time}] [官网采集] 数据库配置: {grab_status_result}, grab_status value: {grab_status}')
        print(f'📊 [{current_time}] [官网采集] 当前状态: {grab_status} (0=未启动, 1=启动中, 2=启动成功)')
        
        if grab_status != '2':
            print(f'⏸️ [{current_time}] [官网采集] 数据开关未开启 (状态={grab_status})')
            return
        
        print(f'✅ [{current_time}] [官网采集] 开始从官网抓取数据...')
        
        try:
            # 关键修复：检查driver和窗口是否有效
            try:
                # 检查窗口句柄是否存在
                window_handles = mainWindow.driver.window_handles
                if not window_handles:
                    print(f'❌ [{current_time}] [官网采集] 浏览器窗口已关闭，无法采集')
                    return
                
                # 切换到第一个窗口（确保在正确的窗口）
                mainWindow.driver.switch_to.window(window_handles[0])
                window_count = len(window_handles)
                print(f'🔍 [{current_time}] [官网采集] 浏览器窗口数量: {window_count}')
            except Exception as window_e:
                print(f'❌ [{current_time}] [官网采集] 检查浏览器窗口失败: {window_e}')
                print(f'⚠️ [{current_time}] [官网采集] 浏览器可能已关闭，跳过本次采集')
                return
            
            if lt_type == 'lucky5':
                # 关键修复：增加等待时间，确保页面内容完全加载（SPA应用需要等待JavaScript执行）
                try:
                    # 步骤1：检查页面是否还在加载，并等待JavaScript执行
                    print(f'🔍 [{current_time}] [官网采集] 检查页面加载状态...')
                    
                    # 关键修复：简化等待逻辑，避免无限等待
                    ready_state = mainWindow.driver.execute_script("return document.readyState")
                    print(f'✅ [{current_time}] [官网采集] 页面readyState: {ready_state}')
                    
                    # 检查窗口是否还存在
                    if not mainWindow.driver.window_handles:
                        print(f'❌ [{current_time}] [官网采集] 浏览器窗口已关闭')
                        return
                    
                    # 检查是否有app元素（SPA应用）
                    try:
                        has_app = mainWindow.driver.execute_script("return document.getElementById('app') !== null")
                        print(f'✅ [{current_time}] [官网采集] 检查app元素: {has_app}')
                    except:
                        has_app = False
                    
                    # 如果页面已经加载完成，直接继续
                    if ready_state == 'complete':
                        print(f'✅ [{current_time}] [官网采集] 页面已加载完成，等待3秒确保SPA渲染...')
                        time.sleep(3)  # 等待3秒确保SPA完全渲染
                    else:
                        # 如果页面还没加载完成，最多等待10秒
                        print(f'⏳ [{current_time}] [官网采集] 页面还在加载，等待最多10秒...')
                        for i in range(10):
                            time.sleep(1)
                            ready_state = mainWindow.driver.execute_script("return document.readyState")
                            if ready_state == 'complete':
                                print(f'✅ [{current_time}] [官网采集] 页面加载完成（等待了{i+1}秒）')
                                time.sleep(2)  # 额外等待2秒确保SPA渲染
                                break
                            if i % 3 == 0:  # 每3秒输出一次
                                print(f'⏳ [{current_time}] [官网采集] 页面加载中... (readyState={ready_state}, 已等待{i+1}秒)')
                    
                    # 步骤2：等待页面元素出现，使用多种策略查找开奖数据
                    print(f'⏳ [{current_time}] [官网采集] 等待页面元素出现（最多60秒）...')
                    wait = WebDriverWait(mainWindow.driver, 60)
                    
                    # 额外等待SPA渲染完成
                    time.sleep(5)
                    print(f'⏳ [{current_time}] [官网采集] 额外等待5秒，确保SPA渲染完成...')
                    
                    # 关键修复：同时从页面和API接口采集，比较期号，选择最新的推送
                    # 这样可以防止页面崩溃导致采集不到号码
                    page_qihao = None
                    page_kjCodes = None
                    api_qihao = None
                    api_kjCodes = None
                    
                    # 策略1：从页面顶部最新开奖区域获取数据（websocket实时更新）
                    try:
                        print(f'🔍 [{current_time}] [官网采集] 策略1：从页面顶部最新开奖区域获取数据（websocket实时更新）...')
                        
                        # 等待最新开奖区域出现
                        result_wait = WebDriverWait(mainWindow.driver, 30)
                        
                        # 最新开奖区域的定位策略
                        result_selectors = [
                            '//div[contains(@class, "result-box")]',  # 最新开奖区域
                            '//div[contains(@class, "result-bg")]',  # 备用选择器
                        ]
                        
                        for selector in result_selectors:
                            try:
                                # 查找最新开奖区域
                                result_box = result_wait.until(
                                    EC.presence_of_element_located((By.XPATH, selector))
                                )
                                
                                print(f'✅ [{current_time}] [官网采集] 找到最新开奖区域，开始提取数据...')
                                
                                # 获取期号：从 <p class="top-periods-1"> 元素
                                try:
                                    # 查找包含"期开奖"的元素
                                    period_elements = result_box.find_elements(By.XPATH, './/p[contains(@class, "top-periods-1")]')
                                    if not period_elements:
                                        # 备用：查找包含"期开奖"文本的元素
                                        period_elements = result_box.find_elements(By.XPATH, './/p[contains(text(), "期开奖")]')
                                    
                                    if period_elements:
                                        period_text = period_elements[0].text.strip()
                                        # 提取期号：例如 "20251127258期开奖" -> "20251127258"
                                        period_match = re.search(r'(\d+)期', period_text)
                                        if period_match:
                                            page_qihao = period_match.group(1)  # 已经包含日期前缀
                                            print(f'✅ [{current_time}] [官网采集] 从顶部区域获取期号: {page_qihao} (原始文本: {period_text})')
                                        else:
                                            # 如果没有"期"字，尝试提取所有数字
                                            period_match = re.search(r'(\d{11,})', period_text)
                                            if period_match:
                                                page_qihao = period_match.group(1)
                                                print(f'✅ [{current_time}] [官网采集] 从顶部区域获取期号: {page_qihao} (原始文本: {period_text})')
                                except Exception as period_e:
                                    print(f'⚠️ [{current_time}] [官网采集] 获取期号失败: {period_e}')
                                
                                # 获取开奖号码：从 <div class="row ball-row"> 中的 <p class="text"> 元素
                                try:
                                    # 查找ball-row区域
                                    ball_row = result_box.find_element(By.XPATH, './/div[contains(@class, "ball-row")]')
                                    
                                    # 查找所有ball-box中的text元素
                                    ball_elements = ball_row.find_elements(By.XPATH, './/div[contains(@class, "ball-box")]//p[contains(@class, "text")]')
                                    
                                    if not ball_elements:
                                        # 备用：直接查找ball中的text
                                        ball_elements = ball_row.find_elements(By.XPATH, './/div[contains(@class, "ball")]//p[contains(@class, "text")]')
                                    
                                    if ball_elements:
                                        numbers = []
                                        for ball_elem in ball_elements:
                                            ball_text = ball_elem.text.strip()
                                            if ball_text.isdigit() and len(ball_text) == 1:
                                                numbers.append(ball_text)
                                            if len(numbers) >= 5:  # 幸运五是5个数字
                                                break
                                        
                                        if len(numbers) >= 5:
                                            page_kjCodes = ','.join(numbers[:5])
                                            print(f'✅ [{current_time}] [官网采集] 从顶部区域获取号码: {page_kjCodes} (找到{len(numbers)}个数字)')
                                        else:
                                            print(f'⚠️ [{current_time}] [官网采集] 找到的数字不足5个: {numbers}')
                                    else:
                                        print(f'⚠️ [{current_time}] [官网采集] 未找到ball-row区域或号码元素')
                                except Exception as ball_e:
                                    print(f'⚠️ [{current_time}] [官网采集] 获取开奖号码失败: {ball_e}')
                                
                                if page_qihao and page_kjCodes:
                                    print(f'✅ [{current_time}] [官网采集] 策略1（页面）成功：期号={page_qihao}, 号码={page_kjCodes}')
                                    break
                                else:
                                    print(f'⚠️ [{current_time}] [官网采集] 策略1（页面）数据不完整：期号={page_qihao}, 号码={page_kjCodes}')
                                    
                            except Exception as result_e:
                                print(f'⚠️ [{current_time}] [官网采集] 选择器 {selector} 失败: {result_e}')
                                continue
                        
                    except Exception as top_strategy_e:
                        print(f'⚠️ [{current_time}] [官网采集] 策略1失败: {top_strategy_e}')
                    
                    # 策略2：同时从API接口获取数据（并行采集，不依赖页面）
                    try:
                        print(f'🔍 [{current_time}] [官网采集] 策略2：从API接口获取最新开奖数据（并行采集）...')
                        
                        # API接口：https://web01.cc138001.com/kaijiang/history/ygxy5.json
                        api_url = 'https://web01.cc138001.com/kaijiang/history/ygxy5.json'
                        timestamp = int(time.time() * 1000)
                        api_url_with_params = f'{api_url}?v={timestamp}&'
                        
                        try:
                            # 发送GET请求
                            response = requests.get(api_url_with_params, timeout=(5, 10))
                            
                            if response.status_code == 200:
                                api_data = response.json()
                                
                                # 检查返回数据格式
                                if api_data.get('code') == 0 and api_data.get('data') and api_data['data'].get('list'):
                                    # 获取第一条数据（最新开奖数据）
                                    latest_item = api_data['data']['list'][0]
                                    
                                    # 提取开奖号码
                                    draw_code = latest_item.get('draw_code', '')
                                    if draw_code:
                                        api_kjCodes = draw_code  # 已经是逗号分隔的格式，例如："0,0,4,7,2"
                                    
                                    # 提取期号（从pc_issue中提取）
                                    pc_issue = latest_item.get('pc_issue', [])
                                    if pc_issue and len(pc_issue) > 0:
                                        # pc_issue[0] 格式：'20251127256期'
                                        issue_text = pc_issue[0]
                                        # 提取期号数字（去掉"期"字）
                                        period_match = re.search(r'(\d+)', issue_text)
                                        if period_match:
                                            api_qihao = period_match.group(1)  # 例如：20251127256
                                    
                                    if api_qihao and api_kjCodes:
                                        print(f'✅ [{current_time}] [官网采集] API接口获取成功：期号={api_qihao}, 号码={api_kjCodes}')
                                    else:
                                        print(f'⚠️ [{current_time}] [官网采集] API接口返回数据不完整：期号={api_qihao}, 号码={api_kjCodes}')
                                else:
                                    print(f'⚠️ [{current_time}] [官网采集] API接口返回格式异常: {api_data}')
                            else:
                                print(f'⚠️ [{current_time}] [官网采集] API接口请求失败: status_code={response.status_code}')
                        except Exception as api_e:
                            print(f'⚠️ [{current_time}] [官网采集] API接口请求异常: {api_e}')
                    
                    except Exception as api_strategy_e:
                        print(f'⚠️ [{current_time}] [官网采集] 策略2（API）失败: {api_strategy_e}')
                    
                    # 关键修复：比较两个方式获取的期号，选择最新的推送
                    qihao = None
                    kjCodes = None
                    
                    if page_qihao and page_kjCodes and api_qihao and api_kjCodes:
                        # 两个方式都成功，比较期号，选择最新的
                        try:
                            page_period = int(page_qihao)
                            api_period = int(api_qihao)
                            
                            if page_period >= api_period:
                                qihao = page_qihao
                                kjCodes = page_kjCodes
                                print(f'✅ [{current_time}] [官网采集] 页面期号更新（{page_qihao} >= {api_qihao}），使用页面数据')
                            else:
                                qihao = api_qihao
                                kjCodes = api_kjCodes
                                print(f'✅ [{current_time}] [官网采集] API期号更新（{api_qihao} > {page_qihao}），使用API数据')
                        except Exception as compare_e:
                            print(f'⚠️ [{current_time}] [官网采集] 比较期号失败: {compare_e}，使用页面数据')
                            qihao = page_qihao
                            kjCodes = page_kjCodes
                    elif page_qihao and page_kjCodes:
                        # 只有页面成功
                        qihao = page_qihao
                        kjCodes = page_kjCodes
                        print(f'✅ [{current_time}] [官网采集] 仅页面采集成功，使用页面数据：期号={qihao}, 号码={kjCodes}')
                    elif api_qihao and api_kjCodes:
                        # 只有API成功
                        qihao = api_qihao
                        kjCodes = api_kjCodes
                        print(f'✅ [{current_time}] [官网采集] 仅API采集成功，使用API数据：期号={qihao}, 号码={kjCodes}')
                    else:
                        print(f'⚠️ [{current_time}] [官网采集] 页面和API都采集失败，将尝试备用方案')
                    
                    # 策略3：如果顶部区域也失败，从历史列表区域获取（备用方案）
                    if not qihao or not kjCodes:
                        try:
                            print(f'🔍 [{current_time}] [官网采集] 策略3：从历史列表区域获取最新开奖数据...')
                            
                            # 等待列表区域出现
                            list_wait = WebDriverWait(mainWindow.driver, 30)
                            
                            # 根据实际HTML结构，列表区域是：<div class="row table-content center-a weex-ct weex-div">
                            # 第一行就是最新的开奖数据
                            list_selectors = [
                                '//div[contains(@class, "table-content") and contains(@class, "center-a")]',  # 第一行（最新）
                                '//div[@class="row table-content center-a weex-ct weex-div"]',  # 精确匹配
                                '//div[contains(@class, "table-content")]',  # 宽松匹配
                            ]
                            
                            for selector in list_selectors:
                                try:
                                    # 查找第一个列表项（最新开奖数据）
                                    first_row = list_wait.until(
                                        EC.presence_of_element_located((By.XPATH, selector))
                                    )
                                    
                                    print(f'✅ [{current_time}] [官网采集] 找到历史列表区域，开始提取数据...')
                                    
                                    # 获取期号：第一个<p class="text-content">元素
                                    try:
                                        period_elements = first_row.find_elements(By.XPATH, './/p[contains(@class, "text-content")]')
                                        if period_elements:
                                            # 第一个text-content是期号
                                            qihao_text = period_elements[0].text.strip()
                                            # 提取期号数字（例如：250, 249等）
                                            period_match = re.search(r'(\d+)', qihao_text)
                                            if period_match:
                                                period_number = period_match.group(1)
                                                
                                                # 关键修复：期号前面要加日期，格式：YYYYMMDD + 期号
                                                # 例如：250 -> 20251127250
                                                import datetime
                                                today = datetime.datetime.now()
                                                date_prefix = today.strftime('%Y%m%d')  # 例如：20251127
                                                qihao = date_prefix + period_number  # 例如：20251127250
                                                
                                                print(f'✅ [{current_time}] [官网采集] 从历史列表获取期号: {qihao} (原始文本: {qihao_text}, 日期前缀: {date_prefix})')
                                    except Exception as period_e:
                                        print(f'⚠️ [{current_time}] [官网采集] 获取期号失败: {period_e}')
                                        import traceback
                                        traceback.print_exc()
                                    
                                    # 获取开奖号码：所有<div class="ball">中的<p class="ball-text">元素
                                    try:
                                        # 查找所有ball元素
                                        ball_elements = first_row.find_elements(By.XPATH, './/div[contains(@class, "ball")]//p[contains(@class, "ball-text")]')
                                        if ball_elements:
                                            numbers = []
                                            for ball_elem in ball_elements:
                                                ball_text = ball_elem.text.strip()
                                                if ball_text.isdigit() and len(ball_text) == 1:
                                                    numbers.append(ball_text)
                                                if len(numbers) >= 5:  # 幸运五是5个数字
                                                    break
                                            
                                            if len(numbers) >= 5:
                                                kjCodes = ','.join(numbers[:5])
                                                print(f'✅ [{current_time}] [官网采集] 从历史列表获取号码: {kjCodes} (找到{len(numbers)}个数字)')
                                            else:
                                                print(f'⚠️ [{current_time}] [官网采集] 找到的数字不足5个: {numbers}')
                                    except Exception as ball_e:
                                        print(f'⚠️ [{current_time}] [官网采集] 获取开奖号码失败: {ball_e}')
                                    
                                    if qihao and kjCodes:
                                        print(f'✅ [{current_time}] [官网采集] 策略3成功：期号={qihao}, 号码={kjCodes}')
                                        break
                                    else:
                                        print(f'⚠️ [{current_time}] [官网采集] 策略3数据不完整：期号={qihao}, 号码={kjCodes}')
                                        
                                except Exception as list_e:
                                    print(f'⚠️ [{current_time}] [官网采集] 选择器 {selector} 失败: {list_e}')
                                    continue
                            
                        except Exception as table_strategy_e:
                            print(f'⚠️ [{current_time}] [官网采集] 策略3失败: {table_strategy_e}')
                            import traceback
                            traceback.print_exc()
                    
                    # 策略4：从当前开奖区域获取（如果策略1、2、3都失败）
                    if not qihao or not kjCodes:
                        try:
                            print(f'🔍 [{current_time}] [官网采集] 策略2：从当前开奖区域获取数据...')
                            
                            # 查找包含"期开奖"的标题元素
                            title_selectors = [
                                '//*[contains(text(), "期开奖")]',
                                '//*[contains(text(), "期") and contains(text(), "开奖")]',
                                '//h1[contains(text(), "期")]',
                                '//h2[contains(text(), "期")]',
                                '//div[contains(text(), "期开奖")]',
                            ]
                            
                            for selector in title_selectors:
                                try:
                                    title_element = wait.until(
                                        EC.presence_of_element_located((By.XPATH, selector))
                                    )
                                    title_text = title_element.text
                                    
                                    # 提取期号
                                    period_match = re.search(r'(\d{8,11})', title_text)
                                    if period_match:
                                        qihao = period_match.group(1)
                                        print(f'✅ [{current_time}] [官网采集] 从标题获取期号: {qihao}')
                                        break
                                except:
                                    continue
                            
                            # 查找开奖号码（红色圆圈或数字）
                            number_selectors = [
                                '//*[contains(@class, "number")]',
                                '//*[contains(@class, "ball")]',
                                '//span[contains(@class, "red")]',
                                '//div[contains(@class, "result")]',
                            ]
                            
                            for selector in number_selectors:
                                try:
                                    number_elements = mainWindow.driver.find_elements(By.XPATH, selector)
                                    if number_elements:
                                        numbers = []
                                        for elem in number_elements[:10]:  # 最多取10个
                                            text = elem.text.strip()
                                            if text.isdigit() and len(text) == 1:
                                                numbers.append(text)
                                            if len(numbers) >= 5:
                                                break
                                        
                                        if len(numbers) >= 5:
                                            kjCodes = ','.join(numbers[:5])
                                            print(f'✅ [{current_time}] [官网采集] 从号码元素获取: {kjCodes}')
                                            break
                                except:
                                    continue
                            
                            if qihao and kjCodes:
                                print(f'✅ [{current_time}] [官网采集] 策略2成功：期号={qihao}, 号码={kjCodes}')
                                
                        except Exception as current_strategy_e:
                            print(f'⚠️ [{current_time}] [官网采集] 策略2失败: {current_strategy_e}')
                    
                    # 策略5：使用JavaScript直接获取页面数据（最后备用方案）
                    if not qihao or not kjCodes:
                        try:
                            print(f'🔍 [{current_time}] [官网采集] 策略3：使用JavaScript获取页面数据...')
                            
                            # 执行JavaScript获取页面文本内容
                            page_text = mainWindow.driver.execute_script("return document.body.innerText;")
                            
                            # 从文本中提取期号
                            period_matches = re.findall(r'(\d{8,11})期', page_text)
                            if period_matches:
                                qihao = period_matches[0]
                                print(f'✅ [{current_time}] [官网采集] 从页面文本获取期号: {qihao}')
                            
                            # 从文本中提取开奖号码（查找5个连续的数字）
                            number_pattern = r'(\d)\s*[,，]?\s*(\d)\s*[,，]?\s*(\d)\s*[,，]?\s*(\d)\s*[,，]?\s*(\d)'
                            number_matches = re.findall(number_pattern, page_text)
                            if number_matches:
                                kjCodes = ','.join(number_matches[0][:5])
                                print(f'✅ [{current_time}] [官网采集] 从页面文本获取号码: {kjCodes}')
                            
                            if qihao and kjCodes:
                                print(f'✅ [{current_time}] [官网采集] 策略3成功：期号={qihao}, 号码={kjCodes}')
                                
                        except Exception as js_strategy_e:
                            print(f'⚠️ [{current_time}] [官网采集] 策略3失败: {js_strategy_e}')
                    
                    # 如果所有策略都失败，输出调试信息
                    if not qihao or not kjCodes:
                        print(f'❌ [{current_time}] [官网采集] 所有策略都失败，输出调试信息...')
                        try:
                            # 输出页面标题和URL
                            print(f'🔍 [{current_time}] [官网采集] 页面标题: {mainWindow.driver.title}')
                            print(f'🔍 [{current_time}] [官网采集] 页面URL: {mainWindow.driver.current_url}')
                            
                            # 输出页面源码的前1000个字符
                            page_source = mainWindow.driver.page_source[:1000]
                            print(f'🔍 [{current_time}] [官网采集] 页面源码（前1000字符）: {page_source}')
                            
                            # 尝试查找所有包含数字的元素
                            all_elements = mainWindow.driver.find_elements(By.XPATH, '//*[text()[contains(., "期") or contains(., "开奖")]]')
                            print(f'🔍 [{current_time}] [官网采集] 找到 {len(all_elements)} 个包含"期"或"开奖"的元素')
                            for i, elem in enumerate(all_elements[:5]):
                                try:
                                    print(f'  元素{i+1}: {elem.text[:50]}')
                                except:
                                    pass
                        except Exception as debug_e:
                            print(f'⚠️ [{current_time}] [官网采集] 输出调试信息失败: {debug_e}')
                        
                        print(f'❌ [{current_time}] [官网采集] 无法找到开奖数据，跳过本次采集')
                        return
                    
                    if not qihao or not kjCodes:
                        print(f'⚠️ [{current_time}] [官网采集] 期号或开奖号码为空，跳过本次采集')
                        return
                    
                except Exception as find_e:
                    print(f'❌ [{current_time}] [官网采集] 查找元素异常: {find_e}')
                    import traceback
                    traceback.print_exc()
                    return
            else:
                print(f'⚠️ [{current_time}] [官网采集] 暂不支持彩种: {lt_type}，仅支持 lucky5')
                return
            
            # 关键修复：更新UI显示最新开奖数据
            # 只有当采集到新的期号时，才更新采集时间
            is_new_period = False
            if hasattr(mainWindow, 'latest_qihao'):
                # 检查是否是新的期号
                if mainWindow.latest_qihao != qihao:
                    is_new_period = True
                    print(f'🆕 [{current_time}] [官网采集] 检测到新期号: {mainWindow.latest_qihao} -> {qihao}')
                else:
                    print(f'🔄 [{current_time}] [官网采集] 同一期号，不更新采集时间: {qihao}')
                mainWindow.latest_qihao = qihao
            else:
                # 如果没有保存的期号，说明是第一次采集
                is_new_period = True
                print(f'🆕 [{current_time}] [官网采集] 首次采集期号: {qihao}')
            
            if hasattr(mainWindow, 'latest_kjCodes'):
                mainWindow.latest_kjCodes = kjCodes
            
            # 关键修复：只有新期号时才更新采集时间（包含秒数）
            if hasattr(mainWindow, 'latest_collect_time'):
                if is_new_period:
                    # 格式化采集时间：YYYY-MM-DD HH:MM:SS（包含秒数）
                    import datetime
                    collect_time_str = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                    mainWindow.latest_collect_time = collect_time_str
                    print(f'⏰ [{current_time}] [官网采集] 记录首次采集时间: {collect_time_str}')
                else:
                    # 同一期号，保持原有采集时间不变
                    print(f'⏰ [{current_time}] [官网采集] 保持原有采集时间: {mainWindow.latest_collect_time}')
            elif is_new_period:
                # 如果没有保存的采集时间，且是新期号，则创建
                import datetime
                collect_time_str = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                mainWindow.latest_collect_time = collect_time_str
                print(f'⏰ [{current_time}] [官网采集] 首次记录采集时间: {collect_time_str}')
            
            if hasattr(mainWindow, 'updateDataDisplay'):
                try:
                    mainWindow.updateDataDisplay("采集成功")
                except Exception as ui_e:
                    print(f'⚠️ [{current_time}] [官网采集] 更新UI显示失败: {ui_e}')
            
            # 构建推送数据（包含精确到秒的采集时间）
            import datetime
            collect_time_precise = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            pushData = {'expect': qihao, 'opencode': kjCodes, 'opentime': collect_time_precise}
            print(f'📤 [{current_time}] [官网采集] 准备推送数据: {pushData}')
            
            # 关键修复：使用UI中的推送开关控制是否推送
            # 从mainWindow获取推送开关状态
            enable_push = True  # 默认开启
            if mainWindow is None:
                print(f'❌ [{current_time}] [官网采集] mainWindow对象为None，使用默认值: enable_push={enable_push}')
            elif not hasattr(mainWindow, 'push_enabled'):
                print(f'⚠️ [{current_time}] [官网采集] mainWindow没有push_enabled属性，使用默认值: enable_push={enable_push}')
                print(f'🔍 [{current_time}] [官网采集] mainWindow属性列表（前20个）: {[attr for attr in dir(mainWindow) if not attr.startswith("_")][:20]}')
            else:
                # 关键修复：确保获取到的是布尔值
                push_enabled_value = mainWindow.push_enabled
                # 转换为布尔值（处理可能的字符串或其他类型）
                if isinstance(push_enabled_value, bool):
                    enable_push = push_enabled_value
                elif isinstance(push_enabled_value, str):
                    enable_push = push_enabled_value.lower() in ('true', '1', 'yes', 'on')
                elif isinstance(push_enabled_value, int):
                    enable_push = bool(push_enabled_value)
                else:
                    enable_push = bool(push_enabled_value)
                
                print(f'🔍 [{current_time}] [官网采集] 推送开关状态检查: push_enabled原始值={push_enabled_value} (type={type(push_enabled_value)}), 转换后enable_push={enable_push}')
            
            if enable_push:
                # 推送到后端
                pushRst = pushSyncKjData(mainWindow.access_token, kj_datas=pushData, lottery_type=lottery_type, fromId='official')
                
                if pushRst:
                    print(f'✅ [{current_time}] [官网采集] 推送结果: status={pushRst.get("status")}, data={pushRst.get("data")}')
                    
                    # 关键修复：禁用自动刷新，避免页面变成空白
                    if pushRst.get('data'):
                        data = pushRst['data']
                        num = int(data.get('num', 0))
                        refresh = data.get('refresh', False)
                        
                        # 关键修复：禁用自动刷新，避免页面变成空白
                        if False:  # 禁用自动刷新
                            if num > 15 or refresh:
                                print(f'🔄 [{current_time}] [官网采集] 检测到需要刷新 (num={num}, refresh={refresh})，但已禁用自动刷新')
                                print(f'💡 [{current_time}] [官网采集] 提示：自动刷新已禁用，避免页面变成空白')
                        else:
                            # 只记录，不刷新
                            if num > 15 or refresh:
                                print(f'📊 [{current_time}] [官网采集] 后端建议刷新 (num={num}, refresh={refresh})，但已禁用自动刷新以避免页面空白')
                else:
                    print(f'⚠️ [{current_time}] [官网采集] 推送返回结果为空')
            else:
                print(f'🔧 [{current_time}] [官网采集] 调试模式：推送功能已屏蔽，仅输出数据: {pushData}')
                print(f'💡 [{current_time}] [官网采集] 提示：推送功能已关闭，请在界面中点击"推送：关闭"按钮开启推送')
                
            print(f'✅ [{current_time}] [官网采集] 本次采集完成')
        except Exception as grab_e:
            print(f'❌ [{current_time}] [官网采集] 抓取数据异常: {grab_e}')
            import traceback
            traceback.print_exc()
    except Exception as e:
        current_time = datetime.datetime.now().strftime('%H:%M:%S')
        print(f'❌ [{current_time}] [官网采集] 异常: {e}')
        import traceback
        traceback.print_exc()
