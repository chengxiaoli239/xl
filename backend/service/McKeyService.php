<?php
/**
 * Created by PhpStorm.
 *   
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
    public static function buildStaticMKey($key, $lottery_type = DEFAULT_LOTTERY_TYPE, $qihao=''){

        $qihao = $qihao?:HN0898Service::getCurrentQihao($lottery_type);
        //$qihao = HN0898Service::getQihao($lottery_type);
        $mkey = \Yii::$app->params['DATA_STATIC_KEY'].'_0_'.$lottery_type.'_'.$qihao.'_'.$key;

        return $mkey;
    }
}
