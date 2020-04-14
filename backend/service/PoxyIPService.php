<?php
namespace backend\service;

use  yii;

class PoxyIPService extends BaseService {

    public static function kuaiPoxy(){
        $API_KEY = BetService::getConfig('KUAI_POXY_API_KEY'); # 快代理 API Key
        $num = 1; # 提取IP数量
        // https://dev.kdlapi.com/api/getorderexpiretime?orderid=938684913491492&signature=vdany88efprusvlm16cb0is9wr9smb4q

        $query = [
            'orderid' => \Yii::$app->params['KUAI_POXY_ORDER_ID'], # 快代理订单号
            'num' => $num,
            'pt' => 1, # 1、http/https,返回http代理的端口号 2、socks4/socks5,返回socks代理的端口号
            'format' => 'json', # json、xml
            'sep' => 1,
            'signature' => $API_KEY,
        ];
        $url = \Yii::$app->params['KUAI_POXY_API'].'/api/getdps/?'.http_build_query($query);
        p($url);

        /*
        IPS :
        36.41.128.60
        125.87.96.94
        60.13.42.108
        113.138.133.210
        110.86.174.182
        182.87.127.158
        171.80.186.62
        36.41.128.129
        27.30.22.230
        125.87.107.170
        */

    }

    public static function kuaiPoxyExpire(){

        $query = [
            'orderid' => \Yii::$app->params['KUAI_POXY_ORDER_ID'], # 快代理订单号
            'signature' => BetService::getConfig('KUAI_POXY_API_KEY'), # 配置
        ];
        $url = \Yii::$app->params['KUAI_POXY_API'].'/api/getorderexpiretime/?'.http_build_query($query);

        $rst = CurlService::getCurl($url);
        p($rst);
        p($rst['data']['expire_time']);
        if($rst['code'] != 0 OR $rst['data']['expire_time']<date("Y-m-d H:i:s")){
            return ['status'=>300, 'msg'=>'使用时间过期'];
        }

        return ['status'=>200, 'expire'=>$rst['data']['expire_time']];
    }
}