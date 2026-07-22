import json
import os
import platform
import random
import subprocess
import sys
import threading
import time
import traceback
from threading import Thread
import psutil
import urllib3

# 第三方库导入
import requests
from PyQt5 import QtGui
from PyQt5 import QtWidgets
from PyQt5.QtWidgets import QApplication
from PyQt5.QtWidgets import QMessageBox
from PyQt5.QtWidgets import QTableWidgetItem
from selenium.webdriver.common.by import By
from selenium import webdriver
from urllib3.util.retry import Retry
from requests.adapters import HTTPAdapter

# 路径配置 - 确保在导入其他模块前设置路径
try:
    current_dir = os.path.dirname(os.path.abspath(__file__))
    parent_dir = os.path.dirname(current_dir)
    if parent_dir not in sys.path:
        sys.path.insert(0, parent_dir)
    print(f"路径配置成功: {parent_dir}")
except Exception as e:
    print(f"路径配置警告: {e}")
    # 备用路径配置
    try:
        import os
        current_dir = os.getcwd()
        if current_dir not in sys.path:
            sys.path.insert(0, current_dir)
        print(f"✅ 备用路径配置成功: {current_dir}")
    except Exception as e2:
        print(f"❌ 备用路径配置也失败: {e2}")

# 全局登录状态标志 - 控制是否允许获取下注任务
GLOBAL_LOGIN_STATUS = False

# 全局登录锁定标志 - 防止登录过程中执行其他任务
GLOBAL_LOGIN_LOCK = False

# 防卡住配置
ENABLE_DETAILED_LOGS = False  # 关闭详细日志，防止日志过多导致卡住
MAX_CHROME_PROCESSES = 15      # 最大Chrome进程数（考虑用户正常使用）
MAX_MEMORY_MB = 1024           # 最大内存使用(MB)（适当提高）
HANG_DETECTION_SECONDS = 60   # 卡住检测时间(秒)

# 日志开关配置 - 可以控制不同业务的日志输出
LOG_CONFIG = {
    'bet_tasks': True,        # 下注任务日志
    'lottery_data': True,     # 开奖数据日志
    'login': False,           # 登录相关日志
    'popup': False,           # 弹框处理日志
    'balance': False,         # 余额同步日志
    'refresh': False,         # 页面刷新日志
    'health_check': False,    # 健康检查日志
    'error_recovery': False,  # 错误恢复日志
    'system': False,          # 系统日志
    'debug': False            # 调试日志
}

# 日志输出限制
LOG_OUTPUT_COUNT = 0
LOG_OUTPUT_LIMIT = 1000  # 每小时最多输出1000条日志
LOG_RESET_TIME = time.time()

def log_print(category, message, force=False):
    """
    统一的日志输出函数
    category: 日志类别
    message: 日志消息
    force: 是否强制输出（忽略开关）
    """
    global LOG_OUTPUT_COUNT, LOG_RESET_TIME
    
    # 每小时重置日志计数
    current_time = time.time()
    if current_time - LOG_RESET_TIME > 3600:  # 1小时
        LOG_OUTPUT_COUNT = 0
        LOG_RESET_TIME = current_time
    
    # 检查日志输出限制
    if LOG_OUTPUT_COUNT >= LOG_OUTPUT_LIMIT and not force:
        return
    
    if force or LOG_CONFIG.get(category, False):
        print(message)
        sys.stdout.flush()  # 强制刷新输出缓冲区
        LOG_OUTPUT_COUNT += 1

# 全局登录重试标志 - 控制登录重试逻辑
GLOBAL_LOGIN_RETRY_COUNT = 0
GLOBAL_LOGIN_MAX_RETRY = 3

# 最后登录时间记录
LAST_LOGIN_TIME = 0

# 日志总开关 - 控制是否显示详细日志
ENABLE_DETAILED_LOGS = True

# 导入项目模块 - 使用try-except确保导入失败时有备用方案
try:
    from xy_client.services.Lucky5 import Lucky
    print("✅ Lucky5模块导入成功")
except Exception as e:
    print(f"⚠️ Lucky5模块导入失败: {e}")
    Lucky = None

try:
    from xy_client.services.User import User
    print("✅ User模块导入成功")
except Exception as e:
    print(f"⚠️ User模块导入失败: {e}")
    User = None

try:
    from xy_client.services.systems_users.SystemsUsers import pushErrorLog
    print("✅ SystemsUsers模块导入成功")
except Exception as e:
    print(f"⚠️ SystemsUsers模块导入失败: {e}")
    def pushErrorLog(*args): pass

try:
    from xy_client.services.tools.Configs import Configs
    print("✅ Configs模块导入成功")
except Exception as e:
    print(f"⚠️ Configs模块导入失败: {e}")
    Configs = None

try:
    from xy_client.services.tools.GlobalSession import GlobalSession
    from xy_client.services.tools.ConnectionPreheater import preheat_robot_connections
    from xy_client.services.tools.ConnectionStabilityManager import get_connection_manager, health_check
    from xy_client.services.tools.BrowserWindowManager import get_account_browser_manager, cleanup_account_browser
    from xy_client.services.tools.SafeBrowserProcessManager import get_safe_process_manager, cleanup_safe_process_manager
    from xy_client.services.tools.BrowserPortManager import get_port_manager, cleanup_port_manager
    from xy_client.services.tools.ClientFriendlyAccountManager import get_account_manager, check_account_registration, unregister_current_account
    print("✅ GlobalSession模块导入成功")
except Exception as e:
    print(f"⚠️ GlobalSession模块导入失败: {e}")
    GlobalSession = None
    preheat_robot_connections = None
    get_connection_manager = None
    health_check = None
    get_account_browser_manager = None
    cleanup_account_browser = None
    get_port_manager = None
    cleanup_port_manager = None
    get_process_manager = None
    check_account_registration = None
    unregister_current_account = None

try:
    from xy_client.services.tools.TaskManager import TaskManager
    print("✅ TaskManager模块导入成功")
except Exception as e:
    print(f"⚠️ TaskManager模块导入失败: {e}")
    TaskManager = None

try:
    from xy_client.services.ui.LuckyClientOppSite import Ui_MainWindow
    print("✅ UI模块导入成功")
except Exception as e:
    print(f"⚠️ UI模块导入失败: {e}")
    Ui_MainWindow = None

try:
    from xy_client.models import users
    print("✅ users模型导入成功")
except Exception as e:
    print(f"⚠️ users模型导入失败: {e}")
    users = None

try:
    from xy_client.services.systems_users import SystemsUsers
    print("✅ SystemsUsers类导入成功")
except Exception as e:
    print(f"⚠️ SystemsUsers类导入失败: {e}")
    SystemsUsers = None

try:
    from xy_client.services.systems_users import AgentUser
    print("✅ AgentUser模块导入成功")
except Exception as e:
    print(f"⚠️ AgentUser模块导入失败: {e}")
    AgentUser = None

try:
    from xy_client.services.tools.PushCache import PushCache
    print("✅ PushCache模块导入成功")
except Exception as e:
    print(f"⚠️ PushCache模块导入失败: {e}")
    PushCache = None

# 导入新的日志管理模块
try:
    from xy_client.services.tools.LogManager import log_business, log_key_info, log_system, set_detailed_logs
    print("✅ 日志管理模块导入成功")
except Exception as e:
    print(f"⚠️ 日志管理模块导入失败: {e}")
    # 创建简单的备用日志函数
    def log_business(*args): pass
    def log_key_info(*args): pass
    def log_system(*args): pass
    def set_detailed_logs(*args): pass
    print("✅ 使用备用日志函数")

# 导入输入阻塞预防模块
try:
    from xy_client.services.tools.InputBlockingPrevention import activate_input_prevention, check_blocking_status, emergency_recovery
    print("✅ 输入阻塞预防模块导入成功")
except Exception as e:
    print(f"⚠️ 输入阻塞预防模块导入失败: {e}")
    # 创建简单的备用函数
    def activate_input_prevention(): pass
    def check_blocking_status(): pass
    def emergency_recovery(): pass
    print("✅ 使用备用输入阻塞预防函数")

# 设置日志开关
try:
    set_detailed_logs(ENABLE_DETAILED_LOGS)
    print("✅ 日志开关设置成功")
except Exception as e:
    print(f"⚠️ 日志开关设置失败: {e}")

# 配置初始化 - 使用try-except确保配置失败时有默认值
try:
    if GlobalSession:
        globalSession = GlobalSession().get_session()
        print("✅ GlobalSession初始化成功")
        
        # 后台预热机器人连接（不阻塞启动）
        if preheat_robot_connections:
            def background_preheat():
                try:
                    preheat_robot_connections()
                except Exception as e:
                    print(f"⚠️ 连接预热失败: {e}")
                    # 记录详细错误信息，但不让程序崩溃
                    import traceback
                    print(f"预热连接详细错误: {traceback.format_exc()}")
            
            import threading
            threading.Thread(target=background_preheat, daemon=True).start()
        
        # 后台连接健康检查（不阻塞启动）
        if health_check:
            def background_health_check():
                try:
                    print("🔍 执行连接健康检查...")
                    if health_check():
                        print("✅ 连接健康检查通过")
                    else:
                        print("⚠️ 连接健康检查失败，但继续运行")
                except Exception as e:
                    print(f"⚠️ 连接健康检查异常: {e}")
            
            import threading
            threading.Thread(target=background_health_check, daemon=True).start()
        
        # 初始化连接稳定性管理器
        if get_connection_manager:
            try:
                connection_manager = get_connection_manager()
                print("✅ 连接稳定性管理器初始化成功")
            except Exception as e:
                print(f"⚠️ 连接稳定性管理器初始化失败: {e}")
    else:
        globalSession = None
        print("⚠️ GlobalSession不可用，使用None")
except Exception as e:
    print(f"⚠️ GlobalSession初始化失败: {e}")
    globalSession = None

import urllib3
urllib3.disable_warnings()
# 禁用连接池满警告（已配置pool_maxsize=200，警告是误报）
try:
    urllib3.disable_warnings(urllib3.exceptions.PoolWarning)
except AttributeError:
    # 如果PoolWarning不存在，使用通用方式禁用所有警告
    pass

try:
    if Configs:
        config = Configs()
        robot_domain = config.get_config('robot_domain')
        lottery_type = int(config.get_config('lottery_type'))
        access_token = config.get_config('access_token')
        driver_name = config.get_config('driver_name')
        is_test = 0 if config.get_config('is_test') is None else int(config.get_config('is_test'))
        print("✅ 配置初始化成功")
    else:
        # 使用默认配置
        config = None
        robot_domain = 'default'
        lottery_type = 8
        access_token = 'default'
        driver_name = 'chrome'
        is_test = 0
        print("⚠️ 使用默认配置")
except Exception as e:
    print(f"⚠️ 配置初始化失败: {e}")
    # 使用默认配置
    config = None
    robot_domain = 'default'
    lottery_type = 8
    access_token = 'default'
    driver_name = 'chrome'
    is_test = 0

exec_count = 0
# 全局变量或标志，用于控制线程的执行
is_running = True

# 创建全局推送缓存实例
try:
    if PushCache:
        push_cache = PushCache()
        print("✅ PushCache初始化成功")
    else:
        push_cache = None
        print("⚠️ PushCache不可用，使用None")
except Exception as e:
    print(f"⚠️ PushCache初始化失败: {e}")
    push_cache = None


class ConnectionPools:
    def __init__(self):
        self._pools = {}
        self._default_pool_config = {
            'pool_connections': 100,
            'pool_maxsize': 200,
            'max_retries': 3,
            'pool_block': False
        }
        
        # Chrome调试端口连接池配置
        self._chrome_pool_config = {
            'pool_connections': 50,  # 增加Chrome连接数
            'pool_maxsize': 100,     # 增加Chrome最大连接数
            'max_retries': 3,
            'pool_block': False
        }

    def get_pool(self, task_type):
        if task_type not in self._pools:
            session = requests.Session()
            retry_strategy = Retry(
                total=3,
                backoff_factor=1,
                status_forcelist=[429, 500, 502, 503, 504]
            )

            # 根据任务类型选择不同的连接池配置
            if task_type in ['chrome_debug', 'browser_control']:
                config = self._chrome_pool_config
            else:
                config = self._default_pool_config

            adapter = HTTPAdapter(
                pool_connections=config['pool_connections'],
                pool_maxsize=config['pool_maxsize'],
                max_retries=retry_strategy,
                pool_block=config['pool_block']
            )

            session.mount("http://", adapter)
            session.mount("https://", adapter)
            self._pools[task_type] = session

        return self._pools[task_type]


# 创建全局连接池实例
connection_pools = ConnectionPools()


def set_global_login_status(status):
    """设置全局登录状态"""
    global GLOBAL_LOGIN_STATUS
    GLOBAL_LOGIN_STATUS = status
    status_text = "已登录" if status else "未登录"
    if ENABLE_DETAILED_LOGS:
        print(f"🔐 全局登录状态已更新: {status_text}")


def get_global_login_status():
    """获取全局登录状态"""
    return GLOBAL_LOGIN_STATUS


def set_global_login_lock(locked):
    """设置全局登录锁定状态"""
    global GLOBAL_LOGIN_LOCK
    GLOBAL_LOGIN_LOCK = locked
    lock_text = "已锁定" if locked else "已解锁"
    if ENABLE_DETAILED_LOGS:
        print(f"🔒 全局登录锁定状态已更新: {lock_text}")


def get_global_login_lock():
    """获取全局登录锁定状态"""
    return GLOBAL_LOGIN_LOCK


def should_retry_login():
    """判断是否应该重试登录"""
    global GLOBAL_LOGIN_RETRY_COUNT, GLOBAL_LOGIN_MAX_RETRY
    return GLOBAL_LOGIN_RETRY_COUNT < GLOBAL_LOGIN_MAX_RETRY


def increment_login_retry():
    """增加登录重试次数"""
    global GLOBAL_LOGIN_RETRY_COUNT
    GLOBAL_LOGIN_RETRY_COUNT += 1
    if ENABLE_DETAILED_LOGS:
        print(f"🔄 登录重试次数: {GLOBAL_LOGIN_RETRY_COUNT}/{GLOBAL_LOGIN_MAX_RETRY}")


def reset_login_retry():
    """重置登录重试次数"""
    global GLOBAL_LOGIN_RETRY_COUNT
    GLOBAL_LOGIN_RETRY_COUNT = 0
    if ENABLE_DETAILED_LOGS:
        print("✅ 登录重试次数已重置")


def is_login_time_valid():
    """检查登录时间是否有效（避免频繁登录）"""
    global LAST_LOGIN_TIME
    current_time = time.time()
    # 如果从未登录过（LAST_LOGIN_TIME为0），允许登录
    if LAST_LOGIN_TIME == 0:
        return True
    # 如果距离上次登录不足5分钟，不允许重新登录
    if current_time - LAST_LOGIN_TIME < 300:  # 5分钟
        return False
    return True


def update_last_login_time():
    """更新最后登录时间"""
    global LAST_LOGIN_TIME
    LAST_LOGIN_TIME = time.time()
    if ENABLE_DETAILED_LOGS:
        print(f"⏰ 最后登录时间已更新: {time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(LAST_LOGIN_TIME))}")


def is_scheduled_login_time():
    """检查是否在计划登录时间（07:55-08:00）"""
    current_time = time.localtime()
    hour = current_time.tm_hour
    minute = current_time.tm_min
    
    # 检查是否在07:55-08:00区间
    if hour == 7 and minute >= 55:
        return True
    elif hour == 8 and minute == 0:
        return True
    return False


