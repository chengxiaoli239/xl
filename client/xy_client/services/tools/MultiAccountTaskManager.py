#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
多账号任务管理器
每个账号独立运行定时任务，互不干扰
"""

import time
import threading
import queue
from typing import Dict, List, Optional, Any
from dataclasses import dataclass
from enum import Enum


class TaskStatus(Enum):
    """任务状态"""
    PENDING = "pending"
    RUNNING = "running"
    COMPLETED = "completed"
    FAILED = "failed"
    STOPPED = "stopped"


@dataclass
class Task:
    """任务定义"""
    task_id: str
    account_id: str
    task_name: str
    task_func: callable
    interval: int  # 执行间隔（秒）
    last_run: float = 0
    status: TaskStatus = TaskStatus.PENDING
    error_count: int = 0
    max_errors: int = 3


class AccountTaskManager:
    """单个账号的任务管理器"""
    
    def __init__(self, account_id: str):
        self.account_id = account_id
        self.tasks: Dict[str, Task] = {}
        self.task_queue = queue.Queue()
        self.worker_thread = None
        self.is_running = False
        self._lock = threading.Lock()
        
        print(f"✅ 账号 {account_id} 任务管理器初始化完成")
    
    def add_task(self, task_id: str, task_name: str, task_func: callable, interval: int):
        """添加任务"""
        with self._lock:
            task = Task(
                task_id=task_id,
                account_id=self.account_id,
                task_name=task_name,
                task_func=task_func,
                interval=interval
            )
            self.tasks[task_id] = task
            print(f"✅ 账号 {self.account_id} 添加任务: {task_name}")
    
    def remove_task(self, task_id: str):
        """移除任务"""
        with self._lock:
            if task_id in self.tasks:
                del self.tasks[task_id]
                print(f"✅ 账号 {self.account_id} 移除任务: {task_id}")
    
    def start(self):
        """启动任务管理器"""
        if self.is_running:
            return
        
        self.is_running = True
        self.worker_thread = threading.Thread(target=self._worker_loop, daemon=True)
        self.worker_thread.start()
        print(f"✅ 账号 {self.account_id} 任务管理器已启动")
    
    def stop(self):
        """停止任务管理器"""
        if not self.is_running:
            return
        
        self.is_running = False
        
        # 等待工作线程结束
        if self.worker_thread and self.worker_thread.is_alive():
            self.worker_thread.join(timeout=5)
        
        # 停止所有任务
        with self._lock:
            for task in self.tasks.values():
                task.status = TaskStatus.STOPPED
        
        print(f"✅ 账号 {self.account_id} 任务管理器已停止")
    
    def _worker_loop(self):
        """工作线程循环"""
        while self.is_running:
            try:
                current_time = time.time()
                
                with self._lock:
                    # 检查需要执行的任务
                    tasks_to_run = []
                    for task in self.tasks.values():
                        if (task.status == TaskStatus.PENDING and 
                            current_time - task.last_run >= task.interval):
                            tasks_to_run.append(task)
                
                # 执行任务
                for task in tasks_to_run:
                    self._execute_task(task)
                
                # 短暂休眠
                time.sleep(1)
                
            except Exception as e:
                print(f"❌ 账号 {self.account_id} 任务管理器异常: {e}")
                time.sleep(5)
    
    def _execute_task(self, task: Task):
        """执行单个任务"""
        try:
            task.status = TaskStatus.RUNNING
            task.last_run = time.time()
            
            print(f"🔄 账号 {self.account_id} 执行任务: {task.task_name}")
            
            # 执行任务函数
            task.task_func()
            
            task.status = TaskStatus.COMPLETED
            task.error_count = 0
            
            print(f"✅ 账号 {self.account_id} 任务完成: {task.task_name}")
            
        except Exception as e:
            task.status = TaskStatus.FAILED
            task.error_count += 1
            
            print(f"❌ 账号 {self.account_id} 任务失败: {task.task_name} - {e}")
            
            # 如果错误次数过多，停止任务
            if task.error_count >= task.max_errors:
                task.status = TaskStatus.STOPPED
                print(f"⚠️ 账号 {self.account_id} 任务 {task.task_name} 错误次数过多，已停止")
    
    def get_task_status(self) -> Dict[str, Dict]:
        """获取任务状态"""
        with self._lock:
            status = {}
            for task_id, task in self.tasks.items():
                status[task_id] = {
                    'task_name': task.task_name,
                    'status': task.status.value,
                    'last_run': task.last_run,
                    'error_count': task.error_count,
                    'interval': task.interval
                }
            return status
    
    def get_manager_status(self) -> Dict:
        """获取管理器状态"""
        return {
            'account_id': self.account_id,
            'is_running': self.is_running,
            'task_count': len(self.tasks),
            'tasks': self.get_task_status()
        }


class MultiAccountTaskManager:
    """多账号任务管理器"""
    
    def __init__(self):
        self.account_managers: Dict[str, AccountTaskManager] = {}
        self._lock = threading.Lock()
        
        print("✅ 多账号任务管理器初始化完成")
    
    def create_account_manager(self, account_id: str) -> AccountTaskManager:
        """创建账号任务管理器"""
        with self._lock:
            if account_id not in self.account_managers:
                manager = AccountTaskManager(account_id)
                self.account_managers[account_id] = manager
                print(f"✅ 创建账号 {account_id} 的任务管理器")
            return self.account_managers[account_id]
    
    def remove_account_manager(self, account_id: str):
        """移除账号任务管理器"""
        with self._lock:
            if account_id in self.account_managers:
                manager = self.account_managers[account_id]
                manager.stop()
                del self.account_managers[account_id]
                print(f"✅ 移除账号 {account_id} 的任务管理器")
    
    def add_task(self, account_id: str, task_id: str, task_name: str, task_func: callable, interval: int):
        """为指定账号添加任务"""
        manager = self.get_account_manager(account_id)
        if manager:
            manager.add_task(task_id, task_name, task_func, interval)
    
    def remove_task(self, account_id: str, task_id: str):
        """移除指定账号的任务"""
        manager = self.get_account_manager(account_id)
        if manager:
            manager.remove_task(task_id)
    
    def start_account_tasks(self, account_id: str):
        """启动指定账号的任务"""
        manager = self.get_account_manager(account_id)
        if manager:
            manager.start()
    
    def stop_account_tasks(self, account_id: str):
        """停止指定账号的任务"""
        manager = self.get_account_manager(account_id)
        if manager:
            manager.stop()
    
    def get_account_manager(self, account_id: str) -> Optional[AccountTaskManager]:
        """获取账号任务管理器"""
        with self._lock:
            return self.account_managers.get(account_id)
    
    def get_all_status(self) -> Dict[str, Dict]:
        """获取所有账号的任务状态"""
        with self._lock:
            status = {}
            for account_id, manager in self.account_managers.items():
                status[account_id] = manager.get_manager_status()
            return status
    
    def stop_all_tasks(self):
        """停止所有任务"""
        with self._lock:
            for manager in self.account_managers.values():
                manager.stop()
            self.account_managers.clear()
        print("✅ 所有账号任务已停止")


# 全局多账号任务管理器
_global_task_manager = None

def get_global_task_manager() -> MultiAccountTaskManager:
    """获取全局多账号任务管理器"""
    global _global_task_manager
    if _global_task_manager is None:
        _global_task_manager = MultiAccountTaskManager()
    return _global_task_manager

def create_account_tasks(account_id: str):
    """为账号创建任务"""
    task_manager = get_global_task_manager()
    
    # 创建账号任务管理器
    manager = task_manager.create_account_manager(account_id)
    
    # 添加各种任务
    # 这里应该根据实际需求添加具体的任务
    
    # 示例任务
    def sync_balance_task():
        print(f"🔄 账号 {account_id} 同步余额")
        # 实际的同步余额逻辑
    
    def bet_tasks():
        print(f"🔄 账号 {account_id} 执行下注任务")
        # 实际的下注逻辑
    
    def get_qi_hao_task():
        print(f"🔄 账号 {account_id} 获取期号")
        # 实际的获取期号逻辑
    
    def login_check_task():
        print(f"🔄 账号 {account_id} 检查登录状态")
        # 实际的登录检查逻辑
    
    def refresh_timer_task():
        print(f"🔄 账号 {account_id} 刷新页面")
        # 实际的页面刷新逻辑
    
    # 添加任务
    manager.add_task("sync_balance", "同步余额", sync_balance_task, 30)
    manager.add_task("bet_tasks", "下注任务", bet_tasks, 5)
    manager.add_task("get_qi_hao", "获取期号", get_qi_hao_task, 30)
    manager.add_task("login_check", "登录检查", login_check_task, 30)
    manager.add_task("refresh_timer", "页面刷新", refresh_timer_task, 300)
    
    return manager

def start_account_tasks(account_id: str):
    """启动账号任务"""
    task_manager = get_global_task_manager()
    task_manager.start_account_tasks(account_id)

def stop_account_tasks(account_id: str):
    """停止账号任务"""
    task_manager = get_global_task_manager()
    task_manager.stop_account_tasks(account_id)

def get_account_task_status(account_id: str) -> Dict:
    """获取账号任务状态"""
    task_manager = get_global_task_manager()
    manager = task_manager.get_account_manager(account_id)
    if manager:
        return manager.get_manager_status()
    return {}

def get_all_account_task_status() -> Dict[str, Dict]:
    """获取所有账号任务状态"""
    task_manager = get_global_task_manager()
    return task_manager.get_all_status()


if __name__ == "__main__":
    # 测试代码
    print("🧪 测试多账号任务管理器...")
    
    # 创建任务管理器
    task_manager = get_global_task_manager()
    
    # 为账号1创建任务
    manager1 = create_account_tasks("account1")
    start_account_tasks("account1")
    
    # 为账号2创建任务
    manager2 = create_account_tasks("account2")
    start_account_tasks("account2")
    
    # 运行一段时间
    time.sleep(10)
    
    # 获取状态
    status = get_all_account_task_status()
    print(f"所有账号任务状态: {status}")
    
    # 停止任务
    stop_account_tasks("account1")
    stop_account_tasks("account2")
    
    print("✅ 测试完成")
