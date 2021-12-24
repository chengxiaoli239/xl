<?php
namespace backend\service\datas;

use backend\service\BaseService;
use backend\service\UserSysPlansService;
use common\tools\Tool_Common;

class DatasClearService extends BaseService{

    public static function clearBettingRecords($post){
        $lottery_types = UserSysPlansService::getMyLotteryTypes($uid=1);
        $db = \Yii::$app->db;

        foreach ($lottery_types as $lottery){
            $lottery_type = $lottery['lottery_type'];
            try {
                $date_nums = DatasClearService::getClearBeforeXDate($lottery_type);
                $dates = [];
                for ($i=0; $i<$date_nums; $i++){
                    $dates[] = date('Y-m-d', time()-$i*86400);
                }
                $count_sql = 'SELECT COUNT(id) FROM {{%betting_records}} WHERE create_time NOT REGEXP "'.implode('|', $dates).'" AND lottery_type='.$lottery_type;
                $rst_count = $db->createCommand($count_sql)->queryScalar();
                $delete_sql = 'DELETE FROM {{%betting_records}} WHERE create_time NOT REGEXP "'.implode('|', $dates).'" AND lottery_type='.$lottery_type;
                $rst_delete = $db->createCommand($delete_sql)->execute();

                $logArr = ['lottery_type'=>$lottery_type, 'count_sql'=>$count_sql, 'rst_count'=>$rst_count, 'delete_sql'=>$delete_sql, 'rst_delete'=>$rst_delete];
                Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '清理数据', $logArr);
            }catch (\Exception $exception){
                Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '清理数据', ['lottery_type'=>$lottery_type, 'err_msg'=>$exception->getMessage()]);
                return false;
            }
        }

        return true;
    }


    /**
     * @desc 保留最近几天的下注记录
     * @param int $lottery_type
     * @return int
     */
    public static function getClearBeforeXDate($lottery_type = DEFAULT_LOTTERY_TYPE){
        $date_nums = 2;
        if(in_array($lottery_type, [1, 17])){
            //$date_nums = 30;
        }

        return $date_nums;
    }
}