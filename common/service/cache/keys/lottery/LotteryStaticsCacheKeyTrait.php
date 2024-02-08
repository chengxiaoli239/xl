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
}
