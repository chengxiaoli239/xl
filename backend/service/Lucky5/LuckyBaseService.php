<?php
namespace backend\service\Lucky5;
/**
 * Created by PhpStorm.
 *   
 * Date: 2018/02/06
 * Time: 09:40
 */

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
use backend\service\NumService;
use backend\service\PoxyIPService;
use common\service\proxy\ProxyBaseService;
use backend\service\SevenService;
use backend\service\SscDataService;
use backend\tools\Tools;
use common\models\AdminModel;
use common\service\CaptchaCodeService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class LuckyBaseService extends BaseTZService { # 重庆7时彩登陆体系
    public static $baseUrl =  '';
    public static $domain =  '';
    public static $cookie = '';
    public static $tzSiteInfo = [];
    public static $tz_system_id = ''; # 投注系统id
    public static $user_agent = '';
    public static $username = '';
    public static $user_id;
    public static $account = '';

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
        \Yii::$app->params['domain']  = str_replace('http://','',$TzSystemUser->ssc_domain);
        $tzSiteInfo = [
            'baseUrl' => $TzSystemUser->ssc_domain,
            'CANCEL_ORDER' => $baseUrl.'/Member/CancelMemberBet',
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
        //d($balance);
        $msg = ['status'=>200, 'msg'=>'金额同步成功~','tz_system_user_id'=>$tz_system_user_id, 'balance'=>$balance ];

        $TzSystemsUsers->balance = $balance;
        $TzSystemsUsers->updated_at = time();
        if(!$rst = $TzSystemsUsers->save()){
            $msg = ['status'=>300, 'msg'=>'金额同步失败11~'];
        }

        return $msg;
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
            25 => 102,
            27 => 104,
            29 => 102,
            30 => 102,
            31 => 102,
            33 => 102,
            18 => 109,
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
            31 => 50,
            33 => 20,
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
        if(in_array($tz_type,[ 27, 30, 31, 33])) { # 二定
            $str = '[二定位]，定位置“[取]”：千=[1]，百=[34]';
        }elseif(in_array($tz_type,[ 29])){
            $str = '[三定位]，定位置“[取]”：千=[0123456789]，百=[0123456789]，十=[0123456789]，固定合分取值：第[3]位选中，第[4]位选中，内容：[13579]；';
        }elseif(in_array($tz_type,[36])){ # 二字现
            $str = '[二字现]，不定合分值(两数合)：[0123456789]，包含“[取]”数：[0123456789]';
        }elseif(in_array($tz_type,[17])){ # 三字现
            $str = '[三字现]，不定合分值(两数合)：[0123456789]，包含“[取]”数：[0123456789]';
        }elseif(in_array($tz_type,[37])){ # 四字现
            $str = '[四字现]，不定合分值(两数合)：[0123456789]，包含“[取]”数：[0123456789]';
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
        $orgin_codesData = $codesData;
        $codes = [];
        $codesData = str_replace(',','',$codesData);
        $codesData = str_replace('@',',',$codesData);

        if($playway == 1){ # 二定
            $codesArr = explode(',', $codesData);
            foreach ($codesArr as $code){
                $dict_no_type_id = self::getdict_no_type_id($code, $playway);
                $codes[] = ['dict_no_type_id'=>$dict_no_type_id, 'bet_no'=>$code, 'bet_money'=>$single];
            }
        }elseif ($playway == 3){ # 四定
            $tmpCodes = explode('@', $orgin_codesData);
            $codesArr = [];
            foreach ($tmpCodes as $tmpCode){
                $t_codes = explode(',', $tmpCode);
                $codes_hz = ['p1'=>$t_codes[0], 'p2'=>$t_codes[1], 'p3'=>$t_codes[2], 'p4'=>$t_codes[3]];
                $codesArr_tmp = NumService::getCodesKuaiXuan($codes_hz, $code_type=4);
                $codesArr = array_merge($codesArr, $codesArr_tmp);
            }
            $codesArr_tmp1 = implode('@', $codesArr);
            $codesArr_tmp2 = str_replace(',', '', $codesArr_tmp1);
            $codes = explode('@', $codesArr_tmp2);
        }elseif ($playway == 4){ # 一定
            $tmpDatas = explode(',', $codesData);
            $tmpArr = [];
            foreach ($tmpDatas as $k=>$tmpData){
                if(!isset($tmpData) OR empty($tmpData)) continue;
                $p = $k + 1;
                $dict_no_type_id = self::getdict_no_type_id_oneFixed($p);
                $len = strlen($tmpData);
                for ($i = 0; $i<$len; $i++){
                    if($p == '1'){
                        $bet_no = $tmpData[$i].'XXXX';
                    }elseif ($p == '2'){
                        $bet_no = 'X'.$tmpData[$i].'XXX';
                    }elseif ($p == '3'){
                        $bet_no = 'X'.$tmpData[$i].'XXX';
                    }elseif ($p == '4'){
                        $bet_no = 'X'.$tmpData[$i].'XXX';
                    }elseif ($p == '5'){
                        $bet_no = 'X'.$tmpData[$i].'XXX';
                    }
                    $tmpArr[] = ['dict_no_type_id'=>$dict_no_type_id, 'bet_no'=>$bet_no, 'bet_money'=>$single];
                }
            }
            $codes = $tmpArr;
        }elseif ($playway == 2){ # 三定
            $codesArr = explode(',', $codesData);
            foreach ($codesArr as $code){
                $dict_no_type_id = self::getdict_no_type_id($code, $playway);
                $codes[] = ['dict_no_type_id'=>$dict_no_type_id, 'bet_no'=>$code, 'bet_money'=>$single];
            }

        }
        /*
        $codesArr = explode(',', $codesData);
        foreach ($codesArr as $code){
            $dict_no_type_id = self::getdict_no_type_id($code, $playway);
            $codes[] = ['dict_no_type_id'=>$dict_no_type_id, 'bet_no'=>$code, 'bet_money'=>$single];
        }
        */
        //p([$codesData, $single, $playway, $codes]);

        return $codes;
    }

    /**
     * @desc 投注前判断
     * @param string $uid
     * @param string $tz_system_id
     * @return array
     */
    private static function getBetforeBetStatus($uid = '', $tz_system_id = ''){

        if(empty($uid) OR empty($tz_system_id)) return ['status'=>200, 'msg'=>'uid或tz_system_id为空'];

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>self::$user_id, 'tz_system_id'=>self::$tz_system_id]);
        $url = self::getTzSiteInfo($tz_system_id, 'SSC_INDEX').'/Member/GetMemberPrint';

        $_t = round(microtime(true) * 1000);
        $headers = [
            'Access-Control-Allow-Headers: x-requested-with,Authorization',
            'Access-Control-Allow-Credentials: true',
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding: gunzip, deflate',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Connection: keep-alive',
            'Content-Length: 0',
            'Cookie: '.trim($TzSystemsUsers->cookie,';'),
            'Host: ' . str_replace('http://', '', $TzSystemsUsers->ssc_domain),
            'Origin: ' . $TzSystemsUsers->ssc_domain,
            'Referer: ' . $TzSystemsUsers->ssc_domain . '/App/Index?_=' . $_t,
            $TzSystemsUsers->user_agent,
            'X-Requested-With: XMLHttpRequest',
        ];
        $rst = CurlService::postCurl($url, [], $headers);

        return $rst;
        //p(['url'=>$url, 'headers'=>$headers, 'rst'=>$rst]);
    }

    /**
     * @desc 根据位置获取dict_no_type_id
     * @param $pos
     * @return int
     */
    public static function getdict_no_type_id_oneFixed($pos){
        $dict_no_type_id = 19;
        if($pos == 1){
            $dict_no_type_id = 19;
        }elseif ($pos == 2){
            $dict_no_type_id = 20;
        }elseif ($pos == 3){
            $dict_no_type_id = 21;
        }elseif ($pos == 4){
            $dict_no_type_id = 22;
        }elseif ($pos == 5){
            $dict_no_type_id = 23;
        }

        return $dict_no_type_id;
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
        if($playway == 1) {
            if ($code3 == 'X' && $code4 == 'X') {
                $dict_no_type_id = 1; # 千百
            } elseif ($code2 == 'X' && $code4 == 'X') {
                $dict_no_type_id = 2; # 千十
            } elseif ($code2 == 'X' && $code3 == 'X') {
                $dict_no_type_id = 3; # 千个
            } elseif ($code1 == 'X' && $code4 == 'X') {
                $dict_no_type_id = 4; # 百十
            } elseif ($code1 == 'X' && $code3 == 'X') {
                $dict_no_type_id = 5; # 百个
            } elseif ($code1 == 'X' && $code2 == 'X') {
                $dict_no_type_id = 6; # 十个
            }
        }elseif ($playway == 3){
            $dict_no_type_id = 11;
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
        $sn = $BettingRecords->sn;
        self::__init($uid, $tz_system_id);

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $qihao = HN0898Service::getQihao($BettingRecords->lottery_type);
        $counts = (int)($BettingRecords->betting_money/$BettingRecords->single);
        $post_data = [ 'ids'=>'{'.$sn.'}|'.$counts, 'period_no' => $qihao];

        $_t = round(microtime(true) * 1000);
        $headers = [
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding: gunzip, deflate',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Connection: keep-alive',
            'Content-Length:'.strlen(http_build_query($post_data)),
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Cookie: '.$TzSystemsUsers->cookie,
            'Host: '.str_replace('http://', '', $TzSystemsUsers->ssc_domain),
            'Origin: '.$TzSystemsUsers->ssc_domain,
            'Referer: '.$TzSystemsUsers->ssc_domain.'/App/Index?_='.$_t,
            'X-Requested-With: XMLHttpRequest',
            $TzSystemsUsers->user_agent,
        ];

        $url = self::getTzSiteInfo(self::$tz_system_id, 'CANCEL_ORDER').'?'.http_build_query($post_data);

        //$rst = CurlService::postCurl($url, $post_data, $headers);
        $rst = self::postBetCurl($url,$post_data, $headers, $TzSystemsUsers->uid);
        if($rst['Status'] == 1 && strpos($rst['Data'], '退码成功')){
            $BettingRecords = BettingRecords::findOne(['snid'=>$snid]);
            $BettingRecords->cancel_status = 1;
            $BettingRecords->save();
            $rst['status'] = 200;
        }else{
            //if(isset($rst['Data'])) p($rst['Data'], 0);
            //sleep(1);
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
        if(isset($rst['Status']) && $rst['Status'] == 1){
            $balance = $rst['Data']['credit_balance'];
        }
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $TzSystemsUsers->balance = $balance;
        $TzSystemsUsers->save();

        Tool_Common::log('getBalance','INFO','幸运五星-用户余额-1', $rst);

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
            Tool_Common::log('getSnidBySn','INFO','获取方案号', $logData);
            $m->set($mkey, 6*3600);
        }

        return $snid;
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
    public static function getCookie($uid, $tz_system_id){
        self::__init($uid, $tz_system_id);
        $m = \Yii::$app->cache;
        $mkey = 'UPDATE_COOKIE_TIME_'.$uid.'_'.$tz_system_id;
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        # 1、预登录
        $_t = microtime(true) * 10000;
        $url = self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/Member/Login'.'?_='.$_t;
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
        $robot7_session_id = self::getSessionId($url, $headers, $uid);
        $headers[] = 'Cookie: '.$robot7_session_id;
        $cookie = self::curlGetSevenCookie($url, $headers, $uid);
        $cookieData = $cookie;
        //if($uid == 19) p([$robot7_session_id, $cookieData, $cookie]);
        if($cookieData){
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
            $TzSystemsUsers->cookie = $robot7_session_id.';'.trim($cookieData);
            $TzSystemsUsers->cookie = trim(str_replace('; path=/; HttpOnly','', $TzSystemsUsers->cookie), ';');
            $rst = $TzSystemsUsers->save();
        }
        self::$headers = [];
        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'cookie'=>$cookie, 'url'=>$url, 'headers'=>$headers];
        //if($uid == 18)p($logArr);
        Tool_Common::log('getCookie','INFO','0898Cookie记录', $logArr);
        $cookie = trim($cookie, ';');
        $cookie = str_replace('; path=/; HttpOnly','',$cookie);
        $m->set($mkey, $cookie, 180);

        return $cookie;
    }
    public static function getSessionId($url, $header, $uid = 0){

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);//登陆后要从哪个页面获取信息
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        self::setPoxy($curl, $url, $uid); # 设置代理

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSLVERSION, 3);

        $content = curl_exec($curl);
        if(strpos($content, 'Set-Cookie') !== false){
            preg_match("/Set\-Cookie:([^\r\n]*)/i", $content, $matches);
        }else{
            preg_match("/document.cookie\=\'([^\r\n]*)\'/i", $content, $matches);
        }

        $Arrs = explode('.', $url);
        $domain = $Arrs[1];
        if(strpos($matches[1], $domain) !== false){
            $roboot_id = trim(str_replace('; path=/; domain=.'.$domain.'.xyz','', $matches[1]));
            Tool_Common::log('getSessionId', 'INFO', '获取session_id', ['url'=>$url, 'domain'=>$domain, 'roboot_id'=>$roboot_id, 'content'=>$content]);
        }
        /*
        if(strpos($matches[1], 'cq779835') !== false){
            $roboot_id = trim(str_replace('; path=/; domain=.cq779835.xyz','', $matches[1]));
        }
        */
        if(strpos($roboot_id, '您当前使用的浏览器不支持') !== false){
            $tmp_roboot_id = explode('\';', $roboot_id);
            if($tmp_roboot_id[0]) $roboot_id = $tmp_roboot_id[0];
        }
        $logArr = ['content'=>$content, 'roboot_id'=>$roboot_id];
        Tool_Common::log('curl_get_cookie', 'INFO', '获取cookie', $logArr);
        if(TRUE OR curl_error($curl)>0){
            $logArr = array_merge($logArr,[ 'errno'=>curl_error($curl), 'roboot_id'=>$roboot_id]);
            Tool_Common::log('roboot_id', 'INFO', '获取roboot_id', $logArr);
        }

        return $roboot_id;

    }

    /**
     *curl get请求
     */
    public static function curlGetSevenCookie($url,$header = [], $uid = 0){

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);//登陆后要从哪个页面获取信息
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        self::setPoxy($curl, $url, $uid); # 设置代理IP

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSLVERSION, 3);

        $content = curl_exec($curl);
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


    public static function loginWriteCookie($uid = 1, $tz_system_id = 1){
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        if($TzSystemsUsers->balance > 0) {
            return ['status'=>200, 'msg'=>'已经登录的状态'];
        }
        //self::__init($uid, $tz_system_id);
        $rst = false;

        # 第一步：获取cookie
        $cookie_key = self::getCookie($uid,$tz_system_id);
        if(isset($cookie_key['status']) && $cookie_key['status'] == 300) return $cookie_key;
        # 第二步：账号、验证码登录
        $rst = self::loginRemote($uid, $tz_system_id);
        # 第三步：同意
        $rst = self::acceptAgreement($uid, $tz_system_id);

        # 获取用户信息
        $rst = self::userInfo($uid, $tz_system_id);

        return $rst;
    }

    /**
     * @desc 获取方案号
     * @param $uid
     * @param $tz_system_id
     * @return array
     */
    public static function getSn($uid, $tz_system_id){
        $rst = self::userInfo($uid, $tz_system_id);
        $data = [];
        if(!isset($rst['Status']) OR $rst['Status'] !=1) return $data;

        //p($rst);
        $data['sn'] = $rst['Data']['serial_no'];
        $data['qihao'] = substr($rst['Data']['previous_period_no'], 2);
        $tzDatas = $rst['Data']['Details'];
        $snidStr = '';
        foreach ($tzDatas as $tzData){
            $snidStr .= $tzData['bet_id'].'|1,';
        }
        //$snidStr = trim('|1,', implode('|1,', $tzDatas));
        $data['snid'] = trim($snidStr, ',');
        Tool_Common::log('getSn','INFO','幸运五获取方案号', $data);

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
            Tool_Common::log('getCurl', 'ERR', 'getCurl获取', ['url'=>$url, 'postRst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr]);
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
     * @desc 登陆
     * @param $uid
     * @param $tz_system_id
     * @return mixed|string
     */
    /**
     * @desc 从登录页面提取RSA公钥
     * @param $uid
     * @param $tz_system_id
     * @return string
     */
    private static function getRsaPublicKey($uid, $tz_system_id){
        $m = \Yii::$app->cache;
        $mkey = 'rsa_public_key_'.$tz_system_id;
        $publicKey = $m->get($mkey);
        if($publicKey) return $publicKey;

        $_t = microtime(true) * 10000;
        $url = self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/Member/Login'.'?_='.$_t;
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Encoding: gunzip, deflate',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Upgrade-Insecure-Requests: 1',
            'Cache-Control: max-age=0',
            $TzSystemsUsers->user_agent,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, BaseService::getSslVersionByUid($uid));
        BaseService::setPoxy($ch, $url, $uid);

        $html = curl_exec($ch);
        curl_close($ch);

        preg_match('/id="hd_publickey"\s+value="([^"]+)"/i', $html, $matches);
        $publicKey = $matches[1] ?? '';
        if($publicKey){
            $m->set($mkey, $publicKey, 3600);
        }
        Tool_Common::log('/luckybase/'.__FUNCTION__, 'INFO', '获取RSA公钥', ['uid'=>$uid, 'publicKey'=>$publicKey ? 'found' : 'NOT FOUND']);

        return $publicKey;
    }

    /**
     * @desc RSA加密（模拟JSEncrypt行为，PKCS#1 v1.5填充，base64输出，避免末尾==）
     * @param string $data
     * @param string $publicKey
     * @return string
     */
    private static function rsaEncrypt($data, $publicKey){
        // 将原始base64公钥转换为PEM格式
        $pemKey = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($publicKey, 64, "\n") . "-----END PUBLIC KEY-----";
        $key = openssl_pkey_get_public($pemKey);
        if(!$key) return $data;

        $result = '';
        $maxRetries = 10;
        $retryCount = 0;
        while($retryCount < $maxRetries){
            openssl_public_encrypt($data, $encrypted, $key, OPENSSL_PKCS1_PADDING);
            $result = base64_encode($encrypted);
            if(substr($result, -2) !== '=='){
                break;
            }
            $retryCount++;
        }

        return $result;
    }

    private static function loginRemote($uid, $tz_system_id){
        self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        # 获取RSA公钥并加密账号密码
        $publicKey = self::getRsaPublicKey($uid, $tz_system_id);
        if($publicKey){
            $post_data['Account'] = self::rsaEncrypt($TzSystemsUsers->account, $publicKey);
            $post_data['Password'] = self::rsaEncrypt($TzSystemsUsers->password, $publicKey);
            Tool_Common::log('/luckybase/loginRemote', 'INFO', 'RSA加密登录', ['uid'=>$uid, 'account'=>$TzSystemsUsers->account]);
        }else{
            $post_data['Account'] = $TzSystemsUsers->account;
            $post_data['Password'] = $TzSystemsUsers->password;
            Tool_Common::log('/luckybase/loginRemote', 'INFO', '明文登录(未找到RSA公钥)', ['uid'=>$uid]);
        }

        if(!$post_data['Account'] OR !$post_data['Password']) return ['status'=>300, 'msg'=>'账号或者密码不能为空'];

        //$url = self::getTzSiteInfo($tz_system_id, 'DO_LOGIN');
        $_t = microtime(true) * 10000;
        $url = self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/Member/DoLogin'.'?_='.$_t;
        $post_data = http_build_query($post_data);
        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36',
            //'Accept-Language:zh-CN,zh;q=0.9,en;q=0.8',
            'Accept-Language:zh-CN,zh;q=0.9',
            'Connection:keep-alive',
            'Accept-Encoding: gunzip, deflate',
            'X-Requested-With: XMLHttpRequest',
            "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
            "Cache-Control:max-age=0",
            "Upgrade-Insecure-Requests:1",
            "Content-Length:".strlen($post_data),
            "Content-Type: application/x-www-form-urlencoded",
            "Cookie: ".str_replace(';;', ';',trim($TzSystemsUsers->cookie)),
            "Origin:".str_replace('www.','',self::$baseUrl),
            "Host:".str_replace('www.','',self::$domain),
            "Referer:".$TzSystemsUsers->ssc_domain,
        ];

        $data = self::httpPost($url,$post_data, $headers, $TzSystemsUsers->uid);
        //sleep(10);
        //self::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'account'=>$TzSystemsUsers->account, 'username'=>$TzSystemsUsers->username, 'tz_system_id'=>$tz_system_id, 'url'=>$url,'post_data'=>$post_data, 'headers'=>$headers,'data'=>$data];
        //p($logArr);
        $desc = '';
        if(isset($data['Status']) && $data['Status'] == 2){
            $desc = $data['Data'];
        }
        $TzSystemsUsers->desc = $desc;
        $TzSystemsUsers->updated_at = time();
        $TzSystemsUsers->save();

        Tool_Common::log('loginRemote','INFO','Luck5登陆记录-1', $logArr);
        return $data;
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
        $url = self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/Member/AcceptAgreement'.'?_'.$_t;
        $headers = [
            "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
            "Cache-Control:max-age=0",
            "Upgrade-Insecure-Requests:1",
            "Cookie: ".trim($TzSystemsUsers->cookie),
            "Host:".str_replace('www.','',self::$domain),
        ];

        $data = self::getCurl($url, $headers, $uid);
        //sleep(10);
        //HN0898Service::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'headers'=>$headers,'data'=>$data];
        //p($logArr);
        Tool_Common::log('acceptAgreement','INFO','幸运五登陆-同意', $logArr);
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

        //$url = self::getTzSiteInfo($tz_system_id, 'DO_LOGIN');
        $_t = (int)microtime(true) * 1000;
        $url = self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/Member/GetMemberPrint?_='.$_t;
        if(strpos(strtolower($url), 'http') === false OR is_array($url)) return ['status'=>300, 'msg'=>'无效url', 'key'=>'SSC_INDEX', 'url'=>$url];
        $headers = [
            "Accept: application/json, text/javascript, */*; q=0.01",
            "Accept-Encoding: gunzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9",
            "Connection: keep-alive",
            "Cookie: ".trim($TzSystemsUsers->cookie),
            //"Origin:".str_replace('www.','',self::$baseUrl),
            "Host:".str_replace('www.','',self::$domain),
            "Referer:".$TzSystemsUsers->ssc_domain.'/App/Index?_='.$_t,
            $TzSystemsUsers->user_agent,
            "X-Requested-With: XMLHttpRequest",
        ];

        $start_time = microtime(true);
        $uid = max($TzSystemsUsers->uid, $uid);
        $data = self::httpGet($url, $headers, $uid);
        $end_time = microtime(true);
        $time_consume = ($end_time-$start_time).'s';
        //sleep(10);
        //HN0898Service::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'account'=>$TzSystemsUsers->account, 'time_consume'=>$time_consume, 'username'=>$TzSystemsUsers->username, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'headers'=>$headers,'data'=>$data];
        //p($logArr);
        Tool_Common::log('userInfo','INFO','幸运五星-用户信息-1', $logArr);
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

        $data = self::getCurl($url, $headers, $uid);
        //HN0898Service::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'headers'=>$headers,'data'=>$data];
        Tool_Common::log('getQihaoInfo','INFO','幸运五登陆前', $logArr);

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
    public function bet($qihao, $plan_id, $codes){
        $bigFlag = 0;
        if(true OR strlen($codes)>5000){ # 针对大量号码下注 用post请求
            $bigFlag = 1;
            return $this->postBatchBet($qihao, $plan_id, $codes);
        }

        $plan = UserSysPlans::findOne($plan_id);
        $playway = $plan->playway ? $plan->playway : 3;
        $single = $plan->single ? $plan->single : 0.1;
        $tz_type = $plan->tz_type ? $plan->tz_type : 0;
        $buy_type = $plan->buy_type ? $plan->buy_type : 1;
        $lottery_type = $plan->lottery_type;
        //p(['playway'=>$playway, 'totalCount'=>count($codes), 'single'=>$single, 'qihao'=>$qihao, 'tz_type'=>$tz_type, 'buy_type'=>$buy_type,'codes'=>$codes]);
        if(!self::$user_id) return ['status'=>400,'msg'=>'账号为空，不能识别用户'];
        $data = ['status'=>200, 'msg'=>$qihao.'期投注成功!', 'time'=>date('Y-m-d H:i:s')];

        # 验证
        $rst = self::validateBettingContent($playway,$codes);
        if($rst['status'] != 200){
            $data = ['status'=>300, 'msg'=>$qihao.$rst['msg']];
        }
        $totalCount = count(explode("@",$codes)); # 注数
        //p($totalCount);
        $totalBetMoney = $totalCount * $single; # 投注总金额
        $way = self::getWay($tz_type);

        $bet_codes = str_replace(',','',$codes);
        $bet_codes = str_replace('@',',',$bet_codes);

        //$post_data = ['totalCount'=>$totalCount, 'totalBetMoney'=>$totalBetMoney, 'bets'=>json_encode($codes), 'way'=>$way, 'period_no'=>'20'.$qihao, 'bet_log'=>urlencode('投注：'.$totalCount.'/'.$single.'注,总共：'.$totalBetMoney.'元'), ];
        $way = self::getWay($tz_type);
        $number_type = self::getNumberType($tz_type);
        $bet_type = self::getBetLog($tz_type);
        $post_data = [
            //'bet_number' => $bet_codes,
            'bet_number' => $bet_codes,
            'bet_money' => $single,
            'bet_way' => $way,
            'is_xian' => 0,
            'is_iframe' => 1,
            'number_type' => $number_type,
            'bet_log' => $bet_type,
            'is_package' => 0,
            'period_no' => $qihao,
            'operation_condition' => self::getOperationCondition($tz_type),
        ];

        $_t = round(microtime(true) * 1000);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$plan->uid, 'tz_system_id'=>self::$tz_system_id]);
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
            'Accept-Encoding: gunzip, deflate',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Cache-Control: max-age=0',
            'Connection: keep-alive',
            'Content-Length:'.strlen(http_build_query($post_data)),
            //'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Content-Type: application/x-www-form-urlencoded',
            'Cookie: '.$TzSystemsUsers->cookie,
            'Host: '.str_replace('http://', '', $TzSystemsUsers->ssc_domain),
            'Origin: '.$TzSystemsUsers->ssc_domain,
            'Referer: '.$TzSystemsUsers->ssc_domain.'/App/Index?_='.$_t,
            'Upgrade-Insecure-Requests: 1',
            $TzSystemsUsers->user_agent,
        ];
        //p($headers);
        //$url = self::getUserUrlArr(self::$user_id, 'ORDER_TZ');
        $url = self::getTzSiteInfo(self::$tz_system_id, 'MULBET_URL');//.'?'.http_build_query($post_data);

        $account = AdminModel::findOne(self::$user_id)->username;  # 投注用户账号
        //p(['headers'=>$headers, 'url'=>$url, 'account'=>$account, 'post_data'=>$post_data]);

        # 缓存锁
        $m = \Yii::$app->cache;
        $betKey = BetService::buildBetKey($account, self::$tz_system_id, $lottery_type, $qihao, $plan_id);
        if($betLock = $m->get($betKey)) return ['status'=>303, 'msg'=>'已经投注过了', 'key'=>$betKey];

        //if(in_array($tz_type, [20, 23, 25]) OR $bigFlag == 1){
        # 和值投注反应时间比较久，无需返回直接锁住
        $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间
        $m->set($betKey, 1, $time);
        //}
        # 真实投注
        $start_time = microtime(true);
        //p(['url'=>$url, 'headers'=>$headers, 'rst'=>$rst,'post_data'=>$post_data]);
        $rst = self::postBetCurl($url, $post_data, $headers, $TzSystemsUsers->uid);
        //$rst = json_encode($rst);
        $end_time = microtime(true);
        $time_consume = ($end_time - $start_time). 's';
        if($rst['Status'] != 1){
            $tzRst = [
                'uid'=>self::$user_id, 'lottery_type'=>$lottery_type, 'status'=>301, 'msg'=>$qihao.$rst['msg'],'url'=>$url,
                'post_data'=>$post_data, 'user_id'=>self::$user_id, 'headers'=>$headers, 'postRst'=>$rst, 'time_consume'=>$time_consume
            ];
            //if($tz_type != 20) $tzRst['code'] = $codes;
            Tool_Common::log('bet_error','INFO','幸运五投注记录-投注失败', $tzRst);
            if(in_array($rst['code'], [302, 303, 304, 305, 306, 307])){ # # 302余额不足、303请登录、304重复提交、305已关盘、306系统维护
                return $rst;
            }
            //return $rst;
        }

        $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间
        $m->set($betKey, 1, $time);

        //p($rst,0);
        //$position = UserFollowData::findOne(self::$plan_id)->position;
        //$position = $position ? $position : self::$position;

        $n = count(explode('@',$codes));
        if(in_array($playway, [2, 3]) && $tz_type != 20){
            $totalmoney = SscDataService::calTzTotalMoney($codes, $single, $playway);
        }else{
            $totalmoney = $n * $single; // 投注总金额 = 注数 * 倍数
        }
        # 获取方案号，记录id, 用于撤单
        $snInfo = self::getSn(self::$user_id, self::$tz_system_id);// 用户信息 Array ( [sn] => 403054677338701312 [qihao] => 190412023 [snid] => 31724311|1,31724312|1 )

        $insertData = [
            'playway'=> $playway,  // 投注方式
            'tz_type'=> $tz_type,  // 投注类型
            'buy_type'=> $buy_type,  // 购买方向类型
            'uid'=> self::$user_id,  // 投注账号id
            'lottery_type' => $lottery_type, # 彩种
            'account' => $account,
            'plan_id' => $plan_id, # 计划id
            'codes' => (string)$codes,  // 投注号码
            'qihao' => $qihao,  // 投注期号
            'tz_system_id' => self::$tz_system_id,  // 投注系统tz_systems .id
            'sn'=>$snInfo['sn'],
            'snid'=>'{'.$snInfo['sn'].'}|'.$n,
            'order_type'=>3, # 单双三字定
            'is_simulate' => 0,  // 是否模拟投注
            'single' => $single,  // 投注倍数
            'betting_money'=> $totalmoney,  // 投注金额
        ];
        $insertRst = BetService::_logRecords($insertData);
        self::$headers = [];

        if(strlen($post_data['bet_number'])>2000) $post_data['bet_number'] = substr($post_data['bet_number'], 0, 200);
        $logArr = ['uid'=>self::$user_id,'url'=>$url,'post_data'=>$post_data,'headers'=>$headers, 'postRst'=>$rst,'insertData'=>$insertData, 'insertRst'=>$insertRst];
        Tool_Common::log('bet','INFO','幸运五星时插入记录-真实投注', $logArr);
        return $data;
    }

    /**
     * @desc 号码拆解
     * @param $codes ['8,9,9,9','9,8,9,9','9,9,8,9','9,9,9,8']
     * @param int $length
     * @return mixed
     */
    public static function splitCodes($codes, $length = 300){

        $codesArr = array_chunk($codes, $length);

        return $codesArr;
    }

    /**
     * @desc 获取每次下注号码量
     * @return int|string
     */
    public static function getBetNumsPer(){
        $nums = SystemConfig::findOne(['key'=>'tz_nums_per'])->value;
        if(!$nums) $nums = 1650;

        return $nums;
    }

    /**
     * @desc 批量号码拆解下注
     * @param $qihao
     * @param $plan_id
     * @param $codes
     * @return array
     */
    public static function postBatchBet($qihao, $plan_id, $codes){
        $tmpCodes = $codes;
        $plan = UserSysPlans::findOne($plan_id);
        if($plan->tz_type == 22){ # 四定单双,codes格式：13579,13579,02468,13579@13579,13579,02468,02468@13579,02468,13579,13579
            $codesArr = self::getBetCodes($codes, $plan->single, $plan->playway);
        }elseif($plan->tz_type == 18){
            $codesArr = self::getBetCodes($codes, $plan->single, $plan->playway);
        }else{
            $tmpCodes = str_replace(',', '', $tmpCodes);
            $codesArr = explode('@', $tmpCodes);
        }
        $BET_BIG_LIMIT_STATUS = BetService::getConfig('BET_BIG_LIMIT_STATUS');
        if($BET_BIG_LIMIT_STATUS){
            if(count($codesArr)>6000) return ['status'=>300, 'msg'=>'号码组数太多不能超过6000组号码'];
        }

        # 组数
        $count = count($codesArr);

        $betNums = self::getBetNumsPer();
        $codesArrs = self::splitCodes($codesArr,  $betNums); # 2500一次
        //p($codesArrs);

        $playway = $plan->playway ? $plan->playway : 3;
        $single = $plan->single ? $plan->single : 0.1;
        $tz_type = $plan->tz_type ? $plan->tz_type : 0;
        $lottery_type = $plan->lottery_type;
        //p(['playway'=>$playway, 'totalCount'=>count($codes), 'single'=>$single, 'qihao'=>$qihao, 'tz_type'=>$tz_type, 'buy_type'=>$plan->buy_type,'codes'=>$codes]);
        if(!self::$user_id) return ['status'=>400,'msg'=>'账号为空，不能识别用户'];

        $data = ['status'=>200, 'msg'=>$qihao.'期投注成功!', 'time'=>date('Y-m-d H:i:s')];

        $url = self::getTzSiteInfo(self::$tz_system_id, 'MULBET_URL');//.'?'.http_build_query($post_data);
        $way = self::getWay($tz_type);
        $snInfo_sn = '';
        $snInfo_snid = '';
        $rst = [];
        foreach ($codesArrs as $key=>$tmpcodesArr){
            $bet_log = self::getBetLog($tz_type);
            if($playway == 4){ # 一字定
                $post_data = [
                    'bets' => json_encode($tmpcodesArr),
                    'way' => $way,
                    'period_no' => $qihao,
                ];
            }else{ # 四定、三定、X字现
                $is_xian = in_array($tz_type, \Yii::$app->params['IS_XIAN']) ? 1 : 0;
                $bet_codes = implode(',', $tmpcodesArr);
                $post_data = [
                    'bet_number'=>$bet_codes,
                    'bet_money'=>$single,
                    'bet_way'=>$way,
                    'is_xian'=>$is_xian,
                    'number_type'=> LuckyBaseService::getNumType($tz_type, $playway, $tmpcodesArr),
                    'is_iframe' => 1,
                    //'guid'=>'3e1752e5-e455-4075-b657-0fd13b90d65d',
                    'bet_log'=>$bet_log,
                    'is_package' => 0,
                    'period_no'=>$qihao,
                    'operation_condition' => self::getOperationCondition(),
                ];
            }

            $_t = round(microtime(true) * 1000);
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$plan->uid, 'tz_system_id'=>self::$tz_system_id]);
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
                'Accept-Encoding: gunzip, deflate',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Cache-Control: max-age=0',
                'Connection: keep-alive',
                'Content-Length:'.strlen(http_build_query($post_data)),
                //'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$TzSystemsUsers->cookie,
                'Host: '.str_replace('http://', '', $TzSystemsUsers->ssc_domain),
                'Origin: '.$TzSystemsUsers->ssc_domain,
                'Referer: '.$TzSystemsUsers->ssc_domain.'/App/Index?_='.$_t,
                'Upgrade-Insecure-Requests: 1',
                $TzSystemsUsers->user_agent,
            ];

            # 缓存锁
            $m = \Yii::$app->cache;
            $betKey = BetService::buildBetKey($plan->account, self::$tz_system_id, $lottery_type, $qihao, $plan_id).'_'.$key; # 分配下注后面加key
            //if($betLock = $m->get($betKey)) return ['status'=>303, 'msg'=>'已经投注过了', 'key'=>$betKey];

            //if(in_array($tz_type, [20, 23, 25]) OR $bigFlag == 1){
            # 和值投注反应时间比较久，无需返回直接锁住
            $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间
            $m->set($betKey, 1, $time);
            //}
            # 真实投注
            $start_time = microtime(true);
            $tmpRst = self::postBetCurl($url, $post_data, $headers, $TzSystemsUsers->uid);
            //sleep(1);
            //p(['url'=>$url, 'headers'=>$headers, 'rst'=>$tmpRst,'post_data'=>$post_data]);
            $rst[$key] = $tmpRst;
            //$rst = json_encode($rst);
            $end_time = microtime(true);
            $time_consume = ($end_time - $start_time). 's';
            if($tmpRst['Status'] != 1){
                $tzRst = [
                    'uid'=>self::$user_id, 'lottery_type'=>$lottery_type, 'status'=>301, 'msg'=>$qihao.$rst['msg'],'url'=>$url,
                    'post_data'=>$post_data, 'user_id'=>self::$user_id, 'headers'=>self::$headers, 'postRst'=>$rst, 'time_consume'=>$time_consume
                ];
                $mkey = 'request_login_'.$TzSystemsUsers->uid.'_'.$qihao.'_'.$key;
                if($f = $m->get($mkey)){
                    return ['status'=>300, 'msg'=>'已经重复登录过一次'];
                }
                if($rst[$key]['code'] == 303){ # 判断掉线登录一次
                    if($rst[$key]['errno']>0){
                        $m = \Yii::$app->cache;
                        $mkey_proxy = PoxyIPService::builProxyIpKey($plan->uid);
                        $m->delete($mkey_proxy);
                    }
                    BaseService::login($TzSystemsUsers->id);
                    $tmpRst = self::postBetCurl($url, $post_data, $headers, $TzSystemsUsers->uid);
                    $m->set($mkey, 1, 5*60);
                }
                //if($tz_type != 20) $tzRst['code'] = $codes;
                Tool_Common::log('bet_error','INFO','幸运星分批投注记录-投注失败', $tzRst);
                # 302余额不足、303请登录、304重复提交、305已关盘、306系统维护，307账号停押
                if(!in_array($plan->account, \Yii::$app->params['test_account']) && in_array($rst[$key]['code'], [302, 303, 304, 305, 306, 307])){
                    //return $rst;
                    continue;
                }
                //return $rst;
            }

            $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间
            $m->set($betKey, 1, $time);

            # 获取方案号，记录id, 用于撤单
            $snInfo = self::getSn(self::$user_id, self::$tz_system_id);// 用户信息 Array ( [sn] => 403054677338701312 [qihao] => 190412023 [snid] => 31724311|1,31724312|1 )
            $snInfo_snid .= '{'.$snInfo['sn'].'}|'.count($tmpcodesArr).';'; # 多次下单需要分开，多次撤单
            $snInfo_sn .= $snInfo['sn'].';'; # 多次下单需要分开，多次撤单
        }
        $data['rst'] = $rst;

        $n = count(explode('@',$codes));
        if(in_array($playway, [2, 3]) && $tz_type != 20){
            $totalmoney = SscDataService::calTzTotalMoney($codes, $single, $playway);
        }else{
            $totalmoney = $n * $single; // 投注总金额 = 注数 * 倍数
        }
        if($playway == 4 && $tz_type == 18){ # 一字定
            $totalmoney = $count * $single;
        }

        $insertData = [
            'playway'=> $playway,  // 投注方式
            'tz_type'=> $tz_type,  // 投注类型
            'buy_type'=> 1,  // 购买方向类型
            'uid'=> self::$user_id,  // 投注账号id
            'lottery_type' => $lottery_type, # 彩种
            'account' => $plan->account,
            'plan_id' => $plan_id, # 计划id
            'codes' => (string)$codes,  // 投注号码
            'qihao' => $qihao,  // 投注期号
            'tz_system_id' => self::$tz_system_id,  // 投注系统tz_systems_id
            'sn'=> trim($snInfo_sn, ';'),
            'snid'=> trim($snInfo_snid, ';'),
            'order_type'=>3, # 单双三字定
            'is_simulate' => 0,  // 是否模拟投注
            'single' => $single,  // 投注倍数
            'betting_money'=> round($totalmoney, 2),  // 投注金额
        ];
        $insertRst = BetService::_logRecords($insertData);
        self::$headers = [];

        unset($post_data['bet_number']);
        $logArr = ['uid'=>self::$user_id,'url'=>$url,'post_data'=>$post_data,'headers'=>self::$headers, 'bigFlag'=>1, 'postRst'=>$rst,'insertData'=>$insertData, 'insertRst'=>$insertRst];
        Tool_Common::log('bet','INFO','幸运五批量插入记录-真实投注3', $logArr);

        return $data;
    }

    /**
     * @param $tz_type
     * @return array|mixed
     */
    public static function getNumType($tz_type, $playway='', $tmpcodesArr=[]){
        if($playway==2) return 30;
        if($tz_type == 27 && !empty($tmpcodesArr[0]) && strlen($tmpcodesArr[0])==5){
            $num_type = 30;
            return $num_type; # 五位二定
        }
        $num_type = 40; # 四定
        $num_types = [
            36 => 21, # 二字现
            17 => 31, # 三字现
            37 => 41, # 四字现
        ];
        if(isset($num_types[$tz_type])) return $num_types[$tz_type];

        return $num_type;
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);

        if(strpos($url, 'ww662889') !== false){
            //curl_setopt($ch, CURLOPT_USERAGENT, ['Chrome 42.0.2311.135']);
        }

        $poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));

        $start_time = microtime(true);
        $data = curl_exec($ch);
        $end_time = microtime(true);
        //d($data);
        $errno = curl_errno( $ch );
        //$logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno]; p($logArr);
        if($errno){
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno];
            //p($logArr);
            Tool_Common::log('httpPostError','INFO','httpPost请求-1-10', $logArr);
        }

        if(strpos($url, 'ajax')){ p(['url'=>$url, 'header'=>$headers,'post_data'=>$post_data,'rstData'=>$data,'errno'=>$errno]); }
        if(curl_close($ch)) {
            echo 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
        }
        if($data == 'ok'){
            return 'ok';
        }
        $rstData = json_decode($data, true); # data : {"Status":1,"Data":{"CompletedStatus":1,"LackStatus":0}}
        //p(['url'=>$url, 'rstData'=>$rstData, 'data'=>$data, 'post_data'=>$post_data, 'headers'=>$headers, 'errno'=>$errno]);
        if(strpos($data, "\"Status\":1") !== false && strpos($data, "\"CompletedStatus\":1") !== false){ # json解析异常处理
            $rstData['Status'] = 1;
        }

        if(strpos($data, '余额不足') !== false){
            $rstData = ["Status"=>0, 'code'=>302, 'msg'=>'余额不足'];
        }elseif(strpos($data, '登录') !== false OR strpos($data, 'Bad Gateway') !== false OR strpos($data, 'Object moved') !== false){
            $rstData = ["Status"=>0, 'code'=>303, 'msg'=>'请重新登录'];
        }elseif(strpos($data, '短时间内重复提交') !== false){
            $rstData = ["Status"=>0, 'code'=>304, 'msg'=>'短时间内重复提交'];
        }elseif(strpos($data, '已关盘') !== false){
            $rstData = ["Status"=>0, 'code'=>305, 'msg'=>'已关盘'];
        }elseif(strpos($data, '维护中') !== false){
            $rstData = ["Status"=>0, 'code'=>306, 'msg'=>'系统线路维护中'];
        }elseif(strpos($data, '停押') !== false){
            $rstData = ["Status"=>0, 'code'=>307, 'msg'=>'您的账号已被停押'];
        }elseif(strpos($data, "\"Status\":1") !== false && strpos($data, "\"CompletedStatus\":1") !== false){ # json解析异常处理
            $rstData = ['Status'=>1, 'Data'=>['CompletedStatus'=>1, 'LackStatus'=>0]];
        }else{
            $rstData = json_decode($data, TRUE);
        }
        if($errno OR in_array($rstData['code'], [302, 303, 304, 305, 306])){
            if(isset($post_data['bet_number']) && strlen($post_data['bet_number'])>200) $post_data['bet_number'] = substr($post_data['bet_number'], 0, 300);
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers, 'rst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr];
            Tool_Common::log('httpPostError','INFO','httpPost请求-3', $logArr);
        }
        $rstData['errno'] = $errno;
        $time_consume = ($end_time-$start_time).'s';
        $logArr = ['url'=>$url, 'headers'=>$headers, 'rst'=>$data, 'errno'=>$errno, 'time_consume'=>$time_consume, 'poxy_addr'=>$poxy_addr];
        Tool_Common::log('postBetCurl','INFO','httpPost下注请求-4', $logArr);
        //p(['url'=>$url, 'rstData'=>$rstData, 'data'=>$data, 'post_data'=>$post_data, 'headers'=>$headers, 'errno'=>$errno]);

        return $rstData;
    }

    /**
     * @desc 判断是否登录
     * @param $uid
     * @param $tz_system_id
     * @return bool
     */
    public static function isLogin($uid, $tz_system_id){

        $balance = LuckyBaseService::getBalance($uid,$tz_system_id);

        $flag = $balance > 0 ? true : false;

        return (boolean)$flag;
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
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        $poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $data = curl_exec($ch);
        $errno = curl_errno( $ch );
        //if($errno && strstr($url, 'BatchBet') OR strstr($url, 'MultipleBet')){
        //$logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$header, 'rst'=>$data, 'errno'=>$errno];p($logArr);
        curl_close($ch);
        if($errno){
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$header, 'rst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr];
            //p($logArr);
            Tool_Common::log('httpPostError','INFO','httpPost请求', $logArr);
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
        $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
        if(!$POXY_STATUS) return []; # CURL 代理开关

        $poxy_addr = ProxyBaseService::getCurrentValidProxyIp();
        if(strpos($url, 'ww662889') === false){
            //$poxy_addr = '218.85.247.70:20000';
        }
        Tool_Common::log('setPoxy', 'INFO', '设置全局代理5', ['url'=>$url, 'poxy_addr'=>$poxy_addr, 'uid'=>$uid]);
        $uids = PoxyIPService::getProxyUids();
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

        return $poxy_addr;
    }

}
