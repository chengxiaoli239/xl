<?php
# 360 彩票网
namespace common\kj\cqssc;
use backend\service\CurlService;
use common\kj\BaseKj;
use common\tools\Tool_Common;
use  yii;

class Cqssc360 extends BaseKj {

    public static function getLotteryNo($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData()){
            $domain = BaseKj::getApiHost(5);
            $url = $domain.'/zst/qkj/?lotId=255401';
            $content=file_get_contents($url);
            //$content = CurlService::httpGet($url);
            $kjData = json_decode($content,320);
            $str = substr($kjData[0]['Issue'],0,6);
            $expect = '20'.str_replace($str, $str.'-', $kjData[0]['Issue']);
            $code = $kjData[0]['WinNumber'];
            if(!$code) return false;
            $opencode = implode(',',[$code[0],$code[1],$code[2],$code[3],$code[4]]);

            $opentime = $kjData[0]['EndTime'];
            $kjData = [ 'expect' => $expect, 'opencode' => $opencode, 'opentime'=>$opentime ];
        }
        $expect = $kjData['expect'];
        $opentime = $kjData['opentime'];
        $opencode = $kjData['opencode'];

        # 设置开奖数据缓存
        self::setKjDataCache($lotter_type =5, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_360', 'INFO', '号码抓取-360', $logArr);
        return $rst;
    }

}
