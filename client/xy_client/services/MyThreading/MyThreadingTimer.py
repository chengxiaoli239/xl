import threading


def myTimer(inc, timer, args):
    """
    启动定时器（统一使用参数元组方式）
    
    Args:
        inc: 间隔时间（秒）
        timer: 定时器函数
        args: 函数参数（元组）
    
    Returns:
        threading.Timer: 定时器实例，用于后续管理
    """
    try:
        # 统一使用参数元组方式，不再做兼容判断
        t = threading.Timer(inc, timer, args)
        t.daemon = True
        t.start()
        return t
    except Exception as e:
        print(f"❌ [MyThreadingTimer] 定时器启动失败: {e}")
        import traceback
        traceback.print_exc()
        return None
