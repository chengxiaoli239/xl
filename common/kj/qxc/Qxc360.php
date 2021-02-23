<?php
# 360 彩票网
namespace common\kj\qxc;
use backend\service\CurlService;
use  yii;

class Qxc360{

    public static function getLotteryNo($returnType = 'json'){
        $url='http://chart.cp.360.cn/zst/qkj/?lotId=255401';
        $content=file_get_contents($url);
        //$content = CurlService::httpGet($url);
        $data = json_decode($content,320);
        //$kjData = $content;

        if(!$data OR !isset($data['data']) OR !$kjData = $data['data'][0]) return false;
        $str = substr($kjData['Issue'],0,6);
        $expect = '20'.str_replace($str, $str.'-', $kjData['Issue']);

        $code = $kjData['WinNumber'];
        if(!$code) return false;
        $opencode = implode(',',[$code[0],$code[1],$code[2],$code[3],$code[4]]);

        $opentime=$kjData['EndTime'];

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            return ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
    }

}
