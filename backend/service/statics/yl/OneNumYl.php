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
            list($pos, $todayCurrent, $currentMiss, $weekMiss, $monthMiss) = self::onePositionYl($pos, $lotteryType);
            for ($code=0; $code<=9; $code++){
            }
        }

        return [];
    }

    private static function onePositionYl($pos, $lotteryType=DEFAULT_LOTTERY_TYPE)
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
        $currentMiss = SscKjData::find()->select([$field, 'index_id'])->where($where)
            ->asArray()->groupBy($field)->orderBy('index_id DESC')->all();
        p($currentMiss);


        list($start, $end) = Timer::todayTime();p([date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end)]); # 本周时间

        list($start, $end) = Timer::todayTime();p([date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end)]); # 本月时间
    }

}