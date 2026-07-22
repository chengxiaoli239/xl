"""
下注任务WebSocket客户端
用于实时接收后端推送的下注任务，替代轮询方式
"""

import json
import threading
import time
from typing import Optional, Callable
from xy_client.services.Lucky5.utils.log_optimizer import optimized_print


class BetTaskWebSocketClient:
    """下注任务WebSocket客户端 - 实时接收下注任务推送"""
    
    def __init__(self, main_window, access_token: str, robot_domain: str):
        """
        初始化WebSocket客户端
        
        Args:
            main_window: 主窗口实例
            access_token: 访问令牌
            robot_domain: 机器人服务器域名
        """
        self.main_window = main_window
        self.access_token = access_token
        self.robot_domain = robot_domain
        
        # 构建WebSocket URL
        ws_scheme = 'wss' if robot_domain.startswith('https') else 'ws'
        ws_host = robot_domain.replace('http://', '').replace('https://', '')
        self.ws_url = f"{ws_scheme}://{ws_host}/api/tz-system-users/bet-tasks-ws"
        
        self.ws = None
        self.connected = False
        self.reconnect_interval = 5  # 重连间隔（秒）
        self.max_reconnect_attempts = 10  # 最大重连次数
        self.reconnect_attempts = 0
        self._stop_event = threading.Event()
        self._reconnect_thread = None
        
    def connect(self) -> bool:
        """
        连接WebSocket服务器
        
        Returns:
            bool: 是否成功启动连接
        """
        try:
            # 检查是否已安装websocket-client库
            try:
                import websocket
            except ImportError:
                optimized_print("❌ [WebSocket] 未安装websocket-client库，无法使用WebSocket功能",
                               category='websocket', level='ERROR', force=True)
                optimized_print("💡 [WebSocket] 安装命令: pip install websocket-client",
                               category='websocket', level='INFO', force=True)
                return False
            
            # 构建WebSocket URL（包含认证信息）
            url = f"{self.ws_url}?access_token={self.access_token}&lottery_type=8"
            
            optimized_print(f"🔄 [WebSocket] 正在连接: {url}",
                           category='websocket', level='INFO', force=True)
            
            import websocket
            self.ws = websocket.WebSocketApp(
                url,
                on_message=self.on_message,
                on_error=self.on_error,
                on_close=self.on_close,
                on_open=self.on_open
            )
            
            # 在后台线程中运行
            ws_thread = threading.Thread(target=self._run_websocket, daemon=True)
            ws_thread.start()
            
            return True
        except Exception as e:
            optimized_print(f"❌ [WebSocket] 连接失败: {e}",
                           category='websocket', level='ERROR', force=True)
            return False
    
    def _run_websocket(self):
        """在后台线程中运行WebSocket"""
        try:
            import websocket
            self.ws.run_forever()
        except Exception as e:
            optimized_print(f"❌ [WebSocket] WebSocket运行异常: {e}",
                           category='websocket', level='ERROR', force=True)
    
    def on_message(self, ws, message):
        """接收到消息时的处理"""
        try:
            data = json.loads(message)
            message_type = data.get('type')
            
            if message_type == 'bet_task':
                # 收到下注任务，立即执行
                task_data = data.get('data')
                if task_data:
                    optimized_print(f"📨 [WebSocket] 收到下注任务推送，立即执行",
                                   category='websocket', level='INFO', force=True)
                    
                    # 触发下注任务执行
                    if hasattr(self.main_window, '_bet_task_manager'):
                        self.main_window._bet_task_manager.execute(direct=1)
                    else:
                        # 备用方案：使用Lucky.betTasksTimer
                        from xy_client.services.Lucky5 import Lucky
                        Lucky.betTasksTimer(self.main_window, direct=1)
            elif message_type == 'ping':
                # 心跳消息，回复pong
                self.send_message({'type': 'pong'})
            elif message_type == 'error':
                # 错误消息
                error_msg = data.get('message', '未知错误')
                optimized_print(f"⚠️ [WebSocket] 服务器错误: {error_msg}",
                               category='websocket', level='WARNING', force=True)
        except json.JSONDecodeError as e:
            optimized_print(f"❌ [WebSocket] 消息解析失败: {e}, 消息: {message[:100]}",
                           category='websocket', level='ERROR', force=True)
        except Exception as e:
            optimized_print(f"❌ [WebSocket] 消息处理异常: {e}",
                           category='websocket', level='ERROR', force=True)
            import traceback
            traceback.print_exc()
    
    def send_message(self, data: dict):
        """发送消息到服务器"""
        try:
            if self.ws and self.connected:
                message = json.dumps(data)
                self.ws.send(message)
                return True
            return False
        except Exception as e:
            optimized_print(f"❌ [WebSocket] 发送消息失败: {e}",
                           category='websocket', level='ERROR', force=True)
            return False
    
    def on_error(self, ws, error):
        """WebSocket错误处理"""
        optimized_print(f"⚠️ [WebSocket] 连接错误: {error}",
                       category='websocket', level='WARNING', force=True)
        self.connected = False
    
    def on_close(self, ws, close_status_code, close_msg):
        """WebSocket关闭处理"""
        self.connected = False
        optimized_print(f"⚠️ [WebSocket] 连接已关闭 (code: {close_status_code}, msg: {close_msg})",
                       category='websocket', level='WARNING', force=True)
        
        # 自动重连（如果未停止）
        if not self._stop_event.is_set():
            self._start_reconnect()
    
    def on_open(self, ws):
        """WebSocket连接成功"""
        self.connected = True
        self.reconnect_attempts = 0
        optimized_print("✅ [WebSocket] 连接成功，开始接收下注任务推送",
                       category='websocket', level='INFO', force=True)
    
    def _start_reconnect(self):
        """启动自动重连"""
        if self._reconnect_thread and self._reconnect_thread.is_alive():
            return  # 已有重连线程在运行
        
        def reconnect_loop():
            while not self._stop_event.is_set() and self.reconnect_attempts < self.max_reconnect_attempts:
                time.sleep(self.reconnect_interval)
                
                if self._stop_event.is_set():
                    break
                
                self.reconnect_attempts += 1
                optimized_print(f"🔄 [WebSocket] 尝试重连 ({self.reconnect_attempts}/{self.max_reconnect_attempts})...",
                               category='websocket', level='INFO', force=True)
                
                if self.connect():
                    break  # 连接成功，退出重连循环
                else:
                    optimized_print(f"❌ [WebSocket] 重连失败，{self.reconnect_interval}秒后重试",
                                   category='websocket', level='WARNING', force=True)
            
            if self.reconnect_attempts >= self.max_reconnect_attempts:
                optimized_print("❌ [WebSocket] 达到最大重连次数，停止重连。将切换到轮询模式",
                               category='websocket', level='ERROR', force=True)
                # 切换到轮询模式
                self._fallback_to_polling()
        
        self._reconnect_thread = threading.Thread(target=reconnect_loop, daemon=True)
        self._reconnect_thread.start()
    
    def _fallback_to_polling(self):
        """降级到轮询模式"""
        optimized_print("🔄 [WebSocket] 切换到轮询模式（WebSocket连接失败）",
                       category='websocket', level='INFO', force=True)
        
        # 确保轮询定时器正在运行
        if hasattr(self.main_window, 'timer_manager'):
            self.main_window.timer_manager.start_bet_task_timer(interval=2, direct=1, force_restart=True)
    
    def disconnect(self):
        """断开WebSocket连接"""
        self._stop_event.set()
        self.connected = False
        
        if self.ws:
            try:
                self.ws.close()
            except:
                pass
        
        optimized_print("✅ [WebSocket] 连接已断开",
                       category='websocket', level='INFO', force=True)
    
    def is_connected(self) -> bool:
        """检查是否已连接"""
        return self.connected and self.ws is not None


def create_websocket_client(main_window) -> Optional[BetTaskWebSocketClient]:
    """
    创建WebSocket客户端实例
    
    Args:
        main_window: 主窗口实例
    
    Returns:
        BetTaskWebSocketClient实例，如果创建失败则返回None
    """
    try:
        access_token = getattr(main_window, 'access_token', None)
        robot_domain = config.get_config('robot_domain', 'system_configs')
        
        if not access_token or not robot_domain:
            optimized_print("⚠️ [WebSocket] 缺少必要参数（access_token或robot_domain），无法创建WebSocket客户端",
                           category='websocket', level='WARNING', force=True)
            return None
        
        client = BetTaskWebSocketClient(main_window, access_token, robot_domain)
        return client
    except Exception as e:
        optimized_print(f"❌ [WebSocket] 创建客户端失败: {e}",
                       category='websocket', level='ERROR', force=True)
        return None

