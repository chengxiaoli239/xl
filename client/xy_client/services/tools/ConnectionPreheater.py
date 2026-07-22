#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
连接预热模块
在程序启动时预热网络连接，减少首次请求失败的概率
"""

import time
import threading
from typing import Optional
from .GlobalSession import GlobalSession
from .Configs import Configs


class ConnectionPreheater:
    """连接预热器"""
    
    def __init__(self):
        self._preheated_domains = set()
        self._lock = threading.Lock()
        self._preheat_threads = []
    
    def preheat_domain(self, domain: str, timeout: int = 10) -> bool:
        """
        预热指定域名的连接
        
        Args:
            domain: 要预热的域名
            timeout: 预热超时时间
            
        Returns:
            bool: 预热是否成功
        """
        with self._lock:
            if domain in self._preheated_domains:
                return True
        
        try:
            print(f"🔄 开始预热连接: {domain}")
            session = GlobalSession.get_session()
            
            # 发送HEAD请求预热连接
            response = session.head(f"{domain}/", timeout=(5, timeout))
            
            if response.status_code in [200, 301, 302, 404, 405]:
                with self._lock:
                    self._preheated_domains.add(domain)
                print(f"✅ 连接预热成功: {domain}")
                return True
            else:
                print(f"⚠️ 连接预热异常，状态码: {response.status_code}")
                return False
                
        except Exception as e:
            print(f"❌ 连接预热失败: {domain} - {e}")
            return False
    
    def preheat_domain_async(self, domain: str, timeout: int = 10):
        """
        异步预热指定域名的连接
        
        Args:
            domain: 要预热的域名
            timeout: 预热超时时间
        """
        def _preheat():
            self.preheat_domain(domain, timeout)
        
        thread = threading.Thread(target=_preheat, daemon=True)
        thread.start()
        self._preheat_threads.append(thread)
    
    def preheat_multiple_domains(self, domains: list, timeout: int = 10) -> dict:
        """
        预热多个域名的连接
        
        Args:
            domains: 域名列表
            timeout: 预热超时时间
            
        Returns:
            dict: 预热结果 {domain: success}
        """
        results = {}
        threads = []
        
        def _preheat_domain(domain):
            results[domain] = self.preheat_domain(domain, timeout)
        
        # 创建预热线程
        for domain in domains:
            thread = threading.Thread(target=_preheat_domain, args=(domain,), daemon=True)
            thread.start()
            threads.append(thread)
        
        # 等待所有线程完成
        for thread in threads:
            thread.join(timeout=timeout + 5)  # 给额外5秒缓冲时间
        
        return results
    
    def is_preheated(self, domain: str) -> bool:
        """检查域名是否已预热"""
        with self._lock:
            return domain in self._preheated_domains
    
    def get_preheated_domains(self) -> set:
        """获取已预热的域名列表"""
        with self._lock:
            return self._preheated_domains.copy()
    
    def clear_preheated(self):
        """清除预热记录"""
        with self._lock:
            self._preheated_domains.clear()
            print("🧹 已清除所有预热记录")


# 全局连接预热器实例
_global_preheater = None

def get_connection_preheater() -> ConnectionPreheater:
    """获取全局连接预热器实例"""
    global _global_preheater
    if _global_preheater is None:
        _global_preheater = ConnectionPreheater()
    return _global_preheater

def preheat_robot_connections():
    """预热机器人相关连接"""
    preheater = get_connection_preheater()
    
    robot_domains = [Configs().get_config("robot_domain").rstrip("/")]
    
    print("🚀 开始预热机器人连接...")
    results = preheater.preheat_multiple_domains(robot_domains, timeout=15)
    
    success_count = sum(1 for success in results.values() if success)
    total_count = len(results)
    
    print(f"📊 连接预热完成: {success_count}/{total_count} 成功")
    
    return results

def preheat_connection_async(domain: str):
    """异步预热连接"""
    preheater = get_connection_preheater()
    preheater.preheat_domain_async(domain)


if __name__ == "__main__":
    # 测试代码
    print("🧪 测试连接预热模块...")
    
    # 创建预热器
    preheater = ConnectionPreheater()
    
    # 测试预热
    test_domains = ["http://httpbin.org", "https://httpbin.org"]
    results = preheater.preheat_multiple_domains(test_domains)
    
    print(f"📊 预热结果: {results}")
    print(f"✅ 已预热域名: {preheater.get_preheated_domains()}")
