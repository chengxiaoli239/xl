<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\service\huiyuan\HuiYuanBaseService;
use backend\service\Juhua\JuHuaBaseService;
use backend\service\Lucky5\Lucky5Service;
use backend\service\NineNine\NineNineNewService;
use backend\service\NineNine\NineNineService6;
use backend\service\pingbo\PingBoBaseService;
use backend\service\qilin\BingDaoService;
use backend\service\qilin\QiLinBaseService;
use common\service\CommonService;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use  yii;
use common\tools\Util;

class BaseService{


    /**
     * @desc 登陆中转
     * @param $id TzSystemsUsers表id
     * @return array|bool
     */
    public static function login($id = '', $is_auto = 1){
        if(!$id) return ['status'=>300, 'msg'=>'id不能为空'];
        if(!$TzSystemsUser = TzSystemsUsers::findOne($id)){
            return ['status'=>300, 'msg'=>'操作失败:找不到记录'];
        }

        $tz_system_id = $TzSystemsUser->tz_system_id;
        # 是否有激活的计划
        $hasActivePlan = CommonService::hasPlansActiveSys($tz_system_id, $TzSystemsUser->uid);
        if($is_auto == 1 && !$hasActivePlan){
            return false;
        }

        if(empty($TzSystemsUser->account) OR empty($TzSystemsUser->password)){
            return false;
        }

        # 密码或账号不正确
        if(strpos($TzSystemsUser->desc, '用户名或密码不正确') !== false OR strpos($TzSystemsUser->desc, '您的访问过于频繁') !== false){
            return false;
        }

        $not_need_login_tz_system_ids = explode(',', $val = SystemConfig::findOne(['key'=>'ssc_kj_time_period'])->value); # 开奖时间间隔:20分钟
        if(in_array($tz_system_id, $not_need_login_tz_system_ids)){
            return ['status'=>200, 'msg'=>'无需登陆站点', 'balance'=>$TzSystemsUser->balance, 'account'=>$TzSystemsUser->account, 'username'=>$TzSystemsUser->username];
        }

        if($is_auto == 1){
            $flag = BetService::isLogin($TzSystemsUser->uid, $tz_system_id); #
            if($flag && $is_auto == 1){
                return ['status'=>200, 'msg'=>'已经是登录状态', 'balance'=>$TzSystemsUser->balance, 'account'=>$TzSystemsUser->account, 'username'=>$TzSystemsUser->username];
            }
        }
        if(in_array($tz_system_id, [1,2])){
            # 1、0898投注、2、99彩票网
            $rst = HN0898Service::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
        }elseif(in_array($tz_system_id, [3, 7, 9, 10])){
            # 3、重庆7时彩网
            if($tz_system_id == 3){
                $rst = SevenService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
            }else{
                $rst = Lucky5Service::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
            }
        }elseif(in_array($tz_system_id, [4])){
            # 4、7天彩票网
        }elseif(in_array($tz_system_id, [5])){
            # 5、希腊网
        }elseif(in_array($tz_system_id, [6])){
            # 6、会员网
            $rst = HuiYuanBaseService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
        }elseif(in_array($tz_system_id, [11])){
            # 11、菊花网暂时没对接登录
            //$rst = JuHuaBaseService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
        }elseif(in_array($tz_system_id, [8])){
            # 8、麒麟财务系统网
            $rst = QiLinBaseService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
        }elseif(in_array($tz_system_id, [13])){
            # 9、冰岛
            $rst = \backend\service\BingDao\BingDaoService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
        }

        return $rst;
    }

