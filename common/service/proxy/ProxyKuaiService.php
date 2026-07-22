<?php
namespace common\service\proxy;

use backend\models\ProxyIpRecords;
use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\CurlService;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use  yii;

class ProxyKuaiService {

    const DPS_API = 'https://dps.kdlapi.com';
    const DEV_API = 'https://dev.kdlapi.com';
    const DEFAULT_VALID_SECONDS = 180;

    private static function getConfig($key, $default = ''){
        $row = SystemConfig::findOne(['key'=>$key]);
        if(empty($row) || $row->value === null || $row->value === ''){
            return $default;
        }

        return trim((string)$row->value);
    }

    private static function getOrderId(){
        return self::getConfig('KUAI_POXY_ORDER_ID');
    }

    private static function getSecretId(){
        return self::getConfig('KUAI_POXY_SECRET_ID');
    }

    private static function getSecretKey(){
        return self::getConfig('KUAI_POXY_SECRET_KEY', self::getConfig('KUAI_POXY_API_KEY'));
    }

    private static function getDpsApi(){
        return rtrim(\Yii::$app->params['KUAI_POXY_API'] ?? self::DPS_API, '/');
    }

    private static function getDefaultValidSeconds(){
        $seconds = (int)self::getConfig('KUAI_POXY_DEFAULT_VALID_SECONDS', self::DEFAULT_VALID_SECONDS);

        return $seconds > 0 ? $seconds : self::DEFAULT_VALID_SECONDS;
    }

    public static function isUseProxyAuth(){
        return (int)self::getConfig('KUAI_POXY_USE_AUTH', 0) === 1;
    }

    private static function buildProxyAuthKey($ipAddr){
        return 'kuai_proxy_auth_'.md5($ipAddr);
    }

    private static function buildFetchCacheKey(){
        $secretId = self::getSecretId() ?: self::getOrderId();

        return 'kuai_proxy_fetch_'.md5($secretId).'_last';
    }

    public static function clearFetchCache(){
        return \Yii::$app->cache->delete(self::buildFetchCacheKey());
    }

    private static function setProxyAuth($ipAddr, $username, $password, $ttl){
        if(!$ipAddr || !$username || !$password){
            return false;
        }
        $ttl = max(60, (int)$ttl);

        return \Yii::$app->cache->set(self::buildProxyAuthKey($ipAddr), [
            'username'=>$username,
            'password'=>$password,
        ], $ttl);
    }

    public static function getProxyAuth($ipAddr){
        if(!$ipAddr){
            return [];
        }
        $auth = \Yii::$app->cache->get(self::buildProxyAuthKey($ipAddr));

        return is_array($auth) ? $auth : [];
    }

    private static function buildApiUrl($baseUrl, $path, $params = []){
        $baseUrl = rtrim($baseUrl, '/');
        $secretId = self::getSecretId();
        $secretKey = self::getSecretKey();

        if($secretId && $secretKey){
            $params['secret_id'] = $secretId;
            $params['sign_type'] = 'hmacsha1';
            $params['timestamp'] = time();
            ksort($params);

            $query = [];
            foreach($params as $key=>$value){
                $query[] = $key.'='.$value;
            }
            $raw = 'GET'.$path.'?'.implode('&', $query);
            $params['signature'] = base64_encode(hash_hmac('sha1', $raw, $secretKey, true));

            return $baseUrl.$path.'?'.http_build_query($params);
        }

        $params['orderid'] = self::getOrderId();
        $params['signature'] = self::getConfig('KUAI_POXY_API_KEY');

        return $baseUrl.$path.'?'.http_build_query($params);
    }

    private static function maskUrl($url){
        $url = preg_replace('/(signature=)[^&]+/i', '$1***', $url);
        $url = preg_replace('/(secret_id=)([^&]{4})[^&]+/i', '$1$2***', $url);

        return $url;
    }

    private static function apiGet($baseUrl, $path, $params = [], $timeout = 10){
        $url = self::buildApiUrl($baseUrl, $path, $params);
        $rst = CurlService::getCurl($url, [], $timeout);
        Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '快代理接口请求', [
            'api'=>$path,
            'url'=>self::maskUrl($url),
            'rst'=>$rst,
        ]);

