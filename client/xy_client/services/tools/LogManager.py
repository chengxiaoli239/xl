"""
统一的日志管理模块
提供优雅的日志记录接口，避免在每个地方都判断开关
"""

import json
import logging
import logging.handlers
import os
import threading
import time
from typing import Any, Optional, Union

from xy_client.services.tools.Configs import application_dir


class LogManager:
    """统一的日志管理类，避免在每个地方都判断开关"""
    
    def __init__(self, log_dir: str = 'logs', enable_detailed_logs: bool = False):
        """
        初始化日志管理器
        
        Args:
            log_dir: 日志文件目录
            enable_detailed_logs: 是否启用详细日志
        """
        self.enable_detailed_logs = enable_detailed_logs
        self.log_dir = log_dir
        self._logger_cache = {}
        self._logger_lock = threading.Lock()
        
        # 确保日志目录存在
        if not os.path.exists(self.log_dir):
            os.makedirs(self.log_dir)
        
        # 关键日志类型（始终在控制台显示）
        self.key_log_types = {
            'bet_success': '💰',
            'kj_push_success': '🎯', 
            'user_info': '👤'
        }
        
        # 日志级别表情符号
        self.level_emojis = {
            'debug': '🔍',
            'info': 'ℹ️',
            'warning': '⚠️',
            'error': '❌',
            'critical': '🚨'
        }
    
    def get_logger(self, business_type: str, logger_name: Optional[str] = None) -> logging.Logger:
        """
        获取指定业务类型的日志器
        
        Args:
            business_type: 业务类型（如：bet_tasks, sync_balance等）
            logger_name: 日志器名称，可选
            
        Returns:
            logging.Logger: 配置好的日志器
        """
        cache_key = f"{business_type}_{logger_name}" if logger_name else business_type
        
        with self._logger_lock:
            if cache_key not in self._logger_cache:
                logger = logging.getLogger(cache_key)
                logger.setLevel(logging.INFO)
                
                # 避免重复添加处理器
                if not logger.handlers:
                    # 创建业务专用日志文件
                    log_file = os.path.join(self.log_dir, f'{business_type}.log')
                    file_handler = logging.handlers.RotatingFileHandler(
                        log_file,
                        maxBytes=5*1024*1024,  # 5MB
                        backupCount=3,
                        encoding='utf-8'
                    )
                    file_handler.setFormatter(logging.Formatter(
                        '%(asctime)s - %(levelname)s - %(message)s',
                        '%Y-%m-%d %H:%M:%S'
                    ))
                    logger.addHandler(file_handler)
                    logger.propagate = False
                
                self._logger_cache[cache_key] = logger
        
        return self._logger_cache[cache_key]
    
    def log(self, business_type: str, message: Any, level: str = 'info', 
             log_type: Optional[str] = None, show_console: Optional[bool] = None) -> None:
        """
        统一的日志记录方法
        
        Args:
            business_type: 业务类型（如：bet_tasks, sync_balance等）
            message: 日志内容，兼容字符串、字典、列表等格式
            level: 日志级别 ('debug', 'info', 'warning', 'error', 'critical')
            log_type: 日志类型（如：bet_success, kj_push_success, user_info）
            show_console: 是否强制在控制台显示，None时自动判断
        """
        try:
            # 格式化消息内容
            formatted_message = self._format_message(message)
            
            # 获取日志器并记录到文件
            logger = self.get_logger(business_type)
            level = level.lower()
            
            if level == 'debug':
                logger.debug(formatted_message)
            elif level == 'info':
                logger.info(formatted_message)
            elif level == 'warning':
                logger.warning(formatted_message)
            elif level == 'error':
                logger.error(formatted_message)
            elif level == 'critical':
                logger.critical(formatted_message)
            else:
                logger.info(formatted_message)
            
            # 判断是否在控制台显示
            if show_console is None:
                # 自动判断：关键日志类型、错误级别、或详细日志开关开启
                should_show = (
                    log_type in self.key_log_types or 
                    level in ['error', 'critical'] or 
                    self.enable_detailed_logs
                )
            else:
                should_show = show_console
            
            # 在控制台显示
            if should_show:
                self._print_to_console(formatted_message, level, log_type)
                
        except Exception as e:
            # 日志记录失败时的兜底处理
            if self.enable_detailed_logs:
                print(f"❌ 日志记录失败: {e}")
                print(f"原始消息: {message}")
    
    def _format_message(self, message: Any) -> str:
        """
        格式化消息内容
        
        Args:
            message: 原始消息
            
        Returns:
            str: 格式化后的消息字符串
        """
        if isinstance(message, dict):
            return json.dumps(message, ensure_ascii=False, indent=2)
        elif isinstance(message, list):
            return json.dumps(message, ensure_ascii=False, indent=2)
        elif isinstance(message, (int, float, bool)):
            return str(message)
        else:
            return str(message)
    
    def _print_to_console(self, message: str, level: str, log_type: Optional[str]) -> None:
        """
        在控制台打印日志
        
        Args:
            message: 格式化后的消息
            level: 日志级别
            log_type: 日志类型
        """
        # 优先使用日志类型的表情符号
        if log_type and log_type in self.key_log_types:
            emoji = self.key_log_types[log_type]
        else:
            emoji = self.level_emojis.get(level, 'ℹ️')
        
        # 添加时间戳
        timestamp = time.strftime('%Y-%m-%d %H:%M:%S')
        print(f"{emoji} [{timestamp}] {message}")
    
    def log_key_info(self, message: str, log_type: str) -> None:
        """
        记录关键信息日志（始终显示）
        
        Args:
            message: 日志消息
            log_type: 日志类型
        """
        self.log('key_info', message, 'info', log_type, show_console=True)
    
    def log_system(self, message: Any, level: str = 'info', show_console: bool = False) -> None:
        """
        系统级日志记录
        
        Args:
            message: 日志消息
            level: 日志级别
            show_console: 是否在控制台显示
        """
        self.log('system', message, level, show_console=show_console)
    
    def log_business(self, business_type: str, message: Any, level: str = 'info', 
                     log_type: Optional[str] = None) -> None:
        """
        业务日志记录
        
        Args:
            business_type: 业务类型
            message: 日志消息
            level: 日志级别
            log_type: 日志类型
        """
        self.log(business_type, message, level, log_type)
    
    def set_detailed_logs(self, enabled: bool) -> None:
        """
        动态设置详细日志开关
        
        Args:
            enabled: 是否启用详细日志
        """
        self.enable_detailed_logs = enabled
    
    def get_log_status(self) -> dict:
        """
        获取日志系统状态
        
        Returns:
            dict: 包含日志系统状态信息的字典
        """
        return {
            'enable_detailed_logs': self.enable_detailed_logs,
            'log_dir': self.log_dir,
            'cached_loggers': len(self._logger_cache),
            'key_log_types': list(self.key_log_types.keys())
        }


# 创建按账号隔离的默认实例
_default_account_key = os.environ.get('LUCKY5_ACCOUNT_KEY', 'default')
default_log_manager = LogManager(
    log_dir=str(application_dir() / 'logs' / _default_account_key)
)


# 便捷函数，用于快速记录日志
def log_business(business_type: str, message: Any, level: str = 'info', 
                 log_type: Optional[str] = None) -> None:
    """记录业务日志"""
    default_log_manager.log_business(business_type, message, level, log_type)


def log_key_info(message: str, log_type: str) -> None:
    """记录关键信息日志"""
    default_log_manager.log_key_info(message, log_type)


def log_system(message: Any, level: str = 'info', show_console: bool = False) -> None:
    """记录系统日志"""
    default_log_manager.log_system(message, level, show_console)


def set_detailed_logs(enabled: bool) -> None:
    """设置详细日志开关"""
    default_log_manager.set_detailed_logs(enabled)


def get_log_status() -> dict:
    """获取日志系统状态"""
    return default_log_manager.get_log_status()
