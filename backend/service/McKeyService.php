<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use  yii;

class McKeyService{

    /**
     * @desc 数据统计key
     * @param $key
     * @param int $lottery_type
     * @return string
     */
    public static function buildStaticMKey($key, $lottery_type = DEFAULT_LOTTERY_TYPE){

        $qihao = HN0898Service::getCurrentQihao($lottery_type);
        $mkey = \Yii::$app->params['DATA_STATIC_KEY'].'_'.$qihao.'_'.$key;

        return $mkey;
    }
}