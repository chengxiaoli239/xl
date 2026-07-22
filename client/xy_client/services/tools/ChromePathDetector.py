#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Chrome路径自动检测模块
自动检测不同用户电脑上的Chrome安装路径，无需手动配置
"""

import os
import sys
import platform
import subprocess
from pathlib import Path
from typing import Optional, List

class ChromePathDetector:
    """Chrome路径自动检测器"""
    
    def __init__(self):
        self.system = platform.system()
        self.architecture = platform.architecture()[0]
        self.detected_paths = []
        
    def detect_chrome_paths(self) -> List[str]:
        """检测所有可能的Chrome安装路径"""
        paths = []
        
        if self.system == "Windows":
            paths.extend(self._detect_windows_chrome())
        elif self.system == "Darwin":  # macOS
            paths.extend(self._detect_macos_chrome())
        elif self.system == "Linux":
            paths.extend(self._detect_linux_chrome())
        
        # 去重并过滤无效路径
        unique_paths = []
        for path in paths:
            if path and path not in unique_paths and self._is_valid_chrome_path(path):
                unique_paths.append(path)
        
        self.detected_paths = unique_paths
        return unique_paths
    
    def _detect_windows_chrome(self) -> List[str]:
        """检测Windows系统Chrome路径"""
        paths = []
        
        # 常见安装路径
        username = os.getenv('USERNAME') or os.getenv('USER') or ''
        common_paths = [
            r"C:\Program Files\Google\Chrome\Application\chrome.exe",
            r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
            r"C:\Users\{}\AppData\Local\Google\Chrome\Application\chrome.exe".format(username),
            r"C:\Program Files\Google\Chrome Beta\Application\chrome.exe",
            r"C:\Program Files (x86)\Google\Chrome Beta\Application\chrome.exe",
        ]
        
        # 替换用户名占位符
        for path in common_paths:
            if '{' in path and username:
                path = path.format(username)
            paths.append(path)
        
        # 从PATH环境变量查找
        try:
            paths.extend(self._find_chrome_in_path())
        except Exception:
            pass
        
        return paths
    
    def _detect_macos_chrome(self) -> List[str]:
        """检测macOS系统Chrome路径"""
        paths = []
        
        common_paths = [
            "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
            "/Applications/Google Chrome Canary.app/Contents/MacOS/Google Chrome Canary",
            "/usr/bin/google-chrome",
            "/usr/bin/chromium",
        ]
        
        for path in common_paths:
            if os.path.exists(path):
                paths.append(path)
        
        return paths
    
    def _detect_linux_chrome(self) -> List[str]:
        """检测Linux系统Chrome路径"""
        paths = []
        
        common_paths = [
            "/usr/bin/google-chrome",
            "/usr/bin/google-chrome-stable",
            "/usr/bin/chromium",
            "/usr/bin/chromium-browser",
            "/snap/bin/google-chrome",
        ]
        
        for path in common_paths:
            if os.path.exists(path):
                paths.append(path)
        
        return paths
    
    def _find_chrome_in_path(self) -> List[str]:
        """从PATH环境变量查找Chrome"""
        paths = []
        path_dirs = os.environ.get('PATH', '').split(os.pathsep)
        
        for directory in path_dirs:
            if directory:
                chrome_exe = os.path.join(directory, "chrome.exe")
                if os.path.exists(chrome_exe):
                    paths.append(chrome_exe)
        
        return paths
    
    def _is_valid_chrome_path(self, path: str) -> bool:
        """验证Chrome路径是否有效"""
        try:
            if not path or not os.path.exists(path):
                return False
            
            # 检查文件大小
            if os.path.isfile(path):
                size = os.path.getsize(path)
                if size < 1024 * 1024:  # 小于1MB
                    return False
            
            return True
            
        except Exception:
            return False
    
    def get_best_chrome_path(self) -> Optional[str]:
        """获取最佳的Chrome路径"""
        if not self.detected_paths:
            self.detect_chrome_paths()
        
        if not self.detected_paths:
            return None
        
        return self.detected_paths[0]
    
    def test_chrome_path(self, chrome_path: str) -> bool:
        """测试Chrome路径是否可用"""
        try:
            if not self._is_valid_chrome_path(chrome_path):
                return False
            
            # 尝试启动Chrome（无界面模式）
            result = subprocess.run([chrome_path, '--version'], 
                                  capture_output=True, text=True, timeout=10)
            
            return result.returncode == 0
            
        except Exception:
            return False

# 全局实例
_chrome_detector = None

def get_chrome_detector() -> ChromePathDetector:
    """获取全局Chrome检测器实例"""
    global _chrome_detector
    if _chrome_detector is None:
        _chrome_detector = ChromePathDetector()
    return _chrome_detector

def auto_detect_chrome_path() -> Optional[str]:
    """自动检测Chrome路径"""
    detector = get_chrome_detector()
    return detector.get_best_chrome_path()

def test_chrome_path(chrome_path: str) -> bool:
    """测试Chrome路径"""
    detector = get_chrome_detector()
    return detector.test_chrome_path(chrome_path)
