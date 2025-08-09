<?php
namespace backend\service\LeCai;
/**
 * Created by PhpStorm.
 * Date: 2018/02/06
 * Time: 09:40
 */

use backend\models\BetErrorPlansTask;
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
use backend\service\plans\BetErrorPlansTaskService;
use backend\service\PoxyIPService;
use backend\service\ProxyBaseService;
use backend\service\SevenService;
use backend\service\SscDataService;
use backend\tools\Tools;
use common\models\AdminModel;
use common\service\CaptchaCodeService;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class ZhongFaService { # 宝岛众发登陆体系
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
        \Yii::$app->params['domain']  = str_replace('https://','',$TzSystemUser->ssc_domain);
        $tzSiteInfo = [
            'baseUrl' => $TzSystemUser->ssc_domain,
            'SSC_INDEX' => $baseUrl,
            'domain' => \Yii::$app->params['domain'],
        ];
        if($url_key && $tzSiteInfo[$url_key]) return $tzSiteInfo[$url_key];

        return $tzSiteInfo;

    }

    /**
     * @decription 同步用户余额 by account
     * @param $tz_system_user_id - 表lt_tz_systems_users.id
     * @return array
     */
    public static function synBalance($tz_system_user_id){
        $TzSystemsUsers = TzSystemsUsers::findOne($tz_system_user_id);
        $balance = self::getBalance($TzSystemsUsers->uid, $TzSystemsUsers->tz_system_id, $r=1);
        //d($balance);
        $msg = ['status'=>200, 'msg'=>'金额同步成功~','tz_system_user_id'=>$tz_system_user_id, 'balance'=>$balance, 'account'=>$TzSystemsUsers->account, 'username'=>$TzSystemsUsers->username];

        $TzSystemsUsers->balance = $balance;
        $TzSystemsUsers->updated_at = time();
        if(!$rst = $TzSystemsUsers->save() OR $balance === false){
            $msg = ['status'=>300, 'msg'=>'金额同步失败10~', 'balance'=>$balance,];
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
            17 => 102,
            36 => 102,
            37 => 102,
        ];

        if(isset($rstData[$tz_type])) return $rstData[$tz_type];

        return 108;
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
            17 => 31,
            36 => 21,
            37 => 41,
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
    public static function getOperationCondition($playway = 3){
        if($playway == 2) {
            $json = '千0123456789百0123456789十0123456789';
        }elseif ($playway == 1){
            $json = '千0123456789百0123456789';
        }else{
            # 四定
            $json = '千0123456789百0123456789个0123456789';
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
    public static function getBetCodes($codesData, $single = 0.1, $playway = 1, $uid=''){
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
        }elseif ($playway == 3 && !in_array($uid, \Yii::$app->params['IMPORT_CODES_KUAIYI_UIDS'])){ # 四定
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
        }elseif ($playway == 2 OR in_array($uid, \Yii::$app->params['IMPORT_CODES_KUAIYI_UIDS'])){ # 三定、 四定
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
        $snid = $BettingRecords->snid ? : $BettingRecords->sn;
        self::__init($uid, $tz_system_id);

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $qihao = HN0898Service::getQihao($BettingRecords->lottery_type);
        //$counts = (int)($BettingRecords->betting_money/$BettingRecords->single);
        $post_data = [ 'no'=>$snid, 'vol' => $qihao];

        $_t = round(microtime(true) * 1000);
        $querys = [
            '_nowTime' => $_t,
            '_uri' => '/orders/cancel-by-no',
        ];
        $sign = ZhongFaService::getSign($querys);
        $querys['_sign'] = $sign;

        $urlArr = self::getTzSiteInfo(self::$tz_system_id);
        $url = $urlArr['SSC_INDEX'].'/user-api/orders/cancel-by-no'.'?'.http_build_query($querys);
        $headers = [
            ':authority: '.$urlArr['domain'],
            ':method: POST',
            ':path: /user-api/orders/cancel-by-no?'.http_build_query($querys),
            ':scheme: https',
            'accept: application/json, text/plain, */*',
            'accept-encoding: gzip, deflate, br',
            'accept-language: zh-CN,zh;q=0.9',
            'content-length: '.strlen(http_build_query($post_data)),
            'content-type: application/x-www-form-urlencoded',
            'cookie: '.$TzSystemsUsers->cookie.'; main-lottery=twk5',
            'origin: '.$TzSystemsUsers->ssc_domain,
            'referer: '.$TzSystemsUsers->ssc_domain.'/order/codes',
            'sec-ch-ua: " Not A;Brand";v="104", "Chromium";v="104", "Google Chrome";v="104"',
            'sec-ch-ua-mobile: ?0',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            $TzSystemsUsers->user_agent,
        ];

        $rst = self::postBetCurl($url,$post_data, $headers, $TzSystemsUsers->uid, $b_type=1);
        if(true OR $rst['success']){
            $BettingRecords = BettingRecords::findOne(['snid'=>$snid, 'uid'=>$uid]);
            $BettingRecords->cancel_status = 1;
            $BettingRecords->save();
            $rst['status'] = 200;
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
    public static function getBalance($uid, $tz_system_id, $r='', $is_auto=1){
        $m = \Yii::$app->cache;

        $mkey = 'getBalance_'.$tz_system_id.'_'.$r.'_'.$is_auto;
        $balance = $m->get($mkey);
        if($balance) return $balance;

        self::__init($uid, $tz_system_id);
        $start_time = microtime(true);
        $rst = self::userInfo($uid, $tz_system_id, $is_auto);
        $balance = false;
        if(isset($rst['success']) && $rst['success']){
            $balance = $rst['data']['balance'];
        }
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $TzSystemsUsers->balance = $balance;
        $TzSystemsUsers->save();
        $end_time = microtime(true);
        $time_consume = ($end_time-$start_time).'s';
        $m->set($mkey, $balance, 5);

        Tool_Common::log('getBalance','INFO','宝岛众发-用户余额-3', ['uid'=>$uid, 'r'=>$r, 'rst'=>$rst, 'balance'=>$balance, 'time_consume'=>$time_consume]);

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
        $urlArr = self::getTzSiteInfo($tz_system_id);
        $url = $urlArr['SSC_INDEX'].'/user-api/captcha';
        if(strpos(strtolower($url), 'http') === false OR is_array($url)) return ['status'=>300, 'msg'=>'无效url'];
        $headers = [
            ':authority: '.$urlArr['domain'],
            ':method: GET',
            ':path: /user-api/captcha',
            ':scheme: https',
            'accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
            'accept-encoding: gunzip, deflate, br',
            'accept-language: zh-CN,zh;q=0.9',
            'referer: '.$TzSystemsUsers->ssc_domain.'/login',
            'sec-ch-ua: " Not A;Brand";v="104", "Chromium";v="104", "Google Chrome";v="104"',
            'sec-ch-ua-mobile: ?0',
            'sec-fetch-dest: image',
            'sec-fetch-mode: no-cors',
            'sec-fetch-site: same-origin',
            $TzSystemsUsers->user_agent,
        ];
        $msg = '操作成功';
        $cookie = self::curlGetSevenCookie($url, $headers, $uid);
        //if($uid == 19) p([$robot7_session_id, $cookieData, $cookie]);
        if($cookie && is_string($cookie)){
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
            $TzSystemsUsers->cookie = $cookie;
            $rst = $TzSystemsUsers->save();
        }else{
            Tool_Common::log('getCookie','INFO','获取cookie', ['cookie'=>$cookie]);
            $msg = '获取cookie失败';
            $cookie = '';
        }
        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'cookie'=>$cookie, 'url'=>$url, 'headers'=>$headers];
        //if($uid == 18)p($logArr);
        Tool_Common::log('getCookie','INFO','0898Cookie记录', $logArr);
        $m->set($mkey, $cookie, 180);

        return ['status'=>200, 'msg'=>$msg, 'cookie'=>$cookie];
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
        curl_setopt($curl, CURLOPT_SSLVERSION, 4);

        $content = curl_exec($curl);
        preg_match_all("/set-cookie: (.*)/i", $content, $matches);
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
            $data .= trim(explode(';', trim($cookie))[0]);
        }

        //$data = str_replace("; path=/; Httponly;",'',$data);
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
        if(empty($rst['cookie'])) return $rst;
        # 第二步：下载验证码图片
        self::downLoadCodeImg($uid, $tz_system_id, $rst['cookie']);
        # 第三步 请求验证码接口获取验证码结果
        $captchaCodeRst = Tools::getCaptchaCode($uid, $tz_system_id, $rst['cookie']); # 真实调用验证码接口，收费
        //$code = $captchaCode['result'];
        if($captchaCodeRst['status'] == 200){
            $code = $captchaCodeRst['code'];
            # 第四步：账号、验证码登录
            $rst = self::loginRemote($uid, $tz_system_id, $code);
        }
        # 第三步：同意
        if(isset($rst['success']) && $rst['success']){
            $rst = self::acceptAgreement($uid, $tz_system_id);
        }

        # 获取用户信息
        $rst = BaseService::synBalance($TzSystemsUsers->id); # 同步余额

        return $rst;
    }

    /**
     * @desc 心跳包
     * @param $uid
     * @param $tz_system_id
     * @return array|mixed|string
     */
    public static function heart($uid, $tz_system_id){

        $m = \Yii::$app->cache;
        $mkey = 'ZHONGFA_heart_'.$uid.'_'.$tz_system_id;
        $rst = $m->get($mkey);
        if(!empty($rst)) return $rst;

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $urlArr = self::getTzSiteInfo($tz_system_id);

        $_t = microtime(true) * 10000;
        $post_data = ['_nowTime'=>$_t, '_uri'=>'/heart'];
        $sign = ZhongFaService::getSign($post_data);
        $querys = array_merge($post_data, [
            '_sign' => $sign,
        ]);
        $url = $urlArr['SSC_INDEX'].'/user-api/heart?'.http_build_query($querys);
        $headers = [
            ':authority: '.$urlArr['domain'],
            ':method: POST',
            ':path: /user-api/heart?_uri=%2Fheart&_nowTime='.$_t.'&_sign='.$sign,
            ':scheme: https',
            'accept: application/json, text/plain, */*',
            'accept-encoding: gzip, deflate, br',
            'accept-language: zh-CN,zh;q=0.9',
            'content-length: 0',
            'cookie: '.$TzSystemsUsers->cookie.'; main-lottery=twk5',
            'origin: '.$TzSystemsUsers->ssc_domain,
            'referer: '.$TzSystemsUsers->ssc_domain.'/game/kuai-da',
            'sec-ch-ua: " Not A;Brand";v="104", "Chromium";v="104", "Google Chrome";v="104"',
            'sec-ch-ua-mobile: ?0',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            $TzSystemsUsers->user_agent,
        ];
        $rst = self::postBetCurl($url, $post_data, $headers, $uid, $b_type=2); # 调试阶段先注释12.26
        $m->set($mkey, $rst, 15);

        $logArr = ['url'=>$url,'headers'=>$headers,'post_data'=>$post_data, 'rst'=>$rst];
        Tool_Common::log('/zhongfa/'.__FUNCTION__, 'INFO', '宝岛众发心跳', $logArr);

        return $rst;
    }

    /**
     * @desc 获取签名
     * @param array $params
     * @return string
     */
    public static function getSign($params=[]){
        $_nowTime = $params['_nowTime'];
        $paramsSplits = ['+', '-', '*', '/', '%', '&', '|', '!', '^', '@', '#', '$', '(', ')'];
        $i = (int)($_nowTime + 4);
        $index = $i%14;

        $split = $paramsSplits[$index];
        ksort($params);
        $sign_Arr = [];
        foreach ($params as $key=>$param){
            $sign_Arr[] = $key.':'.$param;
        }
        $sign_str = implode($split, $sign_Arr);

        $sign = md5($sign_str);

        return $sign;
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

        $_t = (int)(microtime(true) * 1000);
        $urlArr = self::getTzSiteInfo($TzSystemsUsers->tz_system_id);
        $url = $urlArr['SSC_INDEX'].'/user-api/captcha?timestamp='.$_t;

        $headers = [
            ':authority: '.$urlArr['domain'],
            ':method: GET',
            ':path: /user-api/captcha?timestamp='.$_t,
            ':scheme: https',
            'accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
            'accept-encoding: gzip, deflate, br',
            'accept-language: zh-CN,zh;q=0.9',
            'cookie: '.$TzSystemsUsers->cookie,
            'referer: '.$TzSystemsUsers->ssc_domain.'/login',
            'sec-ch-ua: " Not A;Brand";v="104", "Chromium";v="104", "Google Chrome";v="104"',
            'sec-ch-ua-mobile: ?0',
            'sec-fetch-dest: image',
            'sec-fetch-mode: no-cors',
            'sec-fetch-site: same-origin',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36'
        ];
        $imageData = self::httpGet($url, $headers, $uid);
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

    /**
     * @desc 获取方案号
     * @param $uid
     * @param $tz_system_id
     * @return array
     */
    public static function getSn($uid, $tz_system_id, $lottery_type = DEFAULT_LOTTERY_TYPE){
        //$rst = self::userInfo($uid, $tz_system_id);
        self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $qihao = HN0898Service::getQihao($lottery_type);

        $_t = (int)(microtime(true) * 1000);
        $signParams = [
            '_uri' => '/orders/codes',
            '_nowTime' => $_t,
        ];
        $querys = [
            'isXian' => 0,
            'rangeType' => 'money',
            'gameId' => '',
            'isWin' => '',
            'lotteryId' => 'twk5',
            'vol' => $qihao,
            'page' => 1,
            'pageSize' => 20,
        ];
        $sign = ZhongFaService::getSign($signParams);
        $querys['_sign'] = $sign;
        $querys = array_merge($signParams, $querys);
        $urlArr = self::getTzSiteInfo($tz_system_id);
        $url = $urlArr['SSC_INDEX'].'/user-api/orders/codes?'.http_build_query($querys);
        $headers = [
            ':authority: '.$urlArr['domain'],
            ':method: GET',
            ':path: /user-api/orders/codes?'.http_build_query($querys),
            ':scheme: https',
            'accept: application/json, text/plain, */*',
            'accept-encoding: gunzip, deflate, br',
            'accept-language: zh-CN,zh;q=0.9',
            'cookie: '.$TzSystemsUsers->cookie.'; main-lottery=twk5',
            'referer: '.$TzSystemsUsers->ssc_domain.'/order/codes',
            'sec-ch-ua: " Not A;Brand";v="104", "Chromium";v="104", "Google Chrome";v="104"',
            'sec-ch-ua-mobile: ?0',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            $TzSystemsUsers->user_agent,
        ];

        $data = self::httpGet($url, $headers, $uid, $time_out=15);
        $logArr = ['uid'=>$uid, 'url'=>$url, 'headers'=>$headers, 'rst'=>$data];

        Tool_Common::log('/zhongfa/'.__FUNCTION__,'INFO','众发方案号', $logArr);

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
    private static function loginRemote($uid, $tz_system_id, $code){
        self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        $_t = microtime(true) * 1000;
        $post_data = [
            'id' => $TzSystemsUsers->account,
            'password' => $TzSystemsUsers->password,
            'captcha' => $code,
        ];
        $_sign = ZhongFaService::getSign($post_data);

        if(!$post_data['id'] OR !$post_data['password']) return ['status'=>300, 'msg'=>'账号或者密码不能为空'];

        $urlArr = self::getTzSiteInfo($tz_system_id);
        $querys = array_merge($post_data, [
            '_t' => $_t,
            '_sign' => $_sign,
        ]);
        $url = $urlArr['SSC_INDEX'].'/user-api/login?'.http_build_query($querys);
        $headers = [
            ':authority: '.$urlArr['domain'],
            ':method: POST',
            ':path: /user-api/login?_uri=%2Flogin&_nowTime='.$_t.'&_sign='.$querys['_sign'],
            ':scheme: https',
            'accept: application/json, text/plain, */*',
            'accept-encoding: gzip, deflate, br',
            'accept-language: zh-CN,zh;q=0.9',
            'content-length: '.strlen(http_build_query($post_data)),
            'content-type: application/x-www-form-urlencoded',
            'cookie: '.$TzSystemsUsers->cookie,
            'origin: '.$urlArr['SSC_INDEX'],
            'referer: '.$urlArr['SSC_INDEX'].'/login',
            'sec-ch-ua: " Not A;Brand";v="104", "Chromium";v="104", "Google Chrome";v="104"',
            'sec-ch-ua-mobile: ?0',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            $TzSystemsUsers->user_agent,
        ];

        $data = self::httpPost($url, $post_data, $headers, $TzSystemsUsers->uid);
        //self::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'account'=>$TzSystemsUsers->account, 'username'=>$TzSystemsUsers->username, 'tz_system_id'=>$tz_system_id, 'url'=>$url,'post_data'=>$post_data, 'headers'=>$headers,'data'=>$data];
        $desc = '';
        if(isset($data['success']) && !$data['success']){
            $desc = $data['message'];
        }
        $TzSystemsUsers->desc = $desc;
        $TzSystemsUsers->updated_at = time();
        $TzSystemsUsers->save();

        Tool_Common::log('/zhongfa/'.__FUNCTION__,'INFO','宝岛众发登陆记录-2', $logArr);
        return $data;
    }
    /**
     * @desc 宝岛众发确认总共有四步
     * @param $uid
     * @param $tz_system_id
     * @return mixed|string
     */
    private static function acceptAgreement($uid, $tz_system_id){
        //self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        //$url = self::getTzSiteInfo($tz_system_id, 'DO_LOGIN');
        $_t = microtime(true) * 1000;
        $urlArr = self::getTzSiteInfo($tz_system_id);
        $querys = [
            '_uri' => '/lottery-results/vol-status',
            '_nowTime' => $_t,
        ];
        $querys['_sign'] = ZhongFaService::getSign($querys);
        $url = $urlArr['SSC_INDEX'].'/user-api/lottery-results/vol-status'.http_build_query($querys);
        $headers = [
            ':authority: '.$urlArr['domain'],
            ':method: GET',
            ':path: /user-api/lottery-results/vol-status?'.http_build_query($querys),
            ':scheme: https',
            'accept: application/json, text/plain, */*',
            'accept-encoding: gzip, deflate, br',
            'accept-language: zh-CN,zh;q=0.9',
            'cookie: '.$TzSystemsUsers->cookie,
            'referer: '.$TzSystemsUsers->ssc_domain.'/game/kuai-xuan',
            'sec-ch-ua: " Not A;Brand";v="104", "Chromium";v="104", "Google Chrome";v="104"',
            'sec-ch-ua-mobile: ?0',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            $TzSystemsUsers->user_agent
        ];

        $data = self::getCurl($url, $headers, $uid);
        //sleep(10);
        //HN0898Service::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'headers'=>$headers,'data'=>$data];
        //p($logArr);
        Tool_Common::log('acceptAgreement','INFO','宝岛众发登陆-同意', $logArr);
        return $data;
    }

    /**
     * @desc 首页
     * @param $uid
     * @param $tz_system_id
     * @return mixed|string
     */
    public static function userInfo($uid, $tz_system_id, $is_auto = 1){
        self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        if($is_auto == 1 && (strpos($TzSystemsUsers->desc, '您的访问过于频繁') !== false OR strpos($TzSystemsUsers->desc, '用户名或密码不正确') !== false)){
            return ['status'=>300, 'msg'=>'您的访问过于频繁，请稍后再试 或 用户名或密码不正确'];
        }

        $_t = (int)(microtime(true) * 1000);
        $params = ['_nowTime'=>$_t, '_uri'=>'session-user'];
        $params['sign'] = ZhongFaService::getSign($params);
        $urlArr = self::getTzSiteInfo($tz_system_id);
        $url = $urlArr['SSC_INDEX'].'/user-api/session-user'.'?'.http_build_query($params);
        if(strpos(strtolower($url), 'http') === false OR is_array($url)) return ['status'=>300, 'msg'=>'无效url', 'key'=>'SSC_INDEX', 'url'=>$url];
        $headers = [
            ':authority: '.$urlArr['domain'],
            ':method: GET',
            ':path: /user-api/session-user?'.http_build_query($params),
            ':scheme: https',
            'accept: application/json, text/plain, */*',
            'accept-encoding: gzip, deflate, br',
            'accept-language: zh-CN,zh;q=0.9',
            'cookie: '.$TzSystemsUsers->cookie.'; main-lottery=twk5',
            'referer: '.$TzSystemsUsers->ssc_domain.'/game/kuai-xuan',
            'sec-ch-ua: " Not A;Brand";v="104", "Chromium";v="104", "Google Chrome";v="104"',
            'sec-ch-ua-mobile: ?0',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            $TzSystemsUsers->user_agent,
        ];

        $start_time = microtime(true);
        $uid = max($TzSystemsUsers->uid, $uid);
        $data = self::httpGet($url, $headers, $uid, $time_out=15);
        //$data = self::httpGetCurl($url, $headers, $uid, $time_out=15);

        $end_time = microtime(true);
        $time_consume = ($end_time-$start_time).'s';
        //sleep(10);
        //HN0898Service::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'account'=>$TzSystemsUsers->account, 'time_consume'=>$time_consume, 'username'=>$TzSystemsUsers->username, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'headers'=>$headers,'data'=>$data];
        $desc = '';
        if(isset($rst['success']) && !$data['success']){
            $desc = json_encode($data, 320);
        }
        $TzSystemsUsers->desc = $desc;
        $TzSystemsUsers->save();
        Tool_Common::log('userInfo','INFO','宝岛众发-用户信息-2', $logArr);
        $m = \Yii::$app->cache;
        $mkey = 'heart';
        if(!$flag = $m->get($mkey)){
            $m->set($mkey, 1, 120);
            self::heart($uid, $tz_system_id); # 心跳包
        }
        return $data;
    }

    /**
     * @desc 目前为计划任务正常下注入口 2021.05.23
     * @param $id
     */
    public function repeatErrorBet($id){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        $row = BetErrorPlansTask::findOne($id);
        $url = $row->bet_url;
        //$headers = json_decode($row->bet_headers); # 含有cookie，如果是重新登陆 cookie要变动，待处理
        $uid = $row->uid;
        $tz_system_id = $row->tz_system_id;
        ZhongFaService::__init($uid, $tz_system_id);
        $post_data = json_decode($row->post_datas, 320);
        $repeats = []; # 是否有重复号码
        if($row->lottery_type == 8 && in_array($row->uid, \Yii::$app->params['IMPORT_CODES_REPEAT_UIDS'])){
            $tmpDatas = explode(',', $post_data['bet_number']);
            $post_data['bet_number'] = implode(',', array_unique($tmpDatas));
        }

        $_t = round(microtime(true) * 1000);
        $querys = [
            '_uri' => '/orders/bet',
            '_nowTime' => $_t
        ];
        $sign = ZhongFaService::getSign($querys);

        $m = \Yii::$app->cache;
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $urlArr = self::getTzSiteInfo($tz_system_id);
        $headers = [
            ':authority: '.$urlArr['domain'],
            ':method: POST',
            ':path: /user-api/orders/bet?_uri=%2Forders%2Fbet&_nowTime='.$_t.'&_sign='.$sign,
            ':scheme: https',
            'accept: application/json, text/plain, */*',
            'accept-encoding: gzip, deflate, br',
            'accept-language: zh-CN,zh;q=0.9',
            'content-length: '.strlen(http_build_query($post_data)),
            'content-type: application/x-www-form-urlencoded',
            'cookie: '.$TzSystemsUsers->cookie.'; main-lottery=twk5',
            'origin: '.$TzSystemsUsers->ssc_domain,
            'referer: '.$TzSystemsUsers->ssc_domain.'/game/kuai-yi',
            'sec-ch-ua: " Not A;Brand";v="104", "Chromium";v="104", "Google Chrome";v="104"',
            'sec-ch-ua-mobile: ?0',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            $TzSystemsUsers->user_agent,
        ];

        //$headers = json_decode($row->bet_headers, true);
        $time1 = microtime(true);
        $tmpRst = self::postBetCurl($url, $post_data, $headers, $uid, $b_type=3); # 调试阶段先注释12.26
        $time2 = microtime(true);
        $time_consume = ($time2 - $time1).'s';
        $logArr = ['url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers, 'uid'=>$uid, 'tmpRst'=>$tmpRst, 'time_consume'=>$time_consume];
        Tool_Common::log('/zhongfa/'.__FUNCTION__, 'INFO', '众发下注', $logArr);
        $status = 0;
        if($tmpRst['success']){
            $status = 2;
            $tmpRst['status'] = $status;
            //# 获取方案号，记录id, 用于撤单
            $snInfo = self::getSn($row->uid, $row->tz_system_id, $row->lottery_type);// 用户信息 Array ( [sn] => 403054677338701312 [qihao] => 190412023 [snid] => 31724311|1,31724312|1 )
            //$snid = '{'.$snInfo['sn'].'}|'.count(json_decode($row->codes)); # 多次下单需要分开，多次撤单
            $sn = $snInfo['data']['rows'][0]['no'];
            $tmpRst['snid'] = $sn;
            $tmpRst['sn'] = $sn;
            if(!empty($repeats)){
                $post_data_1 = $post_data;
                $post_data_1['bet_number'] = implode(',', $repeats);
                $rst1 = self::postR($uid, $url, $post_data_1, $TzSystemsUsers->cookie, $TzSystemsUsers->ssc_domain, $_t, $TzSystemsUsers->user_agent);
                Tool_Common::log('/bet/repeatErrorBet', 'INFO', '幸运五下注1', [$uid, $url, $post_data_1, $TzSystemsUsers->cookie, $TzSystemsUsers->ssc_domain, $_t, $TzSystemsUsers->user_agent, $rst1]);
            }
            $betKey = BetService::buildLotteryBetKey($row->qihao, $row->plan_id, $row->bet_sort_key);
            $lockTime = BetService::getBetCacheTime($row->lottery_type);
            $m->set($betKey, 1, $lockTime);
        }elseif(!$tmpRst['success'] && in_array($tmpRst['code'], [302, 305, 307])){
            $status = 3; # 不可再次下注：302余额不足305已关盘307网盘账号停押
        }else{
            $betKey = BetService::buildLotteryBetKey($row->qihao, $row->plan_id, $row->bet_sort_key);
            $m->delete($betKey); # 失败之后可重新下注的情况解锁
        }
        if(in_array($tmpRst['code'], [309, 311])){
            $m = \Yii::$app->cache;
            $mkey_time_out = 'mkey_time_out_retry_key_'.$row->uid.'_'.$row->plan_id.'_'.$row->bet_sort_key;
            $val = $m->get($mkey_time_out);
            if($val==1){
                $status = 2;
                $tmpRst = ['Status'=>1, 'msg'=>'网络故障或者超时默认下注成功', 'data'=>['qihao'=>$row->qihao, 'uid'=>$uid, 'plan_id'=>$row->plan_id]];
                Tool_Common::log('repeatErrorBet_time_out', 'INFO', '幸运五下注', $tmpRst);
            }else{
                $m->set($mkey_time_out, 1, 60);
                return ['status'=>301, 'msg'=>'下注请求超时'];
            }
        }

        $tmpRst['bet_time'] = date('Y-m-d H:i:s');
        $tmpRst['time_consume'] = $time_consume;
        $row->status = $status;
        $row->post_desc = json_encode($tmpRst, 320);

        $logArr = ['id'=>$id, 'uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'headers'=>$headers, 'tmpRst'=>$tmpRst, 'time_consume'=>$time_consume];
        Tool_Common::log('repeatErrorBet', 'INFO', '众发下注', $logArr);

        $flag = $row->save();
        if(!$flag){
            return ['status'=>300, 'msg'=>$row->getFirstErrors()];
        }
        $rst['data']['bet_rst'] = $tmpRst;

        return $rst;
    }

    /**
     * @param $uid
     * @param $url
     * @param $post_data
     * @param string $cookie
     * @param string $ssc_domain
     * @param $_t
     * @param string $user_agent
     * @return array|mixed|string
     */
    public static function postR($uid, $url, $post_data, $cookie='', $ssc_domain='', $_t='', $user_agent=''){
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
            'Accept-Encoding: gunzip, deflate',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Cache-Control: max-age=0',
            'Connection: keep-alive',
            'Content-Length:'.strlen(http_build_query($post_data)),
            'Content-Type: application/x-www-form-urlencoded',
            'Cookie: '.$cookie,
            'Host: '.str_replace('http://', '', $ssc_domain),
            'Origin: '.$ssc_domain,
            'Referer: '.$ssc_domain.'/App/Index?_='.$_t,
            'Upgrade-Insecure-Requests: 1',
            $user_agent,
        ];

        $tmpRst = self::postBetCurl($url, $post_data, $headers, $uid, $b_type=4); # 调试阶段先注释12.26

        return $tmpRst;
    }

    /**
     * @desc 获取重复数据
     * @param $array
     * @return array
     */
    public static function fetchRepeatMemberInArray($array)
    {
        // 获取去掉重复数据的数组
        $unique_arr = array_unique($array);
        // 获取重复数据的数组
        $repeat_arr = array_diff_assoc($array, $unique_arr);
        return $repeat_arr;
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
     * @desc 获取正在进行的期号
     * @param string $uid
     * @param string $tz_system_id
     * @return array|string
     */
    public static function getActiveQihao($uid='', $tz_system_id='', $lottery_type = 8){
        $m = \Yii::$app->cache;
        $mkey = BetService::buildActiveQihaoKey($tz_system_id, $lottery_type);
        $qihao = $m->get($mkey);

        if($qihao) return $qihao;
        if(!$uid OR !$tz_system_id) return ['code'=>300, 'msg'=>'uid或者tz_system_id不能为空'];

        $m = \Yii::$app->cache;
        $mkey = 'getActiveQihao_'.$uid.'_'.$tz_system_id.'_'.$lottery_type;
        if($qihao = $m->get($mkey)) return $qihao;
        $data = self::userInfo($uid, $tz_system_id);
        Tool_Common::log('getActiveQihao', 'INFO', '获取正在进行的期号'.$lottery_type, ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'data'=>$data]);
        if(isset($data['success']) && $data['status'] == '30200') return $data;
        if(isset($data['success']) && isset($data['data']['nextVolIsBetting']) && isset($data['data']['nextVol']) && !empty($data['data']['nextVol'])){
            $qihao = $data['data']['nextVol'];
        }else{
            $qihao = '';
        }

        return $qihao;
    }

    /**
     * @desc 投注之前获取期号相关信息
     */
    public static function getQihaoInfo($uid='', $tz_system_id='', $lottery_type = 8){
        //$lottery = self::getSiteLottery($lottery_type);
        self::__init($uid, $tz_system_id);
        $urlArr = self::getTzSiteInfo($tz_system_id);//.'?'.http_build_query($post_data);

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        $_t = round(microtime(true) * 1000);
        $url = $urlArr['baseUrl'].'/drawno/GetCurrentPeriodStatus?_='.$_t;
        $headers = [
            "Accept: application/json, text/javascript, */*; q=0.01",
            "Accept-Encoding: gunzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Cookie: ".$TzSystemsUsers->cookie,
            "Referer: ".$TzSystemsUsers->ssc_domain."/App/Index?_=".$_t,
            "Host:".str_replace('www.','',self::$domain),
            $TzSystemsUsers->user_agent,
            "X-Requested-With: XMLHttpRequest",
        ];
        $data = self::getCurl($url, $headers, $uid);
        if(is_string($data) && strpos($data, '您当前使用的浏览器不支持') !== false){
            $roboot_id = Lucky5Service::getRobootIdByStr($data, $url);
            $cookie = $TzSystemsUsers->cookie;
            preg_match("/robot7=([^\r\n]*);Seven/i", $cookie, $matches);
            $new_cookie = str_replace($matches, $roboot_id.';Seven', $cookie);
            //p(['data'=>$data, 'old_cookie'=>$cookie, 'matches'=>$matches, 'new_cookie'=>$new_cookie]);
            $TzSystemsUsers->cookie = $new_cookie;
            $TzSystemsUsers->save();
        }

        $logArr = ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'headers'=>$headers,'data'=>$data];
        Tool_Common::log('getQihaoInfo','INFO','幸运五登陆前-2', $logArr);

        return $data;
    }

    /**
     * desc 网盘返回
     * @param string $content
     * @param string $url
     * @return string
     */
    public static function getRobootIdByStr($content = '', $url=''){
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
        if(strpos($roboot_id, '您当前使用的浏览器不支持') !== false){
            $tmp_roboot_id = explode('\';', $roboot_id);
            if($tmp_roboot_id[0]) $roboot_id = $tmp_roboot_id[0];
        }

        return $roboot_id;
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
     * @decription post请求根据，接受传递的header头
     * @param $url
     * @param array $post_data
     * @param array $headers
     * @param int $uid
     * @param int $b_type - 业务请求来源
     * @return array|mixed|string
     */
    public static function postBetCurl($url,$post_data = [],$headers=[], $uid = 0, $b_type=0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        if(!$timeout) $timeout = 30;
        $b_types = [0=>'默认', 1=>'取消订单', 2=>'心跳包', 3=>'自动下注任务', 4=>'重复下注', 5=>'手工下注'];

        //$cookie = dirname(__FILE__)."/cookie.txt";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 4);

        if(strpos($url, 'ww662889') !== false){
            //curl_setopt($ch, CURLOPT_USERAGENT, ['Chrome 42.0.2311.135']);
        }

        $poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect curl获取页面内容，不直接输出到页面，必需设置curl的CURLOPT_RETURNTRANSFER选项为1或true
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
            Tool_Common::log('httpPostError','INFO','httpPost请求-1-6', $logArr);
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
        }elseif(strpos($data, '登录') !== false){
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
        }elseif(strpos($data, '您当前使用的浏览器不支持cookie') !== false){
            $rstData = ["Status"=>0, 'code'=>310, 'msg'=>'您当前使用的浏览器不支持cookie'];
        }elseif(strpos($data, 'Bad Gateway') !== false OR strpos($data, 'Object moved') !== false){
            $rstData = ["Status"=>0, 'code'=>311, 'msg'=>'代理IP网络故障'];
        }elseif(strpos($data, 'Too Many Request') !== false){
            $rstData = ["Status"=>0, 'code'=>312, 'msg'=>'代理请求太频繁'];
        }elseif(strpos($data, 'ClearSession') !== false) {
            $rstData = ["Status" => 0, 'code' => 313, 'msg' => '请求重定向跳转'];
        }elseif(strpos($data, "\"Status\":1") !== false && strpos($data, "\"CompletedStatus\":1") !== false){ # json解析异常处理
            $rstData = ['Status'=>1, 'Data'=>['CompletedStatus'=>1, 'LackStatus'=>0]];
        }else{
            $rstData = json_decode($data, TRUE);
        }
        if($errno OR in_array($rstData['code'], [302, 303, 304, 305, 306, 310, 311])){
            if(isset($post_data['bet_number']) && strlen($post_data['bet_number'])>200) $post_data['bet_number'] = substr($post_data['bet_number'], 0, 300);
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers, 'rst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr];
            Tool_Common::log('httpPostError','INFO','httpPost请求-3', $logArr);
        }
        if(empty($rstData)){
            $rstData['data'] = $data;
            $rstData['post_data'] = $post_data;
        }
        $rstData['errno'] = $errno;
        $time_consume = ($end_time-$start_time).'s';

        $logArr = ['uid'=>$uid, 'url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers, 'rstData'=>$rstData, 'errno'=>$errno, 'time_consume'=>$time_consume];
        Tool_Common::log('/zhongfa/postBetCurl','INFO','httpPost'.$b_types[$b_type].'请求-5-2', $logArr);
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

        $balance = self::getBalance($uid,$tz_system_id, $r=2);

        $flag = $balance > 0 ? true : false;

        return (boolean)$flag;
    }

    /**
     * @decription
     * @param $url
     */
    public static function httpGetCurl($url,$headers=[], $uid = 0, $timeout=''){
        if(empty($timeout)){
            $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        }
        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ));
        $poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        $data = curl_exec($ch);
        $errno = curl_errno( $ch );
        $logArr = ['url'=>$url, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr]; p($logArr);
        if($errno){
            $logArr = ['url'=>$url, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno];
            Tool_Common::log('httpPostError','INFO','httpPost请求-1-7', $logArr);
        }

        curl_close($ch);
        $rst = json_decode($data, 320);

        return $rst;
    }

    /**
     * @decription
     * @param $url
     */
    public static function httpGet($url,$header=[], $uid = 0, $timeout=''){
        if(empty($timeout)){
            $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        }
        //if(strpos($url, 'GetPeriodsQuery')){ p([$url, $header]); }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        $poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);

        $data = curl_exec($ch);

        $errno = curl_errno( $ch );
        //$logArr = ['url'=>$url, 'headers'=>$header,'data'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr]; p($logArr);
        //if(strpos($url, 'GetInfoByName') !== false){ p(['header'=>$header, 'url'=>$url, 'rst'=>$data]); }
        if($errno>0){
            return ['status'=>300, 'errno'=>$errno];
        }
        curl_close($ch);
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
        curl_setopt($ch, CURLOPT_SSLVERSION, 4);

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $data = curl_exec($ch);
        $errno = curl_errno( $ch );
        //if($errno && strstr($url, 'BatchBet') OR strstr($url, 'MultipleBet')){
        //$logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$header, 'rst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr];p($logArr);
        curl_close($ch);
        if($errno){
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$header, 'rst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr];
            //p($logArr);
            Tool_Common::log('/zhongfa/'.__FUNCTION__.'_e','INFO','httpPost请求', $logArr);
            return '';
        }
        $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$header, 'rst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr];

        //if(strpos($url, 'betNumber')){ p(['url'=>$url, 'header'=>$header,'post_data'=>$post_data,'rstData'=>$data,curl_close($ch),$errno]); }
        Tool_Common::log('/zhongfa/'.__FUNCTION__,'INFO','宝岛众发POST请求', $logArr);
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
     * @return bool|array
     */
    public static function setPoxy($ch, $url='', $uid = 0){
        $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
        if(!$POXY_STATUS) return ['status'=>301, 'msg'=>'未开启IP代理开关']; # CURL 代理开关

        $is_open_poxy = PoxyIPService::isOpenPoxyIPUser($uid);
        if(!$is_open_poxy){
            return ['status'=>300, 'msg'=>'用户未开启IP代理[uid:'.$uid.']'];
        }

        #$poxy_addr = PoxyIPService::getPoxyIp($uid);
        $poxy_addr = ProxyBaseService::getCurrentValidProxyIp();
        Tool_Common::log('setPoxy', 'INFO', '设置全局代理4', ['url'=>$url, 'poxy_addr'=>$poxy_addr, 'uid'=>$uid]);

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

        return ['status'=>200, 'poxy_addr'=>$poxy_addr];
    }

}
