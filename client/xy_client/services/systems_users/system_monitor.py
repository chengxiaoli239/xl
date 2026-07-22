# -*- coding: utf-8 -*-
"""
系统监控工具
用于检测和诊断系统卡住的问题
"""

import time
import threading
import psutil
import os
from datetime import datetime, timedelta

class SystemMonitor:
    def __init__(self):
        self.monitoring = False
        self.monitor_thread = None
        self.last_activity_time = time.time()
        self.activity_log = []
        self.max_log_size = 1000
        self.heartbeat_interval = 30  # 30秒心跳检测
        
    def start_monitoring(self):
        """启动系统监控"""
        if self.monitoring:
            return
            
        self.monitoring = True
        self.monitor_thread = threading.Thread(target=self._monitor_loop, daemon=True)
        self.monitor_thread.start()
        print("🔄 系统监控已启动")
        
    def stop_monitoring(self):
        """停止系统监控"""
        self.monitoring = False
        if self.monitor_thread:
            self.monitor_thread.join(timeout=5)
        print("⏹️ 系统监控已停止")
        
    def record_activity(self, activity_type, details=""):
        """记录系统活动"""
        current_time = time.time()
        self.last_activity_time = current_time
        
        log_entry = {
            'timestamp': current_time,
            'datetime': datetime.fromtimestamp(current_time).strftime('%Y-%m-%d %H:%M:%S'),
            'type': activity_type,
            'details': details
        }
        
        self.activity_log.append(log_entry)
        
        # 保持日志大小在限制内
        if len(self.activity_log) > self.max_log_size:
            self.activity_log = self.activity_log[-self.max_log_size:]
            
    def _monitor_loop(self):
        """监控主循环"""
        while self.monitoring:
            try:
                current_time = time.time()
                time_since_last_activity = current_time - self.last_activity_time
                
                # 检查系统是否卡住（超过5分钟没有活动）
                if time_since_last_activity > 300:  # 5分钟
                    self._handle_system_hang(time_since_last_activity)
                
                # 检查系统资源使用情况
                self._check_system_resources()
                
                # 记录心跳
                self.record_activity('heartbeat', f'监控心跳 - 距离上次活动: {time_since_last_activity:.1f}秒')
                
                # 等待下次检查
                time.sleep(self.heartbeat_interval)
                
            except Exception as e:
                print(f"❌ 系统监控异常: {e}")
                time.sleep(10)
                
    def _handle_system_hang(self, time_since_last_activity):
        """处理系统卡住的情况"""
        print(f"🚨 检测到系统可能卡住！距离上次活动: {time_since_last_activity:.1f}秒")
        
        # 记录卡住事件
        self.record_activity('system_hang', f'系统卡住 - 距离上次活动: {time_since_last_activity:.1f}秒')
        
        # 尝试获取系统状态信息
        try:
            # 获取进程信息
            process = psutil.Process(os.getpid())
            cpu_percent = process.cpu_percent()
            memory_info = process.memory_info()
            
            print(f"📊 当前进程状态:")
            print(f"   CPU使用率: {cpu_percent}%")
            print(f"   内存使用: {memory_info.rss / 1024 / 1024:.1f} MB")
            
            # 获取线程信息
            threads = process.threads()
            print(f"   线程数量: {len(threads)}")
            
            # 检查是否有线程卡住
            for i, thread in enumerate(threads):
                if thread.user_time > 60:  # 用户态时间超过60秒
                    print(f"   ⚠️ 线程 {i} 可能卡住，用户态时间: {thread.user_time:.1f}秒")
                    
        except Exception as e:
            print(f"❌ 获取系统状态信息失败: {e}")
            
    def _check_system_resources(self):
        """检查系统资源使用情况"""
        try:
            # CPU使用率
            cpu_percent = psutil.cpu_percent(interval=1)
            
            # 内存使用情况
            memory = psutil.virtual_memory()
            
            # 磁盘使用情况
            disk = psutil.disk_usage('/')
            
            # 如果资源使用异常，记录警告
            if cpu_percent > 90:
                self.record_activity('resource_warning', f'CPU使用率过高: {cpu_percent}%')
                print(f"⚠️ CPU使用率过高: {cpu_percent}%")
                
            if memory.percent > 90:
                self.record_activity('resource_warning', f'内存使用率过高: {memory.percent}%')
                print(f"⚠️ 内存使用率过高: {memory.percent}%")
                
            if disk.percent > 90:
                self.record_activity('resource_warning', f'磁盘使用率过高: {disk.percent}%')
                print(f"⚠️ 磁盘使用率过高: {disk.percent}%")
                
        except Exception as e:
            print(f"❌ 检查系统资源失败: {e}")
            
    def get_activity_summary(self, hours=1):
        """获取活动摘要"""
        current_time = time.time()
        cutoff_time = current_time - (hours * 3600)
        
        recent_activities = [
            activity for activity in self.activity_log 
            if activity['timestamp'] > cutoff_time
        ]
        
        # 按类型统计
        activity_counts = {}
        for activity in recent_activities:
            activity_type = activity['type']
            activity_counts[activity_type] = activity_counts.get(activity_type, 0) + 1
            
        return {
            'total_activities': len(recent_activities),
            'activity_counts': activity_counts,
            'last_activity_time': self.last_activity_time,
            'time_since_last_activity': current_time - self.last_activity_time,
            'recent_activities': recent_activities[-10:]  # 最近10个活动
        }
        
    def diagnose_hang(self):
        """诊断系统卡住的原因"""
        summary = self.get_activity_summary()
        
        print("🔍 系统卡住诊断报告:")
        print(f"   距离上次活动: {summary['time_since_last_activity']:.1f}秒")
        print(f"   最近1小时活动总数: {summary['total_activities']}")
        print(f"   活动类型统计: {summary['activity_counts']}")
        
        # 分析可能的原因
        if summary['time_since_last_activity'] > 300:  # 5分钟
            print("🚨 系统确实卡住了！")
            
            # 检查最近的活动
            if summary['recent_activities']:
                print("   最近的活动:")
                for activity in summary['recent_activities']:
                    print(f"     {activity['datetime']} - {activity['type']}: {activity['details']}")
                    
            # 提供建议
            print("\n💡 建议:")
            print("   1. 检查网络连接是否正常")
            print("   2. 检查数据库连接是否正常")
            print("   3. 检查是否有死锁或资源竞争")
            print("   4. 重启相关服务")
            
        else:
            print("✅ 系统运行正常")
            
        return summary

# 全局监控实例
_global_monitor = SystemMonitor()

def start_system_monitoring():
    """启动系统监控"""
    _global_monitor.start_monitoring()
    
def stop_system_monitoring():
    """停止系统监控"""
    _global_monitor.stop_monitoring()
    
def record_activity(activity_type, details=""):
    """记录系统活动"""
    _global_monitor.record_activity(activity_type, details)
    
def diagnose_system():
    """诊断系统状态"""
    return _global_monitor.diagnose_hang()
    
def get_system_summary(hours=1):
    """获取系统摘要"""
    return _global_monitor.get_activity_summary(hours)
