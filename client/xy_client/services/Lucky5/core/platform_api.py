"""
盘口平台API接口封装
用于通过盘口API获取余额、检查登录状态等，减少对WebDriver的依赖
"""

import time
import json
import re
from urllib.parse import urlsplit
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


def _cookie_header(cookies):
    if isinstance(cookies, list):
        return '; '.join(
            f"{cookie.get('name', '')}={cookie.get('value', '')}"
            for cookie in cookies
            if cookie.get('name')
        )
    if isinstance(cookies, str):
        return cookies.strip().rstrip(';')
    return str(cookies).strip().rstrip(';')


def _build_status_headers(mainWindow, cookies, now_time):
    """Build probe headers from the current CDP session."""
    wp_domain = (getattr(mainWindow, 'wp_domain', '') or '').rstrip('/')
    parts = urlsplit(wp_domain)
    base_url = f"{parts.scheme}://{parts.netloc}" if parts.scheme and parts.netloc else wp_domain
    user_agent = getattr(mainWindow, 'browser_user_agent', '') or (
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
        'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'
    )
    version_match = re.search(r'(?:Chrome|Chromium)/(\d+)', user_agent)
    chrome_version = version_match.group(1) if version_match else '142'
    return {
        'Accept': 'application/json, text/javascript, */*; q=0.01',
        'Accept-Encoding': 'gzip, deflate, br',
        'Accept-Language': 'zh-CN,zh;q=0.9',
        'Connection': 'close',
        'Cookie': _cookie_header(cookies),
        'Referer': f"{base_url}/Member/Index?_={now_time}",
        'sec-ch-ua': f'"Chromium";v="{chrome_version}", "Google Chrome";v="{chrome_version}"',
        'Host': parts.netloc,
        'User-Agent': user_agent,
        'X-Requested-With': 'XMLHttpRequest',
    }


def _response_confirms_logout(response_text):
    text = (response_text or '').lower()
    return any(marker in text for marker in (
        '/member/login', '请登录', '请重新登录', '登录已过期',
        'session expired', 'session invalid',
    ))


def check_login_status_by_api(mainWindow) -> Optional[bool]:
    """
    通过盘口API检查登录状态（使用cookie）
    
    Args:
        mainWindow: 主窗口实例
    
    Returns:
        Optional[bool]: True表示已登录，False表示明确未登录，None表示本次无法判断
    """
    try:
        # 获取cookie
        cookies = getattr(mainWindow, 'browser_cookies', None)
        if not cookies:
            optimized_print("⚠️ [PlatformAPI] 无cookie，无法检查登录状态",
                           category='platform_api', level='WARNING')
            return False
        
        # 构建请求头
        now_time = str(int(float(time.time()) * 1000))
        headers = _build_status_headers(mainWindow, cookies, now_time)
        
        # 调用盘口API检查登录状态
        wp_domain = getattr(mainWindow, 'wp_domain', '')
        if not wp_domain:
            optimized_print("⚠️ [PlatformAPI] 无wp_domain，无法检查登录状态",
                           category='platform_api', level='WARNING')
            return None
        
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
            return None
        
        # 解析响应
        try:
            rst = json.loads(response.text)
        except json.JSONDecodeError as json_err:
            # JSON解析失败，可能是未登录或响应格式不对
            optimized_print(f"⚠️ [PlatformAPI] 登录状态检查失败，JSON解析错误: {json_err}",
                           category='platform_api', level='WARNING')
            optimized_print(f"🔍 [PlatformAPI] 响应内容（前200字符）: {response.text[:200]}",
                           category='platform_api', level='DEBUG')
            return False if _response_confirms_logout(response.text) else None
        
        # 检查状态：Status == 1 表示已登录
        status = rst.get('Status')
        try:
            status = int(status)
        except (TypeError, ValueError):
            pass
        if status == 1:
            optimized_print("✅ [PlatformAPI] 登录状态检查：已登录",
                           category='platform_api', level='DEBUG')
            return True
        else:
            msg = rst.get('msg', rst.get('message', ''))
            payload_text = json.dumps({'msg': msg, 'data': rst.get('Data', '')}, ensure_ascii=False)
            if status == 5 or _response_confirms_logout(payload_text):
                optimized_print(f"⚠️ [PlatformAPI] 登录状态检查：明确未登录，Status={status}, msg={msg}",
                               category='platform_api', level='WARNING')
                return False
            optimized_print(f"⚠️ [PlatformAPI] 登录状态检查：响应无法确认，Status={status}, msg={msg}",
                           category='platform_api', level='WARNING')
            return None
            
    except Exception as e:
        optimized_print(f"❌ [PlatformAPI] 检查登录状态异常: {e}",
                       category='platform_api', level='ERROR')
        return None
