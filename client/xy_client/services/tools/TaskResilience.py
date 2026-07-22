#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
任务容错管理器
确保单个任务失败不会影响其他任务的执行
"""

import time
import threading
import traceback
from typing import Callable, Any, Dict, List, Optional
from enum import Enum

class TaskStatus(Enum):
    """任务状态枚举"""
    PENDING = "pending"      # 等待执行
    RUNNING = "running"      # 正在执行
    SUCCESS = "success"      # 执行成功
    FAILED = "failed"        # 执行失败
    SKIPPED = "skipped"      # 跳过执行
    RETRYING = "retrying"    # 重试中

class TaskResult:
    """任务执行结果"""
    
    def __init__(self, task_name: str):
        self.task_name = task_name
        self.status = TaskStatus.PENDING
        self.start_time = None
        self.end_time = None
        self.duration = 0
        self.error = None
        self.retry_count = 0
        self.max_retries = 3
        self.result = None
    
    def start(self):
        """开始执行任务"""
        self.start_time = time.time()
        self.status = TaskStatus.RUNNING
    
    def success(self, result: Any = None):
        """任务执行成功"""
        self.end_time = time.time()
        self.duration = self.end_time - self.start_time
        self.status = TaskStatus.SUCCESS
        self.result = result
    
    def failed(self, error: Exception):
        """任务执行失败"""
        self.end_time = time.time()
        self.duration = self.end_time - self.start_time
        self.status = TaskStatus.FAILED
        self.error = error
    
    def retry(self):
        """重试任务"""
        self.retry_count += 1
        if self.retry_count <= self.max_retries:
            self.status = TaskStatus.RETRYING
            return True
        else:
            self.status = TaskStatus.FAILED
            return False
    
    def skip(self):
        """跳过任务"""
        self.status = TaskStatus.SKIPPED

class TaskResilience:
    """任务容错管理器"""
    
    def __init__(self):
        self.tasks: Dict[str, TaskResult] = {}
        self.task_lock = threading.Lock()
        self.global_error_count = 0
        self.global_success_count = 0
        self.last_error_time = 0
        
        # 容错配置
        self.max_consecutive_failures = 5  # 最大连续失败次数
        self.failure_cooldown = 30  # 失败后冷却时间（秒）
        self.auto_recovery_enabled = True  # 自动恢复
        self.continue_on_failure = True  # 失败后继续执行其他任务
    
    def add_task(self, task_name: str, task_func: Callable, *args, **kwargs) -> str:
        """添加任务"""
        with self.task_lock:
            if task_name in self.tasks:
                # 如果任务已存在，添加时间戳
                task_name = f"{task_name}_{int(time.time())}"
            
            # 创建任务结果对象
            task_result = TaskResult(task_name)
            self.tasks[task_name] = task_result
            
            # 启动任务执行线程
            thread = threading.Thread(
                target=self._execute_task,
                args=(task_name, task_func, args, kwargs),
                daemon=True
            )
            thread.start()
            
            return task_name
    
    def _execute_task(self, task_name: str, task_func: Callable, args: tuple, kwargs: dict):
        """执行任务"""
        task_result = self.tasks[task_name]
        
        try:
            # 开始执行
            task_result.start()
            print(f"🚀 开始执行任务: {task_name}")
            
            # 执行任务函数
            result = task_func(*args, **kwargs)
            
            # 任务成功
            task_result.success(result)
            print(f"✅ 任务执行成功: {task_name} (耗时: {task_result.duration:.2f}秒)")
            
            # 更新全局统计
            with self.task_lock:
                self.global_success_count += 1
                self.global_error_count = 0  # 重置错误计数
            
        except Exception as e:
            # 任务失败
            task_result.failed(e)
            print(f"❌ 任务执行失败: {task_name}: {e}")
            
            # 更新全局统计
            with self.task_lock:
                self.global_error_count += 1
                self.last_error_time = time.time()
            
            # 检查是否需要自动恢复
            if self._should_trigger_recovery():
                self._perform_auto_recovery()
            
            # 如果启用失败后继续，则记录错误但不中断
            if self.continue_on_failure:
                print(f"⚠️ 任务 {task_name} 失败，但系统继续运行其他任务")
            else:
                print(f"🚨 任务 {task_name} 失败，系统可能受到影响")
    
    def _should_trigger_recovery(self) -> bool:
        """检查是否应该触发自动恢复"""
        if not self.auto_recovery_enabled:
            return False
        
        current_time = time.time()
        return (self.global_error_count >= self.max_consecutive_failures and 
               current_time - self.last_error_time > self.failure_cooldown)
    
    def _perform_auto_recovery(self):
        """执行自动恢复"""
        try:
            print("🚨 触发任务自动恢复机制...")
            
            # 重置错误计数
            with self.task_lock:
                self.global_error_count = 0
                self.last_error_time = current_time
            
            print("✅ 任务自动恢复完成")
            
        except Exception as e:
            print(f"❌ 任务自动恢复失败: {e}")
    
    def get_task_status(self, task_name: str) -> Optional[TaskResult]:
        """获取任务状态"""
        return self.tasks.get(task_name)
    
    def get_all_tasks_status(self) -> Dict[str, TaskResult]:
        """获取所有任务状态"""
        with self.task_lock:
            return self.tasks.copy()
    
    def get_global_status(self) -> dict:
        """获取全局状态"""
        with self.task_lock:
            return {
                'total_tasks': len(self.tasks),
                'success_count': self.global_success_count,
                'error_count': self.global_error_count,
                'last_error_time': time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(self.last_error_time)),
                'auto_recovery_enabled': self.auto_recovery_enabled,
                'continue_on_failure': self.continue_on_failure,
                'max_consecutive_failures': self.max_consecutive_failures
            }
    
    def retry_failed_task(self, task_name: str) -> bool:
        """重试失败的任务"""
        task_result = self.tasks.get(task_name)
        if not task_result or task_result.status != TaskStatus.FAILED:
            return False
        
        if task_result.retry():
            print(f"🔄 重试任务: {task_name} (第 {task_result.retry_count} 次)")
            # 重新执行任务
            thread = threading.Thread(
                target=self._execute_task,
                args=(task_name, None, (), {}),  # 这里需要重新传入原始函数
                daemon=True
            )
            thread.start()
            return True
        else:
            print(f"❌ 任务 {task_name} 已达到最大重试次数")
            return False
    
    def reset_failed_tasks(self):
        """重置所有失败的任务"""
        with self.task_lock:
            for task_result in self.tasks.values():
                if task_result.status == TaskStatus.FAILED:
                    task_result.retry_count = 0
                    task_result.status = TaskStatus.PENDING
            print("✅ 所有失败任务已重置")
    
    def clear_completed_tasks(self):
        """清理已完成的任务"""
        with self.task_lock:
            completed_tasks = [name for name, result in self.tasks.items() 
                             if result.status in [TaskStatus.SUCCESS, TaskStatus.SKIPPED]]
            
            for task_name in completed_tasks:
                del self.tasks[task_name]
            
            if completed_tasks:
                print(f"✅ 已清理 {len(completed_tasks)} 个已完成的任务")
    
    def wait_for_task(self, task_name: str, timeout: float = 60.0) -> bool:
        """等待任务完成"""
        start_time = time.time()
        
        while time.time() - start_time < timeout:
            task_result = self.tasks.get(task_name)
            if task_result and task_result.status in [TaskStatus.SUCCESS, TaskStatus.FAILED, TaskStatus.SKIPPED]:
                return True
            time.sleep(0.1)
        
        return False
    
    def wait_for_all_tasks(self, timeout: float = 300.0) -> bool:
        """等待所有任务完成"""
        start_time = time.time()
        
        while time.time() - start_time < timeout:
            with self.task_lock:
                all_completed = all(result.status in [TaskStatus.SUCCESS, TaskStatus.FAILED, TaskStatus.SKIPPED] 
                                  for result in self.tasks.values())
                if all_completed:
                    return True
            time.sleep(0.1)
        
        return False


# 全局任务容错实例
_global_task_resilience = None

def get_task_resilience() -> TaskResilience:
    """获取全局任务容错实例"""
    global _global_task_resilience
    if _global_task_resilience is None:
        _global_task_resilience = TaskResilience()
    return _global_task_resilience

def add_resilient_task(task_name: str, task_func: Callable, *args, **kwargs) -> str:
    """添加容错任务"""
    return get_task_resilience().add_task(task_name, task_func, *args, **kwargs)

def get_task_status(task_name: str) -> Optional[TaskResult]:
    """获取任务状态"""
    return get_task_resilience().get_task_status(task_name)

def get_all_tasks_status() -> Dict[str, TaskResult]:
    """获取所有任务状态"""
    return get_task_resilience().get_all_tasks_status()

def get_global_task_status() -> dict:
    """获取全局任务状态"""
    return get_task_resilience().get_global_status()


# 装饰器：自动容错
def resilient_task(max_retries: int = 3, continue_on_failure: bool = True):
    """任务容错装饰器"""
    def decorator(func: Callable) -> Callable:
        def wrapper(*args, **kwargs):
            task_name = func.__name__
            
            # 创建任务结果
            task_result = TaskResult(task_name)
            task_result.max_retries = max_retries
            
            for attempt in range(max_retries + 1):
                try:
                    task_result.start()
                    result = func(*args, **kwargs)
                    task_result.success(result)
                    return result
                    
                except Exception as e:
                    task_result.failed(e)
                    
                    if attempt < max_retries:
                        print(f"⚠️ 任务 {task_name} 失败 (尝试 {attempt + 1}/{max_retries + 1}): {e}")
                        if task_result.retry():
                            print(f"🔄 重试任务: {task_name}")
                            time.sleep(1)  # 等待1秒后重试
                            continue
                    
                    # 如果所有重试都失败
                    print(f"❌ 任务 {task_name} 最终失败: {e}")
                    
                    if continue_on_failure:
                        print(f"⚠️ 任务 {task_name} 失败，但系统继续运行")
                        return None
                    else:
                        raise e
            
            return None
        
        return wrapper
    return decorator


if __name__ == "__main__":
    # 测试代码
    print("🧪 测试任务容错管理器...")
    
    # 创建实例
    resilience = TaskResilience()
    
    # 测试任务函数
    def successful_task():
        time.sleep(1)
        return "成功"
    
    def failing_task():
        time.sleep(0.5)
        raise Exception("模拟任务失败")
    
    def intermittent_task():
        time.sleep(0.5)
        if random.random() < 0.5:
            raise Exception("随机失败")
        return "成功"
    
    # 添加任务
    print("🔄 添加测试任务...")
    resilience.add_task("success_task", successful_task)
    resilience.add_task("fail_task", failing_task)
    resilience.add_task("intermittent_task", intermittent_task)
    
    # 等待任务完成
    print("⏳ 等待任务完成...")
    resilience.wait_for_all_tasks(timeout=30)
    
    # 查看状态
    status = resilience.get_global_status()
    print(f"📊 全局状态: {status}")
    
    # 查看任务状态
    tasks_status = resilience.get_all_tasks_status()
    for name, result in tasks_status.items():
        print(f"任务 {name}: {result.status.value}")
    
    print("✅ 测试完成")