class MainWindow(QtWidgets.QMainWindow, Ui_MainWindow):
    process_num = 0

    def __init__(self):
        print("🔄 开始初始化MainWindow...")
        super(MainWindow, self).__init__()
        print("✅ MainWindow父类初始化完成")
        
        # 从文件中加载UI定义
        try:
            print("🔄 检查进程是否存在...")
            self.checkProcessIsExist()
            print("✅ 进程检查完成")
            
            print("🔄 初始化UI界面...")
            self.setupUi(self)
            print("✅ UI界面初始化完成")

            self.is_running = is_running
            self.account_status = 1
            self.current_qihao = ''
            self.browser_cookies = None
            self.browser = None  # 用户浏览器
            self.domain = None  # 用户网盘地址
            self.port = random.randint(9000, 9999)  # 主浏览器调试端口
            self._original_port = self.port  # 保存原始端口，确保一致性

            self.chrome_service = None
            self.is_test = is_test
            self.lottery_type = lottery_type
            self.runtime_mode = config.get_runtime_mode() if config else 'background'
            self.is_need_login = 0  # 登录标识
            self.user_info = None
            self.wp_domain = ''
            self.wp_account = ''
            self.wp_password = ''
            
            # 登录状态标志 - 控制是否允许获取下注任务（统一使用is_need_login）
            # 登录并发保护标志，避免一次点击触发多次登录/多开浏览器
            self.is_logging_in = False  # 会员登录标志
            self.is_agent_logging_in = False  # 代理登录标志（独立于会员登录）
            
            # 浏览器窗口管理器（延迟初始化，在access_token设置后）
            self.browser_window_manager = None
            self.port_manager = None

            # 代理
            self.agent_browser = None  # 代理浏览器
            self.agent_port = self.port + 1  # 代理调试端口（比主端口大1）
            self._original_agent_port = self.agent_port  # 保存原始代理端口
            self.agent_session = None  # 代理session对象
            self.agent_cookies = None  # 代理登陆cookie，可直接用于接口访问
            self.agent_user_agent = None  # 浏览器代理
            self.agent_domain = None  # 代理网盘地址
            self.agent_username = None  # 代理网盘账号
            self.agent_password = None  # 代理网盘密码

            self.access_token = access_token
            # print('accountInfo', self.wp_account, self.wp_domain, self.wp_password)
            
            # 初始化浏览器和端口管理器（在access_token设置后）
            if get_account_browser_manager and get_port_manager:
                try:
                    # 使用access_token作为账号ID，确保每个账号独立管理
                    account_id = self.access_token or "default_account"
                    browser_type = self.getPreferredBrowser() or "chrome"
                    
                    # 初始化浏览器窗口管理器
                    self.browser_window_manager = get_account_browser_manager(account_id, browser_type)
                    
                    # 初始化端口管理器
                    self.port_manager = get_port_manager(account_id, browser_type)
                    
                    # 固定端口，避免每次重新分配
                    if self.port_manager:
                        self.port = self.port_manager.debug_port
                        self.agent_port = self.port + 1
                        self._original_port = self.port
                        self._original_agent_port = self.agent_port
                        
                        # 关键修复：将主端口和代理端口都添加到全局端口集合中
                        try:
                            from xy_client.services.tools.BrowserPortManager import _add_port_to_account
                            _add_port_to_account(account_id, self.port)
                            _add_port_to_account(account_id, self.agent_port)
                            print(f"✅ [端口记录] 已记录账户 {account_id} 的端口: 主端口={self.port}, 代理端口={self.agent_port}")
                        except Exception as e:
                            print(f"⚠️ [端口记录] 记录端口异常: {e}")
                        print(f"✅ 端口已固定: 主端口={self.port}, 代理端口={self.agent_port}")
                    
                    print(f"✅ 浏览器管理器初始化成功: 账号={account_id}, 浏览器={browser_type}, 端口={self.port_manager.debug_port if self.port_manager else 'N/A'}")
                except Exception as e:
                    print(f"⚠️ 浏览器管理器初始化失败: {e}")
                    self.browser_window_manager = None
                    self.port_manager = None

            # 重登按钮 - 重启浏览器并重新登录
            def safe_restart_and_login():
                try:
                    print("🔄 [重登按钮] 用户点击重登，开始执行重启和重新登录...")
                    # 使用每日重启调度器的功能
                    if hasattr(self, '_daily_restart_scheduler'):
                        # 重置执行日期，允许立即执行
                        self._daily_restart_scheduler._last_execution_date = None
                        result = self._daily_restart_scheduler.execute_daily_restart()
                        if result:
                            print("✅ [重登按钮] 重启和重新登录成功")
                        else:
                            print("❌ [重登按钮] 重启和重新登录失败")
                    else:
                        # 如果调度器不存在，直接执行重启逻辑
                        print("⚠️ [重登按钮] 每日重启调度器不存在，使用备用方案...")
                        self._manual_restart_and_login()
                except Exception as e:
                    print(f"❌ [重登按钮] 重登异常: {e}")
                    traceback.print_exc()
            
            self.sync_balance_btn.clicked.connect(safe_restart_and_login)

            # 开始游戏，登陆结束自动同步余额，此处暂停
            # self.start_play_btn.clicked.connect(SystemsUsers.heart_beat_sync_balance(self.access_token))

            # 初始化UI工具 - 添加异常处理
            try:
                self.initUiTools()  # 模拟
            except Exception as e:
                print(f"❌ 初始化UI工具异常: {e}")
                traceback.print_exc()

            # table
            # self.table = QTableWidget()
            self.table = self.tableWidget
            # self.table.setColumnCount(18)
            self.table.horizontalHeader().setStretchLastSection(True)

            # 设置列宽度
            self.setWindowTableW()

            # 添加UI事件异常处理
            def safe_doLogin():
                try:
                    self.doLogin()
                except Exception as e:
                    print(f"❌ 登录按钮点击异常: {e}")
                    traceback.print_exc()
            
            def safe_doAgentLogin():
                try:
                    self.doAgentLogin()
                except Exception as e:
                    print(f"❌ 代理登录按钮点击异常: {e}")
                    traceback.print_exc()
            
            self.UserLoginBtn.clicked.connect(safe_doLogin)  # 登陆按钮
            self.AgentLoginBtn.clicked.connect(safe_doAgentLogin)  # 代理登陆按钮
            
            # 初始化浏览器选择控件 - 添加异常处理
            try:
                self.initBrowserSelection()
            except Exception as e:
                print(f"❌ 初始化浏览器选择控件异常: {e}")
                traceback.print_exc()
            
            # 启动后立即同步用户信息 - 添加异常处理
            try:
                self.syncUserInfoOnStartup()
            except Exception as e:
                print(f"❌ 同步用户信息异常: {e}")
                traceback.print_exc()
            
            # 添加鼠标点击事件异常处理
            def mousePressEvent(event):
                try:
                    super(MainWindow, self).mousePressEvent(event)
                except Exception as e:
                    print(f"❌ 鼠标点击事件异常: {e}")
                    traceback.print_exc()
            
            # 重写鼠标点击事件
            self.mousePressEvent = mousePressEvent

            self.setIcon()  # 引入logo

            # 点击查看按钮
            # self.SubViewWindow = SubViewWindow(parent=self)
            # 登陆成功加载主窗口
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print('客户端开启异常：', e.args)
                traceback.print_exc()
            SystemsUsers.pushErrorLog('开启客户端异常：' + str(self.current_qihao), self.access_token, lottery_type,
                                      e.args)

    # ===== 登录去抖与并发保护 =====
    def begin_login_guard(self, login_type='user') -> bool:
        """开始登录前的并发保护。返回True表示允许执行登录。
        
        重要：代理登录和会员登录完全独立，互不影响：
        - 会员登录：使用全局锁和 is_logging_in，用于控制下注任务等
        - 代理登录：使用独立的 is_agent_logging_in，不影响会员登录
        """
        try:
            if login_type == 'user':
                # 会员登录：只检查会员登录相关的锁
                if get_global_login_lock() or self.is_logging_in:
                    if ENABLE_DETAILED_LOGS:
                        print('🔒 [会员登录] 检测到会员登录流程正在进行中，忽略重复触发')
                    return False
                # 设置全局锁和本地标志（用于控制下注任务等）
                set_global_login_lock(True)
                self.is_logging_in = True
                # 只禁用会员登录按钮
                if hasattr(self, 'UserLoginBtn'):
                    self.UserLoginBtn.setEnabled(False)
                    self.UserLoginBtn.setText("登录中...")
                    
            elif login_type == 'agent':
                # 代理登录：使用完全独立的标志，不检查也不设置全局锁
                if not hasattr(self, 'is_agent_logging_in'):
                    self.is_agent_logging_in = False
                if self.is_agent_logging_in:
                    if ENABLE_DETAILED_LOGS:
                        print('🔒 [代理登录] 检测到代理登录流程正在进行中，忽略重复触发')
                    return False
                # 只设置代理登录标志，不影响会员登录和全局锁
                self.is_agent_logging_in = True
                # 只禁用代理登录按钮
                if hasattr(self, 'AgentLoginBtn'):
                    self.AgentLoginBtn.setEnabled(False)
                    self.AgentLoginBtn.setText("代理登录中...")
            
            return True
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f'⚠️ begin_login_guard异常: {e}')
            return True

    def finish_login_guard(self, login_type='user'):
        """登录完成后的解锁与按钮恢复。
        
        重要：代理登录和会员登录完全独立，互不影响
        """
        try:
            if login_type == 'user':
                # 会员登录：解锁全局锁和本地标志
                self.is_logging_in = False
                set_global_login_lock(False)
                # 恢复会员登录按钮
                if hasattr(self, 'UserLoginBtn'):
                    self.UserLoginBtn.setEnabled(True)
                    self.UserLoginBtn.setText("登录")
                    
            elif login_type == 'agent':
                # 代理登录：只解锁代理登录标志，不影响会员登录和全局锁
                self.is_agent_logging_in = False
                # 恢复代理登录按钮
                if hasattr(self, 'AgentLoginBtn'):
                    self.AgentLoginBtn.setEnabled(True)
                    self.AgentLoginBtn.setText("代理登录")
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f'⚠️ finish_login_guard异常: {e}')

    def closeEvent(self, event):
        """优化后的关闭事件 - 在主线程中快速清理，避免跨线程问题"""
        print("🚪 [closeEvent] 开始关闭程序...")
        import traceback
        print("🔍 [closeEvent] 调用堆栈:")
        traceback.print_stack()
        
        try:
            if ENABLE_DETAILED_LOGS:
                print('🚪 开始关闭程序...')
            
            # 立即关闭用户界面，提升用户体验
            self.hide()  # 先隐藏界面
            
            # 在主线程中快速清理资源
            self._quick_cleanup_in_main_thread()
            
            # 注销当前账号
            if unregister_current_account and hasattr(self, 'account_id'):
                try:
                    unregister_current_account()
                    print(f"✅ 账号 {self.account_id} 已注销")
                except Exception as e:
                    print(f"⚠️ 账号注销失败: {e}")
            
            # 使用 QTimer.singleShot 延迟强制退出，确保清理完成
            from PyQt5.QtCore import QTimer
            QTimer.singleShot(1000, self._force_exit)
            
            # 接受关闭事件
            event.accept()
            
        except Exception as e:
            print(f'❌ [closeEvent] 关闭页面异常: {e}')
            import traceback
            traceback.print_exc()
            # 即使异常也要确保界面关闭
            event.accept()
            # 异常情况下立即强制退出
            self._force_exit()
    
    # 删除不再需要的后台清理方法
    
    def _quick_stop_tasks(self):
        """快速停止任务（不等待）"""
        try:
            if ENABLE_DETAILED_LOGS:
                print('🔄 快速停止任务...')
            
            # 设置停止标志
            self.is_running = False
            
            # 快速停止任务管理器（如果存在）
            if hasattr(self, 'task_manager') and self.task_manager:
                try:
                    if ENABLE_DETAILED_LOGS:
                        print('🔄 快速停止任务管理器...')
                    # 直接强制停止，不等待优雅关闭
                    self.task_manager.stop_all_tasks(timeout=0.5)  # 减少超时时间
                    if ENABLE_DETAILED_LOGS:
                        print('✅ 任务管理器已快速停止')
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f'⚠️ 快速停止任务管理器异常: {e}')
                    # 如果正常停止失败，尝试强制停止
                    try:
                        if hasattr(self.task_manager, 'force_stop_all_tasks'):
                            self.task_manager.force_stop_all_tasks()
                            if ENABLE_DETAILED_LOGS:
                                print('✅ 任务管理器已强制停止')
                    except Exception as e2:
                        if ENABLE_DETAILED_LOGS:
                            print(f'⚠️ 强制停止任务管理器也失败: {e2}')
            
            if ENABLE_DETAILED_LOGS:
                print('✅ 任务快速停止完成')
                
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f'⚠️ 快速停止任务异常: {e}')
    
    def _quick_cleanup_in_main_thread(self):
        """在主线程中快速清理资源，避免跨线程问题"""
        try:
            if ENABLE_DETAILED_LOGS:
                print('🔄 在主线程中快速清理资源...')
            
            # 1. 设置停止标志
            self.is_running = False
            
            # 2. 快速停止任务管理器（如果存在）
            if hasattr(self, 'task_manager') and self.task_manager:
                try:
                    if ENABLE_DETAILED_LOGS:
                        print('🔄 快速停止任务管理器...')
                    # 直接强制停止，不等待优雅关闭
                    self.task_manager.stop_all_tasks(timeout=0.5)  # 减少超时时间
                    if ENABLE_DETAILED_LOGS:
                        print('✅ 任务管理器已快速停止')
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f'⚠️ 快速停止任务管理器异常: {e}')
                    # 如果正常停止失败，尝试强制停止
                    try:
                        if hasattr(self.task_manager, 'force_stop_all_tasks'):
                            self.task_manager.force_stop_all_tasks()
                            if ENABLE_DETAILED_LOGS:
                                print('✅ 任务管理器已强制停止')
                    except Exception as e2:
                        if ENABLE_DETAILED_LOGS:
                            print(f'⚠️ 强制停止任务管理器也失败: {e2}')
            
            # 3. 快速关闭浏览器
            self._quick_close_browsers()
            
            # 4. 清理其他资源
            self._cleanup_other_resources()
            
            if ENABLE_DETAILED_LOGS:
                print('✅ 主线程清理完成')
            
            # 5. 使用 QTimer.singleShot 延迟退出，确保界面完全关闭
            # 增加延迟时间，给程序更多时间完成清理
            from PyQt5.QtCore import QTimer
            QTimer.singleShot(1000, self._force_exit)
            
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f'❌ 主线程清理异常: {e}')
            # 即使异常也要强制退出
            self._force_exit()
    
    def _force_exit(self):
        """强制退出程序"""
        try:
            if ENABLE_DETAILED_LOGS:
                print('🔄 强制退出程序...')
            
            # 1. 强制终止所有Python线程
            self._force_kill_all_threads()
            
            # 2. 强制终止所有子进程
            self._force_kill_all_child_processes()
            
            # 3. 强制退出
            if ENABLE_DETAILED_LOGS:
                print('🔄 执行最终强制退出...')
            import os
            os._exit(0)
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f'❌ 强制退出异常: {e}')
            # 最后的退出手段
            try:
                import sys
                sys.exit(0)
            except:
                # 最后的最后手段
                os._exit(0)
    
    def _force_kill_all_threads(self):
        """强制终止所有Python线程"""
        try:
            if ENABLE_DETAILED_LOGS:
                print('🔄 强制终止所有Python线程...')
            
            import threading
            import _thread
            
            # 获取所有活跃线程
            active_threads = threading.enumerate()
            if ENABLE_DETAILED_LOGS:
                print(f"🔍 找到 {len(active_threads)} 个活跃线程")
            
            # 终止非主线程
            for thread in active_threads:
                if thread != threading.main_thread():
                    try:
                        if ENABLE_DETAILED_LOGS:
                            print(f"🔄 终止线程: {thread.name} (ID: {thread.ident})")
                        # 尝试优雅地终止线程
                        if hasattr(thread, '_stop'):
                            thread._stop()
                    except Exception as e:
                        if ENABLE_DETAILED_LOGS:
                            print(f"⚠️ 终止线程异常: {e}")
            
            if ENABLE_DETAILED_LOGS:
                print('✅ 线程终止完成')
                
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f"⚠️ 强制终止线程异常: {e}")
    
    def _force_kill_all_child_processes(self):
        """强制终止所有子进程"""
        try:
            if ENABLE_DETAILED_LOGS:
                print('🔄 强制终止所有子进程...')
            
            import psutil
            import os
            
            current_pid = os.getpid()
            current_process = psutil.Process(current_pid)
            
            # 查找所有子进程
            children = current_process.children(recursive=True)
            if ENABLE_DETAILED_LOGS:
                print(f"🔍 找到 {len(children)} 个子进程")
            
            # 强制终止所有子进程
            for child in children:
                try:
                    if ENABLE_DETAILED_LOGS:
                        print(f"🔄 强制终止子进程: {child.name()} (PID: {child.pid})")
                    child.kill()  # 使用kill()而不是terminate()
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f"⚠️ 终止子进程异常: {e}")
            
            if ENABLE_DETAILED_LOGS:
                print('✅ 子进程终止完成')
                
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f"⚠️ 强制终止子进程异常: {e}")
    
    def _quick_close_browsers(self):
        """快速关闭浏览器（不等待）"""
        try:
            if ENABLE_DETAILED_LOGS:
                print('🔄 快速关闭浏览器...')
            
            # 优先使用浏览器窗口管理器清理
            if self.browser_window_manager:
                try:
                    if ENABLE_DETAILED_LOGS:
                        print('🔄 使用浏览器窗口管理器清理...')
                    self.browser_window_manager.cleanup_browser_resources()
                    if ENABLE_DETAILED_LOGS:
                        print('✅ 浏览器窗口管理器清理完成')
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f'⚠️ 浏览器窗口管理器清理失败: {e}')
            
            # 清理账号浏览器资源
            if cleanup_account_browser:
                try:
                    account_id = self.access_token or "default_account"
                    cleanup_account_browser(account_id)
                    if ENABLE_DETAILED_LOGS:
                        print(f'✅ 账号 {account_id} 浏览器资源清理完成')
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f'⚠️ 账号浏览器资源清理失败: {e}')
            
            # 清理端口管理器
            if cleanup_port_manager:
                try:
                    account_id = self.access_token or "default_account"
                    cleanup_port_manager(account_id)
                    if ENABLE_DETAILED_LOGS:
                        print(f'✅ 账号 {account_id} 端口管理器清理完成')
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f'⚠️ 端口管理器清理失败: {e}')
            
            # 关闭主浏览器
            if hasattr(self, 'driver') and self.driver:
                try:
                    if ENABLE_DETAILED_LOGS:
                        print('🔄 关闭主浏览器...')
                    # 使用quit()但不等待
                    self.driver.quit()
                    if ENABLE_DETAILED_LOGS:
                        print('✅ 主浏览器已关闭')
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f'⚠️ 关闭主浏览器异常: {e}')
                finally:
                    self.driver = None
            
            # 关闭代理浏览器
            if hasattr(self, 'agent_browser') and self.agent_browser:
                try:
                    if ENABLE_DETAILED_LOGS:
                        print('🔄 关闭代理浏览器...')
                    self.agent_browser.quit()
                    if ENABLE_DETAILED_LOGS:
                        print('✅ 代理浏览器已关闭')
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f'⚠️ 关闭代理浏览器异常: {e}')
                finally:
                    self.agent_browser = None
            
            # 关闭其他浏览器
            if hasattr(self, 'browser') and self.browser:
                try:
                    if ENABLE_DETAILED_LOGS:
                        print('🔄 关闭用户浏览器...')
                    self.browser.quit()
                    if ENABLE_DETAILED_LOGS:
                        print('✅ 用户浏览器已关闭')
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f'⚠️ 关闭用户浏览器异常: {e}')
                finally:
                    self.browser = None
            
            # 清理cookies
            self.browser_cookies = None
            self.agent_cookies = None
            
            # 重要：强制终止本程序启动的浏览器进程，确保完全关闭 - 暂时跳过
            # self._force_kill_browser_processes()
            
            if ENABLE_DETAILED_LOGS:
                print('✅ 所有浏览器已快速关闭')
                
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f'⚠️ 快速关闭浏览器异常: {e}')
    
    def _cleanup_other_resources(self):
        """清理其他资源"""
        try:
            if ENABLE_DETAILED_LOGS:
                print('🔄 清理其他资源...')
            
            # 清理连接池
            if hasattr(self, 'connection_pools'):
                try:
                    self.connection_pools._pools.clear()
                    if ENABLE_DETAILED_LOGS:
                        print('✅ 连接池已清理')
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f'⚠️ 清理连接池异常: {e}')
            
            # 清理推送缓存
            if hasattr(self, 'push_cache'):
                try:
                    # 如果有清理方法就调用
                    if hasattr(self.push_cache, 'clear'):
                        self.push_cache.clear()
                    if ENABLE_DETAILED_LOGS:
                        print('✅ 推送缓存已清理')
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f'⚠️ 清理推送缓存异常: {e}')
            
            # 强制垃圾回收
            try:
                import gc
                gc.collect()
                if ENABLE_DETAILED_LOGS:
                    print('✅ 内存已清理')
            except Exception as e:
                if ENABLE_DETAILED_LOGS:
                    print(f'⚠️ 内存清理异常: {e}')
            
            if ENABLE_DETAILED_LOGS:
                print('✅ 其他资源清理完成')
                
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f'⚠️ 清理其他资源异常: {e}')

    def stop_thread(self):
        # 设置标志为 False，停止线程的执行
        # global is_running
        self.is_running = False

    # 会员账号登陆
    def doLogin(self):
        print('doLogin user_info:', self.user_info)

        if self.getRuntimeMode() == 'background':
            if self.restore_background_session(show_message=True):
                self.updateLoginStatus(True)
            return
        
        # 如果用户信息不存在，尝试从界面或API获取
        if not hasattr(self, 'user_info') or not self.user_info:
            print("⚠️ 用户信息不存在，尝试获取...")
            try:
                # 从界面获取用户信息
                from xy_client.services.systems_users.common import getAccountByToken
                account_info = getAccountByToken(self.access_token)
                if account_info and account_info.get('status') == 200:
                    self.user_info = account_info.get('data', {})
                    print(f"✅ 成功获取用户信息: {self.user_info.get('username', 'N/A')}")
                else:
                    print("❌ 无法从API获取用户信息")
                    # 尝试从界面字段获取
                    try:
                        account = self.username_val.text() if hasattr(self, 'username_val') else ''
                        password = self.pwd_val.text() if hasattr(self, 'pwd_val') else ''
                        ssc_domain = self.domain_val.text() if hasattr(self, 'domain_val') else ''
                        if account and password and ssc_domain:
                            self.user_info = {
                                'account': account,
                                'password': password,
                                'ssc_domain': ssc_domain,
                                'username': account,
                                'access_token': self.access_token
                            }
                            print(f"✅ 从界面获取用户信息: {account}")
                        else:
                            print("❌ 界面字段为空，无法登录")
                            return
                    except Exception as ui_e:
                        print(f"❌ 从界面获取用户信息失败: {ui_e}")
                        return
            except Exception as e:
                print(f"❌ 获取用户信息异常: {e}")
                import traceback
                traceback.print_exc()
                return
        
        # 设置手动登录标志，跳过冷却时间检查
        self._manual_login_triggered = True
        
        # 关键修复：会员登录使用 'user' 类型，与代理登录区分开
        # 并发保护：避免一次点击触发多次
        if not self.begin_login_guard(login_type='user'):
            return
        
        # 异步执行登录，避免阻塞UI线程
        def async_login():
            try:
                # 使用新的登录管理逻辑
                print("🔄 开始执行智能登录管理...")
                result = self.smart_login_management()
                if result:
                    print("✅ 登录管理成功")
                    # 更新登录状态（这会触发自动化任务启动）
                    print("🔄 [doLogin] 准备更新登录状态...")
                    try:
                        self.updateLoginStatus(True)
                    except Exception as status_e:
                        print(f"❌ 更新登录状态异常: {status_e}")
                        import traceback
                        traceback.print_exc()
                else:
                    print("❌ 登录管理失败")
                    # 登录失败，更新状态
                    try:
                        self.updateLoginStatus(False)
                    except Exception as status_e:
                        print(f"❌ 更新登录状态异常: {status_e}")
                        import traceback
                        traceback.print_exc()
            except Exception as e:
                print(f"❌ 登录过程异常: {e}")
                import traceback
                traceback.print_exc()
                # 尝试更新登录状态为失败
                try:
                    self.updateLoginStatus(False)
                except:
                    pass
            finally:
                # 关键修复：会员登录使用 'user' 类型解锁
                # 确保解锁
                try:
                    self.finish_login_guard(login_type='user')
                except Exception as unlock_e:
                    print(f"❌ 解锁异常: {unlock_e}")
                    import traceback
                    traceback.print_exc()
        
        # 在后台线程中执行登录
        thread = Thread(target=async_login, daemon=True)
        thread.start()

    # 代理账号登陆
    def doAgentLogin(self):
        # 关键修复：代理登录使用 'agent' 类型，避免影响会员登录按钮
        if not self.begin_login_guard(login_type='agent'):
            return
        
        # 异步执行代理登录，避免阻塞UI线程
        def async_agent_login():
            try:
                # 获取代理登录信息
                agent_domain = self.agent_domain_val.text().strip()
                account = self.agent_username_val.text().strip()
                agent_password = self.agent_password_val.text().strip()
                
                # 验证必要信息
                if not agent_domain:
                    raise ValueError("代理域名不能为空")
                if not account:
                    raise ValueError("代理账号不能为空")
                if not agent_password:
                    raise ValueError("代理密码不能为空")
                
                print(f'[代理登录] 代理信息：域名={agent_domain}, 账号={account}, 密码长度={len(agent_password)}')

                # 执行代理登录
                self.newAgentThreadLogin(agent_domain, account, agent_password)
                
            except Exception as e:
                print(f'❌ [代理登录] 登录异常: {e}')
                traceback.print_exc()
            finally:
                # 关键修复：使用 'agent' 类型解锁
                self.finish_login_guard(login_type='agent')
        
        # 在后台线程中执行代理登录
        thread = Thread(target=async_agent_login, daemon=True)
        thread.start()

    def print_txt(self, input_msg, msg='', is_append=0):
        # input_msg.setText(msg)
        if is_append == 1:
            input_msg.appendPlainText(msg)
        else:
            input_msg.setPlainText(msg)

    def setIcon(self):
        app = QApplication.instance()
        if app:
            self.setWindowIcon(app.windowIcon())

    def initBrowserSelection(self):
        """初始化浏览器选择控件"""
        try:
            if hasattr(self, 'chrome_radio') and hasattr(self, 'firefox_radio'):
                # 从配置文件读取默认选择，从 [system_configs] 节读取
                preferred = config.get_config('preferred_browser', 'system_configs')
                if preferred and preferred.lower() == 'firefox':
                    self.firefox_radio.setChecked(True)
                    print(f"✅ 初始化浏览器选择为: Firefox")
                else:
                    self.chrome_radio.setChecked(True)
                    print(f"✅ 初始化浏览器选择为: Chrome")
                
                # 连接单选按钮的信号
                self.chrome_radio.toggled.connect(lambda: self.onBrowserSelectionChanged('chrome'))
                self.firefox_radio.toggled.connect(lambda: self.onBrowserSelectionChanged('firefox'))

                if hasattr(self, 'open_browser_checkbox'):
                    self.open_browser_checkbox.setChecked(self.runtime_mode == 'browser')
                    self.open_browser_checkbox.toggled.connect(self.onRuntimeModeChanged)
                
                print(f"✅ 浏览器选择控件初始化完成")
            else:
                print(f"⚠️ 界面上没有找到浏览器选择控件")
        except Exception as e:
            print(f"❌ 初始化浏览器选择控件失败: {e}")

    def onRuntimeModeChanged(self, checked):
        self.runtime_mode = 'browser' if checked else 'background'
        if config:
            config.set_config('runtime_mode', self.runtime_mode, 'system_configs')

    def getRuntimeMode(self):
        if hasattr(self, 'open_browser_checkbox'):
            self.runtime_mode = 'browser' if self.open_browser_checkbox.isChecked() else 'background'
        return self.runtime_mode

    def restore_background_session(self, show_message=False):
        try:
            header_data = SystemsUsers.getHeaderData()
            cookies = header_data.get('cookies', '') if isinstance(header_data, dict) else ''
            if not cookies:
                if show_message:
                    QMessageBox.warning(self, '盘口登录', '没有可用的盘口会话，请勾选“打开浏览器登录”后重试。')
                return False

            self.browser_cookies = cookies
            from xy_client.services.Lucky5.core.platform_api import check_login_status_by_api
            if not check_login_status_by_api(self):
                self.browser_cookies = None
                if show_message:
                    QMessageBox.warning(self, '盘口登录', '盘口会话已失效，请勾选“打开浏览器登录”后重试。')
                return False

            self.is_need_login = 1
            set_global_login_status(True)
            print('✅ 已恢复后台HTTP会话，无需启动浏览器')
            return True
        except Exception as exc:
            print(f'❌ 恢复后台HTTP会话失败: {exc}')
            if show_message:
                QMessageBox.warning(self, '盘口登录', '后台会话恢复失败：' + str(exc))
            return False
    
    def onBrowserSelectionChanged(self, browser_type):
        """浏览器选择改变时的处理"""
        try:
            if hasattr(self, 'chrome_radio') and hasattr(self, 'firefox_radio'):
                # 确保只有一个被选中
                if browser_type == 'chrome' and self.chrome_radio.isChecked():
                    self.firefox_radio.setChecked(False)
                    print(f"✅ 浏览器选择已更改为: Chrome")
                    self.savePreferredBrowser('chrome')
                elif browser_type == 'firefox' and self.firefox_radio.isChecked():
                    self.chrome_radio.setChecked(False)
                    print(f"✅ 浏览器选择已更改为: Firefox")
                    self.savePreferredBrowser('firefox')
        except Exception as e:
            print(f"❌ 处理浏览器选择改变失败: {e}")

    def getPreferredBrowser(self):
        """获取用户首选的浏览器"""
        try:
            # 从界面上的单选按钮获取浏览器选择
            if hasattr(self, 'chrome_radio') and hasattr(self, 'firefox_radio'):
                if self.chrome_radio.isChecked():
                    print(f"✅ 从界面获取到浏览器选择: Chrome")
                    return 'chrome'
                elif self.firefox_radio.isChecked():
                    print(f"✅ 从界面获取到浏览器选择: Firefox")
                    return 'firefox'
                else:
                    print(f"⚠️ 界面上没有选择浏览器，使用默认值: Chrome")
                    return 'chrome'
            else:
                # 如果界面上没有浏览器选择控件，从配置文件读取
                print(f"⚠️ 界面上没有浏览器选择控件，从配置文件读取")
                preferred = config.get_config('preferred_browser', 'system_configs')
                print(f"🔍 从配置文件读取的 preferred_browser: {preferred}")
                
                if preferred and preferred.lower() in ['chrome', 'firefox']:
                    print(f"✅ 使用配置文件中的浏览器选择: {preferred.lower()}")
                    return preferred.lower()
                else:
                    # 默认返回chrome
                    print(f"⚠️ 配置文件中的浏览器选择无效或为空，使用默认值: chrome")
                    return 'chrome'
        except Exception as e:
            print(f"❌ 获取首选浏览器失败: {e}")
            # 默认返回chrome
            return 'chrome'
    
    def savePreferredBrowser(self, browser_type):
        """保存用户选择的浏览器到配置文件"""
        try:
            if browser_type.lower() in ['chrome', 'firefox']:
                # 更新配置文件中的preferred_browser，保存到system_configs节
                print(f"🔍 准备保存浏览器选择: {browser_type.lower()}")
                result = config.set_config('preferred_browser', browser_type.lower(), 'system_configs')
                print(f"🔍 保存结果: {result}")
                
                if result:
                    print(f"✅ 已保存浏览器选择: {browser_type}")
                else:
                    print(f"⚠️ 保存浏览器选择可能失败")
                
                return True
            else:
                print(f"❌ 无效的浏览器类型: {browser_type}")
                return False
        except Exception as e:
            print(f"❌ 保存浏览器选择失败: {e}")
            return False

    def syncUserInfoOnStartup(self):
        """程序启动后立即同步用户信息"""
        try:
            log_key_info("🚀 程序启动，开始同步用户信息...", "user_info")
            
            # 导入同步函数
            from xy_client.services.systems_users.SystemsUsers import syncUserInfoTimer
            
            # 立即执行一次同步
            syncUserInfoTimer(0, self)
            
            log_key_info("✅ 启动时用户信息同步完成", "user_info")
            
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f"❌ 启动时同步用户信息失败: {e}")
                traceback.print_exc()

    def debugLoginStatus(self):
        """调试登录状态"""
        try:
            print("🔍 开始调试登录状态...")
            if not hasattr(self, 'driver') or self.driver is None:
                print("❌ 浏览器驱动未初始化")
                return
            print(f"🔍 浏览器驱动状态: {type(self.driver)}")
            
            # 验证WebDriver连接是否仍然有效
            try:
                current_url = self.driver.current_url
                #print(f"🔍 当前页面URL: {current_url}")
            except Exception as e:
                print(f"❌ WebDriver连接已断开: {e}")
                # 标记WebDriver为无效
                self.driver = None
                return
            try:
                page_title = self.driver.title
                current_time = time.strftime('%H:%M:%S', time.localtime())
                print(f"[{current_time}] 🔍 页面标题: {page_title}")
            except Exception as e:
                print(f"❌ 获取页面标题失败: {e}")
            try:
                from xy_client.services.Lucky5.Lucky import check_login_status
                login_status = check_login_status(self.driver)
                #print(f"🔍 登录状态检查结果: {login_status}")
            except Exception as e:
                print(f"❌ 登录状态检查失败: {e}")
                # 如果检查失败，可能是WebDriver连接问题，尝试重新创建
                try:
                    print(f"🔄 尝试重新创建WebDriver...")
                    new_driver = self.recreate_driver()
                    if new_driver:
                        print(f"✅ WebDriver重新创建成功，重新检查登录状态")
                        # 重新检查登录状态
                        login_status = check_login_status(new_driver)
                        print(f"🔍 重新检查登录状态结果: {login_status}")
                    else:
                        print(f"❌ WebDriver重新创建失败")
                except Exception as recreate_error:
                    print(f"❌ 重新创建WebDriver异常: {recreate_error}")
            try:
                if hasattr(self, 'browser_cookies'):
                    print(f"🔍 浏览器cookies状态: {self.browser_cookies}")
                else:
                    print("❌ 未找到browser_cookies属性")
            except Exception as e:
                print(f"❌ 检查cookies状态失败: {e}")
            try:
                elements_to_check = [
                    ("余额元素", "//*[@id='CreditBalance']"),
                    ("用户信息", "//*[contains(text(),'欢迎') or contains(text(),'Welcome')]"),
                    ("退出按钮", "//*[contains(text(),'退出') or contains(text(),'Logout')]"),
                    ("导航菜单", "//nav | //*[@class*='nav'] | //*[@class*='menu']")
                ]
                for element_name, xpath in elements_to_check:
                    try:
                        elements = self.driver.find_elements(By.XPATH, xpath)
                        if elements:
                            element_text = elements[0].get_attribute('textContent') or elements[0].text
                            current_time = time.strftime('%H:%M:%S', time.localtime())
                            print(f"[{current_time}] ✅ 找到{element_name}: {element_text[:50] if element_text else '无文本'}")
                        else:
                            current_time = time.strftime('%H:%M:%S', time.localtime())
                            print(f"[{current_time}] ❌ 未找到{element_name}")
                    except Exception as e:
                        print(f"❌ 查找{element_name}时发生异常: {e}")
            except Exception as e:
                print(f"❌ 检查页面元素时发生异常: {e}")
            current_time = time.strftime('%H:%M:%S', time.localtime())
            print(f"[{current_time}] 🔍 登录状态调试完成")
        except Exception as e:
            print(f"❌ 调试登录状态时发生异常: {e}")
            traceback.print_exc()

    def updateLoginStatus(self, status):
        """更新登录状态标志"""
        print(f"🔄 [updateLoginStatus] 开始更新登录状态: status={status}, 当前is_need_login={self.is_need_login}")
        
        # 如果当前已经登录成功，不允许重置为失败状态
        if not status and self.is_need_login == 1:
            print(f"🔒 [updateLoginStatus] 拒绝重置登录状态：当前已登录，不允许设置为未登录")
            return
            
        old_status = self.is_need_login
        self.is_need_login = 1 if status else 0  # 统一使用is_need_login
        print(f"📊 [updateLoginStatus] 登录状态变更: {old_status} -> {self.is_need_login}")
        
        set_global_login_status(status)
        
        if status:
            # 登录成功，重置重试次数和解锁
            reset_login_retry()
            set_global_login_lock(False)
            update_last_login_time()
            status_text = "已登录"
            print(f"🔐 [updateLoginStatus] 登录状态已更新: {status_text} (is_need_login = {self.is_need_login})")
            
            # 登录成功后，启动所有自动化任务
            print("🚀 [updateLoginStatus] 准备启动自动化任务...")
            self.start_automation_tasks()
        else:
            # 登录失败，关闭所有浏览器窗口
            print(f"🔐 [updateLoginStatus] 登录失败，准备关闭浏览器...")
            self.closeAllBrowsers()
            status_text = "未登录"
            print(f"🔐 [updateLoginStatus] 登录状态已更新: {status_text} (is_need_login = {self.is_need_login})")
    
    def start_automation_tasks(self):
        """登录成功后启动所有自动化任务"""
        # 检查是否已经启动过，避免重复启动
        if hasattr(self, '_tasks_started') and self._tasks_started:
            if ENABLE_DETAILED_LOGS:
                print("📋 自动化任务已经启动过，跳过重复启动")
            return
        
        try:
            print("✅ 登录成功，开始启动所有自动化任务...")
            
            # 统一导入
            from xy_client.services.MyThreading import MyThreadingTimer
            from xy_client.services.Lucky5 import Lucky

            if self.getRuntimeMode() == 'background':
                self._tasks_started = True
                threading.Thread(target=Lucky.betTasksTimer, args=(self, 1), daemon=True).start()
                print("✅ 后台HTTP下注任务已启动（不使用浏览器或驱动）")
                return
            
            # 启动每日定时重启任务（每天早上7:50关闭浏览器并重新登录）
            try:
                from xy_client.services.Lucky5.utils.daily_restart_scheduler import DailyRestartScheduler
                if not hasattr(self, '_daily_restart_scheduler'):
                    self._daily_restart_scheduler = DailyRestartScheduler(self)
                    # 可以通过环境变量启用测试模式（TEST_DAILY_RESTART=1）
                    test_mode = os.environ.get('TEST_DAILY_RESTART', '0') == '1'
                    
                    # 优先级：环境变量 > 配置文件 > 默认值（07:45早盘重启）
                    target_hour = None
                    target_minute = None
                    
                    # 1. 首先尝试从环境变量读取（用于测试，最高优先级）
                    test_hour_str = os.environ.get('TEST_RESTART_HOUR', '')
                    test_minute_str = os.environ.get('TEST_RESTART_MINUTE', '')
                    if test_hour_str and test_minute_str:
                        try:
                            target_hour = int(test_hour_str)
                            target_minute = int(test_minute_str)
                            print(f"🧪 [DailyRestart] 使用环境变量设置的自定义时间: {target_hour:02d}:{target_minute:02d}")
                        except ValueError:
                            print(f"⚠️ [DailyRestart] 环境变量时间格式错误，尝试从配置文件读取")
                    
                    # 2. 如果环境变量未设置，从配置文件读取
                    if target_hour is None or target_minute is None:
                        try:
                            # 尝试从配置文件读取（使用模块级别的config对象）
                            # 注意：config是模块级别的全局变量
                            if config and hasattr(config, 'get_config'):
                                config_hour_str = config.get_config('daily_restart_hour', 'system_configs')
                                config_minute_str = config.get_config('daily_restart_minute', 'system_configs')
                                
                                # 添加调试日志
                                print(f"🔍 [DailyRestart] 从配置文件读取: hour='{config_hour_str}', minute='{config_minute_str}'")
                                
                                if config_hour_str and config_minute_str and config_hour_str.strip() and config_minute_str.strip():
                                    try:
                                        target_hour = int(config_hour_str.strip())
                                        target_minute = int(config_minute_str.strip())
                                        # 验证时间有效性
                                        if 0 <= target_hour <= 23 and 0 <= target_minute <= 59:
                                            print(f"✅ [DailyRestart] 从配置文件读取重启时间: {target_hour:02d}:{target_minute:02d}")
                                        else:
                                            print(f"⚠️ [DailyRestart] 配置文件时间无效（{target_hour:02d}:{target_minute:02d}），使用默认时间 07:45")
                                            target_hour = 7
                                            target_minute = 45
                                    except ValueError as ve:
                                        print(f"⚠️ [DailyRestart] 配置文件时间格式错误（hour='{config_hour_str}', minute='{config_minute_str}'）: {ve}，使用默认时间 07:45")
                                        target_hour = 7
                                        target_minute = 45
                                else:
                                    # 配置文件未设置，使用默认值 07:45（早盘重启）
                                    target_hour = 7
                                    target_minute = 45
                                    print(f"ℹ️ [DailyRestart] 配置文件未设置重启时间（hour='{config_hour_str}', minute='{config_minute_str}'），使用默认时间: {target_hour:02d}:{target_minute:02d}（早盘重启）")
                            else:
                                # 配置文件对象不存在，使用默认值 07:45（早盘重启）
                                target_hour = 7
                                target_minute = 45
                                print(f"ℹ️ [DailyRestart] 配置文件对象不可用（config={config}），使用默认时间: {target_hour:02d}:{target_minute:02d}（早盘重启）")
                        except Exception as config_e:
                            # 读取配置文件异常，使用默认值 07:45（早盘重启）
                            target_hour = 7
                            target_minute = 45
                            print(f"⚠️ [DailyRestart] 读取配置文件异常: {config_e}，使用默认时间: {target_hour:02d}:{target_minute:02d}（早盘重启）")
                            import traceback
                            traceback.print_exc()
                    
                    # 确保最终有有效的时间值（默认07:45早盘重启）
                    if target_hour is None or target_minute is None:
                        target_hour = 7
                        target_minute = 45
                    
                    self._daily_restart_scheduler.start_daily_check(
                        check_interval=60,  # 每分钟检查一次
                        test_mode=test_mode,
                        target_hour=target_hour,
                        target_minute=target_minute
                    )
                    if test_mode:
                        final_hour = target_hour if target_hour is not None else 7
                        final_minute = target_minute if target_minute is not None else 45
                        print(f"🧪 每日定时重启任务启动成功（测试模式：每10秒检查一次，目标时间：{final_hour:02d}:{final_minute:02d}）")
                    else:
                        final_hour = target_hour if target_hour is not None else 7
                        final_minute = target_minute if target_minute is not None else 45
                        print(f"✅ 每日定时重启任务启动成功（目标时间：{final_hour:02d}:{final_minute:02d}）")
                    
                    # 显示配置文件路径提示
                    config_file_path = getattr(self._daily_restart_scheduler, '_config_file_path', None)
                    if config_file_path:
                        print(f"💡 提示：可通过修改配置文件实时更新重启时间: {config_file_path}")
                        print(f"   格式：hour:minute（例如：8:58），修改后1分钟内自动生效")
                    print("💡 提示：测试时可在Python交互环境中调用：")
                    print("   - mainWindow._daily_restart_scheduler.test_restart_now()  # 立即执行")
                    print("   - mainWindow._daily_restart_scheduler.test_restart_in_minutes(3)  # 3分钟后执行")
                    print("   - mainWindow._daily_restart_scheduler.test_restart_at_time(8, 30)  # 8:30执行")
                    print("💡 提示：运行时设置重启时间：")
                    print("   - mainWindow._daily_restart_scheduler.update_restart_time(8, 30)  # 设置为8:30")
                    print("   - mainWindow._daily_restart_scheduler.get_restart_time_str()  # 查看当前设置")
            except Exception as e:
                print(f"⚠️ 每日定时重启任务启动失败: {e}")
                traceback.print_exc()
            
            # 启动登录状态监控器（每1分钟检查一次登录状态）
            try:
                from xy_client.services.Lucky5.core.login_status_monitor import LoginStatusMonitor
                if not hasattr(self, '_login_status_monitor'):
                    self._login_status_monitor = LoginStatusMonitor(self)
                self._login_status_monitor.start()
                print("✅ 登录状态监控器启动成功（每1分钟检查一次）")
            except Exception as e:
                print(f"⚠️ 登录状态监控器启动失败: {e}")
                traceback.print_exc()
            
            # 启动余额同步定时器（如果启用）
            try:
                MyThreadingTimer.myTimer(60, SystemsUsers.syncBalanceTimer, (60, self))
                print("✅ 余额同步定时器启动成功")
            except Exception as e:
                print(f"⚠️ 余额同步定时器启动失败: {e}")
                traceback.print_exc()
            
            # 启动下注任务定时器（如果启用）
            try:
                print("🔄 [start_automation_tasks] 准备启动下注任务定时器...")
                print(f"   - 函数: Lucky.betTasksTimer")
                print(f"   - 参数: mainWindow={self}, direct=1")
                
                # 优化：首次延迟缩短为1秒，加快登录后第一次下注
                first_delay = 1  # 之后循环仍按3-5秒策略由任务内部控制
                MyThreadingTimer.myTimer(first_delay, Lucky.betTasksTimer, (self, 1))
                print("✅ 下注任务定时器启动成功")
                print(f"   - 定时器将在{first_delay}秒后首次执行（3-5秒标准轮询间隔）")
                # 额外优化：登录成功后立即触发一次首轮下注（异步），不等待定时器
                try:
                    import threading
                    threading.Thread(target=Lucky.betTasksTimer, args=(self, 1), daemon=True).start()
                    print("⚡ 已异步触发首次立即执行的下注任务（不等待定时器）")
                except Exception as kickoff_e:
                    print(f"⚠️ 首次立即执行下注任务触发失败（不影响定时器）: {kickoff_e}")
                
                # 优化：登录成功后立即预获取期号，减少第一次下注时的等待
                try:
                    print("🔄 [start_automation_tasks] 预获取期号，加快首次下注...")
                    from xy_client.services.systems_users.SystemsUsers import getRotActiveQiHao
                    access_token = getattr(self, 'access_token', '')
                    lottery_type = getattr(self, 'lottery_type', 8)
                    if access_token:
                        qihao_rst = getRotActiveQiHao(access_token, lottery_type)
                        if qihao_rst and qihao_rst.get('status') == 200 and qihao_rst.get('data'):
                            next_qihao = qihao_rst['data'].get('next_qihao') or qihao_rst['data'].get('period_no')
                            if next_qihao:
                                self.current_qihao = str(next_qihao)
                                print(f"✅ [start_automation_tasks] 期号预获取成功: {self.current_qihao}")
                except Exception as pre_qihao_e:
                    print(f"⚠️ [start_automation_tasks] 预获取期号失败（不影响功能）: {pre_qihao_e}")
            except Exception as e:
                print(f"❌ 下注任务定时器启动失败: {e}")
                traceback.print_exc()
            
            # 启动获取期号定时器（如果启用）
            try:
                MyThreadingTimer.myTimer(30, SystemsUsers.getNowBetQihaoTimer, (30, self))
                print("✅ 获取期号定时器启动成功")
            except Exception as e:
                print(f"⚠️ 获取期号定时器启动失败: {e}")
                traceback.print_exc()
            
            # 启动获取开奖数据定时器（如果启用）
            # 注意：getNowKjDataTimer 和 getNowKjDataTimer2 只需 mainWindow 参数
            try:
                MyThreadingTimer.myTimer(30, SystemsUsers.getNowKjDataTimer, (self,))
                print("✅ 获取开奖数据定时器启动成功")
            except Exception as e:
                print(f"⚠️ 获取开奖数据定时器启动失败: {e}")
                traceback.print_exc()
            
            # 启动获取开奖数据2定时器（如果启用）
            try:
                MyThreadingTimer.myTimer(5, SystemsUsers.getNowKjDataTimer2, (self,))
                print("✅ 获取开奖数据2定时器启动成功")
            except Exception as e:
                print(f"⚠️ 获取开奖数据2定时器启动失败: {e}")
                traceback.print_exc()
            
            # 启动登录检测定时器（暂时禁用，避免登录成功后跳回登录页）
            # 已修复登录状态保护，但为了避免任何潜在问题，暂时禁用登录检测定时器
            # try:
            #     SystemsUsers.loginClient(60, self)
            #     print("✅ 登录检测定时器启动成功")
            # except Exception as e:
            #     if ENABLE_DETAILED_LOGS:
            #         print(f"⚠️ 登录检测定时器启动失败: {e}")
            
            # 启动页面刷新定时器（暂时禁用，避免页面刷新影响登录状态）
            # try:
            #     SystemsUsers.refreshTimer(300, self)
            #     print("✅ 页面刷新定时器启动成功")
            # except Exception as e:
            #     if ENABLE_DETAILED_LOGS:
            #         print(f"⚠️ 页面刷新定时器启动失败: {e}")
            
            # 标记任务已启动
            self._tasks_started = True
            print("✅ 所有自动化任务启动完成")
            
        except Exception as e:
            print(f"❌ 启动自动化任务异常: {e}")
            traceback.print_exc()
    
    def closeAllBrowsers(self):
        """关闭所有浏览器窗口（只关闭会员登录相关的浏览器，不关闭代理浏览器）"""
        try:
            if ENABLE_DETAILED_LOGS:
                print("🔄 开始关闭所有浏览器窗口...")
            
            # 关键修复：只关闭会员登录相关的浏览器，不关闭代理浏览器
            # 代理浏览器是独立的，用于获取会员下注日志，不应该被会员登录状态影响
            
            # 关闭主浏览器（会员登录使用）
            if hasattr(self, 'driver') and self.driver:
                try:
                    self.driver.quit()
                    if ENABLE_DETAILED_LOGS:
                        print("✅ 主浏览器已关闭")
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f"⚠️ 关闭主浏览器时发生异常: {e}")
                finally:
                    self.driver = None
            
            # 关键修复：不关闭代理浏览器，代理登录和会员登录完全独立
            # 代理浏览器用于获取会员下注日志，应该保持独立运行
            # if hasattr(self, 'agent_browser') and self.agent_browser:
            #     try:
            #         self.agent_browser.quit()
            #         if ENABLE_DETAILED_LOGS:
            #             print("✅ 代理浏览器已关闭")
            #     except Exception as e:
            #         if ENABLE_DETAILED_LOGS:
            #             print(f"⚠️ 关闭代理浏览器时发生异常: {e}")
            #     finally:
            #         self.agent_browser = None
            
            # 关闭其他可能的浏览器实例
            if hasattr(self, 'browser') and self.browser:
                try:
                    self.browser.quit()
                    if ENABLE_DETAILED_LOGS:
                        print("✅ 用户浏览器已关闭")
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f"⚠️ 关闭用户浏览器时发生异常: {e}")
                finally:
                    self.browser = None
            
            # 清理cookies（只清理会员登录相关的cookies，保留代理登录的cookies）
            self.browser_cookies = None
            # 关键修复：不清理代理cookies，代理登录和会员登录完全独立
            # self.agent_cookies = None
            
            # 重要：强制终止本程序启动的浏览器进程，确保完全关闭 - 暂时跳过
            # self._force_kill_browser_processes()
            
            if ENABLE_DETAILED_LOGS:
                print("🔄 会员登录相关浏览器窗口已关闭，准备重新登录")
            
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f"❌ 关闭浏览器窗口时发生异常: {e}")
                traceback.print_exc()
    
    def smart_login_management(self, force_login=False):
        """
        智能登录管理 - 统一处理登录逻辑
        
        Args:
            force_login: 是否强制登录（跳过冷却时间和时间间隔检查），用于API明确检测到未登录时
        """
        try:
            # 关键修复：检查WebDriver连接失败标志，如果连接失败，暂停登录重试
            if hasattr(self, '_webdriver_connection_failed') and self._webdriver_connection_failed:
                failed_time = getattr(self, '_webdriver_connection_failed_time', 0)
                # 如果连接失败时间超过5分钟，允许再次尝试（可能浏览器已恢复）
                if time.time() - failed_time < 300:  # 5分钟内暂停
                    if ENABLE_DETAILED_LOGS:
                        print(f"⏸️ WebDriver连接失败，暂停登录重试（避免创建多个窗口），等待5分钟后自动恢复")
                    return False
                else:
                    # 超过5分钟，清除失败标志，允许再次尝试
                    self._webdriver_connection_failed = False
                    print("🔄 WebDriver连接失败已超过5分钟，清除失败标志，允许再次尝试")
            
            # 检查是否在登录锁定状态（但允许当前调用继续）
            if get_global_login_lock() and not self.is_logging_in:
                if ENABLE_DETAILED_LOGS:
                    print("🔒 登录正在进行中，跳过本次登录检查")
                return False
            
            # 检查是否在计划登录时间（计划登录时间不受限制）
            if is_scheduled_login_time():
                if ENABLE_DETAILED_LOGS:
                    print("⏰ 检测到计划登录时间（07:55-08:00），开始执行登录")
                return self.execute_smart_login()
            
            # 检查当前登录状态
            # 关键优化：不仅检查全局状态，还要检查本地状态（is_need_login）和API状态
            # 因为全局状态可能没有及时更新，导致误判
            global_status = get_global_login_status()
            local_status = getattr(self, 'is_need_login', 0) == 1
            
            # 如果全局状态和本地状态都显示已登录，才认为已登录
            # 如果只有全局状态显示已登录，但本地状态显示未登录，说明状态不同步，需要重新登录
            if global_status and local_status:
                if ENABLE_DETAILED_LOGS:
                    print("✅ 当前已登录（全局状态和本地状态一致），无需重新登录")
                return True
            elif global_status and not local_status:
                # 状态不同步，需要重新登录
                if ENABLE_DETAILED_LOGS:
                    print("⚠️ 全局状态显示已登录，但本地状态显示未登录，状态不同步，需要重新登录")
                # 同步全局状态到本地状态
                set_global_login_status(False)
                # 继续执行登录流程
            elif not global_status and local_status:
                # 状态不同步，需要重新登录
                if ENABLE_DETAILED_LOGS:
                    print("⚠️ 本地状态显示已登录，但全局状态显示未登录，状态不同步，需要重新登录")
                # 同步本地状态到全局状态
                self.is_need_login = 0
                # 继续执行登录流程
            else:
                # 两个状态都显示未登录，需要登录
                if ENABLE_DETAILED_LOGS:
                    print("⚠️ 全局状态和本地状态都显示未登录，需要重新登录")
                # 继续执行登录流程
            
            # 关键优化：如果明确检测到未登录（force_login=True），跳过冷却时间和时间间隔检查
            # 因为这是明确的未登录状态（如API返回Status=5），需要立即登录
            if not force_login:
                # 检查登录冷却时间（防止频繁重试）- 只对自动登录生效，手动点击登录按钮不受限制
                # 如果是手动触发（通过按钮点击），跳过冷却时间检查
                is_manual_login = hasattr(self, '_manual_login_triggered') and self._manual_login_triggered
                if not is_manual_login:
                    if hasattr(self, '_last_login_attempt_time'):
                        time_since_last_attempt = time.time() - self._last_login_attempt_time
                        if time_since_last_attempt < 10:  # 手动登录时，10秒内可以重试
                            if ENABLE_DETAILED_LOGS:
                                print(f"⏰ 距离上次登录尝试仅 {int(time_since_last_attempt)} 秒，跳过自动登录")
                            return False
                else:
                    # 手动登录时，重置标志
                    self._manual_login_triggered = False
                
                # 检查登录时间间隔
                if not is_login_time_valid():
                    print("⏰ 距离上次登录时间过短，跳过登录")
                    return False
            else:
                # 强制登录模式：跳过所有限制
                if ENABLE_DETAILED_LOGS:
                    print("🔄 强制登录模式：跳过冷却时间和时间间隔检查，立即执行登录")
            
            # 检查是否应该重试登录
            if not should_retry_login():
                print("❌ 登录重试次数已达上限，停止重试")
                return False
            
            # 执行智能登录
            return self.execute_smart_login()
            
        except Exception as e:
            print(f"❌ 智能登录管理异常: {e}")
            import traceback
            traceback.print_exc()
            return False
    
    def execute_smart_login(self):
        """执行智能登录"""
        try:
            print("🔄 开始执行智能登录...")
            
            # 记录登录尝试时间
            self._last_login_attempt_time = time.time()
            
            # 登录锁定已在doLogin中设置，这里不需要重复设置
            
            # 增加重试次数
            increment_login_retry()
            
            # 检查是否已有浏览器在运行，如果有则直接使用
            if hasattr(self, 'driver') and self.driver:
                try:
                    # 测试现有连接是否有效（添加超时保护，避免长时间卡住）
                    import threading
                    url_result = [None]
                    url_error = [None]
                    
                    def check_url():
                        try:
                            url_result[0] = self.driver.current_url
                        except Exception as e:
                            url_error[0] = e
                    
                    check_thread = threading.Thread(target=check_url, daemon=True)
                    check_thread.start()
                    check_thread.join(timeout=3)  # 最多等待3秒
                    
                    if url_error[0] is None and url_result[0] is not None:
                        print("✅ 检测到现有浏览器连接有效，直接使用")
                        # 直接执行登录，跳过浏览器启动
                        login_success = self.perform_login()
                        if login_success:
                            self.is_need_login = 1
                            print("✅ 使用现有浏览器登录成功")
                            # 清除连接失败标志
                            if hasattr(self, '_webdriver_connection_failed'):
                                self._webdriver_connection_failed = False
                            return True
                    else:
                        error_str = str(url_error[0]).lower() if url_error[0] else ''
                        # 检测连接失败错误（10061、连接被拒绝等）
                        if '10061' in error_str or 'refused' in error_str or '积极拒绝' in error_str or '无法连接' in error_str:
                            print(f"❌ 现有浏览器连接失败（{url_error[0]}），暂停登录重试，避免创建多个窗口")
                            # 设置连接失败标志
                            self._webdriver_connection_failed = True
                            self._webdriver_connection_failed_time = time.time()
                            return False
                        else:
                            print(f"⚠️ 现有浏览器连接无效: {url_error[0]}，重新启动")
                            self.driver = None
                except Exception as check_e:
                    error_str = str(check_e).lower()
                    # 检测连接失败错误
                    if '10061' in error_str or 'refused' in error_str or '积极拒绝' in error_str or '无法连接' in error_str:
                        print(f"❌ 检查浏览器连接失败（{check_e}），暂停登录重试，避免创建多个窗口")
                        # 设置连接失败标志
                        self._webdriver_connection_failed = True
                        self._webdriver_connection_failed_time = time.time()
                        return False
                    else:
                        print(f"⚠️ 检查现有浏览器连接异常: {check_e}，重新启动")
                        self.driver = None
            
            # 关键修复：在启动新浏览器之前，检查WebDriver连接失败标志
            if hasattr(self, '_webdriver_connection_failed') and self._webdriver_connection_failed:
                failed_time = getattr(self, '_webdriver_connection_failed_time', 0)
                # 如果连接失败时间超过5分钟，允许再次尝试（可能浏览器已恢复）
                if time.time() - failed_time < 300:  # 5分钟内暂停
                    print(f"⏸️ [execute_smart_login] WebDriver连接失败，暂停启动新浏览器（避免创建多个窗口），等待5分钟后自动恢复")
                    return False
                else:
                    # 超过5分钟，清除失败标志，允许再次尝试
                    self._webdriver_connection_failed = False
                    print("🔄 [execute_smart_login] WebDriver连接失败已超过5分钟，清除失败标志，允许再次尝试")
            
            # 只有在没有有效浏览器时才启动新浏览器
            if not (hasattr(self, 'driver') and self.driver):
                print("🔄 启动新浏览器...")
                
                # 关键修复：确保端口管理器使用正确的端口
                # 如果已有端口管理器，确保端口一致
                if hasattr(self, 'port_manager') and self.port_manager:
                    # 如果已有原始端口，使用原始端口
                    if hasattr(self, '_original_port') and self._original_port:
                        self.port = self._original_port
                        # 更新端口管理器的端口（如果端口管理器端口不一致）
                        if self.port_manager.debug_port != self.port:
                            print(f"⚠️ 端口管理器端口({self.port_manager.debug_port})与实际端口({self.port})不一致，更新端口管理器")
                            # 重新创建端口管理器，使用正确的端口
                            from xy_client.services.tools.BrowserPortManager import BrowserPortManager
                            account_id = getattr(self, 'access_token', 'default')
                            browser_type = self.getPreferredBrowser() or "chrome"
                            self.port_manager = BrowserPortManager(account_id, browser_type)
                            # 手动设置端口（如果端口管理器支持）
                            if hasattr(self.port_manager, 'debug_port'):
                                self.port_manager.debug_port = self.port
                    else:
                        # 使用端口管理器的端口
                        self.port = self.port_manager.debug_port
                        self._original_port = self.port
                        print(f"🔄 使用端口管理器的端口: {self.port}")
                else:
                    # 没有端口管理器，使用原始端口逻辑
                    if hasattr(self, '_original_port') and self._original_port:
                        self.port = self._original_port
                        print(f"🔄 使用原始浏览器端口: {self.port}")
                    else:
                        self.port = random.randint(9000, 9999)
                        self._original_port = self.port
                        print(f"🔄 生成新的浏览器端口: {self.port}")
                
                # 关键修复：启动浏览器前，先清理可能存在的旧浏览器进程（仅清理占用当前账户端口的进程）
                # 确保不会关闭其他账户的浏览器进程
                account_id = getattr(self, 'access_token', 'default')
                try:
                    import psutil
                    import subprocess
                    # 严格验证：只关闭占用当前账户端口的浏览器进程
                    # 通过验证命令行参数中包含当前端口来确保是当前账户的进程
                    closed_count = 0
                    for proc in psutil.process_iter(['pid', 'name', 'cmdline']):
                        try:
                            proc_name = proc.info['name'] or ''
                            cmdline = ' '.join(proc.info['cmdline'] or [])
                            
                            # 关键验证：确保进程的命令行中包含当前账户的端口
                            # 并且端口格式完全匹配（避免误匹配）
                            port_pattern = f'--remote-debugging-port={self.port}'
                            if ('chrome' in proc_name.lower() or 'firefox' in proc_name.lower()) and port_pattern in cmdline:
                                # 额外验证：检查用户数据目录是否匹配（如果可能）
                                # 这样可以进一步确保是当前账户的进程
                                user_data_pattern = f'--user-data-dir='
                                if user_data_pattern in cmdline:
                                    # 提取用户数据目录
                                    import re
                                    user_data_match = re.search(r'--user-data-dir=([^\s]+)', cmdline)
                                    if user_data_match:
                                        user_data_dir = user_data_match.group(1)
                                        # 检查是否包含当前账户ID（如果用户数据目录包含账户信息）
                                        if account_id in user_data_dir or 'default' in user_data_dir:
                                            print(f"🔄 [账户={account_id}] 发现占用端口 {self.port} 的浏览器进程（已验证用户目录），先关闭: PID={proc.pid}")
                                        else:
                                            print(f"⚠️ [账户={account_id}] 发现占用端口 {self.port} 的进程，但用户目录不匹配，跳过（避免关闭其他账户）")
                                            continue
                                
                                print(f"🔄 [账户={account_id}] 发现占用端口 {self.port} 的浏览器进程，先关闭: PID={proc.pid}")
                                proc.terminate()
                                try:
                                    proc.wait(timeout=2)
                                except psutil.TimeoutExpired:
                                    proc.kill()
                                time.sleep(1)  # 等待进程完全关闭
                                closed_count += 1
                                print(f"✅ [账户={account_id}] 已关闭占用端口 {self.port} 的浏览器进程: PID={proc.pid}")
                        except (psutil.NoSuchProcess, psutil.AccessDenied):
                            continue
                    
                    if closed_count > 0:
                        print(f"✅ [账户={account_id}] 共关闭 {closed_count} 个占用端口 {self.port} 的浏览器进程")
                except Exception as cleanup_e:
                    print(f"⚠️ [账户={account_id}] 清理旧浏览器进程时异常: {cleanup_e}")
            else:
                # 如果有已存在的浏览器，获取其实际端口
                if hasattr(self, 'port_manager') and self.port_manager:
                    actual_port = self.port_manager.debug_port
                    print(f"🔄 使用已有浏览器端口: {actual_port}")
                    self.port = actual_port
                print(f"✅ 使用现有浏览器，端口: {self.port}")
            
            # 代理端口也保持一致
            if hasattr(self, '_original_agent_port') and self._original_agent_port:
                self.agent_port = self._original_agent_port
                print(f"🔄 使用原始代理端口: {self.agent_port}")
            else:
                self.agent_port = self.port + 1
                self._original_agent_port = self.agent_port
                print(f"🔄 生成新的代理端口: {self.agent_port}")
            
            # 确保端口一致性
            self.ensure_port_consistency()
            
            # 关键修复：在登录前，先确保只有一个窗口（防止登录过程中创建新窗口）
            if hasattr(self, 'driver') and self.driver:
                try:
                    window_handles = self.driver.window_handles
                    if len(window_handles) > 1:
                        print(f"⚠️ [登录前] 检测到 {len(window_handles)} 个窗口，先清理...")
                        if self.browser_window_manager:
                            self.browser_window_manager.ensure_single_window()
                        else:
                            self.ensure_single_browser_window()
                except Exception as pre_cleanup_e:
                    print(f"⚠️ [登录前] 清理窗口异常: {pre_cleanup_e}")
            
            # 执行登录
            login_success = self.perform_login()
            
            # 关键修复：登录成功后，清除连接失败标志
            if login_success:
                if hasattr(self, '_webdriver_connection_failed'):
                    self._webdriver_connection_failed = False
                    print("✅ 登录成功，已清除WebDriver连接失败标志")
            
            if login_success:
                # 登录成功，设置登录状态
                self.is_need_login = 1
                print("✅ 智能登录成功")
                print(f"🔍 已设置 is_need_login = {self.is_need_login}")
                
                # 记录当前页面URL
                try:
                    current_url = self.driver.current_url
                    print(f"🌐 [execute_smart_login] 登录成功后当前URL: {current_url}")
                except Exception as url_e:
                    print(f"⚠️ [execute_smart_login] 无法获取当前URL: {url_e}")
                
                # 确保只有一个浏览器窗口
                self.ensure_single_browser_window()
                
                # 再次检查URL是否改变
                try:
                    final_url = self.driver.current_url
                    print(f"🌐 [execute_smart_login] 确保单窗口后URL: {final_url}")
                    
                    # 检查是否被重定向到登录页
                    if 'Login' in final_url or '登录' in final_url or 'Member/Login' in final_url:
                        print(f"⚠️ [execute_smart_login] 警告：URL被重定向到登录页！")
                        print(f"   - 可能原因：cookies未保存或页面刷新导致")
                    else:
                        print(f"✅ [execute_smart_login] 页面URL正常")
                except Exception as url_e:
                    print(f"⚠️ [execute_smart_login] 无法获取最终URL: {url_e}")
                
                return True
            else:
                print("❌ 智能登录失败")
                return False
                
        except Exception as e:
            print(f"❌ 执行智能登录异常: {e}")
            import traceback
            traceback.print_exc()
            return False
        finally:
            # 解锁逻辑由doLogin的finally块处理，这里不需要重复解锁
            pass
    
    def force_close_all_browsers(self):
        """强制关闭所有浏览器窗口"""
        try:
            print("🔄 强制关闭所有浏览器窗口...")
            
            # 关闭主浏览器
            if hasattr(self, 'driver') and self.driver:
                try:
                    self.driver.quit()
                    print("✅ 主浏览器已关闭")
                except Exception as e:
                    print(f"⚠️ 关闭主浏览器异常: {e}")
                finally:
                    self.driver = None
            
            # 关闭代理浏览器
            if hasattr(self, 'agent_browser') and self.agent_browser:
                try:
                    self.agent_browser.quit()
                    print("✅ 代理浏览器已关闭")
                except Exception as e:
                    print(f"⚠️ 关闭代理浏览器异常: {e}")
                finally:
                    self.agent_browser = None
            
            # 关闭其他浏览器
            if hasattr(self, 'browser') and self.browser:
                try:
                    self.browser.quit()
                    print("✅ 用户浏览器已关闭")
                except Exception as e:
                    print(f"⚠️ 关闭用户浏览器异常: {e}")
                finally:
                    self.browser = None
            
            # 强制终止所有Chrome和Firefox进程
            self._force_kill_all_browser_processes()
            
            # 清理cookies和状态
            self.browser_cookies = None
            self.agent_cookies = None
            
            print("✅ 所有浏览器窗口已强制关闭")
                
        except Exception as e:
            print(f"❌ 强制关闭浏览器异常: {e}")
    
    def _manual_restart_and_login(self):
        """
        手动重启和重新登录（备用方案）
        当每日重启调度器不存在时使用
        """
        try:
            import time
            print("🔄 [手动重登] 开始执行重启和重新登录...")
            
            # 步骤1: 关闭浏览器（关键修复：关闭账户的所有端口对应的浏览器进程）
            print("🔄 [手动重登] 步骤1: 关闭浏览器...")
            
            # 关键修复：先关闭账户的所有端口对应的浏览器进程
            account_id = getattr(self, 'access_token', None) or 'default_account'
            if hasattr(self, 'port_manager') and self.port_manager:
                account_id = self.port_manager.account_id
            
            try:
                from xy_client.services.tools.BrowserPortManager import close_all_browsers_for_account
                closed_count = close_all_browsers_for_account(account_id)
                if closed_count > 0:
                    print(f"✅ [手动重登] 已关闭账户 {account_id} 的 {closed_count} 个浏览器进程")
            except ImportError:
                print("⚠️ [手动重登] 无法导入 close_all_browsers_for_account，使用备用方案")
            except Exception as e:
                print(f"⚠️ [手动重登] 关闭所有端口浏览器异常: {e}")
            
            # 备用方案：使用原有的关闭方法
            self.force_close_all_browsers()
            
            # 等待一下，确保浏览器完全关闭
            time.sleep(3)
            
            # 步骤2: 清理登录状态
            print("🔄 [手动重登] 步骤2: 清理登录状态...")
            self.updateLoginStatus(False)
            self.browser_cookies = None
            
            # 步骤3: 重新登录
            print("🔄 [手动重登] 步骤3: 重新启动浏览器并登录...")
            if hasattr(self, 'execute_smart_login'):
                result = self.execute_smart_login()
                if result:
                    print("✅ [手动重登] 重新登录成功")
                else:
                    print("❌ [手动重登] 重新登录失败")
            elif hasattr(self, 'doLogin'):
                self.doLogin()
                time.sleep(5)
                if hasattr(self, 'is_need_login') and self.is_need_login == 1:
                    print("✅ [手动重登] 重新登录成功")
                else:
                    print("❌ [手动重登] 重新登录失败")
            else:
                print("❌ [手动重登] 未找到登录方法")
        except Exception as e:
            print(f"❌ [手动重登] 异常: {e}")
            import traceback
            traceback.print_exc()
    
    def _force_kill_all_browser_processes(self):
        """强制杀死程序启动的浏览器进程（只关闭调试端口的浏览器）"""
        try:
            print("🔄 开始关闭程序启动的浏览器进程...")
            
            import subprocess
            
            # 获取当前程序使用的端口
            current_ports = []
            if hasattr(self, 'port'):
                current_ports.append(str(self.port))
            if hasattr(self, 'agent_port'):
                current_ports.append(str(self.agent_port))
            
            # 如果没有端口信息，跳过清理
            if not current_ports:
                print("ℹ️ 没有找到程序使用的端口，跳过浏览器进程清理")
                return
            
            # 只关闭包含当前程序端口的Chrome进程
            for port in current_ports:
                try:
                    result = subprocess.run([
                        'taskkill', '/f', '/fi', f'COMMANDLINE eq *--remote-debugging-port={port}*'
                    ], capture_output=True, text=True, timeout=10)
                    
                    if result.returncode == 0:
                        print(f"✅ 已关闭端口 {port} 的Chrome进程")
                    else:
                        print(f"ℹ️ 未找到端口 {port} 的Chrome进程")
                except subprocess.TimeoutExpired:
                    print(f"⚠️ 关闭端口 {port} 的Chrome进程超时")
                except Exception as e:
                    print(f"⚠️ 关闭端口 {port} 的Chrome进程异常: {e}")
            
            # 同样处理Firefox
            for port in current_ports:
                try:
                    result = subprocess.run([
                        'taskkill', '/f', '/fi', f'COMMANDLINE eq *--remote-debugging-port={port}*'
                    ], capture_output=True, text=True, timeout=10)
                    
                    if result.returncode == 0:
                        print(f"✅ 已关闭端口 {port} 的Firefox进程")
                    else:
                        print(f"ℹ️ 未找到端口 {port} 的Firefox进程")
                except subprocess.TimeoutExpired:
                    print(f"⚠️ 关闭端口 {port} 的Firefox进程超时")
                except Exception as e:
                    print(f"⚠️ 关闭端口 {port} 的Firefox进程异常: {e}")
            
            # 关键修复：只关闭当前程序使用的chromedriver进程，不关闭其他程序的
            # 通过检查chromedriver进程的命令行参数，只关闭连接到当前程序端口的进程
            try:
                import psutil
                killed_count = 0
                for proc in psutil.process_iter(['pid', 'name', 'cmdline']):
                    try:
                        if proc.info['name'] and 'chromedriver' in proc.info['name'].lower():
                            cmdline = ' '.join(proc.info['cmdline'] or [])
                            # 只关闭连接到当前程序端口的chromedriver
                            for port in current_ports:
                                if f'--port={port}' in cmdline or f'port={port}' in cmdline:
                                    try:
                                        proc.terminate()
                                        proc.wait(timeout=3)
                                        killed_count += 1
                                        print(f"✅ 已关闭连接到端口 {port} 的chromedriver进程 (PID: {proc.pid})")
                                    except (psutil.NoSuchProcess, psutil.TimeoutExpired):
                                        try:
                                            proc.kill()
                                            killed_count += 1
                                        except:
                                            pass
                                    break
                    except (psutil.NoSuchProcess, psutil.AccessDenied):
                        continue
                
                if killed_count == 0:
                    print("ℹ️ 未找到当前程序使用的chromedriver进程")
            except Exception as e:
                print(f"⚠️ 关闭chromedriver进程异常: {e}")
            
            # 关键修复：只关闭当前程序使用的geckodriver进程
            try:
                import psutil
                killed_count = 0
                for proc in psutil.process_iter(['pid', 'name', 'cmdline']):
                    try:
                        if proc.info['name'] and 'geckodriver' in proc.info['name'].lower():
                            cmdline = ' '.join(proc.info['cmdline'] or [])
                            # 只关闭连接到当前程序端口的geckodriver
                            for port in current_ports:
                                if f'--port={port}' in cmdline or f'port={port}' in cmdline:
                                    try:
                                        proc.terminate()
                                        proc.wait(timeout=3)
                                        killed_count += 1
                                        print(f"✅ 已关闭连接到端口 {port} 的geckodriver进程 (PID: {proc.pid})")
                                    except (psutil.NoSuchProcess, psutil.TimeoutExpired):
                                        try:
                                            proc.kill()
                                            killed_count += 1
                                        except:
                                            pass
                                    break
                    except (psutil.NoSuchProcess, psutil.AccessDenied):
                        continue
                
                if killed_count == 0:
                    print("ℹ️ 未找到当前程序使用的geckodriver进程")
            except Exception as e:
                print(f"⚠️ 关闭geckodriver进程异常: {e}")
            
            print("✅ 程序启动的浏览器进程已关闭，用户手动打开的浏览器不受影响")
                
        except Exception as e:
            print(f"❌ 关闭浏览器进程异常: {e}")
    
    def _force_kill_browser_processes(self):
        """强制杀死程序启动的浏览器进程（只关闭调试端口的浏览器）"""
        try:
            if ENABLE_DETAILED_LOGS:
                print("🔄 开始关闭程序启动的浏览器进程...")
            
            # 简化版本：只使用taskkill命令，避免psutil可能的阻塞问题
            try:
                # 使用taskkill命令杀死包含调试端口的Chrome进程
                import subprocess
                result = subprocess.run([
                    'taskkill', '/f', '/fi', 'WINDOWTITLE eq *chrome*', 
                    '/fi', 'COMMANDLINE eq *--remote-debugging-port*'
                ], capture_output=True, text=True, timeout=10)
                
                if ENABLE_DETAILED_LOGS:
                    if result.returncode == 0:
                        print("✅ 已关闭程序启动的Chrome进程")
                    else:
                        print("ℹ️ 未找到程序启动的Chrome进程")
                        
            except subprocess.TimeoutExpired:
                if ENABLE_DETAILED_LOGS:
                    print("⚠️ 关闭Chrome进程超时")
            except Exception as e:
                if ENABLE_DETAILED_LOGS:
                    print(f"⚠️ 关闭Chrome进程异常: {e}")
            
            # 同样处理Firefox
            try:
                result = subprocess.run([
                    'taskkill', '/f', '/fi', 'WINDOWTITLE eq *firefox*', 
                    '/fi', 'COMMANDLINE eq *--remote-debugging-port*'
                ], capture_output=True, text=True, timeout=10)
                
                if ENABLE_DETAILED_LOGS:
                    if result.returncode == 0:
                        print("✅ 已关闭程序启动的Firefox进程")
                    else:
                        print("ℹ️ 未找到程序启动的Firefox进程")
                        
            except subprocess.TimeoutExpired:
                if ENABLE_DETAILED_LOGS:
                    print("⚠️ 关闭Firefox进程超时")
            except Exception as e:
                if ENABLE_DETAILED_LOGS:
                    print(f"⚠️ 关闭Firefox进程异常: {e}")
            
            if ENABLE_DETAILED_LOGS:
                print("✅ 程序启动的浏览器进程已关闭，用户手动打开的浏览器不受影响")
                    
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f"❌ 关闭程序启动的浏览器进程异常: {e}")
    
    def ensure_port_consistency(self):
        """确保端口一致性，避免多个浏览器进程"""
        try:
            # 确保主端口一致性
            if not hasattr(self, '_original_port') or not self._original_port:
                self.port = random.randint(9000, 9999)
                self._original_port = self.port
                print(f"🔄 初始化主端口: {self.port}")
            else:
                self.port = self._original_port
                print(f"🔄 保持主端口一致性: {self.port}")
            
            # 确保代理端口一致性
            if not hasattr(self, '_original_agent_port') or not self._original_agent_port:
                self.agent_port = self.port + 1
                self._original_agent_port = self.agent_port
                print(f"🔄 初始化代理端口: {self.agent_port}")
            else:
                self.agent_port = self._original_agent_port
                print(f"🔄 保持代理端口一致性: {self.agent_port}")
            
            return True
        except Exception as e:
            print(f"❌ 端口一致性检查异常: {e}")
            return False

    def ensure_single_browser_window(self):
        """确保只有一个机器人管理的浏览器窗口（只操作属于当前账户端口的窗口）"""
        try:
            # 优先使用新的浏览器窗口管理器（已包含端口验证）
            if self.browser_window_manager:
                return self.browser_window_manager.ensure_single_window()
            
            # 备用方案：原有的窗口管理逻辑
            if not hasattr(self, 'driver') or not self.driver:
                return True
            
            # 关键修复1：严格验证 driver 是否连接到了当前账户的端口
            # 获取当前账户的端口
            current_port = None
            account_id = getattr(self, 'access_token', 'default')
            
            if hasattr(self, 'port') and self.port:
                current_port = self.port
            elif hasattr(self, 'port_manager') and self.port_manager:
                current_port = self.port_manager.debug_port
            
            if not current_port:
                print(f"⚠️ [账户={account_id}] 无法获取当前账户端口，跳过窗口清理（避免误关闭其他账户窗口）")
                return False
            
            # 严格验证 driver 是否连接到了当前账户的端口
            driver_port = None
            try:
                # 通过 command_executor 检查端口
                command_executor = getattr(self.driver, 'command_executor', None)
                if command_executor:
                    executor_url = str(command_executor._url) if hasattr(command_executor, '_url') else str(command_executor)
                    # 从URL中提取端口号
                    import re
                    port_match = re.search(r':(\d+)(?:/|$)', executor_url)
                    if port_match:
                        driver_port = int(port_match.group(1))
                    
                    # 检查端口是否匹配
                    if driver_port != current_port:
                        # 端口不匹配，说明 driver 连接到了错误的端口（可能是其他账户的）
                        print(f"⚠️ [账户={account_id}] Driver端口不匹配: 期望端口={current_port}, 实际端口={driver_port}, URL={executor_url}")
                        print(f"⚠️ [账户={account_id}] 跳过窗口清理，避免误关闭其他账户的窗口")
                        return False  # 不清理，避免误关闭其他账户的窗口
                    else:
                        if ENABLE_DETAILED_LOGS:
                            print(f"✅ [账户={account_id}] Driver端口验证通过: {current_port}")
                else:
                    print(f"⚠️ [账户={account_id}] 无法获取command_executor，跳过窗口清理（避免误关闭其他账户窗口）")
                    return False
            except Exception as e:
                print(f"⚠️ [账户={account_id}] 验证端口时异常: {e}，跳过窗口清理（避免误关闭其他账户窗口）")
                return False
            
            # 关键修复2：再次验证 - 通过浏览器调试接口验证端口
            # 确保窗口确实属于当前账户的端口
            try:
                import requests
                debug_url = f'http://127.0.0.1:{current_port}/json'
                response = requests.get(debug_url, timeout=2)
                if response.status_code != 200:
                    print(f"⚠️ [账户={account_id}] 端口 {current_port} 的调试接口不可用，跳过窗口清理")
                    return False
            except Exception as e:
                print(f"⚠️ [账户={account_id}] 验证端口调试接口异常: {e}，跳过窗口清理")
                return False
            
            # 关键修复3：只操作属于当前账户端口的窗口
            # 获取所有窗口句柄（这些窗口应该都属于当前账户的端口，因为 driver 已连接到正确的端口）
            window_handles = self.driver.window_handles
            if len(window_handles) <= 1:
                return True
                
            print(f"🔄 [账户={account_id}, 端口={current_port}] 检测到 {len(window_handles)} 个浏览器窗口，开始智能清理（仅清理属于本账户的窗口）...")
            
            # 关键修复：优先保留已登录的窗口，避免关闭登录成功的窗口
            # 检查每个窗口的URL，找出已登录的窗口
            logged_in_window = None
            login_page_windows = []
            
            for handle in window_handles:
                try:
                    self.driver.switch_to.window(handle)
                    # 使用超时保护获取URL
                    import threading
                    url_result = [None]
                    url_timeout = [False]
                    
                    def get_url():
                        try:
                            url_result[0] = self.driver.current_url.lower()
                        except Exception:
                            url_timeout[0] = True
                    
                    url_thread = threading.Thread(target=get_url, daemon=True)
                    url_thread.start()
                    url_thread.join(timeout=2)  # 最多等待2秒
                    
                    if not url_thread.is_alive() and not url_timeout[0] and url_result[0]:
                        current_url = url_result[0]
                        # 检查是否在登录页面
                        is_login_page = any(keyword in current_url for keyword in ['/member/login', '/login', '登录'])
                        if is_login_page:
                            login_page_windows.append(handle)
                            if ENABLE_DETAILED_LOGS:
                                print(f"🔍 窗口 {handle} 在登录页面: {current_url}")
                        else:
                            # 不在登录页面，说明已登录
                            logged_in_window = handle
                            if ENABLE_DETAILED_LOGS:
                                print(f"✅ 窗口 {handle} 已登录: {current_url}")
                            break  # 找到已登录窗口，立即退出
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f"⚠️ 检查窗口 {handle} 的URL失败: {e}")
                    continue
            
            # 决定保留哪个窗口
            if logged_in_window:
                # 优先保留已登录的窗口
                main_window = logged_in_window
                print(f"✅ 优先保留已登录的窗口: {main_window}")
            else:
                # 如果没有已登录的窗口，保留第一个窗口
                main_window = window_handles[0]
                print(f"⚠️ 未找到已登录窗口，保留第一个窗口: {main_window}")
            
            # 关闭其他所有窗口（使用循环，每次重新获取窗口句柄，确保关闭成功）
            closed_count = 0
            max_attempts = 10  # 最多尝试10次，避免无限循环
            attempt = 0
            
            while attempt < max_attempts:
                # 每次重新获取窗口句柄（因为关闭窗口后句柄会变化）
                current_handles = self.driver.window_handles
                if len(current_handles) <= 1:
                    # 只剩下一个窗口，清理完成
                    break
                
                # 找出需要关闭的窗口（除了主窗口外的所有窗口）
                handles_to_close = [h for h in current_handles if h != main_window]
                if not handles_to_close:
                    # 没有需要关闭的窗口，清理完成
                    break
                
                # 尝试关闭第一个需要关闭的窗口
                handle_to_close = handles_to_close[0]
                try:
                    # 先切换到要关闭的窗口
                    self.driver.switch_to.window(handle_to_close)
                    # 关闭窗口
                    self.driver.close()
                    closed_count += 1
                    print(f"✅ 已关闭多余窗口: {handle_to_close}")
                    
                    # 验证窗口是否真的关闭了
                    time.sleep(0.1)  # 短暂等待，让浏览器处理关闭操作
                    remaining_handles = self.driver.window_handles
                    if handle_to_close in remaining_handles:
                        print(f"⚠️ 窗口 {handle_to_close} 关闭后仍在列表中，可能关闭失败")
                    else:
                        print(f"✅ 窗口 {handle_to_close} 已成功关闭")
                except Exception as e:
                    print(f"⚠️ 关闭窗口 {handle_to_close} 失败: {e}")
                    # 即使关闭失败，也尝试继续关闭其他窗口
                    # 检查窗口是否还存在
                    try:
                        remaining_handles = self.driver.window_handles
                        if handle_to_close not in remaining_handles:
                            # 窗口已经不存在了（可能被其他方式关闭了）
                            closed_count += 1
                            print(f"✅ 窗口 {handle_to_close} 已不存在（可能已被关闭）")
                    except:
                        pass
                
                attempt += 1
            
            # 切换回主窗口并最终验证
            try:
                # 重新获取窗口句柄，确保主窗口还在
                final_handles = self.driver.window_handles
                
                # 关键修复：最终验证窗口数量，确保清理成功
                if len(final_handles) > 1:
                    print(f"⚠️ [窗口清理] 清理后仍有 {len(final_handles)} 个窗口，清理可能未完全成功")
                    print(f"   [窗口清理] 剩余窗口: {final_handles}")
                    # 如果还有多个窗口，再次尝试清理（最多再试一次）
                    if len(final_handles) > 1 and attempt < max_attempts:
                        print(f"🔄 [窗口清理] 再次尝试清理剩余窗口...")
                        for handle in final_handles:
                            if handle != main_window:
                                try:
                                    self.driver.switch_to.window(handle)
                                    self.driver.close()
                                    time.sleep(0.2)
                                except:
                                    pass
                        # 重新获取窗口句柄
                        final_handles = self.driver.window_handles
                
                if main_window in final_handles:
                    self.driver.switch_to.window(main_window)
                    print(f"✅ [窗口清理] 已切换回主窗口: {main_window}，当前窗口数: {len(final_handles)}")
                elif final_handles:
                    # 主窗口不存在了，切换到第一个可用窗口
                    self.driver.switch_to.window(final_handles[0])
                    print(f"⚠️ [窗口清理] 主窗口不存在，已切换到第一个可用窗口: {final_handles[0]}，当前窗口数: {len(final_handles)}")
                else:
                    print(f"⚠️ [窗口清理] 没有可用窗口")
                
                # 最终验证：确保只有一个窗口
                final_check_handles = self.driver.window_handles
                if len(final_check_handles) == 1:
                    print(f"✅ [窗口清理] 清理成功，当前只有 {len(final_check_handles)} 个窗口")
                else:
                    print(f"⚠️ [窗口清理] 清理后仍有 {len(final_check_handles)} 个窗口，可能需要手动处理")
            except Exception as e:
                print(f"⚠️ [窗口清理] 切换回主窗口失败: {e}")
                # 如果切换失败，尝试切换到第一个可用窗口
                try:
                    remaining_handles = self.driver.window_handles
                    if remaining_handles:
                        self.driver.switch_to.window(remaining_handles[0])
                        print(f"✅ 已切换到第一个可用窗口: {remaining_handles[0]}")
                except Exception as e2:
                    print(f"❌ 无法切换到任何窗口: {e2}")
            
            # 清理后检查当前窗口状态
            try:
                final_url = self.driver.current_url.lower()
                is_login_page = any(keyword in final_url for keyword in ['/member/login', '/login', '登录'])
                if is_login_page:
                    print(f"⚠️ 清理后当前窗口仍在登录页面，可能需要重新登录")
                    # 设置登录状态为需要登录
                    if hasattr(self, 'is_need_login'):
                        self.is_need_login = 0
                else:
                    print(f"✅ 清理后当前窗口已登录: {final_url}")
            except Exception as e:
                print(f"⚠️ 检查清理后窗口状态失败: {e}")
            
            print(f"✅ [账户端口={current_port}] 已确保只有一个浏览器窗口（关闭了{closed_count}个多余窗口）")
            return True
            
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f"❌ 确保单窗口异常: {e}")
            return False
    
    def perform_login(self):
        """执行登录操作"""
        try:
            print("🔄 开始执行登录操作...")
            
            # 检查是否有用户信息
            if not hasattr(self, 'user_info') or not self.user_info:
                print("❌ 未找到用户信息，无法登录")
                return False
            
            username = self.user_info.get('username', '')
            if not username:
                print("❌ 用户名为空，无法登录")
                return False
            
            # 启动浏览器
            print(f"🔄 启动浏览器，用户: {username}")
            
            # 获取用户选择的浏览器类型
            selected_browser = self.getPreferredBrowser()
            print(f"🔍 用户选择的浏览器: {selected_browser}")

            configured_driver = str(config.get_config('chromedriver_path') or '').strip().lower()
            use_cdp_login = selected_browser == 'chrome' and configured_driver in ('', 'auto')
            self._browser_automation_mode = 'cdp' if use_cdp_login else 'webdriver'
            if use_cdp_login:
                print("✅ ChromeDriver为自动模式，使用Chrome调试协议登录，避免驱动下载和版本不匹配")
            
            # 启动浏览器进程
            print("🔄 开始启动浏览器进程...")
            browser_start_result = self.start_browser_process(selected_browser, username)
            if not browser_start_result:
                print("❌ 启动浏览器进程失败")
                return False
            print("✅ 浏览器进程启动成功")

            # 等待浏览器启动并验证端口可用性
            print("⏳ 等待浏览器启动并验证端口可用性...")
            import socket
            
            # 基础等待
            time.sleep(3)
            print("✅ 基础等待完成，开始验证端口...")
            
            # 验证端口是否可用（最多等待15秒，每秒检查一次）
            port_ready = False
            max_port_wait = 15
            for i in range(max_port_wait):
                try:
                    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
                        s.settimeout(1)
                        result = s.connect_ex(('localhost', int(self.port)))
                        if result == 0:
                            port_ready = True
                            print(f"✅ 端口 {self.port} 已可用（等待 {i+1} 秒）")
                            break
                except Exception as port_check_e:
                    pass
                if i < max_port_wait - 1:
                    if (i + 1) % 3 == 0:  # 每3秒输出一次日志
                        print(f"⏳ 等待端口 {self.port} 就绪... ({i+1}/{max_port_wait}秒)")
                    time.sleep(1)
            
            if not port_ready:
                print(f"⚠️ 端口 {self.port} 在 {max_port_wait} 秒内未就绪，但继续尝试连接...")
            else:
                print(f"✅ 端口 {self.port} 验证完成，准备连接WebDriver")

            # Auto mode uses Chrome DevTools directly and does not need ChromeDriver.
            if use_cdp_login:
                print(f"🔗 准备通过Chrome调试协议连接端口 {self.port}")
                self.driver = None
            else:
                print("🔄 准备连接到浏览器并打开登录页...")
                print(f"🔍 当前端口: {self.port}, 浏览器类型: {self.getPreferredBrowser() if hasattr(self, 'getPreferredBrowser') else 'chrome'}")
                try:
                    attach_result = self.attach_webdriver_and_open_login()
                    print(f"🔍 attach_webdriver_and_open_login 返回: {attach_result}")
                    if not attach_result:
                        print("❌ 连接浏览器失败，无法执行登录，暂停重试，避免创建多个窗口")
                        if not hasattr(self, '_webdriver_connection_failed') or not self._webdriver_connection_failed:
                            self._webdriver_connection_failed = True
                            self._webdriver_connection_failed_time = time.time()
                        return False
                    print("✅ 浏览器连接成功，准备执行登录")
                except Exception as attach_e:
                    error_str = str(attach_e).lower()
                    if '10061' in error_str or 'refused' in error_str or '积极拒绝' in error_str or '无法连接' in error_str:
                        print(f"❌ WebDriver连接失败（{attach_e}），暂停重试，避免创建多个窗口")
                        self._webdriver_connection_failed = True
                        self._webdriver_connection_failed_time = time.time()
                    else:
                        print(f"❌ 连接浏览器时发生异常: {attach_e}")
                        import traceback
                        traceback.print_exc()
                    return False

            # 执行登录
            print("🔄 开始执行登录流程...")

            # 调用实际的登录逻辑
            try:
                print("🔍 检查用户信息...")
                # 获取用户信息
                if not self.user_info:
                    from xy_client.services.systems_users.common import getAccountByToken
                    account_info = getAccountByToken(self.access_token)
                    if account_info and account_info.get('status') == 200:
                        self.user_info = account_info.get('data', {})
                        if ENABLE_DETAILED_LOGS:
                            print(f"✅ 获取用户信息成功: {self.user_info.get('username', 'N/A')}")
                    else:
                        if ENABLE_DETAILED_LOGS:
                            print("❌ 获取用户信息失败")
                        return False
                
                account = self.user_info.get('account', '')
                password = self.user_info.get('password', '')
                
                if not account or not password:
                    print(f"❌ 账号或密码为空，账号: {account}, 密码: {'*' * len(password) if password else 'None'}")
                    return False
                
                print(f"✅ 用户信息检查通过，账号: {account}")
                if use_cdp_login:
                    print("🔄 准备通过Chrome调试协议执行自动登录...")
                    login_result = Lucky.loginAWithCdp(account, password, self)
                    print(f"✅ Chrome调试协议登录完成，结果: {login_result}")
                    if login_result and login_result.get('status') == 200:
                        try:
                            self.balance.setText(str(login_result.get('balance', '0.00') or '0.00'))
                        except Exception as balance_e:
                            print(f"⚠️ 更新余额显示失败: {balance_e}")
                else:
                    print("🔄 准备调用actLogin方法...")
                    login_result = self.actLogin(
                        access_token=self.access_token,
                        ssc_domain=self.user_info.get('ssc_domain', ''),
                        account=account,
                        pwd=password
                    )
                    print(f"✅ actLogin方法调用完成，结果: {login_result}")
                
                if login_result and login_result.get('status') == 200:
                    # 登录成功，设置全局状态和本地状态
                    set_global_login_status(True)
                    self.is_need_login = 1  # 统一使用is_need_login
                    print("✅ 登录操作成功，已设置全局和本地登录状态")
                    
                    # 关键修复：登录成功后，清除连接失败标志
                    if hasattr(self, '_webdriver_connection_failed'):
                        self._webdriver_connection_failed = False
                        print("✅ 登录成功，已清除WebDriver连接失败标志")
                    
                    # 登录成功后导航到盘口地址（取消注释，确保打开盘口）
                    try:
                        if use_cdp_login:
                            print("✅ Chrome调试协议已完成登录和Cookie保存，无需WebDriver二次导航")
                        elif hasattr(self, 'driver') and self.driver:
                            ssc_domain = self.user_info.get('ssc_domain', '')
                            if ssc_domain:
                                # 确保URL格式正确
                                if not ssc_domain.startswith('http'):
                                    ssc_domain = 'https://' + ssc_domain
                                
                                # 导航到盘口地址（通常是App/Index）
                                print(f"🌐 [perform_login] 准备打开盘口地址: {ssc_domain}")
                                if '/App/Index' not in ssc_domain and '/Member/Login' not in ssc_domain:
                                    # 如果URL中没有指定路径，默认打开盘口
                                    if ssc_domain.endswith('/'):
                                        ssc_domain = ssc_domain + 'App/Index'
                                    else:
                                        ssc_domain = ssc_domain + '/App/Index'
                                
                                print(f"🌐 [perform_login] 打开盘口: {ssc_domain}")
                                # 使用安全的页面打开方法（支持超时控制和自动恢复）
                                # 如果失败，回退到简单的 driver.get() 方法
                                success = False
                                try:
                                    from xy_client.services.tools.PageRefreshManager import get_refresh_manager
                                    refresh_manager = get_refresh_manager(page_load_timeout=20, max_retry=2)
                                    success = refresh_manager.safe_get(self.driver, ssc_domain, 
                                                                      reason="打开盘口地址", 
                                                                      check_loading=False, timeout=20)  # 登录后打开盘口，不检查加载状态，避免误判
                                    if success:
                                        print(f"✅ 已打开盘口地址: {ssc_domain}")
                                    else:
                                        print(f"⚠️ 安全打开方式失败，回退到简单方式")
                                except ImportError:
                                    print(f"⚠️ 页面刷新管理器不可用，使用简单方式")
                                except Exception as e:
                                    print(f"⚠️ 安全打开方式异常: {e}，回退到简单方式")
                                
                                # 如果安全方式失败，使用简单的 driver.get() 方法
                                if not success:
                                    try:
                                        self.driver.get(ssc_domain)
                                        print(f"✅ 已使用简单方式打开盘口地址: {ssc_domain}")
                                    except Exception as e:
                                        print(f"❌ 打开盘口地址失败: {e}")
                                        import traceback
                                        traceback.print_exc()
                                
                                # 等待页面加载
                                time.sleep(2)
                                
                                # 检查导航后的URL
                                try:
                                    final_url = self.driver.current_url
                                    print(f"✅ [perform_login] 盘口页面已加载，当前URL: {final_url}")
                                except Exception as url_e:
                                    print(f"⚠️ [perform_login] 无法获取当前URL: {url_e}")
                            else:
                                print("⚠️ [perform_login] 未找到盘口地址，跳过打开盘口")
                        else:
                            print("⚠️ [perform_login] 浏览器驱动不可用，无法打开盘口")
                    except Exception as nav_e:
                        print(f"❌ [perform_login] 打开盘口失败: {nav_e}")
                        import traceback
                        traceback.print_exc()
                    
                    return True
                else:
                    if ENABLE_DETAILED_LOGS:
                        print(f"❌ 登录操作失败: {login_result}")
                    return False
                    
            except Exception as e:
                print(f"❌ 执行登录逻辑异常: {e}")
                import traceback
                traceback.print_exc()
                return False
                
        except Exception as e:
            print(f"❌ 执行登录操作异常: {e}")
            import traceback
            traceback.print_exc()
            return False
    
    def start_browser_process(self, browser_type, username):
        """启动浏览器进程"""
        try:
            # 关键修复：在启动浏览器进程之前，检查WebDriver连接失败标志
            if hasattr(self, '_webdriver_connection_failed') and self._webdriver_connection_failed:
                failed_time = getattr(self, '_webdriver_connection_failed_time', 0)
                # 如果连接失败时间超过5分钟，允许再次尝试（可能浏览器已恢复）
                if time.time() - failed_time < 300:  # 5分钟内暂停
                    print(f"⏸️ [start_browser_process] WebDriver连接失败，暂停启动浏览器进程（避免创建多个窗口），等待5分钟后自动恢复")
                    return False
                else:
                    # 超过5分钟，清除失败标志，允许再次尝试
                    self._webdriver_connection_failed = False
                    print("🔄 [start_browser_process] WebDriver连接失败已超过5分钟，清除失败标志，允许再次尝试")
            
            print(f"🔧 [start_browser_process] 开始启动浏览器进程，类型: {browser_type}, 端口: {self.port}")
            if browser_type == "chrome":
                # 获取Chrome路径
                chrome_path = config.get_config('binary_location')
                if not chrome_path:
                    print("❌ 未找到Chrome路径配置")
                    return False
                print(f"✅ 找到Chrome路径: {chrome_path}")
                
                # 构建启动命令
                system = platform.system()
                if system == "Windows":
                    # ⚠️ 重要：浏览器缓存目录路径 - 不要轻易修改
                    # 每个账号使用独立的缓存目录，确保缓存持久化
                    # 目录结构：C:\.temp\9222\{username}
                    # 修改此路径会导致账号缓存丢失，需要重新登录
                    user_data_dir = f"C:\\.temp\\9222\\{username}"
                    # 确保目录存在
                    import os
                    os.makedirs(user_data_dir, exist_ok=True)
                    cmd_command = (
                        f'"{chrome_path}" --remote-debugging-port={self.port} '
                        f'--remote-allow-origins=* --user-data-dir="{user_data_dir}"'
                    )
                    print(f"📁 [start_browser_process] 浏览器缓存目录: {user_data_dir}")
                elif system == "Darwin":  # macOS
                    # ⚠️ 重要：浏览器缓存目录路径 - 不要轻易修改
                    # 目录结构：/tmp/9222/{username}
                    user_data_dir = f"/tmp/9222/{username}"
                    import os
                    os.makedirs(user_data_dir, exist_ok=True)
                    cmd_command = (
                        f'"{chrome_path}" --remote-debugging-port={self.port} '
                        f'--remote-allow-origins=* --user-data-dir="{user_data_dir}"'
                    )
                    print(f"📁 [start_browser_process] 浏览器缓存目录: {user_data_dir}")
                else:
                    print(f"❌ 不支持的操作系统: {system}")
                    return False
                
                print(f"🔧 [start_browser_process] 启动命令: {cmd_command}")
                # 执行命令
                process = subprocess.Popen(cmd_command, shell=True)
                print(f"✅ Chrome浏览器已启动，端口: {self.port}, PID: {process.pid}")
                
                # 关键修复：将端口记录到全局端口集合中（用于早盘开盘前关闭所有浏览器）
                try:
                    account_id = getattr(self, 'access_token', None) or username or 'default_account'
                    from xy_client.services.tools.BrowserPortManager import _add_port_to_account
                    _add_port_to_account(account_id, int(self.port))
                    if hasattr(self, 'agent_port'):
                        _add_port_to_account(account_id, int(self.agent_port))
                    print(f"✅ [端口记录] 已记录账户 {account_id} 的端口: 主端口={self.port}, 代理端口={getattr(self, 'agent_port', 'N/A')}")
                except Exception as port_record_e:
                    print(f"⚠️ [端口记录] 记录端口异常: {port_record_e}")
                
            elif browser_type == "firefox":
                if ENABLE_DETAILED_LOGS:
                    print("✅ Firefox浏览器选择，等待后续处理...")
                # Firefox的具体启动逻辑可以在这里添加
            
            return True
            
        except Exception as e:
            print(f"❌ 启动浏览器进程异常: {e}")
            import traceback
            traceback.print_exc()
            return False
    
    def execute_login_logic(self, username):
        """执行具体的登录逻辑"""
        try:
            # 这里可以调用具体的登录方法
            # 例如：Lucky.loginA 或 Lucky.loginAgent
            if ENABLE_DETAILED_LOGS:
                print(f"🔄 执行登录逻辑，用户: {username}")
            
            # 模拟登录过程，实际应该调用具体的登录方法
            # 这里需要根据实际的登录逻辑来实现
            time.sleep(3)  # 模拟登录时间
            
            # 这里应该返回实际的登录结果
            # 暂时返回True作为示例
            return True
            
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f"❌ 执行登录逻辑异常: {e}")
            return False

    # ====== 新增：连接到已启动的Chrome并打开登录页 ======
    def attach_webdriver_and_open_login(self):
        """连接到已启动的浏览器并打开登录页（使用稳定的getDriver函数）"""
        try:
            print(f"🔗 [attach_webdriver_and_open_login] 开始，端口: {self.port}")
            
            # 使用现有的getDriver函数（经过测试，更稳定）
            print("🔧 [attach_webdriver_and_open_login] 导入tools模块...")
            from xy_client.services.tools import tools
            selected_browser = self.getPreferredBrowser() if hasattr(self, 'getPreferredBrowser') else 'chrome'
            print(f"🔧 [attach_webdriver_and_open_login] 浏览器类型: {selected_browser}, 端口: {self.port}")
            
            # 再次快速验证端口（确保端口可用）
            print(f"🔍 [attach_webdriver_and_open_login] 快速验证端口 {self.port}...")
            import socket
            try:
                with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
                    s.settimeout(2)
                    result = s.connect_ex(('localhost', int(self.port)))
                    if result == 0:
                        print(f"✅ [attach_webdriver_and_open_login] 端口 {self.port} 确认可用")
                    else:
                        print(f"⚠️ [attach_webdriver_and_open_login] 端口 {self.port} 暂时不可用，但继续尝试...")
            except Exception as port_check_e:
                print(f"⚠️ [attach_webdriver_and_open_login] 端口检查异常: {port_check_e}")
            
            print(f"🔧 [attach_webdriver_and_open_login] 调用tools.getDriver...")
            try:
                print(f"🔧 [attach_webdriver_and_open_login] 调用参数: browser={selected_browser}, port={self.port}")
                self.driver = tools.getDriver(selected_browser, self.port)
                print(f"🔧 [attach_webdriver_and_open_login] getDriver返回: {self.driver is not None}")
                
                # 关键修复：如果getDriver返回None，说明连接失败，设置失败标志
                if self.driver is None:
                    print(f"❌ [attach_webdriver_and_open_login] getDriver返回None，连接失败，暂停重试，避免创建多个窗口")
                    self._webdriver_connection_failed = True
                    self._webdriver_connection_failed_time = time.time()
                    return False
                
                if self.driver:
                    print("✅ [attach_webdriver_and_open_login] 已连接到已启动的浏览器")
                    # 验证连接是否真的可用
                    try:
                        current_url = self.driver.current_url
                        print(f"✅ [attach_webdriver_and_open_login] 当前页面URL: {current_url}")
                    except Exception as url_e:
                        print(f"⚠️ [attach_webdriver_and_open_login] 无法获取当前URL: {url_e}")
                else:
                    print("❌ [attach_webdriver_and_open_login] getDriver返回None，连接失败，暂停重试，避免创建多个窗口")
                    # 设置连接失败标志
                    self._webdriver_connection_failed = True
                    self._webdriver_connection_failed_time = time.time()
                    return False
            except Exception as driver_e:
                error_str = str(driver_e).lower()
                # 检测连接失败错误（10061、连接被拒绝等）
                if '10061' in error_str or 'refused' in error_str or '积极拒绝' in error_str or '无法连接' in error_str:
                    print(f"❌ [attach_webdriver_and_open_login] WebDriver连接失败（{driver_e}），暂停重试，避免创建多个窗口")
                    # 设置连接失败标志
                    self._webdriver_connection_failed = True
                    self._webdriver_connection_failed_time = time.time()
                    return False
                else:
                    print(f"❌ [attach_webdriver_and_open_login] 使用getDriver连接失败: {driver_e}")
                    import traceback
                    traceback.print_exc()
                    return False
            
            # 打开登录页
            print("🔧 [attach_webdriver_and_open_login] 准备打开登录页...")
            login_url = None
            try:
                login_url = self.domain or self.wp_domain or ''
                print(f"🔍 [attach_webdriver_and_open_login] 从domain获取: {login_url}")
            except Exception as domain_e:
                print(f"⚠️ [attach_webdriver_and_open_login] 获取domain异常: {domain_e}")
                login_url = ''
            
            if not login_url and hasattr(self, 'user_info') and isinstance(self.user_info, dict):
                login_url = self.user_info.get('ssc_domain', '')
                print(f"🔍 [attach_webdriver_and_open_login] 从user_info获取: {login_url}")
            
            if login_url:
                # 尝试直接使用登录页
                if 'Member/Login' not in login_url and 'App/Index' not in login_url:
                    login_url = login_url.rstrip('/') + '/Member/Login'
                print(f"🌐 [attach_webdriver_and_open_login] 准备打开登录页: {login_url}")
                try:
                    # 使用安全的页面打开方法（支持超时控制和自动恢复）
                    success = False
                    try:
                        from xy_client.services.tools.PageRefreshManager import get_refresh_manager
                        refresh_manager = get_refresh_manager(page_load_timeout=20, max_retry=2)
                        success = refresh_manager.safe_get(self.driver, login_url, 
                                                          reason="打开登录页", 
                                                          check_loading=True, timeout=20)
                        if success:
                            print(f"🌐 [attach_webdriver_and_open_login] 已打开登录页: {login_url}")
                        else:
                            print(f"⚠️ [attach_webdriver_and_open_login] 安全打开登录页失败，使用driver.get回退")
                    except ImportError:
                        print("⚠️ [attach_webdriver_and_open_login] 页面刷新管理器不可用，使用driver.get回退")

                    if not success:
                        self.driver.get(login_url)
                        print(f"🌐 [attach_webdriver_and_open_login] 已通过driver.get打开登录页: {login_url}")
                except Exception as get_e:
                    print(f"❌ [attach_webdriver_and_open_login] 打开登录页失败: {get_e}")
                    import traceback
                    traceback.print_exc()
                    return False
            else:
                print("⚠️ [attach_webdriver_and_open_login] 未找到登录URL，跳过打开登录页")
            
            print("✅ [attach_webdriver_and_open_login] 完成")
            return True
        except Exception as e:
            print(f"❌ [attach_webdriver_and_open_login] 连接调试端口或打开登录页失败: {e}")
            import traceback
            traceback.print_exc()
            return False

    def restartBrowserForLogin(self):
        """重启浏览器进行登录操作（保持向后兼容）"""
        return self.smart_login_management()

    def actLogin(self, access_token='', ssc_domain='', account='', pwd=''):
        try:
            # ssc_domain = self.domain_val.text()
            # username = self.username_val.text()
            # access_token = self.token_val.text()
            # pwd = self.pwd_val.text()
            if ENABLE_DETAILED_LOGS:
                print((access_token, ssc_domain, account, pwd))

            if self.account_status == 0:
                QMessageBox.about(self, '登陆结果', '登陆失败，账号过期或者被禁用')  # 弹框
            else:
                # ssc_domain = 'http://f1.wg7s5297.xyz'
                # username = 'Qxe121312'
                # pwd = 'Ws112233'

                # 添加超时保护
                import threading
                import queue
                
                result_queue = queue.Queue()
                exception_queue = queue.Queue()
                
                def login_task():
                    try:
                        print(f"🔄 [login_task] 开始执行登录任务，账号: {account}")
                        result = Lucky.loginA(account, pwd, self)
                        print(f"✅ [login_task] 登录任务完成，结果: {result}")
                        result_queue.put(result)
                    except Exception as e:
                        print(f"❌ [login_task] 登录任务异常: {e}")
                        import traceback
                        traceback.print_exc()
                        exception_queue.put(e)
                
                # 启动登录任务线程
                print("🔄 准备启动登录任务线程...")
                login_thread = threading.Thread(target=login_task)
                login_thread.daemon = True
                login_thread.start()
                print("✅ 登录任务线程已启动")
                
                # 等待结果，设置120秒超时（增加超时时间）
                print("⏳ 等待登录任务完成（最多120秒）...")
                try:
                    login_thread.join(timeout=120)
                    if login_thread.is_alive():
                        timeout_msg = "登录任务执行超时"
                        print(f"⚠️ {timeout_msg}")
                        # 强制终止线程
                        try:
                            import ctypes
                            ctypes.pythonapi.PyThreadState_SetAsyncExc(ctypes.c_long(login_thread.ident), ctypes.py_object(SystemExit))
                        except:
                            pass
                        return False
                    else:
                        print("✅ 登录任务线程已结束")
                        # 检查是否有异常
                        if not exception_queue.empty():
                            exception = exception_queue.get()
                            print(f"❌ 登录任务异常: {exception}")
                            import traceback
                            traceback.print_exc()
                            return False
                        else:
                            # 检查结果队列是否有数据
                            if result_queue.empty():
                                print("❌ 登录任务完成但结果队列为空")
                                return False
                            rst = result_queue.get()
                            print(f"✅ 从结果队列获取到登录结果: {rst}")
                except Exception as e:
                    print(f"❌ 登录任务等待异常: {e}")
                    import traceback
                    traceback.print_exc()
                    return False
                
                # rst = {'status': 200}
                print('登录结果打印1：', rst)

                # 安全检查rst是否为字典且包含必要字段
                if not isinstance(rst, dict):
                    print(f"❌ 登录结果格式错误: {type(rst)}")
                    return {'status': 500, 'msg': '登录结果格式错误'}
                
                if 'status' not in rst:
                    print(f"❌ 登录结果缺少status字段: {rst}")
                    return {'status': 500, 'msg': '登录结果缺少status字段'}

                if rst['status'] == 200:
                    # 安全获取balance字段
                    balance = rst.get('balance', '0.00')
                    if not balance:
                        balance = '0.00'
                    try:
                        self.balance.setText(str(balance))
                    except Exception as balance_e:
                        print(f"❌ 设置余额显示失败: {balance_e}")
                        import traceback
                        traceback.print_exc()
                    # 设置本地登录状态
                    self.is_need_login = 1  # 统一使用is_need_login
                    
                    # 更新全局登录状态
                    global GLOBAL_LOGIN_STATUS
                    GLOBAL_LOGIN_STATUS = True
                    
                    # 强制更新浏览器cookies状态
                    try:
                        if hasattr(self, 'driver') and self.driver:
                            cookies = self.driver.get_cookies()
                            if cookies:
                                # 转换为字符串格式，与loginA保持一致
                                cookies_str = ''
                                for cookie in cookies:
                                    cookies_str += cookie.get('name', '') + '=' + cookie.get('value', '') + ';'
                                self.browser_cookies = cookies_str
                                if ENABLE_DETAILED_LOGS:
                                    print('✅ 浏览器cookies已更新')
                    except Exception as cookie_e:
                        if ENABLE_DETAILED_LOGS:
                            print(f'⚠️ 更新cookies失败: {cookie_e}')
                        import traceback
                        traceback.print_exc()
                    
                    # 更新全局登录状态函数
                    try:
                        set_global_login_status(True)
                        if ENABLE_DETAILED_LOGS:
                            print('✅ 全局登录状态已更新')
                    except Exception as status_e:
                        if ENABLE_DETAILED_LOGS:
                            print(f'⚠️ 更新全局登录状态失败: {status_e}')
                    
                    if ENABLE_DETAILED_LOGS:
                        print("✅ actLogin: 已设置本地登录状态和全局登录状态")
                    return rst  # 返回完整的结果对象
                else:
                    if ENABLE_DETAILED_LOGS:
                        print(f"❌ 登录失败: {rst.get('msg', '未知错误')}")
                    return rst  # 返回完整的结果对象

        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f"❌ 登录异常: {e}")
                import traceback
                traceback.print_exc()
            return {'status': 500, 'msg': f'登录异常: {e}'}

    def setWindowTableW(self):
        self.table.setColumnWidth(10, 200)
        self.table.setColumnWidth(11, 280)
        self.table.setColumnWidth(7, 100)

    # @staticmethod
    def getTzSystemUserLists(self):
        url = robot_domain + '/api/betting-records/get-lists'
        post_data = {'access_token': self.token_val.text()}

        headers = {'content-type': 'application/json'}
        rst = globalSession.post(url, data=json.dumps(post_data), headers=headers)
        rstData = rst.json()

        if ENABLE_DETAILED_LOGS:
            print(rstData)
        setDatas = rstData['datas']
        columns = ['qihao', 'codes', 'betting_money', 'bonus', 'single', 'profits', 'kj_codes', 'snid', 'playway_name',
                   'create_time']
        columns_len = len(columns)
        rows_len = len(setDatas)
        self.table.setColumnCount(columns_len)
        self.table.setRowCount(rows_len)

        i = 0  # 行号
        for data in setDatas:
            # print("A:", i, data)
            for column, field in enumerate(columns):
                # print("B:", i, column, field, val)
                # exit(300)
                if field in ['status', 'is_use_proxy', 'is_auto_login', 'is_auto_bet']:
                    val = data[field]
                    # 修改
                    self.table.setCellWidget(i, column, self.setStatusBtn(val, field, data))
                elif field in ['act']:  # 操作
                    self.table.setCellWidget(i, column, self.setViewBtn(data))
                elif field in ['balance']:  # 同步余额
                    self.table.setCellWidget(i, column, self.setSyncBalanceBtn(data))
                else:
                    val = data[field]
                    self.table.setItem(int(i), column, QTableWidgetItem(val))
            i += 1

        self.table.horizontalHeader().setStretchLastSection(True)

    def setStatusBtn(self, val=0, field='status', data={}):
        global login_txt
        widget = QtWidgets.QWidget()
        status_txt = '已禁用'
        color = 'LightCoral'
        if field == 'status':
            login_txt = '登陆'
            if val == '1':
                status_txt = '已激活'
                color = 'NavajoWhite'
        else:
            if val == '1':
                status_txt = '是'
                color = 'NavajoWhite'
            else:
                status_txt = '否'
        # 修改
        updateBtn = QtWidgets.QPushButton(status_txt)
        updateBtn.setStyleSheet(
            "text-align : center; background-color : {0}; height : 30px; width : 150px; border-style: outset; font : 13px".format(
                color))

        # 更新字段状态
        updateBtn.clicked.connect(lambda: self.statusUpdateBtn(data['id'], field))

        hLayout = QtWidgets.QHBoxLayout()
        hLayout.addWidget(updateBtn)
        if field == 'is_auto_login':  # 登陆按钮
            loginBtn = QtWidgets.QPushButton(login_txt)
            loginBtn.setStyleSheet(
                "background-color: text-align: center; background-color: NavajoWhite; height: 30px; width: 150px; border-style: outset; font: 13px")
            hLayout.addWidget(loginBtn)

            loginBtn.clicked.connect(
                lambda: self.clickLoginBtn(data['account'], data['password']))

        hLayout.setContentsMargins(5, 2, 5, 2)
        widget.setLayout(hLayout)
        return widget

    def statusUpdateBtn(self, id='0', field=''):
        if ENABLE_DETAILED_LOGS:
            print('status更新id:' + id + '，field:' + field)
        User.User.updateTzSystemUsersByField(id, field)

    def clickLoginBtn(self, username='', pwd=''):
        if ENABLE_DETAILED_LOGS:
            print('登陆：' + str(id), username, pwd)
        thread = Thread(target=self.newThreadLogin, args=(username, pwd))
        thread.start()

    def newThreadLogin(self, username, pwd):
        try:
            if not username or not pwd:
                raise ValueError('账号信息为空')

            # loginRst = self.actLogin(access_token, ssc_domain, username, pwd)
            loginRst = Lucky.loginA(username, pwd, self)
            if loginRst['status'] == 200:
                # self.ms.text_print.emit(self.tip_msg, "账号：" + username + ' 登录成功，余额：' + loginRst['balance'])
                if ENABLE_DETAILED_LOGS:
                    print('loginRst：', loginRst)
                
                try:
                    # 更新登录状态为成功
                    self.updateLoginStatus(True)
                    
                    # 等待页面加载完成
                    time.sleep(3)
                    
                    # 调试登录状态
                    if ENABLE_DETAILED_LOGS:
                        print("🔍 登录成功后，开始调试登录状态...")
                        self.debugLoginStatus()
                        
                except Exception as status_ex:
                    if ENABLE_DETAILED_LOGS:
                        print(f"⚠️ 登录成功后的状态更新异常: {status_ex}")
                        traceback.print_exc()
                    # 即使状态更新失败，也不影响登录成功的事实
                
            else:
                if ENABLE_DETAILED_LOGS:
                    print('loginRst_err_msg：', loginRst)
                # 更新登录状态为失败
                self.updateLoginStatus(False)

        except Exception as ex:
            if ENABLE_DETAILED_LOGS:
                traceback.print_exc()
            try:
                if self.browser is not None:
                    self.browser.find_element(By.ID, 'btn_enter').click()
                else:
                    if ENABLE_DETAILED_LOGS:
                        print('⚠️ browser对象为None，跳过find_element操作')
            except Exception as ex_inner:
                if ENABLE_DETAILED_LOGS:
                    print('ex_inner' + str(lottery_type) + '：', str(ex_inner))
                pass
            # QMessageBox.about(self.ui, '登陆结果：', 'xxxxxx')  # 弹框
            SystemsUsers.pushErrorLog('登陆异常：' + username, lottery_type, ex.args)
            # self.ms.text_print.emit('登陆异常：' + self.tip_msg, e.args[0])
            # self.newThreadLogin(access_token, ssc_domain, username, pwd)

    # 多线程登陆
    def newAgentThreadLogin(self, ssc_domain, username, pwd):
        try:
            if not ssc_domain or not username or not pwd:
                raise ValueError('账号信息为空')

            # 导入Lucky模块
            from xy_client.services.Lucky5 import Lucky as LuckyModule
            loginRst = LuckyModule.loginAgent(username, pwd, ssc_domain, self)
            
            # 关键修复：如果登录失败，弹出提示框告知用户，不抛出异常
            if loginRst.get('status') != 200:
                error_msg = loginRst.get('msg', '代理登录失败')
                print(f"❌ [代理登录] {error_msg}")
                
                # 在主线程中弹出提示框（使用信号或QTimer）
                from PyQt5.QtCore import QTimer
                def show_error_message():
                    try:
                        QMessageBox.warning(
                            self,
                            "代理登录失败",
                            f"{error_msg}\n\n请检查：\n1. 代理账号和密码是否正确\n2. 代理域名是否有效",
                            QMessageBox.Ok
                        )
                    except Exception as msg_e:
                        print(f"⚠️ 显示错误提示框失败: {msg_e}")
                
                # 使用QTimer确保在主线程中执行
                QTimer.singleShot(100, show_error_message)
                
                # 关键修复：代理登录失败时，不调用updateLoginStatus，避免影响会员登录
                # updateLoginStatus会调用closeAllBrowsers()，会关闭代理浏览器
                # 代理登录失败只更新按钮状态，不影响会员登录状态
                return
            
            if loginRst['status'] == 200:
                # self.ms.text_print.emit(self.tip_msg, "账号：" + username + ' 登录成功，余额：' + loginRst['balance'])
                if ENABLE_DETAILED_LOGS:
                    print('loginRst：', loginRst)
                
                # 关键修复：代理登录成功时，不调用updateLoginStatus
                # updateLoginStatus是用于会员登录的状态管理，会影响会员登录相关的自动化任务
                # 代理登录只负责获取会员下注日志，不应该影响会员登录状态
                # 因此代理登录成功后，只记录日志，不更新会员登录状态
                print("✅ [代理登录] 代理登录成功（不影响会员登录状态）")
                
                # 等待页面加载完成，确保agent_domain等变量已经设置
                time.sleep(1)
                
                # 关键修复：代理登录成功后，保存代理账户信息到数据库
                try:
                    from xy_client.models import users
                    
                    # 优先使用登录成功后设置的agent_domain（从浏览器获取的）
                    # loginAgent函数会设置self.agent_domain，所以这里应该能获取到
                    agent_domain = None
                    if hasattr(self, 'agent_domain') and self.agent_domain:
                        agent_domain = str(self.agent_domain).strip()
                        print(f"✅ [代理登录] 使用已设置的agent_domain: {agent_domain}")
                    elif hasattr(self, 'agent_domain_val') and self.agent_domain_val:
                        agent_domain = self.agent_domain_val.text().strip()
                        print(f"✅ [代理登录] 使用界面输入的agent_domain: {agent_domain}")
                    elif ssc_domain:
                        agent_domain = str(ssc_domain).strip()
                        print(f"✅ [代理登录] 使用传入的ssc_domain: {agent_domain}")
                    
                    # 确保agent_domain格式正确（包含协议）
                    if agent_domain:
                        # 移除末尾的斜杠
                        agent_domain = agent_domain.rstrip('/')
                        # 如果没有协议，添加https://
                        if not agent_domain.startswith(('http://', 'https://')):
                            agent_domain = 'https://' + agent_domain
                    
                    agent_account = str(username).strip() if username else ''
                    agent_password = str(pwd).strip() if pwd else ''
                    access_token = getattr(self, 'access_token', None)
                    
                    print(f"🔍 [代理登录] 准备保存代理信息到数据库:")
                    print(f"   - access_token: {access_token[:20] if access_token else 'None'}...")
                    print(f"   - agent_domain: {agent_domain}")
                    print(f"   - agent_account: {agent_account}")
                    print(f"   - agent_password: {'*' * len(agent_password) if agent_password else 'None'}")
                    
                    # 验证所有必要信息都存在
                    if not access_token:
                        print(f"❌ [代理登录] access_token为空，无法保存代理信息")
                    elif not agent_domain:
                        print(f"❌ [代理登录] agent_domain为空，无法保存代理信息")
                    elif not agent_account:
                        print(f"❌ [代理登录] agent_account为空，无法保存代理信息")
                    elif not agent_password:
                        print(f"❌ [代理登录] agent_password为空，无法保存代理信息")
                    else:
                        # 所有信息都存在，执行保存
                        updateData = {
                            'agent_domain': agent_domain,
                            'agent_account': agent_account,
                            'agent_password': agent_password
                        }
                        
                        print(f"💾 [代理登录] 开始保存代理信息到数据库...")
                        result = users.updateUserAgentInfo(updateData, access_token)
                        if result:
                            print(f"✅ [代理登录] 代理账户信息已保存到数据库成功")
                            print(f"   - 数据库返回记录数: {len(result) if isinstance(result, (list, tuple)) else 1}")
                        else:
                            print(f"⚠️ [代理登录] 代理账户信息保存到数据库失败，返回None")
                            print(f"   - 请检查数据库连接和表结构")
                except Exception as save_e:
                    print(f"❌ [代理登录] 保存代理账户信息到数据库时发生异常: {save_e}")
                    import traceback
                    traceback.print_exc()
                
                # 等待页面加载完成
                time.sleep(3)
                
                # 调试登录状态
                if ENABLE_DETAILED_LOGS:
                    print("🔍 代理登录成功后，开始调试登录状态...")
                    self.debugLoginStatus()
                
            else:
                if ENABLE_DETAILED_LOGS:
                    print('loginRst_err_msg：', loginRst)
                # 更新登录状态为失败
                self.updateLoginStatus(False)

        except Exception as e:
            print(f'❌ [代理登录] 登录异常: {e}')
            traceback.print_exc()
            err_msg = e.args if hasattr(e, 'args') and e.args else str(e)
            try:
                access_token = getattr(self, 'access_token', '')
                lottery_type = getattr(self, 'lottery_type', 8)
                SystemsUsers.pushErrorLog('代理登陆异常', access_token, lottery_type, err_msg)
            except Exception:
                pass
            # self.ms.text_print.emit('登陆异常：' + self.tip_msg, e.args[0])
            # self.newThreadLogin(access_token, ssc_domain, username, pwd)

    def setViewBtn(self, data):
        widget = QtWidgets.QWidget()
        viewBtn = QtWidgets.QPushButton('查看')
        # 初始化子窗口
        # self.subViewWindow = SubViewWindow(parent=self)

        hLayout = QtWidgets.QHBoxLayout()
        viewBtn.setStyleSheet(
            "background-color: text-align: center; background-color: NavajoWhite; height: 30px; width: 150px; border-style: outset; font: 13px")
        hLayout.addWidget(viewBtn)
        viewBtn.clicked.connect(
            # lambda: self.clickViewBtn(data))
            lambda: self.childShowFun(data))
        hLayout.setContentsMargins(5, 2, 5, 2)
        widget.setLayout(hLayout)
        return widget

    def childShowFun(self, data):
        # 注意，这里的childwindow不能定义成临时变量，必须定义成主窗口类MainWindow的成员变量，如果是临时变量，即前面没有self，那么子窗口只会闪一下，就会消失
        try:
            # 创建一个简单的子窗口
            from PyQt5.QtWidgets import QDialog, QVBoxLayout, QTextEdit, QPushButton
            self.childwindow = QDialog(self)
            self.childwindow.setWindowTitle('用户信息查看')
            self.childwindow.setGeometry(300, 300, 500, 400)
            
            # 创建布局
            layout = QVBoxLayout()
            
            # 创建文本显示区域
            text_edit = QTextEdit()
            text_edit.setPlainText(str(data))
            layout.addWidget(text_edit)
            
            # 创建关闭按钮
            close_btn = QPushButton('关闭')
            close_btn.clicked.connect(self.childwindow.close)
            layout.addWidget(close_btn)
            
            self.childwindow.setLayout(layout)
            # 为子窗口设置图标
            app = QApplication.instance()
            if app:
                self.childwindow.setWindowIcon(app.windowIcon())
            self.childwindow.show()
            if ENABLE_DETAILED_LOGS:
                print('data:', data)
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f'创建子窗口失败: {e}')
                # 如果创建失败，直接打印数据
                print('用户数据:', data)

    def clickViewBtn(self, data):
        self.SubViewWindow.show()
        if ENABLE_DETAILED_LOGS:
            print(data)

    # 工具栏
    def initUiTools(self):
        return True
        # 用QtGui.QIcon做一个图标，
        # 建立一个关联快捷键
        exitAction.setShortcut('Ctrl+Q')
        # 关联一个触发函数self.close
        exitAction.triggered.connect(self.close)
        # 建立一个工具栏
        self.toolbar = self.addToolBar('Exit')
        # 为工具栏添加动作
        self.toolbar.addAction(exitAction)

        self.setGeometry(300, 300, 350, 250)
        self.setWindowTitle('Toolbar')
        self.show()

    # 检测进程是否存在
    def checkProcessIsExist(self):
        try:
            # 优先使用新的多账号进程管理器
            if check_account_registration:
                try:
                    self.account_id = check_account_registration()
                    if self.account_id:
                        print(f"✅ 账号注册成功: {self.account_id}")
                        return True
                    else:
                        print("⚠️ 账号注册失败，但继续运行程序")
                        # 不退出程序，继续运行
                        return True
                except Exception as e:
                    print(f"⚠️ 账号注册异常: {e}，但继续运行程序")
                    # 不退出程序，继续运行
                    return True
            
            # 备用方案：原有的进程检查逻辑
            pid = os.getpid()
            p = psutil.Process(pid)
            for proc in psutil.process_iter():
                # print('xxxxx', proc.name(), proc.pid, pid)
                if proc.name() == p.name() and p.name() != 'python.exe' and proc.pid != pid:
                    self.process_num += 1
                    if self.process_num > 2:
                        if ENABLE_DETAILED_LOGS:
                            print('err_msg：Another instance is already running', p.name())
                        # 不抛出异常，只打印警告，继续运行
                        print("⚠️ 检测到其他实例正在运行，但继续运行程序")
                        return True

            return True
        except Exception as e:
            print(f"⚠️ 进程检查异常: {e}，但继续运行程序")
            # 不退出程序，继续运行
            return True

    # 将界面更新的操作放在后台线程中执行
    def update_token_val(mainWindow, userInfo, token_val, access_token):
        token_val.setText(access_token)
        mainWindow.token_val.setText(mainWindow.access_token)
        mainWindow.setWindowTitle('用户界面：' + userInfo['username'])
        # 设置默认值
        mainWindow.domain_val.setText(mainWindow.wp_domain)
        mainWindow.username_val.setText(mainWindow.wp_account)
        mainWindow.pwd_val.setText(mainWindow.wp_password)
        mainWindow.domain_val.sizeHint().width()

    # def updateChromeDriver(self):
    #    # 获取谷歌版本号
    #    chromeVersion = cUpdater.getChromeVersion()
    #    # 下载浏览器驱动
    #    cUpdater.downloadChromeDriver(chromeVersion)

    def query_bet_details(self):
        try:
            # 获取分类值
            category_text = self.category_combo.currentText()
            if ENABLE_DETAILED_LOGS:
                print('category_text', category_text)
            category_map = {
                "全部": "0", "一定位": "109", "口XXXX": "1", "X口XXX": "2", "XX口XX": "3", "XXX口X": "4",
                    "XXXX口": "5", "二定位": "104", "口口XX": "6", "口X口X": "16", "口XX口": "17",
                    "X口X口": "18", "X口口X": "19",
                    "XX口口": "15", "五位二定": "99", "口XXX口": "20", "X口XX口": "21", "XX口X口": "22",
                    "XXX口口": "23", "三定位": "300",
                    "口口口X": "7", "口口X口": "8", "口X口口": "9", "X口口口": "10", "四定位": "11",
                    "二字现": "12", "三字现": "13",
                    "四字现": "14", "快打": "101", "快选": "102", "快译": "108", "txt导入": "103",
                    "一定": "109", "二定": "104"
                }
            category_value = category_map.get(category_text, "0")

            # 获取期号
            period_no = self.period_val.text()
            if not period_no:
                self.query_result.setPlainText("请先获取期号")
                return

            # 检查代理 cookies
            if not self.agent_cookies:
                self.query_result.setPlainText("代理登录状态异常，请重新登录")
                return

            # 将cookies字符串转换为字典
            cookies_dict = {}
            for cookie in self.agent_cookies.split(';'):
                if cookie.strip():
                    key, value = cookie.split('=', 1)
                    cookies_dict[key.strip()] = value.strip()
            if ENABLE_DETAILED_LOGS:
                print(f"cookies_dict: {cookies_dict}")

            # 构建请求 URL
            base_url = f"{self.agent_domain}/betDetail/GetTotalBetDetail"
            params = {
                'category': category_value,
                'page_size': 200,
                'pageSize': 200,
                'PageSize': 200,
                'PageIndex': 1,
                'pageIndex': 1,
                'page_index': 1,
                'period_no': period_no,
                '_': str(int(time.time() * 1000))
            }

            # 构建请求头
            headers = {
                'content-type': 'application/json, text/javascript, */*; q=0.01',
                'Accept-Encoding': 'gzip, deflate, br',
                'Accept-Language': 'zh-CN,zh;q=0.9',
                'Connection': 'keep-alive',
                'Host': self.agent_domain.replace('https://', '').replace('http://', '') if self.agent_domain else '',
                'Referer': f'{self.agent_domain}/Member/BetList',
                'sec-ch-ua-mobile': '?0',
                'sec-ch-ua-platform': '"Windows"',
                'Sec-Fetch-Dest': 'empty',
                'Sec-Fetch-Site': 'same-origin',
                'User-Agent': self.agent_user_agent,
                'X-Requested-With': 'XMLHttpRequest'
            }

            if ENABLE_DETAILED_LOGS:
                print(f"请求参数: {params}")
            # 设置重试策略
            retry_strategy = Retry(
                total=3,
                backoff_factor=1,
                status_forcelist=[429, 500, 502, 503, 504],
                allowed_methods=["HEAD", "GET", "OPTIONS"]
            )
            adapter = HTTPAdapter(max_retries=retry_strategy)
            http = requests.Session()
            http.mount("http://", adapter)
            http.mount("https://", adapter)

            # 发送请求
            response = http.get(base_url, params=params, headers=headers, cookies=cookies_dict, verify=False)

            # 显示结果
            if response.status_code == 200:
                try:
                    result = response.json()
                    if ENABLE_DETAILED_LOGS:
                        print(f"返回数据: {result}")
                    self.query_result.setPlainText(json.dumps(result, indent=2, ensure_ascii=False))
                    if ENABLE_DETAILED_LOGS:
                        print(f"返回数据结束")
                except json.JSONDecodeError:
                    if ENABLE_DETAILED_LOGS:
                        print(f"返回数据格式错误1:{response.text}")
                    self.query_result.setPlainText(f"返回数据格式错误: {response.text}")
                except Exception as e:
                    if ENABLE_DETAILED_LOGS:
                        print(f"处理返回数据时发生错误: {str(e)}")
            else:
                if ENABLE_DETAILED_LOGS:
                    print(f"返回数据格式错误2:{response.text}")
                self.query_result.setPlainText(f"查询失败: HTTP {response.status_code}")

        except requests.exceptions.RequestException as e:
            if ENABLE_DETAILED_LOGS:
                print(f"RequestException0: {str(e)}")
            log_system(f"RequestException: {str(e)}", 'error')
            self.query_result.setPlainText(f"Query exception: {str(e)}")
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f"RequestException1: {str(e)}")
            log_system(f"Unhandled exception: {str(e)}", 'error')
            self.query_result.setPlainText(f"An unexpected error occurred: {str(e)}")

