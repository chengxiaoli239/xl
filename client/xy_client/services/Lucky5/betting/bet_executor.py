"""
下注执行器
负责执行具体的下注操作
"""

import time
import threading
from typing import Dict, Any, Optional, List
from urllib import parse

from xy_client.services.Lucky5.core import StateManager, BrowserManager, ErrorHandler
from xy_client.services.Lucky5.Lucky import localBet, getPostRstByRstText
from xy_client.services.systems_users.SystemsUsers import pushTasksBetRst, pushErrorLog
from xy_client.services.tools import tools
from xy_client.services.tools.GlobalSession import GlobalSession
from xy_client.services.Lucky5.utils.log_optimizer import optimized_print

globalSession = GlobalSession().get_session()


class BetExecutor:
    """下注执行器 - 执行具体的下注操作"""
    
    def __init__(self, main_window, state_manager: StateManager, 
                 browser_manager: BrowserManager, error_handler: ErrorHandler):
        """
        初始化下注执行器
        
        Args:
            main_window: 主窗口实例
            state_manager: 状态管理器
            browser_manager: 浏览器管理器
            error_handler: 错误处理器
        """
        self.main_window = main_window
        self.state_manager = state_manager
        self.browser_manager = browser_manager
        self.error_handler = error_handler
    
    def execute_bets(self, plan_data: Dict[str, Any], direct: int = 1, 
                    session=None) -> bool:
        """
        执行下注任务
        
        Args:
            plan_data: 下注计划数据
            direct: 下注方向
            session: 请求会话（可选）
        
        Returns:
            bool: 是否成功执行
        """
        sorted_data = self._sort_plan_data(plan_data.get('data', []))
        
        success_count = 0
        error_count = 0
        
        current_session = session if session else globalSession
        
        # 关键优化：连接预热机制优化
        # 即使是单个任务，也需要轻量级预热，避免第一次请求慢（建立连接耗时）
        # 使用域名级别的预热，避免URL级别的预热导致延迟
        total_count = len(sorted_data)
        if sorted_data and len(sorted_data) > 0:
            first_bet = sorted_data[0]
            if first_bet.get('plan_type') != 'local' and first_bet.get('bet_url'):
                try:
                    from urllib.parse import urlparse
                    bet_url = first_bet['bet_url']
                    parsed_url = urlparse(bet_url)
                    domain = f"{parsed_url.scheme}://{parsed_url.netloc}"
                    
                    # 检查是否已经预热过（避免每次都预热）
                    if not hasattr(self.main_window, '_connection_warmed'):
                        self.main_window._connection_warmed = set()
                    
                    # 关键优化：使用域名级别的预热key，而不是URL级别
                    # 这样同一个域名的所有请求都可以复用预热连接，避免第一次慢
                    if domain not in self.main_window._connection_warmed:
                        # 关键修复：第一次下注前，同步等待连接预热完成，避免第一次请求超时
                        # 使用HEAD请求到根路径，超时时间1秒，快速建立连接
                        try:
                            session = current_session
                            optimized_print(f"🔥 [BetExecutor] 开始连接预热: {domain}",
                                           category='bet_executor', level='DEBUG')
                            
                            # 同步预热：确保第一次下注前连接已建立（优化：缩短超时时间，提高响应速度）
                            try:
                                response = session.head(f"{domain}/", timeout=(0.5, 1))  # 连接0.5秒，读取1秒（优化：从1秒/2秒缩短）
                                if response.status_code < 500:
                                    self.main_window._connection_warmed.add(domain)
                                    optimized_print(f"✅ [BetExecutor] 连接预热成功: {domain}",
                                                   category='bet_executor', level='DEBUG')
                                else:
                                    optimized_print(f"⚠️ [BetExecutor] 连接预热响应异常: {response.status_code}",
                                                   category='bet_executor', level='DEBUG')
                            except Exception as warm_error:
                                # 预热失败不影响下注，但记录日志
                                optimized_print(f"⚠️ [BetExecutor] 连接预热失败: {warm_error}，继续下注",
                                               category='bet_executor', level='DEBUG')
                        except Exception:
                            # 预热失败不影响下注，静默失败
                            pass
                except Exception:
                    # 预热失败不影响下注，静默失败
                    pass
        
        # 关键优化：批量下注分批处理，避免一次性处理太多任务导致卡住或被踢下线
        # 每批处理30个任务（从20增加到30，减少批次数量，提高速度），处理完一批后检查窗口和状态，防止被踢下线
        batch_size = 30  # 每批处理30个任务（优化：从20增加到30，提高下注速度）
        
        # 如果任务数量很大，输出提示
        if total_count > 50:
            optimized_print(f"📊 [BetExecutor] 开始批量下注: 共{total_count}个任务，将分{((total_count-1)//batch_size)+1}批处理",
                           category='bet_executor', level='INFO', force=True)
        
        # 分批处理任务
        for batch_start in range(0, total_count, batch_size):
            batch_end = min(batch_start + batch_size, total_count)
            batch_data = sorted_data[batch_start:batch_end]
            batch_num = (batch_start // batch_size) + 1
            total_batches = ((total_count - 1) // batch_size) + 1
            
            # 每批开始前检查窗口和状态
            if batch_start > 0:  # 第一批不需要检查，后续批次需要
                self._check_and_cleanup_windows()
                self._keep_alive_activity()  # 防踢机制：保持活动状态
            
            # 执行当前批次的任务
            for index_in_batch, data in enumerate(batch_data):
                index = batch_start + index_in_batch
                try:
                    result = self._execute_single_bet(data, index, direct, current_session, total_count)
                    if result['success']:
                        success_count += 1
                    else:
                        error_count += 1
                        
                        # 如果遇到Cookie无效错误，需要停止下注
                        if result.get('cookie_invalid', False):
                            optimized_print(f"❌ [BetExecutor] Cookie无效，停止下注任务",
                                           category='bet_executor', level='ERROR', force=True)
                            return False
                        
                except Exception as e:
                    error_count += 1
                    self._handle_bet_exception(e, direct)
            
            # 每批完成后输出进度
            if total_count > batch_size:
                optimized_print(f"📊 [BetExecutor] 批次进度: {batch_num}/{total_batches} 完成 (成功{success_count}个, 失败{error_count}个)",
                               category='bet_executor', level='INFO', force=True)
            
            # 批次间短暂延迟，避免请求过快被限制（优化：进一步缩短到0.1秒，提高速度）
            if batch_end < total_count:
                time.sleep(0.1)  # 批次间延迟0.1秒（优化：从0.2秒缩短到0.1秒）
        
        # 打印执行结果（只在有结果或错误时输出）
        if success_count > 0 or error_count > 0:
            current_time = time.strftime('%H:%M:%S', time.localtime())
            optimized_print(f"{current_time} [BetExecutor] 下注任务执行完成 [direct={direct}]: 成功{success_count}个, 失败{error_count}个",
                           category='bet_executor', level='INFO', force=True)  # 执行结果强制输出
        
        return success_count > 0
    
    def _sort_plan_data(self, data_list: List[Dict]) -> List[Dict]:
        """
        对下注计划数据按bet_money排序
        
        Args:
            data_list: 下注计划数据列表
        
        Returns:
            List[Dict]: 排序后的数据列表
        """
        def safe_bet_money_key(x):
            try:
                if x['plan_type'] == 'local':
                    bet_money = x['local_data'].get('bet_money', 0)
                else:
                    bet_money = x['post_data'].get('bet_money', 0)
                
                # 安全转换为浮点数
                if bet_money is None or bet_money == '':
                    return 0.0
                return float(bet_money)
            except (ValueError, TypeError, KeyError):
                return 0.0
        
        return sorted(data_list, key=safe_bet_money_key, reverse=True)
    
    def _execute_single_bet(self, data: Dict[str, Any], index: int, 
                           direct: int, session, total_count: int = 1) -> Dict[str, Any]:
        """
        执行单个下注任务
        
        Args:
            data: 下注任务数据
            index: 任务索引
            direct: 下注方向
            session: 请求会话
            total_count: 总任务数量（用于日志输出）
        
        Returns:
            Dict: 执行结果
        """
        # 优化：移除线程锁，因为外层已经有互斥锁保护（bet_task_manager中的_bet_task_lock）
        # 移除锁可以减少锁竞争，提高并发性能
        start_time = time.time()
        
        # 准备请求头（记录准备时间）
        prepare_start = time.time()
        headers_dict = data['headers'].copy()
        cookies_value = self.state_manager.get_browser_cookies()
        
        if cookies_value:
            if isinstance(cookies_value, list):
                cookies_str = '; '.join([f"{cookie.get('name', '')}={cookie.get('value', '')}" for cookie in cookies_value])
                headers_dict['Cookie'] = cookies_str
            elif isinstance(cookies_value, str):
                headers_dict['Cookie'] = cookies_value.strip().rstrip(';')
            else:
                headers_dict['Cookie'] = str(cookies_value).strip().rstrip(';')
        prepare_time = time.time() - prepare_start
        
        # 执行下注
        if data['plan_type'] == 'local':
            postRst = localBet(self.main_window, data['local_data'])
        else:
            # 只在有多个任务或首次执行时输出日志
            if total_count > 1 or index == 0:
                optimized_print(f"开始执行第{index+1}个下注任务 [direct={direct}]",
                               category='bet_executor', level='INFO')
            
            # 自动重试机制：超时后立即重试一次，避免等待定时器重启
            max_retries = 2  # 最多重试2次（首次+1次重试）
            last_exception = None
            
            for retry_count in range(max_retries):
                try:
                    # 在执行网络请求前更新心跳
                    if hasattr(self.main_window, '_bet_last_heartbeat'):
                        import time as _time
                        self.main_window._bet_last_heartbeat = _time.time()
                    
                    # 记录请求开始时间
                    request_start = time.time()
                    # 优化：平衡速度和稳定性，连接超时1.5秒，读取超时12秒
                    # 如果盘口卡顿超过12秒，可能是真的卡住，需要重试或跳过
                    response = session.post(
                        data['bet_url'],
                        data=parse.urlencode(data['post_data']),
                        headers=headers_dict,
                        verify=False,
                        timeout=(1, 12)  # 优化：连接超时1.5秒，读取超时12秒（从2秒/15秒缩短，提高速度）
                    )
                    request_time = time.time() - request_start
                    
                    # 检查HTTP状态码：401/403 通常表示未登录
                    if response.status_code in [401, 403]:
                        optimized_print(f"⚠️ [BetExecutor] 检测到HTTP登录错误（状态码={response.status_code}），立即停止下注并触发登录",
                                       category='bet_executor', level='WARNING', force=True)
                        # 构造一个错误响应对象
                        error_postRst = {
                            'Status': 0,
                            'code': 310,
                            'msg': f'HTTP {response.status_code}: 未登录或登录已过期'
                        }
                        self._handle_cookie_invalid(data, error_postRst, start_time)
                        return {'success': False, 'cookie_invalid': True}
                    
                    # 网络请求完成后立即更新心跳
                    if hasattr(self.main_window, '_bet_last_heartbeat'):
                        import time as _time
                        self.main_window._bet_last_heartbeat = _time.time()
                    
                    # 记录处理响应时间
                    process_start = time.time()
                    response.encoding = response.apparent_encoding
                    postRst = getPostRstByRstText(response.text)
                    process_time = time.time() - process_start
                    
                    # 输出详细时间统计（只在首次或总时间超过5秒时输出）
                    total_elapsed = time.time() - start_time
                    if total_elapsed > 5 or index == 0:
                        optimized_print(f"⏱️ [BetExecutor] 下注任务时间统计: 准备{prepare_time:.2f}s, 请求{request_time:.2f}s, 处理{process_time:.2f}s, 总计{total_elapsed:.2f}s",
                                       category='bet_executor', level='INFO', force=True)
                    
                    # 成功则跳出重试循环
                    break
                except Exception as e:
                    last_exception = e
                    # 检查是否是连接重置错误（ConnectionResetError 10054）
                    error_str = str(e).lower()
                    is_connection_reset = (
                        'connection aborted' in error_str or 
                        'connectionreseterror' in error_str or 
                        '10054' in error_str or
                        '远程主机强迫关闭' in error_str
                    )
                    is_timeout = 'timeout' in error_str or 'timed out' in error_str
                    
                    # 关键修复：连接重置错误需要快速恢复浏览器连接
                    if is_connection_reset:
                        optimized_print(f"⚠️ [BetExecutor] 第{index+1}个下注任务连接重置错误，尝试恢复浏览器连接...",
                                       category='bet_executor', level='WARNING', force=True)
                        # 尝试恢复浏览器连接
                        try:
                            if hasattr(self.main_window, 'browser_manager'):
                                self.main_window.browser_manager.check_and_recover_browser_connection(silent=False)
                        except Exception as recover_error:
                            optimized_print(f"⚠️ [BetExecutor] 恢复浏览器连接失败: {recover_error}",
                                           category='bet_executor', level='WARNING', force=True)
                        
                        # 连接重置错误可以重试（如果还有重试次数）
                        if retry_count < max_retries - 1:
                            optimized_print(f"⚠️ [BetExecutor] 第{index+1}个下注任务连接重置，立即重试 ({retry_count + 1}/{max_retries})",
                                           category='bet_executor', level='WARNING', force=True)
                            time.sleep(0.2)  # 等待0.2秒，让连接恢复（优化：从0.5秒缩短到0.2秒）
                            continue
                        else:
                            # 已用完重试次数，抛出异常
                            raise
                    
                    # 优化：超时处理策略
                    # 1. 如果超时时间较短（<12秒），可能是网络问题，可以重试
                    # 2. 如果超时时间较长（>=12秒），可能是盘口卡顿，需要谨慎处理，避免重复下注
                    elapsed = time.time() - request_start if 'request_start' in locals() else 0
                    
                    if retry_count < max_retries - 1 and is_timeout:
                        # 超时时间较长（>=12秒），可能是盘口卡顿，请求可能已经成功
                        # 这种情况下不重试，避免重复下注，直接抛出异常让上层处理
                        if elapsed >= 12:
                            optimized_print(f"⚠️ [BetExecutor] 第{index+1}个下注任务长时间超时（耗时{elapsed:.2f}s），可能是盘口卡顿，不重试避免重复下注",
                                           category='bet_executor', level='WARNING', force=True)
                            # 不重试，直接抛出异常，让上层判断是否已经下注成功
                            raise
                        else:
                            # 超时时间较短（<12秒），可能是网络问题，可以重试
                            optimized_print(f"⚠️ [BetExecutor] 第{index+1}个下注任务超时（耗时{elapsed:.2f}s），立即重试 ({retry_count + 1}/{max_retries})",
                                           category='bet_executor', level='WARNING', force=True)
                            # 优化：延迟0.2秒，给网络恢复时间（从0.5秒缩短到0.2秒，提高响应速度）
                            time.sleep(0.2)
                            continue
                    else:
                        # 非超时错误或已用完重试次数，抛出异常
                        raise
        
        # 处理结果
        result = self._process_bet_result(postRst, data, start_time, direct)
        
        # 控制下注间隔
        self._wait_bet_interval(data)
        
        return result
    
    def _process_bet_result(self, postRst: Dict[str, Any], data: Dict[str, Any], 
                           start_time: float, direct: int) -> Dict[str, Any]:
        """
        处理下注结果
        
        Args:
            postRst: 下注响应结果
            data: 下注任务数据
            start_time: 开始时间
            direct: 下注方向
        
        Returns:
            Dict: 处理结果
        """
        # 安全地比较Status值
        post_status = postRst.get('Status')
        if isinstance(post_status, str):
            post_status = int(post_status) if post_status.strip() else 0
        elif post_status is None:
            post_status = 0
        else:
            post_status = int(post_status)
        
        if post_status == 1:
            # 下注成功
            postRst.update({
                'task_status': 2,
                'bet_time': time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()),
                'sn': '6666666666',
                'snid': '6666666666id',
                'time_consume': f"{time.time() - start_time:.2f}s",
                'now_time': time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())
            })
            pushTasksBetRst(data['plan_id'], data['qihao'], postRst)
            return {'success': True}
        else:
            # 下注失败
            post_code = postRst.get('code')
            if isinstance(post_code, str):
                post_code = int(post_code) if post_code.strip() else 0
            elif post_code is None:
                post_code = 0
            else:
                post_code = int(post_code)
            
            # 检查是否是Cookie无效错误（post_code == 310）
            if post_code == 310:
                self._handle_cookie_invalid(data, postRst, start_time)
                return {'success': False, 'cookie_invalid': True}
            
            # 检查响应消息中是否包含登录相关错误
            msg = postRst.get('msg', '').lower()
            is_login_error = (
                '未登录' in msg or
                '需要登录' in msg or
                '请登录' in msg or
                'login' in msg and ('required' in msg or 'please' in msg) or
                'session' in msg and ('expired' in msg or 'invalid' in msg) or
                'cookie' in msg and ('invalid' in msg or 'expired' in msg)
            )
            
            # 如果检测到登录相关错误，立即触发登录
            if is_login_error:
                optimized_print(f"⚠️ [BetExecutor] 检测到登录错误（下注响应）: {postRst.get('msg', '未知错误')}",
                               category='bet_executor', level='WARNING', force=True)
                self._handle_login_error(data, postRst, start_time)
                return {'success': False, 'cookie_invalid': True}
            
            # 其他错误
            postRst.update({
                'task_status': 3,
                'err_msg': postRst.get('msg', '未知错误'),
                'time_consume': f"{time.time() - start_time:.2f}s",
                'now_time': time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())
            })
            pushTasksBetRst(data['plan_id'], data['qihao'], postRst)
            return {'success': False}
    
    def _handle_cookie_invalid(self, data: Dict[str, Any], postRst: Dict[str, Any], 
                              start_time: float):
        """
        处理Cookie无效错误（post_code == 310）
        
        Args:
            data: 下注任务数据
            postRst: 下注响应结果
            start_time: 开始时间
        """
        optimized_print(f"❌ [BetExecutor] 检测到Cookie无效（post_code=310），立即停止下注并触发登录",
                       category='bet_executor', level='ERROR', force=True)
        
        access_token = getattr(self.main_window, 'access_token', '')
        lottery_type = getattr(self.main_window, 'lottery_type', 8)
        
        # 记录错误日志
        pushErrorLog('下注异常重新登录时间：', access_token, lottery_type,
                    {'msg': 'Cookie无效，自动登录', 'login_time': time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()),
                    'current_url': self.main_window.driver.current_url if self.state_manager.has_browser_driver() else ''})
        
        # 推送错误结果
        postRst.update({
            'task_status': 3,
            'err_msg': 'Cookie无效，已停止下注任务并触发自动登录',
            'time_consume': f"{time.time() - start_time:.2f}s",
            'now_time': time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())
        })
        pushTasksBetRst(data['plan_id'], data['qihao'], postRst)
        
        # 立即触发登录（使用LoginStatusMonitor）
        self._trigger_immediate_login()
    
    def _handle_login_error(self, data: Dict[str, Any], postRst: Dict[str, Any], 
                            start_time: float):
        """
        处理登录相关错误（从响应消息中检测到）
        
        Args:
            data: 下注任务数据
            postRst: 下注响应结果
            start_time: 开始时间
        """
        optimized_print(f"❌ [BetExecutor] 检测到登录错误（响应消息），立即停止下注并触发登录",
                       category='bet_executor', level='ERROR', force=True)
        
        access_token = getattr(self.main_window, 'access_token', '')
        lottery_type = getattr(self.main_window, 'lottery_type', 8)
        
        # 记录错误日志
        pushErrorLog('下注异常重新登录时间：', access_token, lottery_type,
                    {'msg': '登录错误，自动登录', 'error_msg': postRst.get('msg', '未知错误'),
                    'login_time': time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()),
                    'current_url': self.main_window.driver.current_url if self.state_manager.has_browser_driver() else ''})
        
        # 推送错误结果
        postRst.update({
            'task_status': 3,
            'err_msg': f"登录错误：{postRst.get('msg', '未知错误')}，已停止下注任务并触发自动登录",
            'time_consume': f"{time.time() - start_time:.2f}s",
            'now_time': time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())
        })
        pushTasksBetRst(data['plan_id'], data['qihao'], postRst)
        
        # 立即触发登录（使用LoginStatusMonitor）
        self._trigger_immediate_login()
    
    def _trigger_immediate_login(self):
        """
        立即触发登录操作（使用LoginStatusMonitor或Lucky.loginClient）
        """
        try:
            # 设置登录状态为需要登录
            if hasattr(self.main_window, 'is_need_login'):
                self.main_window.is_need_login = 0
                optimized_print(f"🔄 [BetExecutor] 已设置 is_need_login=0，准备触发登录",
                               category='bet_executor', level='INFO', force=True)

            if getattr(self.main_window, 'runtime_mode', 'browser') == 'background':
                optimized_print(
                    "后台HTTP模式的盘口会话已失效，请勾选“打开浏览器登录”后点击登录",
                    category='bet_executor', level='WARNING', force=True
                )
                return
            
            # 优先使用LoginStatusMonitor（如果存在）
            if hasattr(self.main_window, '_login_status_monitor'):
                optimized_print(f"🔄 [BetExecutor] 通过LoginStatusMonitor立即触发登录",
                               category='bet_executor', level='INFO', force=True)
                self.main_window._login_status_monitor.check_now()
            else:
                # 如果没有监控器，直接调用Lucky.loginClient
                optimized_print(f"🔄 [BetExecutor] 直接调用Lucky.loginClient触发登录",
                               category='bet_executor', level='INFO', force=True)
                try:
                    from xy_client.services.Lucky5 import Lucky
                    login_thread = threading.Thread(target=Lucky.loginClient, args=(self.main_window,), daemon=True)
                    login_thread.start()
                    optimized_print(f"✅ [BetExecutor] 已启动登录线程（Lucky.loginClient）",
                                   category='bet_executor', level='INFO', force=True)
                except ImportError:
                    optimized_print(f"❌ [BetExecutor] 无法导入 Lucky 模块",
                                   category='bet_executor', level='ERROR', force=True)
                except Exception as login_e:
                    optimized_print(f"❌ [BetExecutor] 启动登录线程异常: {login_e}",
                                   category='bet_executor', level='ERROR', force=True)
        except Exception as e:
            optimized_print(f"❌ [BetExecutor] 触发登录异常: {e}",
                           category='bet_executor', level='ERROR', force=True)
    
    def _wait_bet_interval(self, data: Dict[str, Any]):
        """
        等待下注间隔
        
        Args:
            data: 下注任务数据
        """
        slow_seconds = data.get('slow_seconds', 0.5)
        try:
            if isinstance(slow_seconds, str):
                slow_seconds = float(slow_seconds) if slow_seconds.strip() else 0.5
            elif slow_seconds is None:
                slow_seconds = 0.5
            else:
                slow_seconds = float(slow_seconds)
        except (ValueError, TypeError):
            slow_seconds = 0.5
        
        if slow_seconds > 0:
            # 优化：进一步缩短下注间隔等待时间，提升响应速度（最多等待0.02秒）
            # 单个任务时，下注间隔对性能影响较大，尽量减少等待时间
            time.sleep(min(0.02, max(0.01, slow_seconds)))  # 优化：从0.05秒缩短到0.02秒
    
    def _handle_bet_exception(self, error: Exception, direct: int):
        """
        处理下注异常
        
        Args:
            error: 异常对象
            direct: 下注方向
        """
        error_msg = str(error)
        optimized_print(f"下注任务执行异常 [direct={direct}]: {error_msg}",
                       category='bet_executor', level='ERROR', force=True)  # 错误强制输出
        
        # 检查是否是连接错误
        if self.error_handler.is_connection_error(error_msg):
            optimized_print(f"⚠️ 检测到浏览器连接断开错误，尝试恢复连接...",
                           category='bet_executor', level='WARNING')
            if self.browser_manager.check_and_recover_browser_connection():
                optimized_print(f"✅ 浏览器连接已恢复",
                               category='bet_executor', level='INFO', force=True)
            else:
                optimized_print(f"❌ 浏览器连接恢复失败",
                               category='bet_executor', level='ERROR', force=True)
        
        # 记录错误日志（限制频率，避免日志过多）
        try:
            access_token = getattr(self.main_window, 'access_token', '')
            lottery_type = getattr(self.main_window, 'lottery_type', 8)
            # 限制错误日志频率（每分钟最多记录一次相同的错误）
            error_key = f'bet_error_{direct}'
            if not hasattr(self.main_window, '_last_error_log_time'):
                self.main_window._last_error_log_time = {}
            
            last_log_time = self.main_window._last_error_log_time.get(error_key, 0)
            if time.time() - last_log_time > 60:  # 每分钟最多记录一次
                pushErrorLog(f'下注执行异常 direct={direct}', access_token, lottery_type, error_msg)
                self.main_window._last_error_log_time[error_key] = time.time()
        except Exception:
            pass
        
        time.sleep(0.3)  # 异常后短暂等待（优化：从1秒缩短到0.3秒，提高响应速度）
    
    def _check_and_cleanup_windows(self):
        """
        检查并清理多余的浏览器窗口
        在批量下注过程中定期调用，防止窗口过多
        """
        try:
            if hasattr(self.main_window, 'ensure_single_browser_window'):
                # 检查窗口数量
                if hasattr(self.main_window, 'driver') and self.main_window.driver:
                    try:
                        window_handles = self.main_window.driver.window_handles
                        if len(window_handles) > 1:
                            optimized_print(f"🔄 [BetExecutor] 检测到{len(window_handles)}个窗口，开始清理...",
                                           category='bet_executor', level='DEBUG')
                            self.main_window.ensure_single_browser_window()
                            optimized_print(f"✅ [BetExecutor] 已清理多余窗口，当前窗口数: 1",
                                           category='bet_executor', level='DEBUG')
                    except Exception as e:
                        # 窗口检查失败不影响下注
                        optimized_print(f"⚠️ [BetExecutor] 检查窗口异常: {e}",
                                       category='bet_executor', level='DEBUG')
        except Exception as e:
            # 窗口清理失败不影响下注
            pass
    
    def _keep_alive_activity(self):
        """
        防踢机制：保持活动状态，防止被服务器踢下线
        在批量下注过程中定期调用
        """
        try:
            # 更新心跳时间
            if hasattr(self.main_window, '_bet_last_heartbeat'):
                import time as _time
                self.main_window._bet_last_heartbeat = _time.time()
            
            # 如果浏览器存在，执行轻量级操作保持活动
            if hasattr(self.main_window, 'driver') and self.main_window.driver:
                try:
                    # 尝试获取当前URL（轻量级操作，不会阻塞）
                    # 使用超时保护，避免阻塞
                    import threading
                    url_result = [None]
                    url_timeout = [False]
                    
                    def get_url():
                        try:
                            url_result[0] = self.main_window.driver.current_url
                        except Exception:
                            url_timeout[0] = True
                    
                    url_thread = threading.Thread(target=get_url, daemon=True)
                    url_thread.start()
                    url_thread.join(timeout=1)  # 最多等待1秒
                    
                    # 如果成功获取URL，说明浏览器正常
                    if not url_timeout[0] and url_result[0]:
                        # 活动检测成功，不输出日志避免日志过多
                        pass
                except Exception:
                    # 活动检测失败不影响下注
                    pass
        except Exception:
            # 防踢机制失败不影响下注
            pass

