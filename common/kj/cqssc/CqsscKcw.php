<?php
# 开彩网
namespace common\kj\cqssc;
use backend\models\KjConfig;
use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\service\CurlService;
use common\kj\BaseKj;
use backend\service\HN0898Service;
use common\tools\Tool_Common;
use  yii;

class CqsscKcw extends BaseKj {
    public static $lottery_type = 5;

    public static $lotteryTypeArr = [
        5 => 1, # 5:1.5分
        6 => 2, # 6:3分
        7 => 3, # 7:5分
        8 => 4, # 8:10分
    ];
    public static $lotteryNameArr = [
        1 => '希腊1.5分', # 5:1.5分
        2 => '希腊3分', # 6:3分
        3 => '希腊5分', # 7:5分
        4 => '希腊10分', # 8:10分
        5 => '重庆', # 20分
        6 => '新疆', # 20分
        7 => '北京快乐8', # 5分
        8 => '幸运五星', # 幸运五星系统彩 5分
    ];

    public static function getLotteryNo($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHost(6);
            sleep(3);
            $url = $domain.'/tef05c6c66079ff29k/cqssc-3.json';
            //$content = file_get_contents($url);
            $content = CurlService::httpGet($url);
            //$data = json_decode($content,320);
            $data = $content;
            //p($data);

            if (!$data OR !isset($data['data']) OR !$kjData = $data['data'][0]) return false;
            if (!$kjData) return false;
            $str = substr($kjData['expect'], 0, 8);
            $kjData['expect'] = str_replace($str, $str . '-', $kjData['expect']);
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        self::setKjDataCache($lottery_type = DEFAULT_LOTTERY_TYPE, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kcw', 'INFO', '号码抓取-kcw', $logArr);

        return $rst;
    }

    /**
     * @desc 直播网
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryNoZhiBo($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHost(12);
            sleep(3);
            $date = date('Y-m-d');
            if('00:00' < date('H:i:s') && date('H:i:s') < '03:00'){
                $date = date('Y-m-d', time()-86400);
            }
            $url = $domain.'/data/cqssc/lotteryList/'.$date.'.json?t='.time();
            //$content = file_get_contents($url);
            $content = CurlService::httpGet($url);
            //$data = json_decode($content,320);
            $data = $content[0];

            if (!isset($data['issue']) OR !$data) return false;
            $str = substr($data['issue'], 0, 8);
            $kjData['expect'] = str_replace($str, $str . '-', $data['issue']);
            $kjData['opencode'] = implode(',', $data['openNum']);
            $kjData['opentime'] = $data['openDateTime'];
            //p($kjData);
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        self::setKjDataCache(self::$lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc', 'INFO', '号码抓取-直播网', $logArr);

        return $rst;
    }

    /**
     * @desc 直播网 - 重庆
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryNoNineNum($returnType = 'json'){

        if(true OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHostByRoute('/kj/cqssc/nine-num');
            $url = $domain.'/api/v1/result/service/mobile/results/hist/HF_CQSSC?limit=4&brand=09cp'; # limit 数量
            //$content = file_get_contents($url);
            $content = CurlService::httpGet($url);
            //$data = json_decode($content,320);
            $data = $content[0];

            if (!isset($data['uniqueIssueNumber']) OR !$data) return false;
            $str = substr($data['uniqueIssueNumber'], 0, 8);
            $kjData['expect'] = str_replace($str, $str . '-', $data['uniqueIssueNumber']); # 20200427-059
            $kjData['opencode'] = $data['openCode']; # 1,4,3,5,1
            $kjData['opentime'] = date('Y-m-d H:i:s', strtotime($data['openTime']));
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        self::setKjDataCache(self::$lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc', 'INFO', '号码抓取-直播网', $logArr);

        return $rst;
    }

    /**
     * @desc 1998彩票集团网：https://www.20041998.com/
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryNoOneNineNineEight($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHost(17);
            sleep(3);
            $_t = microtime(true) * 10000;
            $url = $domain.'/static/data/1CurIssue.json?_t='.$_t;
            //$content = file_get_contents($url);
            $content = CurlService::httpGet($url);
            $data = $content;

            if (!$data) return false;
            $str1 = substr($data['issue'], 0, 8);
            $str2 = substr($data['issue'], 8, 3);
            $kjData['expect'] = $str1 . '-' .$str2;
            $kjData['opencode'] = $data['nums'];
            $kjData['opentime'] = $data['opentime'];
            //$kjData = ['expect'=>20190925-006, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        self::setKjDataCache($lottery_type = DEFAULT_LOTTERY_TYPE, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kcw', 'INFO', '号码抓取-kcw', $logArr);

        return $rst;

    }

    /**
     * @desc 希腊ssc
     * @param string $returnType
     * @param int $type
     * @return array|bool
     */
    public static function getLotteryNoXl($lotteryId = 2, $returnType = 'json' ){

        if(!$kjData = self::getCurrentKjData()) {
            //sleep(3);
            //$lotteryId = self::$lotteryTypeArr[$type];
            $domain = BaseKj::getApiHost($lotteryId);
            $url = $domain.'/home/GetNumbers?lotteryId='.$lotteryId.'&pageNmuber=1&number=3&_=1556344774304';
            //$content = file_get_contents($url);
            $content = CurlService::httpGet($url);
            //$data = json_decode($content,320);
            $data = $content;
            //p($content);

            if (!$data OR !isset($data['LsPeridos']) OR !$kjData = $data['LsPeridos'][0]) return false;
            if (!$kjData) return false;
            $str = substr($kjData['expect'], 0, 8);
            $kjData['expect'] = str_replace($str, $str . '-', $kjData['expect']);
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }
        $opencode = $kjData['ResultNumber'];
        preg_match('/\d+/', $kjData['DrawDt'], $matches);
        $opentime = date('Y-m-d H:i:s', $matches[0]/1000);
        $expect = substr($kjData['PeriodsNumber'], 0,8).'-'.substr($kjData['PeriodsNumber'], 8);

        $lottery_type = $lotteryId;
        self::setKjDataCache(self::$lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        $logArr['lottery'] = CqsscKcw::$lotteryNameArr[$lotteryId];
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kcw', 'INFO', '号码抓取-kcw', $logArr);

        return $rst;
    }

    /**
     * @desc 北京快乐8
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryKuaiLe8($returnType = 'json', $lottery = 18){

        if(!$kjData = self::getCurrentKjData($lottery_type = 7)) {
            $domain = BaseKj::getApiHost(10);
            $post_data = [
                'lottery' => $lottery,
                'BeginDt'=> date('Y-m-d'),
                'EndDt'=> date('Y-m-d'),
                'pageSize'=> 20,
                'PageNum'=> 1,
            ];
            $url = $domain.'/api/MemberDesk/GetPeriodsResult?'.http_build_query($post_data);
            $tz_systems_users_id = SystemConfig::findOne(['key'=>'kuaile8_get_kj_user_id'])->value;
            $TzSystemsUsers = TzSystemsUsers::findOne($tz_systems_users_id);

            $headers = [
                'Accept: application/json, text/plain, */*',
                'Accept-Encoding: gzip, deflate',
                'Accept-Language:zh-CN,zh;q=0.9,en;q=0.8',
                'Connection: keep-alive',
                'Cookie: '.$TzSystemsUsers->cookie,
                'Host: '.str_replace('http://', '',$domain),
                'Referer:'.$domain,
                $TzSystemsUsers->user_agent,
            ];
            $start_time = microtime(true);
            $content = CurlService::getCurl($url, $headers);
            $end_time = microtime(true);
            $time_consume = ($end_time-$start_time).'s';
            $logArr = ['url'=>$url, 'headers'=>$headers, 'rst'=>$content, 'time_consume'=>$time_consume];
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kl8', 'INFO', '号码抓取-kcw', $logArr);

            $data = $content;
            //p(['url'=>$url, 'headers'=>$headers, 'post_data'=>$post_data, 'rstData'=>$data]);

            if (!$data OR !isset($data['Data']) OR !$kjData = $data['Data']['List'][0]) return false;
            if (!$kjData) return false;
            $str = substr($kjData['expect'], 0, 8);
            $kjData['expect'] = str_replace($str, $str . '-', $kjData['periodsNumber']);
            $kjData['opencode'] = $kjData['resultNumber'];
            $kjData['opentime'] = str_replace('/', '-',$kjData['drawDt']);
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];
        //p([DEFAULT_LOTTERY_TYPE,$expect, $kjData]);

