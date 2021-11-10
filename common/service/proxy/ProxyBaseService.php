<?php
namespace backend\service;

use backend\models\ProxyIpRecords;
use common\service\CommonService;
use common\tools\Tool_Common;
use  yii;

class ProxyBaseService {

    public static $proxy_url = '';

    public static $ip_addr = '';

    public static $ip_port = '';

    /**
     * @param int $type
     * @return ProxyKuaiService|ProxyZhiMaService
     */
    public static function getProxyServiceModel($type = 1){
        if($type == 2){
            $model = new \backend\service\ProxyZhiMaService();
        }else{
            $model = new \backend\service\ProxyKuaiService();
        }

        return $model;
    }

    /**
     * @desc 清除代理IP
     * @return bool
     */
    public static function clearProxyIpKey(){
        $m = \Yii::$app->cache;
        $mkey = self::builProxyIpKey();
        $oldIP = $m->get($mkey);

        $m->delete($mkey);
        $newIP = self::getPoxyIp();

        return ['status'=>200, 'data'=>['new_ip'=>$newIP, 'old_ip'=>$oldIP]];
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
     * @desc 有效期缓存key
     * @return string
     */
    public static function buildPoxyValidKey(){
        $v_mkey = 'KUAI_POXYIP_ValidTime';

        return $v_mkey;
    }

    public static function getProxyType(){
        $PROXY_TYPE = BetService::getConfig('CURL_PROXY_TYPE') == 'PROXY_TYPE_2' ? 2 : 1; # PROXY_TYPE_1:快代理PROXY_TYPE_2芝麻代理

        return $PROXY_TYPE;
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
     * @desc 代理缓存key
     * @param int $proxy_type
     * @return string
     */
    public static function buildProxyIpKey($proxy_type=1){
        $mkey = 'getValidProxyIp_xxx_0_'.$proxy_type;

        return $mkey;
    }

    /**
     * @desc 获取可用ip
     * @param string $proxy_type
     * @return mixed|string
     */
    public static function getCurrentValidProxyIp($proxy_type=''){
        $m = \Yii::$app->cache;
        if(empty($type)){
            $proxy_type = ProxyBaseService::getProxyType();
        }
        $mkey = ProxyBaseService::buildProxyIpKey($proxy_type);
        $ip_addr = $m->get($mkey);
        if(!$ip_addr){
            $where = ['AND', ['=', 'proxy_type', $proxy_type], ['=', 'status', 1]];
            $row = ProxyIpRecords::find()->where($where)->orderBy(['id'=>SORT_DESC])->one();
            $ip_addr = $row->ip_addr;
            $m->set($mkey, $ip_addr,15);
            $left_time = $row->valid_time - time();
            if($left_time<300){
                $row->status = 0;
                $row->save();
                $ip_addr = '';
            }
        }

        return $ip_addr;
    }

    /**
     * @desc 自动脚本 - 预先判断缓存是否存在  每3-5秒检测一次缓存的ip，如果过期则重新获取代理IP缓存
     * @return array
     */
    public static function preGetValidIp($is_auto = 1){
        $start_time = microtime(true);
        $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
        if(!$POXY_STATUS) return ['status'=>300, 'msg'=>'代理IP开关未开启']; # CURL 代理开关

        $hasPlansActiveLottery = CommonService::hasPlansActiveLottery(\Yii::$app->params['NEED_PROXY_LOTTERYS']);
        if($is_auto == 1 && !$hasPlansActiveLottery){
            return [];
        }
        p('sadlfk');
        $is_need_get_new_ip = 0;

        $proxy_type = ProxyBaseService::getProxyType();
        $current_ip_addr = ProxyBaseService::getCurrentValidProxyIp(); # 获取当前可用的代理IP
        if(!empty($current_ip_addr)){
            $isValid = ProxyBaseService::isValid($current_ip_addr);
            if(!$isValid){
                $is_need_get_new_ip = 1;
                ProxyBaseService::setIpInvalid($current_ip_addr); # 设置当前ip无效
            }
        }else{
            $is_need_get_new_ip = 1;
        }

        $logArr = ['is_need_get_new_ip'=>$is_need_get_new_ip, 'proxy_type'=>$proxy_type, 'is_valid'=>$isValid, 'current_ip_addr'=>$current_ip_addr];
        if($is_need_get_new_ip){
            $new_ip_addr_data = ProxyBaseService::getRemoteProxyIp();
            $logArr['new_ip_addr_data'] = $new_ip_addr_data;
        }

        Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '获取代理ip-缓存', $logArr);
        $end_time = microtime(true);
        $logArr['time_consume'] = ($end_time-$start_time).'s';
        Tool_Common::log('preGetIpValidStatus', 'INFO', '预先缓存代理IP', $logArr);

        return ['status'=>200, 'msg'=>'操作成功', 'data'=>$logArr];
    }

    /**
     * @desc 获取代理IP
     * @return array
     */
    public static function getRemoteProxyIp(){
        $time_HI = date("H:i");
        return ['status'=>300, 'msg'=>'调试'];
        if('04:00'<$time_HI && $time_HI<'08:55'){
            return ['status'=>300, 'msg'=>'非下注时间段，不能获取IP'];
        }
        $proxy_type = ProxyBaseService::getProxyType();

        if($proxy_type == 2){
            $ip_addr_data = ProxyZhiMaService::getRemoteProxyIp();
        }else{
            $ip_addr_data = ProxyKuaiService::getRemoteProxyIp();
        }

        return $ip_addr_data; # ['status'=>200, 'ip_addr'=>$ip_addr];
    }

    /**
     * @desc 判断代理IP有效性
     * @param $poxy_ip array  ['122.7.3.56:17856', '122.8.8.56:176']
     * @return bool
     */
    public static function isValid($proxy_ips = [], $is_auto=1){
        $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
        if(!$POXY_STATUS && $is_auto) return false; # CURL 代理开关
        $m = \Yii::$app->cache;
        $mkey = 'retry_get_isValid_key';

        $url = 'https://www.baidu.com';
        $start_time = microtime(true);
        $checkRst = PoxyIPService::check($url, $proxy_ips[0], 8);
        $end_time = microtime(true);
        $consume_time = ($end_time-$start_time).'s';
        if(!$checkRst && !$r = $m->get($mkey)){
            $m->set($mkey, 1, 6);
            return self::isValid($proxy_ips);
        }

        Tool_Common::log('proxy_ip_is_valid','INFO', '判断代理IP有效性', ['url'=>$url, 'proxy_ips'=>$proxy_ips, 'rst'=>$checkRst, 'consume_time'=>$consume_time]);

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
}