def task_monitor(task_manager):
    """增强版任务监控器 - 智能日志输出到文件"""
    last_status_report = 0
    status_cache = {}
    
    while True:
        try:
            current_time = time.time()
            # 每60秒输出一次状态报告到文件，控制台只显示关键信息
            if current_time - last_status_report > 60:
                status_msg = f"\n=== 任务状态报告 {time.strftime('%H:%M:%S')} ==="
                log_system(status_msg, 'info')
                
                for task_name in task_manager.tasks:
                    try:
                        task = task_manager.tasks[task_name]
                        is_running = task['is_running']
                        
                        # 检查线程存活状态
                        if is_running and task['thread']:
                            thread_alive = task['thread'].is_alive()
                        else:
                            thread_alive = False
                        
                        # 检查最后活动时间
                        if time.time() - task['last_active'] > 120:  # 2 分钟无活动
                            warning_msg = f"警告: 任务 {task_name} 超过2分钟无活动"
                            if ENABLE_DETAILED_LOGS:
                                log_system(warning_msg, 'warning', show_console=True)
                            thread_alive = False
                        
                        # 记录状态变化到文件
                        current_status = '运行中' if thread_alive else '已停止'
                        if task_name not in status_cache or status_cache[task_name] != current_status:
                            status_info = f"{task_name}: {current_status}"
                            log_system(status_info, 'info')
                            status_cache[task_name] = current_status
                        
                        # 重启已停止的任务
                        if not thread_alive:
                            restart_msg = f"重启任务: {task_name}"
                            if ENABLE_DETAILED_LOGS:
                                log_system(restart_msg, 'info', show_console=True)
                        task_manager.stop_task(task_name)
                        time.sleep(2)
                        task_manager.start_task(task_name)
                        complete_msg = f"任务 {task_name} 重启完成"
                        if ENABLE_DETAILED_LOGS:
                            log_system(complete_msg, 'info', show_console=True)
                        
                    except Exception as e:
                        error_msg = f"监控任务 {task_name} 时发生异常: {e}"
                        if ENABLE_DETAILED_LOGS:
                            log_system(error_msg, 'error', show_console=True)
                        # 强制重启异常任务
                        try:
                            force_restart_msg = f"强制重启异常任务: {task_name}"
                            log_system(force_restart_msg, 'info')
                            task_manager.stop_task(task_name)
                            time.sleep(2)
                            task_manager.start_task(task_name)
                        except Exception as restart_e:
                            restart_fail_msg = f"重启任务 {task_name} 失败: {restart_e}"
                            if ENABLE_DETAILED_LOGS:
                                log_system(restart_fail_msg, 'error', show_console=True)
                
                log_system("=" * 50, 'info')
                last_status_report = current_time
            
        except Exception as e:
            monitor_error_msg = f"任务监控器发生严重异常: {e}"
            log_system(monitor_error_msg, 'error', show_console=True)
            if ENABLE_DETAILED_LOGS:
                print("🔄 尝试恢复监控器...")
            time.sleep(10)
        
        # 每30秒检查一次
        time.sleep(30)
        
        # 输入缓冲区自动清理
        try:
            import msvcrt
            if msvcrt.kbhit():
                while msvcrt.kbhit():
                    msvcrt.getch()
                cleanup_msg = "已自动清理输入缓冲区"
                if ENABLE_DETAILED_LOGS:
                    log_system(cleanup_msg, 'info', show_console=True)
        except Exception:
            pass


