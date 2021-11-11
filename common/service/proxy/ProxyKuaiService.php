<?php
namespace common\service\proxy;

use backend\models\ProxyIpRecords;
use backend\models\TzSystemsUsers;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use  yii;

class ProxyKuaiService {

    /**
     * @desc 获取代理ip和接口
     * @param $num = 1; # 提取IP数量
     */
    public static function getPoxyRemoteIp($num = 1){
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

        $m = \Yii::$app->cache;
        $time = 3600 * 4;
        $ip_addr = PoxyIPService::getCurrentValidProxyIp(); # 获取当前可用的代理IP

        $mkey = self::builProxyIpKey($mod_uid);
        $poxy_ip_data = $m->get($mkey);
        Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '获取代理ip-缓存', ['mkey'=>$mkey, 'poxy_ip_data'=>$poxy_ip_data]);
        if(!empty($poxy_ip_data)){
            $isValid = PoxyIPService::isValid([$poxy_ip_data]);
            $isValidRst = PoxyIPService::kuaiIPValidTime([$poxy_ip_data]);
        }

        if(!$isValid OR $isValidRst['status'] != 200 OR $isValidRst['data'][$poxy_ip_data] < 60){
            # 调用失败或者可使用时间少于5分钟则认为IP失效
            //$data = self::kuaiPoxy();
            $data = ProxyKuaiService::getRemoteProxyIp();
            if($data['status'] != 200) {
                return [];
            }
            $poxy_ip_data = $data['ip_addr'];
            $m->set($mkey, $poxy_ip_data, $time);
        }

        $logArr = ['IP'=>$poxy_ip_data, 'is_valid'=>$isValid, 'rst'=>$isValidRst];
        $end_time = microtime(true);
        $logArr['time_consume'] = ($end_time-$start_time).'s';
        Tool_Common::log('preGetIpValidStatus', 'INFO', '预先缓存代理IP', $logArr);

        return ['status'=>200, 'msg'=>'操作成功', 'data'=>$logArr];
    }

    public static function validIpIsValid($ip_addr, $type=0){

    }

    /**
     * @desc 获取代理IP
     * @param int $type 1快代理2芝麻代理
     * @return array
     */
    public static function getRemoteProxyIp($type=1){
        $time_HI = date("H:i");
        if('04:00'<$time_HI && $time_HI<'08:55'){
            return ['status'=>300, 'msg'=>'非下注时间段，不能获取IP'];
        }

        # 快代理
        $data = self::getPoxyRemoteIp($num=1);
        if($data['status'] != 200) {
            return [];
        }
        $ip_addr = $data['data'][0];
        $ip_addr_datas = explode(':', $data['data'][0]);;
        $ip = $ip_addr_datas[0];
        $port = $ip_addr_datas[1];
        $valid_time = PoxyIPService::getProxyIpValidTime();
        $now_time = time();
        $setDatas = [
            'ip_addr' => $ip_addr,
            'ip' => $ip,
            'port' => $port,
            'isp' => $type,
            'proxy_type' => 1,
            'valid_time' => $valid_time,
            'created_at' => $now_time,
            'updated_at' => $now_time,
        ];
        $ProxyIpRecords = new ProxyIpRecords();
        $ProxyIpRecords->setAttributes($setDatas);
        $ProxyIpRecords->save();

        return ['status'=>200, 'ip_addr'=>$ip_addr];
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
            $isValidRst = PoxyIPService::kuaiIPValidTime([$ip_addr]);
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