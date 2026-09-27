#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Chrome路径自动检测模块
自动检测不同用户电脑上的Chrome安装路径，无需手动配置
"""

import os
import sys
import platform
import shutil
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

        # Chrome may be installed per-user, for all users, or on a non-C: drive.
        program_dirs = []
        for key in ('ProgramW6432', 'PROGRAMFILES', 'PROGRAMFILES(X86)'):
            value = os.environ.get(key)
            if value and value not in program_dirs:
                program_dirs.append(value)

        local_app_data = os.environ.get('LOCALAPPDATA')
        if local_app_data:
            program_dirs.append(local_app_data)

        product_dirs = (
            'Google\\Chrome',
            'Google\\Chrome Beta',
            'Google\\Chrome Dev',
            'Google\\Chrome SxS',
            'Chromium',
        )
        for base_dir in program_dirs:
            for product_dir in product_dirs:
                paths.append(os.path.join(base_dir, product_dir, 'Application', 'chrome.exe'))

        paths.extend(self._detect_windows_registry_chrome())

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
        for executable in ('chrome.exe', 'chrome', 'chromium.exe', 'chromium'):
            resolved = shutil.which(executable)
            if resolved:
                paths.append(resolved)
        return paths

    def _detect_windows_registry_chrome(self) -> List[str]:
        """Read Chrome's installer registration without requiring pywin32."""
        if self.system != "Windows":
            return []

        try:
            import winreg
        except ImportError:
            return []

        paths = []
        registry_locations = (
            (winreg.HKEY_CURRENT_USER, r'Software\Microsoft\Windows\CurrentVersion\App Paths\chrome.exe'),
            (winreg.HKEY_LOCAL_MACHINE, r'Software\Microsoft\Windows\CurrentVersion\App Paths\chrome.exe'),
            (winreg.HKEY_LOCAL_MACHINE, r'Software\WOW6432Node\Microsoft\Windows\CurrentVersion\App Paths\chrome.exe'),
        )
        for root, subkey in registry_locations:
            try:
                with winreg.OpenKey(root, subkey) as key:
                    value, _ = winreg.QueryValueEx(key, None)
                    if value:
                        paths.append(str(value).strip('"'))
            except (FileNotFoundError, OSError):
                continue
        return paths
    
    def _is_valid_chrome_path(self, path: str) -> bool:
        """验证Chrome路径是否有效"""
        try:
            if not path or not os.path.isfile(path):
                return False

            # Portable Chromium wrappers can be small; existence and the
            # Windows executable extension are enough for launch validation.
            return os.access(path, os.X_OK) or self.system == 'Windows'
            
        except Exception:
            return False
    
    def get_best_chrome_path(self) -> Optional[str]:
        """获取最佳的Chrome路径"""
        # A browser update can invalidate a cached path during a long session.
        for path in self.detected_paths:
            if self._is_valid_chrome_path(path):
                return path
        paths = self.detect_chrome_paths()
        return paths[0] if paths else None
    
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


def resolve_chrome_path(configured_path: Optional[str] = None) -> Optional[str]:
    """Resolve a configured path, falling back to automatic detection."""
    configured = str(configured_path or '').strip()
    detector = get_chrome_detector()
    if configured and configured.lower() != 'auto' and detector._is_valid_chrome_path(configured):
        return configured
    return detector.get_best_chrome_path()

def test_chrome_path(chrome_path: str) -> bool:
    """测试Chrome路径"""
    detector = get_chrome_detector()
    return detector.test_chrome_path(chrome_path)
