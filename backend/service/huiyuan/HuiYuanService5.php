<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service\huiyuan;
use backend\models\BettingRecords;
use backend\models\SscKjData;
use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\models\User;
use backend\models\UserCustomPlans;
use backend\models\UserFollowData;
use backend\models\UserSysPlans;
use backend\service\BetService;
use backend\service\CurlService;
use backend\service\HN0898Service;
use backend\service\SscDataService;
use backend\tools\Tools;
use common\service\CaptchaCodeService;
use common\tools\Tool_Common;
use  yii;

class HuiYuanService5 extends HuiYuanBaseService {
    public static $username = '';
    public static $password = '';
    public static $baseUrl =  '';
    public static $domain =  '';
    public static $user_id =  '';
    public static $cookie = '';
    public static $account = '';
    public static $position = '';
    public static $is_simulate = 1;
    public static $tzSiteInfo = [];
    public static $tz_system_id = ''; # 投注系统id
    public static $user_agent = '';

    public static $headers = [];

    /**
     * HN0898Service constructor.
     * @param string $account
     * @param int $playway 投注方式
     * @param float $single 投注倍数 1:元 0.1:角
     * @param int $is_simulate 默认为模拟投注
     */
    public function __construct($uid = 1, $tz_system_id = 1){
        self::$headers = [
            //'Accept:*/*',
            'Accept: application/json, text/plain, */*',
            'Content-Type: application/x-www-form-urlencoded',
            //'X-Requested-With:XMLHttpRequest',
        ];
        self::__init($uid, $tz_system_id);
    }

    private static function __init($uid = 1, $tz_system_id = 2){
        $User = User::findOne($uid);
        self::$user_id = $uid;
        self::$account = $User->account;
        self::$tz_system_id = $tz_system_id;
        self::$username = $User->username;
        self::$tzSiteInfo = self::getTzSiteInfo($tz_system_id);
        self::$user_agent = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.'.$uid;
        //self::unitHeaders('Cookie'); # 去除重复的headers，主要是Cookie

        self::$baseUrl = self::$tzSiteInfo['baseUrl'];
        self::$domain = self::$tzSiteInfo['domain'];
        $headers = [
            "Token: ".trim(self::$cookie),
            "Origin: ".str_replace('www.','',self::$baseUrl),
            //"Host:".str_replace('www.','',self::$domain),
            "Referer: ".str_replace('www.','',self::$baseUrl).'/',
        ];
        self::$headers = array_unique(array_merge(self::$headers,$headers));
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
     * @desc 同步用户所有站点余额
     * @param $uid
     */
    public static function synUserAllBalance($uid){
        //$TzSystemsUsers = TzSystemsUsers::find()->where(['status'=>1, 'uid'=>$uid])->all();
        $TzSystemsUsers = TzSystemsUsers::findAll(['status'=>1, 'uid'=>$uid]);
        foreach ($TzSystemsUsers as $TzSystemsUser){
            $rst = self::synBalance($TzSystemsUser->id);
        }

        return $rst;
    }

    /**
     * @decription 新版投注，真实投注入口， 未完待续 2018.12.23
     *
     * @param $qihao
     * @param $plan_id
     * @param $codes
     * @return array
     */
    public function bet($qihao, $plan_id, $codes){
        $time = date('H:i');
        //p([$qihao, $plan_id, 'codes'=>$codes]);
        $plan = UserSysPlans::findOne($plan_id);
        $playway = $plan->playway ? $plan->playway : 3;
        $single = $plan->single ? $plan->single : 0.1;
        $tz_type = $plan->tz_type ? $plan->tz_type : 0;
        $buy_type = $plan->buy_type ? $plan->buy_type : 1;
        $lottery_type = $plan->lottery_type;
        if(!self::$user_id) return ['status'=>400,'msg'=>'账号为空，不能识别用户'];
        $data = ['status'=>200, 'msg'=>$qihao.'期投注成功!', 'time'=>date('Y-m-d H:i:s')];
        $qihaoInfo = self::getPreTz(self::$user_id, self::$tz_system_id, $lottery_type);
        //p([self::$user_id, self::$tz_system_id, $lottery_type, $qihaoInfo]);
        if($qihaoInfo['status'] != 200) return $qihaoInfo;

        # 验证
        $rst = self::validateBettingContent($playway, $codes); # codes:13579,,13579,,@13579,,13579,,
        if($rst['status'] != 200){
            $data = ['status'=>300, 'msg'=>$qihao.$rst['msg']];
        }

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>self::$user_id, 'tz_system_id'=>self::$tz_system_id]);
        $account = $TzSystemsUsers->username;  # 投注用户账号，自动化平台账号

