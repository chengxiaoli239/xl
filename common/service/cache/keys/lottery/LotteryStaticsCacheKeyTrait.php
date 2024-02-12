<?php
namespace common\service\cache\keys\lottery;

trait LotteryStaticsCacheKeyTrait
{

    /**
     * @param $plan_id
     * @param $current_qiHao
     * @return string
     */
    public static function planBetKey($plan_id, $current_qiHao): string
    {
        return 'lotteryStatics:plan_id:planBet_'.$plan_id.'_'.$current_qiHao;
    }

    public static function insertPlanTaskKey($lottery_type=DEFAULT_LOTTERY_TYPE, $qiHao='', $planId=0): string
    {
        return 'lottery:plan_task:qiHao:type_'.$lottery_type.'_'.$qiHao.'_'.$planId;
    }

    public static function preInsertPlanTaskKey($planId, $activeQiHao=''): string
    {
        return 'lottery:pre_insert_plan_task:val_'.$planId.'_'.$activeQiHao;
    }
}