# 使用闭包
def createSyncBalance(mainWindow, inc):
    def inner_sync_balance():
        while True:
            try:
                # 检查全局登录锁定状态 - 如果正在登录则跳过
                if get_global_login_lock():
                    log_print('login', "🔒 登录正在进行中，跳过同步余额任务...")
                    time.sleep(10)  # 延迟10秒后再次检查
                    continue
                
                # 检查全局登录状态 - 如果未登录则延迟执行
                if not get_global_login_status():
                    log_print('login', "⚠️ 全局未登录状态，延迟执行同步余额任务...")
                    time.sleep(10)  # 延迟10秒后再次检查
                    continue
                
                # 改为日志记录，减少控制台输出
                current_time = time.time()
                if not hasattr(inner_sync_balance, 'last_log_time'):
                    inner_sync_balance.last_log_time = 0
                
                # 每60秒记录一次日志到文件
                if current_time - inner_sync_balance.last_log_time > 60:
                    log_msg = f"任务1：同步余额 - 网盘页面... {time.strftime('%H:%M:%S', time.localtime())}"
                    log_business('syncBalance', log_msg, 'info')
                    log_print('balance', log_msg)
                    inner_sync_balance.last_log_time = current_time
                
                SystemsUsers.syncBalanceTimer(inc, mainWindow)  # 同步余额 - 网盘页面
            except Exception as e:
                error_msg = f"同步余额任务异常: {e}"
                log_business('syncBalance', error_msg, 'error')
            finally:
                time.sleep(inc)

    return inner_sync_balance


