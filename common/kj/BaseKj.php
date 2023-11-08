<?php
namespace common\kj;
use backend\models\KjConfig;
use backend\service\HN0898Service;
use common\service\BaseService;
use common\tools\Tool_Common;
use  yii;
use common\tools\KjDataGet;

class BaseKj extends BaseService {
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
     * 用于控制短时间内重复处理
     * @param int $lotery_type
     * @param int $seconds
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function lockGrab(int $lotery_type=26, int $seconds=300): bool
    {
        $exist_key = 'grab_lottery_data_'.$lotery_type;
        $exist = \Yii::$app->redis->sadd($exist_key, $lotery_type);
        if(!$exist){
            throw_info('短时间内多次处理单号：'.$exist_key, 30002);
        }
        \Yii::$app->redis->expire($exist_key, $seconds);

        return true;
    }

    /**
     * @desc 获取当前开奖数据，如果有则返回
     */
    public static function getCurrentKjData($lottery_type = DEFAULT_LOTTERY_TYPE, &$current_qihao=''){
        $m = \Yii::$app->cache;

        $qihao = HN0898Service::getCurrentQihao($lottery_type);
        $mkey = self::buildKjDataKey($lottery_type, $qihao);

        $current_qihao = $qihao;

        return $m->get($mkey);
    }

    /**
     * @param $qihao string 格式：20190125-030
     * @param $kjData  kjData:{"expect":20230326139,"opencode":"9,1,7,3,6","opentime":"2023-03-26 11:36:10"}
     * @return bool
     */
    public static function setKjDataCache($lottery_type = DEFAULT_LOTTERY_TYPE, string $qihao='', $kjData=[], $set_time=300): bool
    {
        $m = \Yii::$app->cache;

        $set_time = ($set_time OR $set_time<300) ? 300 : $set_time;
        $mkey = self::buildKjDataKey($lottery_type, $qihao);
        Tool_Common::log('/kj_datas/'.__FUNCTION__, 'INFO', '设置开奖缓存', ['lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'kjData'=>$kjData, 'set_time'=>$set_time]);
        if($kjData['opencode']){
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
    public static function getApiHost($id): string
    {
        $domain = KjConfig::findOne($id)->api_host;

        return $domain;
    }

    /**
     * @desc 根据路由获取 domain
     * @param string $route
     * @return string
     */
    public static function getApiHostByRoute(string $route = '/kj/cqssc/nine-num'): string
    {
        $domain = KjConfig::findOne(['path'=>$route])->api_host;

        return $domain;
    }

    /**
     * @param $kjData
     * @param $lottery_type
     * @param string $returnType
     * @param $is_auto
     * @return array|void
     */
    public static function extracted($kjData, $lottery_type, string $returnType, $is_auto)
    {
        $opencode = $kjData['opencode']; # 开奖号码
        $opentime = $kjData['opentime']; # 开奖时间
        $expect = $kjData['expect']; # 期号
        //p([DEFAULT_LOTTERY_TYPE,$expect, $kjData]);
        self::setKjDataCache($lottery_type, $expect, $kjData);

        if ($returnType == 'xml') {
            header("Content-type: application/xml");
            echo '<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="' . "$expect" . '" opencode="' . "$opencode" . '" opentime="' . "$opentime" . '" /></xml>';
            ob_end_flush();
            exit;
        } else {
            $rst = ['expect' => $expect, 'opencode' => $opencode, 'opentime' => $opentime];
        }
        $logArr = array_merge($rst, [
            'is_auto' => $is_auto,
            'lottery_type' => $lottery_type,
        ]);
        Tool_Common::log('cqssc_kl8', 'INFO', '体彩网-号码抓取', $logArr);

        return $rst;
    }

}
