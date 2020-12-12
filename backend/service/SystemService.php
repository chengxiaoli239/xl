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
}