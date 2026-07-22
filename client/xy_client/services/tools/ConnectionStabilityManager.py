#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
连接稳定性管理器
专门解决ConnectionResetError(10054)和浏览器连接稳定性问题
"""

import time
import random
import threading
import requests
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry
from urllib3.exceptions import MaxRetryError, ConnectionError as Urllib3ConnectionError
import socket
from typing import Optional, Dict, Any

from .Configs import Configs


class ConnectionStabilityManager:
    """连接稳定性管理器"""
    
    def __init__(self):
        self._lock = threading.Lock()
        self._connection_stats = {
            'total_requests': 0,
            'successful_requests': 0,
            'failed_requests': 0,
            'connection_reset_errors': 0,
            'last_reset_time': 0,
            'consecutive_failures': 0,
            'max_consecutive_failures': 0
        }
        self._session_pools = {}
        self._backoff_until = 0
        self._circuit_breaker_threshold = 5
        self._circuit_breaker_timeout = 60
        
    def create_stable_session(self, pool_name="default", **kwargs):
        """创建稳定的连接会话"""
        with self._lock:
            if pool_name not in self._session_pools:
                session = requests.Session()
                
                # 针对10054错误优化的重试策略
                retry_strategy = Retry(
                    total=5,  # 增加重试次数
                    backoff_factor=1.5,  # 指数退避
                    status_forcelist=[500, 502, 503, 504, 429],
                    allowed_methods=["HEAD", "GET", "POST", "PUT", "DELETE", "OPTIONS", "TRACE"],
                    raise_on_status=False
                )
                
                # 优化的连接池配置 - 增加连接池大小，解决"Connection pool is full"问题
                adapter = HTTPAdapter(
                    pool_connections=50,  # 增加连接池数量（从5增加到50）
                    pool_maxsize=200,      # 增加最大连接数（从20增加到200）
                    max_retries=retry_strategy,
                    pool_block=False       # 不阻塞，连接池满时创建新连接
                )
                
                session.mount('http://', adapter)
                session.mount('https://', adapter)
                
                # 优化超时设置
                session.timeout = (10, 30)
                
                # 设置连接保活和用户代理
                session.headers.update({
                    'Connection': 'keep-alive',
                    'Keep-Alive': 'timeout=60, max=1000',
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept': 'application/json, text/plain, */*',
                    'Accept-Language': 'zh-CN,zh;q=0.9,en;q=0.8',
                    'Accept-Encoding': 'gzip, deflate, br',
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                })
                
                self._session_pools[pool_name] = session
                print(f"✅ 创建稳定连接池: {pool_name}")
            
            return self._session_pools[pool_name]
    
    def safe_request(self, method, url, pool_name="default", **kwargs):
        """安全的网络请求，专门处理10054错误"""
        # 检查熔断器状态
        if self._is_circuit_breaker_open():
            backoff_time = self._backoff_until - time.time()
            if backoff_time > 0:
                print(f"🚨 熔断器开启，等待 {backoff_time:.1f} 秒...")
                time.sleep(backoff_time)
            else:
                self._reset_circuit_breaker()
        
        # 检查退避状态
        if self._should_backoff():
            backoff_delay = self._get_backoff_delay()
            print(f"⏳ 退避等待 {backoff_delay:.1f} 秒...")
            time.sleep(backoff_delay)
        
        # 强制使用 "default" pool_name，确保所有请求复用同一个Session
        session = self.create_stable_session("default")
        
        # 执行请求
        max_retries = 3
        for attempt in range(max_retries + 1):
            try:
                self._record_request_attempt()
                
                # 添加随机延迟，避免多个客户端同时请求
                if attempt > 0:
                    jitter = random.uniform(0.5, 2.0)
                    time.sleep(jitter)
                
                response = session.request(method, url, **kwargs)
                
                # 请求成功
                self._record_success()
                return response
                
            except (requests.exceptions.ConnectionError, 
                    requests.exceptions.Timeout,
                    Urllib3ConnectionError,
                    MaxRetryError) as e:
                
                error_str = str(e)
                print(f"❌ 连接错误 (尝试 {attempt + 1}/{max_retries + 1}): {error_str}")
                
                # 特殊处理10054错误
                if "10054" in error_str or "ConnectionResetError" in error_str:
                    self._record_connection_reset_error()
                    print("🔄 检测到连接重置错误10054，使用特殊处理策略...")
                    
                    # 重置连接池
                    self._reset_session_pool(pool_name)
                    
                    # 延长等待时间
                    if attempt < max_retries:
                        extended_delay = 3 + random.uniform(1, 3)  # 3-6秒
                        print(f"⏳ 连接重置错误，等待 {extended_delay:.1f} 秒后重试...")
                        time.sleep(extended_delay)
                        continue
                
                # 记录失败
                self._record_failure()
                
                # 如果是最后一次尝试，触发熔断器
                if attempt == max_retries:
                    self._trigger_circuit_breaker()
                    return None
                
                # 普通重试延迟
                if attempt < max_retries:
                    delay = 2 ** attempt + random.uniform(0.5, 1.5)
                    print(f"⏳ 等待 {delay:.1f} 秒后重试...")
                    time.sleep(delay)
            
            except Exception as e:
                print(f"❌ 请求异常 (尝试 {attempt + 1}/{max_retries + 1}): {e}")
                self._record_failure()
                
                if attempt == max_retries:
                    self._trigger_circuit_breaker()
                    return None
                
                if attempt < max_retries:
                    time.sleep(1)
        
        return None
    
    def _is_circuit_breaker_open(self):
        """检查熔断器是否开启"""
        return time.time() < self._backoff_until
    
    def _reset_circuit_breaker(self):
        """重置熔断器"""
        self._backoff_until = 0
        with self._lock:
            self._connection_stats['consecutive_failures'] = 0
        print("✅ 熔断器已重置")
    
    def _should_backoff(self):
        """判断是否应该退避"""
        with self._lock:
            return self._connection_stats['consecutive_failures'] >= 3
    
    def _get_backoff_delay(self):
        """获取退避延迟时间"""
        with self._lock:
            failures = self._connection_stats['consecutive_failures']
        # 指数退避，最大30秒
        delay = min(2 ** failures, 30)
        # 添加随机抖动
        jitter = random.uniform(0.5, 1.5)
        return delay * jitter
    
    def _trigger_circuit_breaker(self):
        """触发熔断器"""
        self._backoff_until = time.time() + self._circuit_breaker_timeout
        print(f"🚨 熔断器触发，{self._circuit_breaker_timeout}秒内暂停请求")
    
    def _reset_session_pool(self, pool_name):
        """重置连接池"""
        with self._lock:
            if pool_name in self._session_pools:
                try:
                    self._session_pools[pool_name].close()
                except:
                    pass
                del self._session_pools[pool_name]
                print(f"🔄 重置连接池: {pool_name}")
    
    def _record_request_attempt(self):
        """记录请求尝试"""
        with self._lock:
            self._connection_stats['total_requests'] += 1
    
    def _record_success(self):
        """记录成功请求"""
        with self._lock:
            self._connection_stats['successful_requests'] += 1
            self._connection_stats['consecutive_failures'] = 0
            self._connection_stats['last_reset_time'] = time.time()
    
    def _record_failure(self):
        """记录失败请求"""
        with self._lock:
            self._connection_stats['failed_requests'] += 1
            self._connection_stats['consecutive_failures'] += 1
            if self._connection_stats['consecutive_failures'] > self._connection_stats['max_consecutive_failures']:
                self._connection_stats['max_consecutive_failures'] = self._connection_stats['consecutive_failures']
    
    def _record_connection_reset_error(self):
        """记录连接重置错误"""
        with self._lock:
            self._connection_stats['connection_reset_errors'] += 1
    
    def get_connection_stats(self):
        """获取连接统计信息"""
        with self._lock:
            stats = self._connection_stats.copy()
            stats['success_rate'] = (
                stats['successful_requests'] / stats['total_requests'] * 100 
                if stats['total_requests'] > 0 else 0
            )
            stats['is_circuit_breaker_open'] = self._is_circuit_breaker_open()
            stats['backoff_remaining'] = max(0, self._backoff_until - time.time())
            return stats
    
    def reset_all_connections(self):
        """重置所有连接"""
        with self._lock:
            for pool_name in list(self._session_pools.keys()):
                self._reset_session_pool(pool_name)
            self._connection_stats = {
                'total_requests': 0,
                'successful_requests': 0,
                'failed_requests': 0,
                'connection_reset_errors': 0,
                'last_reset_time': time.time(),
                'consecutive_failures': 0,
                'max_consecutive_failures': 0
            }
            self._backoff_until = 0
        print("🔄 所有连接已重置")
    
    def health_check(self, url=None):
        """连接健康检查"""
        try:
            url = url or Configs().get_config("robot_domain").rstrip("/")
            response = self.safe_request('HEAD', url, timeout=(5, 10))
            if response and response.status_code < 500:
                print("✅ 连接健康检查通过")
                return True
            else:
                print("⚠️ 连接健康检查失败")
                return False
        except Exception as e:
            print(f"❌ 连接健康检查异常: {e}")
            return False


# 全局连接稳定性管理器实例
_global_connection_manager = None

def get_connection_manager() -> ConnectionStabilityManager:
    """获取全局连接稳定性管理器"""
    global _global_connection_manager
    if _global_connection_manager is None:
        _global_connection_manager = ConnectionStabilityManager()
    return _global_connection_manager

def safe_request(method, url, **kwargs):
    """安全的网络请求便捷函数"""
    manager = get_connection_manager()
    return manager.safe_request(method, url, **kwargs)

def get_connection_stats():
    """获取连接统计信息"""
    manager = get_connection_manager()
    return manager.get_connection_stats()

def reset_all_connections():
    """重置所有连接"""
    manager = get_connection_manager()
    manager.reset_all_connections()

def health_check(url=None):
    """连接健康检查"""
    manager = get_connection_manager()
    return manager.health_check(url)


if __name__ == "__main__":
    # 测试代码
    print("🧪 测试连接稳定性管理器...")
    
    manager = ConnectionStabilityManager()
    
    # 测试健康检查
    print("🔍 执行健康检查...")
    health = manager.health_check()
    print(f"健康检查结果: {health}")
    
    # 测试统计信息
    stats = manager.get_connection_stats()
    print(f"连接统计: {stats}")
    
    # 测试重置
    print("🔄 重置所有连接...")
    manager.reset_all_connections()
    
    print("✅ 测试完成")
