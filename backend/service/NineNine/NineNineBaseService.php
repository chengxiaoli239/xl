<?php

/**
 * Created by PhpStorm.
 *
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service\NineNine;
use backend\models\BettingRecords;
use backend\models\DataTime;
use backend\models\KjConfig;
use backend\models\Num4Type;
use backend\models\SscDsYl;
use backend\models\SscKjData;
use backend\models\SysPlansCodes;
use backend\models\SystemConfig;
use backend\models\TzSystems;
use backend\models\TzSystemsUsers;
use backend\models\User;
use backend\models\UserCustomPlans;
use backend\models\UserFollowData;
use backend\models\UserSysPlans;
use backend\service\BaseTZService;
use backend\service\BetService;
use backend\service\CurlService;
use backend\service\HN0898Service;
use backend\service\RemoteHtmlService;
use backend\service\SscDataService;
use backend\tools\Tools;
use common\models\AdminModel;
use common\service\CaptchaCodeService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class NineNineBaseService extends BaseTZService {
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
        /*
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
        */
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
    public static function getTzSiteInfo($tz_system_id, $lottery_type = DEFAULT_LOTTERY_TYPE, $url_key = ''){
        $TzSystemUser = TzSystemsUsers::findOne(['uid'=>self::$user_id, 'tz_system_id'=>$tz_system_id]);
        //p(['uid'=>self::$user_id, 'tz_system_id'=>$tz_system_id, $TzSystemUser->attributes]);
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
        $msg = ['status'=>200, 'msg'=>'金额同步成功~','tz_system_user_id'=>$tz_system_user_id, 'balance'=>$balance ];

        $TzSystemsUsers->balance = $balance;
        $TzSystemsUsers->updated_at = time();
        if(!$TzSystemsUsers->save()){
            $msg = ['status'=>300, 'msg'=>'金额同步失败12~'];
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
    public function bet($qihao, $plan_id, $code, $is_auto = 1){
        self::__init(self::$user_id, self::$tz_system_id);
        $plan = UserSysPlans::findOne($plan_id);
        $playway = $plan->playway ? $plan->playway : 3;
        $single = $plan->single ? $plan->single : 0.1;
        $tz_type = $plan->tz_type ? $plan->tz_type : 0;
        $buy_type = $plan->buy_type ? $plan->buy_type : 1;
        $lottery_type = $plan->lottery_type;
        $TzSystemsUsers = TzSystemsUsers::findOne(['tz_system_id'=>self::$tz_system_id, 'uid'=>$plan->uid]);
        //p(['playway'=>$playway, 'code'=>$code, 'single'=>$single, 'qihao'=>$qihao, 'user_id'=>self::$user_id]);
        if(!self::$user_id) return ['status'=>400,'msg'=>'账号为空，不能识别用户'];
        $data = ['status'=>200, 'msg'=>$qihao.'期投注成功!', 'time'=>date('Y-m-d H:i:s')];

        # 验证
        $rst = self::validateBettingContent($playway,$code);
        if($rst['status'] != 200){
            $data = ['status'=>300, 'msg'=>$qihao.$rst['msg']];
        }

        # 缓存锁
        $m = \Yii::$app->cache;
        $betKey = BetService::buildBetKey(self::$account, self::$tz_system_id, $lottery_type, $qihao, $plan_id);
        if($betLock = $m->get($betKey)) return ['status'=>303, 'msg'=>'已经投注过了', 'key'=>$betKey];
        $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间

        $plan_id_key = BetService::getBetCodesKey($TzSystemsUsers->uid, $code);
        $m->set($plan_id_key, $plan_id, $time);

        $data['code'] = $code;
        $betNums = self::getBetNumsPer();
        $codesArrs = self::splitCodes(explode('@', $code),  $betNums); # 2500一次
        $sn = '';
        $snid = '';
        foreach ($codesArrs as $key=>$codesArr){
            $betKey_i = $betKey.'_'.$key;
            if($fi = $m->get($betKey_i) && $is_auto){
                continue;
            }
            $post_data = [ 'act' => 'postsn', 'playway' => $playway, 'single' => $single, 'qihao' => $qihao, 'code' => implode('@', $codesArr)];

            $TzSiteInfo = self::getTzSiteInfo(self::$tz_system_id, $lottery_type);
            $url = $TzSiteInfo['ORDER_TZ'];
            $headers = [
                'Accept: */*',
                'Accept-Encoding: gunzip, deflate, br',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Connection: keep-alive',
                'Content-Length:'.strlen(http_build_query($post_data)),
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$TzSystemsUsers->cookie,
                "Host:".$TzSiteInfo['domain'],
                //'Origin: https://9912304.com',
                "Origin:".$TzSiteInfo['baseUrl'],
                //'Referer: https://9912304.com/jxssc_qmode/index.aspx',
                "Referer: ".$url,
                $TzSystemsUsers->user_agent,
                'X-Requested-With: XMLHttpRequest',
            ];

            # 和值投注反应时间比较久，无需返回直接锁住
            $m->set($betKey_i, 1, $time);

            # 真实投注
            $start_time = microtime(true);
            //p([$rst,$url, $post_data,http_build_query($post_data), $headers, $is_auto]);
            $rst[$key] = CurlService::postCurl($url, http_build_query($post_data), $headers)[0];
            $end_time = microtime(true);
            $time_consume = ($end_time - $start_time). 's';
            if($rst[$key]['err'] == -1 OR !$rst[$key]){
                $post_data['code'] = strlen($post_data['code'])>2000 ? substr($post_data['code'], 0, 200) : $post_data['code'];
                $tzRst = ['uid'=>self::$user_id, 'account'=>self::$account, 'status'=>301, 'msg'=>$qihao.$rst['msg'],'url'=>$url,'post_data'=>$post_data, 'user_id'=>self::$user_id, 'headers'=>$headers, 'postRst'=>$rst[$key], 'time_consume'=>$time_consume];
                if($tz_type != 20){
                    $tzRst['code'] = $code;
                }
                Tool_Common::log('bet_error','INFO','99投注记录-投注失败', $tzRst);
                return $tzRst;
                //continue;
            }

            $n = count(explode('@',$code));
            if(in_array($playway, [2, 3]) && $tz_type != 20){
                $totalmoney = SscDataService::calTzTotalMoney($code, $single, $playway);
            }else{
                $totalmoney = $n * $single; // 投注总金额 = 注数 * 倍数
            }

            $sn = $sn.','.$rst[$key]['sn'];
            $snid = $snid.','.NineNineBaseService::getSnidBySn($rst[$key]['sn'], $lottery_type); // 获取方案内容
        }

        $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间
        $m->set($betKey, 1, $time);

        $insertData = [
            'playway'=> $playway,  // 投注方式
            'tz_type'=> $tz_type,  // 投注类型
            'buy_type'=> $buy_type,  // 购买方向类型
            'uid'=> $plan->uid,  // 投注账号id
            'lottery_type' => $lottery_type, # 彩种
            'account' => $plan->account,
            'codes' => $code,  // 投注号码
            'qihao' => $qihao,  // 投注期号
            'plan_id' => $plan_id,  // 计划id
            'tz_system_id' => $TzSystemsUsers->tz_system_id,  // 投注系统tz_systems .id
            'sn'=>trim($sn, ','),
            'snid'=>trim($snid, ','),
            'order_type'=>3, # 单双三字定
            'is_simulate' => 0,  // 是否模拟投注
            'single' => $single,  // 投注倍数
            'betting_money'=> $totalmoney,  // 投注金额
        ];
        $insertRst = BetService::_logRecords($insertData);
        self::$headers = [];

        $post_data['code'] = strlen($post_data['code'])>2000 ? substr($post_data['code'], 0, 200) : $post_data['code'];
        $logArr = ['uid'=>$plan->uid,'account'=>$plan->account,'url'=>$url,'post_data'=>$post_data,'headers'=>$headers, 'postRst'=>$rst, 'time_consume'=>$time_consume,'insertData'=>$insertData,'sn'=>$sn, 'lottery_type'=>$lottery_type,'snid'=>$snid, 'insertRst'=>$insertRst];
        //p($logArr);
        Tool_Common::log('bet','INFO','99彩票网['.$lottery_type.']插入记录-真实投注-2', $logArr);

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
        $snids = explode(',', $BettingRecords->snid);
        self::__init($uid, $tz_system_id);
        $lot = $BettingRecords->lottery_type == 6 ? 'jxssc' : 'ssc';

        $rst = ['status'=>300, 'msg'=>'操作成功'];
        $TzSystemsUsers = TzSystemsUsers::findOne(['tz_system_id'=>self::$tz_system_id, 'uid'=>$uid]);
        $TzSiteInfo = NineNineBaseService::getTzSiteInfo($tz_system_id,$BettingRecords->lottery_type);
        $url = $TzSiteInfo['CANCEL_ORDER'];

        foreach ($snids as $key=>$snid) {
            //$url = NineNineBaseService::getTzSiteInfo($tz_system_id,'CANCEL_ORDER', $BettingRecords->lottery_type);
            $post_data = ['act' => 'cancelsn', 'lot' => $lot, 'snid' => $snid];
            $headers = [
                'Accept: */*',
                'Accept-Encoding: gunzip, deflate, br',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Connection: keep-alive',
                'Content-Length:' . strlen(http_build_query($post_data)),
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: ' . $TzSystemsUsers->cookie,
                "Host:" . $TzSiteInfo['domain'],
                //'Origin: https://9912304.com',
                "Origin:" . $TzSiteInfo['baseUrl'],
                //'Referer: https://9912304.com/jxssc_qmode/index.aspx',
                "Referer: " . $url,
                $TzSystemsUsers->user_agent,
                'X-Requested-With: XMLHttpRequest',
            ];

            $rstData = CurlService::postCurl($url, http_build_query($post_data), $headers);
            $rst[$key]['data'] = $rstData;
            if ($rstData == 'ok') {
                BettingRecords::updateAll(['cancel_status'=>1], ['snid' => $snid]);
                $rst[$key]['status'] = 200;
            }
        }
        $logArr = ['url'=>$url, 'snid'=>$snid,'headers'=>$headers,'post_data'=>$post_data, 'rst'=>$rst];
        Tool_Common::log('cancelOrder','INFO','撤单记录', $logArr);

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
     * @description 更新计划表状态
     * @param $id
     * @param $account
     * @return array
     */
    public static function updateSysPlansStatus($id, $status, $uid = '')
    {
        if(!$uid) return ['status'=>300, 'msg'=>'用户id为空'];
        $m = \Yii::$app->cache;
        $mkey = 'updateSysPlansStatus_'.$id.'_'.$status;
        if($rst = $m->get($mkey)) return false;

        $UserSysPlans = UserSysPlans::findOne(['uid' => $uid, 'id' => $id]);
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
    public static function updateKjConfigStatus($id, $status, $uid = '')
    {
        if(!$uid) return ['status'=>300, 'msg'=>'用户id为空'];
        $m = \Yii::$app->cache;
        $mkey = 'updateKjConfigStatus_'.$id.'_'.$status;
        if($rst = $m->get($mkey)) return false;

        $data = KjConfig::findOne($id);
        $data->enable = (int)$status;
        $data->updated_at = date('Y-m-d H:i:s');

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
     * @desc 立即反买
     * @param $account
     * @param $plan_id
     * @return array
     */
   public static function reverseTzNowBetRecord($uid, $BetRecordId){
       $BettingRecords = BettingRecords::findOne($BetRecordId);
       if(!$BettingRecords) return ['status'=>300, 'msg'=>'找不到投注计划记录'];

       $tz_system_id = $BettingRecords->tz_system_id ? $BettingRecords->tz_system_id : 2;  # 默认99网
       $HN0898Service = new self($uid, $tz_system_id);
       $oldCodes = $BettingRecords->codes;
       $oldCodesArr = explode('@', $oldCodes);
       $qihao = HN0898Service::getQihao();
       $playway = $BettingRecords->playway;

       $codes = '';
       $SysPlansCodes = SysPlansCodes::find()->where(['AND',['NOT IN','code', $oldCodesArr], ['=', 'playway', $playway]])->all();
       foreach ($SysPlansCodes as $sysPlansCode){
           $codes .= $sysPlansCode->code.'@';
       }
       $codes = trim($codes, '@');

       $m = \Yii::$app->cache;
       $mkey = 'reverseTzNowBetRecord_'.$qihao.'_'.$playway;
       if($r = $m->get($mkey)) return ['status'=>300, 'msg'=>'已经投注过了'];

       $account = AdminModel::findOne(Yii::$app->user->id)['account'];
       BetService::beforeBetNow($account, $BettingRecords->tz_system_id, $qihao, $BettingRecords->plan_id, $uid);
       $rst = $HN0898Service->bet($qihao, $BettingRecords->plan_id, $codes);
       BetService::afterBetNow($BettingRecords->lottery_type, $qihao, $uid);

       $m->set($mkey, 1, 5);

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
        $TzSiteInfo = self::getTzSiteInfo($tz_system_id );
        $url = $TzSiteInfo['GET_BALANCE'];
        if(strpos(strtolower($url), 'http') === false OR is_array($url)) return ['status'=>300, 'msg'=>'无效url'];
        $start_time = microtime(true);
        $balance = CurlService::postCurl($url,http_build_query($post_data), $headers);#
        //p([$url,$post_data,$headers, $balance]);
        $end_time = microtime(true);
        $time_consume = ($end_time-$start_time).'s';
        $TzSiteInfo = self::getTzSiteInfo($tz_system_id);
        $indexUrl = $TzSiteInfo['SSC_INDEX'];
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
            $content = self::getSscIndexContent(self::$user_id, self::$tz_system_id, $lottery_type);
            //p([self::$user_id, self::$tz_system_id, $lottery_type, $content]);

            $preg = "/<td>".$sn."(.*?) snid=(.*?)\>点击撤单/ism"; // 这里是表达式，大神看看
            preg_match_all($preg,$content,$matches);
            $snid = $matches[2][0];
            $m->set($mkey, $snid, 6*3600);
            Tool_Common::log('getSnidBySn','INFO','0898获取订单号2', ['snid'=>$snid]);
        }

        //p(['$matches'=>$matches[2], 'user_id'=>self::$user_id, 'tz_system_id'=>self::$tz_system_id, 'content'=>$content],0);

        return $snid;
    }

    /**
     * @desc 开奖是否存在
     * @param $qihao
     * @param int $lottery_type
     * @return bool
     */
    public static function isExist($qihao, $lottery_type = DEFAULT_LOTTERY_TYPE){

        $exist = SscKjData::find()->select(['id'])->where(['lottery_type'=>$lottery_type, 'qihao'=>$qihao])->asArray()->limit(1)->one();

        return $exist ? true : false;
    }

    /**
     * @desc 获取首页内容
     * @param $uid
     * @param $tz_system_id
     * @return mixed
     */
    public static function getSscIndexContent($uid, $tz_system_id, $lottery_type = DEFAULT_LOTTERY_TYPE){

        self::__init($uid, $tz_system_id);
        $TzSystemUser = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $TzSiteInfo = self::getTzSiteInfo($TzSystemUser->tz_system_id, $lottery_type);
        //p([$uid, $tz_system_id, $TzSystemUser, $TzSiteInfo]);
        $url = $TzSiteInfo['SSC_INDEX'];
        $headers = [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36",
            'Accept-Language:zh-CN,zh;q=0.9',
            'Connection:keep-alive',
            'Accept-Encoding: gunzip, deflate',
            'X-Requested-With: XMLHttpRequest',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
            'Cache-Control: max-age=0',
            //"Accept-Encoding: gunzip, deflate, br", # gunzip 防止乱码
            "Cookie:".$TzSystemUser->cookie."; VisitorCapacity=1;",
            //"Cookie:ASP.NET_SessionId=pc3w1umag0jyyw45dc2z2smk; VisitorCapacity=1;",
            "Host:".$TzSiteInfo['domain'],
            "Upgrade-Insecure-Requests: 1",
            "Referer: ".$url,
        ];
        //$headers = array_merge(self::$headers,$headers);

        //$content = RemoteHtmlService::getRemoteHtmlContent($url, $headers);
        $content = CurlService::getCurl($url, $headers);
        //p(['url'=>$url, 'cookie'=>$TzSystemUser->cookie, 'tz_system_id'=>$TzSystemUser->tz_system_id, 'headers'=>$headers, 'content'=>$content]);

        return $content;
    }

    /**
     * @desc 获取列表
     * @param $uid
     * @param $tz_system_id
     */
    public static function getTzList($uid, $tz_system_id, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $content = self::getSscIndexContent($uid, $tz_system_id, $lottery_type);

        if($lottery_type == 5){ # 重庆
            $preg = '/<tr>(.*?)<td>SSC(.*?)&nbsp;&nbsp;&nbsp;&nbsp;<a class="cancelsn" style="cursor:pointer;color:blue;" snid=(.*?)>点击撤单<\/a><\/td>(.*?)<td>(.*?)<\/td>(.*?)<td title="(.*?)" style="cursor:pointer;">(.*?)<a href="\.\.\/user\/sninfo\.aspx\?id=(.*?)" target\=\_blank>详细内容<\/a>(.*?)<\/td>(.*?)<td>(.*?)<\/td>(.*?)<td>(.*?)<\/td>/ism'; // 这里是表达式，大神看看
        }elseif($lottery_type == 6){ # 新疆
            $preg = '/<tr>(.*?)<td>JXSSC(.*?)&nbsp;&nbsp;&nbsp;&nbsp;<a class="cancelsn" style="cursor:pointer;color:blue;" snid=(.*?)>点击撤单<\/a><\/td>(.*?)<td>(.*?)<\/td>(.*?)<td title="(.*?)" style="cursor:pointer;">(.*?)<a href="\.\.\/user\/sninfo\.aspx\?id=(.*?)" target\=\_blank>详细内容<\/a>(.*?)<\/td>(.*?)<td>(.*?)<\/td>(.*?)<td>(.*?)<\/td>/ism'; // 这里是表达式，大神看看
        }

        preg_match_all($preg,$content,$matches);
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
     * @desc 获取和值投注记录，和值投注反应慢，异步获取
     * @param $uid
     * @param int $tz_system_id
     * @return array
     */
    public static function getRemoteHzRecords($uid = 0, $tz_system_id, $lottery_type = DEFAULT_LOTTERY_TYPE){

        $rst = ['status'=>200, 'msg'=>'投注记录抓取成功~'];
        //if($uid != 11) return false;
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        //p([$uid, $tz_system_id, $TzSystemsUsers]);

        $m = \Yii::$app->cache;
        sleep(2);
        $lists = self::getTzList($TzSystemsUsers->uid, $TzSystemsUsers->tz_system_id, $lottery_type);
        //p([$TzSystemsUsers->uid, $TzSystemsUsers->tz_system_id, $lottery_type, $lists]);
        $lists = array_reverse($lists);
        foreach ($lists as $key=>$list){
            if(strlen($list['codes']) < 150) continue; # 非和值记录无需抓取
            //$rand = rand(1,5); sleep($rand);

            $setData = [];
            if($BettingRecords = BettingRecords::findOne(['snid'=>$list['snid']])){
                //if($key == 1) p(['FLAG:'.$list['snid'], 'codes'=>$list['codes']]);
                if(strlen($BettingRecords->codes) != 32) continue;
            }else{
                $BettingRecords = new BettingRecords();
                $setData['create_time'] = date('Y-m-d H:i:s');
                $setData['createtime'] = time();
                $setData['created_at'] = time();
            }
            $plan_id_key = BetService::getBetCodesKey($uid, $list['codes']);
            $plan_id = $m->get($plan_id_key);
            $plan_id = $plan_id ? $plan_id : '';
            $cancel_status = ['正常'=>0, '已撤单'=>1];
            $uid = $TzSystemsUsers->uid;
            $betNums = SscDataService::getBetNums($list['codes']); //p($rst);
            $playway = SscDataService::getPlaywayByCodes($list['codes']);
            $single = $list['totalmoney'] / $betNums;
            $playwayName = [1=>'二字定', 2=>'三字定', 3=>'四字定'];

            $account = AdminModel::findOne($uid)->username;
            //if($uid == 11)p(['account'=>$account]);
            //$codesArr = explode('@', $list['codes']);
            $setData = array_merge($setData,[
                'sn' => $list['sn'],
                'snid' => $list['snid'],
                'codes' => $list['codes'],
                'qihao' => $list['qihao'],
                'account' => $account,
                'uid' => $uid,
                'plan_id' => $plan_id,
                'playway' => $playway,
                'tz_system_id' => $TzSystemsUsers->tz_system_id,
                'lottery_type' => $lottery_type,
                'tz_type' => $playway == 3 ? 21 : 16,
                'single' => $single,
                'playway_name' => $playwayName[$playway],
                'betting_money' => $list['totalmoney'],
                'is_simulate' => 0,
                'cancel_status' => $cancel_status[$list['status_txt']] == 1 ? 1 : 0,
                'lotteryclass' => 'ssc',
                'updated_at' => time(),
            ]);

            $BettingRecords->setAttributes($setData);
            //p($BettingRecords->attributes);
            if(!$flag = $BettingRecords->save()){
                p($BettingRecords->getErrors());
            }
        }

        return $rst;
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
        if($TzSystemsUsers->balance > 0) {
            return ['status'=>200, 'msg'=>'已经登录的状态'];
        }
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

        $TzSiteInfo = self::getTzSiteInfo($tz_system_id);
        $url = $TzSiteInfo['INDEX'];
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
        HN0898Service::synBalance($TzSystemsUsers->id); # 同步余额
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
     * @decription 获取即将开奖的期号
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return string
     */
    public static function getQihao($lottery_type = DEFAULT_LOTTERY_TYPE){
        $db = Yii::$app->db;
        //$date = date('Y-m-d');
        $time = date("H:i:s");
        $sql = "SELECT actionNo FROM {{%data_time}} WHERE actionTime >= '".$time."' AND type=$lottery_type ORDER BY id ASC";
        $rst = $db->createCommand($sql)->queryOne();
        switch ($lottery_type) {
            case 1: # 希腊1.5分彩
                //break;
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
                }elseif('00:00:00'<$time && $time<'02:00:00'){
                    $where = ['AND',['=','type', $lottery_type],['>=', 'actionTime', $time],['between', 'actionTime','00:00:00','02:00:00']];
                    $rst = DataTime::find($where)->where($where)->asArray()->one();
                    $date = '20'.date("ymd") - 1;
                    $qihao = $date.sprintf("%02d", $rst['actionNo']);
                }else{
                    $sql = "SELECT actionNo FROM {{%data_time}} WHERE actionTime >= '" . $time . "' AND type=$lottery_type ORDER BY id ASC";
                    $rst = $db->createCommand($sql)->queryOne();
                    $qihao = '20'.date("ymd").sprintf("%02d", $rst['actionNo']);
                }
                break;
            case 7: # 北京快乐8
                $qihao = 967767 + self::getdifferentdays() * 179 + self::getDifferentNums() + 1; # 967767为2019-08-10最后一期期号
                break;
        }

        return $qihao;
    }

    /**
     * @decription 获取当前时间已经开奖的期号
     * @param int $type
     * @return string
     */
    public static function getCurrentQihao($lottery_type = DEFAULT_LOTTERY_TYPE){
        $db = \Yii::$app->db;

        switch ($lottery_type){
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
                $days = self::getDifferentDays();
                $nums = self::getDifferentNums();
                $qihao = 967767 + $days * 179 + $nums; # 967767为2019-08-10最后一期期号
                break;
        }


        return $qihao;
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
     * @desc 北京快乐8 计算当前要开奖期号序号
     * @return float
     */
    public static function getDifferentNums(){
        $time = time();
        $date_time = date('H:i');
        $start_time = strtotime(date('Y-m-d').' 09:05');
        if('00:00'<$date_time && $date_time<'09:05') $time = $start_time;
        //p([$time, $start_time]);
        $nums = floor(($time - $start_time)/(5*60));

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
     * @desc 插入单双遗漏
     */
    public static function insertDsYl(){
        $SscDsYls = SscDsYl::find()->where(['lottery_type'=>2])->all();
        for ($i=1; $i<=6; $i++){
            foreach ($SscDsYls as $SscDsYl){
                $setData = $SscDsYl->attributes;
                $setData['lottery_type'] = $i;
                $where = ['lottery_type'=>$i, 'zhi'=>$setData['zhi'], 'positions'=>$setData['positions']];
                if(!$record = SscDsYl::findOne($where)){
                    $record = new SscDsYl();
                }
                $record->setAttributes($setData);
                $rst = $record->save();
            }
        }

        return $rst;
    }
}
