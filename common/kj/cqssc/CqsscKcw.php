<?php
# 开彩网
namespace common\kj\cqssc;
use backend\service\CurlService;
use common\kj\BaseKj;
use backend\service\HN0898Service;
use common\tools\Tool_Common;
use  yii;

class CqsscKcw extends BaseKj {

    public static function getLotteryNo($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData()) {
            sleep(3);
            $url = 'http://wd.apiplus.net/tef05c6c66079ff29k/cqssc-3.json';
            //$content = file_get_contents($url);
            $content = CurlService::httpGet($url);
            //$data = json_decode($content,320);
            $data = $content;

            if (!$data OR !isset($data['data']) OR !$kjData = $data['data'][0]) return false;
            if (!$kjData) return false;
            $str = substr($kjData['expect'], 0, 8);
            $kjData['expect'] = str_replace($str, $str . '-', $kjData['expect']);
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        self::setKjDataCache($expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/lottery_xl/'.date('Ymd').'/cqssc_kcw', 'INFO', '号码抓取-kcw', $logArr);

        return $rst;
    }


}
