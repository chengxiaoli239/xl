#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
网络请求容错模块
处理网络连接中断和请求失败，确保系统稳定运行
"""

import time
import random
import threading
from typing import Optional, Callable, Any
import requests
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry

class NetworkResilience:
    """网络请求容错器"""
    
    def __init__(self):
        self.max_retries = 3
        self.backoff_factor = 1
        self.status_forcelist = [500, 502, 503, 504, 10054]  # 包含连接重置错误
        self.retry_strategy = None
        self.session = None
        self._setup_session()
        
        # 错误统计
        self.error_count = 0
        self.success_count = 0
        self.last_error_time = 0
        self.error_lock = threading.Lock()
        
        # 自动恢复机制
        self.auto_recovery_enabled = True
        self.recovery_threshold = 5  # 连续5次错误后自动恢复
        self.recovery_cooldown = 60  # 恢复后冷却60秒
    
    def _setup_session(self):
        """设置请求会话"""
        try:
            # 创建重试策略
            self.retry_strategy = Retry(
                total=self.max_retries,
                backoff_factor=self.backoff_factor,
                status_forcelist=self.status_forcelist,
                allowed_methods=["HEAD", "GET", "POST", "PUT", "DELETE", "OPTIONS", "TRACE"]
            )
            
            # 创建会话
            self.session = requests.Session()
            adapter = HTTPAdapter(max_retries=self.retry_strategy)
            self.session.mount("http://", adapter)
            self.session.mount("https://", adapter)
            
            # 设置超时
            self.session.timeout = (10, 30)  # 连接超时10秒，读取超时30秒
            
            print("✅ 网络容错会话创建成功")
            
        except Exception as e:
            print(f"⚠️ 网络容错会话创建失败: {e}")
            # 使用默认会话作为备用
            self.session = requests.Session()
    
    def safe_request(self, method: str, url: str, **kwargs) -> Optional[requests.Response]:
        """安全的网络请求，带容错处理"""
        try:
            # 检查是否需要自动恢复
            if self._should_trigger_recovery():
                self._perform_auto_recovery()
            
            # 执行请求
            response = self.session.request(method, url, **kwargs)
            
            # 记录成功
            with self.error_lock:
                self.success_count += 1
                self.error_count = 0  # 重置错误计数
            
            return response
            
        except requests.exceptions.ConnectionError as e:
            self._handle_connection_error(e, url)
            return None
        except requests.exceptions.Timeout as e:
            self._handle_timeout_error(e, url)
            return None
        except requests.exceptions.RequestException as e:
            self._handle_request_error(e, url)
            return None
        except Exception as e:
            self._handle_unknown_error(e, url)
            return None
    
    def _should_trigger_recovery(self) -> bool:
        """检查是否应该触发自动恢复"""
        if not self.auto_recovery_enabled:
            return False
        
        current_time = time.time()
        with self.error_lock:
            # 如果错误次数超过阈值且不在冷却期
            return (self.error_count >= self.recovery_threshold and 
                   current_time - self.last_error_time > self.recovery_cooldown)
    
    def _perform_auto_recovery(self):
        """执行自动恢复"""
        try:
            print("🚨 触发网络自动恢复机制...")
            
            # 重新创建会话
            self._setup_session()
            
            # 重置错误计数
            with self.error_lock:
                self.error_count = 0
                self.last_error_time = time.time()
            
            print("✅ 网络自动恢复完成")
            
        except Exception as e:
            print(f"❌ 网络自动恢复失败: {e}")
    
    def _handle_connection_error(self, error: Exception, url: str):
        """处理连接错误"""
        self._record_error()
        print(f"⚠️ 连接错误 [{url}]: {error}")
        
        # 特殊处理连接重置错误
        if "10054" in str(error) or "ConnectionResetError" in str(error):
            print("🔄 检测到连接重置，等待后重试...")
            time.sleep(random.uniform(1, 3))  # 随机等待1-3秒
    
    def _handle_timeout_error(self, error: Exception, url: str):
        """处理超时错误"""
        self._record_error()
        print(f"⏰ 请求超时 [{url}]: {error}")
    
    def _handle_request_error(self, error: Exception, url: str):
        """处理请求错误"""
        self._record_error()
        print(f"❌ 请求错误 [{url}]: {error}")
    
    def _handle_unknown_error(self, error: Exception, url: str):
        """处理未知错误"""
        self._record_error()
        print(f"❓ 未知错误 [{url}]: {error}")
    
    def _record_error(self):
        """记录错误"""
        with self.error_lock:
            self.error_count += 1
            self.last_error_time = time.time()
    
    def get_status(self) -> dict:
        """获取网络状态"""
        with self.error_lock:
            return {
                'error_count': self.error_count,
                'success_count': self.success_count,
                'last_error_time': time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(self.last_error_time)),
                'auto_recovery_enabled': self.auto_recovery_enabled,
                'recovery_threshold': self.recovery_threshold,
                'session_healthy': self.session is not None
            }
    
    def reset_error_count(self):
        """重置错误计数"""
        with self.error_lock:
            self.error_count = 0
            self.success_count = 0
            print("✅ 错误计数已重置")
    
    def enable_auto_recovery(self, enabled: bool = True):
        """启用/禁用自动恢复"""
        self.auto_recovery_enabled = enabled
        print(f"✅ 自动恢复已{'启用' if enabled else '禁用'}")


# 全局网络容错实例
_global_network_resilience = None

def get_network_resilience() -> NetworkResilience:
    """获取全局网络容错实例"""
    global _global_network_resilience
    if _global_network_resilience is None:
        _global_network_resilience = NetworkResilience()
    return _global_network_resilience

def safe_request(method: str, url: str, **kwargs) -> Optional[requests.Response]:
    """安全的网络请求"""
    return get_network_resilience().safe_request(method, url, **kwargs)

def get_network_status() -> dict:
    """获取网络状态"""
    return get_network_resilience().get_status()

def reset_network_errors():
    """重置网络错误计数"""
    get_network_resilience().reset_error_count()


# 装饰器：自动重试失败的请求
def resilient_request(max_retries: int = 3, delay: float = 1.0):
    """网络请求容错装饰器"""
    def decorator(func: Callable) -> Callable:
        def wrapper(*args, **kwargs):
            last_error = None
            
            for attempt in range(max_retries + 1):
                try:
                    result = func(*args, **kwargs)
                    if attempt > 0:
                        print(f"✅ 请求成功 (重试 {attempt} 次后)")
                    return result
                    
                except Exception as e:
                    last_error = e
                    if attempt < max_retries:
                        print(f"⚠️ 请求失败 (尝试 {attempt + 1}/{max_retries + 1}): {e}")
                        print(f"🔄 {delay * (2 ** attempt)} 秒后重试...")
                        time.sleep(delay * (2 ** attempt))
                    else:
                        print(f"❌ 请求最终失败 (已重试 {max_retries} 次): {e}")
            
            # 如果所有重试都失败，返回None而不是抛出异常
            return None
        
        return wrapper
    return decorator


if __name__ == "__main__":
    # 测试代码
    print("🧪 测试网络容错模块...")
    
    # 创建实例
    resilience = NetworkResilience()
    
    # 测试状态
    status = resilience.get_status()
    print(f"📊 网络状态: {status}")
    
    # 测试安全请求
    print("🔄 测试安全请求...")
    response = resilience.safe_request("GET", "https://httpbin.org/status/200")
    if response:
        print(f"✅ 请求成功: {response.status_code}")
    else:
        print("❌ 请求失败")
    
    # 测试错误处理
    print("🔄 测试错误处理...")
    response = resilience.safe_request("GET", "https://httpbin.org/status/500")
    if response:
        print(f"⚠️ 请求返回错误状态: {response.status_code}")
    else:
        print("✅ 错误被正确处理")
    
    # 最终状态
    final_status = resilience.get_status()
    print(f"📊 最终状态: {final_status}")
