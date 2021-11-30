<?php
namespace common\service\proxy;

use backend\models\ProxyIpRecords;
use backend\models\TzSystemsUsers;
use backend\service\BetService;
use backend\service\CurlService;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use  yii;

class ProxyDaiLiYunService {

    /**
     * @desc 获取代理ip和接口
     * @param $num = 1; # 提取IP数量
     */
    public static function getPoxyRemoteIp($num = 1){
        $time_HI = date("H:i");
        if('04:00'<$time_HI && $time_HI<'08:55'){
            return ['status'=>300, 'msg'=>'非下注时间段，不能获取IP'];
        }
        $API_KEY = BetService::getConfig('KUAI_POXY_API_KEY'); # 快代理 API Key
        // https://dev.kdlapi.com/api/getorderexpiretime?orderid=938684913491492&signature=vdany88efprusvlm16cb0is9wr9smb4q
        $RedisLock = new RedisLock();
        $Rkey = $API_KEY.'_redis';
        if(!$RedisLock->lock($Rkey.'_redis', 15)){
            sleep(10);
        }
        //$url = \Yii::$app->params['PROXY_DAILIYUN_API'].'/query.txt?key=NPE4D177DB&word=广东,福建,浙江&count='.$num.'&rand=false&ltime=7200&norepeat=true&detail=true'; # 代理云只能选择一个地区
        $url = \Yii::$app->params['PROXY_DAILIYUN_API'].'/query.txt?key=NPE4D177DB&word=广东&count='.$num.'&rand=false&ltime=7200&norepeat=true&detail=true';
        $rst = CurlService::getCurl($url);
        if($rst['code'] != 0){
            Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '代理IP获取-代理云', ['url'=>$url, 'rst'=>$rst]);
            return ['status'=>201, 'msg'=>'获取代理失败'];
        }

        Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '代理IP获取-代理云', ['url'=>$url, 'rst'=>$rst]);

        return ['status'=>200, 'data'=>$rst['data'], 'msg'=>'代理IP数据获取成功'];
    }

    /**
     * @desc 获取代理IP
     * @param int $type 1电信2联通
     * @return array
     */
    public static function getRemoteProxyIp($type=1){
        $time_HI = date("H:i");
        if('04:00'<$time_HI && $time_HI<'08:55'){
            return ['status'=>300, 'msg'=>'非下注时间段，不能获取IP'];
        }

        try {
            # 代理云
            $data = self::getPoxyRemoteIp($num=1);
            if($data['status'] != 200) {
                return [];
            }
            $ip_data= $data['data'][0];
            $ip_addr = $ip_data['ip'].':'.$ip_data['port'];
            $ip = $ip_data['ip'];
            $port = $ip_data['ip'];
            $now_time = time();
            $valid_time = strtotime($ip_data['expire_time']);
            $setDatas = [
                'ip_addr' => $ip_addr,
                'ip' => $ip,
                'port' => $port,
                'proxy_type' => 3,
                'isp' => (string)$type,
                'city' => $ip_data['city'],
                'valid_time' => $valid_time,
                'expire_time' => $valid_time,
                'created_at' => $now_time,
                'updated_at' => $now_time,
            ];
            $ProxyIpRecords = new ProxyIpRecords();
            $ProxyIpRecords->setAttributes($setDatas);
            $ProxyIpRecords->save();
        }catch (\Exception $exception){
            Tool_Common::log('/proxy/'.__FUNCTION__, 'ERR', '获取代理IP-代理云-错误', ['type'=>$type, 'err_msg'=>$exception->getMessage()]);
            return ['status'=>300, 'msg'=>$exception->getMessage()];
        }

        return ['status'=>200, 'ip_addr'=>$ip_addr];
    }

    /**
     * @desc 检测代理IP可用性
     * @param $url
     * @param array $data
     * @param int $timeout
     * @return bool|string
     */
    public static function check($url, $poxy_addr='', $timeout=30){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        if(strpos($url, 'ww662889') !== false){
            //curl_setopt($ch, CURLOPT_USERAGENT, ['Chrome 42.0.2311.135']);
        }

        if(!empty($poxy_addr)){
            //设置代理
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            curl_setopt($ch, CURLOPT_PROXY, $poxy_addr);
            //设置代理用户名密码（私密代理/独享代理）
            //如果是开放代理，请注释掉下面两句
            $username = \Yii::$app->params['KUAI_USERNAME'];
            $password = \Yii::$app->params['KUAI_PASSWORD'];
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$username}:{$password}");
        }

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);

        $start_time = microtime(true);
        $data = curl_exec($ch);
        $end_time = microtime(true);
        //d($data);
        $errno = curl_errno( $ch );
        $logArr = ['url'=>$url, 'errno'=>$errno, 'time_consume'=>($end_time-$start_time).'s'];
        Tool_Common::log('/poxyIP/'.__FUNCTION__, 'INFO', 'IP检测', $logArr);
        $flag = true;
        if($errno>0){
            $flag = false;
        }

        return $flag;
    }

}