import time
from threading import Lock

class PushCache:
    def __init__(self):
        self._cache = {}
        self._lock = Lock()
        self.max_push_attempts = 3
        self.success_codes = [200, 300]  # 成功的状态码
        
    def can_push(self, qihao, response_status=None):
        """
        检查是否可以推送数据
        Args:
            qihao: 期号
            response_status: 接口返回的状态码
        Returns:
            bool: 是否可以推送
        """
        with self._lock:
            current_time = time.time()
            cache_info = self._cache.get(qihao, {
                'push_count': 0,
                'last_push_time': 0,
                'success': False,
                'last_kj_data': None
            })
            
            # 如果已经成功推送过，直接返回False
            if cache_info['success']:
                return False
                
            # 如果达到最大尝试次数，返回False
            if cache_info['push_count'] >= self.max_push_attempts:
                return False
                
            # 更新推送状态
            if response_status is not None:
                cache_info['success'] = response_status in self.success_codes
                
            # 更新推送次数和时间
            cache_info['push_count'] += 1
            cache_info['last_push_time'] = current_time
            self._cache[qihao] = cache_info
            
            return True
    
    def mark_success(self, qihao, kj_data=None):
        """
        标记期号推送成功
        Args:
            qihao: 期号
            kj_data: 开奖号码数据
        """
        with self._lock:
            if qihao not in self._cache:
                self._cache[qihao] = {
                    'push_count': 0,
                    'last_push_time': 0,
                    'success': False,
                    'last_kj_data': None
                }
            
            self._cache[qihao]['success'] = True
            self._cache[qihao]['last_push_time'] = time.time()
            if kj_data:
                self._cache[qihao]['last_kj_data'] = kj_data
    
    def mark_failed(self, qihao, response_status=400):
        """
        标记期号推送失败
        Args:
            qihao: 期号
            response_status: 响应状态码
        """
        with self._lock:
            if qihao not in self._cache:
                self._cache[qihao] = {
                    'push_count': 0,
                    'last_push_time': 0,
                    'success': False,
                    'last_kj_data': None
                }
            
            self._cache[qihao]['push_count'] += 1
            self._cache[qihao]['last_push_time'] = time.time()
            # 不设置success为True，允许重试
            
    def is_successful(self, qihao):
        """
        检查期号是否已经成功推送
        Args:
            qihao: 期号
        Returns:
            bool: 是否已成功推送
        """
        with self._lock:
            cache_info = self._cache.get(qihao, {})
            return cache_info.get('success', False)
    
    def get_cache_info(self, qihao):
        """
        获取期号的缓存信息
        Args:
            qihao: 期号
        Returns:
            dict: 缓存信息
        """
        with self._lock:
            return self._cache.get(qihao, {}).copy()
            
    def cleanup(self, max_age=3600):
        """清理旧的缓存数据"""
        with self._lock:
            current_time = time.time()
            self._cache = {
                qihao: info for qihao, info in self._cache.items()
                if current_time - info['last_push_time'] < max_age
            }
    
    def get_stats(self):
        """获取缓存统计信息"""
        with self._lock:
            total = len(self._cache)
            successful = sum(1 for info in self._cache.values() if info['success'])
            failed = total - successful
            return {
                'total': total,
                'successful': successful,
                'failed': failed
            }