        $bet_codes = self::formCodesStyle($codes, $playway, $plan->single);
        //p(['codes'=>$codes, 'bet_codes'=>$bet_codes]);

        $post_data = [
            'PeriodId' => $qihaoInfo['PeriodsID'],
            'LotteryId' => 2,	# 备注：2重庆时时彩、18快乐8
            'BetNumber' => $bet_codes,
            'BetAmt'=> $single,
            'BetWayId' => self::getWayId($playway),
            'UserName' => $TzSystemsUsers->account,
        ];

        $data['code'] = $codes;
        $strLen = strlen(http_build_query($post_data));
        $headers = [
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Connection: keep-alive',
            'Content-Length:'.$strLen,
            'Content-Type: application/x-www-form-urlencoded',
            'Host: '.str_replace('http://', '', $TzSystemsUsers->ssc_domain),
            'Cookie: '.$TzSystemsUsers->cookie,
            'Origin: '.$TzSystemsUsers->ssc_domain,
            'Referer: '.$TzSystemsUsers->ssc_domain.'/',
            'Token:'.self::getCookieDataByKey($TzSystemsUsers->cookie, 'Token'),
            'type: 2',
            $TzSystemsUsers->user_agent,
        ];

        //$headers = array_merge(self::$headers, $headers);
        //$url = self::getUserUrlArr(self::$user_id, 'ORDER_TZ');
        $url = self::getTzSiteInfo(self::$tz_system_id, 'MULBET_URL'); # .'?'.http_build_query($post_data);

        //p(['headers'=>$headers, 'url'=>$url, 'account'=>$account, 'post_data'=>$post_data]);

        # 缓存锁
        $m = \Yii::$app->cache;
        $betKey = BetService::buildBetKey($account, self::$tz_system_id, $lottery_type, $qihao, $plan_id);
        if($betLock = $m->get($betKey)){
            return ['status'=>303, 'msg'=>'已经投注过了', 'key'=>$betKey];
        }

        if(in_array($tz_type, [20,23,25,19])){
            # 和值投注反应时间比较久，无需返回直接锁住
            $time = 60*18;
            if(substr($qihao,6) == '010') $time = 60 * 60 * 4; # 十小时
            $m->set($betKey, 1, $time);
        }
        # 真实投注
        $start_time = microtime(true);
        //p([$url,$post_data, $headers, $playway]);
        $rst = CurlService::postCurl($url, http_build_query($post_data), $headers);
        //$rst = json_encode($rst);
        //p([$playway, $rst,$url,$post_data, $headers]);
        $end_time = microtime(true);
        $time_consume = ($end_time - $start_time). 's';
        if($rst['Status'] != 1 OR !$rst){
            $tzRst = ['uid'=>self::$user_id,'status'=>301, 'msg'=>$qihao.$rst['msg'],'url'=>$url,'post_data'=>$post_data, 'user_id'=>self::$user_id, 'headers'=>$headers, 'postRst'=>$rst, 'time_consume'=>$time_consume];
            if($tz_type != 20){
                $tzRst['code'] = $codes;
            }
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/bet','INFO','7时彩投注记录-投注失败', $tzRst);
            return $tzRst;
        }
        $time = 600;
        //if(substr($qihao,6) == '023') $time = 60 * 60 * 10; # 十小时
        if('00:00'<date('H:i') && date('H:i')<'09:00') $time = 60 * 60 * 10; # 十小时
        $m->set($betKey, 1, $time);

        $n = count(explode('@',$codes));
        if(in_array($playway, [2, 3]) && $tz_type != 20){
            $totalmoney = SscDataService::calTzTotalMoney($codes, $single, $playway);
        }else{
            $totalmoney = $n * $single; // 投注总金额 = 注数 * 倍数
        }
        # 获取方案号，记录id, 用于撤单
        //$snInfo = SevenService::getSn(self::$user_id, self::$tz_system_id);// 用户信息 Array ( [sn] => 403054677338701312 [qihao] => 190412023 [snid] => 31724311|1,31724312|1 )

        $insertData = [
            'playway'=> $playway,  // 投注方式
            'tz_type'=> $tz_type,  // 投注类型
            'buy_type'=> $buy_type,  // 购买方向类型
            'uid'=> self::$user_id,  // 投注账号id
            'account' => $account,
            'lottery_type' => $lottery_type, # 彩种
            'plan_id' => $plan_id, # 计划id
            'codes' => (string)$codes,  // 投注号码
            'qihao' => $qihao,  // 投注期号
            'tz_system_id' => self::$tz_system_id,  // 投注系统tz_systems .id
            'sn'=>$snInfo['sn'],
            'snid'=>$snInfo['snid'],
            'order_type'=>3, # 单双三字定
            'is_simulate' => 0,  // 是否模拟投注
            'single' => $single,  // 投注倍数
            'betting_money'=> $totalmoney,  // 投注金额
        ];
        //p($insertData);
        $insertRst = BetService::_logRecords($insertData);
        self::$headers = [];

