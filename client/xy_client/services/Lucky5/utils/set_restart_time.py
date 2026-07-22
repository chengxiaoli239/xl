#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
设置每日重启时间的便捷脚本
在Python交互环境中使用，方便设置和查看重启时间
"""

import sys
import os


def get_main_window():
    """
    获取运行中的mainWindow实例
    
    Returns:
        MainWindow实例，如果找不到则返回None
    """
    try:
        # 方法1：从全局变量获取（最常见的情况）
        if 'mainWindow' in globals():
            return globals()['mainWindow']
        
        # 方法2：从调用栈中查找（如果是在程序内部调用）
        import inspect
        frame = inspect.currentframe()
        while frame:
            if 'mainWindow' in frame.f_locals:
                mw = frame.f_locals['mainWindow']
                if mw and hasattr(mw, '_daily_restart_scheduler'):
                    return mw
            if 'self' in frame.f_locals:
                self_obj = frame.f_locals['self']
                if hasattr(self_obj, '_daily_restart_scheduler'):
                    return self_obj
            frame = frame.f_back
        
        # 方法3：从sys.modules获取
        for module_name, module in list(sys.modules.items()):
            if module and hasattr(module, 'mainWindow'):
                mw = getattr(module, 'mainWindow', None)
                if mw and hasattr(mw, '_daily_restart_scheduler'):
                    return mw
        
        # 方法4：从LuckyClientOP模块获取
        try:
            import xy_client.LuckyClientOP as lucky_module
            if hasattr(lucky_module, 'mainWindow'):
                mw = getattr(lucky_module, 'mainWindow', None)
                if mw and hasattr(mw, '_daily_restart_scheduler'):
                    return mw
        except:
            pass
        
        # 方法5：从GuiClient模块获取
        try:
            import xy_client.GuiClient as gui_module
            if hasattr(gui_module, 'mainWindow'):
                mw = getattr(gui_module, 'mainWindow', None)
                if mw and hasattr(mw, '_daily_restart_scheduler'):
                    return mw
        except:
            pass
        
        return None
    except Exception as e:
        print(f"⚠️ 获取mainWindow实例失败: {e}")
        import traceback
        traceback.print_exc()
        return None


def set_restart_time(hour, minute):
    """
    设置每日重启时间（便捷函数）
    
    Args:
        hour: 小时（0-23）
        minute: 分钟（0-59）
    
    Returns:
        bool: 是否成功设置
    
    Example:
        >>> set_restart_time(8, 30)  # 设置为8:30
        ✅ 重启时间已设置为: 08:30
        True
    """
    main_window = get_main_window()
    if not main_window:
        print("❌ 未找到运行中的程序实例，请确保程序已启动并登录")
        return False
    
    if not hasattr(main_window, '_daily_restart_scheduler'):
        print("❌ 未找到每日重启调度器，请确保程序已启动自动化任务")
        return False
    
    scheduler = main_window._daily_restart_scheduler
    success = scheduler.update_restart_time(hour, minute)
    
    if success:
        print(f"✅ 重启时间已设置为: {hour:02d}:{minute:02d}")
    else:
        print(f"❌ 设置重启时间失败: {hour:02d}:{minute:02d}")
    
    return success


def get_restart_time():
    """
    获取当前设置的重启时间（便捷函数）
    
    Returns:
        str: 当前设置的重启时间，格式为 "HH:MM"
    
    Example:
        >>> get_restart_time()
        '07:50'
    """
    main_window = get_main_window()
    if not main_window:
        print("❌ 未找到运行中的程序实例，请确保程序已启动并登录")
        return None
    
    if not hasattr(main_window, '_daily_restart_scheduler'):
        print("❌ 未找到每日重启调度器，请确保程序已启动自动化任务")
        return None
    
    scheduler = main_window._daily_restart_scheduler
    time_str = scheduler.get_restart_time_str()
    print(f"ℹ️ 当前重启时间: {time_str}")
    return time_str


def show_restart_info():
    """
    显示重启时间相关信息（便捷函数）
    
    Example:
        >>> show_restart_info()
        ℹ️ 当前重启时间: 07:50
        ℹ️ 调度器状态: 运行中
    """
    main_window = get_main_window()
    if not main_window:
        print("❌ 未找到运行中的程序实例，请确保程序已启动并登录")
        return
    
    if not hasattr(main_window, '_daily_restart_scheduler'):
        print("❌ 未找到每日重启调度器，请确保程序已启动自动化任务")
        return
    
    scheduler = main_window._daily_restart_scheduler
    time_str = scheduler.get_restart_time_str()
    hour, minute = scheduler.get_restart_time()
    
    print("=" * 50)
    print("📋 每日重启时间信息")
    print("=" * 50)
    print(f"⏰ 当前重启时间: {time_str} ({hour:02d}:{minute:02d})")
    print(f"🔄 定时器状态: {'运行中' if scheduler._current_timer and scheduler._current_timer.is_alive() else '已停止'}")
    print(f"🧪 测试模式: {'是' if scheduler._test_mode else '否'}")
    print(f"⏱️  检查间隔: {scheduler._check_interval}秒")
    print(f"📅 最后执行日期: {scheduler._last_execution_date if scheduler._last_execution_date else '未执行'}")
    print(f"🔄 是否正在执行: {'是' if scheduler._is_executing else '否'}")
    print("=" * 50)


def quick_set(hour, minute):
    """
    快速设置重启时间（别名，更简洁）
    
    Args:
        hour: 小时（0-23）
        minute: 分钟（0-59）
    
    Example:
        >>> quick_set(8, 30)  # 设置为8:30
        ✅ 重启时间已设置为: 08:30
        True
    """
    return set_restart_time(hour, minute)


def quick_get():
    """
    快速获取重启时间（别名，更简洁）
    
    Example:
        >>> quick_get()
        ℹ️ 当前重启时间: 07:50
        '07:50'
    """
    return get_restart_time()


# 常用时间预设
def set_morning_750():
    """设置为早上7:50（默认时间）"""
    return set_restart_time(7, 50)


def set_morning_800():
    """设置为早上8:00"""
    return set_restart_time(8, 0)


def set_morning_830():
    """设置为早上8:30"""
    return set_restart_time(8, 30)


def set_morning_900():
    """设置为早上9:00"""
    return set_restart_time(9, 0)


def set_night_2330():
    """设置为晚上23:30"""
    return set_restart_time(23, 30)


# 导出常用函数
__all__ = [
    'set_restart_time',
    'get_restart_time',
    'show_restart_info',
    'quick_set',
    'quick_get',
    'set_morning_750',
    'set_morning_800',
    'set_morning_830',
    'set_morning_900',
    'set_night_2330',
]


if __name__ == "__main__":
    print("=" * 60)
    print("📋 每日重启时间设置工具")
    print("=" * 60)
    print()
    print("使用方法：")
    print("  1. 在Python交互环境中导入：")
    print("     >>> from xy_client.services.Lucky5.utils.set_restart_time import *")
    print()
    print("  2. 设置重启时间：")
    print("     >>> set_restart_time(8, 30)  # 设置为8:30")
    print("     >>> quick_set(8, 30)         # 快速设置（别名）")
    print()
    print("  3. 查看当前设置：")
    print("     >>> get_restart_time()       # 获取时间字符串")
    print("     >>> quick_get()              # 快速获取（别名）")
    print("     >>> show_restart_info()      # 显示详细信息")
    print()
    print("  4. 使用预设时间：")
    print("     >>> set_morning_800()        # 设置为8:00")
    print("     >>> set_morning_830()        # 设置为8:30")
    print("     >>> set_morning_900()        # 设置为9:00")
    print()
    print("=" * 60)
    
    # 尝试显示当前信息
    try:
        show_restart_info()
    except Exception as e:
        print(f"⚠️ 无法显示当前信息: {e}")
        print("   请确保程序已启动并登录")

