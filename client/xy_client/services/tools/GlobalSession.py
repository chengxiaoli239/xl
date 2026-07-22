import requests
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry
import time
import random
import threading


class GlobalSession:
    _session = None
    _last_reset_time = 0
    _consecutive_errors = 0
    _lock = threading.Lock()

    @classmethod
    def get_session(cls):
        if cls._session is None:
            with cls._lock:
                if cls._session is None:  # 双重检查锁定
                    cls._session = requests.Session()
                    
                    # 配置重试策略 - 针对连接重置错误优化
                    retry_strategy = Retry(
                        total=3,  # 减少重试次数，避免长时间阻塞
                        backoff_factor=2,  # 增加重试间隔
                        status_forcelist=[500, 502, 503, 504, 10054],  # 包含连接重置错误
                        allowed_methods=["HEAD", "GET", "POST", "PUT", "DELETE", "OPTIONS", "TRACE"],
                        raise_on_status=False  # 不抛出异常，让上层处理
                    )
                    
                    # 创建全局的 Session 对象，优化连接池配置
                    # 增加连接池大小，解决"Connection pool is full"问题
                    adapter = HTTPAdapter(
                        pool_connections=50,  # 增加连接池数量（从10增加到50）
                        pool_maxsize=200,     # 增加最大连接数（从50增加到200）
                        max_retries=retry_strategy,
                        pool_block=False  # 不阻塞连接获取
                    )
                    cls._session.mount('http://', adapter)
                    cls._session.mount('https://', adapter)

                    # 优化超时设置 - 连接超时适中，避免长时间等待
                    cls._session.timeout = (8, 30)
                    
                    # 设置连接保活，减少连接被服务器断开
                    cls._session.headers.update({
                        'Connection': 'keep-alive',
                        'Keep-Alive': 'timeout=30, max=100',
                        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    })
                    
                    # 禁用SSL证书验证，解决证书不匹配问题
                    cls._session.verify = False
                    
                    # 禁用SSL警告和连接池满警告
                    import urllib3
                    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
                    # 禁用连接池满警告（pool_maxsize=200已足够，警告是误报）
                    try:
                        urllib3.disable_warnings(urllib3.exceptions.PoolWarning)
                    except AttributeError:
                        # 如果PoolWarning不存在，使用通用方式禁用所有警告
                        urllib3.disable_warnings()
                    
                    print("[OK] GlobalSession初始化完成，连接池配置优化")
        return cls._session

    @classmethod
    def set_default_timeout(cls, connect_timeout=5, read_timeout=30):
        """设置默认超时时间"""
        session = cls.get_session()
        session.timeout = (connect_timeout, read_timeout)
        return session

    @classmethod
    def reset_session(cls):
        """重置session，用于网络问题后的恢复"""
        with cls._lock:
            cls._session = None
            cls._consecutive_errors = 0
            cls._last_reset_time = time.time()
            print("🔄 GlobalSession已重置")
        return cls.get_session()
    
    @classmethod
    def record_error(cls, error_type="connection"):
        """记录错误，用于熔断器判断"""
        with cls._lock:
            cls._consecutive_errors += 1
            current_time = time.time()
            
            # 如果连续错误超过5次，且距离上次重置超过30秒，则自动重置
            if cls._consecutive_errors >= 5 and current_time - cls._last_reset_time > 30:
                print(f"🚨 连续错误{cls._consecutive_errors}次，触发自动重置")
                cls.reset_session()
            elif cls._consecutive_errors >= 3:
                print(f"⚠️ 连续错误{cls._consecutive_errors}次，建议检查网络状态")
    
    @classmethod
    def record_success(cls):
        """记录成功请求，重置错误计数"""
        with cls._lock:
            if cls._consecutive_errors > 0:
                print(f"✅ 网络请求成功，重置错误计数 (之前: {cls._consecutive_errors})")
                cls._consecutive_errors = 0
    
    @classmethod
    def should_backoff(cls):
        """判断是否应该退避（熔断器）"""
        with cls._lock:
            current_time = time.time()
            # 如果连续错误超过3次，且距离上次重置不到10秒，则退避
            return (cls._consecutive_errors >= 3 and 
                   current_time - cls._last_reset_time < 10)
    
    @classmethod
    def get_backoff_delay(cls):
        """获取退避延迟时间"""
        with cls._lock:
            # 根据连续错误次数计算延迟时间，最大10秒
            delay = min(2 ** cls._consecutive_errors, 10)
            # 添加随机抖动，避免多个客户端同时重试
            jitter = random.uniform(0.5, 1.5)
            return delay * jitter
    
    @classmethod
    def warm_up_connection(cls, domain):
        """预热网络连接，解决"第一次失败，第二次成功"问题"""
        try:
            session = cls.get_session()
            print(f"🔄 开始预热网络连接到: {domain}")
            
            # 发送一个简单的HEAD请求来预热连接
            response = session.head(f"{domain}/", timeout=(5, 10))
            if response.status_code in [200, 301, 302, 404]:
                print("✅ 网络连接预热成功")
                return True
            else:
                print(f"⚠️ 网络连接预热异常，状态码: {response.status_code}")
                return False
        except Exception as e:
            print(f"⚠️ 网络连接预热失败: {e}")
            return False
    
    @classmethod
    def check_connection_health(cls, domain):
        """检查连接健康状态"""
        try:
            session = cls.get_session()
            response = session.head(f"{domain}/", timeout=(3, 10))
            return response.status_code < 500
        except Exception:
            return False

# 在其他组件中获取全局 Session 对象
#session = GlobalSession.get_session()
#response = session.get('https://example.com')