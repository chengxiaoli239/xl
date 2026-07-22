import time
import requests
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry
from .GlobalSession import GlobalSession


class ConnectionOptimizer:
    """连接优化器 - 专门解决"每次都第一次失败，第二次成功"问题"""
    
    def __init__(self):
        self.connection_cache = {}
        self.last_warm_up = {}
        self.warm_up_interval = 300  # 5分钟预热一次
    
    def ensure_healthy_connection(self, domain, force_warm_up=False):
        """
        确保连接健康，如果不健康则预热
        
        Args:
            domain: 目标域名
            force_warm_up: 是否强制预热
            
        Returns:
            bool: 连接是否健康
        """
        current_time = time.time()
        
        # 检查是否需要预热
        if (force_warm_up or 
            domain not in self.last_warm_up or 
            current_time - self.last_warm_up.get(domain, 0) > self.warm_up_interval):
            
            print(f"🔄 开始连接健康检查和预热: {domain}")
            
            # 检查连接健康状态
            if not self._check_connection_health(domain):
                print(f"⚠️ 连接不健康，开始预热: {domain}")
                if self._warm_up_connection(domain):
                    self.last_warm_up[domain] = current_time
                    print(f"✅ 连接预热成功: {domain}")
                    return True
                else:
                    print(f"❌ 连接预热失败: {domain}")
                    return False
            else:
                print(f"✅ 连接健康，无需预热: {domain}")
                self.last_warm_up[domain] = current_time
                return True
        
        return True
    
    def _check_connection_health(self, domain):
        """检查连接健康状态"""
        try:
            session = GlobalSession.get_session()
            response = session.head(f"{domain}/", timeout=(3, 10))
            return response.status_code < 500
        except Exception as e:
            print(f"⚠️ 连接健康检查失败: {e}")
            return False
    
    def _warm_up_connection(self, domain):
        """预热网络连接"""
        try:
            session = GlobalSession.get_session()
            print(f"🔄 预热连接到: {domain}")
            
            # 发送多个预热请求，确保连接稳定
            warm_up_urls = [
                f"{domain}/",
                f"{domain}/favicon.ico",
                f"{domain}/robots.txt"
            ]
            
            success_count = 0
            for url in warm_up_urls:
                try:
                    response = session.head(url, timeout=(5, 10))
                    if response.status_code in [200, 301, 302, 404]:
                        success_count += 1
                        print(f"✅ 预热成功: {url}")
                    else:
                        print(f"⚠️ 预热异常: {url}, 状态码: {response.status_code}")
                except Exception as e:
                    print(f"⚠️ 预热失败: {url}, 错误: {e}")
            
            # 至少有一个预热成功就认为预热成功
            return success_count > 0
            
        except Exception as e:
            print(f"❌ 连接预热异常: {e}")
            return False
    
    def pre_request_check(self, domain):
        """
        请求前的连接检查，确保连接健康
        
        Args:
            domain: 目标域名
            
        Returns:
            bool: 是否可以进行请求
        """
        if self.ensure_healthy_connection(domain):
            print(f"✅ 连接检查通过，可以发送请求: {domain}")
            return True
        else:
            print(f"❌ 连接检查失败，建议延迟请求: {domain}")
            return False
    
    def post_request_analysis(self, domain, response, error=None):
        """
        请求后的连接分析，用于优化连接策略
        
        Args:
            domain: 目标域名
            response: 响应对象（如果成功）
            error: 错误信息（如果失败）
        """
        if error:
            if '10054' in str(error) or 'ConnectionResetError' in str(error):
                print(f"🚨 检测到连接重置错误，标记需要预热: {domain}")
                # 强制下次预热
                if domain in self.last_warm_up:
                    del self.last_warm_up[domain]
            else:
                print(f"⚠️ 其他类型错误: {error}")
        else:
            print(f"✅ 请求成功，连接状态良好: {domain}")


# 创建全局连接优化器实例
connection_optimizer = ConnectionOptimizer()


def optimize_bet_request(mainWindow, bet_url):
    """
    优化下注请求，确保连接健康
    
    Args:
        mainWindow: 主窗口实例
        bet_url: 下注URL
        
    Returns:
        bool: 是否可以进行下注请求
    """
    try:
        # 从URL中提取域名
        from urllib.parse import urlparse
        parsed_url = urlparse(bet_url)
        domain = f"{parsed_url.scheme}://{parsed_url.netloc}"
        
        # 检查连接健康状态
        if connection_optimizer.pre_request_check(domain):
            return True
        else:
            print(f"⚠️ 连接不健康，建议延迟下注请求: {domain}")
            return False
            
    except Exception as e:
        print(f"⚠️ 连接优化检查异常: {e}")
        return True  # 异常情况下允许继续请求


def warm_up_before_betting(mainWindow):
    """
    下注前的连接预热
    
    Args:
        mainWindow: 主窗口实例
        
    Returns:
        bool: 预热是否成功
    """
    try:
        if hasattr(mainWindow, 'domain_val') and mainWindow.domain_val.text():
            domain = mainWindow.domain_val.text()
            print(f"🔄 下注前预热连接: {domain}")
            return connection_optimizer.ensure_healthy_connection(domain, force_warm_up=True)
        else:
            print("⚠️ 无法获取域名信息，跳过连接预热")
            return True
    except Exception as e:
        print(f"⚠️ 连接预热异常: {e}")
        return True  # 异常情况下允许继续
