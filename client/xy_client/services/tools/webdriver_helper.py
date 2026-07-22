#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
WebDriver辅助工具
提供WebDriver操作的辅助函数，提高稳定性和性能
"""

import time
from typing import Optional, Callable, Any
from selenium.common.exceptions import (
    StaleElementReferenceException,
    NoSuchElementException,
    TimeoutException,
    WebDriverException
)
from selenium.webdriver.remote.webdriver import WebDriver
from selenium.webdriver.remote.webelement import WebElement
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By


def retry_find_element(max_retries: int = 3, delay: float = 0.3, 
                      operation_delay: float = 0.2) -> Callable:
    """
    元素查找重试装饰器
    
    Args:
        max_retries: 最大重试次数，默认3次
        delay: 重试延迟时间（秒），默认0.3秒
        operation_delay: 操作间隔延迟（秒），默认0.2秒，避免操作过于频繁
    
    Returns:
        装饰器函数
    """
    def decorator(func: Callable) -> Callable:
        def wrapper(driver: WebDriver, *args, **kwargs) -> Optional[WebElement]:
            """包装函数，添加重试逻辑"""
            # 操作间隔延迟，避免操作过于频繁
            if operation_delay > 0:
                time.sleep(operation_delay)
            
            last_exception = None
            for attempt in range(max_retries):
                try:
                    result = func(driver, *args, **kwargs)
                    if result is not None:
                        return result
                except StaleElementReferenceException as e:
                    last_exception = e
                    if attempt < max_retries - 1:
                        # StaleElementReferenceException：元素引用失效，需要重新查找
                        time.sleep(delay)
                        continue
                    else:
                        # 达到最大重试次数，抛出异常
                        raise
                except NoSuchElementException as e:
                    last_exception = e
                    if attempt < max_retries - 1:
                        # NoSuchElementException：元素未找到，可能是页面未加载完成
                        time.sleep(delay)
                        continue
                    else:
                        # 达到最大重试次数，抛出异常
                        raise
                except (TimeoutException, WebDriverException) as e:
                    last_exception = e
                    if attempt < max_retries - 1:
                        # WebDriver异常，可能是连接问题，等待后重试
                        time.sleep(delay * 2)  # 连接问题，延迟时间加倍
                        continue
                    else:
                        raise
            
            # 所有重试都失败，返回None或抛出最后一个异常
            if last_exception:
                raise last_exception
            return None
        
        return wrapper
    return decorator


@retry_find_element(max_retries=3, delay=0.3, operation_delay=0.2)
def safe_find_element(driver: WebDriver, by: By, value: str, 
                      timeout: int = 5) -> Optional[WebElement]:
    """
    安全地查找元素，带重试机制
    
    Args:
        driver: WebDriver实例
        by: 查找方式（By.ID, By.XPATH等）
        value: 查找值
        timeout: 显式等待超时时间（秒），默认5秒
    
    Returns:
        WebElement或None
    """
    try:
        # 使用显式等待，更精确控制等待时间
        element = WebDriverWait(driver, timeout).until(
            EC.presence_of_element_located((by, value))
        )
        return element
    except (TimeoutException, NoSuchElementException):
        return None


@retry_find_element(max_retries=3, delay=0.3, operation_delay=0.2)
def safe_find_elements(driver: WebDriver, by: By, value: str, 
                       timeout: int = 5) -> list:
    """
    安全地查找多个元素，带重试机制
    
    Args:
        driver: WebDriver实例
        by: 查找方式（By.ID, By.XPATH等）
        value: 查找值
        timeout: 显式等待超时时间（秒），默认5秒
    
    Returns:
        元素列表
    """
    try:
        # 使用显式等待，等待至少一个元素出现
        WebDriverWait(driver, timeout).until(
            EC.presence_of_element_located((by, value))
        )
        # 然后查找所有元素
        elements = driver.find_elements(by, value)
        return elements
    except (TimeoutException, NoSuchElementException):
        return []


def check_webdriver_health(driver: WebDriver, timeout: int = 3) -> bool:
    """
    检查WebDriver连接健康状态
    
    Args:
        driver: WebDriver实例
        timeout: 检查超时时间（秒），默认3秒
    
    Returns:
        bool: 连接是否健康
    """
    if driver is None:
        return False
    
    try:
        import threading
        url_result = [None]
        url_error = [None]
        
        def get_url():
            try:
                url_result[0] = driver.current_url
            except Exception as e:
                url_error[0] = e
        
        check_thread = threading.Thread(target=get_url, daemon=True)
        check_thread.start()
        check_thread.join(timeout=timeout)
        
        if url_error[0] is None and url_result[0] is not None:
            return True
        return False
    except Exception:
        return False


def safe_webdriver_operation(operation: Callable, *args, 
                            max_retries: int = 2, 
                            delay: float = 0.5,
                            operation_delay: float = 0.2,
                            **kwargs) -> Any:
    """
    安全地执行WebDriver操作，带重试机制和操作间隔
    
    Args:
        operation: 要执行的操作函数
        *args: 操作函数的参数
        max_retries: 最大重试次数，默认2次
        delay: 重试延迟时间（秒），默认0.5秒
        operation_delay: 操作间隔延迟（秒），默认0.2秒
        **kwargs: 操作函数的关键字参数
    
    Returns:
        操作结果
    """
    # 操作间隔延迟，避免操作过于频繁
    if operation_delay > 0:
        time.sleep(operation_delay)
    
    last_exception = None
    for attempt in range(max_retries):
        try:
            result = operation(*args, **kwargs)
            return result
        except (StaleElementReferenceException, NoSuchElementException) as e:
            last_exception = e
            if attempt < max_retries - 1:
                time.sleep(delay)
                continue
            raise
        except (TimeoutException, WebDriverException) as e:
            last_exception = e
            if attempt < max_retries - 1:
                time.sleep(delay * 2)  # 连接问题，延迟时间加倍
                continue
            raise
        except Exception as e:
            last_exception = e
            if attempt < max_retries - 1:
                time.sleep(delay)
                continue
            raise
    
    if last_exception:
        raise last_exception
    return None


def safe_refresh_with_delay(driver: WebDriver, delay: float = 0.3) -> bool:
    """
    安全地刷新页面，带操作间隔
    
    Args:
        driver: WebDriver实例
        delay: 操作间隔延迟（秒），默认0.3秒
    
    Returns:
        bool: 刷新是否成功
    """
    try:
        time.sleep(delay)  # 操作间隔
        driver.refresh()
        return True
    except Exception:
        return False


def safe_get_with_delay(driver: WebDriver, url: str, delay: float = 0.3) -> bool:
    """
    安全地打开页面，带操作间隔
    
    Args:
        driver: WebDriver实例
        url: 要打开的URL
        delay: 操作间隔延迟（秒），默认0.3秒
    
    Returns:
        bool: 打开是否成功
    """
    try:
        time.sleep(delay)  # 操作间隔
        driver.get(url)
        return True
    except Exception:
        return False

