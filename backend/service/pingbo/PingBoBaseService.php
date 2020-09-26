<?php
namespace backend\service\pingbo;

use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\CurlService;
use backend\service\PoxyIPService;
use common\general\helpers\Curl;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class PingBoBaseService {
    private static $account = '';
    private static $domain = '';

    public static function __init__($uid = '', $tz_system_id = ''){
        self::initParams($uid, $tz_system_id);
    }

    private static function initParams($uid = '', $tz_system_id = ''){
        $TzSystemsUsers = TzSystemsUsers::findOne(['tz_system_id'=>$tz_system_id, 'uid'=>$uid]);
        self::$account = $TzSystemsUsers->account;
        self::$domain = str_replace('http://', '', str_replace('https://', '',$TzSystemsUsers->ssc_domain)); # 结果如：www.baidu.com
    }

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
        //if(isset($rst['status']) && $rst['status'] == 300) return $rst;
        # 第二步：账号、验证码登录
        $rst = self::loginRemote($uid, $tz_system_id);
        # 第三步：同意
        /*
        if(isset($rst['Status']) && $rst['Status'] == 1){
            $rst = self::acceptAgreement($uid, $tz_system_id);
        }
        */

        # 获取用户信息
        $rst = BaseService::synBalance($TzSystemsUsers->id); # 同步余额

        return $rst;
    }

    /**
     * @desc 获取预登陆cookie
     * @param int $uid
     * @param int $tz_system_id
     * @return array
     */
    public static function getCookie($uid = 1, $tz_system_id = 1){
        self::__init__($uid, $tz_system_id);
        $rst = ['status'=>220, 'msg'=>'操作成功'];

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $_ = (int)(microtime(true) * 1000);
        $url = $TzSystemsUsers->ssc_domain.'/member-service/v1/announcement/list-limit?_='.$_.'&locale=zh_CN';
        $headers = [
            ':authority: www.ps3838.com',
            ':method: GET',
            ':path: /member-service/v1/announcement/list-limit?_='.$_.'&locale=zh_CN',
            ':scheme: https',
            'accept: */*',
            'accept-encoding: gunzip, deflate, br',
            'accept-language: zh-CN,zh;q=0.9,en;q=0.8',
            'referer: '.$TzSystemsUsers->ssc_domain.'/zh-cn/sports',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            $TzSystemsUsers->user_agent,
            'x-requested-with: XMLHttpRequest',
        ];
        $rst = self::getCurl($url, $headers, $isHeader = 1);
        p(['url'=>$url, 'header'=>$headers, 'rst'=>$rst]);


        return $rst;
    }

    /**
     * @desc 登陆接口
     * @param $uid
     * @param $tz_system_id
     */
    public static function loginRemote($uid, $tz_system_id){
        self::__init__($uid, $tz_system_id);

        $TzSystemUsers = TzSystemsUsers::findOne(['tz_system_id'=>$tz_system_id, 'uid'=>$uid]);
        $url = $TzSystemUsers->ssc_domain.'/member-service/v1/login?locale=zh_CN';

        $post_data = http_build_query([ 'loginId' => $TzSystemUsers->account, 'password' => $TzSystemUsers->password]);
        $headers = [
            ':authority: '.self::$domain,
            ':method: POST',
            ':path: /member-service/v1/login?locale=zh_CN',
            ':scheme: https',
            'accept: */*',
            'accept-encoding: gunzip, deflate, br',
            'accept-language: zh-CN,zh;q=0.9,en;q=0.8',
            'content-length: '.strlen($post_data),
            'content-type: application/x-www-form-urlencoded; charset=UTF-8',
            'cookie: '.$TzSystemUsers->cookie,
            //'cookie: JSESSIONID=07bfbf9dc0a0c06e9e6da8156bdc; _ga=GA1.2.1484142201.1592757279; _gid=GA1.2.958431061.1600624769; _og=QQ==; __cfduid=d0d72b5ec597271746bb54c9730eebba91600694389; lang=zh_CN; specialLeagueList=',
            'origin: '.$TzSystemUsers->ssc_domain,
            'referer: '.$TzSystemUsers->ssc_domain.'/zh-cn/sports',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'x-requested-with: XMLHttpRequest',
            $TzSystemUsers->user_agent,
        ];

        $rst = self::httpPost($url, $post_data, $headers, $TzSystemUsers->uid, $isCookie = 1);

        $deleteStrs = [
            'Path=/; Domain=.ps3838.com; HttpOnly; SameSite=None; Secure',
            'Path=/; Domain=.ps3838.com; SameSite=None; Secure'
        ];

        preg_match_all("/Set\-Cookie:([^\r\n]*)(.*?)/i", $rst, $matches);
        //preg_match_all("/set\-cookie:([^\r\n]*)(.*?)/i", $rst, $matches2);
        p(['rst'=>$rst, 'matches'=>$matches]);
        $keys = ['JSESSIONID', '__cfduid', '_ga', '_gid'];
        $cookie_str = '';
        foreach ($matches[1] as $match){
            $tmpCookie = $match;
            foreach ($deleteStrs as $deleteStr){
                $tmpCookie = str_replace($deleteStr, '', trim(trim($tmpCookie), ';'));
            }
            $cookie_str .= ';'.trim($tmpCookie, ';');
        }
        //p($cookie_str);
        $TzSystemUsers->cookie = str_replace('; ;', ';', trim($cookie_str, ';'));
        $TzSystemUsers->updated_at = time();
        if(!$TzSystemUsers->save()){
            return ['status'=>300, 'msg'=>$TzSystemUsers->getErrors()];
        }
        return ['status'=>200, 'msg'=>'操作成功'];
    }

    /**
     * @decription 同步用户余额 by account
     * @param $tz_system_user_id 表lt_tz_systems_users.id
     * @return array
     */
    public static function synBalance($tz_system_user_id){
        $TzSystemsUsers = TzSystemsUsers::findOne($tz_system_user_id);
        $balance = self::getBalance($TzSystemsUsers->uid, $TzSystemsUsers->tz_system_id);
        $msg = ['status'=>200, 'msg'=>'金额同步成功~','tz_system_user_id'=>$tz_system_user_id, 'balance'=>$balance ];

        $TzSystemsUsers->balance = $balance;
        $TzSystemsUsers->updated_at = time();
        if(!$TzSystemsUsers->save()){
            $msg = ['status'=>300, 'msg'=>'金额同步失败~'];
        }

        return $msg;
    }

    /**
     * @description 获取对应站点用户余额
     * @param $uid
     * @return mixed
     */
    public static function getBalance($uid, $tz_system_id){
        $rst = self::userInfo($uid, $tz_system_id);
        $balance = '';
        if(isset($rst['Status']) && $rst['Status'] == 1){
            $balance = $rst['Data']['credit_balance'];
        }
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $TzSystemsUsers->balance = $balance;
        $TzSystemsUsers->save();

        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/getBalance','INFO','幸运五星-用户余额', ['rst'=>$rst, 'balance'=>$balance]);

        return $balance;
    }

    /**
     * @desc 首页
     * @param $uid
     * @param $tz_system_id
     * @return mixed|string
     */
    public static function userInfo($uid, $tz_system_id){

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        //$url = self::getTzSiteInfo($tz_system_id, 'DO_LOGIN');
        $_t = (int)microtime(true) * 1000;
        $url = $TzSystemsUsers->ssc_domain . '/member-service/v1/account-balance?locale=zh_CN';
        if(strpos(strtolower($url), 'http') === false) return ['status'=>300, 'msg'=>'无效url', 'key'=>'SSC_INDEX', 'url'=>$url];
        //$post_data = http_build_query(['json']);
        $post_data = ['json'=>''];
        $headers = [
            //':authority: '.str_replace('https://', '', $TzSystemsUsers->ssc_domain),
            //':method: POST',
            //':path: /member-service/v1/account-balance?locale=zh_CN',
            //':scheme: https',
            'accept: */*',
            'accept-encoding: gzip, deflate, br',
            'accept-language: zh-CN,zh;q=0.9,en;q=0.8',
            'content-length: 4',//.strlen($post_data),
            //'content-type: application/x-www-form-urlencoded; charset=UTF-8',
            'content-type: application/x-www-form-urlencoded; charset=UTF-8',
            "Cookie: ".trim($TzSystemsUsers->cookie),
            'origin: '.$TzSystemsUsers->ssc_domain,
            'referer: '.$TzSystemsUsers->ssc_domain.'/zh-cn/sports',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            $TzSystemsUsers->user_agent,
            'x-requested-with: XMLHttpRequest',
        ];

        $start_time = microtime(true);
        $uid = max($TzSystemsUsers->uid, $uid);
        $data = self::httpPost($url, $post_data, $headers, $uid);
        $end_time = microtime(true);
        $time_consume = ($end_time-$start_time).'s';
        $logArr = ['uid'=>$uid, 'account'=>$TzSystemsUsers->account, 'time_consume'=>$time_consume, 'username'=>$TzSystemsUsers->username, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers,'data'=>$data];
        p($logArr);
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/userInfo','INFO','万博-用户信息', $logArr);
        return $data;
    }

    /**
     * @decription 获取远程html内容
     * @param $url
     */
    public static function getCurl($url,$headers=[], $uid = 0, $isHeader = 0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        //$header = array_merge(self::$postHeaders,$header);
        //if(strpos($url, 'GetPeriodsQuery')){ p([$url, $header]); }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        $poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER, $isHeader);

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

    public static function postCurl($url,$post_data = [],$header=[], $uid = 0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        if(!$timeout) $timeout = 15;
        $curl = new Curl();
        $curl->setOptions([
            'CURLOPT_URL' => $url,
            'CURLOPT_HEADER' => 0,
            'CURLOPT_RETURNTRANSFER' => 1,
            'CURLOPT_TIMEOUT' => $timeout,
            'CURLOPT_HTTPHEADER' => $header,
            'CURLOPT_POST' => 1,
            'CURLOPT_POSTFIELDS' => $post_data,
            'CURLOPT_SSL_VERIFYPEER' => 0,
            'CURLOPT_SSL_VERIFYHOST' => 0,
        ]);

        $errno = $curl->getError();
        $rst = $curl->post($url, $post_data);
        //$rst = $curl->getResponse();
        p(['errno'=>$errno, 'rst'=>$rst]);

    }

    /**
     * @decription 获取远程html内容
     * @param $url
     * @param array $post_data
     * @param array $header
     * @param int $uid
     * @param int $isHeader 0正常请求1打印头是否打印header获取cookie，主要用于登陆前后返回获取登陆的cookie
     * @return mixed|string
     */
    public static function httpPost($url,$post_data = [],$header=[], $uid = 0, $isHeader = 0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        if(!$timeout) $timeout = 15;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        $poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //curl_setopt($ch, CURLOPT_SSLVERSION, 3);

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,$isHeader);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $data = curl_exec($ch);
        $errno = curl_errno( $ch );
        //if($errno && strstr($url, 'BatchBet') OR strstr($url, 'MultipleBet')){
        $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$header, 'rst'=>$data, 'errno'=>$errno];p($logArr);
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
        if(BaseService::is_json($data)){
            $rstData = json_decode($data, TRUE);
        }else{
            $rstData = $data;
        }
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
