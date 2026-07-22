"""
日志优化器
减少日志输出，避免影响性能
"""

import time
import sys
from typing import Dict, Optional
from collections import deque

# 日志频率限制配置
LOG_RATE_LIMIT = {
    'bet_task': {'max_per_minute': 12, 'last_logs': deque(maxlen=12)},  # 下注任务：每分钟最多12条（5秒一次）
    'bet_executor': {'max_per_minute': 60, 'last_logs': deque(maxlen=60)},  # 下注执行：每分钟最多60条
    'bet_plan': {'max_per_minute': 12, 'last_logs': deque(maxlen=12)},  # 下注计划：每分钟最多12条
    'browser_check': {'max_per_minute': 6, 'last_logs': deque(maxlen=6)},  # 浏览器检查：每分钟最多6条
    'state_check': {'max_per_minute': 6, 'last_logs': deque(maxlen=6)},  # 状态检查：每分钟最多6条
    'timer_restart': {'max_per_minute': 12, 'last_logs': deque(maxlen=12)},  # 定时器重启：每分钟最多12条
    'qihao_refresh': {'max_per_minute': 12, 'last_logs': deque(maxlen=12)},  # 期号刷新：每分钟最多12条
    'default': {'max_per_minute': 30, 'last_logs': deque(maxlen=30)}  # 默认：每分钟最多30条
}

# 日志级别控制
LOG_LEVELS = {
    'DEBUG': 0,
    'INFO': 1,
    'WARNING': 2,
    'ERROR': 3,
    'CRITICAL': 4
}

# 当前日志级别（默认INFO，只显示INFO及以上）
CURRENT_LOG_LEVEL = LOG_LEVELS['INFO']

# 静默模式（减少所有非关键日志）
SILENT_MODE = False

# 日志计数器（用于控制输出频率）
_log_counters: Dict[str, int] = {}
_last_reset_time = time.time()


def should_log(category: str = 'default', level: str = 'INFO', force: bool = False) -> bool:
    """
    判断是否应该输出日志
    
    Args:
        category: 日志类别
        level: 日志级别
        force: 是否强制输出
    
    Returns:
        bool: 是否应该输出
    """
    if force:
        return True
    
    if SILENT_MODE and level not in ['ERROR', 'CRITICAL']:
        return False
    
    # 检查日志级别
    if LOG_LEVELS.get(level, 1) < CURRENT_LOG_LEVEL:
        return False
    
    # 检查频率限制
    config = LOG_RATE_LIMIT.get(category, LOG_RATE_LIMIT['default'])
    last_logs = config['last_logs']
    max_per_minute = config['max_per_minute']
    
    current_time = time.time()
    
    # 清理1分钟前的日志记录
    while last_logs and current_time - last_logs[0] > 60:
        last_logs.popleft()
    
    # 检查是否超过频率限制
    if len(last_logs) >= max_per_minute:
        return False
    
    # 记录本次日志时间
    last_logs.append(current_time)
    return True


def optimized_print(message: str, category: str = 'default', level: str = 'INFO', force: bool = False):
    """
    优化的日志输出函数
    
    Args:
        message: 日志消息
        category: 日志类别
        level: 日志级别
        force: 是否强制输出
    """
    if not should_log(category, level, force):
        return
    
    try:
        # 使用非阻塞方式输出，避免卡顿
        print(message, flush=False)  # 不立即刷新，减少IO操作
        
        # 每10条日志才刷新一次，减少IO操作
        global _log_counters
        _log_counters[category] = _log_counters.get(category, 0) + 1
        if _log_counters[category] % 10 == 0:
            sys.stdout.flush()
    except Exception:
        # 日志输出失败不影响主流程
        pass


def set_log_level(level: str):
    """
    设置日志级别
    
    Args:
        level: 日志级别 (DEBUG, INFO, WARNING, ERROR, CRITICAL)
    """
    global CURRENT_LOG_LEVEL
    if level in LOG_LEVELS:
        CURRENT_LOG_LEVEL = LOG_LEVELS[level]


def set_silent_mode(enabled: bool):
    """
    设置静默模式
    
    Args:
        enabled: 是否启用静默模式
    """
    global SILENT_MODE
    SILENT_MODE = enabled


def reset_log_counters():
    """重置日志计数器"""
    global _log_counters, _last_reset_time
    current_time = time.time()
    
    # 每分钟重置一次计数器
    if current_time - _last_reset_time > 60:
        _log_counters.clear()
        _last_reset_time = current_time
        
        # 清理所有类别的日志记录
        for config in LOG_RATE_LIMIT.values():
            current_time = time.time()
            last_logs = config['last_logs']
            while last_logs and current_time - last_logs[0] > 60:
                last_logs.popleft()

