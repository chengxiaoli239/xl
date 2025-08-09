<?php

/**
 * Created by PhpStorm.
 *   
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service\BingDao;
use backend\models\BettingRecords;
use backend\models\SscKjData;
use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\models\User;
use backend\models\UserCustomPlans;
use backend\models\UserFollowData;
use backend\models\UserSysPlans;
use backend\service\BaseService;
use backend\service\BaseTZService;
use backend\service\BetService;
use backend\service\CurlService;
use backend\service\HN0898Service;
use backend\service\Juhua\JuHuaBaseService;
use backend\service\plans\BetErrorPlansTaskService;
use backend\service\PoxyIPService;
use backend\service\SscDataService;
use backend\tools\Tools;
use common\models\AdminModel;
use common\service\CaptchaCodeService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class BingDaoService extends BaseTZService { # 冰岛时时彩登陆体系
    public static $baseUrl =  '';
    public static $domain =  '';
    public static $cookie = '';
    public static $tzSiteInfo = [];
    public static $tz_system_id = ''; # 投注系统id
    public static $user_agent = '';
    public static $username = '';
    public static $user_id;
    public static $account = '';
    public static $l_types = [ # 网盘对本系统的彩种类型id
        5 => 6, # 新疆时时彩
        6 => 10, # 90s
        7 => 11, # 3m
        8 => 12, # 5m
        9 => 13, # 10m
        10 => 9, # 台湾冰果
        11 => 15, # 台湾欢乐生肖
    ];
    public static $ll_types = [ # 本系统对网盘的彩种类型id
        6 => 5, # 新疆
        10 => 6, # 90s
        11 => 7, # 3m
        12 => 8, # 5m
        13 => 9, # 10m
        9 => 10, # 台湾冰果
        15 => 11, # 台湾欢乐生肖
    ];

    public static $headers = [];

    /**
     * HN0898Service constructor.
     * @param string $account
     * @param int $playway 投注方式
     * @param float $single 投注倍数 1:元 0.1:角
     * @param int $is_simulate 默认为模拟投注
     */
    public function __construct($uid = 1, $tz_system_id = 1){

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
    public static function getTzSiteInfo($tz_system_id, $url_key = ''){
        $TzSystemUser = TzSystemsUsers::findOne(['uid'=>self::$user_id, 'tz_system_id'=>$tz_system_id]);
        //p(['uid'=>self::$user_id, 'tz_system_id'=>$tz_system_id,$TzSystemUser->attributes]);
        $baseUrl = $TzSystemUser->ssc_domain;
        self::$cookie = $TzSystemUser->cookie;
        \Yii::$app->params['baseUrl']  = $TzSystemUser->ssc_domain;
        \Yii::$app->params['domain']  = str_replace('https://','',$TzSystemUser->ssc_domain);
        $tzSiteInfo = [
            'baseUrl' => $TzSystemUser->ssc_domain,
            'CANCEL_ORDER' => $baseUrl.'/member/?a=member.bet&m=cancelBet',
            'ORDER_TZ' => $baseUrl.'/Member/BatchBet',
            'SSC_INDEX' => $baseUrl,
            'domain' => \Yii::$app->params['domain'],
            'MULBET_URL' => $baseUrl.'/Member/MultipleBet', # 下注接口
            'GetPeriodsQuery' => $baseUrl.'/api/Periods/GetPeriodsQuery',
            'INDEX' => $baseUrl.'/index.aspx',
            'GET_BALANCE' => $baseUrl.'/user/ajax.aspx',
            'CAPTCHA_CODE' => $TzSystemUser->ssc_domain.'/code2.aspx',
        ];
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
        $balance = self::getBalance($TzSystemsUsers->uid, $TzSystemsUsers->tz_system_id);
        $msg = ['status'=>200, 'msg'=>'金额同步成功~','tz_system_user_id'=>$tz_system_user_id, 'balance'=>$balance ];

        $TzSystemsUsers->balance = $balance;
        $TzSystemsUsers->updated_at = time();
        if(!$TzSystemsUsers->save()){
            $msg = ['status'=>300, 'msg'=>'金额同步失败5~'];
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
     * @decription 根据账号获取cookie
     * @param $account
     */
    public static function getCookieByAccount($account, $tz_system_id){
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
        $balance = User::findOne(['account'=>$account])->balance;
        if($is_simulate == 0 && $balance < 0.50){
            $isCaned = 0;
        }

        return $isCaned;
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
    public function bet($qihao, $plan, $codes) {
        return [];
    }
    /**
     * @desc 根据playway 2二定3四定 获取投注方式
     * @param int $tz_type
     * @return array|mixed
     */
    public static function getWayId($playway = ''){
        $rstData = [
            4 => 1,
            3 => 3, # 四字定
            2 => 3, # 三字定 1快选 3导入
        ];

        if(!empty($playway) && isset($rstData[$playway])) return $rstData[$playway];

        return $rstData;
    }

    /**
     * @desc 根据playway、tz_type 获取投注方式
     * @param int $tz_type
     * @return array|mixed
     */
    public static function getWay($tz_type = 20){
        $rstData = [
            20 => 102,
            27 => 104,
            29 => 102,
            30 => 102,
        ];

        if(isset($rstData[$tz_type])) return $rstData[$tz_type];

        return 108;
        //return $rstData;
    }

    /**
     * @desc 根据playway、tz_type 获取投注方式
     * @param int $tz_type
     * @return array|mixed
     */
    public static function getNumberType($tz_type = 20){
        $rstData = [
            27 => 20,
            30 => 20,
            29 => 30,
        ];

        if(isset($rstData[$tz_type])) return $rstData[$tz_type];

        return 40;
        //return $rstData;
    }

    /**
     * @desc 返回投注日
     * @return string
     */
    public static function getBetLog($tz_type = 20){
        if(in_array($tz_type,[ 27, 30])) { # 二定
            $str = '[二定位]，定位置“[取]”：千=[1]，百=[34]';
        }elseif(in_array($tz_type,[ 29])){
            $str = '[三定位]，定位置“[取]”：千=[13579]，固定合分取值：第[3]位选中，第[4]位选中，内容：[13579]；';
        }else{ # 四定
            $str = '[四定位]，合分值范围：[0-36]';
        }

        return $str;
    }

    /**
     * @desc 返回和值投注
     * @return string
     */
    public static function getOperationCondition($tz_type = 20){
        if($tz_type == 27) {
            $json = '{"symbol":"X","isXian":0,"firstNumber":"1","secondNumber":"34","thirdNumber":"","fourthNumber":"","fifthNumber":"","numberType":20,"positionType":0,"positionFilter":0,"remainFixedFilter":0,"remainFixedNumbers":[],"remainMatchFilter":0,"remainMatchNumbers":[],"remainValueRanges":[],"transformNumbers":[],"upperNumbers":[],"exceptNumbers":[],"fixedPositions":[],"symbolPositions":[0,0,0,0],"containFilter":0,"containNumbers":[],"multipleFilter":0,"multipleNumbers":[],"repeatTwoWordsFilter":-1,"repeatThreeWordsFilter":-1,"repeatFourWordsFilter":-1,"repeatDoubleWordsFilter":-1,"twoBrotherFilter":-1,"threeBrotherFilter":-1,"fourBrotherFilter":-1,"logarithmNumberFilter":-1,"logarithmNumbers":[],"oddNumberFilter":-1,"oddNumberPositions":[0,0,0,0],"evenNumberFilter":-1,"evenNumberPositions":[0,0,0,0]}';
        }elseif ($tz_type == 29){
            $json = '{"symbol":"X","isXian":0,"firstNumber":"13579","secondNumber":"","thirdNumber":"","fourthNumber":"","fifthNumber":"","numberType":30,"positionType":0,"positionFilter":0,"remainFixedFilter":0,"remainFixedNumbers":[[[0,0,1,1],[1,3,5,7,9]]],"remainMatchFilter":0,"remainMatchNumbers":[],"remainValueRanges":[],"transformNumbers":[],"upperNumbers":[],"exceptNumbers":[],"fixedPositions":[],"symbolPositions":[0,0,0,0],"containFilter":0,"containNumbers":[],"multipleFilter":0,"multipleNumbers":[],"repeatTwoWordsFilter":-1,"repeatThreeWordsFilter":-1,"repeatFourWordsFilter":-1,"repeatDoubleWordsFilter":-1,"twoBrotherFilter":-1,"threeBrotherFilter":-1,"fourBrotherFilter":-1,"logarithmNumberFilter":-1,"logarithmNumbers":[],"oddNumberFilter":-1,"oddNumberPositions":[0,0,0,0],"evenNumberFilter":-1,"evenNumberPositions":[0,0,0,0]}';
        }else{
            $json = '{"symbol":"X","isXian":0,"firstNumber":"","secondNumber":"","thirdNumber":"","fourthNumber":"","fifthNumber":"","numberType":40,"positionType":0,"positionFilter":0,"remainFixedFilter":0,"remainFixedNumbers":[],"remainMatchFilter":0,"remainMatchNumbers":[],"remainValueRanges":[30,35],"transformNumbers":[],"upperNumbers":[],"exceptNumbers":[],"fixedPositions":[0,0,0,0],"symbolPositions":[],"containFilter":0,"containNumbers":[],"multipleFilter":0,"multipleNumbers":[],"repeatTwoWordsFilter":-1,"repeatThreeWordsFilter":-1,"repeatFourWordsFilter":-1,"repeatDoubleWordsFilter":-1,"twoBrotherFilter":-1,"threeBrotherFilter":-1,"fourBrotherFilter":-1,"logarithmNumberFilter":-1,"logarithmNumbers":[],"oddNumberFilter":-1,"oddNumberPositions":[0,0,0,0],"evenNumberFilter":-1,"evenNumberPositions":[0,0,0,0]}';
        }

        return $json;
    }

    /**
     * @desc 根据彩种和不同投注方式 获取header type 跟下面的方法貌似一
     * @param int $lottery_type
     * @param string $playway
     * @return array|mixed
     */
    public static function getType($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rstData = [
            7 => 18, # 北京快乐8
            5 => 2, # 重庆时时彩
        ];

        if(!empty($lottery_type) && isset($rstData[$lottery_type])) return $rstData[$lottery_type];

        return $rstData;
    }

    /**
     * @desc 根据彩种和不同投注方式 获取header type
     * @param int $lottery_type
     * @param string $playway
     * @return array|mixed
     */
    public static function getLotteryId($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rstData = [
            7 => 18, # 北京快乐8
            5 => 2, # 重庆时时彩
        ];

        if(!empty($lottery_type) && isset($rstData[$lottery_type])) return $rstData[$lottery_type];

        return $rstData;
    }

    /**
     * @desc 获取本站号码存储格式
     * @param $codes
     * @param $playway
     * @return array
     */
    public static function getMySiteCodesStyle($codes, $playway){
        /*
        $codes = [
            ['bet_no'=>'15', 'dict_no_type_id'=>4],
            ['bet_no'=>'178', 'dict_no_type_id'=>9],
            ['bet_no'=>'609', 'dict_no_type_id'=>10],
        ];
        */

        $codesArr = [];

        foreach ($codes as $data){

            $dict_no_type_id = $data['dict_no_type_id'];
            $code = $data['bet_no'];
            $positions = self::getPositionByTypeId($dict_no_type_id);

            $tmp = [];
            $i = 0;
            foreach ($positions as $key=>$position){
                if($position == 'X'){
                    $tmp[$key] = 'X';
                }else{
                    $tmp[$key] = $code[$i];
                    $i++;
                }
            }
            //p(['code'=>$code, 'positions'=>$positions, 'dict_no_type_id'=>$dict_no_type_id, 'codes'=>$tmp]);
            $codesArr[] = implode(',',$tmp);
        }

        return $codesArr;
    }

    /**
     * @desc 获取批投号码格式
     * @param $codesData
     * @param float $single
     * @param integer $playway 1二定2三定3四定
     * @return array
     */
    public static function getBetCodes($codesData, $single = 0.1, $playway = 1){
        $codes = [];
        $codesData = str_replace(',','',$codesData);
        $codesData = str_replace('@',',',$codesData);

        if($playway == 1){ # 二定
            $codesArr = explode(',', $codesData);
            foreach ($codesArr as $code){
                $dict_no_type_id = self::getdict_no_type_id($code, $playway);
                $codes[] = ['dict_no_type_id'=>$dict_no_type_id, 'bet_no'=>$code, 'bet_money'=>$single];
            }
        }elseif ($playway == 2){
            $codesArr = explode(',', $codesData);
            foreach ($codesArr as $code){
                $dict_no_type_id = self::getdict_no_type_id($code, $playway);
                $codes[] = ['dict_no_type_id'=>$dict_no_type_id, 'bet_no'=>$code, 'bet_money'=>$single];
            }

        }
        //p([$codesData, $single, $playway, $codes]);

        return $codes;
    }

    /**
     * @desc 获取号码类型
     * @param $code
     * @param int $playway
     */
    public static function getdict_no_type_id($code, $playway = 1){
        $dict_no_type_id = 1;
        $code1 = $code[0];
        $code2 = $code[1];
        $code3 = $code[2];
        $code4 = $code[3];
        if($playway == 1){
            if($code3 == 'X' && $code4 == 'X'){
                $dict_no_type_id = 1; # 千百
            }elseif($code2 == 'X' && $code4 == 'X'){
                $dict_no_type_id = 2; # 千十
            }elseif($code2 == 'X' && $code3 == 'X'){
                $dict_no_type_id = 3; # 千个
            }elseif($code1 == 'X' && $code4 == 'X'){
                $dict_no_type_id = 4; # 百十
            }elseif($code1 == 'X' && $code3 == 'X'){
                $dict_no_type_id = 5; # 百个
            }elseif($code1 == 'X' && $code2 == 'X'){
                $dict_no_type_id = 6; # 十个
            }
        }else{
            $dict_no_type_id = 7;
        }

        return $dict_no_type_id;
    }

    /**
     * @desc 根据dict_no_type_id判断位置  1千2百3十4个
     * @param $dict_no_type_id
     * @return array
     */
    public static function getPositionByTypeId($dict_no_type_id){
        switch ($dict_no_type_id){
            case 1: # 千百
                $position = [1,2,'X','X'];
                break;
            case 2: # 千十
                $position = [1,'X',3,'X'];
                break;
            case 3: # 千个
                $position = [1,'X','X',4];
                break;
            case 4: # 百个
                $position = ['X',2,'X',4];
                break;
            case 5: # 百十
                $position = ['X',2,3,'X'];
                break;
            case 6: # 十个
                $position = ['X','X',3,4];
                break;
            case 7: # 千百十
                $position = [1,2,3,'X'];
                break;
            case 8: # 千百个
                $position = [1,2,'X',4];
                break;
            case 9: # 千十个
                $position = [1,'X',3,4];
                break;
            case 10: # 百十个
                $position = ['X',2,3,4];
                break;
            case 11: # 千百十个
                $position = [1,2,3,4];
                break;
        }

        return $position;
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

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        //$qihao = HN0898Service::getQihao($BettingRecords->lottery_type);
        $post_data = [ 'apiKey'=>$TzSystemsUsers->cookie, 'lotteryType' => self::$ll_types[$BettingRecords->lottery_type], 'betids'=>$BettingRecords->snid];
        $urlArr = self::getTzSiteInfo(self::$tz_system_id);//.'?'.http_build_query($post_data);
        $url = $urlArr['CANCEL_ORDER'];

        $_t = round(microtime(true) * 1000);
        $headers = [
            ":authority: ".$TzSystemsUsers->ssc_domain,
            ":method: POST",
            ":path: /member/?a=member.bet&m=cancelBet",
            ":scheme: https",
            "accept: application/json, text/plain, */*",
            "accept-encoding: gzip, deflate, br",
            "accept-language: zh-CN,zh;q=0.9,en;q=0.8",
            'Content-Length:'.strlen(http_build_query($post_data)),
            "content-type: application/x-www-form-urlencoded",
            "cookie: apiKey=".$TzSystemsUsers->cookie,
            'Origin: '.$TzSystemsUsers->ssc_domain,
            "referer: ".$TzSystemsUsers->ssc_domain."/main.html",
            "sec-fetch-dest: empty",
            "sec-fetch-mode: cors",
            "sec-fetch-site: same-origin",
            $TzSystemsUsers->user_agent,
        ];

        $rst = BingDaoService::postBetCurl($url, $post_data, $headers, $uid);
        if($rst['code'] == 200){
            $BettingRecords = BettingRecords::findOne(['snid'=>$snid]);
            $BettingRecords->cancel_status = 1;
            $BettingRecords->save();
            $rst['status'] = 200;
        }else{
            sleep(2);
        }
        $logArr = ['url'=>$url, 'snid'=>$snid,'headers'=>$headers,'post_data'=>$post_data, 'rst'=>$rst];
        Tool_Common::log('cancelOrder','INFO','撤单记录', $logArr);

        return $rst;
    }

    /**
     * @desc cookie中提取某个值
     * @param string $cookie
     * @param string $key
     * @return mixed|string
     */
    public static function getCookieDataByKey($cookie = '', $key = 'Token'){
        if(!$cookie) return '';

        $cookies =explode(';', $cookie);
        $cookieArr = [];
        foreach ($cookies as $cookie){
            $tmpVal = trim($cookie);
            $tmpData = explode('=', $tmpVal);
            $cookieArr[$tmpData[0]] = $tmpData[1];
        }
        if(isset($key) && !empty($key)) return $cookieArr[$key];
    }

    /**
     * @desc 希腊站点号码格式化成本站号码存储格式
     * @param $codes # codes:13579,,13579,,@13579,,,13579,      -- playway:10
     * @param $codes # codes:13579,X,13579,X@13579,X,X,13579   -- playway:4
     * @param $playway
     * @return string  格式：X1XX,X6XX
     */
    public static function formCodesStyle($codes, $playway = 10, $single = 0.1){
        //$codes = explode('@', $codes);
        //p([$codes, $playway, $single]);
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
                    $codesArr[str_replace(',','',$code)] = (string)$single;
                    break;
                case 2: # 三字定
                    $codesArr[str_replace(',','',$code)] = (string)$single;
                    break;
                case 3: # 四字定
                    $codesArr[str_replace(',','',$code)] = $single;
                    break;
            }

        }

        return $codesArr;
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
        if(isset($rst['code']) && $rst['code'] == 200){
            $balance = $rst['data'];
        }

        Tool_Common::log('getBalance','INFO','麒麟-用户余额', $rst);

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
     * @description 获取cookie并写表lt_tz_systems_users，场景：未登录情况下
     * @param $uid
     * @param $tz_system_id
     * @return mixed
     */
    public static function getCookie($uid, $tz_system_id){
        self::__init($uid, $tz_system_id);
        $m = \Yii::$app->cache;
        $mkey = 'UPDATE_COOKIE_TIME_'.$uid.'_'.$tz_system_id;
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        //p($TzSystemsUsers);
        //if(!$cookie = $m->get($mkey)){
            //p(HN0898Service::getTzSiteInfo($tz_system_id));
            # 1、预登录
            $_t = microtime(true) * 10000;
            $url = self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/verifycode/generateCode.php';
            if(strpos(strtolower($url), 'http') === false OR is_array($url)) return ['status'=>300, 'msg'=>'无效url'];
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
                'Accept-Encoding: gunzip, deflate',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                //$robot7_session_id,
                'Upgrade-Insecure-Requests: 1',
                'Proxy-Connection: keep-alive',
                'Host: '.self::getTzSiteInfo($tz_system_id,'domain'),
                'Cache-Control: max-age=0',
                'Referer: '.$url,
                $TzSystemsUsers->user_agent,
            ];
            $rst = self::postCurl($url, $headers);
            if(!isset($rst['code']) OR $rst['code'] != 200){
                return $rst;
            }
            $cookie = $rst['data'];

            $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'cookie'=>$rst, 'url'=>$url, 'headers'=>$headers];
            Tool_Common::log('getCookie','INFO','0898Cookie记录', $logArr);
            $m->set($mkey, $cookie, 180);
        //}
        return ['status'=>200, 'data'=>$cookie];
    }

    public static function getSessionId($url, $header){

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);//登陆后要从哪个页面获取信息
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSLVERSION, 3);

        $content = curl_exec($curl);
        //preg_match("/set\-cookie:([^\r\n]*)/i", $content, $matches);
        preg_match("/document.cookie\=\'([^\r\n]*)\'/i", $content, $matches);

        $roboot_id = str_replace('; path=/; domain=.ww99865.xyz','', $matches[1]);
        $logArr = ['content'=>$content, 'roboot_id'=>$roboot_id];
        if(curl_error($curl)>0){
            $logArr = array_merge($logArr,[ 'errno'=>curl_error($curl), 'error'=>curl_error($curl)]);
            Tool_Common::log('curl_get_cookie', 'INFO', '获取cookie', $logArr);
        }

        return $roboot_id;

    }


    /**
     *curl get请求
     */
    public static function curlGetSevenCookie($url,$header = []){

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);//登陆后要从哪个页面获取信息
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSLVERSION, 3);

        $content = curl_exec($curl);
        //preg_match_all("/Set\-cookie:([^\r\n]*)[\; path=\/\; Httponly]/i", $content, $matches);
        preg_match_all("/Set-Cookie: (.*)/i", $content, $matches);
        //p($matches,0);
        $cookies = $matches[1];
        $logArr = ['cookies'=>$cookies, 'matches'=>$matches, 'content'=>$content];
        //p(['url'=>$url, 'header'=>$header, 'matches'=>$matches, 'content'=>$content, 'errno'=>curl_error($curl)]);
        if(curl_error($curl)>0){
            $logArr = array_merge($logArr,[ 'errno'=>curl_error($curl), 'error'=>curl_error($curl)]);
            Tool_Common::log('curl_get_cookie', 'INFO', '获取cookie', $logArr);
        }
        $data = '';
        foreach ($cookies as $cookie){
            $data .= str_replace('; path=/; HttpOnly','',trim($cookie)).';';
        }
        $data = str_replace("; path=/; Httponly;",'',$data);

        return $data;
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
        $cookie_data = ['codeval'=>$cookie_key['code'], 'seq'=>$cookie_key['seq']];
        $path = '/verifycode/code.php?'.http_build_query($cookie_data);
        $headers = [
            ':authority: o1.op5168.com',
            ':method: GET',
            ':path: '.$path,
            ':scheme: https',
            'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
            'Accept-Encoding: gzip, deflate, br',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Referer: '.$TzSystemsUsers->ssc_domain.'/main.html',
            'sec-fetch-dest: image',
            'sec-fetch-mode: no-cors',
            'sec-fetch-site: same-origin',
            $TzSystemsUsers->user_agent,
        ];
        $url = self::getTzSiteInfo($TzSystemsUsers->tz_system_id,'SSC_INDEX').$path;
        $imageData = self::getCurl($url, $headers);
        $filename = Yii::$app->basePath . "/runtime/captcha/".$uid.'_'.$tz_system_id.'_'.md5($cookie_key['seq']).".png";
        $tp = fopen($filename,"w");
        fwrite($tp, trim($imageData));
        fclose($tp);
        $logData = ['url'=>$url,'headers'=>$headers, 'filename'=>$filename];
        Tool_Common::log('downLoadCodeImg','INFO','下载图片验证码', $logData);

        return true;
    }

    /**
     * @desc 判断是否登录
     * @param $uid
     * @param $tz_system_id
     * @return bool
     */
    public static function isLogin($uid, $tz_system_id){

        $balance = BingDaoService::getBalance($uid,$tz_system_id);

        $flag = $balance > 0 ? true : false;

        return (boolean)$flag;
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
        if(!isset($cookie_key['status']) OR $cookie_key['status'] != 200) return $cookie_key;

        # 第二步：下载验证码图片
        self::downLoadCodeImg($uid, $tz_system_id, $cookie_key['data']);
        //p([$uid, $tz_system_id, $cookie_key]);
        # 第三步：调验证码接口获取验证码
        //$captchaCode = '888888'; $rst = self::loginRemote($uid, $tz_system_id,$captchaCode); p($rst);  # 测试
        $captchaCodeRst = Tools::getCaptchaCode($uid, $tz_system_id, md5($cookie_key['data']['seq']), $code_type = '1010'); # 真实调用验证码接口，收费
        //p([$captchaCodeRst, $cookie_key]);
        //$code = $captchaCode['result'];
        if($cookie_key['status'] == 200){
            # 第四步：账号、验证码登录
            $verifyCode = self::getVerifyCodeByCaptchCodeRst($captchaCodeRst['code'], $cookie_key['data']['code']);
            $rst = self::loginRemote($uid, $tz_system_id, array_merge($cookie_key['data'], ['verifyCode'=>$verifyCode]));
        }

        # 第二步：账号、验证码登录
        //$rst = self::loginRemote($uid, $tz_system_id);
        # 第三步：同意
        //$rst = self::acceptAgreement($uid, $tz_system_id);

        # 获取用户信息
        $rst = self::userInfo($uid, $tz_system_id);

        return $rst;
    }

    /**
     * @desc 验证码获取对应顺序码
     * @param $captchaCodeRst 例如： 1423768950
     * @param $code 例如：780
     * @return string 例如：469
     */
    public static function getVerifyCodeByCaptchCodeRst($captchaCodeRst, $code){
        $verifyCode = '';
        for($j=0; $j<strlen($code); $j++){
            for($i=0; $i<strlen($captchaCodeRst); $i++){
                if($code[$j] == $captchaCodeRst[$i]){
                    $verifyCode .= $i;
                    if(strlen($verifyCode == 3)) break;
                }
            }
        }

        return $verifyCode;
    }

    /**
     * @desc 获取方案号
     * @param $uid
     * @param $tz_system_id
     * @return array
     */
    public static function getSn($uid, $tz_system_id, $lottery_type = DEFAULT_LOTTERY_TYPE){
        self::__init($uid, $tz_system_id);

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $qihao = HN0898Service::getQihao($lottery_type);
        $post_data = ['apiKey'=>$TzSystemsUsers->cookie, 'lotteryType' => self::$ll_types[$lottery_type], 'pageIndex'=>1, 'pageSize'=>10, 'drawNumber'=>$qihao];
        $urlArr = self::getTzSiteInfo(self::$tz_system_id);
        $url = $urlArr['baseUrl'].'/member/?a=member.bet&m=getBetList';

        $headers = [
            ":authority: ".$urlArr['domain'],
            ":method: POST",
            ":path: /member/?a=member.bet&m=getBetList",
            ":scheme: https",
            "accept: application/json, text/plain, */*",
            "accept-encoding: gzip, deflate, br",
            "accept-language: zh-CN,zh;q=0.9,en;q=0.8",
            'content-Length:'.strlen(http_build_query($post_data)),
            "content-type: application/x-www-form-urlencoded",
            "Cookie: ".$TzSystemsUsers->cookie,
            'Origin: '.$TzSystemsUsers->ssc_domain,
            "referer: ".$TzSystemsUsers->ssc_domain."/main.html",
            "sec-fetch-dest: empty",
            "sec-fetch-mode: cors",
            "sec-fetch-site: same-origin",
            $TzSystemsUsers->user_agent,
        ];

        $rst = BingDaoService::postBetCurl($url, $post_data, $headers, $uid);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'post_data'=>$post_data, 'rst'=>$rst, 'xx'=>$rst['data']['betList'][0]];
        $data = [];
        if($rst['code'] == 200 && isset($rst['data']['betList'][0]['betid']) && $rst['data']['betList'][0]['betid']){
            $data['sn'] = $rst['data']['betList'][0]['betid'];
        }
        $logArr['sn'] = $data;
        Tool_Common::log('getSn','INFO','冰岛获取方案号', $logArr);

        return $data;
    }

    /**
     * @desc 调用验证码接口
     * @param $uid
     * @param $tz_system_id
     * @param $cookie_key
     * @return mixed
     */
    public static function getCaptchaCode($uid, $tz_system_id, $cookie_key){
        $captcha_code_api = SystemConfig::findOne(['key'=>'captcha_code_api'])->value;
        $filename = Yii::$app->basePath . "/runtime/captcha/".$uid."_".$tz_system_id.'_'.$cookie_key.".png";
        switch ($captcha_code_api){
            case 1:
                $codeRst = CaptchaCodeService::juHe($filename); # 聚合接口
                break;
            case 2:
                $codeRst = CaptchaCodeService::showApi($filename); # 万维易源
                break;
            default:break;
        }

        return $codeRst;
    }

    /**
     * @desc 登陆
     * @param $uid
     * @param $tz_system_id
     * @param $code 验证码
     * @return mixed|string
     */
    private static function loginRemote($uid, $tz_system_id, $codeData){
        self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        if(!$TzSystemsUsers) return ['status'=>300, 'msg'=>'账号或者密码不能为空'];

        //$url = self::getTzSiteInfo($tz_system_id, 'DO_LOGIN');
        $url = self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/member/?a=member.login&m=memberLogin';
        $post_data = [
            'accountName' => $TzSystemsUsers->account,
            'password' => $TzSystemsUsers->password,
            'verifyCode' => $codeData['verifyCode'],
            'seq' => $codeData['seq'],
            'deviceType' => 1,
            'code' => $codeData['code'],
        ];
        $post_data = http_build_query($post_data);
        $headers = [
            "accept: application/json, text/plain, */*",
            "accept-encoding: gunzip, deflate, br",
            "accept-language: zh-CN,zh;q=0.9,en;q=0.8",
            "content-type: application/x-www-form-urlencoded",
            "Content-Length:".strlen($post_data),
            "Origin:".str_replace('www.','',self::$baseUrl),
            "Referer:".$TzSystemsUsers->ssc_domain,
            "sec-fetch-dest: empty",
            "sec-fetch-mode: cors",
            "sec-fetch-site: same-origins",
            $TzSystemsUsers->user_agent,
        ];

        $data = self::postCurl($url, $post_data, $headers);
        if(isset($data['code']) && $data['code'] == 200){
            $TzSystemsUsers->cookie = $data['data']['apiKey'];
            $TzSystemsUsers->balance = $data['data']['userInfo']['balance'];
            $TzSystemsUsers->save();
        }
        //sleep(10);
        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'url'=>$url,'post_data'=>$post_data, 'headers'=>$headers,'data'=>$data, 'codeData'=>$codeData];
        Tool_Common::log('loginRemote','INFO','0898登陆记录', $logArr);
        return $data;
    }

    /**
     * @desc ase加密
     * @param $d
     * @param $k
     * @return string
     */
    public static function getEnc($d, $k){
        $d = md5($d);
        $enc_str = Tool_Common::aes_ecrypt($d, $k);

        # return s2h(btoa(CryptoJS.AES.encrypt(d, k).toString()));

        //$btoa =

        return (string)$str;
    }

    public static function getK(){
        $k = [0,0,0,0,0,0,0,0];

        return implode(',', $k);
    }

    /**
     * @desc 登陆
     * @param $uid
     * @param $tz_system_id
     * @return mixed|string
     */
    private static function acceptAgreement($uid, $tz_system_id){
        //self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        //$url = self::getTzSiteInfo($tz_system_id, 'DO_LOGIN');
        $_t = microtime(true) * 10000;
        //$url = SevenService::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/App/Index'.'?_'.$_t;
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
        Tool_Common::log('loginRemote','INFO','7时彩登陆记录', $logArr);
        return $data;
    }

    /**
     * @desc 首页
     * @param $uid
     * @param $tz_system_id
     * @return mixed|string
     */
    public static function sscIndex($uid, $tz_system_id){
        self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        //$url = self::getTzSiteInfo($tz_system_id, 'DO_LOGIN');
        $_t = microtime(true) * 10000;
        //$url = SevenService::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/App/Index'.'?_'.$_t;
        //$url = SevenService::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/Member/GetMemberPrint?_='.$_t;
        $url = SevenService::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/App/Index?_='.$_t.'#!kuaida';
        $headers = [
            "Accept: application/json, text/javascript, */*; q=0.01",
            "Cookie: ".trim($TzSystemsUsers->cookie),
            //"Origin:".str_replace('www.','',self::$baseUrl),
            "Host:".str_replace('www.','',self::$domain),
            "Referer:".$TzSystemsUsers->ssc_domain.'/App/Index?_='.$_t,
            $TzSystemsUsers->user_agent,
        ];

        $data = CurlService::getCurl($url, $headers);
        //sleep(10);
        //HN0898Service::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'headers'=>$headers,'data'=>$data];
        //p($logArr);
        Tool_Common::log('loginRemote','INFO','0898登陆记录', $logArr);
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

        $start_time = microtime(true);
        //$url = self::getTzSiteInfo($tz_system_id, 'DO_LOGIN');
        $_t = microtime(true) * 10000;
        //$url = SevenService::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/App/Index'.'?_'.$_t;
        $url = self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/member/?a=member.account&m=getBalance';
        if(strpos(strtolower($url), 'http') === false OR is_array($url)) return ['status'=>300, 'msg'=>'无效url', 'key'=>'SSC_INDEX', 'url'=>$url];
        $post_data = http_build_query(['apiKey'=>str_replace('apiKey=', '', $TzSystemsUsers->cookie)]);
        $headers = [
            ":authority: o1.op5168.com",
            ":method: POST",
            ":path: /member/?a=member.account&m=getBalance",
            ":scheme: https",
            "accept: application/json, text/plain, */*1",
            "accept-encoding: gzip, deflate, br",
            "content-type: application/x-www-form-urlencoded",
            "Cookie: ".trim($TzSystemsUsers->cookie),
            "Origin:".str_replace('www.','',self::$baseUrl),
            "Referer:".$TzSystemsUsers->ssc_domain."/main.html",
            "content-length: ".strlen($post_data),
            "sec-fetch-dest: empty",
            "sec-fetch-mode: cors",
            "sec-fetch-site: same-origin",
            $TzSystemsUsers->user_agent,
        ];

        //$data = CurlService::httpGet($url, $headers);
        $data = self::postCurl($url, $post_data, $headers);
        $end_time = microtime(true);
        $consume_time = ($end_time-$start_time).'s';
        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'username'=>$TzSystemsUsers->username, 'url'=>$url, 'headers'=>$headers,'data'=>$data, 'consume_time'=>$consume_time];
        Tool_Common::log('userInfo','INFO','幸运五星-用户信息-5', $logArr);
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

    /**
     * @desc 投注之前获取期号相关信息
     */
    public static function getQihaoInfo($uid, $tz_system_id, $lottery_type = 6){
        self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        $lottery = self::getSiteLottery($lottery_type);
        $url = self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/api/MemberDesk/GetTheLastThree?lottery='.$lottery;
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
        //HN0898Service::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'headers'=>$headers,'data'=>$data];
        Tool_Common::log('getQihaoInfo','INFO','麒麟登陆前', $logArr);

        return $data;
    }


    public static function getSiteLottery($lottery_type = DEFAULT_LOTTERY_TYPE){
        $lottery = [
            7 => 18, # 北京快乐8
            5 => 2, # 重庆
            3 => 7, # 希腊5分彩
        ];

        return $lottery[$lottery_type];
    }

    /**
     * @desc 获取即将投注的期号、期号id
     * @param $uid
     * @param $tz_system_id
     * @param int $lottery_type
     * @return array
     */
    public static function getPreTz($uid, $tz_system_id, $lottery_type = 6){

        $qihaoInfo = self::getQihaoInfo($uid, $tz_system_id, $lottery_type);
        $data = $qihaoInfo['Data']['List'];
        if($qihaoInfo['Status'] != 1 OR $data[0]['periodsStatus'] != 1) return ['status'=>302, 'msg'=>$qihaoInfo['Info']];

        $rst = ['status'=>200, 'PeriodsID' => $data[0]['periodsID'], 'PeriodsNumber'=>$data[0]['periodsNumber']];

        return $rst;
    }

    public static function queryBetInfo(){


        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        $lottery = self::getSiteLottery($lottery_type);
        $url = self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/api/MemberDesk/GetTheLastThree?lottery='.$lottery;

    }

    /**
     * @decription post请求根据，接受传递的header头
     * @param $url
     */
    public static function postCurl($url,$post_data = [],$headers=[]){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        if(!$timeout) $timeout = 15;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["content-type:  application/x-www-form-urlencoded"]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_HTTP_VERSION, 2);

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $data = curl_exec($ch);
        $errno = curl_errno( $ch );

        //if(strpos($url, 'memberLogin') !== false)p($data);//{$logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno, 'error'=>curl_error($ch)]; p($logArr);}
        if($errno){
            if(isset($post_data['code']) && !empty($post_data['code']))$post_data['code'] = strlen($post_data['code'])>2000 ? substr($post_data['code'], 0, 200) : $post_data['code'];
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno];
            //p($logArr);
            Tool_Common::log('httpPostError','INFO','httpPost请求', $logArr);
        }

        //if(strpos($url, 'ajax')){ p(['url'=>$url, 'header'=>$headers,'post_data'=>$post_data,'rstData'=>$data,,$errno]); }
        if(curl_close($ch)) {
            echo 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
        }
        if($data == 'ok'){
            return 'ok';
        }
        //if(strpos($url, 'memberLogin') !== false)p(['data1'=>substr($data,1), 'data2'=>substr($data,2), 'data3'=>substr($data,3), 'ddata'=>json_decode(substr($data, 1))]);//{$logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno, 'error'=>curl_error($ch)]; p($logArr);}
        if(!BaseService::is_json($data)){
            $data = substr($data,3);
        }
        $rstData = json_decode($data, true); # data : {"Status":1,"Data":{"CompletedStatus":1,"LackStatus":0}}
        $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'rstData'=>$rstData, 'errno'=>$errno];
        Tool_Common::log('postCurl','INFO','httpPost请求', $logArr);
        //p(['url'=>$url, 'rstData'=>$rstData, 'data'=>$data, 'post_data'=>$post_data, 'headers'=>$headers, 'errno'=>$errno]);

        return $rstData;
    }

    /**
     * @decription 获取远程html内容
     * @param $url
     */
    public static function getCurl($url,$header=[]){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        //$header = array_merge(self::$postHeaders,$header);
        //if(strpos($url, 'GetPeriodsQuery')){ p([$url, $header]); }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);

        $data = curl_exec($ch);
        //if(true OR strpos($url, 'GetInfoByName') !== false){ p(['header'=>$header, 'url'=>$url, 'rst'=>$data]); }
        $errno = curl_errno($ch);
        if($errno>0) {
            $str = 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
            Tool_Common::log('getCurl', 'ERR', 'getCurl获取', ['url'=>$url, 'errno'=>$errno, 'postRst'=>$data, 'error'=>$str]);
            if($errno == 52){
                return ['Status'=>2, 'Data'=>'网盘网络超时，错误码52'];
            }
            return '';
        }

        return $data;
    }

    /**
     * @decription post请求根据，接受传递的header头
     * @param $url
     */
    public static function postBetCurl($url,$post_data = [],$headers=[], $uid = 0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        if(!$timeout) $timeout = 30;

        //$cookie = dirname(__FILE__)."/cookie.txt";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["content-type:  application/x-www-form-urlencoded"]);

        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        if(strpos($url, 'ww662889') !== false){
            //curl_setopt($ch, CURLOPT_USERAGENT, ['Chrome 42.0.2311.135']);
        }

        //$poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));

        Tool_Common::log('postBetCurl_rst','INFO','httpPost下注请求-冰岛', ['start'=>1]);
        $start_time = microtime(true);
        $data = curl_exec($ch);
        if(!BaseService::is_json($data)){
            $data = substr($data,3);
        }
        $end_time = microtime(true);
        $time_consume = ($end_time-$start_time).'s';
        //d($data);
        $errno = curl_errno( $ch );
        //$logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno]; p($logArr);
        if($errno){
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno];
            //p($logArr);
            Tool_Common::log('httpPostError','INFO','httpPost请求-1-2', $logArr);
        }

        //if(strpos($url, 'ajax')){ p(['url'=>$url, 'header'=>$headers,'post_data'=>$post_data,'rstData'=>$data,'errno'=>$errno]); }
        $rstData = json_decode($data, true); # data : {"Status":1,"Data":{"CompletedStatus":1,"LackStatus":0}}
        Tool_Common::log('postBetCurl_rst','INFO','httpPost下注请求-冰岛', ['end'=>1, 'data'=>$data, 'rstData'=>$rstData, 'time_consume'=>$time_consume]);
        //p(['url'=>$url, 'rstData'=>$rstData, 'data'=>$data, 'post_data'=>$post_data, 'headers'=>$headers, 'errno'=>$errno]);
        if(strpos($data, "\"Status\":1") !== false && strpos($data, "\"CompletedStatus\":1") !== false){ # json解析异常处理
            $rstData['Status'] = 1;
        }
        if(is_array($data)){
            $data_type = 'array';
        }
        if(BaseService::is_json($data)){
            $data_type = 'json';
        }

        if(strpos($data, '余额不足') !== false OR $rstData['code'] == 101){
            $rstData = ["Status"=>0, 'code'=>302, 'msg'=>'余额不足'];
        }elseif(strpos($data, '登录') !== false OR strpos($data, 'Bad Gateway') !== false OR strpos($data, 'Object moved') !== false){
            $rstData = ["Status"=>0, 'code'=>303, 'msg'=>'请重新登录'];
        }elseif(strpos($data, '短时间内重复提交') !== false){
            $rstData = ["Status"=>0, 'code'=>304, 'msg'=>'短时间内重复提交'];
        }elseif(strpos($data, '已关盘') !== false){
            $rstData = ["Status"=>0, 'code'=>305, 'msg'=>'已关盘'];
        }elseif(strpos($data, '维护中') !== false){
            $rstData = ["Status"=>0, 'code'=>306, 'msg'=>'系统线路维护中'];
        }elseif($errno>0){
            $rstData = ["Status"=>0, 'code'=>309, 'errno'=>$errno, 'msg'=>'网络超时'];
        }elseif(strpos($data, '停押') !== false){
            $rstData = ["Status"=>0, 'code'=>307, 'msg'=>'您的账号已被停押'];
        }else{
            $flag = 1;
            $rstData = json_decode($data, TRUE);
            if(empty($rstData)){
                $rstData = json_decode($data, true);
            }
        }
        if($errno OR in_array($rstData['code'], [302, 303, 304, 305, 306])){
            if(isset($post_data['bet_number']) && strlen($post_data['bet_number'])>200) $post_data['bet_number'] = substr($post_data['bet_number'], 0, 300);
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers, 'rst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr];
            Tool_Common::log('httpPostError','INFO','httpPost请求-冰岛-1', $logArr);
        }
        //$rstData['errno'] = $errno;
        $logArr = ['url'=>$url, 'headers'=>$headers, 'data'=>$data, 'errno'=>$errno, 'time_consume'=>$time_consume, 'poxy_addr'=>$poxy_addr, 'rstData'=>$rstData, 'data_type'=>$data_type, 'flag'=>$flag];
        Tool_Common::log('postBetCurl','INFO','httpPost下注请求-冰岛-all', $logArr);
        //p(['url'=>$url, 'rstData'=>$rstData, 'data'=>$data, 'post_data'=>$post_data, 'headers'=>$headers, 'errno'=>$errno]);

        return $rstData;
    }
}