def createBetTasks(mainWindow, inc, direct=1):
    def inner_bet_tasks():
        # 初始化统计信息
        if not hasattr(inner_bet_tasks, 'execution_count'):
            inner_bet_tasks.execution_count = 0
        if not hasattr(inner_bet_tasks, 'last_execution_time'):
            inner_bet_tasks.last_execution_time = 0
        
        # 初始化主窗口统计信息
        if not hasattr(mainWindow, '_betting_task_stats'):
            mainWindow._betting_task_stats = {}
        mainWindow._betting_task_stats['execution_count'] = inner_bet_tasks.execution_count
        mainWindow._betting_task_stats['last_execution_time'] = inner_bet_tasks.last_execution_time
        
        while True:
            try:
                # 简化的登录状态检查 - 优先保证下注任务执行
                # 检查1：登录锁定状态（如果正在登录，短暂等待）
                if get_global_login_lock():
                    log_print('login', "🔒 登录正在进行中，短暂等待...")
                    time.sleep(5)  # 减少等待时间
                    continue
                
                # 检查2：浏览器连接状态（最关键的检查）
                if not hasattr(mainWindow, 'driver') or not mainWindow.driver:
                    log_print('login', "⚠️ 浏览器未初始化，跳过下注任务")
                    time.sleep(10)
                    continue
                
                # 检查3：浏览器cookies（如果有cookies就认为已登录）
                if not hasattr(mainWindow, 'browser_cookies') or not mainWindow.browser_cookies:
                    log_print('login', "⚠️ 无浏览器cookies，跳过下注任务")
                    time.sleep(10)
                    continue
                
                # 检查4：浏览器URL（不在登录页面就认为已登录）
                try:
                    current_url = mainWindow.driver.current_url
                    if any(keyword in current_url.lower() for keyword in ['login', '登录', 'agreement', '协议']):
                        log_print('login', f"⚠️ 当前在登录页面: {current_url}")
                        time.sleep(10)
                        continue
                except Exception as e:
                    log_print('login', f"⚠️ 检查浏览器URL失败: {e}")
                    time.sleep(10)
                    continue
                
                # 如果通过了所有检查，更新全局登录状态
                if not get_global_login_status():
                    set_global_login_status(True)
                    log_print('login', "✅ 通过浏览器状态检查，更新全局登录状态")
                
                # 确保本地登录状态正确
                if not hasattr(mainWindow, 'is_need_login') or mainWindow.is_need_login != 1:
                    mainWindow.is_need_login = 1
                    log_print('login', "✅ 修复本地登录状态")
                
                # 跳过弹框处理，直接执行下注任务
                # 因为已经登录成功，不需要再处理责任声明页面
                log_print('bet_tasks', "✅ 登录状态正常，跳过弹框处理，直接执行下注任务")
                
                # 减少控制台输出，改为日志记录
                current_time = time.time()
                if not hasattr(inner_bet_tasks, 'last_log_time'):
                    inner_bet_tasks.last_log_time = 0
                
                # 添加错误恢复机制，防止程序卡死
                try:
                    # 检查浏览器是否还活着
                    if hasattr(mainWindow, 'driver') and mainWindow.driver:
                        # 尝试获取当前URL，如果失败说明浏览器已死
                        try:
                            current_url = mainWindow.driver.current_url
                        except Exception as e:
                            log_print('error_recovery', f"⚠️ 浏览器连接异常，尝试恢复: {e}")
                            # 尝试重新初始化浏览器
                            try:
                                mainWindow.driver.quit()
                                mainWindow.driver = None
                            except:
                                pass
                            continue
                except Exception as e:
                    log_print('error_recovery', f"⚠️ 错误恢复检查失败: {e}")
                    continue
                
                # 每30秒记录一次日志到文件
                if current_time - inner_bet_tasks.last_log_time > 30:
                    log_msg = f"任务2：下注任务执行中... {time.strftime('%H:%M:%S', time.localtime())}"
                    log_business('betTasks', log_msg, 'info')
                    log_print('bet_tasks', log_msg)
                    inner_bet_tasks.last_log_time = current_time
                
                # 添加下注任务执行监控
                if not hasattr(inner_bet_tasks, 'execution_count'):
                    inner_bet_tasks.execution_count = 0
                if not hasattr(inner_bet_tasks, 'last_execution_time'):
                    inner_bet_tasks.last_execution_time = 0
                
                inner_bet_tasks.execution_count += 1
                inner_bet_tasks.last_execution_time = current_time
                
                # 更新主窗口的统计信息
                if not hasattr(mainWindow, '_betting_task_stats'):
                    mainWindow._betting_task_stats = {}
                mainWindow._betting_task_stats['execution_count'] = inner_bet_tasks.execution_count
                mainWindow._betting_task_stats['last_execution_time'] = inner_bet_tasks.last_execution_time
                
                # 每10次执行记录一次状态
                if inner_bet_tasks.execution_count % 10 == 0:
                    log_print('bet_tasks', f"📊 下注任务执行统计: 已执行{inner_bet_tasks.execution_count}次")
                
                # 添加超时保护（Windows兼容）
                import threading
                import queue
                
                result_queue = queue.Queue()
                exception_queue = queue.Queue()
                
                def bet_task():
                    try:
                        result = Lucky.betTasksTimer(mainWindow, direct)  # 下注任务
                        result_queue.put(result)
                    except Exception as e:
                        exception_queue.put(e)
                
                # 启动下注任务线程
                bet_thread = threading.Thread(target=bet_task)
                bet_thread.daemon = True
                bet_thread.start()
                
                # 等待结果，设置30秒超时
                try:
                    bet_thread.join(timeout=30)
                    if bet_thread.is_alive():
                        timeout_msg = "下注任务执行超时"
                        log_print('bet_tasks', f"⚠️ {timeout_msg}")
                        # 记录超时日志
                        SystemsUsers.pushErrorLog(f'下注任务超时: {timeout_msg}', mainWindow.access_token, mainWindow.lottery_type, [timeout_msg])
                        return
                    else:
                        # 检查是否有异常
                        if not exception_queue.empty():
                            exception = exception_queue.get()
                            log_print('bet_tasks', f"❌ 下注任务执行异常: {exception}")
                            # 记录异常日志
                            SystemsUsers.pushErrorLog(f'下注任务异常: {exception}', mainWindow.access_token, mainWindow.lottery_type, [str(exception)])
                            raise exception
                        else:
                            # 下注任务执行成功
                            log_print('bet_tasks', f"✅ 下注任务执行完成 (第{inner_bet_tasks.execution_count}次)")
                except Exception as e:
                    log_print('bet_tasks', f"❌ 下注任务执行失败: {e}")
                    # 记录失败日志
                    SystemsUsers.pushErrorLog(f'下注任务失败: {e}', mainWindow.access_token, mainWindow.lottery_type, [str(e)])
                    raise e
                    
            except TimeoutError as e:
                timeout_msg = f"下注任务超时: {e}"
                if ENABLE_DETAILED_LOGS:
                    print(f"下注任务警告: {timeout_msg}")
                # 记录超时日志
                SystemsUsers.pushErrorLog(f'下注任务超时: {e}', mainWindow.access_token, mainWindow.lottery_type, [str(e)])
            except Exception as e:
                error_msg = f"下注任务异常: {e}"
                if ENABLE_DETAILED_LOGS:
                    print(f"下注任务错误: {error_msg}")
                # 记录异常日志
                SystemsUsers.pushErrorLog(f'下注任务异常: {e}', mainWindow.access_token, mainWindow.lottery_type, [str(e)])
            finally:
                time.sleep(inc)

    return inner_bet_tasks


