<?php


namespace backend\service\statics\yl;

use backend\models\SscKjData;
use backend\service\BaseService;
use common\models\statics\Ssc1numsYl;
use common\tools\Timer;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class OneNumYl extends BaseService
{
    public static function yl($lotteryType=DEFAULT_LOTTERY_TYPE): bool
    {
        for ($pos=1; $pos<=5; $pos++){
            $data = [];
            $ylData = self::onePositionYl($pos, $lotteryType);
            //p($ylData,0);
            $t1 = microtime(true);
            //list($pos, $todayCurrent, $maxIndexId, $currentMiss, $weekMiss, $monthMiss) = $data;
            for ($code=0; $code<=9; $code++){
                //p([$ylData['maxIndexId'], $ylData['currentIndexIds'][$code]],0);
                $data[] = [
                    'position' => $pos,
                    'code' => $code,
                    # 今日出现
                    'today_current' => $ylData['todayCurrent'][$code]??0,
                    # 当前遗漏
                    'current_miss' => $ylData['maxIndexId'] - $ylData['currentIndexIds'][$code],
                    # 今日遗漏
                    'today_miss' => $ylData['todayAllCount'] - ($ylData['todayMiss'][$code]??0),
                    # 本周遗漏
                    'week_miss' => $ylData['thisWeekAllCount'] - ($ylData['thisWeekMiss'][$code]??0),
                    # 本月遗漏
                    'month_miss' => $ylData['thisMonthAllCount'] - ($ylData['thisMonthMiss'][$code]??0),
                    'lottery_type' => $lotteryType,
                    'created_at' => time(),
                ];
            }
            $t2 = microtime(true);
            var_dump('位置'.$pos.'耗时：'.($t2-$t1).'s');
            $columnKeys = array_keys($data[0]);
            Ssc1numsYl::deleteRecord(['lottery_type'=>$lotteryType, 'position'=>$pos]);
            Ssc1numsYl::find()->createCommand()->batchInsert(Ssc1numsYl::tableName(), $columnKeys, $data)->execute();
        }

        return true;
    }

    public static function onePositionYl($pos, $lotteryType=DEFAULT_LOTTERY_TYPE): array
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
            ->asArray()->groupBy([$field])->indexBy($field)->orderBy('MAX(index_id) DESC')->limit(1000)->all();
        $maxIndexId = current($currentMiss)['index_id'];
        //p([$currentMiss, $maxIndexId]);
        // 重构数组
        $currentIndexIds = array_map(function($item) {
            return $item['index_id'];
        }, $currentMiss);
        //p(['$currentIndexIds'=>$currentIndexIds]);

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

        return [
            'pos' => $pos, # 位置
            'todayCurrent' => $todayCurrent, # 今日出现次数
            'maxIndexId' => $maxIndexId, # 最新的index_id 用于计算遗漏
            'currentIndexIds' => $currentIndexIds, # 0-9最新的index_id，用于计算号码的：当前遗漏
            'todayMiss' => $todayMiss, # 今日未出次数，也就是：今日遗漏
            'todayAllCount' => $todayAllCount, # 今日总共次数，也可以理解为总期数
            'thisWeekMiss' => $thisWeekMiss, # 本周遗漏次数
            'thisWeekAllCount' => $thisWeekAllCount, # 本周总次数，也可以理解为：本周总期数
            'thisMonthMiss' => $thisMonthMiss, # 本月遗漏次数
            'thisMonthAllCount' => $thisMonthAllCount, # 本月总次数，也可以理解为：本月总期数
        ];
    }

    /**
     * 获取某个时间区间的开奖总期数，遗漏总期数
     * @param $field
     * @param $start
     * @param $end
     * @param $where
     * @return array
     */
    public static function getZoneCodeYlInfo($field, $start, $end, $where): array
    {
        $missQuery = SscKjData::find()->where($where)->andWhere([
            'AND',
            ['>=', 'created_at', $start+180],
            ['<=', 'created_at', $end+180],
        ]);
        $allCount = $missQuery->select(['count'=>'COUNT(id)'])->scalar();
        $miss = $missQuery->select([$field, 'count'=>'COUNT(id)'])->indexBy($field)->groupBy($field)->asArray()->all();
        // 重构数组
        $counts = array_map(function($item) {
            return $item['count'];
        }, $miss);

        return [$counts, $allCount];
    }
}