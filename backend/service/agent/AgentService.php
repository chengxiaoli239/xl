<?php
namespace backend\service\agent;

use backend\models\TzSystemsUsers;
use backend\models\wechat\Bets;
use backend\service\BaseService;
use common\service\thirdD\CommonBaseService;

class AgentService extends BaseService {

    /**
     * 汇总
     * @param $userId
     * @return array
     */
    public static function getCalcMoney($userId): array
    {
        // 今日时间
        list($startOfDay, $endOfDay) = [strtotime('today'), strtotime('today') + 24 * 3600 - 1];
        // 本周时间，获取本周的第一天（星期一）的时间戳、 获取本周的最后一天（星期日）的时间戳, 加上一天的秒数，然后减去1秒，以获取当天的最后一秒
        list($startOfWeek, $endOfWeek) = [strtotime('monday this week'), strtotime('sunday this week') + 24 * 3600 - 1];
        // 上周时间
        list($startOfLastWeek, $endOfLastWeek) = [strtotime('last monday', strtotime('-1 week')), strtotime('last sunday', strtotime('-1 week')) + 24 * 3600 - 1];

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$userId]);

        # 盘口余额、今日盈亏、有效金额、本周下单金额、本周实际盈亏、上周下单金额、上周实际盈亏
        return [
            $TzSystemsUsers->balance?:0.00, # 盘口余额
            0-self::getZoneProfits($startOfDay, $endOfDay), # 今日盈亏
            self::getZoneBetMoney($startOfDay, $endOfDay), # 有效金额，暂时计算今日，有待确认
            self::getZoneBetMoney($startOfWeek, $endOfWeek), # 本周下单金额
            0-self::getZoneProfits($startOfWeek, $endOfWeek), # 本周实际盈亏
            self::getZoneBetMoney($startOfLastWeek, $endOfLastWeek), # 上周下单金额
            0-self::getZoneProfits($startOfLastWeek, $endOfLastWeek), # 上周实际盈亏
        ];
    }

    /**
     * 计算区间利润
     * @param $startTime
     * @param $endTime
     * @return false|float|string
     */
    private static function getZoneProfits($startTime, $endTime)
    {
        $profits = Bets::find()->select(['profitAndLoss'=>'SUM(profits)'])->where([
            'AND',
            ['>=', 'created_at', $startTime],
            ['<=', 'created_at', $endTime],
        ])->scalar();

        return $profits?:0.00;
    }

    /**
     * 有效金额
     * @param $startTime
     * @param $endTime
     * @return false|float|string
     */
    public static function getZoneBetMoney($startTime, $endTime)
    {
        $money = Bets::find()->select(['money'=>'SUM(bet_money)'])->where([
            'AND',
            ['>=', 'created_at', $startTime],
            ['<=', 'created_at', $endTime],
            ['!=', 'status', CommonBaseService::STATUS_LT_CANCEL],
        ])->scalar();

        return $money?:0.00;

    }
}
