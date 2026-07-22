"""
下注计划获取器
负责获取下注计划数据
"""

import time
from typing import Optional, Dict, Any
from xy_client.services.Lucky5.core import StateManager
from xy_client.services.systems_users.SystemsUsers import getBetPlanTasks
from xy_client.services.Lucky5.utils.log_optimizer import optimized_print


class BetPlanFetcher:
    """下注计划获取器 - 获取下注计划数据"""
    
    def __init__(self, main_window, state_manager: StateManager):
        """
        初始化下注计划获取器
        
        Args:
            main_window: 主窗口实例
            state_manager: 状态管理器
        """
        self.main_window = main_window
        self.state_manager = state_manager
    
    def fetch_plan(self, direct: int = 1) -> Optional[Dict[str, Any]]:
        """
        获取下注计划
        
        Args:
            direct: 下注方向
        
        Returns:
            Dict: 下注计划数据，如果获取失败则返回None
        """
        optimized_print(f"🔍 [BetPlanFetcher] 开始获取下注计划 [direct={direct}, qihao={self.main_window.current_qihao}]",
                       category='bet_plan', level='DEBUG')
        
        # 获取访问令牌
        access_token = getattr(self.main_window, 'access_token', '')
        if not access_token:
            optimized_print("❌ [BetPlanFetcher] 无法获取access_token",
                           category='bet_plan', level='ERROR', force=True)
            return None
        
        # 获取下注计划
        try:
            postDatas = getBetPlanTasks(access_token, self.main_window.current_qihao, direct)
            
            # 检查API状态
            post_status = postDatas.get('status') if postDatas else None
            if isinstance(post_status, str):
                post_status = int(post_status) if post_status.strip() else 0
            elif post_status is None:
                post_status = 0
            else:
                post_status = int(post_status)
            
            # 只在有数据或错误时输出日志
            if post_status == 200 and postDatas.get('data'):
                optimized_print(f"📊 [BetPlanFetcher] 获取到下注计划: {len(postDatas.get('data', []))}个任务",
                               category='bet_plan', level='INFO')
            else:
                # 优化：无计划任务的情况30秒才打印一次，避免日志太多（持久化在 main_window）
                current_time = time.time()
                last_ts = getattr(self.main_window, '_no_plan_log_ts', 0)
                if current_time - last_ts >= 30:
                    optimized_print(f"⚠️ [BetPlanFetcher] 无下注计划或状态异常 (status={post_status})",
                                   category='bet_plan', level='WARNING')
                    setattr(self.main_window, '_no_plan_log_ts', current_time)
            
            # 优化：检测到 status=300 时，先检查是否真的在登录页面，避免误报
            # status=300 可能只是表示"没有下注计划"，这是正常情况，不一定是认证失败
            if post_status == 300:
                current_time = time.time()
                last_300_check = getattr(self.main_window, '_last_status_300_check', 0)
                
                # 如果距离上次检查超过60秒，检查是否真的需要重新登录
                if current_time - last_300_check > 60:
                    setattr(self.main_window, '_last_status_300_check', current_time)
                    
                    # 先检查URL，只有在登录页面时才认为是认证失败
                    try:
                        if hasattr(self.main_window, 'driver') and self.main_window.driver:
                            import threading
                            url_result = [None]
                            url_timeout = [False]
                            
                            def get_url():
                                try:
                                    url_result[0] = self.main_window.driver.current_url.lower()
                                except Exception:
                                    url_timeout[0] = True
                            
                            url_thread = threading.Thread(target=get_url, daemon=True)
                            url_thread.start()
                            url_thread.join(timeout=3)
                            
                            if not url_thread.is_alive() and not url_timeout[0] and url_result[0]:
                                current_url = url_result[0]
                                # 只有在登录页面时，才认为是认证失败并触发登录检查
                                if any(keyword in current_url for keyword in ['/member/login', '/login', '登录']):
                                    optimized_print(f"⚠️ [BetPlanFetcher] 检测到 status=300 且在登录页面，触发重新登录",
                                                   category='bet_plan', level='WARNING', force=True)
                                    # 设置登录状态为需要登录
                                    if hasattr(self.main_window, 'is_need_login'):
                                        self.main_window.is_need_login = 0
                                    # 触发登录检测
                                    if hasattr(self.main_window, 'loginClient'):
                                        try:
                                            threading.Thread(target=self.main_window.loginClient, daemon=True).start()
                                        except Exception:
                                            pass
                                # 如果不在登录页面，status=300 只是表示没有下注计划，这是正常情况，不打印警告
                    except Exception:
                        pass
            
            # 验证数据有效性
            if not postDatas or post_status != 200 or not postDatas.get('data'):
                return None
            
            # 检查cookies
            if not self.state_manager.has_browser_cookies():
                optimized_print(f"⚠️ [BetPlanFetcher] 无浏览器cookies",
                               category='bet_plan', level='WARNING')
                return None
            
            return postDatas
            
        except Exception as e:
            optimized_print(f"❌ [BetPlanFetcher] 获取下注计划异常: {e}",
                           category='bet_plan', level='ERROR', force=True)
            import traceback
            traceback.print_exc()
            return None