def createGetNowBetQiHao(mainWindow, inc):
    def inner_getNowBetQiHao():
        while True:
            try:
                # 改为日志记录，减少控制台输出
                log_msg = f"任务3：获取正在进行的期号 - 机器人接口 {time.strftime('%H:%M:%S', time.localtime())}"
                log_business('getNowBetQiHao', log_msg, 'info')
                
                # 添加网络请求超时保护
                import threading
                import queue
                
                result_queue = queue.Queue()
                exception_queue = queue.Queue()
                
                def network_request():
                    try:
                        result = SystemsUsers.getNowBetQihaoTimer(inc, mainWindow)
                        result_queue.put(result)
                    except Exception as e:
                        exception_queue.put(e)
                
                # 启动网络请求线程
                request_thread = threading.Thread(target=network_request)
                request_thread.daemon = True
                request_thread.start()
                
                # 等待结果，设置15秒超时
                try:
                    request_thread.join(timeout=15)
                    if request_thread.is_alive():
                        timeout_msg = "警告: 网络请求超时，强制终止"
                        if ENABLE_DETAILED_LOGS:
                            print(f'获取期号警告: {timeout_msg}')
                        # 记录超时日志
                        SystemsUsers.pushErrorLog('获取期号任务超时', mainWindow.access_token, mainWindow.lottery_type, ['网络请求超时'])
                    else:
                        # 检查是否有异常
                        if not exception_queue.empty():
                            raise exception_queue.get()
                except Exception as e:
                    error_msg = f"获取期号任务异常: {e}"
                    if ENABLE_DETAILED_LOGS:
                            print(f'获取期号错误: {error_msg}')
                    SystemsUsers.pushErrorLog(f'获取期号任务异常: {e}', mainWindow.access_token, mainWindow.lottery_type, [str(e)])
                    
            except Exception as e:
                outer_error_msg = f"获取期号任务外层异常: {e}"
                if ENABLE_DETAILED_LOGS:
                    print(f'获取期号外层错误: {outer_error_msg}')
                SystemsUsers.pushErrorLog(f'获取期号任务外层异常: {e}', mainWindow.access_token, mainWindow.lottery_type, [str(e)])
            finally:
                time.sleep(inc)

    return inner_getNowBetQiHao


def createGetSiteNowKjData(mainWindow, inc):
    task_lock = threading.Lock()
    last_run_time = 0

    def inner_getNowKjData():
        nonlocal last_run_time
        while True:
            try:
                # 检查全局登录锁定状态 - 如果正在登录则跳过
                if get_global_login_lock():
                    log_print('login', "🔒 登录正在进行中，跳过开奖数据获取任务...")
                    time.sleep(10)  # 延迟10秒后再次检查
                    continue
                
                # 检查全局登录状态 - 如果未登录则延迟执行
                if not get_global_login_status():
                    log_print('login', "⚠️ 全局未登录状态，延迟执行开奖数据获取任务...")
                    time.sleep(10)  # 延迟10秒后再次检查
                    continue
                
                # 检查本地登录状态 - 双重保险
                if not hasattr(mainWindow, 'is_need_login') or mainWindow.is_need_login != 1:
                    # 如果全局状态为已登录，但本地状态为未登录，修复本地状态
                    if get_global_login_status():
                        mainWindow.is_need_login = 1
                        log_print('login', f"🔄 修复本地登录状态：全局已登录，设置 is_need_login = 1")
                    else:
                        current_value = getattr(mainWindow, 'is_need_login', 'NOT_FOUND')
                        log_print('login', f"⚠️ 本地未登录状态，延迟执行开奖数据获取任务... (is_need_login = {current_value})")
                        time.sleep(10)  # 延迟10秒后再次检查
                        continue
                
                # 在获取开奖数据前处理可能的弹框
                try:
                    if hasattr(mainWindow, 'driver') and mainWindow.driver:
                        from xy_client.services.Lucky5.Lucky import handle_popup_dialogs
                        handle_popup_dialogs(mainWindow.driver, silent=True)
                        
                        # 确保只有一个浏览器窗口
                        mainWindow.ensure_single_browser_window()
                except Exception as e:
                    log_print('popup', f"⚠️ 处理弹框时发生异常: {e}")
                
                current_time = time.time()
                with task_lock:
                    if current_time - last_run_time < inc:
                        time.sleep(1)
                        continue

                    # 静默执行，不输出详细日志
                    SystemsUsers.getNowKjDataTimer(mainWindow)
                    log_print('lottery_data', f"任务3：获取开奖数据完成 {time.strftime('%H:%M:%S', time.localtime())}")
                    last_run_time = current_time

            except Exception as e:
                # 静默处理异常，不输出详细错误信息
                time.sleep(5)

            time.sleep(1)

    return inner_getNowKjData


def createGetNowKjData2(mainWindow, inc):
    task_lock = threading.Lock()
    last_run_time = 0

    def inner_getNowKjData2():
        nonlocal last_run_time
        while True:
            try:
                # 检查登录状态 - 如果未登录则延迟执行
                if not hasattr(mainWindow, 'is_need_login') or mainWindow.is_need_login != 1:
                    # 如果全局状态为已登录，但本地状态为未登录，修复本地状态
                    if get_global_login_status():
                        mainWindow.is_need_login = 1
                        if ENABLE_DETAILED_LOGS:
                            print(f"🔄 修复本地登录状态：全局已登录，设置 is_need_login = 1")
                    else:
                        if ENABLE_DETAILED_LOGS:
                            current_value = getattr(mainWindow, 'is_need_login', 'NOT_FOUND')
                            print(f"⚠️ 未登录状态，延迟执行开奖数据获取任务2... (is_need_login = {current_value})")
                        time.sleep(10)  # 延迟10秒后再次检查
                        continue
                
                # 在获取开奖数据前处理可能的弹框
                try:
                    if hasattr(mainWindow, 'driver') and mainWindow.driver:
                        from xy_client.services.Lucky5.Lucky import handle_popup_dialogs
                        handle_popup_dialogs(mainWindow.driver, silent=True)
                        
                        # 确保只有一个浏览器窗口
                        mainWindow.ensure_single_browser_window()
                except Exception as e:
                    log_print('popup', f"⚠️ 处理弹框时发生异常: {e}")
                
                current_time = time.time()
                with task_lock:
                    if current_time - last_run_time < inc:
                        time.sleep(1)
                        continue

                    # 静默执行，不输出详细日志
                    SystemsUsers.getNowKjDataTimer2(mainWindow)
                    log_print('lottery_data', f"任务4：获取开奖数据2完成 {time.strftime('%H:%M:%S', time.localtime())}")
                    last_run_time = current_time

            except Exception as e:
                # 静默处理异常，不输出详细错误信息
                time.sleep(5)

            time.sleep(1)

    return inner_getNowKjData2


def createLoginClient(mainWindow, inc):
    def inner_getLoginClient():
        while True:
            try:
                # 首先检查是否已登录，如果已登录则跳过登录检测，避免干扰
                if hasattr(mainWindow, 'is_need_login') and mainWindow.is_need_login == 1:
                    # 已登录，跳过登录检测
                    if ENABLE_DETAILED_LOGS:
                        print("✅ 已登录，跳过登录检测")
                    time.sleep(inc)
                    continue
                
                # 改为日志记录，减少控制台输出
                log_msg = f'任务6：登陆检测开始.... {time.strftime("%Y-%m-%d %H:%M:%S")}'
                log_business('loginClient', log_msg, 'info')
                log_print('login', log_msg)
                
                # 添加Selenium操作超时保护
                import threading
                import queue
                
                result_queue = queue.Queue()
                exception_queue = queue.Queue()
                
                def selenium_operation():
                    try:
                        # 在操作开始前再次检查登录状态，避免多线程竞态
                        if hasattr(mainWindow, 'is_need_login') and mainWindow.is_need_login == 1:
                            print("✅ 已登录，跳过登录检测")
                            result_queue.put(True)
                            return
                        
                        result = Lucky.loginClient(mainWindow)
                        result_queue.put(result)
                    except Exception as e:
                        exception_queue.put(e)
                
                # 启动Selenium操作线程
                selenium_thread = threading.Thread(target=selenium_operation)
                selenium_thread.daemon = True
                selenium_thread.start()
                
                # 等待结果，设置20秒超时（Selenium操作通常需要更长时间）
                try:
                    selenium_thread.join(timeout=20)
                    if selenium_thread.is_alive():
                        timeout_msg = "警告: Selenium操作超时，强制终止"
                        if ENABLE_DETAILED_LOGS:
                            print(f'登录检测警告: {timeout_msg}')
                        # 记录超时日志
                        SystemsUsers.pushErrorLog('登录检测任务超时', mainWindow.access_token, mainWindow.lottery_type, ['Selenium操作超时'])
                    else:
                        # 检查是否有异常
                        if not exception_queue.empty():
                            raise exception_queue.get()
                except Exception as e:
                    error_msg = f"登录检测任务异常: {e}"
                    if ENABLE_DETAILED_LOGS:
                        print(f'登录检测错误: {error_msg}')
                    SystemsUsers.pushErrorLog(f'登录检测任务异常: {e}', mainWindow.access_token, mainWindow.lottery_type, [str(e)])
                    
            except Exception as e:
                outer_error_msg = f"登录检测任务外层异常: {e}"
                if ENABLE_DETAILED_LOGS:
                    print(f'登录检测外层错误: {outer_error_msg}')
                SystemsUsers.pushErrorLog(f'登录检测任务外层异常: {e}', mainWindow.access_token, mainWindow.lottery_type, [str(e)])
            finally:
                time.sleep(inc)

    return inner_getLoginClient


