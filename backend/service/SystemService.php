<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use  yii;

class SystemService{

    /**
     * @desc 数据统计key
     * @param $key
     * @param int $lottery_type
     * @return string
     */
    public static function initLottery($lottery_type = DEFAULT_LOTTERY_TYPE){

        $rst = HN0898Service::insertDsYl($lottery_type);

        return $rst;
    }

    /**
     * @desc 删除下注数据
     * @param $key
     * @param int $lottery_type
     * @return string
     */
    public static function delBetRecord($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        $tables = [
            'lt_betting_records', 'lt_bet_error_plans_task'
        ];

        foreach ($tables as $table){
            $sql = 'DELETE FROM '.$table. ' WHERE lottery_type='.$lottery_type;
            $rst['data'][$table] = \Yii::$app->db->createCommand($sql)->execute();//p($rst);
        }

        return $rst;
    }

}