    /**
     * @desc 同步余额中转
     * @param $id
     * @return array|bool
     */
    public static function synBalance($id = '', $is_auto=1){
        if(!$id) return ['status'=>300, 'msg'=>'id不能为空'];
        if(!$TzSystemsUser = TzSystemsUsers::findOne($id)){
            return ['status'=>300, 'msg'=>'操作失败:找不到记录'];
        }

        $tz_system_id = $TzSystemsUser->tz_system_id;
        $m = \Yii::$app->cache;
        $mkey = 'synBalance_'.$tz_system_id.'_'.$TzSystemsUser->id;
        $redisLock = new RedisLock();
        $redisKey = 'Auto_synBalance_'.$id;
        if($redisLock->lock($redisKey, 3)){
            # 是否有激活的计划
            $hasActivePlan = CommonService::hasPlansActiveSys($tz_system_id, $TzSystemsUser->uid);
            if(!$hasActivePlan && $is_auto==1){
                return false;
            }

            if(empty($TzSystemsUser->account) OR empty($TzSystemsUser->password)){
                return false;
            }
            $tz_system_id = $TzSystemsUser->tz_system_id;
            if(in_array($tz_system_id, [1,2])){
                # 1、0898投注、2、99彩票网
                //$rst = HN0898Service::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
                $rst = HN0898Service::synBalance($TzSystemsUser->id);
            }elseif(in_array($tz_system_id, [3, 7, 9, 10])){
                # 3、重庆7时彩网
                if(in_array($tz_system_id, [3, 7])){
                    $rst = SevenService::synBalance($TzSystemsUser->id);
                }else{
                    $rst = Lucky5Service::synBalance($TzSystemsUser->id);// p($rst);# 同步余额
                }
            }elseif(in_array($tz_system_id, [4])){
                # 4、7天彩票网
            }elseif(in_array($tz_system_id, [5])){
                # 5、希腊网
            }elseif(in_array($tz_system_id, [6])){
                # 6、会员网
                $rst = HuiYuanBaseService::synBalance($TzSystemsUser->id);
            }elseif(in_array($tz_system_id, [11])){
                # 菊花网
                $rst = JuHuaBaseService::synBalance($TzSystemsUser->id);
            }elseif(in_array($tz_system_id, [12])){
                # 九九新网
                $rst = NineNineNewService::synBalance($TzSystemsUser->id);
            }elseif(in_array($tz_system_id, [8])){
                # 8、麒麟财务系统网
                $rst = QiLinBaseService::synBalance($TzSystemsUser->id);
            }elseif(in_array($tz_system_id, [14])){
                # 14、平博网
                $rst = PingBoBaseService::synBalance($TzSystemsUser->id);
            }elseif(in_array($tz_system_id, [13])){
                # 13、冰岛
                $rst = \backend\service\BingDao\BingDaoService::synBalance($TzSystemsUser->id);
            }
            $m->set($mkey, $rst, 8);
        }else{
            sleep(3);
            $rst = $m->get($mkey);
            if(empty($rst)){
                if(in_array($tz_system_id, [3, 7, 9, 10])){
                    $rst = Lucky5Service::synBalance($TzSystemsUser->id);// p($rst);# 同步余额
                }else{
                    $rst = ['status'=>301, 'msg'=>'同步余额并发锁-1['.$id.']'];
                }
            }else{
                $rst = ['status'=>302, 'msg'=>'同步余额并发锁-2['.$id.']'];
            }
        }

        return $rst;
    }

    /**
     * @description
     * @param $string
     * @return bool
     */
    public static function is_json($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * @decription post请求根据，接受传递的header头
     * @param $url
     */
    public static function postCommonCurl($url,$post_data = [],$headers=[], $uid = 0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        if(!$timeout) $timeout = 30;

        //$cookie = dirname(__FILE__)."/cookie.txt";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        curl_setopt($ch, CURLOPT_HEADER, 1); #

        //$poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        //设置post方式提交
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER, TRUE);    //表示需要response header
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $start_time = microtime(true);
        $content = curl_exec($ch);
        $end_time = microtime(true);
        //d($content);
        $errno = curl_errno($ch);
        //$logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$content, 'errno'=>$errno]; p($logArr);
        if($errno){
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$content, 'errno'=>$errno];
            //p($logArr);
            Tool_Common::log('httpPostError','INFO','httpPost请求-1', $logArr);
        }

        # ================= xCsrf token start =====================
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') {

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($content, 0, $headerSize);

            preg_match_all("/set\-cookie:([^\r\n]*)/i", $header, $matches1);

            $body = substr($content, $headerSize);
            $result['rstData'] = json_decode($body, true);
            foreach ($matches1[1] as $key=>$match){
                $matches1[1][$key] = trim($match);
            }

            $result['cookie'] = $matches1;
        }
        # ================= xCsrf token start =====================

        return $result;
    }
}