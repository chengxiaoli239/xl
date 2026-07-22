#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
简化版输入阻塞预防模块
只保留最基本的功能，避免干扰程序正常运行
"""

import sys
import os
import threading
import time

class InputBlockingPrevention:
    """简化版输入阻塞预防器"""
    
    def __init__(self):
        self.is_active = False
        self.cleanup_callbacks = []
    
    def activate(self) -> bool:
        """激活输入阻塞预防"""
        try:
            if self.is_active:
                return True
            
            # 只做最基本的预防：清空输入缓冲区
            self._clear_input_buffer()
            
            self.is_active = True
            print("✅ 简化版输入阻塞预防已激活")
            return True
            
        except Exception as e:
            print(f"❌ 激活输入阻塞预防失败: {e}")
            return False
    
    def deactivate(self) -> bool:
        """停用输入阻塞预防"""
        try:
            if not self.is_active:
                return True
            
            # 执行清理回调
            for callback in self.cleanup_callbacks:
                try:
                    callback()
                except Exception as e:
                    print(f"⚠️ 执行清理回调失败: {e}")
            
            self.is_active = False
            print("✅ 输入阻塞预防已停用")
            return True
            
        except Exception as e:
            print(f"❌ 停用输入阻塞预防失败: {e}")
            return False
    
    def _clear_input_buffer(self):
        """清空输入缓冲区"""
        try:
            if os.name == 'nt':  # Windows
                import msvcrt
                while msvcrt.kbhit():
                    msvcrt.getch()
            else:  # Unix/Linux
                import select
                if hasattr(sys.stdin, 'fileno'):
                    while True:
                        ready, _, _ = select.select([sys.stdin], [], [], 0)
                        if not ready:
                            break
                        try:
                            sys.stdin.readline()
                        except:
                            break
        except Exception as e:
            pass  # 静默处理清空缓冲区的异常
    
    def add_cleanup_callback(self, callback):
        """添加清理回调函数"""
        self.cleanup_callbacks.append(callback)
    
    def check_blocking_status(self) -> dict:
        """检查阻塞状态"""
        status = {
            'is_active': self.is_active,
            'method': 'simple',
            'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
        }
        return status
    
    def emergency_recovery(self):
        """紧急恢复机制"""
        try:
            print("🚨 执行简化版紧急恢复...")
            
            # 只做最基本的恢复：清空缓冲区
            self._clear_input_buffer()
            
            print("✅ 简化版紧急恢复完成")
            return True
                
        except Exception as e:
            print(f"❌ 紧急恢复异常: {e}")
            return False


# 全局实例
_global_prevention = None

def get_input_prevention() -> InputBlockingPrevention:
    """获取全局输入阻塞预防实例"""
    global _global_prevention
    if _global_prevention is None:
        _global_prevention = InputBlockingPrevention()
    return _global_prevention

def activate_input_prevention() -> bool:
    """激活全局输入阻塞预防"""
    return get_input_prevention().activate()

def deactivate_input_prevention() -> bool:
    """停用全局输入阻塞预防"""
    return get_input_prevention().deactivate()

def emergency_recovery() -> bool:
    """执行紧急恢复"""
    return get_input_prevention().emergency_recovery()

def check_blocking_status() -> dict:
    """检查阻塞状态"""
    return get_input_prevention().check_blocking_status()


if __name__ == "__main__":
    # 测试代码
    print("🧪 测试简化版输入阻塞预防模块...")
    
    # 激活预防
    if activate_input_prevention():
        print("✅ 预防激活成功")
        
        # 检查状态
        status = check_blocking_status()
        print(f"📊 状态: {status}")
        
        # 模拟程序运行
        print("🔄 模拟程序运行中...")
        for i in range(5):
            print(f"  任务 {i+1} 执行中...")
            time.sleep(1)
        
        # 停用预防
        if deactivate_input_prevention():
            print("✅ 预防停用成功")
        else:
            print("❌ 预防停用失败")
    else:
        print("❌ 预防激活失败")
