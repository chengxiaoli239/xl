"""
盘口平台API接口封装
用于通过盘口API获取余额、检查登录状态等，减少对WebDriver的依赖
"""

import time
import json
from typing import Optional, Dict, Any
from xy_client.services.tools.GlobalSession import GlobalSession
from xy_client.services.systems_users.SystemsUsers import getHeaderData
from xy_client.services.Lucky5.utils.log_optimizer import optimized_print

globalSession = GlobalSession().get_session()


def get_balance_by_api(mainWindow) -> Optional[float]:
    """
    通过盘口API获取余额（使用cookie）
    
    Args:
        mainWindow: 主窗口实例
    
    Returns:
        float: 余额，如果获取失败返回None
    """
    try:
        # 获取cookie
        cookies = getattr(mainWindow, 'browser_cookies', None)
        if not cookies:
            optimized_print("⚠️ [PlatformAPI] 无cookie，无法获取余额",
                           category='platform_api', level='WARNING')
            return None
        
        # 获取headerData
        headerData = getHeaderData()
        if not headerData or not headerData.get('cookies'):
            optimized_print("⚠️ [PlatformAPI] 无法获取headerData",
                           category='platform_api', level='WARNING')
            return None
        
        # 构建cookie字符串
        if isinstance(cookies, list):
            cookies_str = '; '.join([f"{c.get('name', '')}={c.get('value', '')}" for c in cookies])
        elif isinstance(cookies, str):
            cookies_str = cookies.strip().rstrip(';')
        else:
            cookies_str = str(cookies).strip().rstrip(';')
        
        # 构建请求头
        now_time = str(int(float(time.time()) * 1000))
        v1 = headerData.get('v1', '99')
        v2 = headerData.get('v2', '99')
        
        headers = {
            'Accept': 'application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding': 'gzip, deflate, br',
            'Accept-Language': 'zh-CN,zh;q=0.9',
            'Connection': 'close',
            'Cookie': cookies_str,
            'Referer': headerData.get('Referer', '') + now_time,
            'sec-ch-ua': f'"Chromium";v="{v2}", " Not A;Brand";v="{v1}", "Google Chrome";v="{v2}"',
            'Host': headerData.get('Host', ''),
            'User-Agent': headerData.get('user_agent', ''),
            'X-Requested-With': 'XMLHttpRequest',
        }
        
        # 调用盘口API获取用户信息（包含余额）
        wp_domain = getattr(mainWindow, 'wp_domain', '')
        if not wp_domain:
            optimized_print("⚠️ [PlatformAPI] 无wp_domain，无法获取余额",
                           category='platform_api', level='WARNING')
            return None
        
        url = f"{wp_domain}/Member/GetMemberPrint?_={now_time}"
        
        # 发送请求
        response = globalSession.get(url, headers=headers, timeout=(2, 10))
        response.encoding = response.apparent_encoding
        
        # 解析响应
        if response.status_code != 200:
            optimized_print(f"⚠️ [PlatformAPI] 获取余额失败，HTTP状态码: {response.status_code}",
                           category='platform_api', level='WARNING')
            return None
        
        # 检查响应内容
        response_text = response.text
        if not response_text:
            optimized_print("⚠️ [PlatformAPI] 获取余额失败，响应为空",
                           category='platform_api', level='WARNING')
            return None
        
        # 解析JSON
        try:
            rst = json.loads(response_text)
        except json.JSONDecodeError:
            optimized_print(f"⚠️ [PlatformAPI] 获取余额失败，JSON解析错误: {response_text[:100]}",
                           category='platform_api', level='WARNING')
            return None
        
        # 检查状态
        if rst.get('Status') == 1:
            # 成功，提取余额
            data = rst.get('Data', {})
            credit_balance = data.get('credit_balance', 0)
            try:
                balance = float(credit_balance)
                optimized_print(f"✅ [PlatformAPI] 成功获取余额: {balance}",
                               category='platform_api', level='DEBUG')
                return balance
            except (ValueError, TypeError):
                optimized_print(f"⚠️ [PlatformAPI] 余额格式错误: {credit_balance}",
                               category='platform_api', level='WARNING')
                return None
        else:
            # 失败，可能是未登录或cookie无效
            msg = rst.get('msg', '未知错误')
            optimized_print(f"⚠️ [PlatformAPI] 获取余额失败，Status != 1: {msg}",
                           category='platform_api', level='WARNING')
            return None
            
    except Exception as e:
        optimized_print(f"❌ [PlatformAPI] 获取余额异常: {e}",
                       category='platform_api', level='ERROR')
        return None


