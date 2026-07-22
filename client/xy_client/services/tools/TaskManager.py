import threading
import time
from queue import Queue
from threading import Lock

# 日志开关 - 如果未定义则默认为False
try:
    from xy_client.LuckyClientOP import ENABLE_DETAILED_LOGS
except ImportError:
    ENABLE_DETAILED_LOGS = False

class TaskManager:
    def __init__(self):
        self.tasks = {}
        self.task_queues = {}
        self.task_locks = {}  # 为每个任务添加锁
        self.task_running_flags = {}  # 任务运行标志

    def add_task(self, task_name, task_func, interval):
        self.tasks[task_name] = {
            'task_func': task_func,
            'interval': interval,
            'is_running': False,
            'thread': None,
            'queue': Queue(),
            'last_active': time.time(),  # 添加最后活动时间
            'last_run': 0
        }
        self.task_locks[task_name] = Lock()
        self.task_running_flags[task_name] = False

    def start_task(self, task_name):
        with self.task_locks[task_name]:
            task = self.tasks.get(task_name)
            if task and not task['is_running']:
                task['is_running'] = True
                task['thread'] = threading.Thread(
                    target=self._run_task,
                    args=(task_name,),
                    name=f"Task-{task_name}"
                )
                task['thread'].daemon = True  # 设置为守护线程
                task['thread'].start()

    def stop_task(self, task_name):
        """停止指定任务"""
        with self.task_locks[task_name]:
            task = self.tasks.get(task_name)
            if task and task['is_running']:
                task['is_running'] = False
                if task['thread']:
                    try:
                        # 设置更短的超时时间，避免阻塞
                        task['thread'].join(timeout=2)
                        if task['thread'].is_alive():
                            if ENABLE_DETAILED_LOGS:
                                print(f"⚠️ 任务 {task_name} 超时强制停止")
                    except Exception as e:
                        if ENABLE_DETAILED_LOGS:
                            print(f"停止任务{task_name}异常: {e}")
    
    def stop_all_tasks(self, timeout=3):
        """停止所有任务"""
        try:
            if ENABLE_DETAILED_LOGS:
                print(f"🔄 开始停止所有任务，超时时间: {timeout}秒")
            
            # 1. 设置所有任务停止标志
            for task_name in list(self.tasks.keys()):
                with self.task_locks[task_name]:
                    task = self.tasks.get(task_name)
                    if task:
                        task['is_running'] = False
                        # 重置运行标志
                        self.task_running_flags[task_name] = False
            
            # 2. 等待所有任务停止
            start_time = time.time()
            while time.time() - start_time < timeout:
                # 检查是否还有任务在运行
                running_tasks = []
                for task_name, task in self.tasks.items():
                    if task.get('is_running', False) or (task.get('thread') and task['thread'].is_alive()):
                        running_tasks.append(task_name)
                
                if not running_tasks:
                    if ENABLE_DETAILED_LOGS:
                        print("✅ 所有任务已停止")
                    break
                
                if ENABLE_DETAILED_LOGS:
                    print(f"⏳ 等待任务停止: {running_tasks}")
                time.sleep(0.5)
            
            # 3. 强制清理未停止的任务
            for task_name, task in self.tasks.items():
                if task.get('thread') and task['thread'].is_alive():
                    if ENABLE_DETAILED_LOGS:
                        print(f"⚠️ 强制清理任务: {task_name}")
                    try:
                        # 强制停止标志
                        task['is_running'] = False
                        self.task_running_flags[task_name] = False
                        
                        # 尝试中断线程（如果支持）
                        thread = task['thread']
                        if hasattr(thread, '_stop'):
                            thread._stop()
                        
                        # 清空线程引用
                        task['thread'] = None
                        
                    except Exception as e:
                        if ENABLE_DETAILED_LOGS:
                            print(f"强制清理任务 {task_name} 异常: {e}")
            
            # 4. 清空所有任务
            self.tasks.clear()
            self.task_locks.clear()
            self.task_running_flags.clear()
            
            if ENABLE_DETAILED_LOGS:
                print("✅ 任务管理器清理完成")
                
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f"❌ 停止所有任务异常: {e}")
            # 异常情况下强制清空
            try:
                self.tasks.clear()
                self.task_locks.clear()
                self.task_running_flags.clear()
            except:
                pass
    
    def graceful_shutdown(self, timeout=5):
        """优雅关闭所有任务"""
        try:
            if ENABLE_DETAILED_LOGS:
                print(f"🔄 开始优雅关闭，超时时间: {timeout}秒")
            
            # 1. 设置停止标志
            for task_name in list(self.tasks.keys()):
                with self.task_locks[task_name]:
                    task = self.tasks.get(task_name)
                    if task:
                        task['is_running'] = False
            
            # 2. 等待任务自然停止
            start_time = time.time()
            while time.time() - start_time < timeout:
                running_count = sum(1 for task in self.tasks.values() 
                                 if task.get('is_running', False) or 
                                 (task.get('thread') and task['thread'].is_alive()))
                
                if running_count == 0:
                    if ENABLE_DETAILED_LOGS:
                        print("✅ 所有任务已优雅停止")
                    break
                
                if ENABLE_DETAILED_LOGS:
                    print(f"⏳ 等待 {running_count} 个任务停止...")
                time.sleep(0.5)
            
            # 3. 强制停止剩余任务
            if running_count > 0:
                if ENABLE_DETAILED_LOGS:
                    print(f"⚠️ {running_count} 个任务未在超时时间内停止，强制停止")
                self.stop_all_tasks(timeout=2)
            
            if ENABLE_DETAILED_LOGS:
                print("✅ 优雅关闭完成")
                
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f"❌ 优雅关闭异常: {e}")
            # 如果优雅关闭失败，强制停止
            self.stop_all_tasks(timeout=2)

    def _run_task(self, task_name):
        task = self.tasks[task_name]
        while task['is_running']:
            try:
                current_time = time.time()
                # 检查是否达到执行间隔
                if current_time - task['last_run'] < task['interval']:
                    time.sleep(1)
                    continue

                # 尝试获取锁
                if self.task_locks[task_name].acquire(False):  # 非阻塞方式获取锁
                    try:
                        # 双重检查是否已在运行
                        if self.task_running_flags[task_name]:
                            continue
                            
                        self.task_running_flags[task_name] = True
                        task['last_run'] = current_time
                        task['task_func']()
                        task['last_active'] = current_time
                        
                    finally:
                        self.task_running_flags[task_name] = False
                        self.task_locks[task_name].release()
                        
            except Exception as e:
                print(f"任务 {task_name} 执行异常: {str(e)}")
                time.sleep(5)  # 异常后等待

    def get_task_status(self, task_name):
        task = self.tasks.get(task_name)
        if task:
            return task['is_running']
        return None