# -*- coding: utf-8 -*-
"""
网络请求配置管理
统一管理超时设置、重试策略等网络相关配置
"""

# 超时设置（秒）
TIMEOUT_CONFIG = {
    # 连接超时时间
    'CONNECT_TIMEOUT': 5,
    # 读取超时时间
    'READ_TIMEOUT': 15,
    # 默认超时元组 (连接超时, 读取超时)
    'DEFAULT_TIMEOUT': (5, 15),
    # 快速请求超时 (用于简单查询)
    'FAST_TIMEOUT': (3, 10),
    # 慢速请求超时 (用于复杂操作)
    'SLOW_TIMEOUT': (10, 30),
}

# 重试策略配置
RETRY_CONFIG = {
    # 最大重试次数
    'MAX_RETRIES': 2,
    # 重试间隔（秒）
    'RETRY_DELAY': 1,
    # 连接错误重试间隔（秒）
    'CONNECTION_RETRY_DELAY': 2,
    # 连接重置错误特殊重试间隔（秒）
    'CONNECTION_RESET_RETRY_DELAY': 4,
    # 需要重试的HTTP状态码
    'RETRY_STATUS_CODES': [500, 502, 503, 504, 408, 429],
    # 连接重置错误码
    'CONNECTION_RESET_CODES': [10054],
}

# 接口特定的超时配置
API_TIMEOUT_CONFIG = {
    # 期号相关接口
    'qihao': {
        'timeout': (5, 15),
        'max_retries': 2,
        'description': '期号查询接口'
    },
    # 余额同步接口
    'balance': {
        'timeout': (5, 15),
        'max_retries': 2,
        'description': '余额同步接口'
    },
    # 开奖数据接口
    'kj_data': {
        'timeout': (5, 20),
        'max_retries': 3,
        'description': '开奖数据接口'
    },
    # 用户信息接口
    'user_info': {
        'timeout': (5, 15),
        'max_retries': 2,
        'description': '用户信息接口'
    },
    # 下注接口
    'bet': {
        'timeout': (3, 20),  # 优化超时时间：连接3秒，读取20秒（应对盘口卡顿20-30秒的情况）
        'max_retries': 2,  # 减少重试次数（从3减少到2）
        'description': '下注接口'
    },
    # 获取下注计划接口（单独配置，更快响应）
    'bet_plan': {
        'timeout': (2, 3),  # 获取计划接口超时：连接2秒，读取3秒
        'max_retries': 1,  # 只重试1次
        'description': '获取下注计划接口'
    },
    # 外部接口（如官方开奖号码）
    'external': {
        'timeout': (8, 25),
        'max_retries': 2,
        'description': '外部接口'
    },
}

# 网络错误处理配置
ERROR_HANDLING_CONFIG = {
    # 是否在超时后重置session
    'RESET_SESSION_ON_TIMEOUT': True,
    # 是否记录详细的错误日志
    'LOG_DETAILED_ERRORS': True,
    # 是否在连接错误后等待更长时间
    'LONGER_WAIT_ON_CONNECTION_ERROR': True,
    # 最大连续错误次数，超过后重置session
    'MAX_CONSECUTIVE_ERRORS': 5,
}

def get_timeout_for_api(api_type, default_timeout=None):
    """
    获取指定API类型的超时配置
    
    Args:
        api_type: API类型 (qihao, balance, kj_data, user_info, bet)
        default_timeout: 默认超时设置
    
    Returns:
        tuple: (连接超时, 读取超时)
    """
    if api_type in API_TIMEOUT_CONFIG:
        return API_TIMEOUT_CONFIG[api_type]['timeout']
    return default_timeout or TIMEOUT_CONFIG['DEFAULT_TIMEOUT']

def get_retry_config_for_api(api_type):
    """
    获取指定API类型的重试配置
    
    Args:
        api_type: API类型
    
    Returns:
        dict: 重试配置
    """
    if api_type in API_TIMEOUT_CONFIG:
        return {
            'max_retries': API_TIMEOUT_CONFIG[api_type]['max_retries'],
            'retry_delay': RETRY_CONFIG['RETRY_DELAY'],
            'connection_retry_delay': RETRY_CONFIG['CONNECTION_RETRY_DELAY']
        }
    return {
        'max_retries': RETRY_CONFIG['MAX_RETRIES'],
        'retry_delay': RETRY_CONFIG['RETRY_DELAY'],
        'connection_retry_delay': RETRY_CONFIG['CONNECTION_RETRY_DELAY']
    }
