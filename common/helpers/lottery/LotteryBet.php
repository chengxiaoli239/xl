<?php
namespace common\helpers\lottery;

use common\helpers\LotteryType;
use DateTime;

class LotteryBet
{
    const STATUS_DRAW = 1;
    const STATUS_START = 2;
    const STATUS_CLOSE = 3;
    const STATUS_OPTIONS = [
        self::STATUS_DRAW => '封盘-号码抓取',
        self::STATUS_START => '开盘',
        self::STATUS_CLOSE => '封盘',
    ];

    public array $schedule = [
        LotteryType::LUCKY_5 => [
            'minute' => 5,
            'draw' => 30,       // 抓取开奖号码开始时间，例如每5分钟整
            'closeOffset' => -30, // 封盘时间是第5分钟的前30秒，例如 4分30秒
            'open' => 65,  // 开盘时间是开奖后的50秒
        ],
        LotteryType::AZ_LUCKY_5 => [
            'minute' => 5,
            'draw' => 4 * 60,       // 抓取开奖号码时间，例如每4分钟整
            'closeOffset' => -50, // 封盘时间是第4分钟的前30秒，例如 3分30秒
            'open' => 35,  // 开盘时间是开奖后的50秒
        ],
        LotteryType::ETH_3M => [
            'minute' => 3,
            'draw' => 10,       // 抓取开奖号码开始时间，每3分钟整
            'closeOffset' => -15, // 封盘时间是第3分钟的前30秒，例如 2分30秒
            'open' => 60,  // 开盘时间是开奖后的30秒
        ],
        LotteryType::ETH_10M => [
            'minute' => 10,
            'draw' => 30,      // 抓取开奖号码开始时间，每10分钟整
            'closeOffset' => -50, // 封盘时间是第10分钟的前30秒，例如 9分30秒
            'open' => 80,  // 开盘时间是开奖后的80秒
        ],

        // 更多彩种配置...
    ];

    private int $drawFrequency = 5; // 通用的开奖频率（分钟）


    /**
     * 彩种：封盘 -> 抓取 -> 开盘
     * @param int $lotteryType
     * @param string $current
     * @return array
     */
    public static function isEntertained(int $lotteryType=DEFAULT_LOTTERY_TYPE, $current=''): array
    {
        if(empty($current)){
            $current = time();
        }
        // 获取当前时间
        $now = new DateTime();
        $lottery = \backend\models\LotteryType::find()->where(['lottery_type'=>$lotteryType])->asArray()->one();
        // 开奖频率（分钟）
        $drawFrequency = floatval(round($lottery['data_ftime']/60, 1));
        //p([$lotteryType, $drawFrequency]);

        if(!isset(LotteryType::LOTTERY_TIME_CONFIG[$lotteryType])){
            return [false, false];
        }
        $entertainedStatus = false;
        $grabStatus = false;
        list($betsCloseOffset, $grabOffset, $betsOpenOffset) = LotteryType::LOTTERY_TIME_CONFIG[$lotteryType]; #
        //p([$betsCloseOffset, $grabOffset, $betsOpenOffset]);
        # 封盘、开盘时间

        $nowTime = time();
        $nowBetZoneTime = $nowTime - ($nowTime%(60*$drawFrequency)); # 当前开奖时间区间
        //p(['nowBetZoneTime'=>date('Y-m-d H:i:s', $nowBetZoneTime)], 0);
        //p(['当前时间'=>date('Y-m-d H:i:s', $now->getTimestamp()), 'betsCloseOffset'=>$betsCloseOffset, 'betsOpenOffset'=>$betsOpenOffset], 0);

        // 获取当前小时的第0分钟的时间戳
        $hourStart = (clone $now)->setTime($now->format('H'), 0, 0);
        p($hourStart);

        // 计算当前时间与小时开始时间的差异（秒）
        $secondsSinceHourStart = $now->getTimestamp() - $hourStart->getTimestamp();
        //p([$lotteryType, 'hourStart'=>$hourStart, date('Y-m-d H:i:s', $now->getTimestamp()), '开盘'=>date('Y-m-d H:i:s', $hourStart->getTimestamp()), ($secondsSinceHourStart/60).'分钟'], 0);

        // 计算当前时间所在的开奖周期的开始时间
        $cycleStartSeconds = floor($secondsSinceHourStart / ($drawFrequency * 60)) * ($drawFrequency * 60) + $hourStart->getTimestamp();
        //p([ 'cycleStartSeconds'=>date('Y-m-d H:i:s', $cycleStartSeconds), floor($secondsSinceHourStart / ($drawFrequency * 60)) ], 0);

        // 计算封盘和开盘的时间戳
        //$betsNowTimestamp = $cycleStartSeconds + $betsCloseOffset;
        $betsNowOpenTimestamp = $cycleStartSeconds + $betsOpenOffset - $drawFrequency*60;
        $betsCloseTimestamp = $cycleStartSeconds + $betsCloseOffset; # 封盘开始时间
        $grabTimestamp0 = $cycleStartSeconds; # 抓取开始时间0
        $grabTimestamp1 = $cycleStartSeconds + $grabOffset; # 抓取开始时间1
        $betsOpenTimestamp = $cycleStartSeconds + $betsOpenOffset; # 开盘开始时间
        p([
            'lottery_type'=>$lotteryType,
            '当前时间'=>date('Y-m-d H:i:s', $now->getTimestamp()),
            '当前开盘时间'=>date('Y-m-d H:i:s', $betsNowOpenTimestamp),
            '抓取开始时间0'=>date('Y-m-d H:i:s', $grabTimestamp0),
            '抓取开始时间1'=>date('Y-m-d H:i:s', $grabTimestamp1),
            '即将封盘时间'=>date('Y-m-d H:i:s', $betsCloseTimestamp),
            '下个开盘时间'=>date('Y-m-d H:i:s', $betsOpenTimestamp),
        ], 0);

        // 判断当前时间是否处于封盘时间
        if (
            ($now->getTimestamp() >= $betsCloseTimestamp && $now->getTimestamp() < $betsOpenTimestamp)
            OR ($now->getTimestamp() < $betsNowOpenTimestamp)
        ) {
            // 目前是封盘时间，不能下注
            var_dump('当前时间封盘');
            $entertainedStatus = true;
        }else{
            var_dump('当前时间开盘');
        }
        if($now->getTimestamp() >$grabTimestamp0){
            var_dump('当前时间是可以抓取开奖数据');
            $grabStatus = true;
        }else{
            var_dump('当前时间是不可以抓取开奖数据');
        }

        return [$entertainedStatus, $grabStatus];
    }

