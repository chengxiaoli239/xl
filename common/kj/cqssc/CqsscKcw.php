<?php
# 开彩网
namespace common\kj\cqssc;
use backend\service\CurlService;
use common\kj\BaseKj;
use backend\service\HN0898Service;
use common\tools\Tool_Common;
use  yii;

class CqsscKcw extends BaseKj {

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
        5 => '重庆时时彩', # 20分
        6 => '新疆时时彩', # 20分
    ];

    public static function getLotteryNo($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData()) {
            sleep(3);
            $url = 'http://wd.apiplus.net/tef05c6c66079ff29k/cqssc-3.json';
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

        self::setKjDataCache($lottery_type = 5, $expect, $kjData);

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
            $url = 'http://greeceloto.com/home/GetNumbers?lotteryId='.$lotteryId.'&pageNmuber=1&number=3&_=1556344774304';
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
        $logArr['lottery'] = CqsscKcw::$lotteryNameArr[$lotteryId];
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kcw', 'INFO', '号码抓取-kcw', $logArr);

        return $rst;
    }



}
