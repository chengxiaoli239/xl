<?php

/**
 * Created by PhpStorm.
 *   
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\BettingRecords;
use backend\models\DataTime;
use backend\models\KjConfig;
use backend\models\SscDsYl;
use backend\models\SscKjData;
use backend\models\SysPlansCodes;
use backend\models\TzSystemsUsers;
use backend\models\UserCustomPlans;
use backend\models\UserFollowData;
use backend\models\UserSysPlans;
use backend\service\clients\TzSystemUsersService;
use backend\service\NineNine\NineNineBaseService;
use backend\service\statics\statics_base\BaseDataService;
use backend\tools\Tools;
use common\kj\qxc\QxcTcw;
use common\models\AdminModel;
use common\service\jobs\plan\UserPlanBetJob;
use common\service\ssc\QihaoService;
use common\tools\Tool_Common;
use yii;

class HN0898Service extends BaseTZService {
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

    public static $headers = [];

    public static $typeTimes = [
        9 => ['m'=>5, 'max_qihao'=>203, 'start_H'=>8], # 台湾冰果，早start_H:8点开奖
        10 => ['m'=>1.5, 'max_qihao'=>639, 'start_H'=>8], # ，早start_H:8点开奖
        11 => ['m'=>3, 'max_qihao'=>319, 'start_H'=>8], # ，早start_H:8点开奖
        12 => ['m'=>5, 'max_qihao'=>191, 'start_H'=>8], # 待定，早start_H:8点开奖
        13 => ['m'=>10, 'max_qihao'=>95, 'start_H'=>8], # 待定，早start_H:8点开奖
        15 => ['m'=>5, 'max_qihao'=>203, 'start_H'=>8], # 一天最大期号 台湾欢乐生肖，早start_H:8点开奖
        18 => ['m'=>5, 'max_qihao'=>203, 'start_H'=>7], # 台湾快乐五，早start_H:7点开奖
    ];

    /**
     * HN0898Service constructor.
     * @param string $account
     * @param int $playway 投注方式
     * @param float $single 投注倍数 1:元 0.1:角
     * @param int $is_simulate   默认为模拟投注
     */
    public function __construct($uid = 1, $tz_system_id = 1){
        self::$headers = [
            'Accept:*/*',
            'Accept-Encoding:gunzip, deflate, br',
            'Content-Type:application/x-www-form-urlencoded',
            'X-Requested-With:XMLHttpRequest',
        ];
        self::__init($uid, $tz_system_id);
    }

    private static function __init($uid = 1, $tz_system_id = 2){
        //$User = User::findOne($uid);
        $User = AdminModel::findOne($uid);
        self::$user_id = $uid;
        self::$account = $User->username;
        self::$tz_system_id = $tz_system_id;
        self::$username = $User->username;
        self::$tzSiteInfo = HN0898Service::getTzSiteInfo($tz_system_id);
        //self::unitHeaders('Cookie'); # 去除重复的headers，主要是Cookie

        self::$baseUrl = self::$tzSiteInfo['baseUrl'];
        self::$domain = self::$tzSiteInfo['domain'];
        $headers = [
            "Cookie:".trim(self::$cookie),
            "Origin:".str_replace('www.','',self::$baseUrl),
            "Host:".str_replace('www.','',self::$domain),
            "Referer:".str_replace('www.','',Yii::$app->params['ajaxUrlRouteLotDw']),
        ];
        self::$headers = array_unique(array_merge(self::$headers,$headers));
    }

    /**
     * @desc 去除重复的headers
     * @param $headers
     * @param string $str
     * @return bool
     */
    public static function unitHeaders($str = 'Cookie'){
        foreach (self::$headers as $k=>$head){
            if (strstr($head, $str) !== false ){
                unset(self::$headers[$k]);
            }
        }
        return true;
    }

    /**
     * @desc 投注站点信息，未完
     * @param $uid
     * @param $tz_system_id
     */
    public static function getTzSiteInfo($tz_system_id, $url_key = '', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $TzSystemUser = TzSystemsUsers::findOne(['uid'=>self::$user_id, 'tz_system_id'=>$tz_system_id]);
        //p(['uid'=>self::$user_id, 'tz_system_id'=>$tz_system_id,$TzSystemUser->attributes]);
        $baseUrl = $TzSystemUser->ssc_domain;
        self::$cookie = $TzSystemUser->cookie;
        \Yii::$app->params['baseUrl']  = $TzSystemUser->ssc_domain;
        \Yii::$app->params['domain']  = str_replace('https://','',$TzSystemUser->ssc_domain);
        \Yii::$app->params['ajaxUrlRouteUser']  = $TzSystemUser->ssc_domain.'/user/ajax.aspx';
        \Yii::$app->params['sscUrlRoute']  = $TzSystemUser->ssc_domain.Yii::$app->params['sscUrlRoute_key'];
        $tzSiteInfo = [
            'baseUrl' => $TzSystemUser->ssc_domain,
            'domain' => \Yii::$app->params['domain'],
            'CANCEL_ORDER' => $baseUrl.'/api/data.aspx',
            //'ORDER_TZ' => str_replace('www.','',Yii::$app->params['ajaxUrlRouteLotDw']),
            //'SSC_INDEX' => $baseUrl.'/ssc/index.aspx',
            'INDEX' => $baseUrl.'/index.aspx',
            'GET_BALANCE' => $TzSystemUser->ssc_domain.'/user/ajax.aspx',
            'CAPTCHA_CODE' => $TzSystemUser->ssc_domain.'/code2.aspx',
        ];

        switch ($lottery_type){
            case 5: # 重庆
                $tzSiteInfo = array_merge($tzSiteInfo,[
                    'ORDER_TZ' => str_replace('www.','',$TzSystemUser->ssc_domain).'/ssc_qmode/ajax.aspx',
                    'SSC_INDEX' => $baseUrl.'/ssc/index.aspx',
                ]);
                break;
            case 6: # 新疆
                $tzSiteInfo = array_merge($tzSiteInfo,[
                    'ORDER_TZ' => str_replace('www.','',$TzSystemUser->ssc_domain).'/jxssc_qmode/ajax.aspx',
                    'SSC_INDEX' => $baseUrl.'/jxssc/index.aspx',
                ]);
                break;
        }
        if($url_key && $tzSiteInfo[$url_key]) return $tzSiteInfo[$url_key];

        return $tzSiteInfo;

    }

    /**
     * @decription 同步用户余额 by account
     * @param $tz_system_user_id 表lt_tz_systems_users.id
     * @return array
     */
    public static function synBalance($tz_system_user_id){
        $TzSystemsUsers = TzSystemsUsers::findOne($tz_system_user_id);
        $balance = self::getBalance($TzSystemsUsers->uid,$TzSystemsUsers->tz_system_id);
        //p($balance);
        $msg = ['status'=>200, 'msg'=>'金额同步成功~','tz_system_user_id'=>$tz_system_user_id, 'balance'=>$balance, 'account'=>$TzSystemsUsers->account, 'username'=>$TzSystemsUsers->username];

        $TzSystemsUsers->balance = $balance;
        $TzSystemsUsers->updated_at = time();
        if(!$TzSystemsUsers->save()){
            $msg = ['status'=>300, 'msg'=>'金额同步失败1~'];
        }

        return $msg;
    }


    /**
     * @desc 同步用户余额
     * @return mixed
    public static function synUsersBalance(){
        $users = User::findAll(['status'=>1]);
        foreach ($users as $key=>$user){
            $balance = self::getBalance($user->id);
            $user->balance = $balance;
            $rst = $user->save();
        }

        return $rst;
    }
     */

    /**
     * @decription 同步用户余额 by user_id
     * @param $user_id
     */
    public static function synBalanceByUserId($user_id){
        //self::synBalance($user_id);
    }

    /**
     * @decription 根据账号获取cookie
     * @param $account
     */
    public static function getCookieByAccount($account,$tz_system_id){
        //$user = User::find()->select(['cookie'])->where(['account'=>$account])->asArray()->one();
        $TzSystemsUsers = TzSystemsUsers::findOne(['account'=>$account,'tz_system_id'=>$tz_system_id]);

        $m = Yii::$app->cache;
        $mkey = '0898tz_cookie_'.$account;
        $m->set($mkey, $TzSystemsUsers->cookie,2*60*60);

        return $TzSystemsUsers->cookie;
    }

    /**
     * @description 还有未结单的用户不能继续投注
     * @param $account
     * @param $is_simulate 是否模拟
     * @return int
     */
    public static function isCanedTz($account, $plan_id, $is_simulate = 0){
        $isCaned = 1;
        if($rst = BettingRecords::findOne(['status'=>0, 'plan_id'=>$plan_id])){
            //$isCaned = 0; # 缓存控制开关，暂不依据投注记录表
        }
        $balance = AdminModel::findOne(['username'=>$account])->balance;
        if($is_simulate == 0 && $balance < 0.50){
            $isCaned = 0;
        }

        return $isCaned;
    }

    /**
     * @decription 投注接受方法
     * @param $data
     */
    public function tz($data){
        $qihao = $data['qihao'];
        $code = $data['code'];
        $is_simulate = $data['is_simulate'];
        $single = $data['single'];
        $playway = $data['playway'];
        $order_type = $data['order_type'];
        self::$position = $data['position'];

        //p(['account'=>self::$account,'playway'=>$playway, 'code'=>$code, 'single'=>$single, 'qihao'=>$qihao,'is_simulate'=>$is_simulate,'order_type'=>$order_type,'headers'=>self::$headers]);
        $rst = self::betting($playway, $code, $single, $qihao, $is_simulate, $order_type);

        return $rst;
    }

    /**
     * @decription 系统模拟投注
     *
     * @param $account
     * @param int $playway
     * @param $code
     * @param $single
     * @param $qihao
     * @param $is_simulate
     * @param $order_type 1、计划投注订单 2、大数据订单 3、定制化
     * @return array
     */
    public function betting($qihao, $plan_id, $codes){
        self::__init(1,1);
        $plan = UserSysPlans::findOne($plan_id);
        $playway = $plan->playway ? $plan->playway : 3;
        $single = $plan->single ? $plan->single : 0.1;
        $tz_type = $plan->tz_type ? $plan->tz_type : 0;
        $buy_type = $plan->buy_type ? $plan->buy_type : 1;
        $lottery_type = $plan->lottery_type;
        //p([$playway , $code, $single, $qihao]);
        $data = ['status'=>200, 'msg'=>$qihao.'期投注成功!', 'time'=>date('Y-m-d H:i:s')];

        # 验证
        $rst = self::validateBettingContent($playway,$codes);
        if($rst['status'] != 200){
            $data = ['status'=>300, 'msg'=>$qihao.$rst['msg']];
        }

        $post_data = [ 'act' => 'postsn', 'playway' => $playway, 'single' => $single, 'qihao' => $qihao, 'codes' => $codes, ];

        $data['code'] = $codes;
        $header = [
            'Content-Length:'.strlen(http_build_query($post_data)),
        ];

        $headers = array_merge(self::$headers,$header);
        //$url = self::getUserUrlArr(self::$user_id, 'ORDER_TZ');

        $n = count(explode('@',$codes));
        if(in_array($playway, [2, 3, 4])){ # 三、四定
            $totalmoney = SscDataService::calTzTotalMoney($codes, $single, $playway);
        }else{
            $totalmoney = $n * $single; // 投注总金额 = 注数 * 倍数
        }
        $snid = '88888888';
        $insertData = [
            'playway'=> $playway,  // 投注方式
            'codes' => $codes,  // 投注号码
            'qihao' => $qihao,  // 投注期号
            'snid'=>$snid,
            'tz_type' => $tz_type,
            'lottery_type' => $lottery_type, # 彩种
            'plan_id' => $plan_id,
            'buy_type' => $buy_type,
            'uid'=>0, # 系统模拟，uid=0
            'account'=>'admin', # 系统模拟，admin
            'order_type'=>0,
            'is_simulate' => 0,  // 是否模拟投注
            'single' => $single,  // 投注倍数
            'betting_money'=> $totalmoney,  // 投注金额
        ];

        # 缓存锁
        $m = \Yii::$app->cache;
        $betKey = BetService::buildBetKey('admin', self::$tz_system_id, $lottery_type, $qihao, $plan_id);
        if($betLock = $m->get($betKey)) return ['status'=>303, 'msg'=>'已经投注过了', 'key'=>$betKey];
        $insertRst = BetService::_logRecords($insertData);

        $time = \Yii::$app->params['TZ_LOCK_TIME'];
        $m->set($betKey, 1, $time);
        self::$headers = [];

        $logArr = ['post_data'=>$post_data,'headers'=>$headers, 'postRst'=>$rst,'insertData'=>$insertData, 'insertRst'=>$insertRst];
        Tool_Common::log('betting','INFO','0898模拟投注记录-插入记录', $logArr);

        return $data;
    }

    /**
     * @decription 新版投注，真实投注入口， 未完待续 2018.12.23
     *
     * @param $tz_system_id
     * @param $account
     * @param int $playway
     * @param $code
     * @param $single
     * @param $qihao
     * @param $tz_type
     * @param $buy_type
     * @param $order_type 1、跟投订单 2、大数据订单 3、系统计划订单
     * @return array
     */
    //public function bet($playway = 1, $code, $single, $qihao, $tz_type = 0, $buy_type = 1){
    public function bet($qihao, $plan_id, $code){
        $plan = UserSysPlans::findOne($plan_id);
        $playway = $plan->playway ? $plan->playway : 3;
        $single = $plan->single ? $plan->single : 0.1;
        $tz_type = $plan->tz_type ? $plan->tz_type : 0;
        $buy_type = $plan->buy_type ? $plan->buy_type : 1;
        $lottery_type = $plan->lottery_type;
        //$TzSystemsUsers = TzSystemsUsers::findOne(['tz_system_id'=>$tz_system_id, 'account'=>$account]);
        //p([$playway, $code, $single, $qihao]);
        //self::__init($account);
        if(!self::$user_id) return ['status'=>400,'msg'=>'账号为空，不能识别用户'];
        $data = ['status'=>200, 'msg'=>$qihao.'期投注成功!', 'time'=>date('Y-m-d H:i:s')];

        # 验证
        $rst = self::validateBettingContent($playway,$code);
        if($rst['status'] != 200){
            $data = ['status'=>300, 'msg'=>$qihao.$rst['msg']];
        }

        $post_data = [ 'act' => 'postsn', 'playway' => $playway, 'single' => $single, 'qihao' => $qihao, 'code' => $code, ];

        $data['code'] = $code;
        $header = [
            'Content-Length:'.strlen(http_build_query($post_data)),
        ];

        $headers = array_merge(self::$headers,$header);
        //p($headers);
        //$url = self::getUserUrlArr(self::$user_id, 'ORDER_TZ');
        $url = self::getTzSiteInfo(self::$tz_system_id, 'ORDER_TZ', $lottery_type);

        //$account = User::findOne(self::$user_id)->account;  # 投注用户账号
        //$account = User::findOne(['admin_id'=>self::$user_id])->account;  # 投注用户账号

        # 缓存锁
        $m = \Yii::$app->cache;
        $betKey = BetService::buildBetKey(self::$account, self::$tz_system_id, $lottery_type, $qihao, $plan_id);
        if($betLock = $m->get($betKey)) return ['status'=>303, 'msg'=>'已经投注过了', 'key'=>$betKey];

        $isBigNumsBet = BetService::isBigNumsBet($tz_type);
        if($isBigNumsBet){
            # 和值投注反应时间比较久，无需返回直接锁住
            $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间
            $m->set($betKey, 1, $time);
        }
        # 真实投注
        $start_time = microtime(true);
        $rst = CurlService::httpPost($url, http_build_query($post_data), $headers)[0];
        //p([$rst,$url,http_build_query($post_data), $headers],0);
        $end_time = microtime(true);
        $time_consume = ($end_time - $start_time). 's';
        if($rst['err'] == -1 OR !$rst){
            $tzRst = ['uid'=>self::$user_id, 'account'=>self::$account, 'status'=>301, 'msg'=>$qihao.$rst['msg'],'url'=>$url,'post_data'=>$post_data, 'user_id'=>self::$user_id, 'headers'=>$headers, 'postRst'=>$rst, 'time_consume'=>$time_consume];
            if($tz_type != 20){
                $tzRst['code'] = $code;
            }
            Tool_Common::log('bet','INFO','0898投注记录-投注失败', $tzRst);
            return $tzRst;
        }

        $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间
        $m->set($betKey, 1, $time);

        $n = count(explode('@',$code));
        if(in_array($playway, [2, 3]) && $tz_type != 20){
            $totalmoney = SscDataService::calTzTotalMoney($code, $single, $playway);
        }else{
            $totalmoney = $n * $single; // 投注总金额 = 注数 * 倍数
        }

        //$HN0898Service = new HN0898Service(self::$user_id, self::$tz_system_id);
        $snid = HN0898Service::getSnidBySn($rst['sn']); // 获取方案内容

        $insertData = [
            'playway'=> $playway,  // 投注方式
            'tz_type'=> $tz_type,  // 投注类型
            'buy_type'=> $buy_type,  // 购买方向类型
            'uid'=> self::$user_id,  // 投注账号id
            'lottery_type' => $lottery_type, # 彩种
            'account' => (self::$account == "gaozi2018") ? 'admin' : self::$account,
            'codes' => $code,  // 投注号码
            'qihao' => $qihao,  // 投注期号
            'plan_id' => $plan_id,  // 计划id
            'tz_system_id' => self::$tz_system_id,  // 投注系统tz_systems .id
            'sn'=>$rst['sn'],
            'snid'=>$snid,
            'order_type'=>3, # 单双三字定
            'is_simulate' => 0,  // 是否模拟投注
            'single' => $single,  // 投注倍数
            'betting_money'=> $totalmoney,  // 投注金额
        ];
        $insertRst = BetService::_logRecords($insertData);
        self::$headers = [];

        $logArr = ['uid'=>self::$user_id,'account'=>self::$account,'url'=>$url,'post_data'=>$post_data,'headers'=>$headers, 'postRst'=>$rst, 'time_consume'=>$time_consume,/*'insertData'=>$insertData,*/ 'insertRst'=>$insertRst];
        Tool_Common::log('bet','INFO','0898插入记录-真实投注', $logArr);

        return $data;
    }

    /**
     * @description  撤单 - 0898投注系列
     * @param $uid
     * @param $snid
     * @param $tz_system_id
     * @return mixed|string
     */
    public static function cancelOrder($id, $tz_system_id){
        $BettingRecords = BettingRecords::findOne($id);
        $uid = $BettingRecords->uid;
        $snid = $BettingRecords->snid;
        self::__init($uid, $tz_system_id);
        $lot = $BettingRecords->lottery_type == 6 ? 'jxssc' : 'ssc';

        $rst = ['status'=>300, 'msg'=>'操作成功'];
        //$url = HN0898Service::getUserUrlArr(self::$user_id,'CANCEL_ORDER');
        $url = NineNineBaseService::getTzSiteInfo($tz_system_id,'CANCEL_ORDER');
        $post_data = [ 'act' => 'cancelsn', 'lot' => $lot, 'snid'=> $snid ];
        $headers = self::$headers;

        $rstData = CurlService::httpPost($url,http_build_query($post_data), $headers);
        $rst['data'] = $rstData;
        if($rstData == 'ok'){
            $BettingRecords = BettingRecords::findOne(['snid'=>$snid]);
            $BettingRecords->cancel_status = 1;
            $BettingRecords->save();
            $rst['status'] = 200;
        }
        $logArr = ['snid'=>$snid,'headers'=>$headers,'post_data'=>$post_data, 'rst'=>$rst];
        Tool_Common::log('cancelOrder','INFO','撤单记录', $logArr);

        return $rst;
    }

    /**
     * @desc 手动下单
     * @param int $uid
     * @param int $playway
     * @param array $post_data
     * @param int $lottery_type
     */
    public static function postBet($uid = 2, $playway = 3, $single = 0.1, $codes = '', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $HN0898Service = new HN0898Service($uid, $tz_system_id = 2);
        self::__init($uid, $tz_system_id = 2);
        $qihao = HN0898Service::getQihao($lottery_type);
        $post_data = [ 'act' => 'postsn', 'playway' => $playway, 'single' => $single, 'qihao' => $qihao, 'code' => $codes, ];

        $TzSystemsUsers = TzSystemsUsers::findOne(['tz_system_id'=>$tz_system_id, 'uid'=>$uid]);
        //p($TzSystemsUsers);
        //$headers = array_merge(self::$headers, $header);
        $urls = self::getTzSiteInfo($tz_system_id = 2, '', $lottery_type);
        $url = $urls['ORDER_TZ'];

        $headers = [
            "Accept: */*",
            "Accept-Encoding: gunzip, deflate, br",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            'Content-Length: '.strlen(http_build_query($post_data)),
            "Cookie: ".$TzSystemsUsers->cookie,
            "Content-Type: application/x-www-form-urlencoded",
            $TzSystemsUsers->user_agent,
            "Host: ".$urls['domain'],
            "Origin: ".$urls['baseUrl'],
            "Referer:".$urls['ORDER_TZ'],
            "X-Requested-With: XMLHttpRequest"
        ];

        //p($logArr);

        $rst = CurlService::postCurl($url, http_build_query($post_data), $headers)[0];
        $logArr = ['url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers, 'rst'=>$rst];
        //p($logArr);

        return $rst;
    }

    /**
     * @description 更新计划表状态
     * @param $id
     * @param $account
     * @return array
     */
    public static function updateFollowDataStatus($id, $status, $account){

        $UserFollowData = UserFollowData::findOne(['account'=>$account,'id'=>$id]);
        $UserFollowData->status = (int)$status;

        $rst = $UserFollowData->save(false);
        if(!$rst) $logArr['msg'] = current($UserFollowData->getErrors());

        if(!$rst){
            return ['status'=>300, 'msg'=>current($UserFollowData->getErrors())];
        }
        if($UserFollowData->status == 1){
            $plan_id = $UserFollowData->id;
            $mkey_qihao = '0898tz_'.$account.'_'.$plan_id;
            $m = Yii::$app->cache;
            $istz = $m->get($mkey_qihao);
            if($istz) return ['status'=>300, 'msg'=>'当前计划已经投注过'];
            $rst = self::tzNow($account, $plan_id);

            $status = $m->set($mkey_qihao, true,10*60);
        }
        $logArr = ['attributes'=>$UserFollowData->attributes,'id'=>$id,'account'=>$account, 'rst'=>$rst, 'istz'=>$istz, 'status'=>$status];
        Tool_Common::log('/WORK/LOG/lottery_xl/updateFollowDataStatus','INFO','修改状态记录', $logArr);

        return ['status'=>200, 'msg'=>'状态更新成功~'];
   }

    /**
     * @description 更新定制化表状态
     * @param $id
     * @param $account
     * @return array
     */
   public static function updateCustomPlansStatus($id, $account){
        $UserCustomPlans = UserCustomPlans::findOne(['account'=>$account,'id'=>$id]);
        $UserCustomPlans->status = $UserCustomPlans->status==1 ? 0 : 1;

        $rst = $UserCustomPlans->save(false);
        if(!$rst){
            return ['status'=>300, 'msg'=>current($UserCustomPlans->getErrors())];
        }

        return ['status'=>200, 'msg'=>'状态更新成功~'];
    }

    /**
     * @description 更新定制化表状态
     * @param $id
     * @param $account
     * @return array
     */
    public static function updateStatus($id, $model='UserSysPlans', $field='status', $val=null){
        try {
            $M = $model::findOne($id);
            if(empty($M)){
                throw_info($id.'找不到记录:'.$model);
            }
            if($val !== null){
                $M->$field = (int)$val;
            }else{
                $M->$field = $M->$field ? 0 : 1;
            }

            $rst = $M->save(false);
            if(!$rst){
                return ['status'=>300, 'msg'=>current($M->getErrors())];
            }
            TzSystemUsersService::delTzSystemUserData();
        }catch (\Exception $e){
            return ['status'=>300, 'msg'=>$e->getMessage()];
        }

        return ['status'=>200, 'msg'=>'状态更新成功~', 'data'=>[]];
    }

    /**
     * @description 批量更新状态
     * @param $ids
     * @param string $model
     * @param string $field
     * @param null $val
     * @return array
     */
    public static function batchSwitchStatus($ids, string $model='UserSysPlans', string $field='status', $val=null, $admin_id=0): array
    {
        try {

            $userType = \Yii::$app->user->identity['user_type'];
            $user_id_field = 'user_id';
            if(strpos($model, 'UserSysPlans') !== false){
                $user_id_field = 'uid';
            }
            $transaction = \Yii::$app->db->beginTransaction();
            $table = $model::tablename();
            $sql = 'UPDATE '.$table.' SET '.$field.'='. $val .' WHERE id IN('.implode(',', $ids).')';
            if($userType != AdminModel::USER_TYPE_SUPER_ADMIN){
                $sql.= ' AND '.$user_id_field.'='.$admin_id;
            }

            $result = \Yii::$app->db->createCommand($sql)->execute();
            if(!$result){
                throw_info('批量更新失败');
            }

            TzSystemUsersService::delTzSystemUserData();
            $transaction->commit();
        }catch (\Exception $e){
            $transaction->rollBack();;
            throw_info($e->getMessage());
        }

        return ['status'=>200, 'msg'=>'状态更新成功~', 'data'=>[]];
    }

    /**
     * @description 更新计划表status状态
     * @param $id
     * @param $status
     * @param string $uid
     * @return array
     */
    public static function updateSysPlansStatus($id, $status, $uid = '') {
        $where = $id;
        if($uid != 1){
            $where = ['uid' => $uid, 'id' => $id];
        }
        $UserSysPlans = UserSysPlans::findOne($where);
        if(!$uid) return ['status'=>300, 'msg'=>'用户id为空', 'lottery_type'=>$UserSysPlans->lottery_type];
        $m = \Yii::$app->cache;
        $mkey = 'updateSysPlansStatus_'.$id.'_'.$status;
        if($rst = $m->get($mkey)) ['lottery_type'=>$UserSysPlans->lottery_type];

        $UserSysPlans->status = (int)$status;

        $plan_type = $UserSysPlans->plan_type;
        if(in_array($plan_type, [2,3,4,5,6,8,9,12,13,14,
            SscDataService::PLAN_TYPE_YL_ZZ_SINGLES_BET,
            SscDataService::PLAN_TYPE_YL_BET_SINGLES_NUM,
            SscDataService::PLAN_TYPE_YL_START_BET_SINGLES,
            SscDataService::PLAN_TYPE_ZZ_BET_SINGLES_2,
            SscDataService::PLAN_TYPE_LOSS_MONEY_BET_SINGLES,
        ])){ # 倍投
            $singles = explode('-', $UserSysPlans->singles);

            $code_hz = json_decode($UserSysPlans->hz_Arr, true);
            $code_hz['singles_key'] = 0;# 切换开关翻倍回第一次
            if($status && isset($code_hz['filters']['start_qihao'])){
                $code_hz['filters']['start_qihao'] = HN0898Service::getQihao($UserSysPlans->lottery_type); # 重新设置开始计算期号，避免无时间间隔的连续止损，大遗漏倍投问题
            }

            if(in_array($UserSysPlans->plan_type, [4, 5])){ # 切换
                $code_hz['status_val'] = 1;# 切换开关号码回第一组
            }
            if($code_hz['bet_while_miss']>0){ # 遗漏投，遗漏倍投
                $code_hz['current_miss'] = 0;
            }
            if(in_array($plan_type, [
                SscDataService::PLAN_TYPE_YL_BET_SINGLES_NUM,
                SscDataService::PLAN_TYPE_YL_START_BET_SINGLES,
            ])){
                $code_hz['betStatus'] = SscDataService::PLAN_BET_STATUS_INIT;
                $code_hz['current_miss'] = 0;
                $code_hz['has_bet_nums'] = 0;
            }
            if($plan_type == SscDataService::PLAN_TYPE_ZZ_BET_SINGLES_2){
                # 中则倍投2
                $code_hz['betStatus'] = SscDataService::PLAN_BET_STATUS_BETTING;
            }
            if($plan_type == SscDataService::PLAN_TYPE_LOSS_MONEY_BET_SINGLES){
                # 区间亏损起投
                $code_hz['current_area_profits'] = 0; # 当前区间利润
                $code_hz['betStatus'] = SscDataService::PLAN_BET_STATUS_INIT; # 重新计算
            }

            $UserSysPlans->single = !empty($singles[0])? $singles[0] : $UserSysPlans->single;
            if(in_array($plan_type, [12, 13])){
                $code_hz['A_x_B_y_status'] = 0;
                $code_hz['start_bet_yl_nums'] = -1;
                $code_hz['current_arise_A_times'] = 0;
                $code_hz['current_arise_B_times'] = 0;
                $code_hz['current_yl_desc'] = '';
                $code_hz['A_x_B_y_start_time'] = date('Y-m-d H:i:s');
            }
            if($plan_type == SscDataService::PLAN_TYPE_AREA_SINGLES_BET){ # 区间遗漏投
                $code_hz['areaBetStatus'] = 0; # 计划内部下注状态
                $code_hz['area_arise_qishus'] = 0; # 当前上奖期数
                unset($code_hz['current_area_profits']); # 当前区间利润
                unset($code_hz['start_qihao']);
            }
            $UserSysPlans->hz_Arr = json_encode($code_hz, 320);
        }

        $m->set($mkey, 1, 10);

        $rst = $UserSysPlans->save(false);
        if($status == 1){
            UserSysPlansService::enableAutoLoginForRealPlan($UserSysPlans);
            list($currentKjQiHao, $qiHao) = QihaoService::getKjQiHao($UserSysPlans->lottery_type); # 期号数据
            push_queue_fast(UserPlanBetJob::class, ['plan_id'=>$UserSysPlans->id, 'qiHao'=>$qiHao, 'business_id'=>$UserSysPlans->id]);
        }

        $rstData = ['rst'=>$rst, 'lottery_type'=>$UserSysPlans->lottery_type];

        return $rstData;
    }

    /**
     * @description 更新计划表状态
     * @param $id
     * @param $account
     * @return array
     */
    public static function updateKjConfigStatus($id, $status, $uid = '')
    {
        if(!$uid) return ['status'=>300, 'msg'=>'用户id为空'];
        $m = \Yii::$app->cache;
        $mkey = 'updateKjConfigStatus_'.$id.'_'.$status;
        if($rst = $m->get($mkey)) return false;

        $data = KjConfig::findOne($id);
        $data->enable = (int)$status;
        $data->updated_at = time();

        $m->set($mkey, 1, 10);

        $rst = $data->save(false);

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
     * @desc 立即投注
     * @param $account
     * @param $plan_id
     * @return array
     */
   public static function tzNowBetRecord($uid, $BetRecordId){
       $qihao = HN0898Service::getQihao();
       $BettingRecords = BettingRecords::findOne($BetRecordId);
       $playway = $BettingRecords->playway;
       if(!$BettingRecords) return ['status'=>300, 'msg'=>'找不到投注计划记录'];
       $tz_system_id = $BettingRecords->tz_system_id ? $BettingRecords->tz_system_id : 2;
       $HN0898Service = new HN0898Service($uid, $tz_system_id);
       $codes = $BettingRecords->codes;
       $single = $BettingRecords->single;

       $m = \Yii::$app->cache;
       $mkey = 'tzNowBetRecord_'.$uid.'_'.$qihao.'_'.$playway;
       if($r = $m->get($mkey)) return ['status'=>300, 'msg'=>'已经投注过了，请稍后'];

       //p([$qihao, $BettingRecords->plan_id, $codes]);
       BetService::beforeBetNow($BettingRecords->account, $BettingRecords->tz_system_id, $BettingRecords->lottery_type, $qihao, $BettingRecords->plan_id, $uid);
       $rst = $HN0898Service->bet($qihao, $BettingRecords->plan_id, $codes);
       BetService::afterBetNow($BettingRecords->lottery_type, $qihao, $uid);

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
            case 4: // 一字定 code:24,237,125,2346
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

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid,'tz_system_id'=>$tz_system_id]);
        $headers = [
            'Accept: */*',
            'Accept-Encoding: gunzip, deflate, br',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: 11',
            "Host:".str_replace('www.','',self::$domain),
            'Origin:'.$TzSystemsUsers->ssc_domain,
            'Cookie: '.$TzSystemsUsers->cookie,
            //'Cookie: ASP.NET_SessionId=k2m5jxavmfs3hm55qlllwz45; pageReferrInSession=https%3A//9912304.com/user/reg.aspx; firstEnterUrlInSession=https%3A//9912304.com/ssc/index.aspx; VisitorCapacity=1', # .$TzSystemsUsers->cookie,
            "Referer:".$TzSystemsUsers->ssc_domain.'/index.aspx',
            $TzSystemsUsers->user_agent,
            'X-Requested-With: XMLHttpRequest',
        ];
        $post_data = ['act'=>'balance'];
        //$headers = array_merge(self::$headers,$headers);

        //$url = self::getUserUrlArr(self::$user_id,'GET_BALANCE');
        $url = self::getTzSiteInfo($tz_system_id,'GET_BALANCE');
        if(strpos(strtolower($url), 'http') === false OR is_array($url)) return ['status'=>300, 'msg'=>'无效url'];
        $start_time = microtime(true);
        $balance = CurlService::postCurl($url,http_build_query($post_data), $headers);#
        //p([$url,$post_data,$headers, $balance]);
        $end_time = microtime(true);
        $time_consume = ($end_time-$start_time).'s';
        $indexUrl = self::getTzSiteInfo($tz_system_id,'SSC_INDEX');
        $logData = ['url'=>$url,'headers'=>$headers, 'balance'=>$balance, 'indexUrl'=>$indexUrl, 'time_consume'=>$time_consume];
        //p($logData);
        Tool_Common::log('getBalance','INFO','0898用户余额', $logData);
        //sleep(2);
        //$rst = CurlService::getCurl($indexUrl, $headers);
        self::$headers = [];
        //p($balance);

        return $balance;
    }

    /**
     * @decription 获取远程网页表单
     * @param string $cookie
     * @param string $vsid
     * @return mixed
     */
    public static function getRemoteHtmlContent($uid = 1,$tz_system_id = 1, $vsid = '292p133GRw48'){
        self::__init($uid, $tz_system_id);
        //$User = User::findOne(['account'=>$account]);
        $TzSystemUser = TzSystemsUsers::findOne(['uid'=>self::$user_id, 'tz_system_id'=>$tz_system_id]);
        $headers = [
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
            //"Accept-Encoding: gunzip,deflate, br", # gunzip 防止抓取页面内容返回乱码
            "Upgrade-Insecure-Requests: 1",
            "Origin:".self::$baseUrl,
            "Host:".self::$domain,
            //"Cookie:".$TzSystemUser->cookie.';vsid='.$vsid,
            "Cookie:".$TzSystemUser->cookie,
        ];
        $url = HN0898Service::getTzSiteInfo($tz_system_id, 'INDEX');
        $logArr = ['url'=>$url, 'headers'=>$headers, ];
        $htmlData = RemoteHtmlService::getRemoteHtmlContent($url, $headers);
        //p([$logArr,$htmlData]);

        $htmlData = str_replace('/code2.aspx',self::$baseUrl.'/code2.aspx', $htmlData); // 验证码链接

        return $htmlData;
    }

    /**
     * @desc 获取订单号
     * @param $sn 方案号
     * @return mixed
     */
    public static function getSnidBySn($sn, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'SNID_'.$lottery_type.'_'.$sn;
        if(!$snid = $m->get($mkey)){
            switch ($lottery_type){
                case 5:
                    $content = self::getSscIndexContent(self::$user_id, self::$tz_system_id, $lottery_type);

                    $preg = "/<td>".$sn."(.*?) snid=(.*?)\>点击撤单/ism"; // 这里是表达式，大神看看
                    preg_match_all($preg,$content,$matches);
                    $snid = $matches[2][0];
                    break;
                case 6:
                    break;
            }
            Tool_Common::log('getSnidBySn','INFO','0898获取订单号1', ['snid'=>$snid]);
            $m->set($mkey, 6*3600);
        }

        return $snid;
    }

    /**
     * @desc 获取首页内容
     * @param $uid
     * @param $tz_system_id
     * @return mixed
     */
    public static function getSscIndexContent($uid, $tz_system_id, $lottery_type = DEFAULT_LOTTERY_TYPE){

        $url = NineNineBaseService::getTzSiteInfo($tz_system_id,$lottery_type, 'SSC_INDEX');
        $headers = [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36",
            'Accept-Language:zh-CN,zh;q=0.9',
            'Connection:keep-alive',
            'Accept-Encoding: gunzip, deflate',
            'X-Requested-With: XMLHttpRequest',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
            'Cache-Control: max-age=0',
            //"Accept-Encoding: gunzip, deflate, br", # gunzip 防止乱码
            "Cookie:".self::$cookie."; VisitorCapacity=1;",
            //"Cookie:ASP.NET_SessionId=pc3w1umag0jyyw45dc2z2smk; VisitorCapacity=1;",
            "Host:".str_replace('www.','',self::$domain),
            "Upgrade-Insecure-Requests: 1",
            "Referer: ".$url,
        ];
        //$headers = array_merge(self::$headers,$headers);

        //$content = RemoteHtmlService::getRemoteHtmlContent($url, $headers);
        $content = CurlService::getCurl($url, $headers);
        //if($uid == 11) p(['url'=>$url, 'cookie'=>self::$cookie, 'headers'=>$headers, 'content'=>$content]);

        return $content;
    }

    /**
     * @desc 获取列表
     * @param $uid
     * @param $tz_system_id
     */
    public static function getTzList($uid, $tz_system_id){
        $content = self::getSscIndexContent($uid, $tz_system_id);
        $preg = '/<tr>(.*?)<td>SSC(.*?)&nbsp;&nbsp;&nbsp;&nbsp;<a class="cancelsn" style="cursor:pointer;color:blue;" snid=(.*?)>点击撤单<\/a><\/td>(.*?)<td>(.*?)<\/td>(.*?)<td title="(.*?)" style="cursor:pointer;">(.*?)<a href="\.\.\/user\/sninfo\.aspx\?id=(.*?)" target\=\_blank>详细内容<\/a>(.*?)<\/td>(.*?)<td>(.*?)<\/td>(.*?)<td>(.*?)<\/td>/ism'; // 这里是表达式，大神看看

        preg_match_all($preg,$content,$matches);
        //p($matches);
        unset($matches[0], $matches[1], $matches[4], $matches[6], $matches[8], $matches[9], $matches[10], $matches[11], $matches[13]);
        $matches = array_values($matches); # 0:方案号 1:记录id 2:期号 3:号码
        $datas = $matches;
        $tzRecords = [];
        foreach ($datas as $key=>$data){
            foreach ($data as $k=>$v){
                if($key == 0){ # 方案号
                    $tzRecords[$k]['sn'] = 'SSC'.$v;
                }elseif ($key == 1){ # 方案号
                    $tzRecords[$k]['snid'] = $v;
                }elseif ($key == 2){ # 期号
                    $tzRecords[$k]['qihao'] = $v;
                }elseif ($key == 3){ # 号码
                    $tzRecords[$k]['codes'] = $v;
                }elseif ($key == 4){ # 投注金额
                    $tzRecords[$k]['totalmoney'] = $v;
                }elseif ($key == 5){ # 状态
                    $tzRecords[$k]['status_txt'] = $v;
                }
            }
        }

        return $tzRecords;
    }

    /**
     * @decription 获取登录表单
     * @param $formData
     * @return array
     */
    private static function getLoginForm($formData){
        $form = $formData[0];
        $filterField = ['__VIEWSTATE','__EVENTVALIDATION','__VIEWSTATEGENERATOR','ctl00$txtUser', 'ctl00$txtPwd', 'ctl00$txtcode'];
        $inputs = [];
        foreach ($form['inputs'] as $key=>$item){
            $field = $item['name'];
            if(!in_array($field, $filterField)) continue;
            $value = $item['value'];

            $inputs[$field] = $value;
        }
        $inputs['ctl00$btnlogin.x'] = rand(10,99);
        $inputs['ctl00$btnlogin.y'] = rand(10,99);

        return ['action'=>$form['action'],'inputs'=>$inputs];
    }

    public static function getZjByInterval(){
        $times = 0;

        return $times;
    }

    /**
     * @description 获取cookie并写表lt_tz_systems_users，场景：未登录情况下
     * @param $uid
     * @param $tz_system_id
     * @return mixed
     */
    /**
     * @return mixed
     */
    public static function getCookie($uid,$tz_system_id){
        self::__init($uid, $tz_system_id);
        $m = \Yii::$app->cache;
        $mkey = 'UPDATE_COOKIE_TIME_'.$uid.'_'.$tz_system_id;
        if(!$cookie = $m->get($mkey)){
            //p(HN0898Service::getTzSiteInfo($tz_system_id));
            $url = HN0898Service::getTzSiteInfo($tz_system_id,'CAPTCHA_CODE');
            if(strpos(strtolower($url), 'http') === false OR is_array($url)) return ['status'=>300, 'msg'=>'无效url'];
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                'Accept-Encoding: gunzip, deflate, br',
                'Cache-Control: max-age=0',
                'Upgrade-Insecure-Requests: 1',
                'Host: '.HN0898Service::getTzSiteInfo($tz_system_id,'domain'),
                //'Referer:'.HN0898Service::getUserUrlArr($User->admin_id,'domain'),
            ];
            $cookie = CurlService::curl_get_cookie($url, $headers);
            $cookieData = $cookie;
            if($cookieData){
                $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
                $TzSystemsUsers->cookie = trim($cookieData);
                $TzSystemsUsers->cookie = str_replace('; path=/; HttpOnly','', $TzSystemsUsers->cookie);
                $TzSystemsUsers->save();
            }
            self::$headers = [];
            $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'cookie'=>$cookie, 'url'=>$url, 'headers'=>$headers];
            Tool_Common::log('getCookie','INFO','0898Cookie记录', $logArr);
            $cookie = str_replace(' ASP.NET_SessionId=','',$cookie);
            $cookie = str_replace('; path=/; HttpOnly','',$cookie);
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
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            //'Accept-Encoding: gunzip, deflate, br',
            'Cookie: '.$TzSystemsUsers->cookie,
            'Host: '.HN0898Service::getTzSiteInfo($TzSystemsUsers->tz_system_id,'domain'),
            'Upgrade-Insecure-Requests: 1',
        ];
        $url = HN0898Service::getTzSiteInfo($TzSystemsUsers->tz_system_id,'CAPTCHA_CODE');
        $imageData = CurlService::httpGet($url, $headers);
        $filename = Yii::$app->basePath . "/runtime/captcha/".$uid.'_'.$tz_system_id.'_'.$cookie_key.".png";
        //$filename = Yii::$app->basePath . "/runtime/captcha/".$cookie.".png";
        $tp = fopen($filename,"w");
        fwrite($tp, $imageData);
        fclose($tp);
        $logData = ['url'=>$url,'headers'=>$headers, 'filename'=>$filename];
        //p($logData);
        Tool_Common::log('downLoadCodeImg','INFO','下载图片验证码', $logData);

        return true;
    }

    public static function login($uid = 1, $tz_system_id = 1){
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        //if($TzSystemsUsers->balance > 0) {
        //    return ['status'=>200, 'msg'=>'已经登录的状态'];
        //}
        //self::__init($uid, $tz_system_id);
        $rst = false;

        # 第一步：获取cookie
        $cookie_key = HN0898Service::getCookie($uid,$tz_system_id);
        if(isset($cookie_key['status']) && $cookie_key['status'] == 300) return $cookie_key;
        # 第二步：下载验证码图片
        HN0898Service::downLoadCodeImg($uid, $tz_system_id, $cookie_key);
        # 第三步：调验证码接口获取验证码
        //$captchaCode = '888888'; $rst = self::loginRemote($uid, $tz_system_id,$captchaCode); p($rst);  # 测试
        $captchaCodeRst = Tools::getCaptchaCode($uid, $tz_system_id, $cookie_key); # 真实调用验证码接口，收费
        //$code = $captchaCode['result'];
        if($captchaCodeRst['status'] == 200){
            $code = $captchaCodeRst['code'];
            # 第四步：账号、验证码登录
            $rst = self::loginRemote($uid, $tz_system_id, $code);
        }

        $rst = HN0898Service::synBalance($TzSystemsUsers->id);

        return $rst;
    }

    /**
     * @desc 登陆
     * @param $uid
     * @param $tz_system_id
     * @param string $code
     * @return mixed|string
     */
    private static function loginRemote($uid, $tz_system_id, $code = '2251'){
        self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        $htmlData = self::getCodesAndFormData($uid, $tz_system_id);
        //p($htmlData);
        $post_data = $htmlData['htmlArr']['inputs'];
        $post_data['ctl00$txtUser'] = $TzSystemsUsers->account;
        $post_data['ctl00$txtPwd'] = $TzSystemsUsers->password;
        $post_data['ctl00$txtcode'] = $code;
        //p($post_data);

        $url = self::getTzSiteInfo($tz_system_id, 'INDEX');
        if(strpos(strtolower($url), 'http') === false OR is_array($url)) return ['status'=>300, 'msg'=>'无效url', 'url'=>$url];
        $post_data = http_build_query($post_data);
        $headers = [
            "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
            //"Accept-Encoding: gzip, deflate, br", # gunzip 防止乱码
            //"Accept-Encoding: gunzip, deflate, br", # gunzip 防止乱码
            "Cache-Control:max-age=0",
            "Upgrade-Insecure-Requests:1",
            "Content-Length:".strlen($post_data),
            "Content-Type: application/x-www-form-urlencoded",
            "Cookie: ".trim($TzSystemsUsers->cookie),
            "Origin:".str_replace('www.','',self::$baseUrl),
            "Host:".str_replace('www.','',self::$domain),
            "Referer:".$TzSystemsUsers->ssc_domain,
        ];
        //p(self::$headers);
        //$headers = array_unique(array_merge($headers,self::$headers));

        $data = CurlService::httpPost($url,$post_data, $headers);
        //sleep(10);
        //HN0898Service::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'code'=>$code, 'url'=>$url,'post_data'=>$post_data, 'headers'=>$headers,'data'=>$data];
        //p($logArr);
        Tool_Common::log('loginRemote','INFO','0898登陆记录', $logArr);
        return $data;
    }

    public static function getCookies($url = 'https://700161.com/code2.aspx'){
        $responseHeadersArr = self::getHeaders($url);
        foreach ($responseHeadersArr as $loop) {
            if(strpos($loop, "Set-Cookie") !== false){
                preg_match('/^Set-Cookie: (.*?);/m',$loop,$m);
                $cookies = trim(substr($loop, 11));
                p($cookies);
            }
        }

        //p($responseHeadersArr);

        //return $cookies;
    }

    /**
     * @decription 获取 - 即将开奖的期号
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return string
     */
    public static function getQihao($lottery_type = DEFAULT_LOTTERY_TYPE, $time='', $date=''){

        $time = $time ? :date("H:i:s");
        $date = $date ? str_replace('/', '-', $date) : '';
        $m = \Yii::$app->cache;
        $mkey = 'getQihao_'.$lottery_type.'_'.$time;
        #$qihao = $m->get($mkey);
        if(!empty($qihao)) return $qihao;

        $db = Yii::$app->db;
        //$date = date('Y-m-d');
        $sql = "SELECT actionNo FROM {{%data_time}} WHERE actionTime >= '".$time."' AND type=$lottery_type ORDER BY id ASC";
        $rst = $db->createCommand($sql)->queryOne();
        switch ($lottery_type) {
            case 1: # 七星彩
                //$data = QxcTcw::getTcwOne($returnType = 'json', $is_auto = 0);
                $qihao = QxcTcw::getNineNineQihao($lottery_type, $is_auto = 1) + 1;# 期号
                break;
            case 2: # 希腊3分彩
                //break;
            case 3: # 希腊5分彩
                //break;
            case 4: # 希腊10分彩
                if (!$rst) {
                    $rst['actionNo'] = 001; // 大于 23:56  -> null  设置为120期
                    $qihao = date("ymd", strtotime('+1 day')) . sprintf("%03d", $rst['actionNo']);
                } else {
                    $qihao = date("ymd") . sprintf("%03d", $rst['actionNo']);
                }
                break;
            case 5: # 5:重庆ssc
                if (!$rst && $lottery_type == 5) {
                    $rst['actionNo'] = 001; // 大于 23:56  -> null  设置为120期
                    $qihao = date("ymd", strtotime('+1 day')) . sprintf("%03d", $rst['actionNo']);
                } elseif ('03:10:00' < $time && $time < '07:30:00') {
                    $qihao = date("ymd") . sprintf("%03d", 10);
                } else {
                    $qihao = date("ymd") . sprintf("%03d", $rst['actionNo']);
                }
                break;
            case 6: # 新疆ssc
                if ('23:40:00'<$time && $time<'23:59:55'){
                    $rst['actionNo'] = 42;
                    $qihao = date("Ymd").sprintf("%02d", $rst['actionNo']);
                }elseif('00:00:00'<$time && $time<'02:00:00'){
                    $where = ['AND',['=','type', $lottery_type],['>=', 'actionTime', $time],['between', 'actionTime','00:00:00','02:00:00']];
                    $rst = DataTime::find($where)->where($where)->limit(1)->asArray()->one();
                    $date = date("Ymd", time() - 86400);
                    $qihao = $date.sprintf("%02d", $rst['actionNo']);
                }else{
                    $sql = "SELECT actionNo FROM {{%data_time}} WHERE actionTime >= '" . $time . "' AND type=$lottery_type ORDER BY id ASC";
                    $rst = $db->createCommand($sql)->queryOne();
                    $qihao = date("Ymd").sprintf("%02d", $rst['actionNo']);
                }
                break;
            case 7: # 北京快乐8
                $qihao = 947004 + self::getdifferentdays() * 179 + self::getDifferentNums() ; # 967767为2019-08-10最后一期期号
                break;
            case 8:  # 幸运五星彩
                if('23:55:00'<$time && $time<='23:59:59') {
                    $actionNo = '288';
                }elseif('00:00:00'<=$time && $time<'00:05:00'){
                    $actionNo = '001';
                }else{
                    $sql = "SELECT actionNo FROM {{%data_time}} WHERE actionTime >= '".$time."' AND type=$lottery_type ORDER BY id ASC LIMIT 1";
                    $rst = $db->createCommand($sql)->queryOne();

                    $actionNo = $rst['actionNo'];
                }

                $qihao = ($date? str_replace('-', '', $date): date("Ymd")).sprintf("%03d", $actionNo);
                break;
            case 9: # 台湾宾果
                $qihao = 109071659 + self::getTwDifferentDays() * 203 + self::getDifferentNums($lottery_type = 9) + 1; # 109060291为2020-10-23最后一期期号
                break;
            case 10: # 冰岛90s 早8点到凌晨3点
                $timsstamp = time();
                $today_time = strtotime(date('Y-m-d 00:00:00'));

                $nowTime = date("H:i:s");
                if('00:00:00'<$nowTime && $nowTime < '03:00:00') {
                    $max_actionNo = '639';
                    $now_timsstamp = $timsstamp - $today_time; # 早8点到凌晨3点 不开奖
                    $actionNo = (int)($now_timsstamp / 90) + 2 + $max_actionNo;
                    $qihao = date('Ymd', time() - 24 * 3600) . sprintf("%03d", $actionNo);
                }else{
                    $now_timsstamp = $timsstamp - $today_time - 8 * 3600; # 早8点到凌晨3点 不开奖

                    $actionNo = (int)($now_timsstamp / 90) + 1;
                    $qihao = ($date? str_replace('-', '', $date): date("Ymd")).sprintf("%03d", $actionNo);
                }
                break;
            case 11: # 冰岛3分 早8点到凌晨3点
            case 12: # 冰岛5分 早8点到凌晨3点
            case 13: # 冰岛10分 早8点到凌晨3点
            case 18: # 台湾快五 早7点到凌晨3点
                $timsstamp = time();
                $types = self::$typeTimes;

                $qujian_times =  ($types[$lottery_type]['m']*60);

                $today_time = strtotime(date('Y-m-d 00:00:00'));
                $nowTime = date("H:i:s");
                if('00:00:00'<$nowTime && $nowTime < '03:00:00') {
                    $max_actionNo = $types[$lottery_type]['max_qihao']; # 12点最大期号
                    $now_timsstamp = $timsstamp - $today_time; # 早8点到凌晨3点 不开奖
                    $actionNo = (int)($now_timsstamp / $qujian_times) + 2 + $max_actionNo;
                    $qihao = date('Ymd', time() - 24 * 3600) . sprintf("%03d", $actionNo);
                }else{
                    $now_timsstamp = $timsstamp - $today_time - $types[$lottery_type]['start_H'] * 3600; # 早8点到凌晨3点 不开奖

                    $actionNo = (int)($now_timsstamp / $qujian_times) + 1;
                    $qihao = date("Ymd").sprintf("%03d", $actionNo);
                }
                break;
            case 15: # 冰岛-欢乐生肖 早9点到凌晨2点
                //p([self::getHLDifferentDays(), self::getDifferentNums($lottery_type = 15)]);
                $qihao = 109071636 + self::getHLDifferentDays() * 203 + self::getDifferentNums($lottery_type = 15) + 1; # 109071636为2020-12-19最后一期期号
                break;
            case 16: # 加拿大
                $qihao = 2659612 + self::getDifferentNums($lottery_type = 16) + 1; # 2659612为2021-01-01
                break;
            case 17: # 排列五
            case 19: # 纳斯达克
            case 20: # 道琼斯
            case 21: # 上证指数
            case 22: # 深圳成指
            case 23: # 以太坊3分
            case 24: # 以太坊10分
            case 25: # 江苏七位数
                TzSystemUsersService::getActiveQihao($lottery_type, $next_qihao); # 即将开奖的期号
                $qihao = $next_qihao;
                break;
            case 26: # 福彩3D
            case 27: # 排列三
                $qihao = substr(QxcTcw::getNineNineQihao($lottery_type), 2)+1;# 期号
                $localQihao = SscKjData::find()->select(['qihao'=>'MAX(qihao)'])->where(['lottery_type'=>$lottery_type])->scalar() + 1;
                $qihao = max($qihao, $localQihao); # 取最大
                break;
            break;
        }

        if($lottery_type == 23 && substr($qihao, -3, 3) >= 480){
            $date = substr($qihao, 0, 4).'-'.substr($qihao, 4, 2).'-'.substr($qihao, 6, 2).' 00:00:00';
            $date = date('Ymd', strtotime($date) + 86400);
            $qihao = $date.'001';
        }elseif($lottery_type == 24 && substr($qihao, -3, 3) >= 144){
            $date = substr($qihao, 0, 4).'-'.substr($qihao, 4, 2).'-'.substr($qihao, 6, 2).' 00:00:00';
            $date = date('Ymd', strtotime($date) + 86400);
            $qihao = $date.'001';
        }
        if(!empty($qihao)){
            $m->set($mkey, $qihao, 5);
        }

        return $qihao;
    }

    /**
     * @decription 获取当前时间 - 已经开奖的期号
     * @param int $type
     * @return string
     */
    public static function getCurrentQihao($lottery_type = DEFAULT_LOTTERY_TYPE){
        $db = \Yii::$app->db;

        switch ($lottery_type){
            case 1: # 七星彩
                //$data = QxcTcw::getTcwOne($returnType = 'json', $is_auto = 2); # 体彩网
                $qihao = QxcTcw::getNineNineQihao($lottery_type, $is_auto=2); # 九九网 期号
                break;
            case 5:
                $time = date("H:i:s", time() - 20 * 60);
                $sql = "SELECT actionNo FROM {{%data_time}} WHERE actionTime >= '".$time."' AND type=$lottery_type ORDER BY id ASC LIMIT 1";
                $rst = $db->createCommand($sql)->queryOne();

                $actionNo = $rst['actionNo'];

                $qihao = date("ymd").sprintf("%03d", $actionNo);
                break;
            case 6:
                $nTime = date('Y-m-d H:i:s');
                $nowTime = strtotime($nTime);
                $time = date("H:i:s", $nowTime - 20 * 60);

                if('10:00:00'<=$time && $time<='23:40:00'){
                    $sql = "SELECT actionNo FROM {{%data_time}} WHERE actionTime >= '".$time."' AND type=$lottery_type ORDER BY id ASC LIMIT 1";
                    $rst = $db->createCommand($sql)->queryOne();
                    $actionNo = $rst['actionNo'];
                    $qihao = date("Ymd").sprintf("%02d", $actionNo);
                }else{
                    $time = date("H:i:s", $nowTime);
                    $sql = "SELECT actionNo FROM {{%data_time}} WHERE actionTime <= '".$time."' AND type=$lottery_type ORDER BY id DESC LIMIT 1";
                    $rst = $db->createCommand($sql)->queryOne();
                    $actionNo = $rst['actionNo'];
                    $qihao = date("Ymd", strtotime('-1days')).sprintf("%02d", $actionNo);
                }

                break;
            case 7: # 北京快乐8
                $qihao = 947006 + self::getdifferentdays() * 179 + self::getDifferentNums() - 2; # 967767为2019-08-10最后一期期号
                break;
            case 8: # 幸运五星彩
                $time = date("H:i:s", time()-5*60);
                $nowTime = date("H:i:s");
                if('00:00:00'<$nowTime && $nowTime < '00:05:00'){
                    $actionNo = '288';
                    $qihao = date('Ymd', time() - 20*60).sprintf("%03d", $actionNo);
                }else{
                    $sql = "SELECT actionNo FROM {{%data_time}} WHERE actionTime >= '".$time."' AND type=$lottery_type ORDER BY id ASC LIMIT 1";
                    $rst = $db->createCommand($sql)->queryOne();

                    $actionNo = $rst['actionNo'];
                    $qihao = date("Ymd").sprintf("%03d", $actionNo);
                }
                break;
            case 9: # 台湾宾果
                $qihao = 109071659 + self::getTwDifferentDays() * 203 + self::getDifferentNums($lottery_type = 9); # 109071659为2020-10-23最后一期期号
                break;
            case 10: # 冰岛90s 早8点到凌晨3点
                $timsstamp = time();
                $today_time = strtotime(date('Y-m-d 00:00:00'));

                $nowTime = date("H:i:s");
                if('00:00:00'<$nowTime && $nowTime < '03:00:00') {
                    $max_actionNo = '639';
                    $now_timsstamp = $timsstamp - $today_time; # 早8点到凌晨3点 不开奖
                    $actionNo = (int)($now_timsstamp / 90) + 1 + $max_actionNo;
                    $qihao = date('Ymd', time() - 24 * 3600) . sprintf("%03d", $actionNo);
                }else{
                    $now_timsstamp = $timsstamp - $today_time - 8 * 3600; # 早8点到凌晨3点 不开奖

                    $actionNo = (int)($now_timsstamp / 90);
                    $qihao = date("Ymd").sprintf("%03d", $actionNo);
                }
                break;
            case 11: # 冰岛3分 早8点到凌晨3点
            case 12: # 冰岛5分 早8点到凌晨3点
            case 13: # 冰岛10分 早8点到凌晨3点
            case 18: # 台湾快五 早7点到凌晨2点
                $types = self::$typeTimes;
                $qujian_times =  ($types[$lottery_type]['m']*60); # 区间时间，秒数
                $timsstamp = time();
                $today_time = strtotime(date('Y-m-d 00:00:00'));

                $nowTime = date("H:i:s");
                if('00:00:00'<$nowTime && $nowTime < '03:00:00') {
                    $max_actionNo = $types[$lottery_type]['max_qihao']; # 12点最大期号
                    $now_timsstamp = $timsstamp - $today_time; # 凌晨3点早8点到 不开奖
                    $actionNo = (int)($now_timsstamp / $qujian_times) + 1 + $max_actionNo;
                    $qihao = date('Ymd', time() - 24 * 3600) . sprintf("%03d", $actionNo);
                }else{
                    $now_timsstamp = $timsstamp - $today_time - $types[$lottery_type]['start_H'] * 3600; # 早start_H点到凌晨3点 不开奖

                    $actionNo = (int)($now_timsstamp / $qujian_times);
                    $qihao = date("Ymd").sprintf("%03d", $actionNo);
                }
                break;
            case 15: # 冰岛-欢乐生肖 早9点到凌晨2点
                $qihao = 109071636 + self::getHLDifferentDays() * 203 + self::getDifferentNums($lottery_type = 15); # 109071637为2020-12-18最后一期期号
                break;
            case 16: # 加拿大
                $qihao = 2659612 + self::getDifferentNums($lottery_type = 16); # 2659612为2021-01-01
                break;
            case 17: # 七星彩
            case 19: # 纳斯达克
            case 20: # 道琼斯
            case 21: # 上证指数
            case 22: # 深圳成指
            case 23: # 以太坊3分
            case 24: # 以太坊10分
            case 25: # 江苏七位数
                $qihao = QxcTcw::getNineNineQihao($lottery_type, $is_auto = 1);# 已经开奖的期号 九九网 期号
                break;
            case 26: # 福彩3D
            case 27: # 排列三
                $qihao = substr(QxcTcw::getNineNineQihao($lottery_type), 2);# 期号
                $localQihao = SscKjData::find()->select(['qihao'=>'MAX(qihao)'])->where(['lottery_type'=>$lottery_type])->scalar();
                $qihao = max($qihao, $localQihao); # 取最大
                break;
            case 28:
                $dateTime = date('Y-m-d H:i:s');
                $diffDate = '2024-03-17 22:18:40';
                $qihao = 51088277 + floor((strtotime($dateTime) - strtotime($diffDate))/300);
                break;
            default:
                $qihao = SscKjData::find()->select(['qihao'=>'MAX(qihao)'])->where(['lottery_type'=>$lottery_type])->scalar();
                break;
        }

        if(in_array($lottery_type, [23, 24])){
            # 取表最后一条记录的期号
            $SscKjData = SscKjData::find()->select(['qihao'])->where(['lottery_type'=>$lottery_type])->asArray()->orderBy(['id'=>SORT_DESC])->limit(1)->one();
            $endQihao = $SscKjData['qihao'];
            $qihao = max($endQihao, $qihao);
        }

        return $qihao;
    }

    /**
     * @desc 获取台湾欢乐生肖天数差
     * @param string $end_date
     * @return float|int
     */
    public static function getHLDifferentDays($end_date = ''){
        $start_date = '2020-12-18';
        if(!$end_date) $end_date = date('Y-m-d');

        $start = strtotime($start_date);
        $end = strtotime($end_date);

        $nums = ( $end - $start ) / (24 * 3600);

        return $nums - 1;
    }
    /**
     * @desc 计算当前日期距离2020-12-19天数 - 台湾欢乐生肖
     * @param integer $lottery_type
     * @return float|int
     */
    public static function getHLDifferentNums($lottery_type = DEFAULT_LOTTERY_TYPE){
        $time = time();
        $date_time = date('H:i');
        //$date_time = '07:09';
        if($lottery_type == 9){
            $start_time = strtotime(date('Y-m-d').' 07:05');
            if('00:00'<$date_time && $date_time<'07:05') $time = $start_time;
            $nums = floor(($time - $start_time)/(5*60));

        }else{
            $start_time = strtotime(date('Y-m-d').' 09:05');
            if('00:00'<$date_time && $date_time<'09:05') $time = $start_time;
            //p([$time, $start_time]);
            $nums = floor(($time - $start_time)/(5*60));
        }

        return $nums + 1;
    }

    /**
     * @desc 获取台湾宾果天数差
     * @param string $end_date
     * @return float|int
     */
    public static function getTwDifferentDays($end_date = ''){
        $start_date = '2020-12-18';
        if(!$end_date) $end_date = date('Y-m-d');

        $start = strtotime($start_date);
        $end = strtotime($end_date);

        $nums = ( $end - $start ) / (24 * 3600);

        return $nums - 1;
    }

    /**
     * @desc 计算当前日期距离2019-08-10天数 - 北京快乐8
     * @param string $end_date
     * @return float|int
     */
    public static function getDifferentDays($end_date = ''){
        $start_date = '2019-08-10';
        if(!$end_date) $end_date = date('Y-m-d');

        $start = strtotime($start_date);
        $end = strtotime($end_date);

        $nums = ( $end - $start ) / (24 * 3600);

        return $nums - 1;
    }

    /**
     * @desc 计算当前日期距离2019-08-10天数 - 北京快乐8
     * @param string $end_date
     * @return float|int
     */
    public static function getCanadaDifferentDays($end_date = ''){
        $start_date = '2021-01-01';
        if(!$end_date) $end_date = date('Y-m-d');

        $start = strtotime($start_date);
        $end = strtotime($end_date);

        $nums = ( $end - $start ) / (24 * 3600);

        return $nums;
    }

    /**
     * @desc 计算开奖期数
     * @return float
     */
    public static function getDifferentNums($lottery_type = DEFAULT_LOTTERY_TYPE){
        $time = time();
        $date_time = date('H:i');
        //$date_time = '07:09';
        if($lottery_type == 9) {
            $start_time = strtotime(date('Y-m-d') . ' 07:05');
            if ('00:00' < $date_time && $date_time < '07:05') $time = $start_time;
            $nums = floor(($time - $start_time) / (5 * 60));

        }elseif($lottery_type == 15){ # 台湾欢乐生肖
            $today_time = strtotime(date('Y-m-d 00:00:00'));
            $start_time = strtotime(date('Y-m-d').' 09:05');
            if($date_time < '02:00'){
                $start_time = $today_time;
                $nums = floor(($time - $start_time)/(5*60));
            }elseif('02:00'<$date_time && $date_time<'09:05'){
                $nums = 23;
            }elseif($date_time>'09:05'){
                $nums =  23 + floor(($time - $start_time)/(5*60));
            }
        }elseif($lottery_type == 16){ # 加拿大
            $start_time = strtotime('2021-01-01 13:42:00');
            if('20:00'<$date_time && $date_time<'21:00') $time = $start_time;
            $day_z = self::getCanadaDifferentDays();
            $nums = floor(($time - $start_time - $day_z*3600)/(3.5*60));
        }else{
            $start_time = strtotime(date('Y-m-d').' 09:05');
            if('00:00'<$date_time && $date_time<'09:05') $time = $start_time;
            //p([$time, $start_time]);
            $nums = floor(($time - $start_time)/(5*60));
        }

        return $nums + 1;
    }

    /**
     * @decription 根据url获取headers
     * @param $url
     * @return array|ø
     */
    public static function getHeaders($url){

        $responseHeaders = CurlService::httpGetResponseHeaders($url);
        $headArr = array_filter(explode("\r\n", $responseHeaders));

        return $headArr;
    }

    /**
     * @desc 获取远程表单值
     * @param $uid
     * @param $tz_system_id
     * @return array
     */
    public static function getCodesAndFormData($uid, $tz_system_id){
        $mkey = 'form_data_'.$uid.'_'.$tz_system_id;
        $formData = \Yii::$app->cache->get($mkey);
        if(!$formData){
            $htmlData = self::getRemoteHtmlContent($uid, $tz_system_id);
            $formData = FormDataService::get_page_form_data($htmlData);
            //p([$htmlData, $formData, $uid, $tz_system_id]);
            Yii::$app->cache->set($mkey, $formData, 120);
        }
        $htmlArr = self::getLoginForm($formData);

        $rst = ['codeUrl'=>self::$baseUrl."/code2.aspx",'htmlArr'=>$htmlArr];
        return $rst;
    }

    /**
     * @description 投注相关数据获取
     * @param $account
     * @return UserFollowData|array|null
     */
    public static function getTzData($account){
        $fields = ['id', 'account', 'code', 'status', 'playway', 'single'];
        $tzData = UserFollowData::find()->select($fields)->where(['status'=>1,'account'=>$account])->limit(1)->asArray()->one();

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
            'plan_id' => $data['plan_id'],  // 计划id
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
        $id = SscKjData::find()->where($where)->orderBy('id DESC')->limit(1)->one()['id'];

        $maxId = SscKjData::find()->orderBy('id DESC')->limit(1)->one()['id'];
        $times = $maxId - $id;

        return $times;
    }

    /**
     * 获取当前为当年第几天
     * @return false|float|int
     */
    public static function getDateNum(){
        $now = time(); //获取当前时间戳
        $start = strtotime(date('Y-01-01',$now).'00:00:08'); //获取当年的第一天的时间戳
        $diff = $now - $start; //计算时间差
        $day = ceil($diff / 86400) + 1; //计算相差多少天并加1，即为今年的第几天

        return $day;
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

    /**
     * @desc 插入单双遗漏 - 新彩种统计插入 单双记录 - 初始化新彩种
     */
    public static function insertDsYl($lottery_type = DEFAULT_LOTTERY_TYPE){
        $SscDsYls = SscDsYl::find()->where(['lottery_type'=>5])->all();
        //for ($i=1; $i<=6; $i++){
            foreach ($SscDsYls as $SscDsYl){
                $setData = $SscDsYl->attributes;
                $setData['yl_records'] = '';
                $setData['lottery_type'] = $lottery_type;
                $where = ['lottery_type'=>$lottery_type, 'zhi'=>$setData['zhi'], 'positions'=>$setData['positions']];
                if(!$record = SscDsYl::findOne($where)){
                    $record = new SscDsYl();
                }
                $record->setAttributes($setData);
                $rst = $record->save();
            }
        //}

        return ['status'=>200, 'rst'=>$rst];
    }

    /**
     * @desc 开启本期下注状态
     * @param int $lottery_type
     * @return array
     */
    public static function openBetStatus($lottery_type=DEFAULT_LOTTERY_TYPE){
        $rst = ['status'=>200, 'msg'=>'操作成功lottery_type['.$lottery_type.']'];

        $m = Yii::$app->cache;
        $qihao = HN0898Service::getQihao($lottery_type);
        $mkey = TzService::buildNextKey($lottery_type, $qihao);
        $betCacheTime = BetService::getBetCacheTime($lottery_type);
        $r = $m->set($mkey, 1, $betCacheTime);
        $rst['rst'] = $r;

        return $rst;
    }

    /**
     * @desc 初始化彩种单双数据
     * @param int $lottery_type
     * @return array
     */
    public static function initDsDatas($lottery_type = DEFAULT_LOTTERY_TYPE){
        //$rst = ['status'=>200, 'msg'=>'操作成功lottery_type['.$lottery_type.']'];

        $rst = BaseDataService::insertDsTypeDatas($lottery_type);

        return $rst;
    }
}
