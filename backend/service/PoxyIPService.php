<?php
namespace backend\service;

use backend\models\TzSystemsUsers;
use backend\service\Lucky5\Lucky5Service;
use common\service\CommonService;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use  yii;

class PoxyIPService extends BaseService {

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
            'area' => '海南',
            'signature' => $API_KEY,
        ];
        $url = \Yii::$app->params['KUAI_POXY_API'].'/api/getdps/?'.http_build_query($query);
        $rst = CurlService::getCurl($url);

        Tool_Common::log('kuaiPoxy', 'ERR', '代理IP获取', ['url'=>$url, 'query'=>$query, 'rst'=>$rst]);
        if($rst['code'] != 0 OR empty($rst['data']['proxy_list'][0])){
            $m = \Yii::$app->cache;
            $mkey = 're_get_kuai_poxy';
            if(isset($rst['errno']) && in_array($rst['errno'], [28, 52]) && !$m->get($mkey)){
                $m->set($mkey, 1, 10);
                return self::kuaiPoxy(); # 获取代理失败，再次获取一次代理ip
            }
            return ['status'=>300, 'msg'=>'代理端口不可用'];
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
        $logArr = ['url'=>$url, 'rst'=>$rst];
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
     * @desc 自动脚本 - 预先判断缓存是否存在  每3-5秒检测一次缓存的ip，如果过期则重新获取代理IP缓存
     * @return array
     */
    public static function preGetValidIp($mod_uid = '', $is_auto = 1){

        $start_time = microtime(true);
        $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
        if(!$POXY_STATUS) return []; # CURL 代理开关

        $hasPlansActiveLottery = CommonService::hasPlansActiveLottery(\Yii::$app->params['NEED_PROXY_LOTTERYS']);
        if($is_auto == 1 && !$hasPlansActiveLottery){
            return [];
        }

        $redis = new RedisLock();
        $rKey = 'preGetValidIp_'.$mod_uid;
        if(!$redis->lock($rKey, 10)){
            sleep(15);
        }

        $m = \Yii::$app->cache;
        $time = 3600 * 4;

        $mkey = self::builProxyIpKey($mod_uid);
        $poxy_ip_data = $m->get($mkey);
        if(!empty($poxy_ip_data)){
            $isValid = PoxyIPService::isValid([$poxy_ip_data]);
            $isValidRst = PoxyIPService::kuaiIPValidTime([$poxy_ip_data]);
        }

        if(!$isValid OR $isValidRst['status'] != 200 OR $isValidRst['data'][$poxy_ip_data] < 60){
            # 调用失败或者可使用时间少于5分钟则认为IP失效
            $data = self::kuaiPoxy();
            if($data['status'] != 200) {
                return [];
            }
            $poxy_ip_data = $data['data'][0];
            $m->set($mkey, $poxy_ip_data, $time);
        }

        $logArr = ['IP'=>$poxy_ip_data, 'is_valid'=>$isValid, 'rst'=>$isValidRst];
        $end_time = microtime(true);
        $logArr['time_consume'] = ($end_time-$start_time).'s';
        Tool_Common::log('preGetIpValidStatus', 'INFO', '预先缓存代理IP', $logArr);

        return ['status'=>200, 'msg'=>'操作成功', 'data'=>$logArr];
    }

    /**
     * @desc 获取新ip优化
     * @return mixed
     */
    public static function getProxyIpNew($uid=0, $is_auto = 1){
        $mkey = self::builProxyIpKey($uid);

        $m = \Yii::$app->cache;
        $poxy_ip_data = $m->get($mkey);
        if(empty($poxy_ip_data)){
            $redisLock = new RedisLock();
            if($redisLock->lock($mkey.'_redis', 15)){
                PoxyIPService::preGetValidIp($uid);
            }else{
                sleep(10);
            }
        }
        $poxy_ip_data = $m->get($mkey);

        $logArr = ['IP'=>$poxy_ip_data, 'is_auto'=>$is_auto];
        Tool_Common::log('getProxyIpNew', 'INFO', '获取新ip优化', $logArr);

        return $poxy_ip_data;
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