    /**
     * 彩种是否可以下注，不可以下注期间便是可以获取开奖之时
     **/
    public function checkLotteryStatus($lotteryType, $currentTime=''): int
    {
        if(empty($currentTime)){
            $currentTime = date('Y-m-d H:i:s');
        }

        $now = strtotime($currentTime);
        $currentMSeconds = strtotime(date('Y-m-d H:i', $now));
        $currentMinute = date('i', $now);
        $currentSecond = date('s', $now);
        $totalSeconds = $currentMinute * 60 + $currentSecond;
        $cycleStart = $totalSeconds - ($totalSeconds % (5 * 60)); // 当前周期(每小时为周期)开始的秒数

        /*
        p([
            'currentHour'=>date('Y-m-d H:i:s', $currentMSeconds),
            'currentTime' => date('Y-m-d H:i:s', $now),
            'totalSeconds'=>$totalSeconds,
            '分'=>$currentMinute,
            '秒'=>$currentSecond,
            '求余秒数'=>$now%300
        ],0);
        */
        # 七星、排列五
        if(in_array($lotteryType, [LotteryType::HN_SEVEN, LotteryType::PL_5, LotteryType::PL_3D])){
            $HI = date('H:i');
            if('21:00'<$HI AND $HI<'22:00'){
                return LotteryBet::STATUS_DRAW; # 当前为开奖抓取时间
            }else{
                return LotteryBet::STATUS_CLOSE; # 当前为封盘状态
            }
        }

        if (!isset($this->schedule[$lotteryType])) {
            throw_info("彩种时间配置不存在");
        }

        $lotterySchedule = $this->schedule[$lotteryType];

        $currentCycleStart = strtotime(date('Y-m-d H:00', $now)) + $cycleStart + $lotterySchedule['draw'];
        $closeStart = date('Y-m-d H:i:s', $currentCycleStart + $lotterySchedule['closeOffset']); # 下注封盘开始时间
        $drawStart = date('Y-m-d H:i:s', $currentCycleStart);
        $closeEnd = date('Y-m-d H:i:s', $currentCycleStart + $lotterySchedule['open']); # 下注封盘结束时间
        $closeNextStart = date('Y-m-d H:i:s', $currentCycleStart + $lotterySchedule['closeOffset'] + $lotterySchedule['minute']*60); # 下注封盘开始时间
        $closeNextEnd = date('Y-m-d H:i:s', $currentCycleStart + $lotterySchedule['open'] + $lotterySchedule['minute']*60); # 下注封盘结束时间
        /*
        p([
            'lotteryType'=>$lotteryType,
            'currentCycleStart' => date('Y-m-d H:i:s', $currentCycleStart),
            'currentTime'=>$currentTime,
            'closeStart'=>$closeStart,
            'drawStart'=>$drawStart,
            'closeEnd'=>$closeEnd,
            'closeNextStart'=>$closeNextStart,
            'closeNextEnd'=>$closeNextEnd,
        ], 0);
        */

        $txt = $lotteryType.'_'.$currentTime;
        if(($closeStart<=$currentTime && $currentTime<=$closeEnd)) {
            if($drawStart<=$currentTime){
                return LotteryBet::STATUS_DRAW; # 当前为开奖抓取时间
            }
            //var_dump('222');
            return LotteryBet::STATUS_CLOSE; # 当前为封盘状态
        }elseif ($closeNextStart<=$currentTime && $currentTime<=$closeNextEnd){
            return LotteryBet::STATUS_CLOSE; # 当前为下个封盘状态
        }else{
            //var_dump('2222222');
            return LotteryBet::STATUS_START; # 当前为开盘状态
        }
    }

}