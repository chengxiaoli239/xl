import json
import sys
import time
import traceback
import requests
import threading

from xy_client.services.tools.Configs import Configs
from xy_client.services.tools.GlobalSession import GlobalSession

globalSession = GlobalSession().get_session()
config = Configs()
robot_domain = config.get_config('robot_domain')
print('lottery_type', config.get_config('lottery_type'))
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
access_token = config.get_config('access_token')

# 添加请求缓存机制
_request_cache = {}
_cache_lock = threading.Lock()

# 添加请求频率控制
_request_timestamps = {}
_rate_limit_lock = threading.Lock()

def get_cached_response(cache_key, cache_timeout=30):
    """获取缓存的响应数据"""
    with _cache_lock:
        if cache_key in _request_cache:
            cache_time, cache_data = _request_cache[cache_key]
            if time.time() - cache_time < cache_timeout:
                return cache_data
            else:
                # 缓存过期，删除
                del _request_cache[cache_key]
        
        # 智能清理：如果缓存项超过50个，清理最旧的20%
        if len(_request_cache) > 50:
            _cleanup_old_cache()
    
    return None

def _cleanup_old_cache():
    """清理最旧的缓存项"""
    try:
        # 按时间排序，保留最新的80%
        sorted_items = sorted(_request_cache.items(), key=lambda x: x[1][0], reverse=True)
        keep_count = int(len(sorted_items) * 0.8)
        items_to_remove = sorted_items[keep_count:]
        
        for key, _ in items_to_remove:
            del _request_cache[key]
        
        print(f'🔄 缓存清理完成，保留 {keep_count} 项，清理 {len(items_to_remove)} 项')
    except Exception as e:
        print(f'⚠️ 缓存清理异常: {e}')

def set_cached_response(cache_key, data):
    """设置缓存的响应数据"""
    with _cache_lock:
        _request_cache[cache_key] = (time.time(), data)

def get_cache_key(func_name, params):
    """生成缓存键"""
    return f"{func_name}_{hash(str(params))}"

def check_rate_limit(func_name, min_interval=5):
    """检查请求频率限制"""
    with _rate_limit_lock:
        current_time = time.time()
        if func_name in _request_timestamps:
            last_time = _request_timestamps[func_name]
            if current_time - last_time < min_interval:
                remaining = min_interval - (current_time - last_time)
                #rint(f'请求频率限制: {func_name} 需要等待 {remaining:.1f} 秒')
                return False
        
        _request_timestamps[func_name] = current_time
        return True

def log_request_frequency(func_name):
    """记录请求频率信息"""
    with _rate_limit_lock:
        if func_name in _request_timestamps:
            last_time = _request_timestamps[func_name]
            elapsed = time.time() - last_time
            print(f'距离上次 {func_name} 请求: {elapsed:.1f} 秒')

def get_cache_status():
    """获取缓存状态信息"""
    with _cache_lock:
        cache_count = len(_request_cache)
        if cache_count > 0:
            oldest_time = min(item[1][0] for item in _request_cache.items())
            newest_time = max(item[1][0] for item in _request_cache.items())
            oldest_age = time.time() - oldest_time
            newest_age = time.time() - newest_time
            
            print(f'📊 缓存状态: 共 {cache_count} 项')
            print(f'   最旧缓存: {oldest_age:.1f} 秒前')
            print(f'   最新缓存: {newest_age:.1f} 秒前')
        else:
            print('📊 缓存状态: 无缓存项')
    
    with _rate_limit_lock:
        rate_count = len(_request_timestamps)
        if rate_count > 0:
            print(f'📊 频率控制: 监控 {rate_count} 个函数')
        else:
            print('📊 频率控制: 无监控函数')


def log_message(message, message2=''):
    print(message)
    sys.stdout.flush()


