<?php
namespace common\helpers\lottery;

use common\helpers\LotteryType;
use DateTime;

class LotteryBet
{
    /**
     * 彩种是否可以下注，不可以下注期间便是可以获取开奖之时
     * @param int $lotteryType
     * @return bool
     */
    public static function isCanBet(int $lotteryType=DEFAULT_LOTTERY_TYPE): bool
    {
        // 获取当前时间
        $now = new DateTime();
        $lottery = \backend\models\LotteryType::find()->where(['lottery_type'=>$lotteryType])->asArray()->one();
        // 开奖频率（分钟）
        $drawFrequency = floatval(round($lottery['data_ftime']/60, 1));
        //p([$lotteryType, $drawFrequency]);

        // 开盘时间偏移（秒）
        switch (true){
            case $lotteryType == LotteryType::AZ_LUCKY_5:
                // 开奖时间偏移（分钟）
                $drawOffset = 4;
                break;
            case $lotteryType == LotteryType::LUCKY_5:
                // 开奖时间偏移（秒）
                $drawOffset = 5;
                break;
            default:
                var_dump('ddd');
                return false;
        }
        $nowTime = time();
        $nowBetZoneTime = $nowTime - ($nowTime%(60*$drawFrequency)); # 当前开奖时间区间
        //p(['nowBetZoneTime'=>date('Y-m-d H:i:s', $nowBetZoneTime)], 0);

        // 封盘开始时间偏移（秒）
        $betsCloseOffset = ($drawOffset-1) * 60 + 30; // 3分30秒
        // 开盘时间偏移（秒）
        $betsOpenOffset = $drawOffset * 60 + 20; // 4分20秒
        //p(['当前时间'=>date('Y-m-d H:i:s', $now->getTimestamp()), 'betsCloseOffset'=>$betsCloseOffset, 'betsOpenOffset'=>$betsOpenOffset], 0);

        // 获取当前小时的第0分钟的时间戳
        $hourStart = (clone $now)->setTime($now->format('H'), 0, 0);

        // 计算当前时间与小时开始时间的差异（秒）
        $secondsSinceHourStart = $now->getTimestamp() - $hourStart->getTimestamp();
        //p([$lotteryType, 'hourStart'=>$hourStart, date('Y-m-d H:i:s', $now->getTimestamp()), '开盘'=>date('Y-m-d H:i:s', $hourStart->getTimestamp()), ($secondsSinceHourStart/60).'分钟'], 0);

        // 计算当前时间所在的开奖周期的开始时间
        $cycleStartSeconds = floor($secondsSinceHourStart / ($drawFrequency * 60)) * ($drawFrequency * 60) + $hourStart->getTimestamp();
        //p([ 'cycleStartSeconds'=>date('Y-m-d H:i:s', $cycleStartSeconds), floor($secondsSinceHourStart / ($drawFrequency * 60)) ], 0);

        // 计算封盘和开盘的时间戳
        //$betsNowTimestamp = $cycleStartSeconds + $betsCloseOffset;
        $betsCloseTimestamp = $cycleStartSeconds + $betsCloseOffset;
        $betsOpenTimestamp = $cycleStartSeconds + $betsOpenOffset;
        //p([ '当前时间'=>date('Y-m-d H:i:s', $now->getTimestamp()), '即将封盘时间'=>date('Y-m-d H:i:s', $betsCloseTimestamp), '下个开盘时间'=>date('Y-m-d H:i:s', $betsOpenTimestamp) ], 0);

        // 判断当前时间是否处于封盘时间
        if ($now->getTimestamp() >= $betsCloseTimestamp && $now->getTimestamp() < $betsOpenTimestamp) {
            // 目前是封盘时间，不能下注
            var_dump('目前是封盘时间，不能下注');
            return false;
        }
        var_dump('目前是开盘时间，可以下注');

        return true;
    }

    /**
     * 彩种是否可以下注，不可以下注期间便是可以获取开奖之时
     * @param int $lotteryType
     * @return bool
     */
    public static function isCanBetB(int $lotteryType=DEFAULT_LOTTERY_TYPE): bool
    {
        date_default_timezone_set('Asia/Shanghai'); // 设置时区

// 获取当前时间
        $now = new DateTime();

// 开奖频率和时间偏移
        $drawFrequency = 5; // 5分钟开奖一次
        $drawOffset = 4; // 第4分钟开奖

// 封盘和开盘时间偏移
        $betsCloseOffset = 3 * 60 + 30; // 3分30秒封盘
        $betsOpenOffset = 4 * 60 + 20; // 4分20秒开盘

// 计算当前时刻所在的5分钟周期开始时间
        $cycleStart = (clone $now)->setTime($now->format('H'), floor($now->format('i') / $drawFrequency) * $drawFrequency, 0);

// 下一个开奖时间
        $nextDraw = (clone $cycleStart)->modify("+{$drawOffset} minutes");

// 如果当前时间超过了下一个开奖时间，我们需要计算下一个周期的时间
        if ($now >= $nextDraw) {
            $cycleStart->modify("+{$drawFrequency} minutes");
            $nextDraw->modify("+{$drawFrequency} minutes");
        }

// 计算封盘和开盘时间
        $betsCloseTime = (clone $nextDraw)->modify("-" . ($drawFrequency * 60 - $betsCloseOffset) . " seconds");
        $betsOpenTime = (clone $nextDraw)->modify("+" . ($betsOpenOffset - $drawFrequency * 60) . " seconds");

// 输出封盘和开盘时间，用于调试
        echo "当前时间: " . date('Y-m-d H:i:s') . "\n";
        echo "下一个开奖时间: " . $nextDraw->format('Y-m-d H:i:s') . "\n";
        echo "封盘时间: " . $betsCloseTime->format('Y-m-d H:i:s') . "\n";
        echo "开盘时间: " . $betsOpenTime->format('Y-m-d H:i:s') . "\n";

// 判断当前时间是否处于封盘时间
        if ($now >= $betsCloseTime && $now < $betsOpenTime) {
            echo "目前是封盘时间，不能下注。";
        } else {
            echo "现在可以下注。";
        }

        return true;
    }

}