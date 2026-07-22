"""
下注任务管理器
管理下注任务的执行流程
"""

import time
import threading
from typing import Optional
from xy_client.services.Lucky5.core import StateManager, BrowserManager, ErrorHandler
from xy_client.services.Lucky5.utils import TimerManager
from xy_client.services.Lucky5.utils.log_optimizer import optimized_print


class BetTaskManager:
    """下注任务管理器 - 管理下注任务的完整流程"""
    
    def __init__(self, main_window):
        """
        初始化下注任务管理器
        
        Args:
            main_window: 主窗口实例
        """
        self.main_window = main_window
        
        # 初始化核心组件
        self.state_manager = StateManager(main_window)
        self.browser_manager = BrowserManager(main_window, self.state_manager)
        self.error_handler = ErrorHandler(main_window, self.state_manager, self.browser_manager)
        self.timer_manager = TimerManager(main_window)
        
        # 任务执行互斥锁 - 确保同一时间只有一个任务在执行（防止死循环）
        if not hasattr(self.main_window, '_bet_task_lock'):
            self.main_window._bet_task_lock = threading.Lock()
        if not hasattr(self.main_window, '_bet_task_executing'):
            self.main_window._bet_task_executing = False
        if not hasattr(self.main_window, '_bet_task_start_time'):
            self.main_window._bet_task_start_time = 0
        
        # 任务心跳与看门狗（防止假死）- 持久化到 main_window，避免新实例重置
        import time as _time
        if not hasattr(self.main_window, '_bet_last_heartbeat'):
            self.main_window._bet_last_heartbeat = _time.time()
        # 看门狗击中次数与时间窗
        if not hasattr(self.main_window, '_bet_watchdog_strikes'):
            self.main_window._bet_watchdog_strikes = 0
        if not hasattr(self.main_window, '_bet_watchdog_window_start'):
            self.main_window._bet_watchdog_window_start = _time.time()
        # 仅启动一次看门狗
        if not hasattr(self.main_window, '_bet_watchdog_started'):
            try:
                self._start_watchdog()
                self.main_window._bet_watchdog_started = True
            except Exception:
                pass
        
        # 导入下注执行器和计划获取器
        from xy_client.services.Lucky5.betting.bet_executor import BetExecutor
        from xy_client.services.Lucky5.betting.bet_plan_fetcher import BetPlanFetcher
        
        self.executor = BetExecutor(main_window, self.state_manager, self.browser_manager, self.error_handler)
        self.plan_fetcher = BetPlanFetcher(main_window, self.state_manager)
        # 记录上次打印"无下注计划"的时间，控制日志频率（30秒一次）
        self._last_no_plan_log_time = 0
    
    def execute(self, direct: int = 1, session=None) -> bool:
        """
        执行下注任务
        
        Args:
            direct: 下注方向
            session: 请求会话（可选）
        
        Returns:
            bool: 是否成功执行
        """
        # 关键修复：使用互斥锁确保同一时间只有一个任务在执行
        # 如果已有任务在执行，直接返回，避免死循环
        lock = self.main_window._bet_task_lock
        if not lock.acquire(blocking=False):
            # 无法获取锁，说明有任务正在执行
            # 关键修复：即使跳过执行，也要更新心跳，避免看门狗误判
            self._beat("skip_locked")
            optimized_print(f"⏸️ [BetTaskManager] 下注任务正在执行中，跳过本次调用 (direct={direct})", 
                           category='bet_task', level='DEBUG')
            return False
        
        try:
            # 检查是否有任务执行超时（超过60秒认为卡死）
            if self.main_window._bet_task_executing:
                elapsed = time.time() - self.main_window._bet_task_start_time
                if elapsed > 60:
                    optimized_print(f"⚠️ [BetTaskManager] 检测到任务执行超时 ({elapsed:.1f}秒)，强制重置", 
                                   category='bet_task', level='WARNING', force=True)
                    self.main_window._bet_task_executing = False
            
            # 如果已有任务在执行，跳过
            if self.main_window._bet_task_executing:
                # 关键修复：即使跳过执行，也要更新心跳，避免看门狗误判
                self._beat("skip_executing")
                optimized_print(f"⏸️ [BetTaskManager] 下注任务正在执行中，跳过本次调用 (direct={direct})",
                               category='bet_task', level='DEBUG')
                return False
            
            # 标记任务开始执行
            self.main_window._bet_task_executing = True
            self.main_window._bet_task_start_time = time.time()
            
            # 使用优化的日志输出（减少频率）
            #optimized_print(f"🏁 [BetTaskManager] 下注任务开始执行 (direct={direct})", category='bet_task', level='INFO')
            
            self._beat("enter_execute")
            # 步骤1: 检查并恢复浏览器连接（可选，不影响下注）
            # 关键优化：WebDriver连接异常不影响下注流程（下注用requests+cookie）
            # 只要登录状态有效（is_need_login=1）且有cookies，就可以继续下注
            # 不需要WebDriver连接，因为下注使用requests+cookie
            try:
                # 可选：尝试检查浏览器连接（但不影响下注流程）
                # 如果连接不上，只记录日志，不阻止下注
                browser_ok = self._check_browser_connection()
                if not browser_ok:
                    optimized_print("⚠️ [BetTaskManager] WebDriver连接异常，但不影响下注（下注用requests+cookie，不依赖WebDriver）",
                                   category='bet_task', level='DEBUG')
                    # 不返回False，继续执行下注流程
            except Exception as browser_e:
                optimized_print(f"⚠️ [BetTaskManager] 检查WebDriver连接异常: {browser_e}，但不影响下注（下注用requests+cookie）",
                               category='bet_task', level='DEBUG')
                # 不返回False，继续执行下注流程
            
            # 步骤1.5: 定期检查并清理多余窗口（防止窗口过多）
            # 每10次执行检查一次窗口，避免频繁检查影响性能
            # 关键优化：窗口清理失败不影响下注流程（下注不依赖WebDriver）
            if not hasattr(self.main_window, '_window_check_counter'):
                self.main_window._window_check_counter = 0
            self.main_window._window_check_counter += 1
            if self.main_window._window_check_counter >= 10:
                self.main_window._window_check_counter = 0
                try:
                    # 关键优化：检查WebDriver连接是否可用，如果不可用则跳过窗口清理
                    if hasattr(self.main_window, 'driver') and self.main_window.driver:
                        try:
                            # 轻量级检查：尝试获取窗口句柄（带超时保护）
                            import threading
                            window_handles_result = [None]
                            window_check_timeout = [False]
                            
                            def get_window_handles():
                                try:
                                    window_handles_result[0] = self.main_window.driver.window_handles
                                except Exception:
                                    window_check_timeout[0] = True
                            
                            window_thread = threading.Thread(target=get_window_handles, daemon=True)
                            window_thread.start()
                            window_thread.join(timeout=2)  # 2秒超时
                            
                            if window_thread.is_alive() or window_check_timeout[0]:
                                optimized_print("⚠️ [BetTaskManager] WebDriver连接异常，跳过窗口清理（不影响下注，下注使用requests+cookie）",
                                               category='bet_task', level='DEBUG')
                            elif window_handles_result[0] and len(window_handles_result[0]) > 1:
                                optimized_print(f"🔄 [BetTaskManager] 定期检查：检测到{len(window_handles_result[0])}个窗口，开始清理...",
                                               category='bet_task', level='INFO')
                                if hasattr(self.main_window, 'ensure_single_browser_window'):
                                    self.main_window.ensure_single_browser_window()
                                    optimized_print(f"✅ [BetTaskManager] 已清理多余窗口",
                                                   category='bet_task', level='DEBUG')
                        except Exception as window_check_e:
                            optimized_print(f"⚠️ [BetTaskManager] 窗口检查异常: {window_check_e}，跳过窗口清理（不影响下注）",
                                           category='bet_task', level='DEBUG')
                except Exception as window_e:
                    optimized_print(f"⚠️ [BetTaskManager] 窗口清理异常: {window_e}，但不影响下注（下注不依赖WebDriver）",
                                   category='bet_task', level='DEBUG')
            
            # 步骤2: 检查登录状态（只检查登录状态和Cookies，不依赖WebDriver）
            if not self.state_manager.is_ready_for_betting():
                # 关键优化：检测到未登录时，立即触发LoginStatusMonitor检查（不等待1分钟）
                optimized_print(f"⚠️ [BetTaskManager] 未准备好下注（登录状态或Cookies异常），立即触发登录状态检查",
                               category='bet_task', level='WARNING', force=True)
                
                if getattr(self.main_window, 'runtime_mode', 'browser') == 'background':
                    optimized_print(
                        "后台HTTP模式的盘口会话已失效，请勾选“打开浏览器登录”后点击登录",
                        category='bet_task', level='WARNING', force=True
                    )
                    self.state_manager.set_login_status(False)
                    self._beat("background_session_expired")
                    self._restart_timer(direct, reason="后台会话失效", delay=30)
                    return False

                # 立即触发登录状态检查（使用强制登录模式，因为这是明确的未登录状态）
                try:
                    if hasattr(self.main_window, '_login_status_monitor'):
                        # 立即检查登录状态，如果未登录则触发强制登录
                        self.main_window._login_status_monitor.check_now()
                        optimized_print(f"✅ [BetTaskManager] 已触发立即登录状态检查（将使用强制登录模式）",
                                       category='bet_task', level='INFO', force=True)
                    else:
                        # 如果没有监控器，直接触发强制登录
                        optimized_print(f"⚠️ [BetTaskManager] 未找到LoginStatusMonitor，直接触发强制登录",
                                       category='bet_task', level='WARNING', force=True)
                        try:
                            if hasattr(self.main_window, 'smart_login_management'):
                                # 使用强制登录模式，跳过冷却时间限制
                                import threading
                                login_thread = threading.Thread(
                                    target=lambda: self.main_window.smart_login_management(force_login=True), 
                                    daemon=True
                                )
                                login_thread.start()
                                optimized_print(f"✅ [BetTaskManager] 已启动强制登录线程（smart_login_management）",
                                               category='bet_task', level='INFO', force=True)
                            else:
                                # 如果没有smart_login_management，使用Lucky.loginClient
                                from xy_client.services.Lucky5 import Lucky
                                import threading
                                login_thread = threading.Thread(target=Lucky.loginClient, args=(self.main_window,), daemon=True)
                                login_thread.start()
                                optimized_print(f"✅ [BetTaskManager] 已启动登录检测线程（Lucky.loginClient）",
                                               category='bet_task', level='INFO', force=True)
                        except ImportError:
                            optimized_print(f"❌ [BetTaskManager] 无法导入 Lucky 模块",
                                           category='bet_task', level='ERROR', force=True)
                        except Exception as login_e:
                            optimized_print(f"❌ [BetTaskManager] 启动登录检测线程异常: {login_e}",
                                           category='bet_task', level='ERROR', force=True)
                except Exception as trigger_e:
                    optimized_print(f"⚠️ [BetTaskManager] 触发登录检查异常: {trigger_e}",
                                   category='bet_task', level='WARNING')
                
                # 关键修复：即使登录异常，也要更新心跳，避免看门狗误判
                self._beat("login_status_failed")
                # 等待一段时间让登录检查完成（缩短等待时间，因为已经立即触发了）
                self._restart_timer(direct, reason="未登录或登录状态异常", delay=10)
                return False
            
            # 步骤3: 检查执行间隔（优化：第一次执行时跳过间隔检查）
            task_key = f"bet_task_{direct}"
            is_first_execution = not hasattr(self.main_window, '_bet_task_executed') or \
                               not self.main_window._bet_task_executed.get(task_key, False)
            
            if not is_first_execution:
                # 非第一次执行才检查间隔
                if not self.state_manager.can_execute_bet_task(task_key, min_interval=0.5):
                    # 关键修复：即使间隔太短，也要更新心跳，避免看门狗误判
                    self._beat("interval_too_short")
                    optimized_print(f"⏰ [BetTaskManager] 下注任务{direct}执行间隔太短，跳过本次执行",
                                   category='bet_task', level='DEBUG')
                    self._restart_timer(direct)
                    return False
            else:
                # 第一次执行，标记为已执行
                if not hasattr(self.main_window, '_bet_task_executed'):
                    self.main_window._bet_task_executed = {}
                self.main_window._bet_task_executed[task_key] = True
                optimized_print(f"⚡ [BetTaskManager] 首次执行下注任务{direct}，跳过间隔检查",
                               category='bet_task', level='INFO', force=True)  # 首次执行强制输出
            
            # 步骤4: 刷新期号
            self._refresh_qihao()
            self._beat("after_refresh_qihao")
            
            # 步骤5: 获取下注计划
            self._beat("before_fetch_plan")
            plan_data = self.plan_fetcher.fetch_plan(direct)
            self._beat("after_fetch_plan")
            
            # 记录是否有任务，用于优化轮询间隔
            has_tasks = plan_data and plan_data.get('data')
            if not hasattr(self.main_window, '_last_has_tasks'):
                self.main_window._last_has_tasks = False
            self.main_window._last_has_tasks = has_tasks
            
            if not plan_data:
                # 优化：无计划任务的情况30秒才打印一次，避免日志太多
                # 关键修复：即使无计划，也要更新心跳，避免看门狗误判
                self._beat("no_plan")
                current_time = time.time()
                if current_time - self._last_no_plan_log_time >= 30:
                    optimized_print(f"⚠️ [BetTaskManager] 无下注计划或状态异常，跳过执行 [direct={direct}]",
                                   category='bet_task', level='WARNING')
                    self._last_no_plan_log_time = current_time
                
                # 优化：当无计划时，缩短等待时间，加快响应（从5-8秒缩短到3-5秒）
                # 保持对服务器的合理压力，同时确保有任务时能快速响应
                import random
                delay = random.uniform(3, 5)  # 3-5秒延迟（从5-8秒缩短）
                self._restart_timer(direct, reason="无下注计划", delay=delay)
                return False
            
            # 步骤6: 执行下注
            self._beat("before_execute_bets")
            success = self.executor.execute_bets(plan_data, direct, session)
            
            # 步骤7: 刷新页面（如果需要）
            # 注意：刷新在下注完成后执行，不会影响下注任务
            # 使用异步刷新，避免阻塞下注任务完成后的其他操作
            if direct != 2:
                # 异步执行刷新，不阻塞后续操作
                def async_refresh():
                    try:
                        self._refresh_page()
                    except Exception as e:
                        optimized_print(f"⚠️ [BetTaskManager] 异步刷新页面异常: {e}",
                                       category='bet_task', level='WARNING')
                
                # 在后台线程中执行刷新，不阻塞主流程
                import threading
                refresh_thread = threading.Thread(target=async_refresh, daemon=True)
                refresh_thread.start()
            
            # 步骤8: 重新启动定时器
            self._restart_timer(direct, reason="下注任务完成" if success else "下注任务失败")
            self._beat("after_restart_timer")
            
            return success
            
        except Exception as e:
            # 错误处理
            # 关键修复：即使异常，也要更新心跳，避免看门狗误判
            self._beat("exception")
            self.error_handler.handle_bet_error(e, direct)
            # 仍然需要重启定时器（异常重启）
            self._restart_timer(direct, reason=f"异常: {type(e).__name__}")
            return False
        finally:
            # 关键修复：无论成功还是失败，都要释放锁和重置执行状态
            # 关键修复：在 finally 中也要更新心跳，确保即使异常退出也有心跳
            self._beat("finally")
            self.main_window._bet_task_executing = False
            self.main_window._bet_task_start_time = 0
            lock.release()

    # ---------------------- 看门狗与心跳 ----------------------
    def _beat(self, point: str):
        try:
            import time as _time
            self.main_window._bet_last_heartbeat = _time.time()
        except Exception:
            pass

    def _start_watchdog(self):
        # 参考拼多多爬虫：移除复杂的看门狗机制，使用简单的超时保护
        # 看门狗功能已由 createBetTasks 的 while True 循环和超时保护替代
        # 这里只保留一个简单的标记，表示看门狗已初始化
        optimized_print("🛡️ [BetTaskManager] 看门狗已简化（使用外部循环保护）",
                       category='watchdog', level='INFO', force=True)

    def _watchdog_tick(self):
        # 参考拼多多爬虫：移除复杂的看门狗逻辑
        # 超时保护已由 createBetTasks 的线程超时机制处理（30秒超时）
        # 这里不再需要复杂的看门狗逻辑，避免死循环
        pass

    def _record_watchdog_strike(self):
        import time as _time
        window_start = getattr(self.main_window, '_bet_watchdog_window_start', _time.time())
        strikes = getattr(self.main_window, '_bet_watchdog_strikes', 0)
        # 2分钟的滑动窗口
        if _time.time() - window_start > 120:
            self.main_window._bet_watchdog_window_start = _time.time()
            self.main_window._bet_watchdog_strikes = 1
        else:
            self.main_window._bet_watchdog_strikes = strikes + 1

    def _should_perform_hard_recovery(self) -> bool:
        """
        在2分钟窗口内击中>=3次触发硬恢复
        添加冷却期，避免频繁硬恢复（硬恢复后5分钟内不再触发）
        """
        import time as _time
        current_time = _time.time()
        window_start = getattr(self.main_window, '_bet_watchdog_window_start', current_time)
        strikes = getattr(self.main_window, '_bet_watchdog_strikes', 0)
        
        # 检查硬恢复冷却期（5分钟）
        last_hard_recovery = getattr(self.main_window, '_last_hard_recovery_time', 0)
        if current_time - last_hard_recovery < 300:  # 5分钟内不再次硬恢复
            return False
        
        should_recover = (current_time - window_start) <= 120 and strikes >= 3
        if should_recover:
            # 记录硬恢复时间
            self.main_window._last_hard_recovery_time = current_time
            # 重置计数器，避免立即再次触发
            self.main_window._bet_watchdog_strikes = 0
            self.main_window._bet_watchdog_window_start = current_time
        
        return should_recover
    
    def _check_browser_connection(self) -> bool:
        """
        检查浏览器连接（可选检查，不影响下注流程）
        
        关键说明：此方法仅用于可选检查，即使返回False也不会阻止下注
        因为下注使用requests+cookie，不依赖WebDriver连接
        
        Returns:
            bool: 连接是否正常（仅用于日志记录，不影响下注流程）
        """
        try:
            if not self.browser_manager.check_and_recover_browser_connection():
                # 关键优化：只记录日志，不阻止下注（下注不依赖WebDriver）
                optimized_print(f"⚠️ [BetTaskManager] WebDriver连接异常（不影响下注，下注使用requests+cookie）",
                               category='browser_check', level='DEBUG')
                return False
            return True
        except Exception as e:
            # 关键优化：异常也不影响下注流程
            optimized_print(f"⚠️ [BetTaskManager] 检查WebDriver连接异常: {e}（不影响下注，下注使用requests+cookie）",
                           category='browser_check', level='DEBUG')
            return False
    
    def _refresh_qihao(self):
        """刷新当前期号（优化：如果已有期号且距离上次刷新时间短，可以跳过）"""
        try:
            # 优化：如果已有期号且最近刷新过（5秒内），可以跳过刷新
            if hasattr(self.main_window, 'current_qihao') and self.main_window.current_qihao:
                if hasattr(self.main_window, '_last_qihao_refresh_time'):
                    time_since_refresh = time.time() - self.main_window._last_qihao_refresh_time
                    if time_since_refresh < 5:  # 5秒内刷新过，跳过
                        return
            
            from xy_client.services.systems_users.SystemsUsers import getRotActiveQiHao
            access_token = getattr(self.main_window, 'access_token', '')
            lottery_type = getattr(self.main_window, 'lottery_type', 8)
            
            qihao_rst = getRotActiveQiHao(access_token, lottery_type)
            if qihao_rst and qihao_rst.get('status') == 200 and qihao_rst.get('data'):
                next_qihao = qihao_rst['data'].get('next_qihao') or qihao_rst['data'].get('period_no')
                if next_qihao:
                    new_qihao = str(next_qihao)
                    # 优化：只有期号变化时才打印，同样的期号不重复打印
                    old_qihao = getattr(self.main_window, 'current_qihao', '')
                    if new_qihao != old_qihao:
                        self.main_window.current_qihao = new_qihao
                        self.main_window._last_qihao_refresh_time = time.time()  # 记录刷新时间
                        optimized_print(f"✅ [BetTaskManager] 已刷新期号: {self.main_window.current_qihao}",
                                       category='qihao_refresh', level='INFO')
                    else:
                        # 期号未变化，只更新时间和期号值，不打印日志
                        self.main_window.current_qihao = new_qihao
                        self.main_window._last_qihao_refresh_time = time.time()
        except Exception as e:
            optimized_print(f"⚠️ [BetTaskManager] 刷新期号失败: {e}",
                           category='qihao_refresh', level='WARNING')
    
    def _refresh_page(self):
        """
        刷新页面（关键优化：改为可选，不影响下注）
        
        优化说明：
        - 下注已经用 requests.post() + cookie，不需要刷新页面
        - 页面刷新改为可选、异步的，不影响下注流程
        - 如果浏览器连接异常，直接跳过，不影响下注
        """
        # 关键优化：检查是否启用页面刷新（默认禁用，提高稳定性）
        # 下注已经用 requests + cookie，不需要刷新页面
        enable_refresh = getattr(self.main_window, 'enable_page_refresh_after_bet', False)
        if not enable_refresh:
            # 默认禁用页面刷新，提高稳定性
            optimized_print("ℹ️ [BetTaskManager] 页面刷新已禁用（下注用 requests + cookie，不需要刷新）",
                           category='bet_task', level='DEBUG')
            return
        
        try:
            # 异步刷新，不阻塞下注流程
            import threading
            def async_refresh():
                try:
                    # 关键优化：尝试恢复浏览器连接（如果连接失败，只记录日志，不阻止下注）
                    # 即使连接完全失败，下注业务也能正常执行（只要cookie有效）
                    try:
                        browser_ok = self.browser_manager.check_and_recover_browser_connection(silent=True)
                        if not browser_ok:
                            optimized_print("⚠️ [BetTaskManager] WebDriver连接异常，跳过页面刷新（不影响下注，下注使用requests+cookie）",
                                           category='bet_task', level='DEBUG')
                            return
                    except Exception as conn_e:
                        optimized_print(f"⚠️ [BetTaskManager] 检查WebDriver连接异常: {conn_e}，跳过页面刷新（不影响下注）",
                                       category='bet_task', level='DEBUG')
                        return
                    
                    driver = self.browser_manager.get_driver()
                    if driver:
                        # 使用页面刷新管理器执行刷新（支持超时控制和自动恢复）
                        try:
                            from xy_client.services.tools.PageRefreshManager import get_refresh_manager
                            refresh_manager = get_refresh_manager(page_load_timeout=10, max_retry=2)
                            # 优化：禁用加载状态检测，避免误判下注请求为页面加载
                            success = refresh_manager.safe_refresh(driver, reason="下注完成后的页面刷新（异步）",
                                                                  check_loading=False, timeout=10)
                            if success:
                                optimized_print("✅ [BetTaskManager] 下注完成后的页面刷新成功（异步）",
                                               category='bet_task', level='DEBUG')
                            else:
                                optimized_print("⚠️ [BetTaskManager] 页面刷新失败（已重试，异步）",
                                               category='bet_task', level='DEBUG')
                        except ImportError:
                            # 如果导入失败，使用原有的简单刷新方式
                            optimized_print("⚠️ [BetTaskManager] 页面刷新管理器不可用，使用简单刷新方式",
                                           category='bet_task', level='DEBUG')
                            # 添加超时保护，避免 driver.current_url 和 driver.refresh() 阻塞
                            import threading
                            
                            url_result: list = [None]
                            url_timeout: list = [False]
                            
                            def get_url():
                                try:
                                    url_result[0] = driver.current_url
                                except Exception:
                                    url_timeout[0] = True
                            
                            url_thread = threading.Thread(target=get_url, daemon=True)
                            url_thread.start()
                            url_thread.join(timeout=3)  # 最多等待3秒
                            
                            if url_thread.is_alive() or url_timeout[0]:
                                optimized_print("⚠️ [BetTaskManager] 获取URL超时，跳过页面刷新",
                                               category='bet_task', level='DEBUG')
                                return
                            
                            test_url = url_result[0]
                            if test_url and 'http' in test_url:
                                # 刷新页面也添加超时保护
                                refresh_done: list = [False]
                                refresh_timeout: list = [False]
                                
                                def refresh_page():
                                    try:
                                        driver.refresh()
                                        refresh_done[0] = True
                                    except Exception:
                                        refresh_timeout[0] = True
                                
                                refresh_thread = threading.Thread(target=refresh_page, daemon=True)
                                refresh_thread.start()
                                refresh_thread.join(timeout=5)  # 最多等待5秒
                                
                                if refresh_thread.is_alive() or refresh_timeout[0]:
                                    optimized_print("⚠️ [BetTaskManager] 页面刷新超时（异步）",
                                                   category='bet_task', level='DEBUG')
                                else:
                                    optimized_print("✅ [BetTaskManager] 下注完成后的页面刷新成功（异步）",
                                                   category='bet_task', level='DEBUG')
                            else:
                                optimized_print("⚠️ [BetTaskManager] WebDriver连接异常，跳过页面刷新",
                                               category='bet_task', level='DEBUG')
                        except Exception as e:
                            error_msg = str(e)
                            optimized_print(f"⚠️ [BetTaskManager] 页面刷新异常（异步）: {error_msg[:100]}",
                                           category='bet_task', level='DEBUG')
                except Exception as e:
                    error_msg = str(e)
                    optimized_print(f"⚠️ [BetTaskManager] 异步刷新异常: {error_msg[:100]}",
                                   category='bet_task', level='DEBUG')
            
            # 启动异步刷新线程
            threading.Thread(target=async_refresh, daemon=True).start()
        except Exception as e:
            optimized_print(f"⚠️ [BetTaskManager] 页面刷新异常: {e}",
                           category='bet_task', level='WARNING')
    
    def _restart_timer(self, direct: int, reason: str = "正常完成", delay: Optional[float] = None):
        """
        重新启动定时器 - 单线程顺序执行：下注完成后延迟3-5秒再获取下一个任务
        
        Args:
            direct: 下注方向
            reason: 重启原因（用于日志）
            delay: 可选的延迟时间（秒），如果提供则使用此延迟，否则使用默认逻辑
        """
        try:
            import threading
            
            # 确定延迟时间
            if delay is not None:
                # 如果提供了延迟时间，使用它
                delay_seconds = delay
                need_delay = True
            elif "下注任务完成" in reason:
                # 下注任务成功完成，立即重启，不延迟，确保快速获取下一个任务
                need_delay = False
            elif "下注任务失败" in reason:
                # 下注任务失败（已在内部重试过），缩短等待时间到1-2秒，加快恢复
                import random
                delay_seconds = random.uniform(1, 2)  # 1-2秒随机延迟（从3-5秒缩短）
                need_delay = True
            elif "无下注计划" in reason:
                # 无下注计划时，根据上次是否有任务来决定轮询间隔
                # 如果上次有任务，说明可能刚完成，间隔短一些（1-2秒）
                # 如果上次无任务，说明可能长时间无任务，间隔长一些（2-3秒）
                import random
                if getattr(self.main_window, '_last_has_tasks', False):
                    delay_seconds = random.uniform(1, 2)  # 1-2秒
                else:
                    delay_seconds = random.uniform(2, 3)  # 2-3秒
                need_delay = True
            else:
                # 其他情况立即重启，不延迟
                need_delay = False
            
            if need_delay:
                optimized_print(f"⏰ [BetTaskManager] 等待 {delay_seconds:.1f} 秒后重新启动定时器（原因: {reason}）...",
                               category='timer_restart', level='INFO', force=True)
                
                def delayed_restart():
                    time.sleep(delay_seconds)
                    try:
                        optimized_print(f"🔄 [BetTaskManager] 重新启动定时器（原因: {reason}）...",
                                       category='timer_restart', level='DEBUG')
                        # 缩短定时器间隔到1秒，确保10秒内完成（从2秒缩短）
                        self.timer_manager.start_bet_task_timer(interval=1, direct=direct, force_restart=True)
                        optimized_print(f"✅ [BetTaskManager] 定时器已重新启动（间隔1秒，原因: {reason}）",
                                       category='timer_restart', level='DEBUG')
                    except Exception as e:
                        optimized_print(f"❌ [BetTaskManager] 重新启动定时器失败: {e}",
                                       category='timer_restart', level='ERROR', force=True)
                
                # 异步执行延迟重启，避免阻塞当前线程
                threading.Thread(target=delayed_restart, daemon=True).start()
            else:
                # 其他情况立即重启，不延迟
                optimized_print(f"🔄 [BetTaskManager] 重新启动定时器（原因: {reason}）...",
                               category='timer_restart', level='DEBUG')
                
                # 智能调整轮询间隔
                interval = 1  # 默认1秒
                self.timer_manager.start_bet_task_timer(interval=interval, direct=direct, force_restart=True)
                optimized_print(f"✅ [BetTaskManager] 定时器已重新启动（间隔{interval}秒，原因: {reason}）",
                               category='timer_restart', level='DEBUG')
        except Exception as e:
            optimized_print(f"❌ [BetTaskManager] 重新启动定时器失败: {e}",
                           category='timer_restart', level='ERROR', force=True)
            import traceback
            traceback.print_exc()
