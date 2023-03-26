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
        $mkey = self::buildKjDataKey($lottery_type, $qihao);

        return $m->get($mkey);
    }

    /**
     * @param $qihao string 格式：20190125-030
     * @param $kjData  kjData:{"expect":20230326139,"opencode":"9,1,7,3,6","opentime":"2023-03-26 11:36:10"}
     * @return bool
     */
    public static function setKjDataCache($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao='', $kjData=[]){
        $m = \Yii::$app->cache;

        $set_time = 5*60;
        if($lottery_type == 5) {
            $str = substr($qihao, 2, 10);
            $qihao = str_replace('-', '', $str);
        }elseif (in_array($lottery_type, [10, 11, 12, 13, 19,20,21,22, 23])){ # 冰岛3分  90s
            $set_time = 10;
        }else{
            $qihao = $qihao;
        }
        if($kjData['opencode']){
            $mkey = self::buildKjDataKey($lottery_type, $qihao);
            $m->set($mkey, $kjData, $set_time);
        }

        return true;
    }

    public static function buildKjDataKey($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao=''){

        $mkey = 'KJ_DATA_QIHAO_KEY_'.$lottery_type.'_'.$qihao;

        return $mkey;
    }

    /**
     * @param integer $id
     * @return string
     */
    public static function getApiHost($id){
        $domain = KjConfig::findOne($id)->api_host;

        return $domain;
    }

    /**
     * @desc 根据路由获取 domain
     * @param string $route
     * @return string
     */
    public static function getApiHostByRoute($route = '/kj/cqssc/nine-num'){
        $domain = KjConfig::findOne(['path'=>$route])->api_host;

        return $domain;
    }

}
