"""
错误处理器
统一处理错误和恢复逻辑
"""

import time
from typing import Optional, Callable
from xy_client.services.Lucky5.utils.log_optimizer import optimized_print


class ErrorHandler:
    """错误处理器 - 统一处理错误和恢复"""
    
    def __init__(self, main_window, state_manager, browser_manager):
        """
        初始化错误处理器
        
        Args:
            main_window: 主窗口实例
            state_manager: 状态管理器实例
            browser_manager: 浏览器管理器实例
        """
        self.main_window = main_window
        self.state_manager = state_manager
        self.browser_manager = browser_manager
    
    def is_connection_error(self, error_msg: str) -> bool:
        """
        判断是否为连接错误
        
        Args:
            error_msg: 错误消息
        
        Returns:
            bool: 是否为连接错误
        """
        connection_keywords = [
            '10054',
            'ConnectionResetError',
            'Connection aborted',
            '远程主机强迫关闭',
            'InvalidSessionIDException',
            'InvalidSessionIdException',
            'Tried to run command without establishing a connection'
        ]
        
        return any(keyword in str(error_msg) for keyword in connection_keywords)
    
    def handle_error(self, error: Exception, context: str = "", auto_recover: bool = True) -> bool:
        """
        处理错误
        
        Args:
            error: 异常对象
            context: 错误上下文描述
            auto_recover: 是否自动恢复
        
        Returns:
            bool: 是否已处理/恢复
        """
        error_msg = str(error)
        optimized_print(f"❌ [ErrorHandler] {context}: {error_msg}",
                       category='error_recovery', level='ERROR', force=True)  # 错误强制输出
        
        # 如果是连接错误，尝试恢复
        if auto_recover and self.is_connection_error(error_msg):
            optimized_print(f"🔄 [ErrorHandler] 检测到连接错误，尝试恢复...",
                           category='error_recovery', level='WARNING')
            return self._recover_connection()
        
        return False
    
    def _recover_connection(self) -> bool:
        """
        恢复连接
        
        Returns:
            bool: 是否成功恢复
        """
        # 尝试恢复浏览器连接
        if self.browser_manager.check_and_recover_browser_connection():
            optimized_print("✅ [ErrorHandler] 连接已恢复",
                           category='error_recovery', level='INFO', force=True)
            return True
        else:
            optimized_print("❌ [ErrorHandler] 连接恢复失败",
                           category='error_recovery', level='ERROR', force=True)
            return False
    
    def handle_bet_error(self, error: Exception, direct: int = 1) -> bool:
        """
        处理下注错误
        
        Args:
            error: 异常对象
            direct: 下注方向
        
        Returns:
            bool: 是否已处理
        """
        error_msg = str(error)
        context = f"下注任务异常 direct={direct}"
        
        # 记录错误日志（只记录关键错误，减少日志量）
        try:
            from xy_client.services.systems_users.SystemsUsers import pushErrorLog
            access_token = getattr(self.main_window, 'access_token', '')
            lottery_type = getattr(self.main_window, 'lottery_type', 8)
            # 只记录重要错误，避免日志过多
            if not self.is_connection_error(error_msg) or not hasattr(self.main_window, '_last_error_log_time'):
                pushErrorLog(context, access_token, lottery_type, error_msg)
                if not hasattr(self.main_window, '_last_error_log_time'):
                    self.main_window._last_error_log_time = {}
                self.main_window._last_error_log_time[context] = time.time()
        except Exception:
            pass
        
        # 尝试处理错误
        return self.handle_error(error, context, auto_recover=True)

