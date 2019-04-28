<?php
# 开彩网
namespace common\kj\qxc;
use backend\service\CurlService;
use  yii;

class QxcKcw{

    public static function getLotteryNo($returnType = 'json'){

        //$url='http://wd.apiplus.net/tef05c6c66079ff29k/cqssc-3.json';
        $url='http://f.apiplus.net/qxc.json';
        //$content = file_get_contents($url);
        $content = CurlService::httpGet($url);
        //$data = json_decode($content,320);
        $data = $content;

        if(!$data OR !isset($data['data']) OR !$kjData = $data['data'][0]) return false;
        $expect = $kjData['expect'];
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];

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
