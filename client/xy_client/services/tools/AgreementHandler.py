#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
责任声明页面处理模块
专门处理协议页面的"同意"按钮点击操作
"""

import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.keys import Keys
from selenium.common.exceptions import TimeoutException, NoSuchElementException, ElementNotInteractableException


class AgreementHandler:
    """责任声明页面处理器"""
    
    def __init__(self, driver, timeout=5):
        self.driver = driver
        self.timeout = timeout  # 减少超时时间到5秒
        self.wait = WebDriverWait(driver, timeout)
        self._main_window = None  # 主窗口实例（用于连接恢复）
    
    def wait_for_page_load(self):
        """等待页面完全加载"""
        try:
            print("⏳ 等待责任声明页面完全加载...")
            
            # 先处理可能出现的弹框
            try:
                # 等待1秒让弹框出现
                time.sleep(1)
                
                # 尝试关闭弹框
                close_buttons = self.driver.find_elements(By.CSS_SELECTOR, ".btn-close.fn-close")
                if close_buttons:
                    for btn in close_buttons:
                        try:
                            if btn.is_displayed():
                                btn.click()
                                print("✅ 已关闭登录后弹框")
                                time.sleep(1)
                                break
                        except:
                            continue
            except:
                pass
            
            # 等待页面标题包含协议相关关键词
            self.wait.until(
                lambda d: any(keyword in d.title.lower() for keyword in 
                             ['协议', 'agreement', 'disclaimer', '责任声明', '条款'])
            )
            print("✅ 页面标题确认：已进入协议页面")
            
            # 等待页面内容加载完成
            self.wait.until(
                lambda d: d.execute_script("return document.readyState") == "complete"
            )
            print("✅ 页面DOM加载完成")
            
            # 减少等待时间，只等待1秒
            time.sleep(1)
            
            return True
            
        except TimeoutException:
            print("⚠️ 页面加载超时，但继续尝试查找按钮")
            return False
        except Exception as e:
            print(f"⚠️ 页面加载检查异常: {e}")
            return False
    
    def _check_and_recover_driver(self, main_window=None):
        """检查并恢复WebDriver连接"""
        try:
            # 尝试获取当前URL来验证连接是否可用
            self.driver.current_url
            return True
        except Exception as e:
            error_str = str(e).lower()
            if 'invalid session' in error_str or 'session id' in error_str:
                print(f"⚠️ WebDriver连接已断开，尝试恢复连接...")
                # 方法1：尝试从mainWindow恢复连接（如果driver是从mainWindow传入的）
                if hasattr(self.driver, '_main_window_ref'):
                    main_window = self.driver._main_window_ref
                
                # 方法2：如果main_window作为参数传入，使用它
                if main_window and hasattr(main_window, 'browser_manager'):
                    try:
                        new_driver = main_window.browser_manager.check_and_recover_browser_connection(silent=False)
                        if new_driver:
                            self.driver = new_driver
                            self.wait = WebDriverWait(self.driver, self.timeout)
                            print("✅ WebDriver连接已恢复")
                            return True
                    except Exception as recover_e:
                        print(f"⚠️ 恢复WebDriver连接失败: {recover_e}")
                
                # 方法3：尝试从全局mainWindow恢复（如果可用）
                try:
                    import sys
                    for obj in sys.modules.values():
                        if hasattr(obj, 'mainWindow') and hasattr(obj.mainWindow, 'browser_manager'):
                            new_driver = obj.mainWindow.browser_manager.check_and_recover_browser_connection(silent=False)
                            if new_driver:
                                self.driver = new_driver
                                self.wait = WebDriverWait(self.driver, self.timeout)
                                print("✅ WebDriver连接已恢复（从全局mainWindow）")
                                return True
                except:
                    pass
                
                return False
            return False
    
    def find_agree_button(self):
        """查找同意按钮，使用多种选择器策略"""
        print("🔍 开始查找同意按钮...")
        
        # 检查并恢复WebDriver连接
        if not self._check_and_recover_driver(self._main_window):
            print("❌ WebDriver连接异常，无法查找同意按钮")
            return None
        
        # 策略1：通过ID查找
        try:
            print(f"🔍 方式1：通过ID查找 id='agree'，等待时间: {self.timeout}秒...")
            agree_btn = self.wait.until(
                EC.element_to_be_clickable((By.ID, "agree"))
            )
            print("✅ 方式1成功：找到 id='agree' 的按钮")
            return agree_btn
        except TimeoutException:
            print(f"❌ 方式1失败：在 {self.timeout} 秒内未找到 id='agree' 的按钮")
        except Exception as e:
            error_str = str(e).lower()
            if 'invalid session' in error_str or 'session id' in error_str:
                print(f"❌ 方式1异常：WebDriver连接断开: {e}")
                # 尝试恢复连接后重试
                if self._check_and_recover_driver(self._main_window):
                    try:
                        agree_btn = WebDriverWait(self.driver, self.timeout).until(
                            EC.element_to_be_clickable((By.ID, "agree"))
                        )
                        print("✅ 方式1成功（恢复连接后）：找到 id='agree' 的按钮")
                        return agree_btn
                    except:
                        pass
            else:
                print(f"❌ 方式1异常：{e}")
        
        # 策略2：通过文本内容查找
        try:
            print("🔍 方式2：通过文本内容查找包含'同意'的按钮...")
            # 检查连接
            if not self._check_and_recover_driver(self._main_window):
                return None
            agree_btn = self.wait.until(
                EC.element_to_be_clickable((By.XPATH, "//button[contains(text(),'同意')] | //input[@type='button' and contains(@value,'同意')] | //a[contains(text(),'同意')]"))
            )
            print("✅ 方式2成功：找到包含'同意'文本的按钮")
            return agree_btn
        except TimeoutException:
            print("❌ 方式2失败：未找到包含'同意'文本的按钮")
        except Exception as e:
            error_str = str(e).lower()
            if 'invalid session' in error_str or 'session id' in error_str:
                print(f"❌ 方式2异常：WebDriver连接断开: {e}")
            else:
                print(f"❌ 方式2异常：{e}")
        
        # 策略3：查找所有按钮元素
        try:
            print("🔍 方式3：查找所有按钮元素...")
            # 检查连接
            if not self._check_and_recover_driver(self._main_window):
                return None
            buttons = self.driver.find_elements(By.TAG_NAME, "button")
            buttons.extend(self.driver.find_elements(By.XPATH, "//input[@type='button']"))
            buttons.extend(self.driver.find_elements(By.XPATH, "//input[@type='submit']"))
            
            print(f"找到 {len(buttons)} 个按钮:")
            for i, btn in enumerate(buttons):
                try:
                    text = btn.text or btn.get_attribute('value') or btn.get_attribute('title') or '无文本'
                    print(f"  按钮{i+1}: {text}")
                    
                    # 检查是否包含"同意"
                    if '同意' in text:
                        print(f"✅ 方式3成功：找到包含'同意'的按钮")
                        return btn
                except Exception as btn_e:
                    error_str = str(btn_e).lower()
                    if 'invalid session' in error_str or 'session id' in error_str:
                        print(f"  按钮{i+1}: WebDriver连接断开")
                        if self._check_and_recover_driver(self._main_window):
                            continue
                        else:
                            break
                    else:
                        print(f"  按钮{i+1}: 无法获取文本 ({btn_e})")
            
            print("❌ 方式3失败：所有按钮中都没有找到'同意'")
        except Exception as e:
            error_str = str(e).lower()
            if 'invalid session' in error_str or 'session id' in error_str:
                print(f"❌ 方式3异常：WebDriver连接断开: {e}")
            else:
                print(f"❌ 方式3异常：{e}")
        
        print("❌ 所有方式都未找到同意按钮")
        return None
    
    def is_button_clickable(self, button):
        """检查按钮是否真正可点击"""
        try:
            # 检查元素是否存在
            if not button:
                return False
            
            # 检查元素是否可见
            if not button.is_displayed():
                print("⚠️ 按钮不可见")
                return False
            
            # 检查元素是否启用
            if not button.is_enabled():
                print("⚠️ 按钮未启用")
                return False
            
            # 检查元素是否在视口内
            try:
                is_in_viewport = self.driver.execute_script(
                    "var rect = arguments[0].getBoundingClientRect();"
                    "return (rect.top >= 0 && rect.left >= 0 && "
                    "rect.bottom <= window.innerHeight && "
                    "rect.right <= window.innerWidth);",
                    button
                )
                if not is_in_viewport:
                    print("⚠️ 按钮不在视口内")
                    return False
            except:
                pass  # 如果检查失败，继续尝试点击
            
            print("✅ 按钮状态检查通过：可见、启用、可交互")
            return True
            
        except Exception as e:
            print(f"❌ 按钮状态检查异常：{e}")
            return False
    
    def scroll_to_button(self, button):
        """滚动到按钮位置"""
        try:
            print("🔄 滚动到按钮位置...")
            self.driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", button)
            time.sleep(0.5)  # 等待滚动完成
            print("✅ 滚动完成")
        except Exception as e:
            print(f"⚠️ 滚动异常：{e}")
    
    def click_button(self, button):
        """点击按钮，使用最可靠的方式"""
        if not button:
            print("❌ 按钮对象为空，无法点击")
            return False
        
        print("🖱️ 开始点击按钮...")
        
        # 等待0.5秒确保按钮完全可点击
        time.sleep(0.5)
        
        # 策略1：JavaScript点击（最可靠，避免stale element reference）
        try:
            print("🖱️ 使用JavaScript点击...")
            self.driver.execute_script("arguments[0].click();", button)
            print("✅ JavaScript点击成功")
            return True
        except Exception as e:
            print(f"❌ JavaScript点击失败：{e}")
        
        # 策略2：直接点击（备用方案）
        try:
            print("🖱️ 使用直接点击...")
            button.click()
            print("✅ 直接点击成功")
            return True
        except Exception as e:
            print(f"❌ 直接点击失败：{e}")
        
        print("❌ 所有点击策略都失败")
        return False
    
    def verify_click_success(self):
        """验证点击是否成功"""
        try:
            print("🔍 验证点击结果...")
            time.sleep(3)  # 等待页面响应
            
            current_url = self.driver.current_url
            page_title = self.driver.title
            
            print(f"当前URL: {current_url}")
            print(f"页面标题: {page_title}")
            
            # 检查是否离开了协议页面
            if not any(keyword in current_url.lower() for keyword in 
                      ['agreement', '协议', 'disclaimer', '责任声明']):
                print("✅ 点击成功：已离开协议页面")
                return True
            else:
                print("⚠️ 仍在协议页面，可能点击未生效")
                return False
                
        except Exception as e:
            print(f"❌ 验证点击结果异常：{e}")
            return False
    
    def handle_agreement_page(self):
        """处理责任声明页面的完整流程"""
        try:
            print("🚀 开始处理责任声明页面...")
            
            # 1. 等待页面加载
            if not self.wait_for_page_load():
                print("⚠️ 页面加载检查失败，但继续尝试")
            
            # 2. 查找同意按钮（在指定时间内一直等待）
            agree_button = self.find_agree_button()
            if not agree_button:
                print(f"❌ 在 {self.timeout} 秒内未找到同意按钮")
                # 关键优化：即使找不到按钮，也检查是否已经离开协议页面（可能已经自动跳转）
                try:
                    if self._check_and_recover_driver(self._main_window):
                        current_url = self.driver.current_url.lower()
                        if "agreement" not in current_url and "协议" not in current_url:
                            print("✅ 虽然未找到按钮，但已离开协议页面，可能已自动跳转")
                            return True
                except:
                    pass
                return False
            
            # 3. 点击按钮
            if not self.click_button(agree_button):
                print("❌ 点击同意按钮失败")
                # 关键优化：即使点击失败，也检查是否已经离开协议页面
                try:
                    if self._check_and_recover_driver(self._main_window):
                        time.sleep(0.5)  # 优化：减少等待时间
                        current_url = self.driver.current_url.lower()
                        if "agreement" not in current_url and "协议" not in current_url:
                            print("✅ 虽然点击失败，但已离开协议页面，可能已自动跳转")
                            return True
                except:
                    pass
                return False
            
            # 4. 验证点击是否成功（检查是否已离开协议页面）
            # 优化：使用WebDriverWait智能等待，而不是固定sleep
            from selenium.webdriver.support.ui import WebDriverWait
            from selenium.webdriver.support import expected_conditions as EC
            try:
                wait = WebDriverWait(self.driver, 2, 0.2)
                wait.until(lambda d: "agreement" not in d.current_url.lower() and "协议" not in d.current_url.lower())
            except:
                time.sleep(0.5)  # 如果等待失败，只等待0.5秒
            try:
                if self._check_and_recover_driver(self._main_window):
                    current_url = self.driver.current_url.lower()
                    if "agreement" not in current_url and "协议" not in current_url:
                        print("✅ 责任声明页面处理完成，已离开协议页面")
                        return True
                    else:
                        print("⚠️ 仍在协议页面，可能点击未生效")
                        return False
                else:
                    print("⚠️ WebDriver连接异常，无法验证，但假设处理成功")
                    return True  # 关键优化：即使连接异常，也假设处理成功，避免一直停留在协议页面
            except Exception as verify_e:
                print(f"⚠️ 验证点击结果异常: {verify_e}，但假设处理成功")
                return True  # 关键优化：即使验证异常，也假设处理成功，避免一直停留在协议页面
                
        except Exception as e:
            print(f"❌ 处理责任声明页面异常：{e}")
            return False


def handle_agreement_page(driver, timeout=15, main_window=None):
    """
    处理责任声明页面的便捷函数
    
    Args:
        driver: WebDriver实例
        timeout: 超时时间（秒）
        main_window: 主窗口实例（可选，用于恢复WebDriver连接）
    
    Returns:
        bool: 处理是否成功
    """
    # 首先检查当前URL，如果不在协议页面，直接返回
    try:
        current_url = driver.current_url.lower()
        
        # 如果已经在主页面，跳过处理
        if "app" in current_url or "index" in current_url:
            # 静默返回，不打印日志（避免日志过多）
            return True
        
        # 如果不在协议页面，也跳过处理
        if "agreement" not in current_url and "协议" not in current_url:
            # 静默返回，不打印日志
            return True
        
        # 只有在协议页面才处理
        handler = AgreementHandler(driver, timeout)
        # 传递main_window给handler，用于连接恢复
        if main_window:
            handler._main_window = main_window
        return handler.handle_agreement_page()
    except Exception as e:
        # 如果检查URL失败，尝试处理（可能是页面加载问题）
        print(f"⚠️ [AgreementHandler] URL检查异常: {e}，尝试处理")
        handler = AgreementHandler(driver, timeout)
        # 传递main_window给handler，用于连接恢复
        if main_window:
            handler._main_window = main_window
        return handler.handle_agreement_page()


if __name__ == "__main__":
    # 测试代码
    print("🧪 责任声明页面处理器测试")
    print("使用方法：")
    print("  from xy_client.services.tools.AgreementHandler import handle_agreement_page")
    print("  success = handle_agreement_page(driver)")
