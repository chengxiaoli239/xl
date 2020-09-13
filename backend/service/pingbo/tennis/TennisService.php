<?php
namespace backend\service\pingbo\tennis;

use backend\service\wanbo\PingBoBaseService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class TennisService extends PingBoBaseService { #
    public static $baseUrl = 'https://www.ps3838.com';

    public static function getTennisGame(){
        $v = microtime(true) * 1000;
        $_ = $v + 60 * 40;
        $url = self::$baseUrl.'/sports-service/sv/odds/events?mk=2&sp=33&ot=1&btg=1&o=1&lg=&ev=&d=&l=3&v='.$v.'&me=0&more=false&c=CN&tm=0&g=&pa=0&cl=3&_g=1&_='.$_.'&locale=zh_CN';
        $rst = PingBoBaseService::getCurl($url);

        $data = $rst['l'][0][2][0]; # 第一行
        //$data = $rst['l'][0][2][1]; # 第二行
        p($data);
        $name = $rst['l'][0][2][0][1]; # ATP罗马站 - 资格赛

        return $data;
    }

}