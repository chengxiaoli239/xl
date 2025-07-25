<?php
namespace common\service\cache\keys\lottery;

trait LotteryCacheKeyTrait
{
    /**
     * 管理员lottery key
     * @param $grabDataStatus
     * @return string
     */
    public static function lotteryData($grabDataStatus=1): string
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

    /**
     * 是否需要登录标识key
     * @param $userId
     * @return string
     */
    public static function getIsClientNeedLoginKey($userId=0): string
    {
        return 'lottery:need_login:user_'.$userId;
    }

    /**
     * 是否需要登录标识key
     * @param $userId
     * @return string
     */
    public static function getSiteReportDataKey($userId=0): string
    {
        return 'lottery:site_report:user_'.$userId;
    }

    /**
     * 获取计划开奖ley
     * @param int $planId
     * @param string $qiHao
     * @return string
     */
    public static function getPlanKjKey(int $planId=0, $qiHao=''): string
    {
        return 'lottery:plan_kj:plan_'.$planId.'_'.$qiHao;
    }

    /**
     * 随机下注的描述
     * @param int $planId
     * @param string $qiHao
     * @return string
     */
    public static function getBetRandDescKey(int $planId=0, $qiHao=''): string
    {
        return 'lottery:rand_bet_desc:plan_'.$planId.'_'.$qiHao;
    }

    /**
     * 开奖数据缓存key
     * @param $lottery_type
     * @param $qiHao
     * @return string
     */
    public static function lotteryOpenDataKey($lottery_type, $qiHao): string
    {
        return 'lottery:open_data:lt_'.$lottery_type.'_'.$qiHao;
    }

    /**
     * 排序id key
     * @param $lottery_type
     * @return string
     */
    public static function lotteryLastIndexKey($lottery_type): string
    {
        return 'lottery:last_index_key:lt_'.$lottery_type;
    }

    /**
     * 彩种基本信息
     * @param $lottery_type
     * @return string
     */
    public static function lotteryBaseInfo($lottery_type): string
    {
        return 'lottery:lottery_base_info:lt_x1_'.$lottery_type;
    }

    /**
     * 获取随机号码key
     * @param $planId
     * @param $qiHao
     * @param $type
     * @return string
     */
    public static function getRangeCode($planId, $qiHao, $type): string
    {
        return 'lottery:rand_code:lt_'.$planId.'_'.$qiHao.'_'.$type;
    }

    /**
     * 获取随机号码key
     * @param $planId
     * @param $qiHao
     * @return string
     */
    public static function getRangeCodeKey($planId, $qiHao): string
    {
        return 'lottery:rand_code:lt_key_'.$planId.'_'.$qiHao;
    }

    /**
     * 获取计划当期的号码缓存key
     * @param $planId
     * @param $qiHao
     * @return string
     */
    public static function getPlanCurrentCodeKey($planId, $qiHao, $planMd5Key): string
    {
        return 'lottery:plan_code:lt_key_'.$planId.'_'.$qiHao.'_'.$planMd5Key;
    }
}