def createRefreshTimer(mainWindow, inc):
    def inner_refreshTimer():
        while True:
            # 检查是否已登录，如果已登录则跳过刷新，避免干扰
            if hasattr(mainWindow, 'is_need_login') and mainWindow.is_need_login == 1:
                # 已登录，跳过刷新
                time.sleep(inc)
                continue
            
            # 改为日志记录，减少控制台输出
            log_msg = f'任务7：定时刷新页面 {time.strftime("%Y-%m-%d %H:%M:%S")}'
            log_business('refreshTimer', log_msg, 'info')
            log_print('refresh', log_msg)
            SystemsUsers.refreshTimer(mainWindow)  # 自动刷新
            time.sleep(inc)

    return inner_refreshTimer


def createSyncUserInfoTimer(mainWindow, inc):
    def inner_syncUserInfoTimer():
        while True:
            try:
                sync_msg = f'任务8： 同步用户信息 {time.strftime("%Y-%m-%d %H:%M:%S")}'
                log_business('syncUserInfo', sync_msg, 'info')
                AgentUser.syncUserInfoTimer(5, mainWindow)
            except Exception as e:
                error_msg = f'同步用户信息异常: {e}'
                if ENABLE_DETAILED_LOGS:
                    print(f'同步用户信息错误: {error_msg}')
                # 在这里可以添加其他异常处理逻辑，比如记录日志或者尝试重新同步用户信息
            time.sleep(inc)

    return inner_syncUserInfoTimer


def createGetUserBetDescByApi(mainWindow, inc):
    def inner_getUserBetDescByApi():
        while True:
            try:
                if mainWindow.agent_browser:
                    # 改为日志记录，减少控制台输出
                    log_msg = f'任务9：日志搜集 - 接口 {time.strftime("%Y-%m-%d %H:%M:%S")}'
                    log_business('getUserBetDescByApi', log_msg, 'info')
                    AgentUser.getUserBetDescByApi(mainWindow)  # 日志搜集 - 接口
            except Exception as e:
                error_msg = f'日志搜集任务异常: {e}'
                if ENABLE_DETAILED_LOGS:
                    print(f'用户注单描述错误: {error_msg}')
                SystemsUsers.pushErrorLog(f'日志搜集任务异常: {e}', mainWindow.access_token, mainWindow.lottery_type, [str(e)])
            finally:
                time.sleep(inc)

    return inner_getUserBetDescByApi


def createBettingTaskHealthCheck(mainWindow, inc):
    """下注任务健康检查 - 确保下注任务正常运行"""
    def inner_betting_health_check():
        while True:
            try:
                current_time = time.time()
                
                # 检查下注任务是否在运行
                if hasattr(mainWindow, '_betting_task_stats'):
                    stats = mainWindow._betting_task_stats
                    last_execution = stats.get('last_execution_time', 0)
                    execution_count = stats.get('execution_count', 0)
                    
                    # 如果超过2分钟没有执行，发出警告
                    if current_time - last_execution > 120:
                        warning_msg = f"⚠️ 下注任务超过2分钟未执行 (最后执行: {int(current_time - last_execution)}秒前)"
                        log_print('bet_tasks', warning_msg)
                        SystemsUsers.pushErrorLog(f'下注任务健康检查: {warning_msg}', mainWindow.access_token, mainWindow.lottery_type, [warning_msg])
                    
                    # 记录健康检查状态
                    if execution_count > 0:
                        log_print('bet_tasks', f"📊 下注任务健康检查: 已执行{execution_count}次，最后执行{int(current_time - last_execution)}秒前")
                else:
                    log_print('bet_tasks', "⚠️ 下注任务统计信息未初始化")
                
                time.sleep(inc)
            except Exception as e:
                log_print('bet_tasks', f"❌ 下注任务健康检查异常: {e}")
                time.sleep(inc)
    
    return inner_betting_health_check


def createSystemHealthCheck(mainWindow, inc):
    """系统健康检查任务 - 监控系统状态并自动恢复"""
    def inner_health_check():
        while True:
            try:
                current_time = time.strftime('%Y-%m-%d %H:%M:%S')
                log_msg = f'系统健康检查: {current_time}'
                log_business('systemHealthCheck', log_msg, 'info')
                log_print('health_check', log_msg)
                
                # 检查内存使用
                import psutil
                memory = psutil.virtual_memory()
                if memory.percent > 80:
                    warning_msg = f'警告: 内存使用率过高: {memory.percent}%'
                    if ENABLE_DETAILED_LOGS:
                        print(f'系统健康检查警告: {warning_msg}')
                    # 强制垃圾回收
                    import gc
                    gc.collect()
                    cleanup_msg = '已执行内存清理'
                    if ENABLE_DETAILED_LOGS:
                        print(f'系统健康检查: {cleanup_msg}')
                
                # 检查磁盘空间
                try:
                    disk = psutil.disk_usage('C:\\')  # Windows系统
                except:
                    try:
                        disk = psutil.disk_usage('/')  # Linux/Mac系统
                    except:
                        disk = None
                
                if disk and disk.percent > 90:
                    disk_warning_msg = f'警告: 磁盘空间不足: {disk.percent}%'
                    if ENABLE_DETAILED_LOGS:
                        print(f'系统健康检查磁盘警告: {disk_warning_msg}')
                
                # 检查网络连接
                try:
                    import requests
                    response = requests.get('http://www.baidu.com', timeout=5)
                    if response.status_code != 200:
                        network_warning_msg = '警告: 网络连接异常'
                        if ENABLE_DETAILED_LOGS:
                            print(f'系统健康检查网络警告: {network_warning_msg}')
                except Exception as e:
                    network_error_msg = f'网络连接检查失败: {e}'
                    if ENABLE_DETAILED_LOGS:
                        print(f'系统健康检查网络错误: {network_error_msg}')
                
                # 检查任务管理器状态
                if hasattr(mainWindow, 'task_manager') and mainWindow.task_manager:
                    active_tasks = sum(1 for task in mainWindow.task_manager.tasks.values() 
                                    if task.get('is_running', False))
                    task_status_msg = f'当前活跃任务数: {active_tasks}'
                    log_business('systemHealthCheck', task_status_msg, 'info')
                    
                    # 检查是否有任务长时间运行
                    current_time_ts = time.time()
                    for task_name, task_info in mainWindow.task_manager.tasks.items():
                        if task_info.get('is_running', False):
                            last_active = task_info.get('last_active', 0)
                            if current_time_ts - last_active > 300:  # 5分钟无活动
                                task_warning_msg = f'警告: 任务 {task_name} 可能卡死，最后活动时间: {time.strftime("%H:%M:%S", time.localtime(last_active))}'
                                if ENABLE_DETAILED_LOGS:
                                    print(f'系统健康检查任务警告: {task_warning_msg}')
                
                # 检查Chrome进程状态
                try:
                    chrome_processes = []
                    for proc in psutil.process_iter(['pid', 'name', 'memory_info']):
                        if 'chrome' in proc.info['name'].lower():
                            chrome_processes.append(proc.info)
                    
                    if chrome_processes:
                        total_memory = sum(proc['memory_info'].rss for proc in chrome_processes)
                        chrome_status_msg = f'Chrome进程数: {len(chrome_processes)}, 总内存占用: {total_memory / 1024 / 1024:.1f} MB'
                        log_business('systemHealthCheck', chrome_status_msg, 'info')
                        
                        # 如果Chrome占用内存过多，给出警告
                        if total_memory > 1024 * 1024 * 1024:  # 1GB
                            chrome_warning_msg = '警告: Chrome占用内存过多，建议重启浏览器'
                            log_business('systemHealthCheck', chrome_warning_msg, 'warning')
                except Exception as e:
                    chrome_error_msg = f'检查Chrome进程状态失败: {e}'
                    if ENABLE_DETAILED_LOGS:
                        print(f'系统健康检查Chrome错误: {chrome_error_msg}')
                
                complete_msg = f'系统健康检查完成: {current_time}'
                log_business('systemHealthCheck', complete_msg, 'info')
                log_print('health_check', complete_msg)
                
            except Exception as e:
                error_msg = f'系统健康检查异常: {e}'
                if ENABLE_DETAILED_LOGS:
                    print(f'系统健康检查错误: {error_msg}')
            finally:
                time.sleep(inc)

    return inner_health_check


def createInputBufferMonitor(mainWindow, inc):
    """输入缓冲区监控任务 - 检测并解决输入阻塞问题"""
    def inner_input_monitor():
        while True:
            try:
                current_time = time.strftime('%Y-%m-%d %H:%M:%S')
                
                # 检查是否有输入阻塞
                import msvcrt  # Windows专用
                import sys
                
                # 尝试非阻塞方式检查输入缓冲区
                try:
                    # 检查stdin是否有数据可读
                    if msvcrt.kbhit():
                        # 有按键输入，清空缓冲区
                        key = msvcrt.getch()
                        input_msg = f'检测到按键输入: {key}, 时间: {current_time}'
                        if ENABLE_DETAILED_LOGS:
                            print(f'输入监控: {input_msg}')
                        
                        # 如果检测到输入阻塞，记录日志
                        SystemsUsers.pushErrorLog('检测到输入缓冲区阻塞', mainWindow.access_token, mainWindow.lottery_type, ['按键输入: ' + str(key)])
                        
                except Exception as e:
                    # 如果msvcrt不可用，尝试其他方法
                    pass
                
                # 检查控制台状态
                try:
                    # 检查是否有任务在等待输入
                    import threading
                    for thread_id, frame in threading._active.items():
                        if hasattr(frame, '_block'):
                            # 检查线程是否在等待输入
                            if hasattr(frame, '_input_waiting') and frame._input_waiting:
                                thread_warning_msg = f'警告: 线程 {thread_id} 可能在等待输入，时间: {current_time}'
                                if ENABLE_DETAILED_LOGS:
                                    print(f'输入监控线程警告: {thread_warning_msg}')
                                SystemsUsers.pushErrorLog('检测到线程等待输入', mainWindow.access_token, mainWindow.lottery_type, ['线程ID: ' + str(thread_id)])
                except Exception as e:
                    pass
                
                # 定期输出心跳信号，确保控制台响应
                if int(time.time()) % 60 == 0:  # 每分钟输出一次
                    heartbeat_msg = f'输入监控心跳: {current_time}'
                    log_business('inputBufferMonitor', heartbeat_msg, 'info')
                
            except Exception as e:
                error_msg = f'输入缓冲区监控异常: {e}'
                if ENABLE_DETAILED_LOGS:
                    print(f'输入监控错误: {error_msg}')
            finally:
                time.sleep(inc)

    return inner_input_monitor


def createBrowserWindowManager(mainWindow, inc):
    """浏览器窗口管理任务 - 确保只有一个浏览器窗口（只操作属于当前账户的窗口）"""
    def inner_browser_manager():
        account_id = getattr(mainWindow, 'access_token', 'default')
        while True:
            try:
                # 关键修复：无论是否登录，都检查窗口数量
                # 因为未登录时也可能有多个窗口（登录重试时创建的）
                if hasattr(mainWindow, 'driver') and mainWindow.driver:
                    try:
                        # 关键修复：先验证driver是否连接到当前账户的端口
                        # 避免误操作其他账户的窗口
                        current_port = None
                        if hasattr(mainWindow, 'port') and mainWindow.port:
                            current_port = mainWindow.port
                        elif hasattr(mainWindow, 'port_manager') and mainWindow.port_manager:
                            current_port = mainWindow.port_manager.debug_port
                        
                        if not current_port:
                            if ENABLE_DETAILED_LOGS:
                                print(f"⚠️ [窗口管理-账户={account_id}] 无法获取端口，跳过窗口检查")
                            time.sleep(inc)
                            continue
                        
                        # 验证driver端口
                        driver_port = None
                        try:
                            command_executor = getattr(mainWindow.driver, 'command_executor', None)
                            if command_executor:
                                executor_url = str(command_executor._url) if hasattr(command_executor, '_url') else str(command_executor)
                                import re
                                port_match = re.search(r':(\d+)(?:/|$)', executor_url)
                                if port_match:
                                    driver_port = int(port_match.group(1))
                        except:
                            pass
                        
                        if driver_port and driver_port != current_port:
                            if ENABLE_DETAILED_LOGS:
                                print(f"⚠️ [窗口管理-账户={account_id}] Driver端口({driver_port})与账户端口({current_port})不匹配，跳过窗口检查（避免误关闭其他账户窗口）")
                            time.sleep(inc)
                            continue
                        
                        # 先检查窗口数量
                        window_handles = mainWindow.driver.window_handles
                        if len(window_handles) > 1:
                            print(f"🔄 [窗口管理-账户={account_id}, 端口={current_port}] 检测到 {len(window_handles)} 个浏览器窗口，开始清理（仅清理属于本账户的窗口）...")
                            
                            # 优先使用新的浏览器窗口管理器（已包含端口验证）
                            if mainWindow.browser_window_manager:
                                try:
                                    result = mainWindow.browser_window_manager.ensure_single_window()
                                    if result:
                                        print(f"✅ [窗口管理-账户={account_id}] 窗口清理完成（使用窗口管理器）")
                                    else:
                                        print(f"⚠️ [窗口管理-账户={account_id}] 窗口清理失败或跳过（端口验证未通过）")
                                except Exception as e:
                                    print(f"⚠️ [窗口管理-账户={account_id}] 窗口管理器清理异常: {e}，使用备用方法")
                                    # 备用方案：使用原有方法（也包含端口验证）
                                    mainWindow.ensure_single_browser_window()
                            else:
                                # 备用方案：使用原有方法（也包含端口验证）
                                mainWindow.ensure_single_browser_window()
                                print(f"✅ [窗口管理-账户={account_id}] 窗口清理完成（使用备用方法）")
                        # 如果已登录，也监控浏览器进程状态
                        if hasattr(mainWindow, 'is_need_login') and mainWindow.is_need_login == 1:
                            if mainWindow.browser_window_manager:
                                try:
                                    status = mainWindow.browser_window_manager.monitor_browser_processes()
                                    if not status['is_healthy']:
                                        print(f"⚠️ [窗口管理-账户={account_id}] 浏览器进程状态异常，尝试恢复...")
                                        mainWindow.browser_window_manager.kill_existing_browser_processes()
                                except Exception as e:
                                    print(f"⚠️ [窗口管理-账户={account_id}] 监控浏览器进程异常: {e}")
                    except Exception as driver_e:
                        # driver可能已断开，跳过本次检查
                        if ENABLE_DETAILED_LOGS:
                            print(f"⚠️ [窗口管理-账户={account_id}] 检查driver异常: {driver_e}")
                
                time.sleep(inc)
            except Exception as e:
                error_msg = f'浏览器窗口管理异常: {e}'
                log_business('browserWindowManager', error_msg, 'error')
                # 异常时等待更长时间
                time.sleep(min(inc * 2, 300))  # 最多等待5分钟

    return inner_browser_manager


def createBrowserProcessMonitor(mainWindow, inc):
    """浏览器进程监控任务 - 防止Chrome/Firefox进程过多导致卡住"""
    def inner_browser_monitor():
        while True:
            try:
                # 只检查用户选择的浏览器类型
                selected_browser = getattr(mainWindow, 'browser_type', 'chrome').lower()
                browser_count = 0
                total_memory = 0
                
                # 根据用户选择只检测对应的浏览器
                target_browser = 'chrome' if selected_browser == 'chrome' else 'firefox'
                
                for proc in psutil.process_iter(['pid', 'name', 'memory_info']):
                    try:
                        proc_name = proc.info['name'].lower()
                        if target_browser in proc_name:
                            browser_count += 1
                            total_memory += proc.info['memory_info'].rss
                    except (psutil.NoSuchProcess, psutil.AccessDenied):
                        continue
                
                # 只对用户选择的浏览器进行进程数量检查
                if browser_count > MAX_CHROME_PROCESSES:
                    print(f"⚠️ {target_browser.capitalize()}进程过多({browser_count})，尝试清理...")
                    try:
                        if hasattr(mainWindow, 'driver') and mainWindow.driver:
                            # 先检查窗口数量
                            window_handles = mainWindow.driver.window_handles
                            if len(window_handles) > 1:
                                # 只清理机器人管理的浏览器窗口，不影响用户浏览器
                                mainWindow.ensure_single_browser_window()
                                print(f"✅ 已清理多余的机器人浏览器窗口 (从{len(window_handles)}个减少到1个)")
                            else:
                                print("ℹ️ 机器人浏览器窗口数量正常，无需清理")
                    except Exception as cleanup_ex:
                        print(f"⚠️ 清理浏览器窗口失败: {cleanup_ex}")
                
                # 如果内存占用过多，执行垃圾回收
                if total_memory > MAX_MEMORY_MB * 1024 * 1024:  # 转换为字节
                    print(f"⚠️ 浏览器内存占用过高({total_memory / 1024 / 1024:.1f}MB)，执行垃圾回收...")
                    import gc
                    gc.collect()
                    print("✅ 已执行内存清理")
                
                time.sleep(inc)
            except Exception as e:
                print(f'浏览器进程监控异常: {e}')
                time.sleep(inc)

    return inner_browser_monitor


def createSystemHangDetector(mainWindow, inc):
    """系统卡住检测任务 - 检测并处理系统卡住情况"""
    def inner_hang_detector():
        last_activity_time = time.time()
        hang_threshold = HANG_DETECTION_SECONDS
        
        while True:
            try:
                current_time = time.time()
                
                # 检查系统是否卡住
                if current_time - last_activity_time > hang_threshold:
                    print(f"🚨 检测到系统可能卡住！距离上次活动: {current_time - last_activity_time:.1f}秒")
                    
                    # 尝试恢复系统
                    try:
                        # 强制垃圾回收
                        import gc
                        gc.collect()
                        print("✅ 已执行内存清理")
                        
                        # 检查并清理浏览器进程
                        try:
                            if hasattr(mainWindow, 'driver') and mainWindow.driver:
                                # 先检查窗口数量
                                window_handles = mainWindow.driver.window_handles
                                if len(window_handles) > 1:
                                    mainWindow.ensure_single_browser_window()
                                    print(f"✅ 已清理多余的浏览器窗口 (从{len(window_handles)}个减少到1个)")
                                else:
                                    print("ℹ️ 浏览器窗口数量正常，无需清理")
                        except Exception:
                            pass
                        
                        # 重置活动时间
                        last_activity_time = current_time
                        
                    except Exception as recovery_ex:
                        print(f"❌ 系统恢复失败: {recovery_ex}")
                
                # 更新活动时间（如果系统有活动）
                if current_time - last_activity_time < 30:  # 如果最近有活动
                    last_activity_time = current_time
                
                time.sleep(inc)
            except Exception as e:
                print(f'系统卡住检测异常: {e}')
                time.sleep(inc)

    return inner_hang_detector


def createTaskTimeoutDetector(mainWindow, inc):
    """任务超时检测 - 检测长时间运行的任务并强制结束"""
    def inner_timeout_detector():
        while True:
            try:
                if hasattr(mainWindow, 'task_manager') and mainWindow.task_manager:
                    current_time = time.time()
                    timeout_threshold = 300  # 5分钟超时
                    
                    for task_name, task_info in mainWindow.task_manager.tasks.items():
                        if task_info.get('is_running', False):
                            last_active = task_info.get('last_active', 0)
                            if current_time - last_active > timeout_threshold:
                                print(f"⚠️ 任务 {task_name} 超时({current_time - last_active:.1f}秒)，尝试重置...")
                                try:
                                    # 重置任务状态
                                    task_info['is_running'] = False
                                    task_info['last_active'] = current_time
                                    print(f"✅ 任务 {task_name} 已重置")
                                except Exception as reset_ex:
                                    print(f"❌ 重置任务 {task_name} 失败: {reset_ex}")
                
                time.sleep(inc)
            except Exception as e:
                print(f'任务超时检测异常: {e}')
                time.sleep(inc)

    return inner_timeout_detector


def createMemoryMonitor(mainWindow, inc):
    """内存监控任务 - 监控系统内存使用并自动清理"""
    def inner_memory_monitor():
        while True:
            try:
                # 获取系统内存使用情况
                memory_info = psutil.virtual_memory()
                memory_percent = memory_info.percent
                
                # 如果内存使用率超过80%，执行清理
                if memory_percent > 80:
                    print(f"⚠️ 系统内存使用率过高({memory_percent:.1f}%)，执行清理...")
                    
                    # 强制垃圾回收
                    import gc
                    gc.collect()
                    
                    # 清理浏览器进程
                    try:
                        if hasattr(mainWindow, 'driver') and mainWindow.driver:
                            mainWindow.ensure_single_browser_window()
                    except Exception:
                        pass
                    
                    print("✅ 内存清理完成")
                
                time.sleep(inc)
            except Exception as e:
                print(f'内存监控异常: {e}')
                time.sleep(inc)

    return inner_memory_monitor


def createSmartLoginManager(mainWindow, inc):
    """智能登录管理任务 - 统一处理登录逻辑"""
    def inner_smart_login_manager():
        while True:
            try:
                # 检查是否在计划登录时间（07:55-08:00）
                if is_scheduled_login_time():
                    if ENABLE_DETAILED_LOGS:
                        print("⏰ 检测到计划登录时间，开始执行智能登录...")
                    
                    # 执行智能登录
                    login_success = mainWindow.smart_login_management()
                    
                    if login_success:
                        log_msg = f"智能登录成功: {time.strftime('%Y-%m-%d %H:%M:%S')}"
                        log_business('smartLoginManager', log_msg, 'info')
                    else:
                        log_msg = f"智能登录失败: {time.strftime('%Y-%m-%d %H:%M:%S')}"
                        log_business('smartLoginManager', log_msg, 'warning')
                
                # 检查当前登录状态，如果未登录，立即尝试登录
                elif not get_global_login_status():
                    if ENABLE_DETAILED_LOGS:
                        print("🔄 检测到未登录状态，立即尝试执行登录...")
                    
                    # 执行智能登录
                    login_success = mainWindow.smart_login_management()
                    
                    if login_success:
                        log_msg = f"自动登录成功: {time.strftime('%Y-%m-%d %H:%M:%S')}"
                        log_business('smartLoginManager', log_msg, 'info')
                        
                        # 登录成功后，确保程序不会立即退出
                        if ENABLE_DETAILED_LOGS:
                            print("✅ 登录成功，程序将继续运行...")
                        
                        # 强制更新所有相关状态
                        try:
                            set_global_login_status(True)
                            mainWindow.is_need_login = 1
                            if ENABLE_DETAILED_LOGS:
                                print("✅ 登录成功后状态已同步")
                        except Exception as status_e:
                            if ENABLE_DETAILED_LOGS:
                                print(f"⚠️ 登录成功后状态同步失败: {status_e}")
                    else:
                        log_msg = f"自动登录失败: {time.strftime('%Y-%m-%d %H:%M:%S')}"
                        log_business('smartLoginManager', log_msg, 'warning')
                
            except Exception as e:
                error_msg = f'智能登录管理任务异常: {e}'
                if ENABLE_DETAILED_LOGS:
                    print(f'智能登录管理错误: {error_msg}')
                    import traceback
                    traceback.print_exc()
                log_business('smartLoginManager', error_msg, 'error')
            finally:
                time.sleep(inc)

    return inner_smart_login_manager


def cancelBetTimer(mainWindow, inc):
    def inner_cancelBet():
        while True:
            if ENABLE_DETAILED_LOGS:
                print('任务10：撤单 - 接口', time.strftime('%Y-%m-%d %H:%:%S'))
            Lucky.cancelBet(mainWindow)  # 撤单
            time.sleep(inc)

    return inner_cancelBet


def createCancelBetTimer(mainWindow, inc):
    task_lock = threading.Lock()
    last_run_time = 0
    init_msg = f"初始化撤单任务，config is_test={is_test}, mainWindow.is_test={mainWindow.is_test}"
    log_business('cancelBetTimer', init_msg, 'info')  # 记录到日志文件

    def inner_cancelBetTimer():
        nonlocal last_run_time
        while True:
            try:
                current_time = time.time()
                with task_lock:
                    if current_time - last_run_time < inc:
                        time.sleep(1)
                        continue

                    # 改为日志记录，减少控制台输出
                    status_msg = f"""
撤单任务状态检查:
- 全局 is_test: {is_test}
- mainWindow.is_test: {mainWindow.is_test}
- 当前时间: {time.strftime('%Y-%m-%d %H:%M:%S')}
                    """
                    log_business('cancelBetTimer', status_msg, 'info')

                    # 使用 int() 确保类型一致
                    if int(mainWindow.is_test) == 1:
                        start_msg = "开始执行撤单任务..."
                        if ENABLE_DETAILED_LOGS:
                            print(f'撤单任务: {start_msg}')
                        Lucky.cancelBetTimer(mainWindow, inc)
                        complete_msg = "撤单任务执行完成"
                        if ENABLE_DETAILED_LOGS:
                            print(f'撤单任务: {complete_msg}')
                    else:
                        skip_msg = f"非测试模式，跳过撤单 (mainWindow.is_test={mainWindow.is_test})"
                        log_business('cancelBetTimer', skip_msg, 'info')

                    last_run_time = current_time

            except Exception as e:
                error_msg = f"撤单任务异常: {str(e)}"
                if ENABLE_DETAILED_LOGS:
                    print(f'撤单任务错误: {error_msg}')
                time.sleep(5)

            time.sleep(1)

    return inner_cancelBetTimer


