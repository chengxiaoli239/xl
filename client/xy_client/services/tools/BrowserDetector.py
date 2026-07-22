#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
浏览器检测工具
检测Chrome和Firefox是否已安装
"""

import os
import platform
import subprocess
import winreg
from pathlib import Path


class BrowserDetector:
    """浏览器检测器"""
    
    def __init__(self):
        self.system = platform.system()
    
    def detect_chrome(self):
        """检测Chrome浏览器是否安装"""
        if self.system == "Windows":
            return self._detect_chrome_windows()
        elif self.system == "Darwin":  # macOS
            return self._detect_chrome_macos()
        else:  # Linux
            return self._detect_chrome_linux()
    
    def detect_firefox(self):
        """检测Firefox浏览器是否安装"""
        if self.system == "Windows":
            return self._detect_firefox_windows()
        elif self.system == "Darwin":  # macOS
            return self._detect_firefox_macos()
        else:  # Linux
            return self._detect_firefox_linux()
    
    def _detect_chrome_windows(self):
        """Windows系统检测Chrome"""
        chrome_paths = [
            r"C:\Program Files\Google\Chrome\Application\chrome.exe",
            r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
            os.path.expanduser(r"~\AppData\Local\Google\Chrome\Application\chrome.exe"),
        ]
        
        # 检查注册表
        try:
            with winreg.OpenKey(winreg.HKEY_LOCAL_MACHINE, r"SOFTWARE\Microsoft\Windows\CurrentVersion\App Paths\chrome.exe") as key:
                chrome_paths.append(winreg.QueryValue(key, None))
        except:
            pass
        
        try:
            with winreg.OpenKey(winreg.HKEY_CURRENT_USER, r"SOFTWARE\Microsoft\Windows\CurrentVersion\App Paths\chrome.exe") as key:
                chrome_paths.append(winreg.QueryValue(key, None))
        except:
            pass
        
        # 检查PATH环境变量
        for path in os.environ.get('PATH', '').split(';'):
            if path.strip():
                chrome_paths.append(os.path.join(path, 'chrome.exe'))
        
        for path in chrome_paths:
            if path and os.path.exists(path):
                return True, path
        
        return False, None
    
    def _detect_chrome_macos(self):
        """macOS系统检测Chrome"""
        chrome_paths = [
            "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
            os.path.expanduser("~/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"),
        ]
        
        for path in chrome_paths:
            if os.path.exists(path):
                return True, path
        
        return False, None
    
    def _detect_chrome_linux(self):
        """Linux系统检测Chrome"""
        chrome_paths = [
            "/usr/bin/google-chrome",
            "/usr/bin/google-chrome-stable",
            "/usr/bin/chromium-browser",
            "/usr/bin/chromium",
        ]
        
        for path in chrome_paths:
            if os.path.exists(path):
                return True, path
        
        return False, None
    
    def _detect_firefox_windows(self):
        """Windows系统检测Firefox"""
        firefox_paths = [
            r"C:\Program Files\Mozilla Firefox\firefox.exe",
            r"C:\Program Files (x86)\Mozilla Firefox\firefox.exe",
            os.path.expanduser(r"~\AppData\Local\Mozilla Firefox\firefox.exe"),
        ]
        
        # 检查注册表
        try:
            with winreg.OpenKey(winreg.HKEY_LOCAL_MACHINE, r"SOFTWARE\Microsoft\Windows\CurrentVersion\App Paths\firefox.exe") as key:
                firefox_paths.append(winreg.QueryValue(key, None))
        except:
            pass
        
        try:
            with winreg.OpenKey(winreg.HKEY_CURRENT_USER, r"SOFTWARE\Microsoft\Windows\CurrentVersion\App Paths\firefox.exe") as key:
                firefox_paths.append(winreg.QueryValue(key, None))
        except:
            pass
        
        # 检查PATH环境变量
        for path in os.environ.get('PATH', '').split(';'):
            if path.strip():
                firefox_paths.append(os.path.join(path, 'firefox.exe'))
        
        for path in firefox_paths:
            if path and os.path.exists(path):
                return True, path
        
        return False, None
    
    def _detect_firefox_macos(self):
        """macOS系统检测Firefox"""
        firefox_paths = [
            "/Applications/Firefox.app/Contents/MacOS/firefox",
            os.path.expanduser("~/Applications/Firefox.app/Contents/MacOS/firefox"),
        ]
        
        for path in firefox_paths:
            if os.path.exists(path):
                return True, path
        
        return False, None
    
    def _detect_firefox_linux(self):
        """Linux系统检测Firefox"""
        firefox_paths = [
            "/usr/bin/firefox",
            "/usr/bin/firefox-esr",
            "/usr/bin/mozilla-firefox",
        ]
        
        for path in firefox_paths:
            if os.path.exists(path):
                return True, path
        
        return False, None
    
    def get_browser_status(self):
        """获取所有浏览器的安装状态"""
        chrome_installed, chrome_path = self.detect_chrome()
        firefox_installed, firefox_path = self.detect_firefox()
        
        return {
            'chrome': {
                'installed': chrome_installed,
                'path': chrome_path
            },
            'firefox': {
                'installed': firefox_installed,
                'path': firefox_path
            }
        }
    
    def check_browser_installed(self, browser_type):
        """检查指定浏览器是否安装"""
        if browser_type.lower() == 'chrome':
            return self.detect_chrome()
        elif browser_type.lower() == 'firefox':
            return self.detect_firefox()
        else:
            return False, None


def main():
    """测试函数"""
    detector = BrowserDetector()
    status = detector.get_browser_status()
    
    print("浏览器检测结果:")
    print(f"Chrome: {'已安装' if status['chrome']['installed'] else '未安装'}")
    if status['chrome']['installed']:
        print(f"  - 路径: {status['chrome']['path']}")
    
    print(f"Firefox: {'已安装' if status['firefox']['installed'] else '未安装'}")
    if status['firefox']['installed']:
        print(f"  - 路径: {status['firefox']['path']}")


if __name__ == "__main__":
    main()
