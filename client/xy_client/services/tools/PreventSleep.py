import ctypes

class PreventSleep:
    ES_CONTINUOUS = 0x80000000
    ES_SYSTEM_REQUIRED = 0x00000001

    def __enter__(self):
        ctypes.windll.kernel32.SetThreadExecutionState(
            self.ES_CONTINUOUS | self.ES_SYSTEM_REQUIRED)

    def __exit__(self, exc_type, exc_value, traceback):
        ctypes.windll.kernel32.SetThreadExecutionState(self.ES_CONTINUOUS)

with PreventSleep():
    # 在这里放置您的长时间运行的代码
    pass
