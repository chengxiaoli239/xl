<?php


namespace backend\service\statics\yl;

use backend\models\SscKjData;
use backend\service\BaseService;
use common\tools\Timer;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class OneNumYl extends BaseService
{
    public static function yl($lotteryType=DEFAULT_LOTTERY_TYPE): array
    {
        for ($pos=1; $pos<5; $pos++){
            $data = self::onePositionYl($pos, $lotteryType);
            p($data);
            list($pos, $todayCurrent, $maxIndexId, $currentMiss, $weekMiss, $monthMiss) = $data;
            for ($code=0; $code<=9; $code++){
            }
        }

        return [];
    }

    private static function onePositionYl($pos, $lotteryType=DEFAULT_LOTTERY_TYPE): array
    {
        $field = 'code'.$pos;
        $where = ['lottery_type'=>$lotteryType];
        list($start, $end) = Timer::todayTime();//p([date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end)]); # 今日时间

        # 今日出现
        $todayCurrent = SscKjData::find()->select([$field, 'count'=>'COUNT(id)'])->where($where)->andWhere([
            'AND',
            ['>=', 'created_at', $start+180], # 前移3分钟避免最后一期和第一期时间是跨天的情况
            ['<=', 'created_at', $end+180], # 前移3分钟避免最后一期和第一期时间是跨天的情况
        ])->asArray()->groupBy($field)->all();
        $todayCurrent = ArrayHelper::getColumn($todayCurrent,'count', $field); # 今日$pos位置0-9出现次数
        //p(['lotteryType'=>$lotteryType, 'todayCurrent'=>$todayCurrent]);

        # 当前遗漏
        $currentMiss = SscKjData::find()->select([$field, 'index_id'=>'MAX(index_id)'])->where($where)
            ->asArray()->groupBy([$field])->orderBy('MAX(index_id) DESC')->all();
        $maxIndexId = $currentMiss[0]['index_id'];
        #p($currentMiss);

        # 今日遗漏
        list($start, $end) = Timer::todayTime();//p([date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end)]);
        list($todayMiss, $todayAllCount) = self::getZoneCodeYlInfo($field, $start, $end, $where);
        //p(['todayMiss'=>$todayMiss, 'todayAllCount'=>$todayAllCount]);

        # 本周时间
        list($start, $end) = Timer::thisWeekTime();//p([date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end)]); # 本周时间
        list($thisWeekMiss, $thisWeekAllCount) = self::getZoneCodeYlInfo($field, $start, $end, $where);
        # 本月时间
        list($start, $end) = Timer::thisMonthTime();//p([date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end)]); # 本月时间
        list($thisMonthMiss, $thisMonthAllCount) = self::getZoneCodeYlInfo($field, $start, $end, $where);

        return [$pos, $todayCurrent, $maxIndexId, $currentMiss, $todayMiss, $todayAllCount, $thisWeekMiss, $thisWeekAllCount, $thisMonthMiss, $thisMonthAllCount];
    }

    /**
     * 获取某个时间区间的开奖总期数，遗漏总期数
     * @param $field
     * @param $start
     * @param $end
     * @param $where
     * @return array
     */
    private static function getZoneCodeYlInfo($field, $start, $end, $where): array
    {
        $missQuery = SscKjData::find()->select([$field, 'count'=>'COUNT(id)'])->where($where)->andWhere([
            'AND',
            ['>=', 'created_at', $start+180],
            ['<=', 'created_at', $end+180],
        ]);
        $allCount = $missQuery->select(['count'=>'COUNT(id)'])->scalar();
        $miss = $missQuery->groupBy($field)->asArray()->all();

        return [$miss, $allCount];
    }
}