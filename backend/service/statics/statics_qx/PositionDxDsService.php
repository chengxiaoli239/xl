<?php
namespace backend\service\statics\statics_qx;
use backend\models\statics\StaticPositionTypeArisePerdate;
use backend\service\BaseService;
use common\tools\Tool_Common;

class PositionDxDsService extends BaseService {

    /**
     * 统计大小、单双数量统计
     * @param int $lotteryType
     * @param $date
     * @return bool
     */
    public static function staticPositionDxDs(int $lotteryType=DEFAULT_LOTTERY_TYPE, $date=''): bool
    {
        try {
            $nowTime = time();
            $date = $date?:date('Y-m-d');
            $types = StaticPositionTypeArisePerdate::TYPE_OPTIONS;
            // 构建 SQL 查询，统计大小和单双
            $query = "
            SELECT
                SUM(CASE WHEN code1 >= 5 THEN 1 ELSE 0 END) AS p1_1,
                SUM(CASE WHEN code1 < 5 THEN 1 ELSE 0 END) AS p1_2,
                SUM(CASE WHEN code2 >= 5 THEN 1 ELSE 0 END) AS p2_1,
                SUM(CASE WHEN code2 < 5 THEN 1 ELSE 0 END) AS p2_2,
                SUM(CASE WHEN code3 >= 5 THEN 1 ELSE 0 END) AS p3_1,
                SUM(CASE WHEN code3 < 5 THEN 1 ELSE 0 END) AS p3_2,
                SUM(CASE WHEN code4 >= 5 THEN 1 ELSE 0 END) AS p4_1,
                SUM(CASE WHEN code4 < 5 THEN 1 ELSE 0 END) AS p4_2,
                SUM(CASE WHEN code5 >= 5 THEN 1 ELSE 0 END) AS p5_1,
                SUM(CASE WHEN code5 < 5 THEN 1 ELSE 0 END) AS p5_2,
                SUM(CASE WHEN code1 % 2 = 1 THEN 1 ELSE 0 END) AS p1_3,
                SUM(CASE WHEN code1 % 2 = 0 THEN 1 ELSE 0 END) AS p1_4,
                SUM(CASE WHEN code2 % 2 = 1 THEN 1 ELSE 0 END) AS p2_3,
                SUM(CASE WHEN code2 % 2 = 0 THEN 1 ELSE 0 END) AS p2_4,
                SUM(CASE WHEN code3 % 2 = 1 THEN 1 ELSE 0 END) AS p3_3,
                SUM(CASE WHEN code3 % 2 = 0 THEN 1 ELSE 0 END) AS p3_4,
                SUM(CASE WHEN code4 % 2 = 1 THEN 1 ELSE 0 END) AS p4_3,
                SUM(CASE WHEN code4 % 2 = 0 THEN 1 ELSE 0 END) AS p4_4,
                SUM(CASE WHEN code5 % 2 = 1 THEN 1 ELSE 0 END) AS p5_3,
                SUM(CASE WHEN code5 % 2 = 0 THEN 1 ELSE 0 END) AS p5_4
            FROM lt_ssc_kj_data
            WHERE date = :date AND lottery_type = :lotteryType
        ";

            // 运行查询
            $command = \Yii::$app->db->createCommand($query);
            $command->bindValue(':date', $date);
            $command->bindValue(':lotteryType', $lotteryType);
            $results = $command->queryOne();

            // 更新数据库
            foreach ($types as $type=>$name) {
                $where = ['date' => $date, 'type' => $type, 'lottery_type' => $lotteryType];
                $staticData = StaticPositionTypeArisePerdate::findOne($where);

                if (!$staticData) {
                    $staticData = new StaticPositionTypeArisePerdate();
                    $staticData->created_at = $nowTime;
                    $staticData->date = $date;
                    $staticData->type = $type;
                    $staticData->lottery_type = $lotteryType;
                }

                // 更新数量
                if ($type == StaticPositionTypeArisePerdate::TYPE_DX) {
                    $staticData->p1_1 = $results['p1_1'];
                    $staticData->p1_2 = $results['p1_2'];
                    $staticData->p2_1 = $results['p2_1'];
                    $staticData->p2_2 = $results['p2_2'];
                    $staticData->p3_1 = $results['p3_1'];
                    $staticData->p3_2 = $results['p3_2'];
                    $staticData->p4_1 = $results['p4_1'];
                    $staticData->p4_2 = $results['p4_2'];
                    $staticData->p5_1 = $results['p5_1'];
                    $staticData->p5_2 = $results['p5_2'];
                } else {
                    $staticData->p1_1 = $results['p1_3'];
                    $staticData->p1_2 = $results['p1_4'];
                    $staticData->p2_1 = $results['p2_3'];
                    $staticData->p2_2 = $results['p2_4'];
                    $staticData->p3_1 = $results['p3_3'];
                    $staticData->p3_2 = $results['p3_4'];
                    $staticData->p4_1 = $results['p4_3'];
                    $staticData->p4_2 = $results['p4_4'];
                    $staticData->p5_1 = $results['p5_3'];
                    $staticData->p5_2 = $results['p5_4'];
                }

                // 更新最后更新时间
                $staticData->updated_at = $nowTime;
                //p($staticData->attributes);

                // 保存数据
                if (!$staticData->save()) {
                    // 处理错误，记录日志等
                    \Yii::error("Failed to save StaticPositionTypeArisePerdate: " . json_encode($staticData->getErrors()));
                    return false;
                }
            }
        }catch (\Exception $e){
            Tool_Common::log('/statics/'.__FUNCTION__, 'ERR', '位置大小单双统计异常', ['lottery_type'=>$lotteryType, 'date'=>$date, 'err_msg'=>$e->getMessage()]);
            return false;
        }

        return true;
    }

}
