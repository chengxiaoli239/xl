"""
定时器管理器
统一管理所有定时器，避免重复启动
"""

import threading
from typing import Dict, Optional, Callable, Any
from xy_client.services.MyThreading import MyThreadingTimer


class TimerManager:
    """定时器管理器 - 统一管理所有定时器"""
    
    def __init__(self, main_window):
        """
        初始化定时器管理器
        
        Args:
            main_window: 主窗口实例
        """
        self.main_window = main_window
        self._active_timers: Dict[str, threading.Timer] = {}
        self._timer_lock = threading.Lock()
    
    def _get_timer_key(self, name: str, *args) -> str:
        """
        生成定时器键
        
        Args:
            name: 定时器名称
            *args: 额外参数用于区分不同的定时器实例
        
        Returns:
            str: 定时器键
        """
        if args:
            return f"{name}_{args}"
        return name
    
    def _is_timer_active(self, timer_key: str) -> bool:
        """
        检查定时器是否活跃
        
        Args:
            timer_key: 定时器键
        
        Returns:
            bool: 是否活跃
        """
        timer = self._active_timers.get(timer_key)
        if timer is None:
            return False
        
        # 检查定时器是否还在运行
        return timer.is_alive()
    
    def start_timer(self, name: str, interval: float, func: Callable, args: tuple = (), 
                   force_restart: bool = False) -> bool:
        """
        启动定时器
        
        Args:
            name: 定时器名称
            interval: 间隔时间（秒）
            func: 定时器函数
            args: 函数参数
            force_restart: 是否强制重启（如果已存在）
        
        Returns:
            bool: 是否成功启动
        """
        timer_key = self._get_timer_key(name, *args)
        
        with self._timer_lock:
            # 如果定时器已存在且活跃，且不强制重启，则跳过
            if self._is_timer_active(timer_key) and not force_restart:
                # 静默跳过，不输出日志
                return False
            
            # 如果定时器存在但不活跃，先清理
            if timer_key in self._active_timers:
                try:
                    self._active_timers[timer_key].cancel()
                except Exception:
                    pass
                del self._active_timers[timer_key]
            
            # 启动新定时器
            try:
                timer = MyThreadingTimer.myTimer(interval, func, args)
                if timer:
                    self._active_timers[timer_key] = timer
                    # 减少定时器启动日志（只在DEBUG级别输出）
                    from xy_client.services.Lucky5.utils.log_optimizer import optimized_print
                    optimized_print(f"✅ [TimerManager] 定时器 '{name}' 启动成功 (间隔: {interval}秒)",
                                   category='timer_restart', level='DEBUG')
                    return True
                else:
                    from xy_client.services.Lucky5.utils.log_optimizer import optimized_print
                    optimized_print(f"❌ [TimerManager] 定时器 '{name}' 启动失败: 返回None",
                                   category='timer_restart', level='ERROR', force=True)
                    return False
            except Exception as e:
                from xy_client.services.Lucky5.utils.log_optimizer import optimized_print
                optimized_print(f"❌ [TimerManager] 定时器 '{name}' 启动失败: {e}",
                               category='timer_restart', level='ERROR', force=True)
                return False
    
    def stop_timer(self, name: str, *args) -> bool:
        """
        停止定时器
        
        Args:
            name: 定时器名称
            *args: 额外参数
        
        Returns:
            bool: 是否成功停止
        """
        timer_key = self._get_timer_key(name, *args)
        
        with self._timer_lock:
            timer = self._active_timers.get(timer_key)
            if timer is None:
                return False
            
            try:
                timer.cancel()
                del self._active_timers[timer_key]
                print(f"✅ [TimerManager] 定时器 '{name}' 已停止")
                return True
            except Exception as e:
                print(f"⚠️ [TimerManager] 停止定时器 '{name}' 失败: {e}")
                return False
    
    def stop_all_timers(self):
        """停止所有定时器"""
        with self._timer_lock:
            for timer_key, timer in list(self._active_timers.items()):
                try:
                    timer.cancel()
                except Exception:
                    pass
            
            self._active_timers.clear()
            print("✅ [TimerManager] 所有定时器已停止")
    
    # ==================== 具体定时器方法 ====================
    
    def start_bet_task_timer(self, interval: float = 5, direct: int = 1, force_restart: bool = False) -> bool:
        """
        启动下注任务定时器
        
        Args:
            interval: 间隔时间（秒），默认5秒
            direct: 下注方向
            force_restart: 是否强制重启
        
        Returns:
            bool: 是否成功启动
        """
        from xy_client.services.Lucky5 import Lucky
        return self.start_timer(
            name='bet_task',
            interval=interval,
            func=Lucky.betTasksTimer,
            args=(self.main_window, direct),
            force_restart=force_restart
        )
    
    def start_balance_sync_timer(self, interval: float = 60, force_restart: bool = False) -> bool:
        """
        启动余额同步定时器
        
        Args:
            interval: 间隔时间（秒），默认60秒
            force_restart: 是否强制重启
        
        Returns:
            bool: 是否成功启动
        """
        from xy_client.services.systems_users.SystemsUsers import syncBalanceTimer
        return self.start_timer(
            name='balance_sync',
            interval=interval,
            func=syncBalanceTimer,
            args=(interval, self.main_window),
            force_restart=force_restart
        )
    
    def get_active_timers(self) -> list:
        """获取所有活跃的定时器名称"""
        with self._timer_lock:
            return [key for key, timer in self._active_timers.items() if timer.is_alive()]