        self::setKjDataCache($lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kl8', 'INFO', '号码抓取-kcw', $logArr);

        return $rst;
    }

    /**
     * @desc 北京快乐8
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryKuaiLe8NineNine($returnType = 'json', $lottery = 18){

        if(true OR !$kjData = self::getCurrentKjData($lottery_type = 7)) {
            //$domain = BaseKj::getApiHost(11);
            $domain = BaseKj::getApiHostByRoute('/kj/kuai-le8/nine-nine');
            $url = $domain.'/k8/ajax.aspx';

            $post_data = ['act'=>'getlastkj'];
            $headers = [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36',
            ];
            $start_time = microtime(true);
            $content = CurlService::postCurl($url, http_build_query($post_data), $headers);
            $end_time = microtime(true);
            $time_consume = ($end_time-$start_time).'s';
            $logArr = ['url'=>$url, 'headers'=>$headers, 'post_data'=>$post_data, 'rst'=>$content, 'time_consume'=>$time_consume];
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kl8_99', 'INFO', '号码抓取-kcw', $logArr);

            $data = $content;
            //p(['url'=>$url, 'headers'=>$headers, 'post_data'=>$post_data, 'rstData'=>$data]);

            if (!$data OR !isset($data) OR !$kjData = $data[0]) return false;
            if (!$kjData) return false;
            $kjData['expect'] = $kjData['qihao'];
            $codesArr = explode(',',$kjData['code']);
            $code1 = substr($codesArr[0] + $codesArr[5] + $codesArr[10] + $codesArr[15], -1);
            $code2 = substr($codesArr[1] + $codesArr[6] + $codesArr[11] + $codesArr[16], -1);
            $code3 = substr($codesArr[2] + $codesArr[7] + $codesArr[12] + $codesArr[17], -1);
            $code4 = substr($codesArr[3] + $codesArr[8] + $codesArr[13] + $codesArr[18], -1);
            $code5 = substr($codesArr[4] + $codesArr[9] + $codesArr[14] + $codesArr[19], -1);
            $kjData['opencode'] = $code1.','.$code2.','.$code3.','.$code4.','.$code5;
            $kjData['opentime'] = date('Y-m-d H:i:s');
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];
        //p([DEFAULT_LOTTERY_TYPE,$expect, $kjData]);

        self::setKjDataCache($lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kl8', 'INFO', '99彩票网-号码抓取', $logArr);

        return $rst;
    }

    /**
     * @desc 北京快乐8  800网 , $lottery_type = 7
     * @param string $returnType
     * @return array|bool 返回格式(数组)：{"expect":"2020100623","opencode":"0,8,6,3,6","opentime":"2020-10-06 17:41:38"}
     */
    public static function getLotteryKuaiLe8Eight($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData($lottery_type = 7)) {
            $domain = BaseKj::getApiHostByRoute('/kj/kuai-le8/eight');
            $url = $domain.'/getbasebjkl8shicai2?lotCode=30010';

            $start_time = microtime(true);
            $content = CurlService::getCurl($url);
            $end_time = microtime(true);
            $time_consume = ($end_time-$start_time).'s';
            $logArr = ['url'=>$url, 'rst'=>$content, 'time_consume'=>$time_consume];
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kl8_99', 'INFO', '号码抓取-kcw', $logArr);

            $data = $content;

            if (!isset($data) OR !$data OR !$kData = $data['result']['data']) return false;
            $kjData['expect'] = $kData['preDrawIssue'];
            $kjData['opencode'] = $kData['preDrawCode'];
            $kjData['opentime'] = date('Y-m-d H:i:s');
            //p($kjData);
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ] # 返回格式
        }
        $opencode = $kjData['opencode']; # 开奖号码
        $opentime = $kjData['opentime']; # 开奖时间
        $expect = $kjData['expect']; # 期号
        //p([DEFAULT_LOTTERY_TYPE,$expect, $kjData]);

        self::setKjDataCache($lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kl8', 'INFO', '99彩票网-号码抓取', $logArr);

        return $rst;
    }
}
