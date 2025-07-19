<?php
namespace common\kj;
use backend\models\KjConfig;
use backend\service\HN0898Service;
use common\exceptions\InfoException;
use common\service\BaseService;
use common\service\cache\CacheKeyService;
use common\service\ssc\SscKjDataService;
use common\tools\Tool_Common;
use  yii;
use common\tools\KjDataGet;

class BaseKj extends BaseService {
    private static $currentQihao = '190125023';
    private static $tblEndQihao = '190125023';
    const LIMIT_GRAB_TIME = 180; # 新数据刚抓完3分钟内不允许再次抓取
    CONST SUCCESS_CODE = 20000;

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
     * 检测是否已经抓取
     * @param int $lotteryType
     * @return array
     */
    public static function checkHasOpened(int $lotteryType=DEFAULT_LOTTERY_TYPE): array
    {
        $kjData = self::getCurrentKjData($lotteryType, $currentQiHao);

        $SscKjData = SscKjDataService::getKjData($lotteryType, $currentQiHao);
        if(!empty($SscKjData) && ((time()-self::LIMIT_GRAB_TIME)<$SscKjData['created_at'])){
            $kjData = [
                'expect'=>$SscKjData['qihao'],
                'opencode'=>$SscKjData['code_str'],
                'opentime'=>date('Y-m-d H:i:s', $SscKjData['created_at'])
            ];
            Tool_Common::log('/kj_aozhou5/'.__FUNCTION__, ' INFO', '已存在号码？', ['lottery_type'=>$lotteryType, 'currentQiHao'=>$currentQiHao, 'kjData'=>$kjData]);
            return [self::SUCCESS_CODE, $currentQiHao, $kjData, '3分钟内新数据不需再次抓取'.$currentQiHao];
        }

        return [0, $kjData, '可以正常抓取'];
    }

    /**
     * @decipion 用于控制短时间内重复处理
     * @param int $lottery_type
     * @param int $seconds
     * @return bool
     * @throws InfoException
     */
    public static function lockGrab(int $lottery_type=26, int $seconds=300): bool
    {
        $exist_key = 'grab_lottery_data_x1_'.$lottery_type;
        $exist = \Yii::$app->redis->sadd($exist_key, $lottery_type);
        if(!$exist){
            throw_info('短时间内多次处理key:'.$exist_key, 30002);
        }
        \Yii::$app->redis->expire($exist_key, $seconds);

        return true;
    }

    /**
     * @desc 获取当前开奖数据，如果有则返回
     */
    public static function getCurrentKjData($lotteryType = DEFAULT_LOTTERY_TYPE, &$currentQiHao=''){
        $qiHao = HN0898Service::getCurrentQihao($lotteryType);
        $mKey = CacheKeyService::lotteryOpenDataKey($lotteryType, $qiHao);

        $currentQiHao = $qiHao;

        return commonRedis()->get($mKey);
    }

    /**
     * @param $qihao string 格式：20190125-030
     * @param $kjData - kjData:{"expect":20230326139,"opencode":"9,1,7,3,6","opentime":"2023-03-26 11:36:10"}
     * @return bool
     */
    public static function setKjDataCache($lottery_type = DEFAULT_LOTTERY_TYPE, string $qihao='', $kjData=[], $set_time=300): bool
    {
        //$set_time = ($set_time OR $set_time<300) ? 300 : $set_time;
        $mKey = CacheKeyService::lotteryOpenDataKey($lottery_type, $qihao);
        //Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '设置开奖缓存', ['lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'kjData'=>$kjData, 'set_time'=>$set_time]);
        if($kjData['opencode']){
            commonRedis()->setex($mKey, $set_time, $kjData);
        }

        return true;
    }

    public static function getOpenCodeLtKey($lottery_type=DEFAULT_LOTTERY_TYPE, $qihao=''): string
    {
        return __FUNCTION__.'_x0_'.$lottery_type.'_'.$qihao;
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
    public static function getApiHostByRoute(string $route = '/kj/cqssc/nine-num')
    {
        $domain = KjConfig::findOne(['path'=>$route])->api_host;

        return $domain;
    }

    /**
     * 输出xml格式
     * @param $expect
     * @param $openCode
     * @param $openTime
     * @return void
     */
    public static function outputXml($expect='', $openCode='', $openTime='')
    {
        header("Content-type: application/xml");
        echo'<?xml version="1.0" encoding="utf-8"?>';
        echo '<xml><row expect="'."$expect".'" opencode="'."$openCode".'" opentime="'."$openTime".'" /></xml>';
        ob_end_flush();exit;
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
        //p([$lottery_type, $expect, $kjData]);
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
