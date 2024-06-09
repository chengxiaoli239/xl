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
     * 开奖信息缓存
     * @param int $lottery_type
     * @return string
     */
    public static function lotteryGrabInfo(int $lottery_type=DEFAULT_LOTTERY_TYPE): string
    {
        return 'lottery:grabInfo:current_'.$lottery_type;
    }

    /**
     * 开盘开关缓存
     * @param int $lottery_type
     * @return string
     */
    public static function lotteryOpenSwitch(int $lottery_type=DEFAULT_LOTTERY_TYPE): string
    {
        return 'lottery:openSwitch:lottery_type_'.$lottery_type;
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

    public static function lotteryBetPostSiteKey($betRowId='', $activeQiHao=''): string
    {
        return 'lottery:betPostSite:account_'.$betRowId.'_'.$activeQiHao;
    }

    /**
     * @param $tz_type
     * @return string
     */
    public static function lotteryTzType($tz_type): string
    {
        return 'lottery:tz_type:type_name_'.$tz_type;
    }

    /**
     * 客户端是否需要登录
     * @param int $lottery_type
     * @param string $qiHao
     * @param string $status_key
     * @return string
     */
    public static function lotteryDealStatus(int $lottery_type=DEFAULT_LOTTERY_TYPE, string $qiHao='', $status_key=''): string
    {
        return 'lottery:getDataDealStatus_:info_'.$lottery_type.'_'.$qiHao.'_'.$status_key;
    }

    public static function getIsClientNeedLoginKey($userId=0): string
    {
        return 'lottery:need_login:user_'.$userId;
    }
}
