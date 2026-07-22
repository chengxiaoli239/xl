# -*- coding: utf-8 -*-
"""
日志配置文件
用于控制哪些日志显示在控制台，哪些只写入文件
"""

# 控制台显示的关键日志类型
CONSOLE_LOG_TYPES = {
    # 登录相关
    'login_success': True,      # 登录成功
    'login_failed': True,       # 登录失败
    
    # 开奖相关
    'kj_push_success': True,    # 开奖号码推送成功
    'kj_push_failed': True,     # 开奖号码推送失败
    
    # 下注相关
    'bet_success': True,        # 下注成功
    'bet_failed': True,         # 下注失败
    
    # 系统错误
    'system_error': True,       # 系统级错误
    'network_error': True,      # 网络错误
    
    # 其他重要信息
    'balance_sync': False,       # 余额同步
    'task_start': False,        # 任务开始
    'task_complete': False,     # 任务完成
    'task_timeout': True,       # 任务超时
    'chrome_memory': False,     # Chrome内存警告
    'debug_info': False,        # 调试信息
}

def should_show_in_console(log_type):
    """
    判断指定类型的日志是否应该在控制台显示
    
    Args:
        log_type (str): 日志类型
        
    Returns:
        bool: 是否在控制台显示
    """
    return CONSOLE_LOG_TYPES.get(log_type, False)

def get_console_log_types():
    """
    获取所有应该在控制台显示的日志类型
    
    Returns:
        list: 日志类型列表
    """
    return [k for k, v in CONSOLE_LOG_TYPES.items() if v]

def get_file_only_log_types():
    """
    获取只写入文件的日志类型
    
    Returns:
        list: 日志类型列表
    """
    return [k for k, v in CONSOLE_LOG_TYPES.items() if not v]
