<?php
namespace common\kj;
use backend\service\HN0898Service;
use  yii;
use common\tools\KjDataGet;

class BaseKj{
    private static $currentQihao = '190125023';
    private static $tblEndQihao = '190125023';

    public static function _init($lotteryType = 'ssc'){
        self::$currentQihao = HN0898Service::getCurrentQihao();
        $status = true;
        if($lotteryType == 'qxc'){
        }else{
            self::$tblEndQihao = KjDataGet::getEndQihao();
            self::$currentQihao = HN0898Service::getCurrentQihao();
            if(self::$tblEndQihao >= self::$currentQihao){
                $status = false;
            }
        }

        return $status;
    }

    /**
     * @desc 获取当前开奖数据，如果有则返回
     */
    public static function getCurrentKjData(){
        $m = \Yii::$app->cache;

        $qihao = HN0898Service::getCurrentQihao();
        $mkey = 'KJ_DATA_QIHAO_KEY_'.$qihao;

        return $m->get($mkey);
    }

    /**
     * @param $qihao string 格式：20190125-030
     * @param $kjData
     */
    public static function setKjDataCache($lottery_type = 2, $qihao, $kjData){
        $m = \Yii::$app->cache;

        $str = substr($qihao, 2, 10);
        $setQihao = str_replace('-', '',$str);
        if($kjData['opencode']){
            $mkey = 'KJ_DATA_QIHAO_KEY_'.$lottery_type.'_'.$setQihao;
            $m->set($mkey, $kjData, 10*60);
        }

        return true;
    }


}