def createConnectionMonitor(mainWindow, inc):
    """连接监控任务 - 监控连接状态并自动恢复"""
    def inner_connection_monitor():
        while True:
            try:
                if get_connection_manager:
                    # 获取连接统计信息
                    stats = get_connection_manager().get_connection_stats()
                    
                    # 每5分钟记录一次连接状态
                    current_time = time.time()
                    if not hasattr(inner_connection_monitor, 'last_log_time'):
                        inner_connection_monitor.last_log_time = 0
                    
                    if current_time - inner_connection_monitor.last_log_time > 300:  # 5分钟
                        log_msg = f"连接状态监控 - 成功率: {stats['success_rate']:.1f}%, 连接重置错误: {stats['connection_reset_errors']}, 连续失败: {stats['consecutive_failures']}"
                        
                        # 如果检测到10054错误，尝试重连浏览器
                        if stats['connection_reset_errors'] > 0:
                            print("⚠️ 检测到连接重置错误(10054)，尝试重连浏览器...")
                            try:
                                if hasattr(mainWindow, 'driver') and mainWindow.driver:
                                    # 测试浏览器连接
                                    mainWindow.driver.current_url
                                    print("✅ 浏览器连接正常")
                                else:
                                    print("🔄 浏览器连接丢失，尝试重新连接...")
                                    # 重新连接到固定端口
                                    if hasattr(mainWindow, 'port') and mainWindow.port:
                                        mainWindow.start_browser()
                                        print(f"✅ 已重连到端口: {mainWindow.port}")
                            except Exception as reconnect_e:
                                print(f"⚠️ 重连浏览器失败: {reconnect_e}")
                                # 如果重连失败，尝试重启浏览器
                                try:
                                    if hasattr(mainWindow, 'force_close_all_browsers'):
                                        mainWindow.force_close_all_browsers()
                                    if hasattr(mainWindow, 'start_browser'):
                                        mainWindow.start_browser()
                                    print("✅ 已重启浏览器")
                                except Exception as restart_e:
                                    print(f"⚠️ 重启浏览器失败: {restart_e}")
                        log_business('connectionMonitor', log_msg, 'info')
                        log_print('connection_monitor', log_msg)
                        inner_connection_monitor.last_log_time = current_time
                    
                    # 如果连续失败过多，尝试重置连接
                    if stats['consecutive_failures'] >= 10:
                        print(f"🚨 连续失败{stats['consecutive_failures']}次，尝试重置连接...")
                        get_connection_manager().reset_all_connections()
                        log_business('connectionMonitor', f"连接重置触发 - 连续失败{stats['consecutive_failures']}次", 'warning')
                    
                    # 如果熔断器开启，记录日志
                    if stats['is_circuit_breaker_open']:
                        remaining = stats['backoff_remaining']
                        if remaining > 0:
                            log_msg = f"熔断器开启，剩余时间: {remaining:.1f}秒"
                            log_business('connectionMonitor', log_msg, 'warning')
                            log_print('connection_monitor', log_msg)
                
            except Exception as e:
                error_msg = f"连接监控任务异常: {e}"
                log_business('connectionMonitor', error_msg, 'error')
                log_print('connection_monitor', error_msg)
            finally:
                time.sleep(inc)
    
    return inner_connection_monitor


    def recreate_driver(self):
        """重新创建WebDriver，用于恢复浏览器连接"""
        try:
            print("🔄 开始重新创建WebDriver...")
            
            # 关闭现有的WebDriver
            if hasattr(self, 'driver') and self.driver:
                try:
                    self.driver.quit()
                    print("✅ 旧WebDriver已关闭")
                except Exception as e:
                    print(f"⚠️ 关闭旧WebDriver时出现异常: {e}")
                finally:
                    self.driver = None
            
            # 重新创建WebDriver
            try:
                from xy_client.services.tools.tools import getDriver
                new_driver = getDriver(self.getPreferredBrowser())
                
                if new_driver:
                    print("✅ 新WebDriver创建成功")
                    self.driver = new_driver
                    
                    # 尝试恢复登录状态
                    if self.restore_login_session():
                        print("✅ 登录会话恢复成功")
                        return new_driver
                    else:
                        print("⚠️ 登录会话恢复失败，需要重新登录")
                        return new_driver
                else:
                    print("❌ 新WebDriver创建失败")
                    return None
                    
            except Exception as e:
                print(f"❌ 创建新WebDriver异常: {e}")
                return None
                
        except Exception as e:
            print(f"❌ 重新创建WebDriver过程中发生异常: {e}")
            return None
    
    def restore_login_session(self):
        """尝试恢复登录会话"""
        try:
            if not self.driver:
                return False
            
            print("🔄 尝试恢复登录会话...")
            
            # 检查当前URL
            current_url = self.driver.current_url
            
            # 如果已经在游戏页面，尝试检查登录状态
            if any(keyword in current_url.lower() for keyword in ['member', 'game', 'bet', '下注', '游戏', 'home', 'index', 'main']):
                print("✅ 当前已在游戏页面，可能已登录")
                return True
            
            # 如果不在游戏页面，尝试导航到主页
            try:
                # 尝试访问主页（使用安全的页面打开方法）
                target_url = self.wp_domain or "http://localhost"
                try:
                    from xy_client.services.tools.PageRefreshManager import get_refresh_manager
                    refresh_manager = get_refresh_manager(page_load_timeout=20, max_retry=2)
                    success = refresh_manager.safe_get(self.driver, target_url, 
                                                      reason="恢复登录会话", 
                                                      check_loading=True, timeout=20)
                    if not success:
                        print("⚠️ 打开主页失败（已重试），但继续检查登录状态")
                except ImportError:
                    # 如果导入失败，使用原有的简单方式
                    self.driver.get(target_url)
                time.sleep(3)
                
                # 检查是否有用户信息
                user_elements = self.driver.find_elements(By.XPATH, "//*[contains(text(), '会员') or contains(text(), '账号')]")
                if user_elements:
                    print("✅ 找到用户信息，登录会话恢复成功")
                    return True
                else:
                    print("⚠️ 未找到用户信息，需要重新登录")
                    return False
                    
            except Exception as e:
                print(f"⚠️ 导航到主页失败: {e}")
                return False
                
        except Exception as e:
            print(f"❌ 恢复登录会话异常: {e}")
            return False

    # 删除重复的方法定义
            try:
                # 检查本程序使用的调试端口
                used_ports = []
                if hasattr(self, 'port'):
                    used_ports.append(self.port)
                if hasattr(self, 'agent_port'):
                    used_ports.append(self.agent_port)
                
                if ENABLE_DETAILED_LOGS:
                    print(f"🔍 本程序使用的调试端口: {used_ports}")
                
                # 查找使用这些端口的进程
                for port in used_ports:
                    try:
                        import socket
                        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
                            s.settimeout(0.1)
                            result = s.connect_ex(('localhost', port))
                            if result == 0:
                                # 端口被占用，查找占用进程
                                if ENABLE_DETAILED_LOGS:
                                    print(f"🔍 端口 {port} 被占用，查找占用进程...")
                                # 这里可以进一步查找占用端口的进程
                    except:
                        pass
            except Exception as e:
                if ENABLE_DETAILED_LOGS:
                    print(f"⚠️ 端口检查异常: {e}")
            
            # 方法2：通过进程树查找（更安全）
            try:
                current_process = psutil.Process(current_pid)
                if ENABLE_DETAILED_LOGS:
                    print(f"🔍 当前程序名称: {current_process.name()}")
                
                # 查找子进程
                children = current_process.children(recursive=True)
                if ENABLE_DETAILED_LOGS:
                    print(f"🔍 找到 {len(children)} 个子进程")
                
                for child in children:
                    try:
                        child_name = child.name().lower()
                        if ENABLE_DETAILED_LOGS:
                            print(f"🔍 子进程: {child.name()} (PID: {child.pid})")
                        
                        # 只终止浏览器相关进程
                        if any(browser in child_name for browser in ['chrome', 'firefox', 'chromedriver', 'geckodriver']):
                            if ENABLE_DETAILED_LOGS:
                                print(f"🔄 终止浏览器进程: {child.name()} (PID: {child.pid})")
                            child.terminate()
                            killed_count += 1
                        else:
                            if ENABLE_DETAILED_LOGS:
                                print(f"⏭️ 跳过非浏览器进程: {child.name()}")
                    except Exception as e:
                        if ENABLE_DETAILED_LOGS:
                            print(f"⚠️ 处理子进程异常: {e}")
                
            except Exception as e:
                if ENABLE_DETAILED_LOGS:
                    print(f"⚠️ 进程树查找异常: {e}")
            
            # 方法3：通过用户数据目录查找（最精确）
            try:
                # 查找使用本程序特定用户数据目录的Chrome进程
                if platform.system() == "Windows":
                    user_data_patterns = [
                        f"C:\\.temp\\9222\\*",  # 主登录
                        f"D:\\.temp\\9223\\*"   # 代理登录
                    ]
                else:
                    user_data_patterns = [
                        "/tmp/9222/*",  # 主登录
                        "/tmp/9223/*"   # 代理登录
                    ]
                
                if ENABLE_DETAILED_LOGS:
                    print(f"🔍 查找特定用户数据目录的进程...")
                
                # 这里可以进一步实现通过命令行参数查找进程的逻辑
                # 但由于复杂性，我们主要依赖方法2（进程树）
                
            except Exception as e:
                if ENABLE_DETAILED_LOGS:
                    print(f"⚠️ 用户数据目录查找异常: {e}")
            
            if killed_count > 0:
                if ENABLE_DETAILED_LOGS:
                    print(f"✅ 已终止 {killed_count} 个本程序启动的浏览器进程")
            else:
                if ENABLE_DETAILED_LOGS:
                    print("🔄 没有找到需要终止的浏览器进程")
                    
        except Exception as e:
            if ENABLE_DETAILED_LOGS:
                print(f"❌ 强制终止浏览器进程异常: {e}")


# @pysnooper.snoop()
def main(existing_app=None):
    try:
        # 立即激活输入阻塞预防，防止程序卡住等待输入
        print("🚀 启动程序，激活输入阻塞预防...")
        if activate_input_prevention():
            print("✅ 输入阻塞预防已激活，程序将完全自动化运行")
        else:
            print("⚠️ 输入阻塞预防激活失败，程序可能仍会等待输入")
        
        app = existing_app or QApplication(sys.argv)
        
        # 设置应用程序属性：防止主窗口意外关闭导致整个程序退出
        # 登录成功后需要保持客户端常驻，因此不因最后一个窗口关闭而退出
        app.setQuitOnLastWindowClosed(False)
        
        # 添加全局异常处理器
        def global_exception_handler(exc_type, exc_value, exc_traceback):
            print(f"🚨 全局异常捕获: {exc_type.__name__}: {exc_value}")
            traceback.print_exception(exc_type, exc_value, exc_traceback)
            # 不要退出程序，继续运行
        
        sys.excepthook = global_exception_handler
        
        # 添加PyQt5异常处理器
        def qt_exception_handler(exc_type, exc_value, exc_traceback):
            print(f"🚨 PyQt5异常捕获: {exc_type.__name__}: {exc_value}")
            traceback.print_exception(exc_type, exc_value, exc_traceback)
            # 不要退出程序，继续运行
        
        # 设置PyQt5异常处理器
        from PyQt5.QtCore import qInstallMessageHandler
        def qt_message_handler(msg_type, context, message):
            if msg_type == 4:  # QtCriticalMsg
                print(f"🚨 PyQt5关键消息: {message}")
            elif msg_type == 3:  # QtWarningMsg
                print(f"⚠️ PyQt5警告消息: {message}")
        
        qInstallMessageHandler(qt_message_handler)
        
        # 添加信号处理，确保程序能够快速响应退出信号
        import signal
        def signal_handler(signum, frame):
            if ENABLE_DETAILED_LOGS:
                print(f'🔄 收到信号 {signum}，开始快速退出...')
            app.quit()
            # 不强制退出，让程序正常关闭
            # import os
            # os._exit(0)
        
        # 注册信号处理器
        signal.signal(signal.SIGINT, signal_handler)
        signal.signal(signal.SIGTERM, signal_handler)
        
        mainWindow = MainWindow()

        if mainWindow.getRuntimeMode() == 'background':
            mainWindow.restore_background_session(show_message=False)
        
        # 在设置 is_test 之前先打印值
        if ENABLE_DETAILED_LOGS:
            print(f"初始化前 mainWindow.is_test = {mainWindow.is_test}")
        
        # 确保正确设置 is_test
        mainWindow.is_test = int(is_test)  # 使用 int() 确保类型一致
        
        # 设置后立即验证
        if ENABLE_DETAILED_LOGS:
            print(f"""
    初始化状态:
    - 配置文件 is_test: {is_test}
    - 全局 is_test: {is_test}
    - mainWindow.is_test: {mainWindow.is_test}
            """)

        print("🔄 准备显示主窗口...")
        mainWindow.show()
        print("✅ 主窗口已显示")
        mainWindow.is_test = is_test
        if ENABLE_DETAILED_LOGS:
            print('access_token:' + mainWindow.access_token, 'is_test', is_test)

        if mainWindow.is_need_login == 1:
            mainWindow.start_automation_tasks()
        
        print("🔄 准备启动任务管理器...")

        ######################  监控开始  #############################
        manager = TaskManager()
        # 暂时不添加任何任务，只保持UI可见，避免崩溃
        # manager.add_task('loginClient', createLoginClient(mainWindow, 60), 60)  # 检测掉线自动登陆 - 增加间隔避免冲突
        
        print("⚠️ 警告：当前为最小配置，未启用任何后台任务")
        print("⚠️ 程序只会显示UI界面，不会执行任何自动化操作")
        print("⚠️ 如需启用功能，请逐步取消注释任务")
        
        # 暂时禁用所有任务，直到确认程序稳定
        # manager.add_task("syncBalance", createSyncBalance(mainWindow, 60), 30)
        # manager.add_task("betTasks", createBetTasks(mainWindow, 2), 5)  # 下注任务 - 优化间隔提高效率
        # manager.add_task("getNowBetQiHao", createGetNowBetQiHao(mainWindow, 10), 30)  # 获取正在进行的期号
        # manager.add_task("connectionMonitor", createConnectionMonitor(mainWindow, 60), 120)  # 连接监控任务
        # manager.add_task("getNowKjData", createGetSiteNowKjData(mainWindow, 30), 30)  # 获取当前开奖号码 - 网盘接口
        # manager.add_task("getNowKjData2", createGetNowKjData2(mainWindow, 5), 30)  # 获取当前开奖号码2，获取页面上的开奖号码 - 网盘页面
        # manager.add_task('refreshTimer', createRefreshTimer(mainWindow, 300), 30)  # 定时刷新页面
        manager.add_task('getUserBetDescByApi', createGetUserBetDescByApi(mainWindow, 5), 30)  # 日志搜集 - 接口
        
        # 暂时禁用这些任 务
        # manager.add_task('systemHealthCheck', createSystemHealthCheck(mainWindow, 60), 45)  # 系统健康检查
        # manager.add_task('bettingTaskHealthCheck', createBettingTaskHealthCheck(mainWindow, 30), 60)  # 下注任务健康检查
        # manager.add_task('inputBufferMonitor', createInputBufferMonitor(mainWindow, 10), 30)  # 输入缓冲区监控
        
        # 关键修复：启用浏览器窗口管理任务，但使用更长的间隔，避免频繁检查
        # 每60秒检查一次窗口，确保只有一个窗口
        try:
            manager.add_task('browserWindowManager', createBrowserWindowManager(mainWindow, 60), 60)  # 浏览器窗口管理（每60秒检查一次）
            print("✅ 浏览器窗口管理任务已启动（每60秒检查一次）")
        except Exception as e:
            print(f"⚠️ 启动浏览器窗口管理任务失败: {e}")
        
        # 暂时禁用浏览器进程监控，避免崩溃
        # manager.add_task('browserProcessMonitor', createBrowserProcessMonitor(mainWindow, 10), 10)  # 浏览器进程监控
        # 暂时禁用可能有问题的任务
        # manager.add_task('systemHangDetector', createSystemHangDetector(mainWindow, 30), 30)  # 系统卡住检测
        # manager.add_task('taskTimeoutDetector', createTaskTimeoutDetector(mainWindow, 60), 60)  # 任务超时检测
        # manager.add_task('memoryMonitor', createMemoryMonitor(mainWindow, 30), 30)  # 内存监控
        
        # 暂时禁用智能登录管理任务，避免崩溃
        # manager.add_task('smartLoginManager', createSmartLoginManager(mainWindow, 30), 30)  # 智能登录管理
        
        if int(is_test) == 1:
            if ENABLE_DETAILED_LOGS:
                print("添加撤单任务前确认: mainWindow.is_test =", mainWindow.is_test)
            manager.add_task('cancelBetTimer', createCancelBetTimer(mainWindow, 5), 30)
            if ENABLE_DETAILED_LOGS:
                print("撤单任务已添加，mainWindow.is_test =", mainWindow.is_test)
        # 启动任务监控
        monitor_thread = threading.Thread(target=task_monitor, args=(manager,))
        monitor_thread.daemon = True  # 设置为守护线程，主程序退出时自动结束
        monitor_thread.start()

        # 启动所有任务
        print(f"🔄 准备启动 {len(manager.tasks)} 个任务...")
        print("ℹ️ 注意：当前为最小配置，未启用任何后台任务")
        print("   - 所有任务已暂时禁用，避免崩溃")
        print("   - 程序只会显示UI界面，不会执行自动化操作")
        print("   - 如需启用功能，请逐步取消注释任务")
        for i, task_name in enumerate(manager.tasks):
            print(f'🔄 启动任务 {i+1}/{len(manager.tasks)}: {task_name}')
            try:
                manager.start_task(task_name)
                print(f'✅ 任务 {task_name} 启动成功')
            except Exception as e:
                print(f'❌ 任务 {task_name} 启动失败: {e}')
                import traceback
                traceback.print_exc()
                # 不要因为单个任务失败就退出程序
                continue
        print("✅ 所有任务启动完成")
        ######################  监控结束  #############################
        print("✅ 所有任务已启动，准备进入事件循环...")
        print(f"📊 当前任务数量: {len(manager.tasks)}")
        print("🔄 准备启动用户信息同步定时器...")

        # SystemsUsers.syncBalanceTimer(60, mainWindow)  # 同步余额 - 网盘页面
        # SystemsUsers.betTasksTimer(5, mainWindow)  # 下注任务
        # SystemsUsers.getNowBetQihaoTimer(30, mainWindow)  # 获取正在进行的期号 - 机器人接口
        # SystemsUsers.getNowKjDataTimer(30, mainWindow)  # 获取当前开奖号码 - 网盘接口
        # SystemsUsers.getNowKjDataTimer2(5, mainWindow)  # 获取当前开奖号码2，获取页面上的开奖号码 - 网盘页面
        # SystemsUsers.loginClient(60, mainWindow)  # 检测掉线自动登陆
        # SystemsUsers.refreshTimer(300, mainWindow)  # 自动刷新
        # 暂时禁用所有定时器，确保程序稳定运行
        print("⚠️ 当前为最小化配置，已禁用所有定时器，确保程序稳定运行")
        print("   如需启用功能，请逐步取消注释定时器")
        """
        try:
            print("🔄 准备启动用户信息同步定时器...")
            AgentUser.syncUserInfoTimer(5, mainWindow)  # 同步用户信息
            print("✅ 用户信息同步定时器启动成功")
        except Exception as e:
            print(f"❌ 用户信息同步定时器启动失败: {e}")
            import traceback
            traceback.print_exc()
            # 不要因为同步失败就退出程序
            print("⚠️ 用户信息同步定时器启动失败，但程序继续运行")
        """
        # Lucky.cancelBetTimer(25, mainWindow)  # 下注撤销任务
        ## SystemsUsers.getDrawCodeClient(60, mainWindow)  # 检测掉线自动登陆
        ##AgentUser.getUserImportBetDescByApi(5, mainWindow)  # 文本导入注单搜集 - 接口

        # 设置应用程序退出时的清理函数
        def cleanup_on_exit():
            if ENABLE_DETAILED_LOGS:
                print('🔄 应用程序退出，开始清理...')
            try:
                # 快速停止任务管理器
                if hasattr(manager, 'stop_all_tasks'):
                    manager.stop_all_tasks(timeout=1)
                if ENABLE_DETAILED_LOGS:
                    print('✅ 任务管理器已停止')
            except Exception as e:
                if ENABLE_DETAILED_LOGS:
                    print(f'⚠️ 停止任务管理器异常: {e}')
        
        # 注册退出时的清理函数
        print("🔄 准备注册退出清理函数...")
        app.aboutToQuit.connect(cleanup_on_exit)
        print("✅ 退出清理函数已注册")
        
        print("🔄 启动PyQt5事件循环...")
        print("==========================================")
        print("🚀 程序已完全启动，进入事件循环...")
        print("==========================================")
        
        # 确保主窗口可见
        if not mainWindow.isVisible():
            print("⚠️ 主窗口不可见，尝试显示...")
            mainWindow.show()
        
        # 确保应用程序状态正常
        if not app.instance():
            print("❌ 应用程序实例不存在！")
            raise RuntimeError("应用程序实例不存在")
        
        try:
            # 启动事件循环
            print(f"⏰ [main] 开始等待事件循环...")
            print(f"🔍 [main] 当前线程列表:")
            for t in threading.enumerate():
                print(f"   - {t.name} (daemon={t.daemon})")
            
            # 确保主窗口在事件循环中保持可见
            def keep_window_alive():
                if mainWindow.isVisible():
                    print("✅ 主窗口可见，程序正常运行")
                else:
                    print("⚠️ 主窗口不可见，尝试显示...")
                    mainWindow.show()
            
            # 设置一个定时器，定期检查窗口状态（可选，用于调试）
            # 暂时注释掉，避免干扰
            # QTimer.singleShot(1000, keep_window_alive)
            
            print("🔄 [main] 调用 app.exec_() 启动事件循环...")
            exit_code = app.exec_()
            print(f"🔄 [main] PyQt5事件循环已退出，退出码: {exit_code}")
            print(f"📅 [main] 退出时间: {time.strftime('%Y-%m-%d %H:%M:%S')}")
            print(f"🔍 [main] 退出时线程列表:")
            for t in threading.enumerate():
                print(f"   - {t.name} (daemon={t.daemon})")
        except KeyboardInterrupt:
            print("🔸 [main] 收到键盘中断信号")
            exit_code = 0
        except SystemExit as e:
            print(f"🔸 [main] 收到SystemExit信号: {e}")
            exit_code = e.code if e.code else 0
        except Exception as e:
            print(f"❌ [main] PyQt5事件循环异常: {e}")
            import traceback
            traceback.print_exc()
            # 尝试保存错误日志
            try:
                with open('crash_log.txt', 'w', encoding='utf-8') as f:
                    f.write(f"PyQt5事件循环异常: {e}\n")
                    f.write(f"时间: {time.strftime('%Y-%m-%d %H:%M:%S')}\n")
                    traceback.print_exc(file=f)
                print("✅ 错误日志已保存到 crash_log.txt")
            except:
                pass
            exit_code = 1
        
        if ENABLE_DETAILED_LOGS:
            print(f'🔄 应用程序正常退出，退出码: {exit_code}')
        
        # 不强制退出，让程序正常关闭
        # import os
        # os._exit(exit_code)
        
    except KeyboardInterrupt:
        print('🔸 [main] 主程序收到键盘中断信号，开始快速退出...')
        # 不强制退出，让程序正常关闭
        sys.exit(0)
    except SystemExit as e:
        print(f'🔸 [main] 主程序收到SystemExit信号: {e}')
        sys.exit(e.code if e.code else 0)
    except BaseException as e:
        print(f'❌ [main] 主程序异常: {type(e).__name__}: {e}')
        import traceback
        traceback.print_exc()
        sys.exit(1)
    except Exception as e:
        print(f'主程序main异常: {type(e).__name__}: {e}')
        traceback.print_exc()
        
        # 保存详细的崩溃日志
        try:
            with open('main_crash_log.txt', 'w', encoding='utf-8') as f:
                f.write(f"主程序main异常: {e}\n")
                f.write(f"异常类型: {type(e).__name__}\n")
                f.write(f"时间: {time.strftime('%Y-%m-%d %H:%M:%S')}\n")
                f.write(f"异常参数: {e.args}\n")
                f.write("=" * 50 + "\n")
                traceback.print_exc(file=f)
            print("✅ 主程序崩溃日志已保存到 main_crash_log.txt")
        except Exception as log_ex:
            print(f"⚠️ 保存崩溃日志失败: {log_ex}")
        
        try:
            pushErrorLog('主程序main异常', access_token, lottery_type, e.args)
        except Exception as e2:
            pass
        
        # 不要立即退出，尝试恢复
        print('🔄 尝试从异常中恢复...')
        try:
            # 强制垃圾回收
            import gc
            gc.collect()
            
            # 清理Chrome进程
            try:
                if 'mainWindow' in locals() and hasattr(mainWindow, 'driver') and mainWindow.driver:
                    mainWindow.ensure_single_browser_window()
            except Exception:
                pass
            
            # 等待一段时间后重试
            time.sleep(5)
            print('🔄 异常恢复完成，程序继续运行...')
        except Exception as recovery_ex:
            print(f'❌ 异常恢复失败: {recovery_ex}')
            # 不退出程序，继续运行，让用户手动处理
            print('⚠️ 程序将继续运行，请检查日志并手动处理问题')
            # 注释掉自动退出，让程序继续运行
            # import os
            # os._exit(1)


def check_network_status():
    """检测网络状态"""
    if ENABLE_DETAILED_LOGS:
        print("🔍 开始检测网络状态...")
    
    # 检测基本网络连接
    test_urls = [
        ('百度', 'http://www.baidu.com'),
        #('腾讯', 'http://www.qq.com'),
        #('阿里云', 'http://www.aliyun.com'),
        ('机器人接口', robot_domain)
    ]
    
    network_status = {}
    
    for name, url in test_urls:
        try:
            if ENABLE_DETAILED_LOGS:
                print(f"正在检测 {name} ({url})...")
            response = requests.get(url, timeout=10, proxies={})
            if response.status_code == 200:
                network_status[name] = {
                    'status': '✅ 正常',
                    'response_time': response.elapsed.total_seconds(),
                    'status_code': response.status_code
                }
                if ENABLE_DETAILED_LOGS:
                    print(f"✅ {name}: 连接正常 (响应时间: {response.elapsed.total_seconds():.2f}秒)")
            else:
                network_status[name] = {
                    'status': '⚠️ 异常',
                    'response_time': None,
                    'status_code': response.status_code
                }
                if ENABLE_DETAILED_LOGS:
                    print(f"⚠️ {name}: HTTP状态码异常 ({response.status_code})")
        except requests.exceptions.ConnectionError as e:
            network_status[name] = {
                'status': '❌ 连接失败',
                'response_time': None,
                'error': str(e)
            }
            if ENABLE_DETAILED_LOGS:
                print(f"❌ {name}: 连接失败 - {e}")
        except requests.exceptions.Timeout as e:
            network_status[name] = {
                'status': '⏰ 超时',
                'response_time': None,
                'error': str(e)
            }
            if ENABLE_DETAILED_LOGS:
                print(f"⏰ {name}: 请求超时 - {e}")
        except Exception as e:
            network_status[name] = {
                'status': '❓ 未知错误',
                'response_time': None,
                'error': str(e)
            }
            if ENABLE_DETAILED_LOGS:
                print(f"❓ {name}: 未知错误 - {e}")
    
    # 检测本地网络配置
    try:
        import socket
        hostname = socket.gethostname()
        local_ip = socket.gethostbyname(hostname)
        if ENABLE_DETAILED_LOGS:
            print(f"🌐 本地网络信息:")
            print(f"   主机名: {hostname}")
            print(f"   本地IP: {local_ip}")
    except Exception as e:
        if ENABLE_DETAILED_LOGS:
            print(f"❌ 获取本地网络信息失败: {e}")
    
    # 检测DNS解析
    try:
        import socket
        test_domains = ['www.baidu.com', 'www.qq.com', 'www.aliyun.com']
        if ENABLE_DETAILED_LOGS:
            print(f"🔍 DNS解析检测:")
        for domain in test_domains:
            try:
                ip = socket.gethostbyname(domain)
                if ENABLE_DETAILED_LOGS:
                    print(f"   {domain} -> {ip}")
            except Exception as e:
                if ENABLE_DETAILED_LOGS:
                    print(f"   {domain} -> 解析失败: {e}")
    except Exception as e:
        if ENABLE_DETAILED_LOGS:
            print(f"❌ DNS检测失败: {e}")
    
    # 检测端口连通性
    try:
        import socket
        test_ports = [80, 443, 8080, 8090]
        if ENABLE_DETAILED_LOGS:
            print(f"🔌 端口连通性检测:")
        for port in test_ports:
            try:
                with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
                    s.settimeout(3)
                    result = s.connect_ex(('8.8.8.8', port))
                    if result == 0:
                        if ENABLE_DETAILED_LOGS:
                            print(f"   端口 {port}: ✅ 可访问")
                    else:
                        if ENABLE_DETAILED_LOGS:
                            print(f"   端口 {port}: ❌ 不可访问")
            except Exception as e:
                if ENABLE_DETAILED_LOGS:
                    print(f"   端口 {port}: ❓ 检测失败: {e}")
    except Exception as e:
        if ENABLE_DETAILED_LOGS:
            print(f"❌ 端口检测失败: {e}")
    
    # 总结网络状态
    if ENABLE_DETAILED_LOGS:
        print(f"\n📊 网络状态总结:")
        success_count = sum(1 for status in network_status.values() if '正常' in status['status'])
        total_count = len(network_status)
        print(f"   总检测站点: {total_count}")
        print(f"   正常连接: {success_count}")
        print(f"   异常连接: {total_count - success_count}")
        
        if success_count == total_count:
            print("🎉 网络状态: 完全正常")
        elif success_count > total_count // 2:
            print("⚠️ 网络状态: 部分异常，但基本可用")
        else:
            print("❌ 网络状态: 严重异常，建议检查网络配置")
    
    return network_status



if __name__ == "__main__":
    # 检查命令行参数
    if len(sys.argv) > 1 and sys.argv[1] == "--check-network":
        # 如果指定了 --check-network 参数，执行网络检测
        check_network_status()
    else:
        # 否则正常启动主程序
        main()

# ==================== 日志系统使用示例 ====================
"""
新的日志系统使用方法：

1. 基本业务日志记录：
   log_manager.log_business('bet_tasks', '开始执行下注任务', 'info')
   log_manager.log_business('sync_balance', '同步余额完成', 'info')

2. 关键信息日志（始终显示）：
   log_manager.log_key_info('下注成功：100元', 'bet_success')
   log_manager.log_key_info('开奖号码：12345', 'kj_push_success')
   log_manager.log_key_info('用户登录成功', 'user_info')

3. 系统日志：
   log_manager.log_system('系统启动完成', 'info')
   log_manager.log_system('内存使用率过高', 'warning', show_console=True)

4. 带日志类型的业务日志：
   log_manager.log('bet_tasks', '下注数据', 'info', 'bet_success')
   log_manager.log('kj_data', '开奖数据', 'info', 'kj_push_success')

5. 动态控制日志开关：
   log_manager.set_detailed_logs(True)   # 开启详细日志
   log_manager.set_detailed_logs(False)  # 关闭详细日志

优势：
- 无需在每个地方判断 ENABLE_DETAILED_LOGS
- 自动根据日志类型和级别决定是否显示
- 统一的日志格式，包含时间、业务类型、级别、内容
- 支持字符串、字典、列表等多种数据格式
- 向后兼容原有接口
"""
