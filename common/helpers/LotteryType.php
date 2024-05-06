<?php
namespace common\helpers;

use common\models\thirdD\BetOrderId;

class LotteryType
{
    const HN_SEVEN = 1;
    const LUCKY_5 = 8;
    const PL_5 = 17;
    const JS_SEVEN = 25;
    const FC_3D = 26;
    const PL_3D = 27;
    const AZ_LUCKY_5 = 28;

    const TYPE_OPTIONS = [
        self::HN_SEVEN => '七星',
        self::LUCKY_5 => '幸运五',
        self::PL_5 => '排列五',
        self::FC_3D => '福',
        self::PL_3D => '排',
        self::JS_SEVEN => '七位数',
        self::AZ_LUCKY_5 => '澳洲五',
    ];

    # 0898系统
    const TZ_SYSTEM_ID_0898 = 2;
    # 澳洲五
    const TZ_SYSTEM_TYPE_ID_AZ = 16;

    const LOTTERY_TIME_CONFIG = [
        self::LUCKY_5 => [4*60+30, 5*60+30, 5*60+50], # 封盘偏移时间:betsCloseOffset、开始抓取时间:grabOffset、开盘偏移时间:betsOpenOffset
        self::AZ_LUCKY_5 => [3*60+30, 4*60, 4*60+20], # 封盘偏移时间:betsCloseOffset、开始抓取时间:grabOffset、开盘偏移时间:betsOpenOffset
    ];

    public static function getName($lottery_type=DEFAULT_LOTTERY_TYPE): string
    {
        return self::TYPE_OPTIONS[$lottery_type]??'未知彩种';
    }

    /**
     * 下注单号
     * @return bool|mixed|null
     */
    public static function getOrderId(){
        $BetOrderId = new BetOrderId();
        $BetOrderId->created_at = time();
        $BetOrderId->updated_at = time();
        $r = $BetOrderId->save();
        if(empty($r)){
            return false;
        }

        return $BetOrderId->bet_order_id;
    }
}
