<?php
namespace backend\service;

use common\tools\Tool_Common;
use  yii;

class PoxyIPService extends BaseService {

    /**
     * @desc 获取代理ip和接口
     * @param $num = 1; # 提取IP数量
     */
    public static function kuaiPoxy($num = 1){
        $API_KEY = BetService::getConfig('KUAI_POXY_API_KEY'); # 快代理 API Key
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
        $rst = CurlService::getCurl($url);

        if($rst['code'] != 0 OR empty($rst['data']['proxy_list'][0])){
            Tool_Common::log('kuaiPoxy', 'ERR', '代理IP获取', ['url'=>$url, 'query'=>$query, 'rst'=>$rst]);
            return ['status'=>300, 'msg'=>'代理端口不可用'];
        }

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

        return ['status'=>200, 'data'=>$rst['data']['proxy_list'], 'msg'=>'代理IP数据获取成功'];
    }

    public static function getPoxyIp(){
        //return ['171.83.165.196', '20000'];
        $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
        if($POXY_STATUS == 0){
            # CURL 代理开关
            return [];
        }
        $m = \Yii::$app->cache;
        $time = 3600 * 3;
        $mkey = 'getPoxyIp_Kuai_0';
        if(!$poxy_ip_data = $m->get($mkey)){
            $data = self::kuaiPoxy();
            //return $data;
            $poxy_ip_data = explode(':', $data['data'][0]);
            $m->set($mkey, $poxy_ip_data, $time);
            if($data['status'] != 200) {
                return [];
            }
        }
        return $poxy_ip_data;
    }

    public static function kuaiPoxyExpire(){
        $query = [
            'orderid' => \Yii::$app->params['KUAI_POXY_ORDER_ID'], # 快代理订单号
            'signature' => BetService::getConfig('KUAI_POXY_API_KEY'), # 配置
        ];
        $url = \Yii::$app->params['KUAI_POXY_API'].'/api/getorderexpiretime/?'.http_build_query($query);

        $rst = CurlService::getCurl($url);
        if($rst['code'] != 0 OR $rst['data']['expire_time']<date("Y-m-d H:i:s")){
            return ['status'=>300, 'msg'=>'使用时间过期'];
        }

        return ['status'=>200, 'expire'=>$rst['data']['expire_time']];
    }
}