<?php
namespace common\service\proxy;

use backend\models\ProxyIpRecords;
use backend\models\TzSystemsUsers;
use backend\service\BetService;
use backend\service\CurlService;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use  yii;

class ProxyZhiMaService {

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
        # num=1&type=1&pro=&city=0&yys=0&port=1&time=1&ts=0&ys=0&cs=0&lb=1&sb=0&pb=4&mr=1&regions=
        $query = [
            'num' => $num,
            'type' => 1, #
            'pro' => '', #
            'city' => 0,
            'yys' => 0,
            'port' => 1,
            'time' => 1,
            'ts' => 0,
            'ys' => 0,
            'cs' => 0,
            'lb' => 1,
            'sb' => 0,
            'pb' => 4,
            'mr' => 1,
            'reginos' => '', #
        ];
        //$url = \Yii::$app->params['PROXY_ZHIMA_API'].'/getip3?'.http_build_query($query);
        $url = \Yii::$app->params['PROXY_ZHIMA_API'].'/getip3?num=1&type=2&pro=0&city=0&yys=0&port=11&time=3&ts=1&ys=0&cs=1&lb=1&sb=0&pb=4&mr=2&regions=330000,350000,440000,460000&gm=4';
        $rst = CurlService::getCurl($url);
        if($rst['code'] != 0){
            Tool_Common::log('/proxy/'.__FUNCTION__.'_err', 'INFO', '代理IP获取失败-芝麻', ['url'=>$url, 'query'=>$query, 'rst'=>$rst]);
            return ['status'=>201, 'msg'=>'获取代理失败'];
        }

        Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '代理IP获取-芝麻', ['url'=>$url, 'query'=>$query, 'rst'=>$rst]);

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
            # 芝麻代理
            $data = ProxyZhiMaService::getPoxyRemoteIp($num=1);
            Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '获取代理IP-芝麻代理1', ['data'=>$data]);
            if($data['status'] != 200) {
                return [];
            }
            $ip_data= $data['data'][0];
            $ip_addr = $ip_data['ip'].':'.$ip_data['port'];
            $ip = $ip_data['ip'];
            $port = $ip_data['port'];
            $now_time = time();
            $valid_time = strtotime($ip_data['expire_time']);
            $setDatas = [
                'ip_addr' => $ip_addr,
                'ip' => $ip,
                'port' => (string)$port,
                'proxy_type' => 2,
                'isp' => (string)$type,
                'city' => $ip_data['city'],
                'valid_time' => $valid_time,
                'expire_time' => $valid_time,
                'created_at' => $now_time,
                'updated_at' => $now_time,
            ];
            $ProxyIpRecords = new ProxyIpRecords();
            $ProxyIpRecords->setAttributes($setDatas);
            $flag = $ProxyIpRecords->save();
            if(!$flag){
                $logArr['err_msg'] = $ProxyIpRecords->getErrors();
            }
            Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '获取代理IP-芝麻代理2', $logArr);
        }catch (\Exception $exception){
            Tool_Common::log('/proxy/'.__FUNCTION__, 'ERR', '获取代理IP-芝麻代理-错误', ['type'=>$type, 'data'=>$data, 'err_msg'=>$exception->getMessage()]);
            return ['status'=>301, 'msg'=>$exception->getMessage()];
        }

        return ['status'=>200, 'ip_addr'=>$ip_addr];
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