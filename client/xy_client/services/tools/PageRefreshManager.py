#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
页面刷新管理器
处理页面刷新时的加载超时问题，自动取消卡住的加载并重新刷新
"""

import time
import threading
from typing import Optional, Callable
from selenium.common.exceptions import TimeoutException, WebDriverException
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


class PageRefreshManager:
    """页面刷新管理器 - 处理页面刷新超时和卡住问题"""
    
    def __init__(self, page_load_timeout: int = 10, max_retry: int = 2):
        """
        初始化页面刷新管理器
        
        Args:
            page_load_timeout: 页面加载超时时间（秒），默认10秒
            max_retry: 最大重试次数，默认2次
        """
        self.page_load_timeout = page_load_timeout
        self.max_retry = max_retry
    
    def is_page_loading(self, driver) -> bool:
        """
        检测页面是否正在加载中
        
        Args:
            driver: WebDriver实例
            
        Returns:
            bool: True表示正在加载，False表示已加载完成
        """
        try:
            # 方法1: 检查 document.readyState
            ready_state = driver.execute_script("return document.readyState")
            if ready_state != "complete":
                return True
            
            # 方法2: 检查是否有正在进行的网络请求（通过 performance API）
            try:
                active_requests = driver.execute_script("""
                    if (window.performance && window.performance.getEntriesByType) {
                        var entries = window.performance.getEntriesByType('resource');
                        var active = entries.filter(function(e) {
                            return e.transferSize === 0 && e.duration === 0;
                        });
                        return active.length > 0;
                    }
                    return false;
                """)
                if active_requests:
                    return True
            except:
                pass
            
            # 方法3: 检查页面标题或URL是否在变化（通过比较）
            # 这个方法需要外部调用时提供之前的URL进行比较
            
            return False
        except Exception as e:
            # 如果检测失败，假设页面不在加载中
            return False
    
    def stop_page_loading(self, driver) -> bool:
        """
        停止页面加载（相当于点击浏览器的停止按钮）
        
        Args:
            driver: WebDriver实例
            
        Returns:
            bool: 是否成功停止
        """
        try:
            # 方法1: 使用 window.stop() 停止页面加载
            driver.execute_script("window.stop();")
            time.sleep(0.5)  # 等待停止生效
            
            # 方法2: 如果 window.stop() 无效，尝试中断所有网络请求
            try:
                driver.execute_script("""
                    if (window.stop) {
                        window.stop();
                    }
                    // 尝试中断 fetch 请求
                    if (window.AbortController) {
                        // 这里可以维护一个 AbortController 列表来中断请求
                    }
                """)
            except:
                pass
            
            return True
        except Exception as e:
            return False
    
    def safe_refresh(self, driver, reason: str = "页面刷新", 
                     check_loading: bool = True, 
                     timeout: Optional[int] = None) -> bool:
        """
        安全地刷新页面，包含超时控制和自动恢复
        
        Args:
            driver: WebDriver实例
            reason: 刷新原因描述
            check_loading: 是否检查页面加载状态
            timeout: 自定义超时时间（秒），如果为None则使用默认值
            
        Returns:
            bool: 刷新是否成功
        """
        if timeout is None:
            timeout = self.page_load_timeout
        
        try:
            print(f"🔄 [{reason}] 开始刷新页面...")
            
            # 步骤1: 如果页面正在加载，先停止加载
            if check_loading and self.is_page_loading(driver):
                print(f"⚠️ [{reason}] 检测到页面正在加载，先停止加载...")
                self.stop_page_loading(driver)
                time.sleep(0.5)
            
            # 步骤2: 执行刷新，带超时控制
            refresh_success = [False]
            refresh_error = [None]
            
            def do_refresh():
                try:
                    # 设置页面加载超时（某些WebDriver可能不支持，忽略错误）
                    try:
                        driver.set_page_load_timeout(timeout)
                    except:
                        pass  # 某些WebDriver可能不支持
                    
                    # 执行刷新
                    driver.refresh()
                    refresh_success[0] = True
                except TimeoutException:
                    refresh_error[0] = "页面加载超时"
                except WebDriverException as e:
                    refresh_error[0] = f"WebDriver异常: {str(e)}"
                except Exception as e:
                    refresh_error[0] = f"刷新异常: {str(e)}"
            
            # 在独立线程中执行刷新，以便超时控制
            refresh_thread = threading.Thread(target=do_refresh, daemon=True)
            refresh_thread.start()
            refresh_thread.join(timeout=timeout + 2)  # 多给2秒缓冲时间
            
            # 步骤3: 检查刷新结果
            if refresh_thread.is_alive():
                # 刷新超时，停止加载
                print(f"⚠️ [{reason}] 页面刷新超时（{timeout}秒），停止加载...")
                self.stop_page_loading(driver)
                
                # 等待线程结束
                refresh_thread.join(timeout=2)
                
                # 重试刷新
                return self._retry_refresh(driver, reason, timeout)
            elif refresh_error[0]:
                print(f"⚠️ [{reason}] 刷新失败: {refresh_error[0]}")
                # 重试刷新
                return self._retry_refresh(driver, reason, timeout)
            elif refresh_success[0]:
                # 检查页面是否正常加载（最多等待5秒）
                if check_loading:
                    max_wait = 5  # 最多等待5秒
                    for i in range(max_wait):
                        if not self.is_page_loading(driver):
                            print(f"✅ [{reason}] 页面刷新成功")
                            return True
                        time.sleep(1)
                    
                    # 如果5秒后还在加载，停止加载并重试
                    print(f"⚠️ [{reason}] 页面刷新后仍在加载（超过5秒），停止加载并重试...")
                    self.stop_page_loading(driver)
                    return self._retry_refresh(driver, reason, timeout)
                else:
                    print(f"✅ [{reason}] 页面刷新成功")
                    return True
            else:
                print(f"⚠️ [{reason}] 刷新状态未知，重试...")
                return self._retry_refresh(driver, reason, timeout)
                
        except Exception as e:
            print(f"❌ [{reason}] 刷新异常: {e}")
            return False
    
    def _retry_refresh(self, driver, reason: str, timeout: int, retry_count: int = 0) -> bool:
        """
        重试刷新页面
        
        Args:
            driver: WebDriver实例
            reason: 刷新原因
            timeout: 超时时间
            retry_count: 当前重试次数
            
        Returns:
            bool: 是否成功
        """
        if retry_count >= self.max_retry:
            print(f"❌ [{reason}] 刷新失败，已达到最大重试次数（{self.max_retry}次）")
            return False
        
        print(f"🔄 [{reason}] 重试刷新（第{retry_count + 1}/{self.max_retry}次）...")
        time.sleep(1)  # 等待1秒后重试
        
        # 直接执行刷新逻辑，避免递归调用
        try:
            # 先停止可能正在进行的加载
            self.stop_page_loading(driver)
            time.sleep(0.5)
            
            # 执行刷新，带超时控制
            refresh_success = [False]
            refresh_error = [None]
            
            def do_refresh():
                try:
                    # 设置页面加载超时（某些WebDriver可能不支持，忽略错误）
                    try:
                        driver.set_page_load_timeout(timeout)
                    except:
                        pass
                    
                    driver.refresh()
                    refresh_success[0] = True
                except TimeoutException:
                    refresh_error[0] = "页面加载超时"
                except WebDriverException as e:
                    refresh_error[0] = f"WebDriver异常: {str(e)}"
                except Exception as e:
                    refresh_error[0] = f"刷新异常: {str(e)}"
            
            refresh_thread = threading.Thread(target=do_refresh, daemon=True)
            refresh_thread.start()
            refresh_thread.join(timeout=timeout + 2)
            
            if refresh_thread.is_alive():
                self.stop_page_loading(driver)
                refresh_thread.join(timeout=2)
                # 如果还是失败，继续重试
                return self._retry_refresh(driver, reason, timeout, retry_count + 1)
            elif refresh_error[0]:
                # 如果出错，继续重试
                return self._retry_refresh(driver, reason, timeout, retry_count + 1)
            elif refresh_success[0]:
                # 检查页面是否正常加载（最多等待5秒）
                max_wait = 5
                for i in range(max_wait):
                    if not self.is_page_loading(driver):
                        print(f"✅ [{reason}] 页面刷新成功（重试{retry_count + 1}次后）")
                        return True
                    time.sleep(1)
                
                # 如果还在加载，继续重试
                self.stop_page_loading(driver)
                return self._retry_refresh(driver, reason, timeout, retry_count + 1)
            else:
                return self._retry_refresh(driver, reason, timeout, retry_count + 1)
        except Exception as e:
            print(f"❌ [{reason}] 重试刷新异常: {e}")
            if retry_count < self.max_retry:
                return self._retry_refresh(driver, reason, timeout, retry_count + 1)
            return False
    
    def safe_get(self, driver, url: str, reason: str = "打开页面",
                 check_loading: bool = True, 
                 timeout: Optional[int] = None) -> bool:
        """
        安全地打开页面，包含超时控制和自动恢复
        
        Args:
            driver: WebDriver实例
            url: 要打开的URL
            reason: 打开原因描述
            check_loading: 是否检查页面加载状态
            timeout: 自定义超时时间（秒），如果为None则使用默认值
            
        Returns:
            bool: 打开是否成功
        """
        if timeout is None:
            timeout = self.page_load_timeout
        
        try:
            print(f"🔄 [{reason}] 开始打开页面: {url}")
            
            # 步骤1: 如果页面正在加载，先停止加载
            if check_loading and self.is_page_loading(driver):
                print(f"⚠️ [{reason}] 检测到页面正在加载，先停止加载...")
                self.stop_page_loading(driver)
                time.sleep(0.5)
            
            # 步骤2: 执行打开，带超时控制
            get_success = [False]
            get_error = [None]
            
            def do_get():
                try:
                    # 设置页面加载超时（某些WebDriver可能不支持，忽略错误）
                    try:
                        driver.set_page_load_timeout(timeout)
                    except:
                        pass
                    
                    # 执行打开
                    driver.get(url)
                    get_success[0] = True
                except TimeoutException:
                    get_error[0] = "页面加载超时"
                except WebDriverException as e:
                    get_error[0] = f"WebDriver异常: {str(e)}"
                except Exception as e:
                    get_error[0] = f"打开异常: {str(e)}"
            
            # 在独立线程中执行打开，以便超时控制
            get_thread = threading.Thread(target=do_get, daemon=True)
            get_thread.start()
            get_thread.join(timeout=timeout + 2)  # 多给2秒缓冲时间
            
            # 步骤3: 检查打开结果
            if get_thread.is_alive():
                # 打开超时，停止加载
                print(f"⚠️ [{reason}] 页面打开超时（{timeout}秒），停止加载...")
                self.stop_page_loading(driver)
                
                # 等待线程结束
                get_thread.join(timeout=2)
                
                # 重试打开
                return self._retry_get(driver, url, reason, timeout)
            elif get_error[0]:
                print(f"⚠️ [{reason}] 打开失败: {get_error[0]}")
                # 重试打开
                return self._retry_get(driver, url, reason, timeout)
            elif get_success[0]:
                # 检查页面是否正常加载（最多等待5秒）
                if check_loading:
                    max_wait = 8  # 最多等待8秒
                    for i in range(max_wait):
                        if not self.is_page_loading(driver):
                            print(f"✅ [{reason}] 页面打开成功")
                            return True
                        time.sleep(1)
                    
                    # 如果5秒后还在加载，停止加载并重试
                    print(f"⚠️ [{reason}] 页面打开后仍在加载（超过8秒），停止加载并重试...")
                    self.stop_page_loading(driver)
                    return self._retry_get(driver, url, reason, timeout)
                else:
                    print(f"✅ [{reason}] 页面打开成功")
                    return True
            else:
                print(f"⚠️ [{reason}] 打开状态未知，重试...")
                return self._retry_get(driver, url, reason, timeout)
                
        except Exception as e:
            print(f"❌ [{reason}] 打开异常: {e}")
            return False
    
    def _retry_get(self, driver, url: str, reason: str, timeout: int, retry_count: int = 0) -> bool:
        """
        重试打开页面
        
        Args:
            driver: WebDriver实例
            url: 要打开的URL
            reason: 打开原因
            timeout: 超时时间
            retry_count: 当前重试次数
            
        Returns:
            bool: 是否成功
        """
        if retry_count >= self.max_retry:
            print(f"❌ [{reason}] 打开失败，已达到最大重试次数（{self.max_retry}次）")
            return False
        
        print(f"🔄 [{reason}] 重试打开（第{retry_count + 1}/{self.max_retry}次）...")
        time.sleep(1)  # 等待1秒后重试
        
        # 直接执行打开逻辑，避免递归调用
        try:
            # 先停止可能正在进行的加载
            self.stop_page_loading(driver)
            time.sleep(0.5)
            
            # 执行打开，带超时控制
            get_success = [False]
            get_error = [None]
            
            def do_get():
                try:
                    # 设置页面加载超时（某些WebDriver可能不支持，忽略错误）
                    try:
                        driver.set_page_load_timeout(timeout)
                    except:
                        pass
                    
                    driver.get(url)
                    get_success[0] = True
                except TimeoutException:
                    get_error[0] = "页面加载超时"
                except WebDriverException as e:
                    get_error[0] = f"WebDriver异常: {str(e)}"
                except Exception as e:
                    get_error[0] = f"打开异常: {str(e)}"
            
            get_thread = threading.Thread(target=do_get, daemon=True)
            get_thread.start()
            get_thread.join(timeout=timeout + 2)
            
            if get_thread.is_alive():
                self.stop_page_loading(driver)
                get_thread.join(timeout=2)
                # 如果还是失败，继续重试
                return self._retry_get(driver, url, reason, timeout, retry_count + 1)
            elif get_error[0]:
                # 如果出错，继续重试
                return self._retry_get(driver, url, reason, timeout, retry_count + 1)
            elif get_success[0]:
                # 检查页面是否正常加载（最多等待5秒）
                max_wait = 5
                for i in range(max_wait):
                    if not self.is_page_loading(driver):
                        print(f"✅ [{reason}] 页面打开成功（重试{retry_count + 1}次后）")
                        return True
                    time.sleep(1)
                
                # 如果还在加载，继续重试
                self.stop_page_loading(driver)
                return self._retry_get(driver, url, reason, timeout, retry_count + 1)
            else:
                return self._retry_get(driver, url, reason, timeout, retry_count + 1)
        except Exception as e:
            print(f"❌ [{reason}] 重试打开异常: {e}")
            if retry_count < self.max_retry:
                return self._retry_get(driver, url, reason, timeout, retry_count + 1)
            return False


# 全局实例
_global_refresh_manager = None

def get_refresh_manager(page_load_timeout: int = 15, max_retry: int = 2) -> PageRefreshManager:
    """获取全局页面刷新管理器实例"""
    global _global_refresh_manager
    if _global_refresh_manager is None:
        _global_refresh_manager = PageRefreshManager(page_load_timeout, max_retry)
    return _global_refresh_manager