def check_login_status_by_api(mainWindow) -> bool:
    """
    通过盘口API检查登录状态（使用cookie）
    
    Args:
        mainWindow: 主窗口实例
    
    Returns:
        bool: True表示已登录，False表示未登录
    """
    try:
        # 获取cookie
        cookies = getattr(mainWindow, 'browser_cookies', None)
        if not cookies:
            optimized_print("⚠️ [PlatformAPI] 无cookie，无法检查登录状态",
                           category='platform_api', level='WARNING')
            return False
        
        # 获取headerData
        headerData = getHeaderData()
        if not headerData or not headerData.get('cookies'):
            optimized_print("⚠️ [PlatformAPI] 无法获取headerData",
                           category='platform_api', level='WARNING')
            return False
        
        # 构建cookie字符串
        if isinstance(cookies, list):
            cookies_str = '; '.join([f"{c.get('name', '')}={c.get('value', '')}" for c in cookies])
        elif isinstance(cookies, str):
            cookies_str = cookies.strip().rstrip(';')
        else:
            cookies_str = str(cookies).strip().rstrip(';')
        
        # 构建请求头
        now_time = str(int(float(time.time()) * 1000))
        v1 = headerData.get('v1', '99')
        v2 = headerData.get('v2', '99')
        
        headers = {
            'Accept': 'application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding': 'gzip, deflate, br',
            'Accept-Language': 'zh-CN,zh;q=0.9',
            'Connection': 'close',
            'Cookie': cookies_str,
            'Referer': headerData.get('Referer', '') + now_time,
            'sec-ch-ua': f'"Chromium";v="{v2}", " Not A;Brand";v="{v1}", "Google Chrome";v="{v2}"',
            'Host': headerData.get('Host', ''),
            'User-Agent': headerData.get('user_agent', ''),
            'X-Requested-With': 'XMLHttpRequest',
        }
        
        # 调用盘口API检查登录状态
        wp_domain = getattr(mainWindow, 'wp_domain', '')
        if not wp_domain:
            optimized_print("⚠️ [PlatformAPI] 无wp_domain，无法检查登录状态",
                           category='platform_api', level='WARNING')
            return False
        
        url = f"{wp_domain}/Member/GetMemberPrint?_={now_time}"
        
        # 发送请求
        response = globalSession.get(url, headers=headers, timeout=(2, 10))
        response.encoding = response.apparent_encoding
        
        # 检查HTTP状态码
        if response.status_code in [401, 403]:
            optimized_print(f"⚠️ [PlatformAPI] 登录状态检查失败，HTTP状态码: {response.status_code}",
                           category='platform_api', level='WARNING')
            return False
        
        if response.status_code != 200:
            optimized_print(f"⚠️ [PlatformAPI] 登录状态检查失败，HTTP状态码: {response.status_code}",
                           category='platform_api', level='WARNING')
            return False
        
        # 解析响应
        try:
            rst = json.loads(response.text)
        except json.JSONDecodeError as json_err:
            # JSON解析失败，可能是未登录或响应格式不对
            optimized_print(f"⚠️ [PlatformAPI] 登录状态检查失败，JSON解析错误: {json_err}",
                           category='platform_api', level='WARNING')
            optimized_print(f"🔍 [PlatformAPI] 响应内容（前200字符）: {response.text[:200]}",
                           category='platform_api', level='DEBUG')
            return False
        
        # 检查状态：Status == 1 表示已登录
        status = rst.get('Status')
        if status == 1:
            optimized_print("✅ [PlatformAPI] 登录状态检查：已登录",
                           category='platform_api', level='DEBUG')
            return True
        else:
            # 获取错误信息
            msg = rst.get('msg', rst.get('message', '未知错误'))
            optimized_print(f"⚠️ [PlatformAPI] 登录状态检查：未登录，Status={status}, msg={msg}",
                           category='platform_api', level='WARNING')
            # 如果响应中有Data字段，也记录一下（可能包含有用信息）
            if 'Data' in rst:
                optimized_print(f"🔍 [PlatformAPI] 响应Data: {str(rst.get('Data'))[:100]}",
                               category='platform_api', level='DEBUG')
            return False
            
    except Exception as e:
        optimized_print(f"❌ [PlatformAPI] 检查登录状态异常: {e}",
                       category='platform_api', level='ERROR')
        return False

