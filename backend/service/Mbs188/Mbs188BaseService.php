<?php
namespace backend\service\Mbs188;

use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\PoxyIPService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class Mbs188BaseService {

    /**
     * @desc 判断是否登录
     * @param $uid
     * @param $tz_system_id
     * @return bool
     */
    public static function isLogin($uid, $tz_system_id){

        $balance = self::getBalance($uid,$tz_system_id);

        $flag = $balance > 0 ? true : false;

        return (boolean)$flag;
    }

    /**
     * @desc 登录
     * @param int $uid
     * @param int $tz_system_id
     * @return array|bool|mixed|string
     */
    public static function login($uid = 1, $tz_system_id = 1){
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        # 第一步：获取cookie
        $rst = self::getCookie($uid,$tz_system_id);
        if(isset($rst['status']) && $rst['status'] == 300) return $rst;
        # 第二步：账号、验证码登录
        $rst = self::loginRemote($uid, $tz_system_id);
        # 第三步：同意
        if(isset($rst['Status']) && $rst['Status'] == 1){
            $rst = self::acceptAgreement($uid, $tz_system_id);
        }

        # 获取用户信息
        $rst = BaseService::synBalance($TzSystemsUsers->id); # 同步余额

        return $rst;
    }

    /**
     * @decription 获取远程html内容
     * @param $url
     */
    public static function getCurl($url,$header=[], $uid = 0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        //$header = array_merge(self::$postHeaders,$header);
        //if(strpos($url, 'GetPeriodsQuery')){ p([$url, $header]); }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        $poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);

        $data = curl_exec($ch);
        //if(strpos($url, 'GetInfoByName') !== false){ p(['header'=>$header, 'url'=>$url, 'rst'=>$data]); }
        $errno = curl_errno($ch);
        if($errno>0) {
            $str = 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/getCurl', 'ERR', 'getCurl获取', ['url'=>$url, 'postRst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr]);
            return $str;
        }
        if(!BaseService::is_json($data)){
            return $data;
        }
        $data = json_decode($data, true);

        if($data['Status'] == false){
            //$data['headers'] = $header;
        }

        return $data;
    }

    /**
     * @decription
     * @param $url
     */
    public static function httpGet($url,$header=[], $uid = 0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        //if(strpos($url, 'GetPeriodsQuery')){ p([$url, $header]); }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        self::setPoxy($ch, $url, $uid); # 设置代理IP

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);

        $data = curl_exec($ch);

        //$logArr = ['url'=>$url, 'url'=>$url, 'headers'=>$header,'data'=>$data]; p($logArr);
        //if(strpos($url, 'GetInfoByName') !== false){ p(['header'=>$header, 'url'=>$url, 'rst'=>$data]); }
        if(curl_close($ch)) {
            echo 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
        }
        if(!BaseService::is_json($data)){
            return $data;
        }
        $data = json_decode($data, true);

        if($data['Status'] == false){
            //$data['headers'] = $header;
        }

        return $data;
    }

    /**
     * @decription 获取远程html内容
     * @param $url
     */
    public static function httpPost($url,$post_data = [],$header=[], $uid = 0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        if(!$timeout) $timeout = 15;

        $ch = curl_init();
        $version = curl_version();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        $poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        //curl_setopt($ch, CURLOPT_USERAGENT, '');

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $data = curl_exec($ch);
        $errno = curl_errno( $ch );
        if($errno) {
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$header, 'rst'=>$data, 'errno'=>$errno];p($logArr);
        }
        curl_close($ch);
        if($errno){
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$header, 'rst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr];
            //p($logArr);
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/httpPostError','INFO','httpPost请求', $logArr);
            return '';
        }

        //if(strpos($url, 'betNumber')){ p(['url'=>$url, 'header'=>$header,'post_data'=>$post_data,'rstData'=>$data,curl_close($ch),$errno]); }
        if($data == 'ok'){
            return 'ok';
        }
        $rstData = json_decode($data, TRUE);
        //p(['data'=>$data, 'rstData'=>$rstData, 'post_data'=>$post_data, 'header'=>$header]);

        return $rstData;
    }

    /**
     * @desc 设置全局代理
     * @param $ch
     * @return bool
     */
    public static function setPoxy($ch, $url='', $uid = 0){
        $poxy_addr = PoxyIPService::getPoxyIp();
        if(!empty($poxy_addr)){
            //$poxy_addr = '218.85.247.70:20000';
            Tool_Common::log('setPoxy', 'INFO', '设置全局代理', ['url'=>$url, 'poxy_addr'=>$poxy_addr, 'uid'=>$uid]);
            $POXY_USER_IDS = BetService::getConfig('TENNIS_POXY_USER_IDS');
            $uids = explode(',', $POXY_USER_IDS);
            if(empty($uids) OR !in_array($uid, $uids) OR !$uid){
                return [];
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
        }

        return $poxy_addr;
    }

}