        return $rst;
    }

    private static function isWhitelistError($rst){
        $msg = $rst['msg'] ?? '';

        return isset($rst['code']) && (int)$rst['code'] === -108
            || stripos($msg, 'whitelist') !== false;
    }

    public static function getIpWhitelist(){
        $rst = self::apiGet(self::DEV_API, '/api/getipwhitelist', [], 10);
        if(isset($rst['code']) && (int)$rst['code'] === 0){
            return ['status'=>200, 'data'=>$rst['data'], 'msg'=>'白名单获取成功'];
        }

        return ['status'=>300, 'data'=>$rst, 'msg'=>$rst['msg'] ?? '白名单获取失败'];
    }

    public static function addWhiteIp($iplist = ''){
        $params = [];
        if($iplist){
            $params['iplist'] = $iplist;
        }

        $rst = self::apiGet(self::DEV_API, '/api/addwhiteip', $params, 10);
        if(isset($rst['code']) && (int)$rst['code'] === 0){
            return ['status'=>200, 'data'=>$rst['data'], 'msg'=>$rst['msg'] ?? '白名单添加成功'];
        }

        return ['status'=>300, 'data'=>$rst, 'msg'=>$rst['msg'] ?? '白名单添加失败'];
    }

    private static function parseProxyItem($proxyItem){
        $data = [
            'ip_addr' => '',
            'valid_seconds' => 0,
            'username' => '',
            'password' => '',
        ];

        if(is_array($proxyItem)){
            $ipAddr = $proxyItem['proxy'] ?? $proxyItem['ip_addr'] ?? '';
            if(!$ipAddr && !empty($proxyItem['ip']) && !empty($proxyItem['port'])){
                $ipAddr = $proxyItem['ip'].':'.$proxyItem['port'];
            }
            $data['ip_addr'] = $ipAddr;
            $data['valid_seconds'] = (int)($proxyItem['valid_time'] ?? $proxyItem['et'] ?? $proxyItem['expire_time'] ?? 0);
            $data['username'] = (string)($proxyItem['username'] ?? $proxyItem['user'] ?? '');
            $data['password'] = (string)($proxyItem['password'] ?? $proxyItem['pass'] ?? '');

            return $data;
        }

        $proxyItem = trim((string)$proxyItem);
        $parts = explode(':', $proxyItem);
        if(count($parts) >= 4 && filter_var($parts[0], FILTER_VALIDATE_IP)){
            $data['ip_addr'] = $parts[0].':'.$parts[1];
            $data['username'] = $parts[2];
            $data['password'] = implode(':', array_slice($parts, 3));

            return $data;
        }

        if(preg_match('/((?:\d{1,3}\.){3}\d{1,3}:\d{2,5})/', $proxyItem, $matches)){
            $data['ip_addr'] = $matches[1];
        }
        if(preg_match('/(?:,|\s)(\d{2,6})$/', $proxyItem, $matches)){
            $data['valid_seconds'] = (int)$matches[1];
        }

        return $data;
    }

    /**
     * @desc 获取代理ip和接口
     * @param $num = 1; # 提取IP数量
     */
    public static function getProxyRemoteIp($num = 1, $proxyScene = BaseService::PROXY_SCENE_BET){
        $time_HI = date("H:i");
        if($proxyScene !== BaseService::PROXY_SCENE_LOGIN && '04:00'<$time_HI && $time_HI<'08:55'){
            return ['status'=>300, 'msg'=>'非下注时间段，不能获取IP'];
        }
        $secretId = self::getSecretId() ?: self::getOrderId();
        $RedisLock = new RedisLock();
        $Rkey = 'kuai_proxy_fetch_'.md5($secretId);
        $m = \Yii::$app->cache;
        $cacheKey = self::buildFetchCacheKey();
        if($cacheData = $m->get($cacheKey)){
            return $cacheData;
        }
        if(!$RedisLock->lock($Rkey.'_redis', 10)){
            sleep(2);
            if($cacheData = $m->get($cacheKey)){
                return $cacheData;
            }

            return ['status'=>300, 'msg'=>'代理IP提取过于频繁，已限流'];
        }

        $baseQuery = [
            'num' => $num,
            'pt' => 1,
            'format' => 'json',
            'sep' => 1,
            'f_auth' => 1,
            'generateType' => 1,
            'carrier' => 2,
        ];
        $areas = [
            '浙江,福建,江西,上海,湖北,江苏,广东',
            '广西,湖南,浙江,湖北',
            '',
        ];

        $rst = [];
        $query = $baseQuery;
        try {
            foreach($areas as $area){
                $query = $baseQuery;
                if($area){
                    $query['area'] = $area;
                }
                $rst = self::apiGet(self::getDpsApi(), '/api/getdps', $query, 10);
                if(isset($rst['code']) && (int)$rst['code'] === 0 && !empty($rst['data']['proxy_list'])){
                    $data = ['status'=>200, 'data'=>$rst['data']['proxy_list'], 'raw'=>$rst['data'], 'msg'=>'代理IP数据获取成功'];
                    $m->set($cacheKey, $data, 5);

                    return $data;
                }
                if(self::isWhitelistError($rst)){
                    $addRst = self::addWhiteIp();
                    Tool_Common::log('/proxy/'.__FUNCTION__, 'ERR', '快代理白名单未命中', [
                        'query'=>$query,
                        'proxy_scene'=>$proxyScene,
                        'rst'=>$rst,
                        'addWhiteIpRst'=>$addRst,
                    ]);

                    return ['status'=>300, 'msg'=>'快代理白名单未生效: '.($rst['msg'] ?? ''), 'data'=>$rst, 'addWhiteIpRst'=>$addRst];
                }
            }
        }finally{
            $RedisLock->unlock($Rkey.'_redis');
        }

        Tool_Common::log('/proxy/'.__FUNCTION__, 'ERR', '代理IP获取-快代理失败', ['query'=>$query, 'proxy_scene'=>$proxyScene, 'rst'=>$rst]);

        return ['status'=>300, 'data'=>$rst, 'msg'=>$rst['msg'] ?? '代理IP数据获取失败'];
    }

    /**
     * @desc 代理账号过期时间
     * @return array
     */
    public static function kuaiPoxyExpire(){
        $rst = self::apiGet(self::DEV_API, '/api/getorderexpiretime', [], 10);
        if(!isset($rst['code']) || $rst['code'] != 0 || empty($rst['data']['expire_time']) || $rst['data']['expire_time']<date("Y-m-d H:i:s")){
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

        $query = [
            'proxy' => implode(',', $poxy_ips),
        ];

        $rst = self::apiGet(self::getDpsApi(), '/api/getdpsvalidtime', $query, 6);
        if(isset($rst['errno']) && $rst['errno']>0 && !$r = $m->get($mkey)){
            $m->set($mkey, 1, 5);
            return self::kuaiIPValidTime($poxy_ips);
        }
        $logArr = ['poxy_ips'=>$poxy_ips, 'rst'=>$rst];
        Tool_Common::log('kuaiIPValidTime', 'INFO', '获取私密代理可用时长', $logArr);
        if(!isset($rst['code']) || $rst['code'] != 0){ # 为确保稳定，使用时间少于60s则认为IP失效
            return ['status'=>301, 'msg'=>$rst['msg'] ?? '接口调用失败'];
        }

        return ['status'=>200, 'data'=>$rst['data']];
    }

    /**
     * @desc 获取代理IP
     * @param int $type 1快代理2芝麻代理
     * @return array
     */
    public static function getRemoteProxyIp($type=1, $proxyScene = BaseService::PROXY_SCENE_BET){
        $time_HI = date("H:i");
        if($proxyScene !== BaseService::PROXY_SCENE_LOGIN && '04:00'<$time_HI && $time_HI<'08:55'){
            return ['status'=>300, 'msg'=>'非下注时间段，不能获取IP'];
        }

        $ip_addr = '';
        try {
            # 快代理
            $data = self::getProxyRemoteIp($num=1, $proxyScene);
            if($data['status'] != 200) {
                return $data;
            }
            $proxyData = self::parseProxyItem($data['data'][0]);
            $ip_addr = $proxyData['ip_addr']; # 110.86.176.46:15064
            if(empty($ip_addr)){
                throw_info('代理IP格式为空');
            }
            $ProxyIpRecords = ProxyIpRecords::findOne(['ip_addr'=>$ip_addr]);
            $ip_addr_datas = explode(':', $ip_addr);
            $ip = $ip_addr_datas[0];
            $port = $ip_addr_datas[1];
            $valid_seconds = $proxyData['valid_seconds'];
            if(!$valid_seconds){
                $valid_time_from_api = ProxyKuaiService::getProxyIpValidTime($ip_addr);
                $valid_seconds = max(0, $valid_time_from_api - time());
            }
            if($valid_seconds <= 0){
                $valid_seconds = self::getDefaultValidSeconds();
            }
            $valid_time = time() + $valid_seconds;
            self::setProxyAuth($ip_addr, $proxyData['username'], $proxyData['password'], $valid_seconds);
            $now_time = time();
            $setDatas = [
                'ip_addr' => $ip_addr,
                'ip' => $ip,
                'port' => $port,
                'isp' => (string)$type,
                'proxy_type' => 1,
                'valid_time' => $valid_time,
                'expire_time' => $valid_time,
                'status' => 1,
                'created_at' => $now_time,
                'updated_at' => $now_time,
            ];
            if(empty($ProxyIpRecords)){
                $ProxyIpRecords = new ProxyIpRecords();
            }else{
                unset($setDatas['created_at']);
            }
            $ProxyIpRecords->setAttributes($setDatas);
            $saveExceptionMsg = '';
            try {
                $flag = $ProxyIpRecords->save();
            } catch (\Throwable $saveException){
                $flag = false;
                $saveExceptionMsg = $saveException->getMessage();
            }
            $logArr = ['data'=>$data, 'ip_addr'=>$ip_addr, 'proxy_scene'=>$proxyScene, 'setDatas'=>$setDatas, 'flag'=>$flag];
            if(!$flag){
                $errors = $ProxyIpRecords->getErrors();
                $errorText = json_encode($errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if($saveExceptionMsg){
                    $logArr['save_exception'] = $saveExceptionMsg;
                }
                if(stripos($errorText, 'Duplicate entry') !== false || stripos($errorText, '1062') !== false || (!empty($saveExceptionMsg) && stripos($saveExceptionMsg, 'Duplicate entry') !== false)){
                    ProxyIpRecords::updateAll($setDatas, ['ip_addr'=>$ip_addr]);
                    $flag = true;
                    $logArr['duplicate_recovered'] = 1;
                }else{
                    $logArr['err_msg'] = $errors;
                }
            }
            Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '获取代理IP-快代理', $logArr);
        }catch (\Exception $exception){
            Tool_Common::log('/proxy/'.__FUNCTION__, 'ERR', '获取代理IP-快代理-错误', ['type'=>$type, 'proxy_scene'=>$proxyScene, 'ip_addr'=>$ip_addr, 'err_msg'=>$exception->getMessage()]);
            if($exception->getCode() != 20000){
                return ['status'=>300, 'msg'=>$exception->getMessage()];
            }
        }

        return ['status'=>200, 'ip_addr'=>$ip_addr];
    }

    /**
     * @desc 获取ip可用截止时间
     * @param string $ip_addr
     * @param int $type
     * @return int|mixed
     */
    public static function getProxyIpValidTime($ip_addr=''){
        $valid_time = time() + 10;

        $proxy_type = ProxyBaseService::getProxyType();
        if($proxy_type == 2){

        }else{
            # 快代理
            $isValidRst = ProxyKuaiService::kuaiIPValidTime([$ip_addr]);
            if($isValidRst['status'] == 200){
                $valid_time = time() + $isValidRst['data'][$ip_addr];
            }
        }
        Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '获取ip截止日期', ['ip_addr'=>$ip_addr, 'type'=>$proxy_type, 'isValidRst'=>$isValidRst]);

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
