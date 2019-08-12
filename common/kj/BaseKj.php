<?php
namespace common\kj;
use backend\models\KjConfig;
use backend\service\HN0898Service;
use  yii;
use common\tools\KjDataGet;

class BaseKj{
    private static $currentQihao = '190125023';
    private static $tblEndQihao = '190125023';

    public static function _init($lotteryType = DEFAULT_LOTTERY_TYPE){
        self::$currentQihao = HN0898Service::getCurrentQihao($lotteryType);
        $status = true;
        if($lotteryType == 'qxc'){
        }else{
            self::$tblEndQihao = KjDataGet::getEndQihao($lotteryType);
            self::$currentQihao = HN0898Service::getCurrentQihao($lotteryType);
            if(self::$tblEndQihao >= self::$currentQihao){
                $status = false;
            }
        }

        return $status;
    }

    /**
     * @desc 获取当前开奖数据，如果有则返回
     */
    public static function getCurrentKjData($lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;

        $qihao = HN0898Service::getCurrentQihao($lottery_type);
        $mkey = 'KJ_DATA_QIHAO_KEY_'.$lottery_type.'_'.$qihao;

        return $m->get($mkey);
    }

    /**
     * @param $qihao string 格式：20190125-030
     * @param $kjData
     */
    public static function setKjDataCache($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao, $kjData){
        $m = \Yii::$app->cache;

        if($lottery_type == 5){
            $str = substr($qihao, 2, 10);
            $setQihao = str_replace('-', '',$str);
        }else{
            $setQihao = $qihao;
        }
        if($kjData['opencode']){
            $mkey = 'KJ_DATA_QIHAO_KEY_'.$lottery_type.'_'.$setQihao;
            $m->set($mkey, $kjData, 10*60);
        }

        return true;
    }

    /**
     * @param $id
     * @return string
     */
    public static function getApiHost($id){
        $domain = KjConfig::findOne($id)->api_host;

        return $domain;
    }


}
