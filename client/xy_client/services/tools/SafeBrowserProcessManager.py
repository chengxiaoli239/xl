#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
安全的浏览器进程管理器
确保只控制程序启动的浏览器，不会误杀用户正常使用的浏览器
"""

import os
import time
import psutil
import threading
import subprocess
import platform
import json
from typing import Dict, List, Optional, Set

from xy_client.services.tools.account_runtime import debug_port_for_account
import requests
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry
from pathlib import Path


class SafeBrowserProcessManager:
    """安全的浏览器进程管理器"""
    
    def __init__(self, account_id: str, browser_type: str = "chrome"):
        self.account_id = account_id
        self.browser_type = browser_type.lower()
        self._lock = threading.Lock()
        self._managed_processes: Set[int] = set()  # 记录程序管理的进程
        self._user_data_dir = self._get_user_data_dir()
        self._debug_port = self._get_debug_port()
        self._process_log_file = self._get_process_log_file()
        
        # 浏览器进程名称映射
        self.browser_process_names = {
            'chrome': ['chrome.exe', 'chrome', 'chromedriver.exe', 'chromedriver'],
            'firefox': ['firefox.exe', 'firefox', 'geckodriver.exe', 'geckodriver']
        }
        
        # 加载已管理的进程记录
        self._load_managed_processes()
        
        print(f"✅ 安全浏览器进程管理器初始化: 账号={account_id}, 浏览器={browser_type}")
    
    def _get_user_data_dir(self) -> str:
        """获取用户数据目录（兼容现有系统）"""
        # 使用现有的用户目录结构，不改变现有用户的使用
        if platform.system() == "Windows":
            return f"C:\\.temp\\9222\\{self.account_id}"
        else:
            return f"/tmp/9222/{self.account_id}"
    
    def _get_debug_port(self) -> int:
        """获取调试端口"""
        return debug_port_for_account(self.account_id)
    
    def _get_process_log_file(self) -> str:
        """获取进程日志文件"""
        if platform.system() == "Windows":
            return os.path.join(os.environ.get('TEMP', 'C:\\temp'), f'lucky_client_processes_{self.account_id}.json')
        else:
            return f'/tmp/lucky_client_processes_{self.account_id}.json'
    
    def _load_managed_processes(self):
        """加载已管理的进程记录"""
        try:
            if os.path.exists(self._process_log_file):
                with open(self._process_log_file, 'r', encoding='utf-8') as f:
                    data = json.load(f)
                    self._managed_processes = set(data.get('managed_processes', []))
                print(f"✅ 加载了 {len(self._managed_processes)} 个已管理的进程")
        except Exception as e:
            print(f"⚠️ 加载进程记录失败: {e}")
            self._managed_processes = set()
    
    def _save_managed_processes(self):
        """保存已管理的进程记录"""
        try:
            data = {
                'account_id': self.account_id,
                'managed_processes': list(self._managed_processes),
                'last_updated': time.time()
            }
            with open(self._process_log_file, 'w', encoding='utf-8') as f:
                json.dump(data, f, ensure_ascii=False, indent=2)
        except Exception as e:
            print(f"⚠️ 保存进程记录失败: {e}")
    
    def _is_managed_process(self, proc: psutil.Process) -> bool:
        """判断进程是否由程序管理"""
        try:
            pid = proc.pid
            
            # 检查是否在管理列表中
            if pid in self._managed_processes:
                return True
            
            # 检查进程是否还在运行
            if not proc.is_running():
                return False
            
            # 检查进程命令行参数
            cmdline = ' '.join(proc.cmdline() or [])
            
            # 检查是否包含程序特有的参数（只使用当前程序的标识，避免误判其他程序的进程）
            program_indicators = [
                f'--user-data-dir={self._user_data_dir}',
                f'--remote-debugging-port={self._debug_port}',
                f'--profile={self._user_data_dir}',
                # 关键修复：只使用当前程序的account_id和端口，不使用硬编码的端口
                # 确保每个程序实例只管理自己的进程，不会误判其他程序的进程
                f'--user-data-dir="C:\\.temp\\9222\\{self.account_id}"',
                f'--user-data-dir="D:\\.temp\\9223\\{self.account_id}"',
                f'--user-data-dir="/tmp/9222/{self.account_id}"',
                f'--user-data-dir="/tmp/9223/{self.account_id}"',
            ]
            
            # 关键修复：检查account_id是否在命令行中（但需要确保是完整的account_id，避免误判）
            # 例如，如果account_id是"aa33"，不应该匹配"aa335"或"aa33abc"
            if self.account_id and self.account_id != "default_account":
                # 使用更严格的匹配：确保account_id是独立的标识符
                account_patterns = [
                    f'\\{self.account_id}',  # 在路径中
                    f'/{self.account_id}',   # 在路径中
                    f'={self.account_id}',    # 在参数中
                    f'"{self.account_id}"',  # 在引号中
                ]
                for pattern in account_patterns:
                    if pattern in cmdline:
                        program_indicators.append(pattern)
                        break
            
            # 关键修复：必须同时匹配account_id和端口/目录，确保只识别当前程序管理的进程
            # 避免误判其他程序的进程（即使它们使用了相同的端口或目录结构）
            matches_account = False
            matches_port_or_dir = False
            
            # 检查是否匹配account_id（如果account_id不是默认值）
            if self.account_id and self.account_id != "default_account":
                for pattern in [f'\\{self.account_id}', f'/{self.account_id}', f'={self.account_id}', f'"{self.account_id}"']:
                    if pattern in cmdline:
                        matches_account = True
                        break
            else:
                # 如果account_id是默认值，跳过account_id检查（兼容旧系统）
                matches_account = True
            
            # 检查是否匹配端口或用户数据目录
            for indicator in program_indicators:
                if indicator in cmdline:
                    matches_port_or_dir = True
                    break
            
            # 只有同时匹配account_id和端口/目录，才认为是程序管理的进程
            if matches_account and matches_port_or_dir:
                # 记录到管理列表
                self._managed_processes.add(pid)
                self._save_managed_processes()
                return True
            
            return False
            
        except (psutil.NoSuchProcess, psutil.AccessDenied, AttributeError):
            return False
    
    def _is_user_browser_process(self, proc: psutil.Process) -> bool:
        """判断是否为用户正常使用的浏览器进程"""
        try:
            cmdline = ' '.join(proc.cmdline() or [])
            
            # 首先检查是否是程序管理的进程
            if self._is_managed_process(proc):
                return False
            
            # 用户浏览器的特征（排除程序管理的进程）
            user_indicators = [
                '--profile-directory=Default',  # 默认用户配置
                '--profile-directory=Profile',  # 用户配置
                '--no-first-run',  # 用户浏览器通常没有这个参数
                '--disable-extensions',  # 用户浏览器通常没有这个参数
            ]
            
            # 排除程序管理的进程特征
            program_indicators = [
                '--remote-debugging-port',  # 调试端口通常是程序启动的
                '--user-data-dir=',  # 用户数据目录通常是程序启动的
                'C:\\.temp\\9222\\',  # 程序专用的用户目录
                'D:\\.temp\\9223\\',  # 程序专用的用户目录
                '/tmp/9222/',  # 程序专用的用户目录
                '/tmp/9223/',  # 程序专用的用户目录
            ]
            
            # 如果包含程序管理的特征，不是用户进程
            for indicator in program_indicators:
                if indicator in cmdline:
                    return False
            
            # 如果包含用户浏览器的特征，认为是用户正常使用的
            for indicator in user_indicators:
                if indicator in cmdline:
                    return True
            
            # 检查是否在用户目录下启动
            try:
                cwd = proc.cwd()
                if cwd:
                    # 如果在用户目录下启动，可能是用户正常使用的
                    user_home = os.path.expanduser('~')
                    if cwd.startswith(user_home) and 'lucky_client' not in cwd.lower():
                        return True
            except:
                pass
            
            return False
            
        except (psutil.NoSuchProcess, psutil.AccessDenied, AttributeError):
            return False
    
    def start_browser_process(self) -> bool:
        """启动浏览器进程并记录"""
        try:
            if self.browser_type == "chrome":
                return self._start_chrome_process()
            elif self.browser_type == "firefox":
                return self._start_firefox_process()
            else:
                print(f"❌ 不支持的浏览器类型: {self.browser_type}")
                return False
        except Exception as e:
            print(f"❌ 启动浏览器进程异常: {e}")
            return False
    
    def _start_chrome_process(self) -> bool:
        """启动Chrome进程"""
        try:
            # 获取Chrome路径
            chrome_paths = [
                r"C:\Program Files\Google\Chrome\Application\chrome.exe",
                r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
                r"C:\Users\{}\AppData\Local\Google\Chrome\Application\chrome.exe".format(os.getenv('USERNAME')),
                "/usr/bin/google-chrome",
                "/usr/bin/chromium-browser",
                "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
            ]
            
            chrome_path = None
            for path in chrome_paths:
                if os.path.exists(path):
                    chrome_path = path
                    break
            
            if not chrome_path:
                print("❌ 未找到Chrome浏览器路径")
                return False
            
            # 构建启动命令（兼容现有系统）
            cmd = [
                chrome_path,
                f"--remote-debugging-port={self._debug_port}",
                f"--user-data-dir={self._user_data_dir}",
                "--no-first-run",
                "--no-default-browser-check",
                "--disable-extensions",
                "--disable-plugins",
                "--disable-web-security",
                "--disable-features=VizDisplayCompositor"
            ]
            
            # 启动浏览器
            process = subprocess.Popen(
                cmd, 
                stdout=subprocess.DEVNULL, 
                stderr=subprocess.DEVNULL,
                cwd=os.getcwd()  # 设置工作目录
            )
            
            # 记录进程ID
            with self._lock:
                self._managed_processes.add(process.pid)
                self._save_managed_processes()
            
            print(f"✅ Chrome浏览器已启动，PID: {process.pid}, 端口: {self._debug_port}")
            
            # 等待浏览器启动
            for i in range(10):
                time.sleep(1)
                if self._is_browser_running():
                    return True
            
            print(f"❌ Chrome浏览器启动超时")
            return False
            
        except Exception as e:
            print(f"❌ 启动Chrome浏览器异常: {e}")
            return False
    
    def _start_firefox_process(self) -> bool:
        """启动Firefox进程"""
        try:
            # 获取Firefox路径
            firefox_paths = [
                r"C:\Program Files\Mozilla Firefox\firefox.exe",
                r"C:\Program Files (x86)\Mozilla Firefox\firefox.exe",
                "/usr/bin/firefox",
                "/Applications/Firefox.app/Contents/MacOS/firefox"
            ]
            
            firefox_path = None
            for path in firefox_paths:
                if os.path.exists(path):
                    firefox_path = path
                    break
            
            if not firefox_path:
                print("❌ 未找到Firefox浏览器路径")
                return False
            
            # 构建启动命令（兼容现有系统）
            cmd = [
                firefox_path,
                "--new-instance",
                "--profile", self._user_data_dir,
                "--remote-debugging-port", str(self._debug_port)
            ]
            
            # 启动浏览器
            process = subprocess.Popen(
                cmd, 
                stdout=subprocess.DEVNULL, 
                stderr=subprocess.DEVNULL,
                cwd=os.getcwd()  # 设置工作目录
            )
            
            # 记录进程ID
            with self._lock:
                self._managed_processes.add(process.pid)
                self._save_managed_processes()
            
            print(f"✅ Firefox浏览器已启动，PID: {process.pid}, 端口: {self._debug_port}")
            
            # 等待浏览器启动
            for i in range(10):
                time.sleep(1)
                if self._is_browser_running():
                    return True
            
            print(f"❌ Firefox浏览器启动超时")
            return False
            
        except Exception as e:
            print(f"❌ 启动Firefox浏览器异常: {e}")
            return False
    
    def _is_browser_running(self) -> bool:
        """检查浏览器是否在运行"""
        try:
            response = _get_local_http().get(f'http://127.0.0.1:{self._debug_port}/json', timeout=2)
            return response.status_code == 200
        except:
            return False
    
    def kill_managed_processes(self):
        """终止程序管理的浏览器进程"""
        with self._lock:
            try:
                killed_count = 0
                processes_to_kill = []
                
                # 检查已管理的进程
                for pid in list(self._managed_processes):
                    try:
                        proc = psutil.Process(pid)
                        if proc.is_running():
                            processes_to_kill.append(proc)
                        else:
                            # 进程已不存在，从管理列表中移除
                            self._managed_processes.discard(pid)
                    except psutil.NoSuchProcess:
                        # 进程已不存在，从管理列表中移除
                        self._managed_processes.discard(pid)
                
                # 终止找到的进程
                for proc in processes_to_kill:
                    try:
                        # 再次确认是程序管理的进程
                        if self._is_managed_process(proc):
                            print(f"🔄 终止程序管理的浏览器进程: {proc.name()} (PID: {proc.pid})")
                            proc.terminate()
                            killed_count += 1
                            
                            # 等待进程终止
                            try:
                                proc.wait(timeout=5)
                            except psutil.TimeoutExpired:
                                print(f"⚠️ 进程 {proc.pid} 未在5秒内终止，强制杀死")
                                proc.kill()
                            
                            # 从管理列表中移除
                            self._managed_processes.discard(proc.pid)
                        else:
                            print(f"⚠️ 跳过非程序管理的进程: {proc.name()} (PID: {proc.pid})")
                            
                    except Exception as e:
                        print(f"⚠️ 终止进程 {proc.pid} 失败: {e}")
                
                # 保存更新后的管理列表
                self._save_managed_processes()
                
                if killed_count > 0:
                    print(f"✅ 已终止 {killed_count} 个程序管理的浏览器进程")
                    time.sleep(2)  # 等待进程完全终止
                else:
                    print("ℹ️ 未找到需要终止的程序管理进程")
                
                return killed_count > 0
                
            except Exception as e:
                print(f"❌ 终止程序管理进程异常: {e}")
                return False
    
    def cleanup_orphaned_processes(self):
        """清理孤立的进程（程序启动但不在管理列表中的）"""
        try:
            cleaned_count = 0
            
            # 查找可能的孤立进程
            for proc in psutil.process_iter(['pid', 'name', 'cmdline']):
                try:
                    proc_name = proc.info['name'].lower()
                    cmdline = ' '.join(proc.info['cmdline'] or [])
                    
                    # 检查是否是目标浏览器的进程
                    if any(browser_name in proc_name for browser_name in self.browser_process_names[self.browser_type]):
                        # 关键修复：使用_is_managed_process方法进行严格判断
                        # 确保只清理当前程序管理的进程，不会误杀其他程序的进程
                        if self._is_managed_process(proc):
                            # 检查是否在管理列表中
                            if proc.pid not in self._managed_processes:
                                print(f"🔍 发现孤立进程: {proc.name()} (PID: {proc.pid})")
                                print(f"🔄 清理孤立进程: {proc.name()} (PID: {proc.pid})")
                                proc.terminate()
                                cleaned_count += 1
                                
                                try:
                                    proc.wait(timeout=3)
                                except psutil.TimeoutExpired:
                                    proc.kill()
                                
                                # 从管理列表中移除（如果存在）
                                self._managed_processes.discard(proc.pid)
                                
                except (psutil.NoSuchProcess, psutil.AccessDenied, AttributeError):
                    continue
            
            if cleaned_count > 0:
                print(f"✅ 已清理 {cleaned_count} 个孤立进程")
                self._save_managed_processes()
            
            return cleaned_count > 0
            
        except Exception as e:
            print(f"❌ 清理孤立进程异常: {e}")
            return False
    
    def get_managed_processes(self) -> List[Dict]:
        """获取程序管理的进程信息"""
        with self._lock:
            processes = []
            for pid in list(self._managed_processes):
                try:
                    proc = psutil.Process(pid)
                    if proc.is_running():
                        processes.append({
                            'pid': pid,
                            'name': proc.name(),
                            'status': 'running',
                            'cmdline': ' '.join(proc.cmdline() or [])
                        })
                    else:
                        # 进程已不存在，从管理列表中移除
                        self._managed_processes.discard(pid)
                except psutil.NoSuchProcess:
                    # 进程已不存在，从管理列表中移除
                    self._managed_processes.discard(pid)
            
            # 保存更新后的管理列表
            self._save_managed_processes()
            
            return processes
    
    def get_user_browser_processes(self) -> List[Dict]:
        """获取用户正常使用的浏览器进程（仅用于监控，不操作）"""
        try:
            user_processes = []
            
            for proc in psutil.process_iter(['pid', 'name', 'cmdline']):
                try:
                    proc_name = proc.info['name'].lower()
                    cmdline = ' '.join(proc.info['cmdline'] or [])
                    
                    # 检查是否是浏览器进程
                    if any(browser_name in proc_name for browser_name in self.browser_process_names[self.browser_type]):
                        # 检查是否是用户正常使用的
                        if self._is_user_browser_process(proc):
                            user_processes.append({
                                'pid': proc.pid,
                                'name': proc.name(),
                                'cmdline': cmdline,
                                'status': 'user_browser'
                            })
                
                except (psutil.NoSuchProcess, psutil.AccessDenied, AttributeError):
                    continue
            
            return user_processes
            
        except Exception as e:
            print(f"❌ 获取用户浏览器进程异常: {e}")
            return []
    
    def cleanup(self):
        """清理资源"""
        try:
            # 终止程序管理的进程
            self.kill_managed_processes()
            
            # 清理进程记录文件
            try:
                if os.path.exists(self._process_log_file):
                    os.remove(self._process_log_file)
            except Exception as e:
                print(f"⚠️ 清理进程记录文件失败: {e}")
            
            print(f"✅ 安全浏览器进程管理器清理完成: {self.account_id}")
            
        except Exception as e:
            print(f"❌ 安全浏览器进程管理器清理异常: {e}")


# 全局安全进程管理器
_global_safe_process_managers = {}

# ------------- 本地HTTP会话（用于localhost/127.0.0.1） -------------
_local_http = None

def _get_local_http():
    global _local_http
    if _local_http is None:
        session = requests.Session()
        retry_strategy = Retry(total=2, backoff_factor=0.3)
        adapter = HTTPAdapter(pool_connections=50, pool_maxsize=200, max_retries=retry_strategy, pool_block=False)
        session.mount('http://', adapter)
        session.mount('https://', adapter)
        _local_http = session
    return _local_http

def get_safe_process_manager(account_id: str, browser_type: str = "chrome") -> SafeBrowserProcessManager:
    """获取指定账号的安全进程管理器"""
    if account_id not in _global_safe_process_managers:
        _global_safe_process_managers[account_id] = SafeBrowserProcessManager(account_id, browser_type)
    return _global_safe_process_managers[account_id]

def cleanup_safe_process_manager(account_id: str):
    """清理指定账号的安全进程管理器"""
    if account_id in _global_safe_process_managers:
        _global_safe_process_managers[account_id].cleanup()
        del _global_safe_process_managers[account_id]

def get_all_managed_processes():
    """获取所有程序管理的进程"""
    all_processes = {}
    for account_id, manager in _global_safe_process_managers.items():
        all_processes[account_id] = manager.get_managed_processes()
    return all_processes

def get_all_user_browser_processes():
    """获取所有用户浏览器进程（仅监控）"""
    all_processes = {}
    for account_id, manager in _global_safe_process_managers.items():
        all_processes[account_id] = manager.get_user_browser_processes()
    return all_processes


if __name__ == "__main__":
    # 测试代码
    print("🧪 测试安全浏览器进程管理器...")
    
    # 创建安全进程管理器
    manager = SafeBrowserProcessManager("test_account", "chrome")
    
    # 获取程序管理的进程
    managed_processes = manager.get_managed_processes()
    print(f"程序管理的进程: {managed_processes}")
    
    # 获取用户浏览器进程
    user_processes = manager.get_user_browser_processes()
    print(f"用户浏览器进程: {user_processes}")
    
    # 清理
    manager.cleanup()
    
    print("✅ 测试完成")
