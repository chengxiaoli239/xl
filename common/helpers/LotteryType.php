<?php
namespace common\helpers;

use common\models\thirdD\BetOrderId;
use common\tools\KjDataGet;

class LotteryType
{
    const HN_SEVEN = 1;
    const LUCKY_5 = 8;
    const PL_5 = 17;
    const JS_SEVEN = 25;
    const ETH_3M = 23;
    const ETH_10M = 24;
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

    const LT_PLAY_WAY_1 = 4;
    const LT_PLAY_WAY_2 = 1;
    const LT_PLAY_WAY_3= 2;
    const LT_PLAY_WAY_4 = 3;
    const LT_PLAY_WAY_5= 5;
    const LT_PLAY_WAY_OPTIONS = [
        self::LT_PLAY_WAY_2 => '二定位',
        self::LT_PLAY_WAY_3 => '三定位',
        self::LT_PLAY_WAY_4 => '四定位',
        self::LT_PLAY_WAY_1 => '一定位',
        self::LT_PLAY_WAY_5 => '五定位',
    ];

    const LOTTERY_TIME_CONFIG = [
        self::LUCKY_5 => [4*60+30, 5*60+30, 5*60+50], # 封盘偏移时间:betsCloseOffset、开始抓取时间:grabOffset、开盘偏移时间:betsOpenOffset
        self::AZ_LUCKY_5 => [3*60+30, 4*60, 4*60+20], # 封盘偏移时间:betsCloseOffset、开始抓取时间:grabOffset、开盘偏移时间:betsOpenOffset
        self::ETH_3M => [2*60+30, 3*60-10, 3*60+50], # 以太坊3分：封盘偏移时间、开始抓取时间、开盘偏移时间
        self::ETH_10M => [9*60+30, 10*60-30, 10*60+80], # 以太坊10分：封盘偏移时间、开始抓取时间、开盘偏移时间
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

    /**
     * 获取前x期期号
     * @param $qiHao
     * @param int $beforeNum
     * @param int $lotteryType
     * @return bool|int|mixed|string
     */
    public static function getBeforeNQiHao($qiHao, $beforeNum=1, $lotteryType=DEFAULT_LOTTERY_TYPE)
    {
        for($i=0; $i<$beforeNum; $i++){
            $qiHao = KjDataGet::getBeforeQiHaoByQiHao($qiHao, $lotteryType);
        }

        return $qiHao;
    }
}
