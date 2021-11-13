<?php
namespace common\service\proxy;

use backend\models\ProxyIpRecords;
use backend\models\TzSystemsUsers;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use  yii;

class ProxyZhiMaService {

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

        # 快代理
        $data = ProxyZhiMaService::getRemoteProxyIp($num=1);
        if($data['status'] != 200) {
            return [];
        }
        $ip_addr = $data['data'][0];
        $ip_addr_datas = explode(':', $data['data'][0]);;
        $ip = $ip_addr_datas[0];
        $port = $ip_addr_datas[1];
        $valid_time = ProxyZhiMaService::getProxyIpValidTime($ip_addr);
        $now_time = time();
        $setDatas = [
            'ip_addr' => $ip_addr,
            'ip' => $ip,
            'port' => $port,
            'proxy_type' => 2,
            'isp' => (string)$type,
            'valid_time' => $valid_time,
            'expire_time' => $valid_time,
            'created_at' => $now_time,
            'updated_at' => $now_time,
        ];
        $ProxyIpRecords = new ProxyIpRecords();
        $ProxyIpRecords->setAttributes($setDatas);
        $ProxyIpRecords->save();

        return ['status'=>200, 'ip_addr'=>$ip_addr];
    }

    /**
     * @desc 获取代理ip和接口
     * @param $num = 1; # 提取IP数量
     */
    public static function kuaiPoxy($num = 1){
        $time_HI = date("H:i");
        if('04:00'<$time_HI && $time_HI<'08:55'){
            return ['status'=>300, 'msg'=>'非下注时间段，不能获取IP'];
        }
        $KUAI_POXY_ORDER_ID = BetService::getConfig('KUAI_POXY_ORDER_ID'); # 快代理 订单id
        $API_KEY = BetService::getConfig('KUAI_POXY_API_KEY'); # 快代理 API Key
        // https://dev.kdlapi.com/api/getorderexpiretime?orderid=938684913491492&signature=vdany88efprusvlm16cb0is9wr9smb4q
        $RedisLock = new RedisLock();
        $Rkey = $API_KEY.'_redis';
        if(!$RedisLock->lock($Rkey.'_redis', 15)){
            sleep(10);
        }
        $query = [
            'orderid' => $KUAI_POXY_ORDER_ID, # 快代理订单号
            'num' => $num,
            'pt' => 1, # 1、http/https,返回http代理的端口号 2、socks4/socks5,返回socks代理的端口号
            'format' => 'json', # json、xml
            'sep' => 1,
            //'area' => '浙江,福建,江西,上海,湖北,江苏,广东',
            'area' => '福建,广东',
            'signature' => $API_KEY,
            'carrier' => 2, # 0: 不筛选(默认) 1: 联通 2: 电信 ]  此参数仅支持按IP付费订单
        ];
        $url = \Yii::$app->params['KUAI_POXY_API'].'/api/getdps/?'.http_build_query($query);
        $rst = CurlService::getCurl($url);

        Tool_Common::log('kuaiPoxy', 'INFO', '代理IP获取', ['url'=>$url, 'query'=>$query, 'rst'=>$rst]);
        if($rst['code'] != 0 OR empty($rst['data']['proxy_list'][0])){
            $m = \Yii::$app->cache;
            $mkey = 're_get_kuai_poxy';
            if(isset($rst['errno']) && in_array($rst['errno'], [28, 52]) && !$m->get($mkey)){
                $m->set($mkey, 1, 10);
                return self::kuaiPoxy(); # 获取代理失败，再次获取一次代理ip
            }
            return ['status'=>300, 'msg'=>'代理端口异常，不可用'];
        }

        return ['status'=>200, 'data'=>$rst['data']['proxy_list'], 'msg'=>'代理IP数据获取成功'];
    }

    /**
     * @desc 获取快代理的代理IP
     * @return array|mixed
     */
    public static function getPoxyIp($uid=0, $is_auto = 1){
        return PoxyIPService::getProxyIpNew($uid, $is_auto);
    }

    /**
     * @desc 有效期缓存key
     * @return string
     */
    public static function buildPoxyValidKey(){
        $v_mkey = 'KUAI_POXYIP_ValidTime';

        return $v_mkey;
    }

    /**
     * @desc
     * @return string
     */
    public static function builProxyIpKey($uid=''){
        $multi_status = BetService::getConfig('MULTI_PROXY_STATUS');
        $mol = $uid%2; # 求余
        $mkey = 'getPoxyIp_Kuai_1';
        if(!empty($uid) && $multi_status && $mol == 1){
            $mkey = $mkey.$mol;
        }

        return $mkey;
    }

    /**
     * @desc 清除代理IP
     * @return bool
     */
    public static function clearProxyIpKey(){
        $m = \Yii::$app->cache;
        $mkey = self::builProxyIpKey();
        $oldIP = $m->get($mkey);

        $flag = $m->delete($mkey);
        $newIP = self::getPoxyIp();
        return ['status'=>200, 'data'=>['new_ip'=>$newIP, 'old_ip'=>$oldIP]];
    }

    /**
     * @desc 代理账号过期时间
     * @return array
     */
    public static function kuaiPoxyExpire(){
        $KUAI_POXY_ORDER_ID = BetService::getConfig('KUAI_POXY_ORDER_ID'); # 快代理 订单id
        $query = [
            'orderid' => $KUAI_POXY_ORDER_ID, # 快代理订单号
            'signature' => BetService::getConfig('KUAI_POXY_API_KEY'), # 配置
        ];
        $url = \Yii::$app->params['KUAI_POXY_API'].'/api/getorderexpiretime/?'.http_build_query($query);

        $rst = CurlService::getCurl($url);
        if($rst['code'] != 0 OR $rst['data']['expire_time']<date("Y-m-d H:i:s")){
            return ['status'=>300, 'msg'=>'使用时间过期'];
        }

        return ['status'=>200, 'expire'=>$rst['data']['expire_time']];
    }

    /**
     * @desc 获取私密代理可用时长
     * @param $poxy_ip 单个：['113.120.61.166:22989']  或多个：['113.120.61.166:22989','122.4.44.132:21808']
     * @return array
     */
    public static function kuaiIPValidTime($poxy_ips = ''){
        if(strpos($poxy_ips[0], ':') === false) return ['status'=>300, 'msg'=>'IP格式错误，缺少冒号 ":"'];
        $m = \Yii::$app->cache;
        $mkey = 'retry_kuaiIPValidTime_key';

        $KUAI_POXY_ORDER_ID = BetService::getConfig('KUAI_POXY_ORDER_ID'); # 快代理 订单id
        $query = [
            'orderid' => $KUAI_POXY_ORDER_ID, # 快代理订单号
            'proxy' => implode(',', $poxy_ips),
            'signature' => BetService::getConfig('KUAI_POXY_API_KEY'), # 配置
        ];
        $url = \Yii::$app->params['KUAI_POXY_API'].'/api/getdpsvalidtime/?'.http_build_query($query);

        $rst = CurlService::getCurl($url, [], 6);
        if($rst['errno']>0 && !$r = $m->get($mkey)){
            $m->set($mkey, 1, 5);
            return self::kuaiIPValidTime($poxy_ips);
        }
        $logArr = ['poxy_ips'=>$poxy_ips, 'url'=>$url, 'rst'=>$rst];
        Tool_Common::log('kuaiIPValidTime', 'INFO', '获取私密代理可用时长', $logArr);
        if($rst['code'] != 0){ # 为确保稳定，使用时间少于60s则认为IP失效
            return ['status'=>301, 'msg'=>'接口调用失败'];
        }

        return ['status'=>200, 'data'=>$rst['data']];
    }

    /**
     * @desc 判断代理IP有效性
     * @param $poxy_ip array  ['122.7.3.56:17856', '122.8.8.56:176']
     * @return bool
     */
    public static function isValid($poxy_ips = [], $is_auto=1){
        $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
        if(!$POXY_STATUS && $is_auto) return false; # CURL 代理开关
        $m = \Yii::$app->cache;
        $mkey = 'retry_get_isValid_key';

        $url = 'https://www.baidu.com';
        $start_time = microtime(true);
        //$rst = CurlService::getCurl($url, [], 9);
        $checkRst = PoxyIPService::check($url, $poxy_ips[0], 8);
        $end_time = microtime(true);
        $consume_time = ($end_time-$start_time).'s';
        if(!$checkRst && !$r = $m->get($mkey)){
            $m->set($mkey, 1, 6);
            return self::isValid($poxy_ips);
        }

        Tool_Common::log('poxy_ip_is_valid','INFO', '判断代理IP有效性', ['url'=>$url, 'poxy_ips'=>$poxy_ips, 'rst'=>$checkRst, 'consume_time'=>$consume_time]);

        return  $checkRst;
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

    /**
     * @desc 获取ip可用截止时间
     * @param string $ip_addr
     * @param int $type
     * @return int|mixed
     */
    public static function getProxyIpValidTime($ip_addr='', $type = 1){
        $valid_time = time() + 10;

        if($type == 2){

        }else{
            # 快代理
            $isValidRst = ProxyBaseService::kuaiIPValidTime([$ip_addr]);
            if($isValidRst['status'] == 200){
                $valid_time = time() + $isValidRst['data'][$ip_addr];
            }
        }

        return $valid_time;
    }

    /**
     * @desc 设置某个代理ip为不可用
     * @param string $ip
     * @return bool
     */
    public static function setIpInvalid($ip_addr=''){
        $row = ProxyIpRecords::findOne(['ip_addr'=>$ip_addr]);
        if(!empty($row)){
            $row->status = 0;
            $flag = $row->save();
        }
        Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '设代理IP不可用', ['ip'=>$ip_addr, 'flag'=>$flag]);

        return true;
    }

    /**
     * @desc 获取可用ip
     * @return array|ProxyIpRecords|null
     */
    public static function getCurrentValidProxyIp(){
        $m = \Yii::$app->cache;
        $mkey = 'getValidProxyIp_xxx_0';
        $ip_addr = $m->get($mkey);
        if(!$ip_addr){
            $where = ['AND', ['=', 'status', 1], ['>', 'valid_time', time()-300]];
            $row = ProxyIpRecords::find()->where($where)->orderBy(['id'=>SORT_DESC])->one();
            $ip_addr = $row->ip_addr;
            $m->set($mkey, $ip_addr,15);
        }

        return $ip_addr;
    }

    /**
     * @desc 获取新ip优化
     * @return mixed
     */
    public static function getProxyIpNew($uid=0, $is_auto = 1){
        $ip_addr = PoxyIPService::getCurrentValidProxyIp();

        $logArr = ['ip_addr'=>$ip_addr, 'is_auto'=>$is_auto];
        Tool_Common::log('getProxyIpNew', 'INFO', '获取新ip优化', $logArr);

        return $ip_addr;
    }

    /**
     * @desc 代理id key
     * @return string
     */
    public static function buildProxyUidsKey(){
        return 'buildProxyUidsKey_0';
    }

    public static function delProxyUidsKey(){
        $m = \Yii::$app->cache;
        $mkey = self::buildProxyUidsKey();
        $flag = $m->delete($mkey);

        return $flag;
    }

    /**
     * @desc 使用代理用户uid lt_admin.id
     * @return array|mixed
     */
    public static function getProxyUids(){
        $m = \Yii::$app->cache;
        $mkey = self::buildProxyUidsKey();
        $uids = $m->get($mkey);
        if(empty($uids)){
            $TzSystemsUsers = TzSystemsUsers::findAll(['is_use_proxy'=>1]);
            $uids = yii\helpers\ArrayHelper::getColumn($TzSystemsUsers, 'uid');

            $m->set($mkey, $uids, 4*3600);
        }

        $uids = $uids ? $uids : [];

        return $uids;
    }

    /**
     * @desc 用户是否开启代理IP
     * @param string $uid
     * @return bool
     */
    public static function isOpenPoxyIPUser($uid=''){
        $flag = false;
        $uids = self::getProxyUids();
        if(in_array($uid, $uids)){
            $flag = true;
        }
        return $flag;
    }
}