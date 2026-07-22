<?php
/**
 * Created by PhpStorm.
 *   
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\SystemConfig;
use backend\models\TzSystems;
use backend\models\TzSystemsUsers;
use backend\service\clients\TzSystemUsersService;
use backend\service\huiyuan\HuiYuanBaseService;
use backend\service\Juhua\JuHuaBaseService;
use backend\service\LeCai\ZhongFaService;
use backend\service\Lucky5\Lucky5Service;
use backend\service\NineNine\NineNineNewService;
use backend\service\pingbo\PingBoBaseService;
use backend\service\qilin\QiLinBaseService;
use common\models\AdminModel;
use common\service\CommonService;
use common\service\open\aozhou5\ActionService;
use common\service\proxy\ProxyBaseService;
use common\service\thirdD\sx\Sx3dUserService;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use  yii;
use common\tools\Util;

class BaseService{

    const PROXY_SCENE_LOGIN = 'login';
    const PROXY_SCENE_BET = 'bet';


    /**
     * @desc 登陆中转
     * @param int $id - TzSystemsUsers表id
     * @return array|bool
     */
    public static function login(int $id = 0, $is_auto = 1){
        try {
            if(!$TzSystemsUser = TzSystemsUsers::findOne($id)){
                throw_info('操作失败:找不到记录', 301);
            }
            if($is_auto == 1 && !$TzSystemsUser->is_auto_login){
                throw_info('操作失败:账号没设置自动登陆', 302);
            }

            $tz_system_id = $TzSystemsUser->tz_system_id;
            $TzSystems = TzSystems::findOne($tz_system_id);
            if(!in_array($tz_system_id, [17, 18, 19]) && $TzSystems->system_type_id != TzSystemUsersService::TZ_SYSTEM_TYPES_OPTIONS[AdminModel::USER_TYPE_GUI_ALL]){
                # 是否有激活的计划（包含测试和真实计划）
                $hasActivePlan = CommonService::hasPlansActiveSys($tz_system_id, $TzSystemsUser->uid);
                if($is_auto == 1 && !$hasActivePlan){
                    throw_info('没有激活的投注计划', 303);
                }
            }

            if(empty($TzSystemsUser->account) OR empty($TzSystemsUser->password)){
                throw_info('账号或密码为空，不能自动登录', 304);
            }

            # 密码或账号不正确 - 自动登录时清除旧错误描述，允许重新尝试
            if($is_auto == 1 && (strpos($TzSystemsUser->desc, '用户名或密码不正确') !== false OR strpos($TzSystemsUser->desc, '您的访问过于频繁') !== false)){
                Tool_Common::log('/login/'.__FUNCTION__, 'INFO', '清除旧的登录错误描述，重新尝试', ['id'=>$id, 'old_desc'=>$TzSystemsUser->desc]);
                $TzSystemsUser->desc = '';
                $TzSystemsUser->save();
                # 不清除的错误继续登录尝试（可能是用户已修改密码）
            }

            $not_need_login_tz_system_ids = explode(',', $val = SystemConfig::findOne(['key'=>'ssc_kj_time_period'])->value); # 开奖时间间隔:20分钟
            if(in_array($tz_system_id, $not_need_login_tz_system_ids)){
                throw_info('无需登陆站点', 306);
            }

            if($is_auto == 1){
                $flag = BetService::isLogin($TzSystemsUser->uid, $tz_system_id, $r=1); #
                if($flag && $is_auto == 1){
                    throw_info('已经是登录状态', 306);
                }
            }
            switch (true){
                case in_array($tz_system_id, [1, 2]):
                    # 1、0898投注、2、99彩票网
                    $rst = HN0898Service::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
                    break;
                case in_array($tz_system_id, [3, 7, 9, 10]): # 3、重庆7时彩网
                    if($tz_system_id == 3){
                        $rst = SevenService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
                    }else{
                        $rst = Lucky5Service::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id, $is_auto);
                    }
                    break;
                case in_array($tz_system_id, [4, 5]):
                    break;
                case $tz_system_id == 6:# 6、会员网
                    $rst = HuiYuanBaseService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
                    break;
                case $tz_system_id == 11:# 11、菊花网暂时没对接登录
                    //$rst = JuHuaBaseService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
                    break;
                case $tz_system_id == 8: # 8、麒麟财务系统网
                    $rst = QiLinBaseService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
                    break;
                case in_array($tz_system_id, [9]): # 9、冰岛
                    $rst = \backend\service\BingDao\BingDaoService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
                    break;
                case $tz_system_id == 16: # 宝岛众发
                    $rst = ZhongFaService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
                    break;
                case in_array($tz_system_id, [17, 18]):# 排sx 福sx
                    $rst = Sx3dUserService::login($TzSystemsUser);
                    break;
                case $TzSystems->system_type_id == 16: # 澳洲五
                    list($code, $data, $msg) = (new ActionService($TzSystemsUser))->login();
                    if(!$code){
                        $rst = ['status'=>200, 'data'=>$data, 'msg'=>$msg];
                    }
                    break;
            }
            if($rst['status'] != 200){
                $errMsg = $rst['msg'] ?? $rst['err_msg'] ?? $rst['curl_error'] ?? '登录失败';
                throw_info($errMsg);
            }
        }catch (\Exception $e){
            Tool_Common::log('/login/'.__FUNCTION__, 'ERR', '自动登录异常', ['id'=>$id, 'err_msg'=>$e->getMessage(), 'balance'=>$TzSystemsUser->balance, 'account'=>$TzSystemsUser->account, 'username'=>$TzSystemsUser->username, 'file'=>$e->getFile().'_'.$e->getLine()]);
            $rst = ['status'=>301, 'msg'=>$e->getMessage()];
        }

        return $rst;
    }

    /**
     * @desc 客户端手动同步余额
     * @param string $access_token
     * @return array|bool
     */
    public static function synBalanceByAccessToken($access_token='', $is_auto=2){
        try {
            $TzSystemsUsers = TzSystemsUsers::findOne(['access_token'=>$access_token]);
            $tz_systerm_user_id = BaseService::getTzSystemUserIdByAccessTokn($access_token);

            $rst = BaseService::synBalance($tz_systerm_user_id, $is_auto);
            Tool_Common::log('/lucky5/'.__FUNCTION__, 'INFO', '客户端手动同步余额', ['access_token'=>$access_token, 'account'=>$TzSystemsUsers->account, 'is_auto'=>$is_auto, 'rst'=>$rst]);
        }catch (\Exception $e){
            return ['status'=>300, 'data'=>[], 'msg'=>$e->getMessage()];
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
        try {
            if(!$TzSystemsUser = TzSystemsUsers::findOne($id)){
                return ['status'=>300, 'msg'=>'操作失败:找不到记录'];
            }
            $start_time = microtime(true);

            $tz_system_id = $TzSystemsUser->tz_system_id;
            $now_time = date('H:i');
            $clock_times = [16=>'02:00'];
            if(isset($clock_times[$tz_system_id]) && $now_time>$clock_times[$tz_system_id]){
                return ['status'=>300, 'msg'=>'关盘时间'];
            }
            $m = \Yii::$app->cache;
            $mkey = 'synBalance_'.$tz_system_id.'_'.$TzSystemsUser->id;
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
                    $rst = Lucky5Service::synBalance($TzSystemsUser->id, $is_auto);// p($rst);# 同步余额
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
            }elseif(in_array($tz_system_id, [16])){
                # 16、众发
                $rst = ZhongFaService::synBalance($TzSystemsUser->id);
            }
            $m->set($mkey, $rst, 8);
            $end_time = microtime(true);
            $time_consume = ($end_time-$start_time).'s';
            Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '同步余额耗时', ['tz_system_id'=>$tz_system_id, 'TzSystemsUserID'=>$TzSystemsUser->id, 'time_consume'=>$time_consume, 'rst'=>$rst]);
        }catch (\Exception $exception){
            $rst = ['status'=>300, 'msg'=>$exception->getMessage()];
            Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '获取余额失败', ['TzSystemsUsers.id'=>$id, 'err_msg'=>$exception->getMessage()]);
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
            Tool_Common::log('httpPostError','INFO','httpPost请求-1-1', $logArr);
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

    /**
     * @param string $access_token
     * @return int|string
     */
    public static function getTzSystemUserIdByAccessTokn($access_token=''){
        $tz_system_user_id = 0;
        if(empty($access_token)) return $tz_system_user_id;

        $tz_system_user_id = TzSystemsUsers::findOne(['access_token'=>$access_token])->id;

        return $tz_system_user_id;
    }

    /**
     * @desc 强制使用 TLS 1.2 的用户
     * @return false|string[]
     */
    public static function getSslVersionUids(){
        $uids = BetService::getConfig('CURLOPT_SSLVERSION_UIDS');

        return explode(',', $uids);
    }

    /**
     * @desc 获取ssl version 版本
     * @param string $uid
     * @return int
     */
    public static function getSslVersionByUid($uid='', $tzSystemId=''){
        $query = TzSystemsUsers::find()->where(['uid'=>(int)$uid]);
        if($tzSystemId !== '' && $tzSystemId !== null){
            $query->andWhere(['tz_system_id'=>(int)$tzSystemId]);
        }
        $TzSystemsUser = $query->orderBy(['id'=>SORT_ASC])->one();
        $sslMode = ($TzSystemsUser && $TzSystemsUser->hasAttribute('ssl_mode'))
            ? (int)$TzSystemsUser->ssl_mode
            : TzSystemsUsers::SSL_MODE_INHERIT;

        switch ($sslMode){
            case TzSystemsUsers::SSL_MODE_AUTO:
                return CURL_SSLVERSION_DEFAULT;
            case TzSystemsUsers::SSL_MODE_TLS12:
                return CURL_SSLVERSION_TLSv1_2;
            case TzSystemsUsers::SSL_MODE_COMPATIBLE:
                return CURL_SSLVERSION_TLSv1;
            default:
                $ssl_uids = BaseService::getSslVersionUids();
                return in_array((string)$uid, $ssl_uids, true)
                    ? CURL_SSLVERSION_TLSv1_2
                    : CURL_SSLVERSION_TLSv1;
        }
    }

    /**
     * @desc 设置全局代理
     * @param $ch
     * @return bool|array
     */
    public static function isProxySceneOpen($TzSystemsUsers, $proxyScene = self::PROXY_SCENE_BET){
        if(empty($TzSystemsUsers) || !(int)$TzSystemsUsers->is_use_proxy){
            return false;
        }
        if($proxyScene === self::PROXY_SCENE_LOGIN){
            return !$TzSystemsUsers->hasAttribute('is_proxy_login') || (int)$TzSystemsUsers->is_proxy_login === 1;
        }

        return !$TzSystemsUsers->hasAttribute('is_proxy_bet') || (int)$TzSystemsUsers->is_proxy_bet === 1;
    }

    public static function setPoxy($ch, $url='', $uid = 0, $proxyScene = self::PROXY_SCENE_BET, $tzSystemId = ''){
        try {
            $query = TzSystemsUsers::find()->where(['uid'=>(int)$uid]);
            if($tzSystemId !== '' && $tzSystemId !== null){
                $query->andWhere(['tz_system_id'=>(int)$tzSystemId]);
            }
            $TzSystemsUsers = $query->one();
            if(empty($TzSystemsUsers) OR !$TzSystemsUsers->is_use_proxy){
                throw_info('无需代理IP的用户或uid为空');
            }
            if(!self::isProxySceneOpen($TzSystemsUsers, $proxyScene)){
                throw_info('当前接口未开启代理');
            }
            $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
            if(!$POXY_STATUS){# CURL 代理开关
                throw_info('IP代理开关未开启1');
            }

            if(!$uid){
                return ['status'=>200, 'msg'=>'无需代理IP的用户或uid为空'];
            }
            Tool_Common::log('setPoxy', 'INFO', '设置全局代理2', ['url'=>$url, 'uid'=>$uid, 'tz_system_id'=>$tzSystemId, 'proxy_scene'=>$proxyScene]);

            return ProxyBaseService::setProxy($ch, $uid, $proxyScene, $tzSystemId); # 设置全局代理
        }catch (\Exception $e){
            Tool_Common::log('setPoxy', 'INFO', '设置全局代理3', ['url'=>$url, 'uid'=>$uid, 'tz_system_id'=>$tzSystemId, 'proxy_scene'=>$proxyScene, 'err_msg'=>$e->getMessage()]);
            return false;
        }
    }
}
