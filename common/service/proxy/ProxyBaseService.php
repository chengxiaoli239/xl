<?php
namespace common\service\proxy;

use backend\models\ProxyIpRecords;
use backend\models\TzSystemsUsers;
use backend\service\BetService;
use backend\service\CurlService;
use common\service\CommonService;
use common\tools\Tool_Common;
use  yii;

class ProxyBaseService {

    public static $proxy_url = '';

    public static $ip_addr = '';

    public static $ip_port = '';

    public static $proxy_types = [1, 2, 3]; # 1快代理2芝麻代理3代理云

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
        $PROXY_TYPE = BetService::getConfig('CURL_PROXY_TYPE') == 'PROXY_TYPE_3' ? 3 : $PROXY_TYPE; # PROXY_TYPE_1:快代理PROXY_TYPE_2芝麻代理PROXY_TYPE_3:代理云

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
     * @param integer $type 1正常获取2检测
     * @param integer $is_warnning 是否告警：0否1是  1的时候为即将过时临界点告警
     * @return mixed|string
     */
    public static function getCurrentValidProxyIp($proxy_type='', $type=1, &$is_warnning=0){
        $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
        if(!$POXY_STATUS) return []; # CURL 代理开关

        $m = \Yii::$app->cache;
        if(empty($proxy_type)){
            $proxy_type = ProxyBaseService::getProxyType();
        }
        $mkey = ProxyBaseService::buildProxyIpKey($proxy_type);
        $ip_addr = $m->get($mkey);
        if(!$ip_addr){
            $flag = ProxyIpRecords::updateAll(['status'=>0, 'updated_at'=>time()], ['AND', ['=', 'status', 1], ['<', 'expire_time', time()]]);
            $where = ['AND', ['=', 'proxy_type', $proxy_type], ['=', 'status', 1]];
            $row = ProxyIpRecords::find()->where($where)->orderBy(['id'=>SORT_DESC])->one();
            if(empty($row)){
                return '';
            }
            $ip_addr = $row->ip_addr;
            $m->set($mkey, $ip_addr,10);
            $left_time = $row->valid_time - time();
            if($type == 2 && $left_time<90){
                $is_warnning = 1;
            }
            Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '代理IP', ['ip_addr'=>$ip_addr, 'flag'=>$flag, 'expire_time'=>date('Y-m-d H:i:s', $row->expire_time)]);
        }

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
                return [];
                //return ['status'=>401, 'msg'=>'无对应代理激活的计划'];
            }
            $is_need_get_new_ip = 0;

            //$proxy_type = ProxyBaseService::getProxyType();
            $current_ip_addr = ProxyBaseService::getCurrentValidProxyIp($proxy_type, $type=2, $is_warnning); # 获取当前可用的代理IP
            if($is_warnning == 0 && !empty($current_ip_addr)){
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
                $new_ip_addr_data = ProxyBaseService::getRemoteProxyIp($proxy_type);

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
            return ['status'=>303, 'msg'=>$exception->getMessage()];
            Tool_Common::log('/proxy/'.__FUNCTION__.'_err', 'ERR', '代理ip缓存失败', ['proxy_type'=>$proxy_type, 'err_msg'=>$exception->getMessage()]);
        }

        return ['status'=>200, 'msg'=>'操作成功', 'data'=>$logArr];
    }

    /**
     * @desc 获取代理IP
     * @return array
     */
    public static function getRemoteProxyIp($proxy_type=''){
        $time_HI = date("H:i");
        //return ['status'=>300, 'msg'=>'调试'];
        if('04:00'<$time_HI && $time_HI<'08:55'){
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
            $ip_addr_data = ProxyKuaiService::getRemoteProxyIp();
        }
        Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '获取代理IP', ['proxy_type'=>$proxy_type, 'ip_addr_data'=>$ip_addr_data]);

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

        Tool_Common::log('/proxy/'.__FUNCTION__,'INFO', '判断代理IP有效性', ['url'=>$url, 'proxy_ips'=>$proxy_ip, 'rst'=>$checkRst, 'consume_time'=>$consume_time]);

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
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        ProxyBaseService::setProxy($ch); # 设置全局代理

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);

        $start_time = microtime(true);
        $data = curl_exec($ch);
        $end_time = microtime(true);
        $errno = curl_errno( $ch );
        $current_proxy_addr = ProxyBaseService::getCurrentValidProxyIp();
        $logArr = ['url'=>$url, 'errno'=>$errno, 'time_consume'=>($end_time-$start_time).'s', 'current_proxy_addr'=>$current_proxy_addr];
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
            $proxy_type = $TzSystemsUsers->proxy_type;

            $m->set($mkey, $proxy_type, 30);
        }
        $proxy_type = $proxy_type ? :ProxyBaseService::getProxyType();

        return (int)$proxy_type;
    }

    /**
     * @desc 设置全局代理
     * @param $ch
     * @return bool
     */
    public static function setProxy($ch, $uid=0){
        $proxy_type = ProxyBaseService::getProxyTypeByUid($uid);

        $current_proxy_addr = ProxyBaseService::getCurrentValidProxyIp($proxy_type);
        Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '设置全局代理-1', ['uid'=>$uid, 'proxy_type'=>$proxy_type, 'current_proxy_addr'=>$current_proxy_addr]);
        if(empty($current_proxy_addr)) return [];
        if($proxy_type == 2) { # 芝麻云
            // 代理服务器
            $proxyServer = "http://" . $current_proxy_addr;
            curl_setopt($ch, CURLOPT_PROXYTYPE, 5); //sock5
            curl_setopt($ch, CURLOPT_PROXY, $proxyServer);

        }elseif($proxy_type == 3){ # 代理云
            $current_proxy_addr = ProxyBaseService::getCurrentValidProxyIp($proxy_type);
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
            $current_proxy_addr = ProxyBaseService::getCurrentValidProxyIp($proxy_type);
            # 快代理
            $username = \Yii::$app->params['KUAI_USERNAME'];
            $password = \Yii::$app->params['KUAI_PASSWORD'];
            Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '设置全局代理-1', ['uid'=>$uid, 'proxy_type'=>$proxy_type, 'current_proxy_addr'=>$current_proxy_addr,
                'username'=>$username,'password'=>$password]);
            if(!empty($current_proxy_addr)){
                //设置代理
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt($ch, CURLOPT_PROXY, $current_proxy_addr);
                //设置代理用户名密码（私密代理/独享代理）
                //如果是开放代理，请注释掉下面两句
                curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$username}:{$password}");
            }
        }



        return true;
    }
}