def getAccountByToken(token=''):
    # 检查请求频率限制 - 增加到5分钟间隔
    if not check_rate_limit('getAccountByToken', min_interval=300):  # 最少间隔5分钟
        return get_cached_response(get_cache_key('getAccountByToken', {'token': token}), cache_timeout=600)
    
    # 检查缓存 - 增加到10分钟缓存
    cache_key = get_cache_key('getAccountByToken', {'token': token})
    cached_data = get_cached_response(cache_key, cache_timeout=600)  # 10分钟缓存
    if cached_data:
        print(f'使用缓存的用户信息 (缓存时间: {time.strftime("%H:%M:%S", time.localtime())})')
        return cached_data
    
    # 记录请求频率
    log_request_frequency('getAccountByToken')
    
    data = {}
    max_retries = 3
    retry_delay = 2
    
    for attempt in range(max_retries):
        try:
            url = robot_domain + '/api/index/get-user-info-by-token'
            print('getAccountByToken_url:', url)
            global access_token
            access_token = token if token != '' else access_token
            post_data = {'access_token': access_token}
            headers = {'content-type': 'application/json'}

            # 设置超时时间，避免长时间等待，禁用SSL验证
            rst = requests.post(url, data=json.dumps(post_data), headers=headers, proxies={}, timeout=10, verify=False)
            data = rst.json()
            print(time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()))
            
            # 缓存成功的结果
            if data and 'status' in data and data['status'] == 200:
                set_cached_response(cache_key, data)
                print(f'✅ 用户信息已缓存，有效期10分钟')
            
            break  # 成功则跳出重试循环

        except requests.exceptions.ConnectionError as e:
            if attempt < max_retries - 1:
                print(f'网络连接失败 (尝试 {attempt + 1}/{max_retries}): {str(e)}')
                print(f'等待 {retry_delay} 秒后重试...')
                time.sleep(retry_delay)
                retry_delay *= 2  # 指数退避
            else:
                print(f'网络连接失败，已达到最大重试次数: {str(e)}')
                traceback.print_exc()
        except requests.exceptions.Timeout as e:
            if attempt < max_retries - 1:
                print(f'请求超时 (尝试 {attempt + 1}/{max_retries}): {str(e)}')
                print(f'等待 {retry_delay} 秒后重试...')
                time.sleep(retry_delay)
                retry_delay *= 2
            else:
                print(f'请求超时，已达到最大重试次数: {str(e)}')
                traceback.print_exc()
        except Exception as e:
            print(f'其他错误: {str(e)}')
            traceback.print_exc()
            break  # 其他错误不重试

    # 确保返回有效的数据结构
    if not data or 'status' not in data:
        print("⚠️ 网络请求失败，返回默认数据结构")
        return {
            'status': 500,
            'msg': '网络请求失败',
            'data': {}
        }
    
    return data


# 推送错误日志
def pushErrorLog(qihao='', access_token='', lottery_type=8, err_log=None):
    if err_log is None:
        err_log = {}
    
    # 检查缓存，避免重复推送相同错误
    cache_key = get_cache_key('pushErrorLog', {'qihao': qihao, 'err_log': str(err_log), 'lottery_type': lottery_type})
    cached_data = get_cached_response(cache_key, cache_timeout=300)  # 5分钟缓存
    if cached_data:
        print(f'错误日志已缓存，跳过重复推送 (缓存时间: {time.strftime("%H:%M:%S", time.localtime())})')
        return cached_data
    
    max_retries = 3
    retry_delay = 2
    
    for attempt in range(max_retries):
        try:
            url = robot_domain + '/api/tz-system-users/push-err-log'
            post_data = {'access_token': access_token, 'qihao': qihao, 'err_log': err_log, 'lottery_type': lottery_type}

            headers = {'content-type': 'application/json'}

            # 设置超时时间
            rst = globalSession.post(url, data=json.dumps(post_data), headers=headers, timeout=10)
            data = rst.json()
            
            # 缓存成功的结果
            if data and 'status' in data and data['status'] == 200:
                set_cached_response(cache_key, data)
                print(f'错误日志推送成功，已缓存')
            
            break  # 成功则跳出重试循环

        except Exception as e:
            if attempt < max_retries - 1:
                print(f'推送错误日志失败 (尝试 {attempt + 1}/{max_retries}): {str(e)}')
                print(f'等待 {retry_delay} 秒后重试...')
                time.sleep(retry_delay)
                retry_delay *= 2
            else:
                print(f'推送错误日志失败，已达到最大重试次数: {str(e)}')
                data = {'status': 300, 'msg': e.args}

    return data


def synReportData(data, data_type='week'):
    """
    推送报表日志
    """
    max_retries = 3
    retry_delay = 2
    
    for attempt in range(max_retries):
        try:
            url = robot_domain + '/api/agent-clients/sync-report-data'
            post_data = {'access_token': access_token, 'lottery_type': lottery_type, 'data': data, 'data_type': data_type}
            headers = {'content-type': 'application/json'}

            # 设置超时时间，禁用SSL验证
            rst = requests.post(url, data=json.dumps(post_data), headers=headers, timeout=10, verify=False)
            data = rst.json()

            print('pushErrorLog:', data)
            return data

        except requests.exceptions.ConnectionError as e:
            if attempt < max_retries - 1:
                print(f'推送报表日志网络连接失败 (尝试 {attempt + 1}/{max_retries}): {str(e)}')
                print(f'等待 {retry_delay} 秒后重试...')
                time.sleep(retry_delay)
                retry_delay *= 2
            else:
                print(f'推送报表日志网络连接失败，已达到最大重试次数: {str(e)}')
                pushErrorLog('推送用户报表日志' + str(data_type) + '：', access_token, lottery_type, e.args)
                return False
        except requests.exceptions.Timeout as e:
            if attempt < max_retries - 1:
                print(f'推送报表日志请求超时 (尝试 {attempt + 1}/{max_retries}): {str(e)}')
                print(f'等待 {retry_delay} 秒后重试...')
                time.sleep(retry_delay)
                retry_delay *= 2
            else:
                print(f'推送报表日志请求超时，已达到最大重试次数: {str(e)}')
                pushErrorLog('推送用户报表日志' + str(data_type) + '：', access_token, lottery_type, e.args)
                return False
        except Exception as e:
            print(f'推送报表日志其他错误: {str(e)}')
            pushErrorLog('推送用户报表日志' + str(data_type) + '：', access_token, lottery_type, e.args)
            return False

    return False