        $logArr = ['uid'=>self::$user_id,'url'=>$url,'post_data'=>$post_data,'headers'=>$headers, 'postRst'=>$rst,'insertData'=>$insertData, 'insertRst'=>$insertRst];
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/bet','INFO','7时插入记录-真实投注', $logArr);

        return $data;
    }

    /**
     * @description  撤单
     * @param $snid
     * @param $tz_system_id
     * @return mixed
     */
    public static function cancelOrder($id, $tz_system_id){
        $BettingRecords = BettingRecords::findOne($id);
        $uid = $BettingRecords->uid;
        $snid = $BettingRecords->snid;
        self::__init($uid, $tz_system_id);

        $qihao = HN0898Service::getQihao($BettingRecords->lottery_type);
        $post_data = [ 'ids'=>$snid, 'period_no' => '20'.$qihao];
        $headers = self::$headers;

        $url = self::getTzSiteInfo(self::$tz_system_id, 'CANCEL_ORDER').'?'.http_build_query($post_data);

        $rst = CurlService::postCurl($url, $post_data, $headers);
        if($rst['Status'] == 1 && strpos($rst['Data'], '退码成功')){
            $BettingRecords = BettingRecords::findOne(['snid'=>$snid]);
            $BettingRecords->cancel_status = 1;
            $BettingRecords->save();
            $rst['status'] = 200;
        }else{
            if(isset($rst['Data'])) p($rst['Data'], 0);
            sleep(2);
        }
        $logArr = ['snid'=>$snid,'headers'=>$headers,'post_data'=>$post_data, 'rst'=>$rst];
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cancelOrder','INFO','撤单记录', $logArr);

        return $rst;
    }

    /**
     * @desc 希腊站点号码格式化成本站号码存储格式
     * @param $codes # codes:13579,,13579,,@13579,,,13579,      -- playway:10
     * @param $codes # codes:13579,X,13579,X@13579,X,X,13579   -- playway:4
     * @param $playway
     * @return string  格式：X1XX,X6XX
     */
    public static function formCodesStyle($codes, $playway = 10, $single = 0.1){
        //p([$codes, $playway, $single]);
        $codes = explode('@', $codes);
        //p($codes, 0);

        $codesArr = [];
        foreach ($codes as $code){
            //p($tmpArr);
            switch ($playway) {
                case 4:
                case 10: # playway 一字定 2357,2468,X,X
                    $tmpArr = explode(',', $code);
                    foreach ($tmpArr as $key => $str) {
                        $len = strlen($str);
                        if ($len > 0) {
                            for ($i = 0; $i < $len; $i++) {
                                if($str[$i] == 'X') continue;
                                if ($key == 0) {
                                    $codesArr[] = $str[$i] . 'XXX';
                                } elseif ($key == 1) {
                                    $codesArr[] = 'X' . $str[$i] . 'XX';
                                } elseif ($key == 2) {
                                    $codesArr[] = 'XX' . $str[$i] . 'X';
                                } elseif ($key == 3) {
                                    $codesArr[] = 'XXX' . $str[$i];
                                }
                            }
                        }
                    }
                    break;
                case 1: # 二字定 2357,2468,X,X
                    break;
                case 2: # 三字定
                    $codesArr[] = str_replace(',','',strtolower($code)).'#'.$single;
                    break;
                case 3: # 四字定
                    $codesArr[] = str_replace(',','',$code).'#'.$single;
                    break;
            }

        }

        return implode(',', $codesArr);
    }

    /**
     * @description 更新计划表状态
     * @param $id
     * @param $account
     * @return array
     */
    public static function updateSysPlansStatus($id, $status, $account)
    {
        $m = \Yii::$app->cache;
        $mkey = 'updateSysPlansStatus_'.$id.'_'.$status;
        if($rst = $m->get($mkey)) return false;

        $UserSysPlans = UserSysPlans::findOne(['account' => $account, 'id' => $id]);
        $UserSysPlans->status = (int)$status;

        $m->set($mkey, 1, 10);

        $rst = $UserSysPlans->save(false);

        return $rst;
    }

    /**
     * @description 更新计划表状态
     * @param $id
     * @param $account
     * @return array
     */
    public static function updateSysPlansBuyType($id, $status, $account)
    {
        $m = \Yii::$app->cache;
        $mkey = 'updateSysPlansBuyType_'.$id.'_'.$status;
        if($rst = $m->get($mkey)) return false;

        $UserSysPlans = UserSysPlans::findOne(['account' => $account, 'id' => $id]);
        $UserSysPlans->buy_type = (int)$status;

        $m->set($mkey, 1, 3);

        $rst = $UserSysPlans->save(false);

        return $rst;
    }

    /**
     * @desc 立即投注之后设置缓存锁
     * @param $qihao
     * @return bool
     */
    public static function afterBetNow($qihao){
        $m = \Yii::$app->cache;

        $pkey = \Yii::$app->params['TZ_SWITCH_KEY'].'_'.$qihao;
        $rst = $m->set($pkey, 0, 5);

        return $rst;
    }

    /**
     * @desc 计划任务列表立即投注
     * @param $id
     * @param $account
     */
   public static function userSysPlansTzNow($id, $account){
       if(!$UserSysPlans = UserSysPlans::findOne(['id'=>$id, 'account'=>$account])){
           return ['status'=>300, 'msg'=>'找不到对应记录'];
       }
       $m = \Yii::$app->cache;
       $qihao = HN0898Service::getQihao();
       $mkey = 'userSysPlansTzNow_'.$account.'_'.$qihao.'_'.$UserSysPlans->playway;
       if($r = $m->get($mkey)) return ['status'=>300, 'msg'=>'已经投注过了，请稍后'];


       $rst = self::tzByPlanId($id);
       $m->set($mkey, 1, 10);

       return $rst;
    }

    /**
     * @decription 验证投注格式是否正确，待完善 2018.0220
     * @param $playway
     * @param $code
     * @return array
     */
    public static function validateBettingContent($playway,$code, $flag = ','){
        $rst = ['status'=>200, 'msg'=>'操作成功!'];
        switch ($playway){
            //===========================更多玩法====================
            case 1: // 两字定 code:25,36,X,X@4,X,4,X@3567,X,X,2357
                break;
            case 2: // 三字定 code:578,368,359,X
                break;
            case 3: // 四字定 code:478,4679,469,4678
                break;
            case 4: // 一字定 code:24,237,125,2346 或者 13579,,13579,,@13579,,13579,,
                break;
            case 5: // 二字现 code:28@46@78@23
                break;
            case 6: // 三字现 code:345@258
                break;
            case 10: // 定位胆 code:0,,0,,0
                break;
            case 11: // 后二 code:,,,256,246@,,,23,246
                break;
            case 12: // 后三 code:,,13,1236,1256
                break;
            case 13: // 大小单双 code:小双,小单双@单,单双
                break;
            case 14: // 前二  code:13,246,,,
                break;
            case 15: // 前三 code:2478,2468,23678,,  或者：2478,2468,23678,,@458,457,48,,
                break;
            case 16: // 组三 code:23568
                break;
            case 17: // 组六 code:13579
                break;
            //===========================更多玩法====================
            default:;
        }

        return $rst;
    }
    /**
     * @description 获取对应站点用户余额
     * @param $uid
     * @return mixed
     */
    public static function getBalance($uid, $tz_system_id){
        self::__init($uid, $tz_system_id);
        $rst = self::userInfo($uid, $tz_system_id);
        $balance = '';
        if(isset($rst['Status']) && $rst['Status'] == 1){
            $balance = $rst['Data']['List'][0]['cashAmount'];
        }

        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/getBalance','INFO','希腊-用户余额', $rst);

        return $balance;
    }

    /**
     * @desc 获取订单号
     * @param $sn 方案号
     * @return mixed
     */
    public static function getSnidBySn($sn){
        $m = \Yii::$app->cache;
        $mkey = 'SNID_'.$sn;
        if(!$snid = $m->get($mkey)){
            //$url = HN0898Service::getUserUrlArr($user_id,'SSC_INDEX');
            $url = HN0898Service::getTzSiteInfo(self::$tz_system_id,'SSC_INDEX');
            $headers = [
                'Cache-Control: max-age=0',
                //'Upgrade-Insecure-Requests: 1',
                "Host:".str_replace('www.','',self::$domain),
                //"Accept-Encoding: gzip, deflate, br",
                //"Accept-Encoding: gunzip, deflate, br", # gunzip 防止乱码
                "Cookie:".self::$cookie,
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8'
            ];
            //$headers = array_merge(self::$headers,$headers);

            $content = RemoteHtmlService::getRemoteHtmlContent($url, $headers);
            $preg = "/<td>".$sn."(.*?) snid=(.*?)\>点击撤单/ism"; // 这里是表达式，大神看看
            preg_match_all($preg,$content,$matches);
            $snid = $matches[2][0];
            $logData = ['url'=>$url,'headers'=>$headers, 'snid'=>$snid,/* 'content'=>$content*/];
            //p($logData);
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/getSnidBySn','INFO','获取方案号', $logData);
            $m->set($mkey, 6*3600);
        }

        return $snid;
    }

    /**
     * @description 获取cookie并写表lt_tz_systems_users，场景：未登录情况下
     * @param $uid
     * @param $tz_system_id
     * @return mixed
     */
    public static function getCookie($uid,$tz_system_id){
        self::__init($uid, $tz_system_id);
        $m = \Yii::$app->cache;
        $mkey = 'UPDATE_COOKIE_TIME_'.$uid.'_'.$tz_system_id;
        if(!$cookie = $m->get($mkey)){
            //p(HN0898Service::getTzSiteInfo($tz_system_id));
            # 1、预登录
            $_t = floor(microtime(true) * 100);
            $url = self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/api/home/GetValidateCode'.'?_'.$_t;
            if(strpos(strtolower($url), 'http') === false OR is_array($url)) return ['status'=>300, 'msg'=>'无效url'];
            $user_agent = 'User-Agent: '.$_SERVER['HTTP_USER_AGENT'];
            //$user_agent = self::$user_agent;
            $domain = self::getTzSiteInfo($tz_system_id,'domain');
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
                'Accept-Encoding: gzip, deflate, br',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                //'Connection: keep-alive',
                'Cache-Control: max-age=0',
                'Host: '.$domain,
                //'Referer: '.self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/login.html',
                'Upgrade-Insecure-Requests: 1',
                'Accept-Encoding: gunzip, deflate',
                'X-Requested-With: XMLHttpRequest',
                $user_agent,
                //self::$user_agent,
            ];
            $cookie = CurlService::curlGetCookie($url, $headers);
            $cookieData = $cookie;
            if($cookieData){
                $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
                $TzSystemsUsers->cookie = trim($cookieData);
                $TzSystemsUsers->user_agent = $user_agent;
                $TzSystemsUsers->cookie = str_replace('; path=/','', $TzSystemsUsers->cookie);
                $TzSystemsUsers->save();
            }
            $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'cookie'=>$cookie, 'url'=>$url, 'headers'=>$headers];
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/getCookie','INFO','0898Cookie记录', $logArr);
            $cookie = str_replace(' ValidateToken=','',$cookie);
            $cookie = str_replace('; path=/','',$cookie);

            //p(['url'=>$url, 'headers'=>$headers, 'cookie'=>$cookie],0);
            $m->set($mkey, $cookie, 180);
        }
        return $cookie;
    }

    /**
     * @desc 下载图片验证码
     * @param $uid
     * @param $tz_system_id
     * @param $cookie_key
     * @return bool
     */
    public static function downLoadCodeImg($uid, $tz_system_id, $cookie_key){
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $headers = [
            'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
            'Accept-Encoding: gzip, deflate',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Connection: keep-alive',
            'Cookie: '.$TzSystemsUsers->cookie,
            'Host: '.str_replace('http://', '', self::getTzSiteInfo($TzSystemsUsers->tz_system_id,'SSC_INDEX')),
            'Referer: '.$TzSystemsUsers->ssc_domain.'/login.html',
            //'Upgrade-Insecure-Requests: 1',
            $TzSystemsUsers->user_agent,
            //self::$user_agent,
        ];
        $_t = floor(microtime(true) * 1000);
        $url = self::getTzSiteInfo($TzSystemsUsers->tz_system_id,'SSC_INDEX').'/api/home/GetValidateCode?_='.$_t;
        $imageData = CurlService::getCurl($url, $headers);
        //p($imageData);
        $filename = Yii::$app->basePath . "/runtime/captcha/".$uid.'_'.$tz_system_id.'_'.$cookie_key.".png";
        $tp = fopen($filename,"w");
        fwrite($tp, trim($imageData));
        fclose($tp);
        $logData = ['url'=>$url,'headers'=>$headers, 'filename'=>$filename];
        //p($logData);
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/downLoadCodeImg','INFO','下载图片验证码', $logData);

        return true;
    }

    /**
     * @desc 下载图片验证码
     * @param $uid
     * @param $tz_system_id
     * @param $cookie_key
     * @return bool
     */
    public static function downLoadCodeImgNew($uid, $tz_system_id){
        self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $headers = [
            'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
            'Accept-Encoding: gzip, deflate',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Connection: keep-alive',
            //'Cookie: '.$TzSystemsUsers->cookie,
            'Host: '.str_replace('http://', '', self::getTzSiteInfo($TzSystemsUsers->tz_system_id,'SSC_INDEX')),
            'Referer: '.$TzSystemsUsers->ssc_domain.'/login.html',
            //'Upgrade-Insecure-Requests: 1',
            //$TzSystemsUsers->user_agent,
            self::$user_agent,
            'Cookie:ValidateToken=08fbfb46cc8d59159308d435d21c5bb7',
        ];
        $_t = floor(microtime(true) * 1000);
        $url = self::getTzSiteInfo($TzSystemsUsers->tz_system_id,'SSC_INDEX').'/api/home/GetValidateCode?_='.$_t;
        $imageData = CurlService::getCurl($url, $headers);
        $filename = Yii::$app->basePath . "/runtime/captcha/".$uid.'_'.$tz_system_id.'_'.$cookie_key.".png";
        $tp = fopen($filename,"w");
        fwrite($tp, $imageData);
        fclose($tp);
        $logData = ['url'=>$url,'headers'=>$headers, 'filename'=>$filename];
        //p($logData);
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/downLoadCodeImg','INFO','下载图片验证码', $logData);

        return true;
    }

    public static function login($uid = 1, $tz_system_id = 1){
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        if($TzSystemsUsers->balance > 0) {
            return ['status'=>200, 'msg'=>'已经登录的状态'];
        }
        //self::__init($uid, $tz_system_id);
        $rst = false;

        # 第一步：获取cookie
        $cookie_key = self::getCookie($uid,$tz_system_id);
        if(isset($cookie_key['status']) && $cookie_key['status'] == 300) return $cookie_key;
        # 第二步：下载验证码图片
        self::downLoadCodeImg($uid, $tz_system_id, $cookie_key);
        //p([$uid, $tz_system_id, $cookie_key]);
        # 第三步：调验证码接口获取验证码
        //$captchaCode = '888888'; $rst = self::loginRemote($uid, $tz_system_id,$captchaCode); p($rst);  # 测试
        $captchaCodeRst = Tools::getCaptchaCode($uid, $tz_system_id, $cookie_key); # 真实调用验证码接口，收费
        //p($captchaCodeRst);
        //$code = $captchaCode['result'];
        if($captchaCodeRst['status'] == 200){
            $code = $captchaCodeRst['code'];
            # 第四步：账号、验证码登录
            $rst = self::loginRemote($uid, $tz_system_id, $code);
        }

        # 第四步：同意
        //$rst = self::acceptAgreement($uid, $tz_system_id);

        return $rst;
    }

    public static function loginNew($uid = 1, $tz_system_id = 1){
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        if($TzSystemsUsers->balance > 0) {
            return ['status'=>200, 'msg'=>'已经登录的状态'];
        }
        self::__init($uid, $tz_system_id);
        $rst = false;

        # 第一步：获取cookie
        //$cookie_key = self::getCookie($uid,$tz_system_id);
        //if(isset($cookie_key['status']) && $cookie_key['status'] == 300) return $cookie_key;
        # 第二步：下载验证码图片
        $rst = self::downLoadCodeImgNew($uid, $tz_system_id);
        $cookie_key = $rst['cookie_key'];
        //p([$uid, $tz_system_id, $cookie_key]);
        # 第三步：调验证码接口获取验证码
        //$captchaCode = '888888'; $rst = self::loginRemote($uid, $tz_system_id,$captchaCode); p($rst);  # 测试
        $captchaCodeRst = Tools::getCaptchaCode($uid, $tz_system_id, $cookie_key); # 真实调用验证码接口，收费
        //p($captchaCodeRst);
        //$code = $captchaCode['result'];
        if($captchaCodeRst['status'] == 200){
            $code = $captchaCodeRst['code'];
            # 第四步：账号、验证码登录
            $rst = self::loginRemote($uid, $tz_system_id, $code);
        }

        # 第四步：同意
        //$rst = self::acceptAgreement($uid, $tz_system_id);

        return $rst;
    }

    /**
     * @desc 获取方案号
     * @param $uid
     * @param $tz_system_id
     * @return array
     */
    public static function getSn($uid, $tz_system_id){
        $rst = HuiYuanBaseService::userInfo($uid, $tz_system_id);
        $data = [];
        if($rst['Status'] !=1) return $data;

        $data['sn'] = $rst['Data']['serial_no'];
        $data['qihao'] = substr($rst['Data']['previous_period_no'], 2);
        $tzDatas = $rst['Data']['Details'];
        $snidStr = '';
        foreach ($tzDatas as $tzData){
            $snidStr .= $tzData['bet_id'].'|1,';
        }
        $data['snid'] = trim($snidStr, ',');
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/getSn','INFO','7时彩获取方案号', $data);

        return $data;
    }

    /**
     * @desc 登陆
     * @param $uid
     * @param $tz_system_id
     * @param $code
     * @return mixed|string
     */
    private static function loginRemote($uid, $tz_system_id, $code){
        self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        $post_data = [
            'NECaptchaValidate' => $code,
            'Password' => $TzSystemsUsers->password,
            'Username' => $TzSystemsUsers->account,
        ];

        $url = self::getTzSiteInfo($tz_system_id, 'SSC_INDEX').'/api/Home/MemberLogin';
        if(strpos(strtolower($url), 'http') === false OR is_array($url)) return ['status'=>300, 'msg'=>'无效url', 'url'=>$url];
        $strLen = strlen(json_encode($post_data));
        $headers = [
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            //'Content-Type: application/x-www-form-urlencoded',
            'Connection: keep-alive',
            'Content-Length: '.$strLen,
            'Content-Type: application/json;charset=UTF-8',
            'Cookie: '.str_replace('; path=/', '',$TzSystemsUsers->cookie),

            'Host: '.str_replace('http://', '', $TzSystemsUsers->ssc_domain),
            'Origin: '.$TzSystemsUsers->ssc_domain,
            'Referer: '.$TzSystemsUsers->ssc_domain.'/login.html',
            $TzSystemsUsers->user_agent,
            //self::$user_agent,
        ];

        //$headers = array_unique(array_merge($headers,self::$headers));

        $data = CurlService::kl8PostCurlLogin($url,http_build_query($post_data), $headers);
        //sleep(10);
        //self::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'code'=>$code, 'url'=>$url,'post_data'=>$post_data, 'headers'=>$headers,'data'=>$data];
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/loginRemote','INFO','0898登陆记录', $logArr);
        p($logArr);
        return $data;
    }

    /**
     * @desc 登陆
     * @param $uid
     * @param $tz_system_id
     * @return mixed|string
     */
    private static function acceptAgreement($uid, $tz_system_id){
        $rst = CurlService::getCurl('http://zhxtimght.miandianjiameng.com/static/js/0.8c65143ce3ef6da23e77.js');
        return $rst;
        self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        $_t = microtime(true) * 10000;
        $url = self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/Member/AcceptAgreement'.'?_'.$_t;
        $headers = [
            "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
            "Cache-Control:max-age=0",
            "Upgrade-Insecure-Requests:1",
            "Cookie: ".trim($TzSystemsUsers->cookie),
            "Host:".str_replace('www.','',self::$domain),
        ];

        $data = CurlService::getCurl($url, $headers);
        //sleep(10);
        //HN0898Service::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'headers'=>$headers,'data'=>$data];
        //p($logArr);
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/loginRemote','INFO','7时彩登陆记录', $logArr);
        return $data;
    }

    /**
     * @desc 首页
     * @param $uid
     * @param $tz_system_id
     * @return mixed|string
     */
    public static function userInfo($uid, $tz_system_id){
        self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        $url = HuiYuanBaseService::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/api/MemberDesk/GetInfoByName?Lottery=2';
        if(strpos(strtolower($url), 'http') === false OR is_array($url)) return ['status'=>300, 'msg'=>'无效url', 'url'=>$url];
        $headers = [
            "Accept: application/json, text/plain, */*",
            "Accept-Encoding: gzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Cookie:".trim($TzSystemsUsers->cookie),
            //"Origin:".str_replace('www.','',self::$baseUrl),
            "Host:".str_replace('www.','',self::$domain),
            "Referer:".$TzSystemsUsers->ssc_domain.'/',
            $TzSystemsUsers->user_agent,
        ];
        $data = CurlService::getCurl($url, $headers);
        //sleep(10);
        //HN0898Service::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'headers'=>$headers,'data'=>$data];
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/loginRemote','INFO','7时彩-登陆记录', $logArr);
        return $data;
    }


    /**
     * @decription 获取即将开奖的期号
     * @param int $type
     * @return string
     */
    public static function getQihao($type = 1){
        $db = Yii::$app->db;
        //$date = date('Y-m-d');
        $time = date("H:i:s");
        $sql = "SELECT actionNo FROM {{%data_time}} WHERE actionTime >= '".$time."' AND type=$type ORDER BY id ASC";
        $rst = $db->createCommand($sql)->queryOne();
        if(!$rst && $type == 1){
            $rst['actionNo'] = 001; // 大于 23:56  -> null  设置为120期
            $qihao = date("ymd",strtotime('+1 day')).sprintf("%03d", $rst['actionNo']);
        }else{
            $qihao = date("ymd").sprintf("%03d", $rst['actionNo']);
        }

        return $qihao;
    }

    /**
     * @decription 获取当前时间已经开奖的期号
     * @param int $type
     * @return string
     */
    public static function getCurrentQihao($type = 'ssc'){
        $qihao = HN0898Service::getQihao();
        $strRQ = substr($qihao, 0, 6);
        $qihao_n = str_replace($strRQ, '', $qihao);

        switch ($type){
            case 'ssc':
                if($qihao_n == '001') $actionNo = '120';
                else $actionNo = $qihao_n - 1;
                break;
        }

        $qihao = date("ymd").sprintf("%03d", $actionNo);

        return $qihao;
    }

    /**
     * @description 投注相关数据获取
     * @param $account
     * @return UserFollowData|array|null
     */
    public static function getTzData($account){
        $fields = ['id', 'account', 'code', 'status', 'playway', 'single'];
        $tzData = UserFollowData::find()->select($fields)->where(['status'=>1,'account'=>$account])->asArray()->one();

        return $tzData;
    }

    /**
     * @decipion 记录投注记录(条件：已经投注成功)
     * @param $data
     * @return bool
     */
    public static function _logRecords($data){
        if(!$data OR !is_array($data)) return false;
        $insertData = [
            'sn' => $data['sn'],  // 方案号
            'snid'=>$data['snid'],
            'playway'=> $data['playway'],  // 投注方式
            'tz_type'=> $data['tz_type'],  // 投注类型
            'account'=> $data['account'],  // 投注账号
            'playway_name'=> BetService::lotteryClass($data['playway']),  // 投注名称
            'uid' => $data['uid'],  // 投注用户id
            'buy_type'=> $data['buy_type'],  // 购买方向类型
            'codes' => $data['codes'],  // 投注号码
            'qihao' => $data['qihao'],  // 投注期号
            'plan_id' => $data['plan_id'], # 计划id
            'single' => $data['single'],  // 投注期号
            'betting_money'=> $data['betting_money'],  // 投注金额
            'tz_system_id'=> $data['tz_system_id'],  // 投注系统tz_systems .id
            'lotteryclass'=> 'ssc',  // 彩种
            'is_simulate' => $data['is_simulate'],  // 是否模拟投注
            'position' => $data['position'],  // 是否模拟投注
            'order_type' => $data['order_type'],  // 订单来源
            'bonus' => 0.00,  // 奖金
            'status' => 0,  // 中奖状态：0:正常、1:中奖、2:未中奖
            'createtime' => time(),  // 下单时间 int
            'create_time' => date('Y-m-d H:i:s'),  // 下单时间 string
        ];
        //if($data['tz_type'] == 20) $insertData['codes'] = md5($insertData['codes']);

        $bettingRecords = new BettingRecords();
        $bettingRecords->setAttributes($insertData);
        $rst = $bettingRecords->save();

        if(!$rst) return ['status'=>200,'msg'=>current($bettingRecords->getErrors())];

        return $rst;
    }


    /**
     * @desc 某定位组合遗漏 例如： [8,9]
     * @return array
     */
    public static function dwHzZuHeYL($zuhe = [2,3],$hezhis = [8,9]){
        $field = 'code_'.implode('_',$zuhe);
        $where = [ $field => $hezhis];
        $id = SscKjData::find()->where($where)->orderBy('id DESC')->one()['id'];

        $maxId = SscKjData::find()->orderBy('id DESC')->one()['id'];
        $times = $maxId - $id;

        return $times;
    }

    /**
     * @desc 彩种
     * @param string $playway
     * @return array|mixed
     */
    public static function lotteryClass($playway = ''){

        $lotteries = [

            1 => '两字定', // code:25,36,X,X@4,X,4,X@3567,X,X,2357 # 前4位为开奖号码，海南玩法
            2 => '三字定', // code:578,368,359,X   # 前4位为开奖号码，海南玩法
            3 => '四字定', // code:478,4679,469,4678 # 前4位为开奖号码，海南玩法
            4 => '一字定', // code:24,237,125,2346
            5 => '二字现', // code:28@46@78@23
            6 => '三字现', // code:345@258
            10 => '定位胆', // code:0,,0,,0
            11 => '后二', // code:,,,256,246@,,,23,246
            12 => '后三', // code:,,13,1236,1256
            13 => '大小单双', // code:小双,小单双@单,单双
            14 => '前二', //  code:13,246,,,
            15 => '前三', // code:2478,2468,23678,,  或者：2478,2468,23678,,@458,457,48,,
            16 => '组三', // code:23568
            17 => '组六', // code:13579
        ];

        if(!$playway OR !$lotteries[$playway]) return $lotteries;

        return $lotteries[$playway];
    }
}