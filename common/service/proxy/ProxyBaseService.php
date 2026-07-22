<?php
namespace common\service\proxy;

use backend\models\ProxyIpRecords;
use backend\models\TzSystemsUsers;
use backend\service\BaseService;
use backend\service\BetService;
use common\service\CommonService;
use common\tools\Tool_Common;
use  yii;

class ProxyBaseService {

    public static $proxy_url = '';

    public static $ip_addr = '';

    public static $ip_port = '';

    public static $proxy_types = [1]; # 1快代理2芝麻代理3代理云

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
        return self::clearCurrentProxyIp();
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
        $PROXY_TYPE = BetService::getConfig('CURL_PROXY_TYPE') == 'PROXY_TYPE_3' ? 3 : $PROXY_TYPE; # PROXY_TYPE_1:快代理PROXY_TYPE_2芝麻代理PROXY_TYPE_3:代理云

        return $PROXY_TYPE;
    }

    /**
     * @desc 设置某个代理ip为不可用
     * @param string $ip
     * @return bool
     */
    public static function setIpInvalid($ip_addr=''){
        $flag = false;
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

    public static function clearCurrentProxyIp($proxy_type='', $ip_addr=''){
        if(empty($proxy_type)){
            $proxy_type = self::getProxyType();
        }
        $m = \Yii::$app->cache;
        $mkey = self::buildProxyIpKey($proxy_type);
        $cachedIp = $m->get($mkey);
        $oldIp = $ip_addr ?: $cachedIp;
        if(!empty($oldIp)){
            self::setIpInvalid($oldIp);
        }
        $m->delete($mkey);
        $fetchCacheCleared = false;
        if((int)$proxy_type === 1){
            $fetchCacheCleared = ProxyKuaiService::clearFetchCache();
        }
        Tool_Common::log('/proxy/'.__FUNCTION__, 'WARN', '代理IP故障清理缓存', [
            'proxy_type'=>$proxy_type,
            'old_ip'=>$oldIp,
            'cached_ip'=>$cachedIp,
            'mkey'=>$mkey,
            'fetch_cache_cleared'=>$fetchCacheCleared,
        ]);

        return ['status'=>200, 'data'=>['old_ip'=>$oldIp, 'cached_ip'=>$cachedIp, 'proxy_type'=>$proxy_type, 'fetch_cache_cleared'=>$fetchCacheCleared]];
    }

    /**
     * @desc 获取可用ip
     * @param string $proxy_type
     * @param integer $type 1正常获取2检测
     * @param integer $is_warnning 是否告警：0否1是  1的时候为即将过时临界点告警
     * @return mixed|string
     */
    public static function getCurrentValidProxyIp($proxy_type='', $type=1, &$is_warnning=0, $proxyScene = BaseService::PROXY_SCENE_BET){
        $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
        if(!$POXY_STATUS) return []; # CURL 代理开关

        $m = \Yii::$app->cache;
        if(empty($proxy_type)){
            $proxy_type = ProxyBaseService::getProxyType();
        }
        $mkey = ProxyBaseService::buildProxyIpKey($proxy_type);
        $ip_addr = $m->get($mkey);
        #p(['proxy_type'=>$proxy_type, 'ip_addr'=>$ip_addr]);
        if(!$ip_addr){
            $min_left_seconds = 60;
            $flag = ProxyIpRecords::updateAll(['status'=>0, 'updated_at'=>time()], [
                'AND',
                ['=', 'status', 1],
                ['OR', ['<', 'expire_time', time() + $min_left_seconds], ['<', 'valid_time', time() + $min_left_seconds]],
            ]);
            $where = [
                'AND',
                ['=', 'proxy_type', $proxy_type],
                ['=', 'status', 1],
                ['>', 'expire_time', time() + $min_left_seconds],
                ['>', 'valid_time', time() + $min_left_seconds],
            ];
            $row = ProxyIpRecords::find()->where($where)->orderBy(['id'=>SORT_DESC])->one();
            if(empty($row)){
                if($type == 1){
                    $new_ip_addr_data = ProxyBaseService::getRemoteProxyIp($proxy_type, $proxyScene);
                    if(!empty($new_ip_addr_data['ip_addr']) && isset($new_ip_addr_data['status']) && $new_ip_addr_data['status'] == 200){
                        $ip_addr = $new_ip_addr_data['ip_addr'];
                        $m->set($mkey, $ip_addr, 10);

                        return $ip_addr;
                    }
                    Tool_Common::log('/proxy/'.__FUNCTION__, 'ERR', '代理IP为空且远程获取失败', ['proxy_type'=>$proxy_type, 'proxy_scene'=>$proxyScene, 'rst'=>$new_ip_addr_data]);
                }

                return '';
            }
            $ip_addr = $row->ip_addr;
            $m->set($mkey, $ip_addr,10);
            $left_time = $row->valid_time - time();
            if($type == 2 && $left_time<90){
                $is_warnning = 1;
            }
            $expire_time = $row->expire_time;
            Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '代理IP-1', ['ip_addr'=>$ip_addr, 'left_time'=>$left_time, 'proxy_type'=>$proxy_type, 'proxy_scene'=>$proxyScene, 'flag'=>$flag, 'expire_time'=>date('Y-m-d H:i:s', $expire_time)]);
        }
        Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '代理IP2', ['ip_addr'=>$ip_addr, 'proxy_type'=>$proxy_type, 'proxy_scene'=>$proxyScene]);

        return $ip_addr;
    }

    /**
     * @desc 自动脚本 - 预先判断缓存是否存在  每3-5秒检测一次缓存的ip，如果过期则重新获取代理IP缓存
     * @return array
     */
    public static function preGetValidIp($proxy_type=1, $is_auto = 1){
        $start_time = microtime(true);
        $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
        if(!$POXY_STATUS) return ['status'=>300, 'msg'=>'代理IP开关未开启']; # CURL 代理开关
        //p('asdkjfl');

        try {
            $hasPlansActiveLottery = CommonService::hasPlansActiveLottery(\Yii::$app->params['NEED_PROXY_LOTTERYS'], $proxy_type);
            if($is_auto == 1 && !$hasPlansActiveLottery){
                throw_info('无对应代理激活的计划');
            }
            $is_need_get_new_ip = 0;

            //$proxy_type = ProxyBaseService::getProxyType();
            $current_ip_addr = ProxyBaseService::getCurrentValidProxyIp($proxy_type, $type=2, $is_warnning); # 获取当前可用的代理IP
            if($is_warnning == 0 && !empty($current_ip_addr)){
                $isValid = ProxyBaseService::isValid($current_ip_addr);
            }else{
                $is_need_get_new_ip = 1;
            }

            $logArr = ['is_need_get_new_ip'=>$is_need_get_new_ip, 'proxy_type'=>$proxy_type, 'is_valid'=>$isValid, 'current_ip_addr'=>$current_ip_addr];
            if($is_need_get_new_ip){
                $new_ip_addr_data = ProxyBaseService::getRemoteProxyIp($proxy_type);
                if(empty($new_ip_addr_data)){
                    throw_info('代理IP为空proxy_type:'.$proxy_type);
                }

                # 代理IP告警节点，获取新ip成功则设置旧代理ip失效
                if($is_warnning == 1 && $new_ip_addr_data['status'] == 200 && !empty($new_ip_addr_data['ip_addr']) && !empty($current_ip_addr)){
                    ProxyBaseService::setIpInvalid($current_ip_addr);
                }

                $logArr['new_ip_addr_data'] = $new_ip_addr_data;
            }

            $end_time = microtime(true);
            $logArr['time_consume'] = ($end_time-$start_time).'s';
            Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '获取代理ip-缓存1', $logArr);
        }catch (\Exception $exception){
            Tool_Common::log('/proxy/'.__FUNCTION__, 'ERR', '代理ip缓存失败', ['proxy_type'=>$proxy_type, 'err_msg'=>$exception->getMessage()]);
            return ['status'=>303, 'msg'=>$exception->getMessage()];
        }

        return ['status'=>200, 'msg'=>'操作成功', 'data'=>$logArr];
    }

    /**
     * @desc 获取代理IP
     * @return array
     */
    public static function getRemoteProxyIp($proxy_type='', $proxyScene = BaseService::PROXY_SCENE_BET){
        $time_HI = date("H:i");
        //return ['status'=>300, 'msg'=>'调试'];
        if($proxyScene !== BaseService::PROXY_SCENE_LOGIN && '04:00'<$time_HI && $time_HI<'08:55'){
            return ['status'=>300, 'msg'=>'非下注时间段，不能获取IP'];
        }
        if(!$proxy_type){
            $proxy_type = ProxyBaseService::getProxyType();
        }

        if($proxy_type == 2) {
            $ip_addr_data = ProxyZhiMaService::getRemoteProxyIp();
        }elseif($proxy_type == 3){
            $ip_addr_data = ProxyDaiLiYunService::getRemoteProxyIp();
        }else{
            $ip_addr_data = ProxyKuaiService::getRemoteProxyIp(1, $proxyScene);
        }
        Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '获取代理IP', ['proxy_type'=>$proxy_type, 'proxy_scene'=>$proxyScene, 'ip_addr_data'=>$ip_addr_data]);

        return $ip_addr_data; # ['status'=>200, 'ip_addr'=>$ip_addr];
    }

    /**
     * @desc 判断代理IP有效性
     * @param $poxy_ip array  ['122.7.3.56:17856', '122.8.8.56:176']
     * @return bool
     */
    public static function isValid($proxy_ip = '', $is_auto=1){
        $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
        if(!$POXY_STATUS && $is_auto) return false; # CURL 代理开关
        $m = \Yii::$app->cache;
        $mkey = 'retry_get_isValid_key';

        $url = 'https://www.baidu.com';
        $start_time = microtime(true);
        $checkRst = ProxyBaseService::check($url, 8);
        $end_time = microtime(true);
        $consume_time = ($end_time-$start_time).'s';
        if(!$checkRst && !$r = $m->get($mkey)){
            $m->set($mkey, 1, 6);
            return self::isValid($proxy_ip);
        }
        $ssl_version = BaseService::getSslVersionByUid();

        Tool_Common::log('/proxy/'.__FUNCTION__,'INFO', '判断代理IP有效性', ['url'=>$url, 'proxy_ips'=>$proxy_ip, 'rst'=>$checkRst, 'ssl_version'=>$ssl_version, 'consume_time'=>$consume_time]);

        return  $checkRst;
    }

    /**
     * @desc 检测代理IP可用性
     * @param $url
     * @param int $timeout
     * @return bool|string
     */
    public static function check($url, $timeout=30){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, BaseService::getSslVersionByUid());

        ProxyBaseService::setProxy($ch); # 设置全局代理

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);

        $start_time = microtime(true);
        $data = curl_exec($ch);
        $end_time = microtime(true);
        $errno = curl_errno( $ch );
        $current_proxy_addr = ProxyBaseService::getCurrentValidProxyIp('', 2);
        $logArr = ['url'=>$url, 'errno'=>$errno, 'time_consume'=>($end_time-$start_time).'s', 'current_proxy_addr'=>$current_proxy_addr, 'ssl_version'=>BaseService::getSslVersionByUid()];
        Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', 'IP检测', $logArr);
        $flag = true;
        if($errno>0){
            $flag = false;
        }

        return $flag;
    }

    /**
     * @param int $uid
     * @return int
     */
    public static function getProxyTypeByUid($uid=0){
        if(!$uid) return 1;
        $m = \Yii::$app->cache;
        $mkey = 'getProxyTypeByUid_'.$uid;
        if(!$proxy_type = $m->get($mkey)){
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid]);
            if(!empty($TzSystemsUsers)){
                $proxy_type = $TzSystemsUsers->proxy_type;
            }
        }

        $proxy_type = $proxy_type ? :ProxyBaseService::getProxyType();
        $m->set($mkey, $proxy_type, 30);

        return (int)$proxy_type;
    }

    /**
     * @desc 设置全局代理
     * @param $ch
     * @return bool
     */
    public static function setProxy($ch, $uid=0, $proxyScene = BaseService::PROXY_SCENE_BET, $tzSystemId = ''){
        $proxy_type = 0;
        try {
            $TzSystemsUsers = null;
            if($uid){
                $query = TzSystemsUsers::find()->where(['uid'=>(int)$uid, 'is_use_proxy'=>1]);
                if($tzSystemId !== '' && $tzSystemId !== null){
                    $query->andWhere(['tz_system_id'=>(int)$tzSystemId]);
                }
                $TzSystemsUsers = $query->one();
                if(empty($TzSystemsUsers)){
                    throw_info('无需代理IP的用户或uid为空');
                }
                if(!BaseService::isProxySceneOpen($TzSystemsUsers, $proxyScene)){
                    throw_info('当前接口未开启代理');
                }
            }
            $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
            if(!$POXY_STATUS){# CURL 代理开关
                throw_info('IP代理开关未开启2');
            }
            $proxy_type = $uid && !empty($TzSystemsUsers->proxy_type) ? (int)$TzSystemsUsers->proxy_type : ProxyBaseService::getProxyType();

            $warnning = 0;
            $current_proxy_addr = ProxyBaseService::getCurrentValidProxyIp($proxy_type, 1, $warnning, $proxyScene);
            Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '设置全局代理-1', ['uid'=>$uid, 'tz_system_id'=>$tzSystemId, 'proxy_type'=>$proxy_type, 'proxy_scene'=>$proxyScene, 'current_proxy_addr'=>$current_proxy_addr]);
            if(empty($current_proxy_addr)){
                throw_info('代理IP为空');
            }
            if($proxy_type == 2) { # 芝麻云
                // 代理服务器
                $proxyServer = "http://" . $current_proxy_addr;
                curl_setopt($ch, CURLOPT_PROXYTYPE, 5); //sock5
                curl_setopt($ch, CURLOPT_PROXY, $proxyServer);

            }elseif($proxy_type == 3){ # 代理云
                # 快代理
                $username = \Yii::$app->params['DAILIYUN_USERNAME'];
                $password = \Yii::$app->params['DAILIYUN_PASSWORD'];
                if(!empty($current_proxy_addr)){
                    //设置代理
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                    curl_setopt($ch, CURLOPT_PROXY, $current_proxy_addr);
                    //设置代理用户名密码（私密代理/独享代理）
                    //如果是开放代理，请注释掉下面两句
                    curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$username}:{$password}");
                }
            }else{
                # 快代理
                $username = \Yii::$app->params['KUAI_USERNAME'];
                $password = \Yii::$app->params['KUAI_PASSWORD'];
                $proxyAuth = ProxyKuaiService::getProxyAuth($current_proxy_addr);
                if(!empty($proxyAuth['username']) && !empty($proxyAuth['password'])){
                    $username = $proxyAuth['username'];
                    $password = $proxyAuth['password'];
                }
                Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '设置全局代理-1', ['uid'=>$uid, 'tz_system_id'=>$tzSystemId, 'proxy_type'=>$proxy_type, 'proxy_scene'=>$proxyScene, 'current_proxy_addr'=>$current_proxy_addr]);
                if(!empty($current_proxy_addr)){
                    //设置代理
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                    curl_setopt($ch, CURLOPT_PROXY, $current_proxy_addr);
                    if(!empty($proxyAuth) || ProxyKuaiService::isUseProxyAuth()){
                        //设置代理用户名密码（私密代理/独享代理）
                        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$username}:{$password}");
                    }
                }
            }
        }catch (\Exception $e){
            Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '设置全局代理-异常', ['uid'=>$uid, 'tz_system_id'=>$tzSystemId, 'proxy_type'=>$proxy_type, 'proxy_scene'=>$proxyScene, 'err_msg'=>$e->getMessage()]);
            return false;
        }

        return $current_proxy_addr;
    }
}
