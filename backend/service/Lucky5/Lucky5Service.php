<?php
namespace backend\service\Lucky5;
/**
 * Created by PhpStorm.
 *
 * Date: 2018/02/06
 * Time: 09:40
 */

use backend\models\AgentUserBetLogs;
use backend\models\BetErrorPlansTask;
use backend\models\BettingRecords;
use backend\models\Num4Type;
use backend\models\SscKjData;
use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\models\User;
use backend\models\UserCustomPlans;
use backend\models\UserFollowData;
use backend\models\UserSysPlans;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\CurlService;
use backend\service\HN0898Service;
use backend\service\numbers\NumCodeService;
use backend\service\NumService;
use backend\service\plans\BetErrorPlansTaskService;
use backend\service\SscDataService;
use common\helpers\LotteryType;
use common\service\CaptchaCodeService;
use common\service\proxy\ProxyBaseService;
use common\tools\KjDataGet;
use common\tools\Tool_Common;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use yii\helpers\ArrayHelper;
use  yii;
use yii\helpers\Json;

class Lucky5Service { # 重庆7时彩登陆体系
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

    public static function __init($uid = 1, $tz_system_id = 2){
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
        if(!$TzSystemUser){
            Tool_Common::log('/lucky5/'.__FUNCTION__, 'ERR', '找不到站点信息', ['uid'=>self::$user_id, 'tz_system_id'=>$tz_system_id]);
            return [];
        }
        $baseUrl = $TzSystemUser->ssc_domain;
        if(empty($baseUrl)){
            Tool_Common::log('/lucky5/'.__FUNCTION__, 'ERR', 'ssc_domain为空', ['uid'=>self::$user_id, 'tz_system_id'=>$tz_system_id]);
        }
        self::$cookie = $TzSystemUser->cookie;
        \Yii::$app->params['baseUrl']  = $TzSystemUser->ssc_domain;
        \Yii::$app->params['domain']  = str_replace('http://','',str_replace('https', 'http', $TzSystemUser->ssc_domain));
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
     * @param $tz_system_user_id - 表lt_tz_systems_users.id
     * @return array
     */
    public static function synBalance($tz_system_user_id, $is_auto=1): array
    {
        $TzSystemsUsers = TzSystemsUsers::findOne($tz_system_user_id);
        $balance = self::getBalance($TzSystemsUsers->uid, $TzSystemsUsers->tz_system_id, $r=1, $is_auto);
        //d($balance);
        $msg = ['status'=>200, 'msg'=>'金额同步成功~','tz_system_user_id'=>$tz_system_user_id, 'balance'=>$balance, 'account'=>$TzSystemsUsers->account, 'username'=>$TzSystemsUsers->username];

        if(isset($balance['status']) && $balance['status'] == 3004){
            $TzSystemsUsers->desc = $balance['msg'];
        }

        $TzSystemsUsers->balance = $balance;
        $TzSystemsUsers->updated_at = time();
        if(!$TzSystemsUsers->save() OR $balance === false){
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
    public static function getBetLog($tz_type = 20, $plan_id=0, $hzArr=[]){
        $BetLog = AgentUserBetLogs::find()->where(['wp_record_id'=>$plan_id])->limit(1)->asArray()->one();
        if(!empty($BetLog) && $BetLog['bet_logs']){
            return $BetLog['bet_logs'];
        }

        $UserSysPlans = UserSysPlans::findOne($plan_id);
        if(!empty($UserSysPlans)){
            $tz_type = $UserSysPlans->tz_type;
            $hzArr = Json::decode($UserSysPlans->hz_Arr);
        }

        $randNum1 = rand(0, 9);
        $p1 = str_replace($randNum1, '', '0123456789');
        $randNum2 = rand(0, 9);
        $p2 = str_replace($randNum2, '', '0123456789');
        $randNum3 = rand(0, 9);
        $p3 = str_replace($randNum3, '', '0123456789');

        $randHz1 = rand(3, 10);
        $randHz2 = rand(29, 35);

        if(in_array($tz_type,[ 27, 30, 31, 33])) { # 二定
            # 二定位，定位置“取”:干=0123459，百=0235678
            $str = '[二定位]，定位置“[取]”：千=['.$p1.']，百=['.$p2.']';
        }elseif($tz_type == 29){
            $str = '[三定位]，定位置“[取]”：千=['.$p1.']，百=['.$p2.']，十=['.$p3.']，固定合分取值：第[3]位选中，第[4]位选中，内容：['.$p3.']；';
        }elseif($tz_type == 36){ # 二字现
            $str = '[二字现]，不定合分值(两数合)：['.$p1.']，包含“[取]”数：['.$p3.']';
        }elseif($tz_type == 17){ # 三字现
            $str = '[三字现]，不定合分值(两数合)：['.$p1.']，包含“[取]”数：['.$p2.']';
        }elseif($tz_type == 37){ # 四字现
            $str = '[四字现]，不定合分值(两数合)：['.$p1.']，包含“[取]”数：['.$p2.']';
        }else{ # 四定
            $str = '[四定位]，定位置“[取]”：千=['.$p1.']，百=['.$p2.']，十=['.$p3.']，合分值范围：['.$randHz1.'-'.$randHz2.']，三重“除”操作，四重“除”操作，双双重“除”操作';
        }
        if(empty($hzArr) OR in_array($tz_type, [19, 27, 34])){ # 导入方式
            return $str;
        }

        if($tz_type == 31){
            $desc = '五位二定';
        }else{
            $desc = LotteryType::LT_PLAY_WAY_OPTIONS[$UserSysPlans->playway]??'定位';
        }
        $desc .= '，';

        # 定位除、取
        if(!empty($hzArr['fixed_pos_sel'])){
            $desc .= ($hzArr['fixed_pos_sel']==NumService::EXCLUDE)?'定位置“除”：':'定位置“取”：';
            if(!empty($hzArr['p1'])){
                $desc .= '千='.$hzArr['p1'].'，';
            }
            if(isset($hzArr['p2']) && $hzArr['p2'] !== ''){
                $desc .= '百='.$hzArr['p2'].'，';
            }
            if(isset($hzArr['p3']) && $hzArr['p3'] !== ''){
                $desc .= '十='.$hzArr['p3'].'，';
            }
            if(isset($hzArr['p4']) && $hzArr['p4'] !== ''){
                $desc .= '个='.$hzArr['p4'].'，';
            }
            if(isset($hzArr['p5']) && $hzArr['p5'] !== ''){
                $desc .= '五='.$hzArr['p5'].'，';
            }
        }

        # 配数 除、取
        if(isset($hzArr['ps_sel']) && $hzArr['ps_sel']){
            $desc .= $hzArr['ps_sel']==NumService::PEI_SHU_OBTAIN ? ' 配数“取”:' : '配数“除”:';
            if(isset($hzArr['ps_1']) && $hzArr['ps_1'] !== ''){
                $desc .= '第1位：'.$hzArr['ps_1'].'，';
            }
            if(isset($hzArr['ps_2']) && $hzArr['ps_2'] !== ''){
                $desc .= '第2位：'.$hzArr['ps_2'].'，';
            }
            if(isset($hzArr['ps_3']) && $hzArr['ps_3'] !== ''){
                $desc .= '第3位：'.$hzArr['ps_3'].'，';
            }
            if(isset($hzArr['ps_4']) && $hzArr['ps_4'] !== ''){
                $desc .= '第4位：'.$hzArr['ps_4'].'，';
            }
        }

        # 定位合分，除取
        if(!empty($hzArr['fixed_pos_hefen_sel'])){
            $desc .= ($hzArr['fixed_pos_hefen_sel']==NumService::EXCLUDE)? '固定合分除值：' : '固定合分取值：';
            foreach (['hefen_pos1', 'hefen_pos2', 'hefen_pos3', 'hefen_pos4'] as $kHf=>$hefen_pos){
                $keyHf = $kHf + 1;
                if (!empty($hzArr[$hefen_pos])){
                    $hfPoss = explode(',', $hzArr[$hefen_pos]);
                    foreach ($hfPoss as $hfPos){
                        $desc .= "第{$hfPos}位选中，";
                    }
                    $desc .= '内容：'.$hzArr['hefen'.$keyHf].'；';
                }
            }
        }

        # 不定位合分:两数、三数
        if(!empty($hzArr['no_fix_hefen_pos']) && isset($hzArr['no_fix_hefen'])){ # no_fix_hefen_pos=1:两数、no_fix_hefen_pos=2:三数
            $desc .= ($hzArr['no_fix_hefen_pos'] == 2) ? ' 不定合分(三数合)：'.$hzArr['no_fix_hefen'] : '不定合分(两数合)：'.$hzArr['no_fix_hefen'];
            $desc .= '，';
        }

        # 和值
        if(!empty($hzArr['hz'])){
            $desc .= '合分值范围：'.$hzArr['hz'][0].'-'.$hzArr['hz'][count($hzArr['hz'])-1].'，';
        }
        if(!empty($hzArr['arise_in_sel']) && !empty($hzArr['arise_in'])){
            $desc .= ($hzArr['arise_in_sel']==NumService::EXCLUDE) ? '包含“除”数：' : '包含“取”数：';
            $desc .= $hzArr['arise_in'].'，';
        }

        # {"get_types":["1","2"],"remove_types":["4","5"],"get_hzs":["7","8","10"],"remove_hzs":["12","13","14"],"get_arises":"123","remove_arises":"456"}
        # 0.1、上奖取
        if(isset($hzArr['arise']) OR isset($hzArr['get_arises'])){
            if(isset($hzArr['get_arises'])) $hzArr['arise'] = $hzArr['get_arises'];
            $desc .= "上奖“取”：".$hzArr['arise'].'，';
        }
        # 0.2、上奖除 - 新
        if(!empty($hzArr['remove_arises'])){
            $desc .= "上奖“取”：".$hzArr['remove_arises'].'，';
        }

        # 1、双重
        if(isset($hzArr['type_2'])){
            $desc .= ($hzArr['type_2'] == 1)? '双重“取”操作' : '双重“除”操作';
            $desc .= '，';
        }
        # 2、三重
        if(isset($hzArr['type_3'])){
            $desc .= ($hzArr['type_3'] == 1)? '三重“取”操作' : '三重“除”操作';
            $desc .= '，';
        }
        # 3、四重
        if(isset($hzArr['type_4'])){
            $desc .= ($hzArr['type_4'] == 1)? '四重“取”操作' : '四重“除”操作';
            $desc .= '，';
        }
        # 4、双双重
        if(isset($hzArr['type_22'])){
            $desc .= ($hzArr['type_22'] == 1)? '双双重“取”操作' : '双双重“除”操作';
            $desc .= '，';
        }
        # 5、两兄弟
        if(isset($hzArr['type_2b'])){
            $desc .= ($hzArr['type_2b'] == 1)? '二兄弟“取”操作' : '二兄弟“除”操作';
            $desc .= '，';
        }
        # 6、三兄弟
        if(isset($hzArr['type_3b'])){
            $desc .= ($hzArr['type_3b'] == 1)? '三兄弟“取”操作' : '三兄弟“除”操作';
            $desc .= '，';
        }
        # 7.1、四兄弟
        if(isset($hzArr['type_4b'])){
            $desc .= ($hzArr['type_4b'] == 1)? '四兄弟“取”操作' : '四兄弟“除”操作';
            $desc .= '，';
        }
        # 7.2、对数
        if(isset($hzArr['log_sel'])){
            $desc .= ($hzArr['log_sel'] == 2)? '对数“取”数' : '对数“除”数';
            $desc .= $hzArr['log_1'].'，';
        }

        # 筛选位置：单
        if(!empty($hz_Arr['odd_sel']) && $hz_Arr['odd_pos']){
            $desc .= $hz_Arr['odd_sel']==NumService::POS_ODD_OBTAIN ? '单数“取”数：' : '单数“除”数：';
            foreach (explode(',', $hzArr['odd_pos']) as $pos){
                $desc .= '第'.$hz_Arr['odd_pos'].'位，';
            }
        }
        # 筛选位置：双
        if(!empty($hz_Arr['even_sel']) && $hz_Arr['even_pos']){
            $desc .= $hz_Arr['even_sel']==NumService::POS_ODD_OBTAIN ? '双数“取”数：' : '双数“除”数：';
            foreach (explode(',', $hzArr['even_pos']) as $pos){
                $desc .= '第'.$pos.'位，';
            }
        }
        # 筛选位置：大
        if(!empty($hz_Arr['big_sel']) && $hz_Arr['big_pos']){
            $desc .= $hzArr['even_sel']==NumService::POS_ODD_OBTAIN ? '大“取”数：' : '小“除”数：';
            foreach (explode(',', $hzArr['big_pos']) as $pos){
                $desc .= '第'.$pos.'位，';
            }
        }
        # 筛选位置：小
        if(!empty($hz_Arr['small_sel']) && $hz_Arr['small_pos']){
            $desc .= $hz_Arr['small_sel']==NumService::POS_ODD_OBTAIN ? '双数“取”数：' : '双数“除”数：';
            foreach (explode(',', $hzArr['small_pos']) as $pos){
                $desc .= '第'.$pos.'位，';
            }
        }

        return trim($desc, '，');
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
            $jsonArr = [
                "symbol" => "X",
                "isXian" => 0,
                "firstNumber" => "",
                "secondNumber" => "",
                "thirdNumber" => "",
                "fourthNumber" => "",
                "fifthNumber" => "",
                "numberType" => 40,
                "positionType" => 0,
                "positionFilter" => 0,
                "remainFixedFilter" => 0,
                "remainFixedNumbers" => [ ],
                "remainMatchFilter" => 0,
                "remainMatchNumbers" => [ ],
                "remainValueRanges" => [
                    30,
                    35
                ],
                "transformNumbers" => [ ],
                "upperNumbers" => [ ],
                "exceptNumbers" => [ ],
                "fixedPositions" => [
                    0,
                    0,
                    0,
                    0
                ],
                "symbolPositions" => [ ],
                "containFilter" => 0,
                "containNumbers" => [ ],
                "multipleFilter" => 0,
                "multipleNumbers" => [ ],
                "repeatTwoWordsFilter" => -1,
                "repeatThreeWordsFilter" => -1,
                "repeatFourWordsFilter" => -1,
                "repeatDoubleWordsFilter" => -1,
                "twoBrotherFilter" => -1,
                "threeBrotherFilter" => -1,
                "fourBrotherFilter" => -1,
                "logarithmNumberFilter" => -1,
                "logarithmNumbers" => [],
                "oddNumberFilter" => -1,
                "oddNumberPositions" => [
                    0,
                    0,
                    0,
                    0
                ],
                "evenNumberFilter" => -1,
                "evenNumberPositions" => [
                    0,
                    0,
                    0,
                    0
                ]
            ];
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
        $origin_codesData = $codesData;
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
            $tmpCodes = explode('@', $origin_codesData);
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
            //$tmpDatas = explode(',', $codesData);
            #if($uid == 14){
            #    # 固定去掉一个号码，用户需求
            #    $n = rand(0, 9);
            #    $origin_codesData = str_replace($n, '', $origin_codesData);
            #}
            $tmpDatas = explode(',', $origin_codesData);
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
                        $bet_no = 'XX'.$tmpData[$i].'XX';
                    }elseif ($p == '4'){
                        $bet_no = 'XXX'.$tmpData[$i].'X';
                    }elseif ($p == '5'){
                        $bet_no = 'XXXX'.$tmpData[$i];
                    }
                    $tmpArr[] = ['dict_no_type_id'=>(string)$dict_no_type_id, 'bet_no'=>$bet_no, 'bet_money'=>(string)$single];
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
            'Host: '. str_replace('http://', '', str_replace('https:', 'http', $TzSystemsUsers->ssc_domain)),
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
        try {
            $mkey = 'cancel_order_key_id_'.$id;
            $BettingRecords = BettingRecords::findOne($id);
            $uid = $BettingRecords->uid;
            $snid = $BettingRecords->snid;
            $sn = $BettingRecords->sn;
            self::__init($uid, $tz_system_id);
            $t1 = microtime(true);

            Lucky5Service::userInfo($uid, $tz_system_id);//p($userInfo);
            $t2 = microtime(true);

            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
            $qihao = HN0898Service::getQihao($BettingRecords->lottery_type);
            $counts = (int)round($BettingRecords->betting_money / $BettingRecords->single, 0);
            $post_data = [ 'ids'=>"{".$snid."}|".$counts, 'period_no' => $qihao];

            $_t = round(microtime(true) * 1000);
            $headers = [
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Accept-Encoding' => 'gunzip, deflate',
                'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
                'Connection' => 'keep-alive',
                'Content-Length' => strlen(http_build_query($post_data)),
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'Cookie' => $TzSystemsUsers->cookie,
                'Host' => str_replace('http://', '', str_replace('https:', 'http:', $TzSystemsUsers->ssc_domain)),
                'Origin' => $TzSystemsUsers->ssc_domain,
                'Referer' => $TzSystemsUsers->ssc_domain.'/App/Index?_='.$_t,
                'X-Requested-With' => 'XMLHttpRequest',
                //$TzSystemsUsers->user_agent,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
            ];

            $url = self::getTzSiteInfo(self::$tz_system_id, 'CANCEL_ORDER').'?'.http_build_query($post_data);

            $options = [
                RequestOptions::FORM_PARAMS => $post_data,
                RequestOptions::HEADERS => $headers,
            ];
            $response = (new Client())->request('POST', self::getTzSiteInfo(self::$tz_system_id, 'CANCEL_ORDER'), $options);
            $content = $response->getBody()->getContents();
            $rst = Json::decode($content);
            $t3 = microtime(true);

            if($rst['Status'] == 1 && strpos($rst['Data'], '退码成功')){
                BettingRecords::updateAll(['cancel_status'=>1], ['snid' => $snid]);
                $rst['status'] = 200;
            }

            $logArr = [
                'url'=>$url,
                'snid'=>$snid,
                'headers'=>$headers,
                'post_data'=>$post_data,
                'rst'=>$rst,
                'content' => $content,
                't1' => ($t2 - $t1).'s',
                't2' => ($t3 - $t2).'s',
            ];
            Tool_Common::log('cancelOrder','INFO','撤单记录', $logArr);
        }catch (\Exception $e){
            Tool_Common::log('cancelOrder','ERR','撤单记录-失败', ['id'=>$id, 'tz_system_id'=>$tz_system_id, 'err'=>$e->getMessage(), 'option'=>$options??[], 'responseRst'=>$rst??[]]);
        }

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
        $rst = self::userInfo($uid, $tz_system_id, $is_auto);
        self::__init($uid, $tz_system_id);
        $start_time = microtime(true);
        $balance = false;
        if(isset($rst['Status']) && $rst['Status'] == 1){
            $balance = $rst['Data']['credit_balance'];
        }

        return $balance;
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
            'sec-ch-ua: " Not;A Brand";v="24", "Google Chrome";v="113", "Chromium";v="113"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            $TzSystemsUsers->user_agent,
        ];
        $robot7_session_id = self::getSessionId($url, $headers, $uid);
        $headers[] = 'Cookie: '.$robot7_session_id;
        $cookie = self::curlGetSevenCookie($url, $headers, $uid);
        $cookieData = $cookie;
        #if($uid == 12) p([$robot7_session_id, $cookieData, $cookie]);
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

        BaseService::setPoxy($curl, $url, $uid); # 设置代理

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSLVERSION, BaseService::getSslVersionByUid($uid));

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

        BaseService::setPoxy($curl, $url, $uid); # 设置代理IP

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSLVERSION, BaseService::getSslVersionByUid($uid));

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
        Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '登陆', ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'rst'=>$rst]);
        // loginRemote返回 Status=1(成功) Status=2(失败)，检查大写Status
        if(isset($rst['status']) && $rst['status'] != 200){
            Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '登陆-错误', ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'rst'=>$rst]);
            return $rst;
        }
        if(isset($rst['Status']) && $rst['Status'] != 1){
            Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '登陆失败(Status!=1)', ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'rst'=>$rst]);
            $errorMsg = $rst['Data'] ?? '登录失败';
            return ['status'=>301, 'msg'=>$errorMsg];
        }
        # 第三步：同意
        if(isset($rst['Status']) && $rst['Status'] == 1){
            $rst = self::acceptAgreement($uid, $tz_system_id);
        }

        # 获取用户信息
        $rst = BaseService::synBalance($TzSystemsUsers->id); # 同步余额
        Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '获取用户信息', ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @desc 获取方案号
     * @return array
     */
    public static function getSn(){

        return ['sn'=>BetService::$true_bet_sn];
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

        BaseService::setPoxy($ch, $url, $uid); # 设置代理IP

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, BaseService::getSslVersionByUid($uid));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);

        $data = curl_exec($ch);
        //if(strpos($url, 'GetInfoByName') !== false){ p(['header'=>$header, 'url'=>$url, 'rst'=>$data]); }
        $errno = curl_errno($ch);
        if($errno>0) {
            $str = 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
            if(in_array($errno, [7, 28])){
                $poxy_addr = \common\service\proxy\ProxyBaseService::getCurrentValidProxyIp();
            }
            Tool_Common::log('/error/getCurl', 'ERR', 'getCurl获取', ['url'=>$url, 'postRst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr]);
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

        // 先去除HTML注释，避免匹配到注释中的旧公钥（f2/f3等盘口存在此问题）
        $htmlNoComment = preg_replace('/<!--.*?-->/s', '', $html);
        preg_match('/id="hd_publickey"\s+value="([^"]+)"/i', $htmlNoComment, $matches);
        $publicKey = $matches[1] ?? '';
        // 公钥为空或非base64格式则不使用RSA加密
        if(!empty($publicKey) && !preg_match('/^[A-Za-z0-9+\/=]+$/', $publicKey)){
            $publicKey = '';
        }
        if($publicKey){
            $m->set($mkey, $publicKey, 3600); # 缓存1小时
        }
        Tool_Common::log('/lucky5/'.__FUNCTION__, 'INFO', '获取RSA公钥', ['uid'=>$uid, 'publicKey'=>$publicKey ? 'found' : 'NOT FOUND']);

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
            // JSEncrypt行为：避免base64末尾出现==
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
            Tool_Common::log('/lucky5/loginRemote', 'INFO', 'RSA加密登录', ['uid'=>$uid, 'account'=>$TzSystemsUsers->account]);
        }else{
            $post_data['Account'] = $TzSystemsUsers->account;
            $post_data['Password'] = $TzSystemsUsers->password;
            Tool_Common::log('/lucky5/loginRemote', 'INFO', '明文登录(未找到RSA公钥)', ['uid'=>$uid]);
        }
        //p($post_data);

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
            'sec-ch-ua: " Not;A Brand";v="24", "Google Chrome";v="113", "Chromium";v="113"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            "Referer:".$TzSystemsUsers->ssc_domain,
        ];

        $data = self::httpPost($url,$post_data, $headers, $TzSystemsUsers->uid);
        $logArr = ['uid'=>$uid, 'account'=>$TzSystemsUsers->account, 'username'=>$TzSystemsUsers->username, 'tz_system_id'=>$tz_system_id, 'url'=>$url,'post_data'=>$post_data, 'headers'=>$headers,'data'=>$data];
        $desc = '';
        if(isset($data['Status']) && $data['Status'] == 2){
            $desc = $data['Data'];
        }
        $TzSystemsUsers->desc = $desc;
        $TzSystemsUsers->updated_at = time();
        $TzSystemsUsers->save();
        $data['username'] = $TzSystemsUsers->username;
        $data['account'] = $TzSystemsUsers->account;

        Tool_Common::log('loginRemote','INFO','Luck5登陆记录-2', $logArr);
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
            'sec-ch-ua: " Not;A Brand";v="24", "Google Chrome";v="113", "Chromium";v="113"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
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
    public static function userInfo($uid, $tz_system_id, $is_auto = 1){
        $m = \Yii::$app->cache;
        $mkey = 'get_userInfo_'.$uid.'_'.$tz_system_id.'_'.$is_auto;
        self::__init($uid, $tz_system_id);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        if($is_auto==1 && !empty($TzSystemsUsers->expire_time) && $TzSystemsUsers->expire_time<time()){
            return ['status'=>3004, 'msg'=>'账号已过期，请续费'];
        }
        if($is_auto==1 && $TzSystemsUsers->status==0){
            return ['status'=>3004, 'msg'=>'账号未激活'];
        }
        if($is_auto == 1 && (strpos($TzSystemsUsers->desc, '您的访问过于频繁') !== false OR strpos($TzSystemsUsers->desc, '用户名或密码不正确') !== false)){
            return ['status'=>300, 'msg'=>'您的访问过于频繁，请稍后再试 或 用户名或密码不正确'];
        }
        if(empty($TzSystemsUsers->cookie)){
            return ['status'=>302, 'msg'=>'cookie为空，不能正常获取用户信息'];
        }

        //p('xxxxxxxxxxx'.rand());
        $_t = (int)microtime(true) * 1000;
        $url = self::getTzSiteInfo($tz_system_id,'SSC_INDEX').'/Member/GetMemberPrint?_='.$_t;
        if(strpos(strtolower($url), 'http') === false OR is_array($url)) return ['status'=>300, 'msg'=>'无效url', 'key'=>'SSC_INDEX', 'url'=>$url];
        $headers = [
            "Accept: application/json, text/javascript, */*; q=0.01",
            "Accept-Encoding: gzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9",
            #"Connection: keep-alive",
            "Cookie: ".trim($TzSystemsUsers->cookie).' NOTICE_LOGIN_IN=1',
            //"Origin:".str_replace('www.','',self::$baseUrl),
            "Host:".str_replace('www.','',self::$domain),
            "Referer:".$TzSystemsUsers->ssc_domain.'/App/Index?_='.$_t,
            #'sec-ch-ua: " Not A; Brand";v="104", "Google Chrome";v="104", "Chromium";v="104"',
            #'sec-ch-ua-mobile: ?0',
            #'sec-ch-ua-platform: "Windows"',
            #'Sec-Fetch-Dest: empty',
            #'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            $TzSystemsUsers->user_agent,
            "X-Requested-With: XMLHttpRequest",
        ];

        $start_time = microtime(true);
        $uid = max($TzSystemsUsers->uid, $uid);
        $data = self::httpGet($url, $headers, $uid, $time_out=15);
        if(is_string($data) && strpos($data, '您当前使用的浏览器不支持') !== false){
            $robot_id = Lucky5Service::getRobotIdByStr($data, $url);
            $cookie = $TzSystemsUsers->cookie;
            preg_match("/robot7=([^\r\n]*); Seven/i", $cookie, $matches);
            $new_cookie = str_replace('robot7='.$matches[1], $robot_id, $cookie);
            //p(['data'=>$data, 'old_cookie'=>$cookie, 'matches'=>$matches, 'new_cookie'=>$new_cookie]);
            $TzSystemsUsers->cookie = $new_cookie;
            $TzSystemsUsers->save();
        }
        $end_time = microtime(true);
        $time_consume = ($end_time-$start_time).'s';
        //sleep(10);
        //HN0898Service::synBalance($TzSystemsUsers->id); # 同步余额
        $logArr = ['uid'=>$uid, 'account'=>$TzSystemsUsers->account, 'time_consume'=>$time_consume, 'username'=>$TzSystemsUsers->username, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'headers'=>$headers,'data'=>$data];
        $desc = '';
        // 修复：使用$data而不是$rst
        if(isset($data['Status']) && $data['Status'] != 1){
            $desc = json_encode($data, 320);
        }
        $TzSystemsUsers->desc = $desc;
        //p($logArr);
        Tool_Common::log('userInfo','INFO','幸运五星-用户信息-2', $logArr);

        // 检查是否有错误（如zstd压缩错误等）
        if(isset($data['status']) && $data['status'] != 200 && $data['status'] != 1){
            // 如果是错误响应，不缓存，直接返回
            return $data;
        }

        // 确保data是数组且包含正常数据时才设置余额
        if(is_array($data) && isset($data['Data']['credit_balance'])){
            $TzSystemsUsers->balance = $data['Data']['credit_balance'];
            $TzSystemsUsers->save();
        }

        $m->set($mkey, $data, 15);
        return $data;
    }

    /**
     * @desc 目前为计划任务正常下注入口 2021.05.23
     * @param $id
     */
    public function repeatErrorBet($id){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        Tool_Common::log('/repeatErrorBet/'.__FUNCTION__,'INFO','幸运-下注节点-1', ['task_id'=>$id]);
        $row = BetErrorPlansTask::findOne($id);
        $url = $row->bet_url;
        //$headers = json_decode($row->bet_headers); # 含有cookie，如果是重新登陆 cookie要变动，待处理
        $uid = $row->uid;
        $plan_id = $row->plan_id;
        $account = $row->account;
        $tz_system_id = $row->tz_system_id;
        $post_data = json_decode($row->post_datas, 320);
        $repeats = []; # 是否有重复号码
        if($row->lottery_type == 8 && in_array($row->uid, \Yii::$app->params['IMPORT_CODES_REPEAT_UIDS'])){
            $tmpDatas = explode(',', $post_data['bet_number']);
            $post_data['bet_number'] = implode(',', array_unique($tmpDatas));

            $repeats = Lucky5Service::fetchRepeatMemberInArray($tmpDatas);
        }
        $slow_seconds = BetService::getConfig('BET_SLOW_SECONDS'); # 下注延迟秒数设置

        $m = \Yii::$app->cache;
        $_t = round(microtime(true) * 1000);
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
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
            'Host: '.str_replace('http://', '', str_replace('https:', 'http:', $TzSystemsUsers->ssc_domain)),
            'Origin: '.$TzSystemsUsers->ssc_domain,
            'Referer: '.$TzSystemsUsers->ssc_domain.'/App/Index?_='.$_t,
            #'sec-ch-ua: " Not;A Brand";v="24", "Google Chrome";v="113", "Chromium";v="113"',
            #'sec-ch-ua-mobile: ?0',
            #'sec-ch-ua-platform: "Windows"',
            #'Sec-Fetch-Dest: empty',
            #'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'Upgrade-Insecure-Requests: 1',
            $TzSystemsUsers->user_agent,
        ];

        $mkey = 'repeatErrorBet_retry_'.$id;
        $open_retry = $m->get($mkey); # 重试锁开启开关
        Tool_Common::log('/repeatErrorBet/'.__FUNCTION__,'INFO','幸运-下注节点-2', ['task_id'=>$id, 'plan_id'=>$plan_id, 'account'=>$account]);

        try {
            $time1 = microtime(true);
            $tmpRst = BetService::requestBetWithRetry(function () use ($url, $post_data, $headers, $uid) {
                return self::postBetCurl($url, $post_data, $headers, $uid);
            }, [
                'platform' => 'lucky5',
                'task_id' => $id,
                'plan_id' => $plan_id,
                'account' => $account,
                'url' => $url,
            ]);
            $time11 = microtime(true);
            Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'INFO', '幸运-下注节点-3', ['task_id'=>$id, 'plan_id'=>$plan_id, 'account'=>$account, 'tmpRst'=>$tmpRst, 'time_consume'=>($time11-$time1).'s', 'slow_seconds'=>$slow_seconds]);
            sleep((int)$slow_seconds); # 下注延迟秒数
            $time2 = microtime(true);
            $status = 0;
            Tool_Common::log('/repeatErrorBet/xxx', 'INFO', '幸运下注x0', ['task_id'=>$id, 'plan_id'=>$plan_id, 'account'=>$account]);
            $TIME_OUT_RETRY = BetService::getConfig('TIME_OUT_RETRY'); # 超时重复下开关，幸运五
            $m = \Yii::$app->cache;
            $mkey_time_out = self::buildBetTimeOutPlanKey($row->uid, $row->plan_id, $row->bet_sort_key);
            Tool_Common::log('/repeatErrorBet/xxx', 'INFO', '幸运下注x1', [
                'task_id'=>$id,
                'plan_id'=>$plan_id,
                'account'=>$account,
                'mkey_time_out'=>$mkey_time_out,
                'time_out'=>$TIME_OUT_RETRY,
                'is_true'=>(boolean)(($tmpRst['Status'] ?? 0)==1),
                'SerialNo' => $tmpRst['Data']['SerialNo'] ?? '',
            ]);
            $tmpStatus = (int)($tmpRst['Status'] ?? 0);
            $tmpCode = (int)($tmpRst['code'] ?? 0);
            $tmpErrno = (int)($tmpRst['errno'] ?? 0);
            if($tmpStatus == 1){
                $status = 2;
                $tmpRst['status'] = $status; # 下注成功
                if(isset($tmpRst['Data']['SerialNo'])){
                    $snid = $tmpRst['Data']['SerialNo'];
                    $sn = $tmpRst['Data']['SerialNo'];
                }else{
                    //# 获取方案号，记录id, 用于撤单
                    $snInfo = self::getSn();// 用户信息 Array ( [sn] => 403054677338701312 [qihao] => 190412023 [snid] => 31724311|1,31724312|1 )
                    Tool_Common::log('/repeatErrorBet/xxx', 'INFO', '幸运下注x2', ['task_id'=>$id, 'plan_id'=>$plan_id, 'account'=>$account,'Status'=>$tmpRst['Status'], 'snInfo'=>$snInfo, 'tmpRst'=>$tmpRst, 'codes'=>json_decode($row->codes)]);
                    if(isset($snInfo['snid'])) $snInfo['snid'] = substr($snInfo['snid'],0,20).'...';
                    //$snid = '{'.$snInfo['sn'].'}|'.count(json_decode($row->codes)); # 多次下单需要分开，多次撤单
                    $snid = $snInfo['sn'];
                    $sn = $snInfo['sn'];
                }
                $tmpRst['snid'] = $snid;
                $tmpRst['sn'] = $sn;
                Tool_Common::log('/repeatErrorBet/xxx', 'INFO', '幸运下注x3', ['task_id'=>$id, 'plan_id'=>$plan_id, 'account'=>$account, 'tmpRst'=>$tmpRst, 'repeats'=>$repeats, 'is_repeats'=>(boolean)!empty($repeats)]);
                if(!empty($repeats)){
                    $post_data_1 = $post_data;
                    $post_data_1['bet_number'] = implode(',', $repeats);
                    $rst1 = self::postR($uid, $url, $post_data_1, $TzSystemsUsers->cookie, $TzSystemsUsers->ssc_domain, $_t, $TzSystemsUsers->user_agent); # 重复号码下注请求
                    Tool_Common::log('/bet/repeatErrorBet', 'INFO', '幸运五下注1', [$uid, $url, $post_data_1, $TzSystemsUsers->cookie, $TzSystemsUsers->ssc_domain, $_t, $TzSystemsUsers->user_agent, $rst1]);
                }
                Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'INFO', '幸运-下注节点-4', ['task_id'=>$id, 'plan_id'=>$plan_id, 'account'=>$account, 'tmpRst'=>$tmpRst, 'time_consume'=>($time11-$time1).'s', 'slow_seconds'=>$slow_seconds]);
            }elseif($tmpStatus == 0 && in_array($tmpCode, [309, 311, 312], true)){
                $m->set($mkey, 1, 300);
                $status = 4; # 远程盘口请求异常，已重试3次
                Tool_Common::log('/repeatErrorBet/'.__FUNCTION__.'_err', 'ERR', '幸运-下注节点-40', ['task_id'=>$id, 'plan_id'=>$plan_id, 'account'=>$account, 'tmpRst'=>$tmpRst]);
            }elseif($tmpStatus == 0 && in_array($tmpCode, [302, 303, 304, 305, 306, 307, 310, 313], true)){
                $status = 4; # 明确业务失败，不自动重推
                $tmpRst['status'] = $status;
                Tool_Common::log('/repeatErrorBet/'.__FUNCTION__.'_err', 'ERR', '幸运-下注节点-业务失败', ['task_id'=>$id, 'plan_id'=>$plan_id, 'account'=>$account, 'tmpRst'=>$tmpRst]);
            }elseif(($tmpRst['data'] ?? '') == "Proxy Connect Error"){
                $betKey = BetService::buildLotteryBetKey($row->qihao, $row->plan_id, $row->bet_sort_key, $id);
                $m->delete($betKey); # 失败之后可重新下注的情况解锁
                $status = 4; # 代理连接异常，已重试3次
            }else{
                $betKey = BetService::buildLotteryBetKey($row->qihao, $row->plan_id, $row->bet_sort_key, $id);
                $m->delete($betKey); # 失败之后可重新下注的情况解锁
                $status = 3;
                $tmpRst['status'] = $status;
                $tmpRst['msg'] = $tmpRst['msg'] ?? '幸运-下注节点-异常';
                Tool_Common::log('/repeatErrorBet/'.__FUNCTION__.'_err', 'ERR', '幸运-下注节点-未知失败', ['task_id'=>$id, 'plan_id'=>$plan_id, 'account'=>$account, 'tmpRst'=>$tmpRst]);
            }
            if($tmpErrno>0 OR in_array($tmpCode, [309,311,312], true)){ # 309,310,311   310有排查是已经换过代理IP,有待排查，为确保
                $status = 4; # 下注请求超时计划，后续可根据这个状态做是否重复下注处理，
                $m->set($mkey_time_out, 1, 60);
                $tmpRst['msg'] = $tmpRst['msg'] ?? '远程盘口请求异常，已重试3次';
            }

            $time_consume = ($time2 - $time1).'s';
            $tmpRst['bet_time'] = date('Y-m-d H:i:s');
            $tmpRst['time_consume'] = $time_consume;
            $tmpRst['proxy_ip'] = ProxyBaseService::getCurrentValidProxyIp(); # 获取当前可用的代理IP;
            $logArr = ['task_id'=>$id, 'uid'=>$uid, 'plan_id'=>$plan_id, 'account'=>$account, 'tz_system_id'=>$tz_system_id, 'url'=>$url, 'snInfo'=>$snInfo??[], 'tmpRst'=>$tmpRst, 'time_consume'=>$time_consume];
            Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'INFO', '幸运-下注节点-5', $logArr);

            $row->status = $status;
            $row->post_desc = json_encode($tmpRst, 320);
            $flag = $row->save();
            if(!$flag){
                throw_info(Json::encode($row->getFirstErrors()));
            }
            Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'INFO', '幸运-下注节点-6', ['task_id'=>$id, 'plan_id'=>$plan_id, 'msg'=>'任务状态修改成功']);
        }catch (\Exception $exception){
            Tool_Common::log('/repeatErrorBet/'.__FUNCTION__.'_err', 'ERR', '下注中途错误', ['task_id'=>$id, 'plan_id'=>$plan_id, 'account'=>$account, 'err_msg'=>$exception->getMessage()]);
            throw_info($exception->getMessage());
        }

        return $snid??'';
    }

    /**
     * @desc 重复超时计划的key
     * @param string $uid
     * @param string $plan_id
     * @param string $bet_sort_key
     * @return string
     */
    public static function buildBetTimeOutPlanKey($uid='', $plan_id='', $bet_sort_key=''){
        $mkey_time_out = 'mkey_time_out_retry_key_'.$uid.'_'.$plan_id.'_'.$bet_sort_key;
        return $mkey_time_out;
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

        $tmpRst = self::postBetCurl($url, $post_data, $headers, $uid); # 重复号码下注请求

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

        $status = KjDataGet::isCanBet($lottery_type);
        if(!$status) return ['code'=>301, 'msg'=>'非下注或者数据抓取时间'];

        $data = self::getQihaoInfo($uid, $tz_system_id);
        Tool_Common::log('getActiveQihao', 'INFO', '获取正在进行的期号'.$lottery_type, ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'data'=>$data]);
        if(isset($data['status']) && $data['status'] == '30200') return $data;
        if(isset($data['Status']) && isset($data['Data']) && isset($data['Data']['status']) && $data['Data']['status']==0){
            $qihao = $data['Data']['real_period_no'];
        }else{
            $qihao = '';
        }

        if(isset($data['Status']) && $data['Status'] == 1 && isset($data['Data']['real_period_no'])){
            $m->set($mkey, $qihao, 30);
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
        # http://f1.wfq66376.xyz/drawno/GetCurrentPeriodStatus?_=1632757019714
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
            $robot_id = Lucky5Service::getRobotIdByStr($data, $url);
            $cookie = $TzSystemsUsers->cookie;
            preg_match("/robot7=([^\r\n]*)==/i", $cookie, $matches);
            $new_cookie = str_replace('robot7='.$matches[1].'==', $robot_id, $cookie);
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
    public static function getRobotIdByStr($content = '', $url=''){
        if(strpos($content, 'Set-Cookie') !== false){
            preg_match("/Set\-Cookie:([^\r\n]*)/i", $content, $matches);
        }else{
            preg_match("/document.cookie\=\'([^\r\n]*)\'/i", $content, $matches);
        }
        $Arrs = explode('.', $url);
        $domain = $Arrs[1];
        if(strpos($matches[1], $domain) !== false){
            $robot_id = trim(str_replace('; path=/; domain=.'.$domain.'.xyz','', $matches[1]));
            Tool_Common::log('getSessionId', 'INFO', '获取session_id', ['url'=>$url, 'domain'=>$domain, 'robot_id'=>$robot_id, 'content'=>$content]);
        }
        if(strpos($robot_id, '您当前使用的浏览器不支持') !== false){
            $tmp_roboot_id = explode('\';', $robot_id);
            if($tmp_roboot_id[0]) $robot_id = $tmp_roboot_id[0];
        }

        return $robot_id;
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

        $diffArr = array_diff_assoc($codes, array_unique($codes)); # 重复号码
        $codesArr = array_chunk(array_unique($codes), $length);
        if(!empty($diffArr)){
            $codesArr[] = $diffArr;
        }

        return $codesArr;
    }

    /**
     * @desc 获取每次下注号码量
     * @return int|string
     */
    public static function getBetNumsPer($uid=0){
        $splitCodeUids = SystemConfig::findOne(['key'=>'need_split_code_uids'])->value;
        $nums = 10000;
        if(!empty($splitCodeUids)){
            $splitCodeUids = explode(',', str_replace('，', ',', $splitCodeUids));
            if(in_array($uid, $splitCodeUids)){
                $nums = SystemConfig::findOne(['key'=>'tz_nums_per'])->value;
            }
            //if(!$nums) $nums = 1650;
        }

        return $nums;
    }

    /**
     * @desc 批量号码拆解下注
     * @param $qihao
     * @param $plan
     * @param $codes - 1,2,3,4@2,3,4,5@5,6,7,8
     * @param int $is_task - 默认1，本地下注，如果服务器上下则传0
     * @return array
     */
    public function postBatchBet($qihao, $plan, $codes, $is_task=1){
        $tmpCodes = $codes;
        //$plan = UserSysPlans::findOne($plan_id);
        $plan_id = $plan->id;
        if($plan->tz_type == 22){ # 四定单双,codes格式：13579,13579,02468,13579@13579,13579,02468,02468@13579,02468,13579,13579
            $codesArr = self::getBetCodes($codes, $plan->single, $plan->playway);
        }elseif($plan->tz_type == 18){
            $codesArr = self::getBetCodes($codes, $plan->single, $plan->playway, $plan->uid);
        }else{
            $tmpCodes = str_replace(',', '', $tmpCodes);
            $codesArr = explode('@', $tmpCodes);
        }

        $playway = $plan->playway ?: 3;
        $single = $plan->single ?: 0.1;
        # 组数
        $count = count($codesArr);
        //p(['codesArr'=>$codesArr,'count'=>$count, 'codeHz'=>Json::decode($plan->hz_Arr)], 0);
        $hzArr = Json::decode($plan->hz_Arr);

        # 获取倍数倍数（bet_op_to_wp_singles）
        $opWpSingles = isset($hzArr['bet_op_to_wp_singles']) ? explode('-', $hzArr['bet_op_to_wp_singles']) : [];
        if(empty($opWpSingles[0])){
            $opWpSingle = 1;
        }else{
            $opWpSingle = floatval($opWpSingles[0]);
        }

        if($hzArr['bet_op_to_wp'] == UserSysPlans::BET_DIRECT_F){
            # 反向打盘口
            $query = Num4Type::find()->where(['NOT IN', 'code_n', $codesArr])->select('code_n');
            if($playway == 3){
                $query->andWhere(['code_type'=>4]);
            }else{
                if($playway == 1) { # 两字定
                    $query->andWhere(['code_type' => 2]);
                }elseif ($playway == 2){ # 三字定
                    $query->andWhere(['code_type' => 3]);
                }elseif ($playway == 4){ # 一字定
                    $query->andWhere(['code_type' => 1]);
                }
                $firstCodes = $codesArr[0];
                for ($i=0; $i<strlen($firstCodes); $i++){
                    if($firstCodes[$i] == 'X'){
                        $query->andWhere(['code_'.($i+1)=>'X']);
                    }
                }
            }
            $codesArr = $query->column();
        }

        # 应用倍数倍数：原始倍数 * 倍数倍数 = 理论倍数
        $opSingle = $single * $opWpSingle;

        # 向上取整到0.1（乘以10向上取整再除以10），因为盘口最低是1毛，没有具体到分
        $bet_single = ceil($opSingle * 10) / 10;

        # 根据玩法设置最小值
        if(in_array($playway, [2, 3]) && $bet_single<0.1){
            $bet_single = 0.1;
        }
        if($playway == 1){
            if($bet_single<1){
                $bet_single = 1;
            }else{
                $bet_single = (int)$bet_single;
            }
        }

        $single = $bet_single;
        //p(['count1'=>$count, 'count2'=>count($codesArr)]);

        $betNums = self::getBetNumsPer($plan->uid);
        $codesArrs = self::splitCodes($codesArr,  $betNums); # 2500一次

        $single = floatval($single);
        $tz_type = $plan->tz_type ? $plan->tz_type : 0;
        $lottery_type = $plan->lottery_type;
        //p(['playway'=>$playway, 'totalCount'=>count($codes), 'single'=>$single, 'qihao'=>$qihao, 'tz_type'=>$tz_type, 'buy_type'=>$plan->buy_type,'codes'=>$codes]);
        if(!self::$user_id) return ['status'=>400,'msg'=>'账号为空，不能识别用户'];

        $data = ['status'=>200, 'msg'=>$qihao.'操作成功!', 'time'=>date('Y-m-d H:i:s')];

        $url = self::getTzSiteInfo(self::$tz_system_id, 'MULBET_URL');//.'?'.http_build_query($post_data);
        $way = self::getWay($tz_type);
        $snInfo_sn = '';
        $snInfo_snid = '';
        $rst = [];
        foreach ($codesArrs as $key=>$tmpcodesArr){
            $bet_log = self::getBetLog($tz_type, $plan_id);
            if($playway == 4){ # 一字定
                $url = self::getTzSiteInfo(self::$tz_system_id, 'ORDER_TZ');//.'?'.http_build_query($post_data);
                $post_data = [
                    'bets' => json_encode($tmpcodesArr),
                    #'bets' => $tmpcodesArr,
                    'way' => $way,
                    'period_no' => $qihao,
                ];
            }else{ # 四定、三定
                if(in_array($plan->uid, \Yii::$app->params['IMPORT_CODES_KUAIYI_UIDS']) && in_array($tz_type, \Yii::$app->params['IMPORT_CODES_TYPES'])){
                    $url = self::getTzSiteInfo(self::$tz_system_id, 'ORDER_TZ');//.'?'.http_build_query($post_data);
                    $bets = self::getBetCodes($codes, $plan->single, $plan->playway, $plan->uid);
                    $post_data = [
                        'totalCount' => count($tmpcodesArr),
                        'totalBetMoney' => $single,
                        'bets' => json_encode($bets),
                        'way' => $way,
                        'period_no' => $qihao,
                        'bet_log' => '1234%201234%20',
                    ];

                }else{
                    $is_xian = in_array($tz_type, \Yii::$app->params['IS_XIAN']) ? 1 : 0;
                    // todo 防止盘口提示短时间重复注单，随机打乱
                    shuffle($tmpcodesArr);
                    $bet_codes = implode(',', $tmpcodesArr);
                    $post_data = [
                        'bet_number'=>$bet_codes,
                        'bet_money'=>$single,
                        'bet_way'=>$way,
                        'is_xian'=>$is_xian,
                        'is_iframe' => 1,
                        'number_type'=> LuckyBaseService::getNumType($tz_type, $playway, $tmpcodesArr),
                        //'guid'=>'3e1752e5-e455-4075-b657-0fd13b90d65d',
                        'bet_log'=>$bet_log,
                        'is_package' => 0,
                        'period_no'=>$qihao,
                        'operation_condition' => self::getOperationCondition(),
                    ];
                }
            }

            $_t = round(microtime(true) * 1000);
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$plan->uid, 'tz_system_id'=>self::$tz_system_id]);
            $need_money = count($tmpcodesArr) * $single;
            $left_money = $TzSystemsUsers->balance;
            if($key==0 && $need_money>$left_money){
                $msg = '第一次余额不足中断该用户后面所有下注';
                Tool_Common::log('less_bet_money', 'INFO', '下注之后', ['account'=>$plan->account, 'uid'=>$plan->uid, 'plan_id'=>$plan->id, 'single'=>$single, 'left_money'=>$left_money, 'need_money'=>$need_money, 'lottery_type'=>$lottery_type, 'msg'=>$msg]);
                //return ['status'=>303, 'msg'=>$msg];
            }
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
                'Accept-Encoding: gunzip, deflate, br',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Cache-Control: max-age=0',
                'Connection: keep-alive',
                'Content-Length:'.strlen(http_build_query($post_data)),
                //'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$TzSystemsUsers->cookie,
                'Host: '.str_replace('http://', '', str_replace('https:', 'http:', $TzSystemsUsers->ssc_domain)),
                'Origin: '.$TzSystemsUsers->ssc_domain,
                'Referer: '.$TzSystemsUsers->ssc_domain.'/App/Index?_='.$_t,
                'Upgrade-Insecure-Requests: 1',
                $TzSystemsUsers->user_agent,
            ];

            if(!$is_task){
                # 缓存锁
                # is_task:0 为直接下载
                $m = \Yii::$app->cache;
                $betKey = BetService::buildBetKey($plan->account, self::$tz_system_id, $lottery_type, $qihao, $plan_id).'_'.$key; # 分配下注后面加key
                if($betLock = $m->get($betKey)) return ['status'=>303, 'msg'=>'已经投注过了', 'key'=>$betKey];

                //if(in_array($tz_type, [20, 23, 25]) OR $bigFlag == 1){
                # 和值投注反应时间比较久，无需返回直接锁住
                $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间
                $m->set($betKey, 1, $time);
                //}
                # 真实投注
                $tmpRst = self::postBetCurl($url, $post_data, $headers, $TzSystemsUsers->uid);
                $data['bet_rst'] = $tmpRst;
            }else{
                # is_task:1 默认为任务表下载
                Tool_Common::log('afterPostBetCurl', 'INFO', '下注之后', ['account'=>$plan->account, 'uid'=>$plan->uid, 'plan_id'=>$plan->id, 'single'=>$single, 'left_money'=>$left_money, 'need_money'=>$need_money, 'lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'tmpcodesArr'=>count($tmpcodesArr)]);
                $taskId = BetErrorPlansTaskService::recordPlanTask($plan->uid, $plan->account, $plan_id, $qihao, $key, $tmpcodesArr, $tz_type, $url, $headers, json_encode($post_data,320), $single, count($tmpcodesArr)*$single, $playway,self::$tz_system_id, [], $lottery_type);
                $data['task_id'] = $taskId;
                $logArr1 = ['uid'=>self::$user_id, 'lottery_type'=>$lottery_type, 'key'=>$key, 'task_id'=>$taskId];
                Tool_Common::log('recordBetPlansTaskLog', 'INFO', '拆分记录下注号码至推送表', $logArr1);
            }

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

        $post_desc = $plan->hz_Arr;
        $post_desc = ($post_desc && is_json($post_desc))?Json::decode($post_desc):[];
        $pDesc = NumCodeService::getRandBetDesc($plan_id, $qihao);
        if(!empty($pDesc)){
            $post_desc['下注描述'] = $pDesc;
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
            'post_desc' => Json::encode($post_desc),
            'order_type'=>3, # 单双三字定
            'is_simulate' => 0,  // 是否模拟投注
            'single' => $single,  // 投注倍数
            'betting_money'=> round($totalmoney, 2),  // 投注金额
        ];
        $insertRst = BetService::_logRecords($insertData);
        self::$headers = [];

        if(strlen($post_data['bet_number'])>200) $post_data['bet_number'] = substr($post_data['bet_number'], 0, 200).'...';
        $logArr = [
            'uid'=>self::$user_id,
            'url'=>$url,
            'bigFlag'=>1,
            'postRst'=>$rst,
            //'insertData'=>$insertData,
            'insertRst'=>$insertRst,
        ];
        Tool_Common::log('/bet/'.__FUNCTION__,'INFO','幸运五批量插入记录-真实投注1', $logArr);

        return $data;
    }

    /**
     * @desc 写下注任务 - from 代理日志下注
     * @param $qihao
     * @param $codes
     * @param int $tz_type
     * @param float $single
     * @param int $playway
     * @param string $uid
     * @param int $lottery_type
     * @param int $is_task
     * @return array
     */
    public function pushIntoBetTask($qihao, $codes, $tz_type=25, $single=0.1, $playway=3, $uid='', $plan_id='', $lottery_type=DEFAULT_LOTTERY_TYPE, $is_task=1){
        $tmpCodes = $codes;
        $tmpCodes = str_replace(',', '', $tmpCodes);
        $codesArr = explode('@', $tmpCodes);

        if(empty($plan_id)) $plan_id = $uid.'8888';
        # 组数
        $count = count($codesArr);

        $betNums = self::getBetNumsPer($uid);
        $codesArrs = self::splitCodes($codesArr,  $betNums); # 2500一次

        if(!self::$user_id) return ['status'=>400,'msg'=>'账号为空，不能识别用户'];

        $data = ['status'=>200, 'msg'=>$qihao.'操作成功!', 'time'=>date('Y-m-d H:i:s')];

        $url = self::getTzSiteInfo(self::$tz_system_id, 'MULBET_URL');//.'?'.http_build_query($post_data);
        $way = self::getWay($tz_type);
        $snInfo_sn = '';
        $snInfo_snid = '';
        $rst = [];
        foreach ($codesArrs as $key=>$tmpcodesArr){
            $bet_log = self::getBetLog($tz_type, $plan_id);
            if($playway == 4){ # 一字定
                $url = self::getTzSiteInfo(self::$tz_system_id, 'ORDER_TZ');//.'?'.http_build_query($post_data);
                $post_data = [
                    'bets' => json_encode($tmpcodesArr),
                    #'bets' => $tmpcodesArr,
                    'way' => $way,
                    'period_no' => $qihao,
                ];
            }else{ # 四定、三定
                $is_xian = in_array($tz_type, \Yii::$app->params['IS_XIAN']) ? 1 : 0;
                $bet_codes = implode(',', $tmpcodesArr);
                $post_data = [
                    'bet_number'=>$bet_codes,
                    'bet_money'=>$single,
                    'bet_way'=>$way,
                    'is_xian'=>$is_xian,
                    'is_iframe' => 1,
                    'number_type'=> LuckyBaseService::getNumType($tz_type, $playway, $tmpcodesArr),
                    //'guid'=>'3e1752e5-e455-4075-b657-0fd13b90d65d',
                    'bet_log'=>$bet_log,
                    'is_package' => 0,
                    'period_no'=>$qihao,
                    'operation_condition' => self::getOperationCondition(),
                ];
            }

            $_t = round(microtime(true) * 1000);
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>self::$tz_system_id]);
            $need_money = count($tmpcodesArr) * $single;
            $left_money = $TzSystemsUsers->balance;
            if($key==0 && $need_money>$left_money){
                $msg = '第一次余额不足中断该用户后面所有下注';
                Tool_Common::log('less_bet_money', 'INFO', '下注之后', ['account'=>$TzSystemsUsers->account, 'uid'=>$TzSystemsUsers->uid, 'plan_id'=>$plan_id, 'single'=>$single, 'left_money'=>$left_money, 'need_money'=>$need_money, 'lottery_type'=>$lottery_type, 'msg'=>$msg]);
                //return ['status'=>303, 'msg'=>$msg];
            }
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
                'Accept-Encoding: gunzip, deflate, br',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Cache-Control: max-age=0',
                'Connection: keep-alive',
                'Content-Length:'.strlen(http_build_query($post_data)),
                //'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$TzSystemsUsers->cookie,
                'Host: '.str_replace('http://', '', str_replace('https:', 'http:', $TzSystemsUsers->ssc_domain)),
                'Origin: '.$TzSystemsUsers->ssc_domain,
                'Referer: '.$TzSystemsUsers->ssc_domain.'/App/Index?_='.$_t,
                'Upgrade-Insecure-Requests: 1',
                $TzSystemsUsers->user_agent,
            ];

            if(!$is_task){
                # 缓存锁
                $m = \Yii::$app->cache;
                $betKey = BetService::buildBetKey($TzSystemsUsers->account, self::$tz_system_id, $lottery_type, $qihao, $plan_id).'_'.$key; # 分配下注后面加key
                if($betLock = $m->get($betKey)) return ['status'=>303, 'msg'=>'已经投注过了', 'key'=>$betKey];

                # 和值投注反应时间比较久，无需返回直接锁住
                $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间
                $m->set($betKey, 1, $time);
                # 真实投注
                $tmpRst = self::postBetCurl($url, $post_data, $headers, $TzSystemsUsers->uid);
            }else{
                # 默认为任务表下载
                Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '下注之后', ['account'=>$TzSystemsUsers->account, 'uid'=>$TzSystemsUsers->uid, 'plan_id'=>$plan_id, 'single'=>$single, 'left_money'=>$left_money, 'need_money'=>$need_money, 'lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'tmpcodesArr'=>count($tmpcodesArr)]);
                BetErrorPlansTaskService::recordPlanTask($uid, $TzSystemsUsers->username, $plan_id, $qihao, $key, $tmpcodesArr, $tz_type, $url, $headers, json_encode($post_data,320), $single, count($tmpcodesArr)*$single, $playway,self::$tz_system_id, [], $lottery_type);
                $logArr1 = ['uid'=>self::$user_id, 'lottery_type'=>$lottery_type, 'key'=>$key];
                Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '拆分记录下注号码至推送表', $logArr1);
            }

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
            'account' => $TzSystemsUsers->username,
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

        if(strlen($post_data['bet_number'])>2000) $post_data['bet_number'] = substr($post_data['bet_number'], 0, 200);
        $logArr = ['uid'=>self::$user_id,'url'=>$url,'post_data'=>$post_data,'headers'=>self::$headers, 'bigFlag'=>1, 'postRst'=>$rst,'insertData'=>$insertData, 'insertRst'=>$insertRst];
        Tool_Common::log('bet','INFO','幸运五批量插入记录-真实投注2', $logArr);

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
        $headers[] = ['Accept: application/json, text/javascript, */*; q=0.01'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, BaseService::getSslVersionByUid($uid));

        if(strpos($url, 'ww662889') !== false){
            //curl_setopt($ch, CURLOPT_USERAGENT, ['Chrome 42.0.2311.135']);
        }

        BaseService::setPoxy($ch, $url, $uid); # 设置代理IP

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
            Tool_Common::log('httpPostError','INFO','httpPost请求-1-8', $logArr);
        }

        //if(strpos($url, 'ajax')){ p(['url'=>$url, 'header'=>$headers,'post_data'=>$post_data,'rstData'=>$data,'errno'=>$errno]); }
        curl_close($ch);
        if($data == 'ok'){
            Tool_Common::log('httpPostError','INFO','httpPost请求-1-9', $logArr);
            return 'ok';
        }
        $rstData = json_decode($data, true); # data : {"Status":1,"Data":{"CompletedStatus":1,"LackStatus":0}}
        //p(['url'=>$url, 'rstData'=>$rstData, 'data'=>$data, 'post_data'=>$post_data, 'headers'=>$headers, 'errno'=>$errno]);
        $time_consume = ($end_time - $start_time).'s';
        $logArr = ['uid'=>$uid, 'url'=>$url, 'headers'=>$headers, 'rstData'=>$rstData, 'SerialNo'=>$rstData['Data']['SerialNo']??'', 'errno'=>$errno, 'time_consume'=>$time_consume];
        Tool_Common::log('postBetCurl','INFO','httpPost下注结果', $logArr);
        if($rstData['Status'] == 1){
            return $rstData; // 成功
        }
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
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers, 'rst'=>$data, 'errno'=>$errno];
            Tool_Common::log('httpPostError','INFO','httpPost请求-3', $logArr);
        }
        if(empty($rstData)){
            $rstData['data'] = $data;
            $rstData['post_data'] = $post_data;
        }
        $rstData['errno'] = $errno;
        $time_consume = ($end_time-$start_time).'s';

        $logArr = ['uid'=>$uid, 'url'=>$url, 'headers'=>$headers, 'rstData'=>$rstData, 'errno'=>$errno, 'time_consume'=>$time_consume];
        Tool_Common::log('postBetCurl','INFO','httpPost下注请求-5-1', $logArr);
        //p(['url'=>$url, 'rstData'=>$rstData, 'data'=>$data, 'post_data'=>$post_data, 'headers'=>$headers, 'errno'=>$errno]);

        return $rstData;
    }

    /**
     * @desc 判断是否登录
     * @param $uid
     * @param $tz_system_id
     * @return bool
     */
    public static function isLogin($uid, $tz_system_id): bool
    {
        $balance = Lucky5Service::getBalance($uid,$tz_system_id, $r=2);

        return $balance > 0 OR $balance === 0 OR $balance === '0';
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

        // 移除Accept-Encoding中的br和zstd，避免服务器返回zstd压缩
        $header_modified = [];
        foreach($header as $h){
            if(stripos($h, 'Accept-Encoding') !== false){
                // 只保留gzip和deflate，移除br和zstd
                $h = preg_replace('/Accept-Encoding:\s*(.*)/i', 'Accept-Encoding: gzip, deflate', $h);
            }
            $header_modified[] = $h;
        }

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_modified);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        BaseService::setPoxy($ch, $url, $uid); # 设置代理IP

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //curl_setopt($ch, CURLOPT_SSLVERSION, BaseService::getSslVersionByUid($uid));
        curl_setopt($ch, CURLOPT_SSLVERSION, BaseService::getSslVersionByUid($uid));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER, 1);  // 获取响应头以检查Content-Encoding
        curl_setopt($ch, CURLOPT_ENCODING, ''); // 启用curl自动解压缩gzip/deflate

        $response = curl_exec($ch);

        // 分离响应头和响应体
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response_headers = substr($response, 0, $header_size);
        $data = substr($response, $header_size);

        $errno = curl_errno( $ch );
        //$logArr = ['url'=>$url, 'url'=>$url, 'headers'=>$header,'data'=>$data]; p($logArr);
        //if(strpos($url, 'GetInfoByName') !== false or $uid==17){ p(['header'=>$header, 'url'=>$url, 'rst'=>$data]); }
        $curl_error = curl_error($ch);

        // 检查响应头中的Content-Encoding
        if(stripos($response_headers, 'Content-Encoding: zstd') !== false || stripos($response_headers, 'content-encoding: zstd') !== false){
            // 如果服务器返回了zstd压缩，记录错误并尝试提示
            Tool_Common::log('/error/'.__FUNCTION__, 'ERR', '服务器返回zstd压缩，无法解压', [
                'url'=>$url,
                'headers'=>$header_modified,
                'response_headers'=>$response_headers,
                'data_preview'=>substr($data, 0, 200)
            ]);
            curl_close($ch);
            // 返回错误信息，提示需要zstd解压缩支持
            return ['status'=>500, 'err_msg'=>'服务器返回zstd压缩格式，当前环境不支持解压。请检查服务器IP限制或请求头配置。', 'raw_data_preview'=>substr($data, 0, 500)];
        }

        curl_close($ch);
        //p(['data'=>$data, 'errno'=>$errno]);
        if($errno) {
            return ['status'=>401, 'err_msg'=>'Curl error: '.$curl_error, 'errno'=>$errno];
        }

        if(!BaseService::is_json($data)){
            Tool_Common::log('/error/'.__FUNCTION__, 'ERR', 'get请求失败', ['url'=>$url, 'headers'=>$header_modified, 'errno'=>$errno, 'data'=>$data]);
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

        $poxy_addr = BaseService::setPoxy($ch, $url, $uid); # 设置代理IP

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, BaseService::getSslVersionByUid($uid));

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $data = curl_exec($ch);
        $errno = curl_errno( $ch );
        //if($errno && strstr($url, 'BatchBet') OR strstr($url, 'MultipleBet')){
        //$logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$header, 'rst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr];p($logArr);
        $curl_error = curl_error($ch);
        curl_close($ch);
        if($errno){
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$header, 'rst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr];
            //p($logArr);
            Tool_Common::log('httpPostError','INFO','httpPost请求', $logArr);
            return ['status'=>301, 'errno'=>$errno, 'curl_error'=>$curl_error];
        }

        //if(strpos($url, 'betNumber')){ p(['url'=>$url, 'header'=>$header,'post_data'=>$post_data,'rstData'=>$data,curl_close($ch),$errno]); }
        if($data == 'ok'){
            return 'ok';
        }
        $rstData = json_decode($data, TRUE);
        if(empty($rstData)){
            $rstData = ['status'=>401, 'msg'=>$data];
            Tool_Common::log('httpPostError','INFO','httpPost请求', ['status'=>301, 'errno'=>$errno, 'curl_error'=>$curl_error, 'data'=>$data]);
        }
        //p(['data'=>$data, 'rstData'=>$rstData, 'post_data'=>$post_data, 'header'=>$header]);

        return $rstData;
    }

    /**
     * 获取注单编号
     * @param Object $TzSystemUsers
     * @param string $newCookie
     * @return int|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function getSnId(Object $TzSystemUsers, string $newCookie='')
    {
        $client = new \GuzzleHttp\Client();
        $cookie  = $newCookie ? : $TzSystemUsers->cookie;
        $_t = microtime(true) * 1000;
        $url = $TzSystemUsers->ssc_domain.'/Member/GetMemberPrint?_='.$_t;
        $response = $client->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Accept-Encoding' => 'gzip, deflate, br, zstd',
                'Accept-Language' => 'zh-CN,zh;q=0.9',
                'Connection' => 'keep-alive',
                //'Cookie' => 'robot7=VfCMM/JIT2lLOlnK/mGhcx9Pd1BaMSU77dg0ToPTMcVvD9h4djiL5Kkz9atpaFb7jz2Qb4rNwXAUuXS/Rr8NEA==; ASP.NET_SessionId=ixx5zwntfyzm0isvwk1hiesx; Akamai_Cookie=2836400650.13685.0000; NOTICE_LOGIN_IN=1',
                'Cookie' => $cookie,
                'Host' => 'f3.w576yz32.xyz',
                'Referer' => $TzSystemUsers->ssc_domain.'/App/Index?_='.$_t,
                'Sec-Ch-UA' => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
                'Sec-Ch-UA-Mobile' => '?0',
                'Sec-Ch-UA-Platform' => '"Windows"',
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-origin',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'X-Requested-With' => 'XMLHttpRequest',
            ]
        ]);

        $body = $response->getBody()->getContents();

        if(str_contains($body, '您当前使用的浏览器不支持cookie')){
            //p($body);
            $robot_id = Lucky5Service::getRobotIdByStr($body, $url);
            $cookie = $TzSystemUsers->cookie;
            preg_match("/robot7=([^\r\n]*); Seven/i", $cookie, $matches);
            $new_cookie = str_replace('robot7='.$matches[1], $robot_id, $cookie);
            //p(['data'=>$data, 'old_cookie'=>$cookie, 'matches'=>$matches, 'new_cookie'=>$new_cookie]);
            $TzSystemUsers->cookie = $new_cookie;
            $TzSystemUsers->save();
            var_dump('333');
            return self::getSnId($TzSystemUsers);
        }
        $content = Json::decode($body);

        return [($content['Status'] == 1 and $content['Data']['serial_no']) ? $content['Data']['serial_no'] : 1, $content['Data']['credit_balance']];
    }
}
