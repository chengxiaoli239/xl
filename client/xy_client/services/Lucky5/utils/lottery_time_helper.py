"""
彩票时间辅助工具
参考PHP代码 LotteryBet.php 的逻辑，判断当前时间是否处于特定时间段
"""

import time
from datetime import datetime
from typing import Tuple, Optional


class LotteryTimeHelper:
    """彩票时间辅助类 - 判断当前时间是否处于特定时间段"""
    
    # 幸运五（lottery_type=8）的时间配置
    # 参考PHP代码：每5分钟一期
    LUCKY_5_CONFIG = {
        'minute': 5,           # 每5分钟一期
        'draw': 30,            # 抓取开奖号码开始时间（秒），例如每5分钟整后的30秒
        'closeOffset': -30,   # 封盘时间是第5分钟的前30秒，例如 4分30秒
        'open': 65,            # 开盘时间是开奖后的65秒
    }
    
    @staticmethod
    def get_current_cycle_start(lottery_type: int = 8) -> int:
        """
        获取当前开奖周期的开始时间戳
        
        Args:
            lottery_type: 彩种类型，默认8（幸运五）
        
        Returns:
            int: 当前周期的开始时间戳（秒）
        """
        now = time.time()
        
        if lottery_type == 8:  # 幸运五
            config = LotteryTimeHelper.LUCKY_5_CONFIG
            draw_frequency = config['minute'] * 60  # 转换为秒
            
            # 计算当前时间所在的开奖周期的开始时间
            # 例如：如果现在是 10:23:45，每5分钟一期，则周期开始时间是 10:20:00
            cycle_start = int(now - (now % draw_frequency))
            return cycle_start
        else:
            # 其他彩种，使用默认5分钟
            draw_frequency = 5 * 60
            cycle_start = int(now - (now % draw_frequency))
            return cycle_start
    
    @staticmethod
    def is_draw_time(lottery_type: int = 8) -> bool:
        """
        判断当前时间是否可以抓取开奖号码
        
        参考PHP代码逻辑：
        - 幸运五：每5分钟一期，在周期开始后的30秒开始抓取（例如：10:00:30, 10:05:30, 10:10:30...）
        - 抓取时间窗口：从周期开始+30秒 到 下一个周期开始前
        
        Args:
            lottery_type: 彩种类型，默认8（幸运五）
        
        Returns:
            bool: 是否可以抓取开奖号码
        """
        if lottery_type == 8:  # 幸运五
            config = LotteryTimeHelper.LUCKY_5_CONFIG
            cycle_start = LotteryTimeHelper.get_current_cycle_start(lottery_type)
            now = time.time()
            
            # 抓取开始时间：周期开始时间 + draw秒（例如：10:00:30）
            draw_start = cycle_start + config['draw']
            
            # 下一个周期开始时间
            next_cycle_start = cycle_start + config['minute'] * 60
            
            # 如果当前时间 >= 抓取开始时间 且 < 下一个周期开始时间，则可以抓取
            # 这样可以避免在封盘期间频繁请求
            return draw_start <= now < next_cycle_start
        else:
            # 其他彩种，默认可以抓取
            return True
    
    @staticmethod
    def is_close_time(lottery_type: int = 8) -> bool:
        """
        判断当前时间是否处于封盘时间
        
        Args:
            lottery_type: 彩种类型，默认8（幸运五）
        
        Returns:
            bool: 是否处于封盘时间
        """
        if lottery_type == 8:  # 幸运五
            config = LotteryTimeHelper.LUCKY_5_CONFIG
            cycle_start = LotteryTimeHelper.get_current_cycle_start(lottery_type)
            now = time.time()
            
            # 封盘开始时间：周期开始时间 + closeOffset秒
            close_start = cycle_start + config['minute'] * 60 + config['closeOffset']
            # 开盘时间：周期开始时间 + open秒
            open_time = cycle_start + config['open']
            
            # 如果当前时间在封盘开始时间和开盘时间之间，则处于封盘状态
            return close_start <= now < open_time
        else:
            return False
    
    @staticmethod
    def is_open_time(lottery_type: int = 8) -> bool:
        """
        判断当前时间是否处于开盘时间（可以下注）
        
        Args:
            lottery_type: 彩种类型，默认8（幸运五）
        
        Returns:
            bool: 是否处于开盘时间
        """
        return not LotteryTimeHelper.is_close_time(lottery_type)
    
    @staticmethod
    def get_next_draw_time(lottery_type: int = 8) -> Optional[datetime]:
        """
        获取下次抓取开奖号码的时间
        
        Args:
            lottery_type: 彩种类型，默认8（幸运五）
        
        Returns:
            datetime: 下次抓取时间，如果无法计算则返回None
        """
        if lottery_type == 8:  # 幸运五
            config = LotteryTimeHelper.LUCKY_5_CONFIG
            cycle_start = LotteryTimeHelper.get_current_cycle_start(lottery_type)
            now = time.time()
            
            # 当前周期的抓取开始时间
            current_draw_start = cycle_start + config['draw']
            
            if now >= current_draw_start:
                # 当前周期已过，计算下一个周期
                next_cycle_start = cycle_start + config['minute'] * 60
                next_draw_start = next_cycle_start + config['draw']
            else:
                # 当前周期还未到抓取时间
                next_draw_start = current_draw_start
            
            return datetime.fromtimestamp(next_draw_start)
        else:
            return None
    
    @staticmethod
    def get_time_info(lottery_type: int = 8) -> dict:
        """
        获取当前时间的详细信息
        
        Args:
            lottery_type: 彩种类型，默认8（幸运五）
        
        Returns:
            dict: 包含周期开始时间、抓取时间、封盘时间、开盘时间等信息
        """
        if lottery_type == 8:  # 幸运五
            config = LotteryTimeHelper.LUCKY_5_CONFIG
            cycle_start = LotteryTimeHelper.get_current_cycle_start(lottery_type)
            now = time.time()
            
            draw_start = cycle_start + config['draw']
            close_start = cycle_start + config['minute'] * 60 + config['closeOffset']
            open_time = cycle_start + config['open']
            
            return {
                'current_time': datetime.fromtimestamp(now).strftime('%Y-%m-%d %H:%M:%S'),
                'cycle_start': datetime.fromtimestamp(cycle_start).strftime('%Y-%m-%d %H:%M:%S'),
                'draw_start': datetime.fromtimestamp(draw_start).strftime('%Y-%m-%d %H:%M:%S'),
                'close_start': datetime.fromtimestamp(close_start).strftime('%Y-%m-%d %H:%M:%S'),
                'open_time': datetime.fromtimestamp(open_time).strftime('%Y-%m-%d %H:%M:%S'),
                'is_draw_time': now >= draw_start,
                'is_close_time': close_start <= now < open_time,
                'is_open_time': not (close_start <= now < open_time),
            }
        else:
            return {
                'current_time': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                'is_draw_time': True,
                'is_close_time': False,
                'is_open_time': True,
            }

