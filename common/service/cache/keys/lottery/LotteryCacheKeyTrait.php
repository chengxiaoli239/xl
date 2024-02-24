<?php
namespace common\service\cache\keys\lottery;

trait LotteryCacheKeyTrait
{
    /**
     * 管理员lottery key
     * @param int $grabDataStatus
     * @return string
     */
    public static function lotteryData(int $grabDataStatus=1): string
    {
        return 'lottery:type:grabDataStatus_x0' . $grabDataStatus;
    }

    /**
     * @param int $lottery_type
     * @return string
     */
    public static function lotteryQiHaoInfo(int $lottery_type=DEFAULT_LOTTERY_TYPE): string
    {
        return 'lottery:qiHaoInfo:current_'.$lottery_type;
    }

    /**
     * @param int $lottery_type
     * @param string $qiHao
     * @return string
     */
    public static function lotteryKjInfo(int $lottery_type=DEFAULT_LOTTERY_TYPE, string $qiHao=''): string
    {
        return 'lottery:kjInfo:info_'.$lottery_type.'_'.$qiHao;
    }

    /**
     * @param int $user_id
     * @return string
     */
    public static function userLotteryTypes(int $user_id=0): string
    {
        return 'lottery:userLotteryTypes:user_id_'.$user_id;
    }

    public static function lotteryBetPlanIdKey($account='', $activeQiHao='', $planId=0): string
    {
        return 'lottery:userBetPlan:account_'.$account.'_'.$activeQiHao.'_'.$planId;
    }
}
