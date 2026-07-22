"""
状态管理器
统一管理登录状态、浏览器状态、任务状态
"""

import time
from typing import Optional, Dict, Any
from xy_client.services.Lucky5.utils.log_optimizer import optimized_print


class StateManager:
    """状态管理器 - 统一管理所有状态"""
    
    def __init__(self, main_window):
        """
        初始化状态管理器
        
        Args:
            main_window: 主窗口实例
        """
        self.main_window = main_window
        self._init_states()
    
    def _init_states(self):
        """初始化所有状态"""
        # 登录状态
        if not hasattr(self.main_window, 'is_need_login'):
            self.main_window.is_need_login = 0
        
        # 浏览器状态
        if not hasattr(self.main_window, 'browser_cookies'):
            self.main_window.browser_cookies = None
        
        # 任务执行时间记录
        if not hasattr(self.main_window, '_last_bet_time'):
            self.main_window._last_bet_time = {}
        
        # 任务统计信息
        if not hasattr(self.main_window, '_betting_task_stats'):
            self.main_window._betting_task_stats = {}
    
    # ==================== 登录状态管理 ====================
    
    def is_logged_in(self) -> bool:
        """检查是否已登录"""
        return getattr(self.main_window, 'is_need_login', 0) == 1
    
    def set_login_status(self, status: bool):
        """
        设置登录状态
        
        Args:
            status: True表示已登录，False表示未登录
        """
        self.main_window.is_need_login = 1 if status else 0
        optimized_print(f"🔐 [StateManager] 登录状态已更新: {'已登录' if status else '未登录'}",
                       category='state_check', level='INFO')
    
    def get_login_status(self) -> int:
        """获取登录状态"""
        return getattr(self.main_window, 'is_need_login', 0)
    
    # ==================== 浏览器状态管理 ====================
    
    def has_browser_cookies(self) -> bool:
        """检查是否有浏览器cookies"""
        cookies = getattr(self.main_window, 'browser_cookies', None)
        return cookies is not None and cookies != ''
    
    def set_browser_cookies(self, cookies):
        """
        设置浏览器cookies
        
        Args:
            cookies: cookies值（可以是字符串或列表）
        """
        self.main_window.browser_cookies = cookies
        optimized_print(f"🍪 [StateManager] Cookies已更新: {'存在' if cookies else '不存在'}",
                       category='state_check', level='DEBUG')
    
    def get_browser_cookies(self):
        """获取浏览器cookies"""
        return getattr(self.main_window, 'browser_cookies', None)
    
    def has_browser_driver(self) -> bool:
        """检查是否有浏览器驱动"""
        return hasattr(self.main_window, 'driver') and self.main_window.driver is not None
    
    # ==================== 任务状态管理 ====================
    
    def can_execute_bet_task(self, task_key: str, min_interval: float = 0.5) -> bool:
        """
        检查是否可以执行下注任务（基于时间间隔）
        
        Args:
            task_key: 任务键（如 'bet_task_1'）
            min_interval: 最小执行间隔（秒）
        
        Returns:
            bool: 是否可以执行
        """
        current_time = time.time()
        last_time = self.main_window._last_bet_time.get(task_key, 0)
        
        if current_time - last_time < min_interval:
            return False
        
        # 更新执行时间
        self.main_window._last_bet_time[task_key] = current_time
        return True
    
    def update_bet_task_time(self, task_key: str):
        """更新下注任务执行时间"""
        self.main_window._last_bet_time[task_key] = time.time()
    
    def get_bet_task_stats(self) -> Dict[str, Any]:
        """获取下注任务统计信息"""
        return getattr(self.main_window, '_betting_task_stats', {})
    
    def update_bet_task_stats(self, stats: Dict[str, Any]):
        """更新下注任务统计信息"""
        self.main_window._betting_task_stats.update(stats)
    
    # ==================== 综合状态检查 ====================
    
    def is_ready_for_betting(self) -> bool:
        """
        检查是否准备好执行下注任务
        关键优化：只检查登录状态和Cookies，不依赖WebDriver（下注用requests+cookie）
        
        Returns:
            bool: 是否准备好
        """
        # 1. 检查登录状态（基于API检查结果，由LoginStatusMonitor定期更新）
        if not self.is_logged_in():
            optimized_print(f"⚠️ [StateManager] 未登录状态",
                           category='state_check', level='WARNING')
            return False
        
        # 2. 检查是否有cookies（下注必需）
        if not self.has_browser_cookies():
            optimized_print(f"⚠️ [StateManager] 无浏览器cookies",
                           category='state_check', level='WARNING')
            return False
        
        # 3. 关键优化：不再强制要求WebDriver（下注用requests+cookie，不需要WebDriver）
        # WebDriver只在登录时需要，运行时不需要
        # 如果WebDriver连接异常，不影响下注流程
        
        # 4. 可选：快速检查API登录状态（如果监控器最近检查过，可以跳过）
        # 这里不做强制检查，因为LoginStatusMonitor会定期检查
        
        return True
    
    def get_state_summary(self) -> Dict[str, Any]:
        """获取状态摘要"""
        return {
            'logged_in': self.is_logged_in(),
            'has_cookies': self.has_browser_cookies(),
            'has_driver': self.has_browser_driver(),
            'ready_for_betting': self.is_ready_for_betting(),
            'bet_task_stats': self.get_bet_task_stats()
        }

