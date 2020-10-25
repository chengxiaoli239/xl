<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/12/10
 * Time: 17:28
 */

namespace backend\service;

use backend\service\huiyuan\HuiYuanService5;
use backend\models\LotteryType;
use backend\models\SystemConfig;
use backend\models\User;
use backend\service\huiyuan\KuaiLe8Service;
use backend\service\Juhua\JuHuaBaseService;
use backend\service\Lucky5\Lucky5Service;
use backend\service\NineNine\NineNineBaseService;
use backend\service\NineNine\NineNineService6;
use common\kj\cqssc\CqsscKcw;
use Yii;
use backend\models\BettingRecords;
use backend\models\Num4Type;
use backend\models\SysPlansCodes;
use backend\models\TzSystems;
use backend\models\TzSystemsUsers;
use backend\models\TzTypes;
use backend\models\UserSysPlans;
use common\service\CommonService;
use common\tools\Tool_Common;
use common\tools\KjDataGet;
use yii\helpers\ArrayHelper;

abstract class BetService extends BaseBetService {
    protected $_nowTime = null;    // 当前时间戳
    protected $_operateTime = null;    // 当前时间戳的格式
    protected $_baseUrl = '';    // 当前时间戳的格式
    public static $maxQihaoArr = [1=>960, 2=>480, 3=>288, 4=>144, 5=>59, 6=>48, 7=>179, 8=>288]; # $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分

    protected function __construct() {
        parent::__construct();
    }

    /**
     * @desc 获取对象
     * @param $uid
     * @param $tz_system_id 表lt_tz_systems.id
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return HN0898Service|KuaiLe8Service|SevenService|XlService
     */
    public static function getBetObj($uid, $tz_system_id, $lottery_type = DEFAULT_LOTTERY_TYPE){
        //p([$uid, $tz_system_id, $lottery_type]);
        if(in_array($tz_system_id, [1,2])){
            # 1、0898投注、2、99彩票网
            if($lottery_type == 5){ # 0898体系重庆
                $BetService = new NineNineService6($uid, $tz_system_id);
            }elseif ($lottery_type == 6){
                $BetService = new NineNineService6($uid, $tz_system_id);
            }
        }elseif(in_array($tz_system_id, [3, 7, 9, 10])){
            # 3、重庆7时彩网：重庆7时彩、幸运五星彩
            if($lottery_type == 5){ # 7时彩重庆
                $BetService = new SevenService($uid, $tz_system_id);
            }elseif($lottery_type == 8){ # 幸运五星彩
                $BetService = new Lucky5Service($uid, $tz_system_id);
            }
        }elseif(in_array($tz_system_id, [4])){
            # 4、7天彩票网
        }elseif(in_array($tz_system_id, [5])){
            # 5、希腊网
            if($lottery_type == 3) { # 希腊网 5分彩
                $BetService = new XlService($uid, $tz_system_id);
            }
        }elseif(in_array($tz_system_id, [11])){
            if($lottery_type == 5){ # 重庆
                $BetService = new JuHuaBaseService($uid, $tz_system_id);
            }else{ # 北京快乐、台湾宾果
                $BetService = new JuHuaBaseService($uid, $tz_system_id);
            }
        }elseif(in_array($tz_system_id, [6])){
            # 6、会员网
            if($lottery_type == 5) { # 重庆时时彩
                $BetService = new HuiYuanService5($uid, $tz_system_id);
            }elseif($lottery_type == 7){ # 北京快乐8
                $BetService = new KuaiLe8Service($uid, $tz_system_id);
            }

        }

        return $BetService;
    }

    /**
     * @desc 获取投注号码
     * @param $system_type_id
     * @param $playway
     * @param $tz_type
     * @param $buy_type
     * @param float $single
     * @param $sel_same
     * @param array|string $hz_Arr
     * @return string
     */
    public static function getCodes($system_type_id, $tz_type, $buy_type, $sel_same = 1, $hz_Arr = [], $plan_id = ''){
        //p([$system_type_id, $tz_type, $buy_type, $sel_same, $hz_Arr]);
        switch ($system_type_id){ # system_type_id = lt_system_type.id
            case 1: # 重庆0898系统
            case 3: # 希腊彩系统
            case 4: # 北京快乐8
                $codes = BetService::getPlansAllCodesType1($tz_type, $buy_type, $sel_same, $hz_Arr, $plan_id);
                break;
            case 2: # 7时彩 重庆时时彩
            case 5: # 7时彩 幸运五星彩
                $codes = BetService::getPlansAllCodesType2($tz_type, $buy_type, $sel_same, $hz_Arr, $plan_id);
                break;
            default:
                $codes = BetService::getPlansAllCodesType2($tz_type, $buy_type, $sel_same, $hz_Arr, $plan_id);
                break;
        }

        return $codes;
    }

    /**
     * @desc 1.1 投注：投注之前业务逻辑判断
     * @param $qihao
     * @param string $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     */
    //public static function beforeBet($qihao, $tz_system_id, $playway, $account = 'gaozi2017', $lottery_type = 'ssc'){
    public static function beforeBet($qihao, $lottery_type = DEFAULT_LOTTERY_TYPE, $uid = ''){
        $m = \Yii::$app->cache;
        $rst = ['status'=>200, 'msg'=>'可以投注~'];
        switch ($lottery_type){
            case 1:
                $mkey = BetService::buildBeforeAndAfterBetKey($lottery_type, $qihao, $uid);
                $tzStatus = $m->get($mkey);

                # 判断当期开奖数据处理是否完成，未完成则不能下一期的投注
                if(!$tzStatus){
                    $rst = ['status'=>300, 'msg'=>'投注开关未开启，有未处理完成的数据~','mkey'=>$mkey,'tzStatus'=>$tzStatus];
                    Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/tzCron','INFO','不能投注', $rst);
                }
                break;
            default:
                break;
        }
        return $rst;
    }

    /**
     * @desc 用户投注基本入口
     */
    public static function bet(){
        $tzStatus = SystemConfig::findOne(['key'=>'tz_status'])->value;
        if(!$tzStatus) return ['status'=>300, 'msg'=>'投注开关未开启'];
        $lottery_types = StaticService::getLotteryTypes();
        foreach ($lottery_types as $lottery_type) {
            $qihao = HN0898Service::getQihao($lottery_type);
            $tzStatus = BetService::isCanBet($lottery_type);
            if (!$tzStatus) continue;
            //$where = ['AND',['=', 'lottery_type', $lottery_type], ['=', 'status', 1], ['>', 'uid', 0], ['=', 'is_parent', 1], ['=', 'is_test', 0]];
            $where = ['AND',['=', 'lottery_type', $lottery_type], ['=', 'status', 1], ['>', 'uid', 0], ['=', 'is_parent', 1]];
            $plans = UserSysPlans::find()->where($where)->orderBy(['tz_sort'=>SORT_ASC])->all();
            if ($plans) {
                $datas = [];
                foreach ($plans as $key => $plan) {
                    //return ['status'=>300, 'msg'=>'当前期投注任务已经完成~'];
                    if ($plan->children_plan_id > 0) {
                        $ids = explode(',', $plan->children_plan_id);
                    } else {
                        $ids[] = $plan->id;
                    }
                    foreach ($ids as $id) {
                        $tzRst[$id] = self::tzByPlanId($id);
                    }
                }
                $datas[] = ['qihao'=>$qihao, 'tzStatus'=>$tzStatus, 'lottery' => CqsscKcw::$lotteryNameArr[$lottery_type], 'tzRst'=>$tzRst];
                //p($datas);
                BetService::afterBetNow($plan->lottery_type, $qihao, $plan->uid); # 彩种投注结束锁
                $logArr[$lottery_type]['plans'] = $plans;
            }
            $count = count($plans);
            $logArr[$lottery_type]['ids'] = $ids;
            $logArr[$lottery_type]['qihao'] = $qihao;
            $logArr[$lottery_type]['msg'] = $count == 0 ? '无投注计划' : $count.'条计划';
        }
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/bet','INFO','用户真实投注', $logArr);

        return ['status'=>200, 'msg'=>'系统定制化投注处理完成~'];
    }

    /**
     * @desc 单用户下注
     */
    public static function betByUid($uid = 0){
        if(!$uid) return ['status'=>300, 'msg'=>'用户id不能为0'];
        $tzStatus = SystemConfig::findOne(['key'=>'tz_status'])->value;
        if(!$tzStatus) return ['status'=>300, 'msg'=>'投注开关未开启'];
        $lottery_types = StaticService::getLotteryTypes();
        foreach ($lottery_types as $lottery_type) {
            $hasActivePlan = CommonService::hasPlansActive($lottery_type);
            if(in_array($lottery_type, [8]) && !$hasActivePlan){
                continue;
            }
            $qihao = HN0898Service::getQihao($lottery_type);
            $tzStatus = BetService::isCanBet($lottery_type, $uid);
            Tool_Common::log('betByUid', 'INFO', '单用户下单', ['uid'=>$uid, 'lottery_type'=>$lottery_type, 'tzStatus'=>$tzStatus]);
            if (!$tzStatus) continue;
            $where = ['AND',['=', 'lottery_type', $lottery_type], ['=', 'status', 1], ['=', 'uid', $uid], ['=', 'is_parent', 1]];
            $plans = UserSysPlans::find()->where($where)->orderBy(['tz_sort'=>SORT_ASC])->all();
            if ($plans) {
                $datas = [];
                foreach ($plans as $key => $plan) {
                    $tzRst[$plan->id] = self::tzByPlanId($plan->id);
                }
                $datas[] = ['qihao'=>$qihao, 'tzStatus'=>$tzStatus, 'lottery' => CqsscKcw::$lotteryNameArr[$lottery_type], 'tzRst'=>$tzRst];
                BetService::afterBetNow($plan->lottery_type, $qihao, $plan->uid); # 彩种投注结束锁
                $logArr[$lottery_type]['plans'] = $plans;
                $count = count($plans);
                $logArr[$lottery_type]['qihao'] = $qihao;
                $logArr[$lottery_type]['msg'] = $count == 0 ? '无投注计划' : $count.'条计划';
            }
        }
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/bet','INFO','用户真实投注', $logArr);

        return ['status'=>200, 'msg'=>'系统定制化投注处理完成~'];
    }

   /**
     * @desc 判断当前期是否可以自动化投注
     * @param int $lottery_type
     * @param string $uid
     * @return bool|mixed
     */
    public static function isCanBet($lottery_type = DEFAULT_LOTTERY_TYPE, $uid = ''){

        $qihao = HN0898Service::getQihao($lottery_type);
        $pkey = BetService::buildBeforeAndAfterBetKey($lottery_type, $qihao, $uid);

        $m = \Yii::$app->cache;
        $status = $m->get($pkey);

        $time = date('H:i:s');
        if($lottery_type == 5){
            if(
                (\Yii::$app->params['ssc_kj_time_start'] <= $time && $time <= \Yii::$app->params['ssc_kj_time_end']) OR
                ('23:50:00' <= $time && $time <= '23:59:59') OR ('00:00:00' <= $time && $time <= '00:12:00')
            ){
                //$rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
                $status = false;
            }
        }elseif($lottery_type == 7){
            # 北京快乐8
            if('00:00:00'<$time && $time<'09:00:00'){
                $status = false;
            }
            /*
            $tz_systems_users_id = SystemConfig::findOne(['key'=>'kuaile8_get_kj_user_id'])->value;
            $TzSystemsUsers = TzSystemsUsers::findOne($tz_systems_users_id);
            $qihaoInfo = KuaiLe8Service::getPreTz($TzSystemsUsers->uid, $TzSystemsUsers->tz_system_id, $lottery_type);
            if($qihaoInfo['status'] != 200) $status = false;
            */
        }elseif($lottery_type == 6){ # 新疆
            if(\Yii::$app->params['LOTTERY_TYPE_6_STOP_START_TIME'] < $time && $time < \Yii::$app->params['LOTTERY_TYPE_6_STOP_END_TIME']){
                $status = false;
            }
        }elseif($lottery_type == 8){ # 幸运五星
            if('04:00:00' < $time && $time < '09:00:00'){
                $status = false;
            }
        }

        return $status;
    }

    /**
     * @desc 同步余额
     * @param $uid
     * @param $tz_system_id
     */
    public static function synBalance($uid, $tz_system_id){
        $TzSystemsUser = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        $rst = BaseService::synBalance($TzSystemsUser->id);
        /*
        if(in_array($tz_system_id, [1,2])){
            # 1、0898投注、2、99彩票网
            $rst = HN0898Service::synBalance($TzSystemsUser->id);
        }elseif(in_array($tz_system_id, [3, 7, 9])){
            # 3、重庆7时彩网
            if($tz_system_id == 3){
                $rst = SevenService::synBalance($TzSystemsUser->id);
            }else{
                $rst = LuckyBaseService::synBalance($TzSystemsUser->id);
            }
        }elseif(in_array($tz_system_id, [4])){
            # 4、7天彩票网
        }elseif(in_array($tz_system_id, [5])){
            # 5、希腊网
        }elseif(in_array($tz_system_id, [6])){
            # 6、会员网
            $rst = KuaiLe8Service::synBalance($TzSystemsUser->id);
        }
        */

        return $rst;
    }

    /**
     * @desc 投注完成之后业务处理
     * @param int $lottery_type
     * @return bool
     */
    public static function afterBet($qihao, $lottery_type = DEFAULT_LOTTERY_TYPE){
        if(!$qihao) return false;
        $m = \Yii::$app->cache;

        switch ($lottery_type){
            case 1:
            case 2:
            case 3:
            case 4:
                $next_qihao = KjDataGet::getNextQihaoByQihao($qihao, $lottery_type);
                $next_mkey = \Yii::$app->params['TZ_SWITCH_KEY'].'_'.$lottery_type.'_'.$next_qihao;
                //$next_mkey = \Yii::$app->params['TZ_SWITCH_KEY'].'_'.$next_qihao.'_'.$tz_system_id.'_'.$playway.'_'.$account;
                $pkey = \Yii::$app->params['TZ_SWITCH_KEY'].'_'.$lottery_type.'_'.$qihao;
                //$pkey = \Yii::$app->params['TZ_SWITCH_KEY'].'_'.$qihao.'_'.$tz_system_id.'_'.$playway.'_'.$account;

                $time = \Yii::$app->params['TZ_LOCK_TIME'];  # 4小时
                $m->set($next_mkey,0,$time); # 投注完成下一期的投注关闭
                $m->set($pkey,0,$time); # 投注完成当前期的投注关闭
                break;
            default:
                break;
        }

        return true;
    }

    /**
     * @desc 0898体系投注号码
     * @param $playway 1:二字定 2三字定 3四字定
     * @param $tz_type 投注类型(三字定):1大小单双三字定2大小三字定3单双三字定
     * $playway 为3的时候，四字定： $kArr = [0=>'所有', 1=>'一双三单、一单三双', 2=>'两双两单', 3=>'四双四单', 4=>'一单三双', 5=>'一双三单', 6=>'一单三双|四双', 7=>'一双三单|四单', 8=>'四双', 9=>'四单', 10=>'单数量', 11=>'双数量', 12=>'一单三双|四单', 13=>'一双三单|四双', 14=>'一单三双|四单|四双', 15=>'一双三单|四单|四双'];
     * @param $buy_type 默认正买
     * @param $limit int 默认获取注数
     * @param $sel_same 是否排除上一次中奖的相同组合, 主要针对四字定
     * @return string
     */
    public static function getPlansAllCodesType1($tz_type = 1, $buy_type = 1, $sel_same = 0, $codes_hz = '', $plan_id = ''){
        $playway = BetService::getPlaywayByTzType($tz_type);
        //p([$tz_type, $buy_type, $sel_same, $codes_hz, $playway]);
        $m = \Yii::$app->cache;
        $qihao = HN0898Service::getQihao();
        $mkey = 'getPlansAllCodesType1_'.$playway.'_'.$tz_type.'_'.$buy_type.'_'.$qihao;
        //if($codes = $m->get($mkey)) return $codes;

        switch ($playway){
            case 4: # 一字定
                // {"p1":"123","p2":"345","p3":"569","p4":"6589","p5":"1234"}
                $codesArr = NumService::getOneFixedCode(json_decode($codes_hz, true));
                break;
            case 10: # 一字定
                $codesArr = explode('@', $codes_hz);
                break;
            case 1: # 二字定

                if(in_array($tz_type, [30])) { # 二定快选
                    $codesArr = NumService::getCodesKuaiXuan(json_decode($codes_hz, true), $code_type = 2);
                }elseif(in_array($tz_type, [31])) { # 五位二定
                    $codesArr = NumService::getCodesKuaiXuan(json_decode($codes_hz, true), $code_type = 5);
                }elseif(in_array($tz_type, [33])) { # 二定号码翻倍切换
                    $codes_hz_arr = json_decode($codes_hz, true);
                    $codes_desc = $codes_hz_arr['status_val'] == 1 ? $codes_hz_arr['code1'] : $codes_hz_arr['code2'];
                    unset($codes_hz_arr['code1'], $codes_hz_arr['code2'], $codes_hz_arr['singles_key'], $codes_hz_arr['status_val']);
                    $codes_hz = NumService::getCodesHzByDesc($codes_desc);
                    $codes_hz = array_merge($codes_hz_arr, $codes_hz);
                    $codesArr = NumService::getCodesKuaiXuan($codes_hz, $code_type = 2);
                }
                break;
            case 2: # 三字定
                if(in_array($tz_type, \Yii::$app->params['IMPORT_CODES_TYPES'])) { # 导入方案
                    $codesArr = UserSysPlansService::getImportCodes($plan_id);
                }else{
                    if(in_array($tz_type, [29])){ # 三定快选
                        $codesArr = NumService::getCodesKuaiXuan(json_decode($codes_hz, true), $code_type = 3);
                    }elseif(in_array($tz_type, [32])) { # 三定切换
                        $codes_hz_arr = json_decode($codes_hz, true);
                        $codes_desc = $codes_hz_arr['status_val'] == 1 ? $codes_hz_arr['code1'] : $codes_hz_arr['code2'];
                        $codesArr = NumService::getCodesByDesc($codes_desc, $code_type = 3);
                        //p([$codes_hz_arr, $codesArr]);
                    }else{
                        $params = ['playway'=>$playway, 'tz_type'=>$tz_type, 'status'=>$buy_type];
                        $SysPlansCodes = SysPlansCodes::find()->where($params)->orderBy(['rand()' => SORT_DESC])->asArray()->all(); # ->limit($limit) 限制数量去掉
                        $codesArr = [];
                        foreach ($SysPlansCodes as $key=>$plan){
                            $codesArr[] = $plan['code'];
                        }
                    }
                }
                break;
            case 3: # 四字定
                $code_type = 4;
                if($tz_type == 21){ # 四定两兄弟
                    $codesArr = NumService::get2bCodeArr();
                }elseif(in_array($tz_type, \Yii::$app->params['IMPORT_CODES_TYPES'])) { # 导入方案 34
                    $codesArr = UserSysPlansService::getImportCodes($plan_id);
                }elseif($tz_type == 22){ # 四定单双
                    $codesArr = NumService::getCodesByDs(explode(',',$codes_hz));
                }elseif($tz_type == 23){ # 上奖
                    $codesArr = NumService::getCodesArise(explode(',',$codes_hz));
                }elseif($tz_type == 24){ # 直码
                    $codesArr = [];
                    $tmpArr = explode(',',$codes_hz);
                    foreach ($tmpArr as $arr){
                        $codesArr[] = $arr[0].','.$arr[1].','.$arr[2].','.$arr[3];
                    }
                }elseif(in_array($tz_type, [20, 25])){ # 过滤
                    $codesArr = NumService::getCodesKuaiXuan(json_decode($codes_hz, true));
                }elseif($tz_type == 26){ # 去除近xxx期号码
                    $codesArr = NumService::getNotLatelyCodes(json_decode($codes_hz, true));
                }elseif($tz_type == 28){ # 系统快捷
                    $codesArr = NumService::getCodesKuaiXuan(json_decode($codes_hz, true));
                }else{
                    $params = ['playway'=>$playway, 'tz_type'=>$tz_type];
                    $SysPlansCodes = SysPlansCodes::find()->where($params)->orderBy(['rand()' => SORT_DESC])->asArray()->all();
                    $codesArr = [];
                    foreach ($SysPlansCodes as $key=>$plan){
                        $mKeyRecord = 'SD_LAST_TIME_RECORD_'.$tz_type;
                        $LastTime = $m->get($mKeyRecord);
                        $code = $LastTime[0] % 2 == 0 ? '02468' : '13579';
                        $code .= $LastTime[1] % 2 == 0 ? ',02468' : ',13579';
                        $code .= $LastTime[2] % 2 == 0 ? ',02468' : ',13579';
                        $code .= $LastTime[3] % 2 == 0 ? ',02468' : ',13579';
                        if($sel_same == 0){
                            # 去除上次一样的单双
                            $plan['code'] = $plan['code'].'@';
                            $plan['code'] = str_replace($code, '', $plan['code']);
                        }

                        if($sel_same == 2){
                            # 随机去掉某一个
                            $tmpCodesArr = explode('@', $plan['code']);
                            $len = count($tmpCodesArr);
                            $k = rand(0, $len-1);
                            unset($tmpCodesArr[$k]);
                            $plan['code'] = implode('@', $tmpCodesArr);
                        }

                        $codesArr[] = trim($plan['code'], '@');
                    }
                }

                break;
            case 6:
                $codesArr = explode(',', $codes_hz);
                break;
        }

        $before_count=count($codesArr);
        # 反买号码获取
        if(!in_array($tz_type, [22]) && in_array($tz_type, \Yii::$app->params['can_change_buy_type']) && $buy_type == 0){ # 22 四定单双
            $codesArr = self::getInverseCodes($codesArr, $code_type);
        }
        //p(['buy_type'=>$buy_type, 'before_count'=>$before_count, 'after_count'=>count($codesArr), 'codesArr'=>$codesArr]);

        $codes = implode('@', $codesArr);
        //$m->set($mkey, $codes, 5*60);

        return $codes;
    }

    /**
     * @desc 和值号码读取
     * @param $playway
     * @param $tz_type
     * @param $codes_hz
     * @return string
     */
    public static function getHzCodes($tz_type, $codes_hz, $plan_id = ''){
        return self::getPlansAllCodesType1($tz_type, $buy_type = 1, $sel_same = 0, $codes_hz, $plan_id);
    }

    /**
     * @desc 7时彩
     * @param $playway
     * @param $tz_type 投注类型:1大小单双三字定2大小三字定3单双三字定
     * @param $buy_type
     * @param $limit int 默认获取注数
     * @return string
     */
    public static function getPlansAllCodesType2($tz_type = 1, $buy_type = 1, $sel_same = 1, $codes_hz = '', $plan_id = ''){
        $codes = self::getPlansAllCodesType1($tz_type, $buy_type, $sel_same, $codes_hz, $plan_id);

        return $codes;
    }

    /**
     * @desc 是否批量投注，号码格式跟其它不一样
     * @param int $tz_type
     */
    public static function isBigNumsBet($tz_type = 20){
        if(in_array($tz_type, [19, 20, 24, 25, 26, 28])){
            return true;
        }

        return false;
    }

    /**
     * @desc 获取最终的投注号码
     * @param $codesDatas  [0] => Array ( [0] => 1 [1] => 0 [2] => 0 [3] => 0 ) [1] => Array ( [0] => 1 [1] => 2 [2] => 0 [3] => 0 ) [2] => Array ( [0] => 1 [1] => 4 [2] => 0 [3] => 0 )
     * @param float $single
     * @param $dict_no_type_id
     * @return array
     * 返回格式：[{"bet_money":"0.1","bet_no":"1234","dict_no_type_id":11},{"bet_money":"0.1","bet_no":"1243","dict_no_type_id":11}]
     */
    public static function getEndCodes($codesDatas, $single = 0.1, $dict_no_type_id = 11){
        $codesArr = [];
        # 测试投注 start
        //$codesDatas = [ '0609','2514' ];
        # 测试投注 end

        foreach ($codesDatas as $codes){
            $tmp = [
                'bet_money' => $single,
                'bet_no' => $codes[0].$codes[1].$codes[2].$codes[3],
                'dict_no_type_id' => $dict_no_type_id,
            ];
            $codesArr[] = $tmp;
        }

        return $codesArr;
    }

    /**
     * @desc 生成投注key, 用做缓存锁
     * @param $qihao
     * @param $tz_system_id
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     * @param string $account
     * @param int $tz_type 三定：投注类型:1大小单双三字定2大小三字定3单双三字定    四定：详见staticServices::$kArr
     * @return string
     */
    public static function buildBetKey($account = 'gaozi2017', $tz_system_id, $lottery_type = DEFAULT_LOTTERY_TYPE, $qihao, $plan_id = 0){
        $mkey = \Yii::$app->params['TZ_SWITCH_KEY'].'_'.$account.'_'.$tz_system_id.'_'.$lottery_type.'_'.$qihao.'_'.$plan_id;

        return $mkey;
    }

    /**
     * @desc 计划任务列表立即投注
     * @param $id
     * @param $account
     */
   public static function userSysPlansTzNow($id, $uid){
       if(!$UserSysPlans = UserSysPlans::findOne(['id'=>$id, 'uid'=>$uid])){
           return ['status'=>300, 'msg'=>'找不到对应记录'];
       }
       $m = \Yii::$app->cache;
       $qihao = HN0898Service::getQihao($UserSysPlans->lottery_type);
       $mkey = 'userSysPlansTzNow_'.$uid.'_'.$id.'_'.$qihao.'_'.$UserSysPlans->playway;
       if($r = $m->get($mkey)) return ['status'=>300, 'msg'=>'已经投注过了，请稍后'];

       $rst = self::tzByPlanId($id, 0);
       $m->set($mkey, 1, 10);

       $rst['lottery_type'] = $UserSysPlans->lottery_type;

       return $rst;
    }

    /**
     * @desc 计划任务列表立即投注
     * @param $id
     * @param $uid
     */
   public static function reCalculateProfits($id, $uid){
       if(!$UserSysPlans = UserSysPlans::findOne(['id'=>$id, 'uid'=>$uid])){
           return ['status'=>300, 'msg'=>'找不到对应记录'];
       }
       $m = \Yii::$app->cache;
       $qihao = HN0898Service::getQihao($UserSysPlans->lottery_type);
       $mkey = 'reCalculateProfits_'.$uid.'_'.$id.'_'.$qihao.'_'.$UserSysPlans->playway;
       if($r = $m->get($mkey)) return ['status'=>300, 'msg'=>'已经投注过了，请稍后'];

       $codes_hz = json_decode($UserSysPlans->hz_Arr, true);
       $codes_hz['current_miss'] = 0; # 当前遗漏
       $codes_hz['singles_key'] = 0; # 倍数key
       $codes_hz['is_init'] = 1; # 是否最初

       $rstFlag = BettingRecords::updateAll(['is_profits_record'=>0], ['plan_id'=>$id]);
       $UserSysPlans->current_profits = 0.00;
       $UserSysPlans->hz_Arr = json_encode($codes_hz, 320);
       $UserSysPlans->save();

       $m->set($mkey, 1, 10);

       $rst['lottery_type'] = $UserSysPlans->lottery_type;
       $rst['flag'] = $rstFlag;

       return $rst;
    }

    /**
     * @desc 投注列表 - 立即投注
     */
    public static function tzNowBetRecord($uid, $id){
        $BettingRecords = BettingRecords::findOne($id);
        $TzSystems = TzSystems::findOne($BettingRecords->tz_system_id);

        switch ($TzSystems->system_type_id){
            case 1:
                $rst = HN0898Service::tzNowBetRecord($uid, $id);
                break;
            case 2:
                $rst = SevenService::tzNowBetRecord($uid, $id);
                break;
            case 3:
                break;
        }

        return $rst;
    }

    /**
     * @desc 立即反买
     * @param $account
     * @param $plan_id
     * @return array
     */
    public static function reverseTzNowBetRecord($uid, $id){
        $BettingRecords = BettingRecords::findOne($id);
        $TzSystems = TzSystems::findOne($BettingRecords->tz_system_id);

        switch ($TzSystems->system_type_id){
            case 1:
                $rst = HN0898Service::reverseTzNowBetRecord($uid, $id);
                break;
            case 2:
                $rst = SevenService::reverseTzNowBetRecord($uid, $id);
                break;
            case 3:
                break;
        }

        return $rst;
    }

    /**
     * @desc 根据计划id投注 - 立即投注
     * @param $planId
     * @param $isAuto 是否自动,默认自动
     * @return array
     */
    public static function tzByPlanId($planId, $isAuto = 1){
       if(!$plan = UserSysPlans::findOne($planId)){
            return ['status'=>300, 'msg'=>'找不到对应记录'];
       }
       $m = \Yii::$app->cache;
       $tz_sites = explode(',', trim($plan->tz_sites));
       $qihao = HN0898Service::getQihao($plan->lottery_type);
       $mkey = self::buildBetPlanIdKey($plan->account, $qihao, $plan->id);
       $rst = [];
       foreach ($tz_sites as $tz_system_id){
           $system_type_id = TzSystems::findOne($tz_system_id)->system_type_id;

           $status = UserService::accountIsExpire($plan->uid, $tz_system_id); # 账号是否过期
           if($status && $plan->account != 'gaozi2018'){
               Tool_Common::log('accountIsExpire', 'ERR', '账号过期提示', ['uid'=>$plan->uid, 'account'=>$plan->account, 'tz_system_id'=>$tz_system_id]);
               continue;
           }
           # 4、投注号码 codes
           $codes = self::getCodes($system_type_id, $plan->tz_type, $plan->buy_type, $plan->sel_same, $plan->hz_Arr, $planId);
           //p([$system_type_id, $plan->tz_type, $plan->buy_type, $plan->sel_same, $plan->hz_Arr, $codes]);

           $isAuto == 0 && BetService::beforeBetNow($plan->account, $tz_system_id, $plan->lottery_type, $qihao, $plan->id, $plan->uid); # 手动下注时，先删除缓存

           if($tzflag = $m->get($mkey)) continue; # ['status'=>300, 'msg'=>'已经投注过了~'];
           $time = BetService::getBetCacheTime($plan->lottery_type, $qihao); # 投注之后缓存时间
           $m->set($mkey, 1, $time);

           $is_test = $plan->is_test;
           if(in_array($plan->plan_type, [6, 8, 9])){ # 6中则投 8、9遗漏多少期投
               //j$flag = SscDataService::isZjBefore($planId); # 上期是否中奖，第一次下注认为是上期不中
               $flag = BetService::getIsBetTrue($planId);
               if(in_array($flag, [0, -1]) && $isAuto == 1){
                   $is_test = 1;
                   $sn = 'istest';
                   $snid = 'istest_id';
               }
           }

           if($is_test == 1 OR $plan->uid == 1){ # 模拟下注
               $tmpRst = self::_logRecordsByPlandId($planId, $qihao, $codes, $plan->lottery_type, $is_test = 1, $sn, $snid); # 直接记录表
           }else{ # 正式下注
               # 1、首先判断是否登录，否则登录之后再下注
               if(!$flag = self::isLogin($plan->uid, $tz_system_id)){
                   if(!$TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$plan->uid, 'tz_system_id'=>$tz_system_id, 'status'=>1])){
                       Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/tzByPlanId_isLogin','INFO','投注记录tzByPlanId', ['uid'=>$plan->uid,'account'=>$plan->account, 'msg'=>'账号已被禁用不能下注']);
                       continue;
                   }
                   $loginRst = BaseService::login($TzSystemsUsers->id);
                   Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/tzByPlanId_isLogin','INFO','投注记录tzByPlanId', ['loginRst'=>$loginRst]);
                   if($loginRst['status'] != 200) continue;
               }

               $logArr = ['uid'=>$plan->uid, 'planId'=>$planId, 'qihao'=>$qihao, 'time'=>$time, 'mkey'=>$mkey, 'account'=>$plan->account, 'tz_system_id'=>$tz_system_id, 'tz_sites'=>$tz_sites];
               Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/tzByPlanId','INFO','投注记录tzByPlanId', $logArr);
               # 5、投注请求
               $BetService = self::getBetObj($plan->uid, $tz_system_id, $plan->lottery_type);
               $tmpRst = $BetService->bet($qihao, $plan->id, $codes, $isAuto);

               BetService::synBalance($plan->uid, $tz_system_id);

               # 测试账号取消订单
               if($tmpRst['status'] == 200 && in_array($plan->account, \Yii::$app->params['test_account'])){
                   if($tmpBets = BettingRecords::findAll(['account'=>$plan->account, 'cancel_status'=>0, 'qihao'=>$qihao])){
                       foreach ($tmpBets as $tmpBet){
                           if($tmpBet->sn) BetService::cancelOrder($plan->uid, $tmpBet->id);
                       }
                   }
               }
           }
           $isAuto == 0 && BetService::afterBetNow($plan->lottery_type, $qihao, $plan->uid); # 手动无需锁
           $rst[] = $tmpRst;
       }
       $logArr = ['tz_sites'=>$tz_sites,'codes'=>$codes, 'postRst'=>$rst];
       Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/plan_bet','INFO','0898投注记录', $logArr);

       return $rst;
    }

    /**
     * @desc 获取计划是否投注为真实
     * @param string $plan_id
     * @return bool 0不中奖1中奖 -1最初添加计划未投注，可当作未中奖，等同于0
     */
    public static function getIsBetTrue($plan_id = ''){

        $flag = SscDataService::isZjBefore($plan_id); # 上期是否中奖，第一次下注认为是上期不中 中则投
        $plan = UserSysPlans::findOne($plan_id);
        if(in_array($plan->plan_type, [8, 9])){ # 遗漏多少期启投
            $flag = 0;
            $codes_hz = json_decode($plan->hz_Arr, true);
            if($codes_hz['current_miss']>=$codes_hz['bet_while_miss']){
                $flag = 1;
            }
        }

        return (int)$flag;
    }

    /**
     * @desc 立即投注之前清除缓存锁
     * @param $account
     * @param $tz_system_id
     * @param $qihao
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     * @param $plan_id
     */
    public static function beforeBetNow($account, $tz_system_id, $lottery_type = DEFAULT_LOTTERY_TYPE, $qihao, $plan_id = 0, $uid = ''){
        $m = \Yii::$app->cache;
        $mkey = BetService::buildBetKey($account, $tz_system_id, $lottery_type, $qihao, $plan_id);
        $m->delete($mkey);

        $pkey = BetService::buildBeforeAndAfterBetKey($lottery_type, $qihao, $uid);
        $m->delete($pkey);

        $tzPlanIdmkey = self::buildBetPlanIdKey($account, $qihao, $plan_id);
        $m->delete($tzPlanIdmkey);
    }

    /**
     * @desc 立即投注之后设置缓存锁
     * @param $qihao
     * @return bool
     */
    public static function afterBetNow($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao, $uid = ''){
        $m = \Yii::$app->cache;

        $pkey = BetService::buildBeforeAndAfterBetKey($lottery_type, $qihao, $uid);

        $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间
        $rst = $m->set($pkey, 0, $time);

        return $rst;
    }

    /**
     * @desc 生成投注前后缓存key
     * @param int $lottery_type
     * @param $qihao
     * @return string
     */
    public static function buildBeforeAndAfterBetKey($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao, $uid){

        $pkey = \Yii::$app->params['TZ_SWITCH_KEY'].'_'.$lottery_type.'_'.$qihao.'_'.$uid;

        return $pkey;
    }

    /**
     * @desc 计划投注的key
     * @param string $account
     * @param string $qihao
     * @param string $plan_id
     * @return string
     */
    public static function buildBetPlanIdKey($account = '', $qihao = '', $plan_id =''){
        $key = 'tzByPlanId_account_'.$account.'_qihao_'.$qihao.'_plan_id_'.$plan_id;

        return $key;
    }

    /**
     * @desc 投注计划缓存key
     * @param int $lottery_type
     * @param $qihao
     * @return string
     */
    public static function buildPlanSwitchKey($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao){

        $pkey = \Yii::$app->params['PLAN_SWITCH_KEY'].'_'.$lottery_type.'_'.$qihao;

        return $pkey;
    }

    /**
     * @description 更新计划表状态
     * @param $id
     * @param $account
     * @return bool|mixed
     */
    public static function updateSysPlansBuyType($id, $status, $account)
    {
        $m = \Yii::$app->cache;
        $mkey = 'updateSysPlansBuyType_'.$id.'_'.$status;
        if($rst = $m->get($mkey)) return false;

        $UserSysPlans = UserSysPlans::findOne(['account' => $account, 'id' => $id]);
        $UserSysPlans->buy_type = (int)$status;
        UserSysPlansService::switchBuyType($id);

        $m->set($mkey, 1, 3);

        $rst = $UserSysPlans->save(false);

        return $rst;
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
            'playway_name'=> self::lotteryClass($data['playway']),  // 投注名称
            'uid' => $data['uid'],  // 投注用户id
            'buy_type'=> $data['buy_type'],  // 购买方向类型
            'codes' => $data['codes'],  // 投注号码
            'qihao' => (string)$data['qihao'],  // 投注期号
            'plan_id' => $data['plan_id'],  // 计划id
            'single' => $data['single'],  // 投注期号
            'betting_money'=> $data['betting_money'],  // 投注金额
            'tz_system_id'=> $data['tz_system_id'],  // 投注系统tz_systems .id
            'lottery_type'=> $data['lottery_type'],  // 彩种
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

        if(!$rst){
            Tool_Common::log('logRecords', 'INFO', '记录投注表', ['msg'=>$bettingRecords->getErrors()]);
            return ['status'=>200,'msg'=>current($bettingRecords->getErrors())];
        }

        return $rst;
    }

    /**
     * @description  撤单
     * @param $uid
     * @param $snid
     * @param $tz_system_id
     * @return mixed|string
     */
    public static function cancelOrder($uid, $bet_id){
        $BettingRecords = BettingRecords::findOne(['id'=>$bet_id, 'uid'=>$uid]);
        if(!$BettingRecords) return ['status'=>300, 'msg'=>'非法操作'];
        $m = \Yii::$app->cache;
        $mkey = 'ORDER_CANCEL_'.$bet_id;
        if($isCanceled = $m->get($mkey)) return ['status'=>300, 'msg'=>'正在退单请稍等~'];

        $tz_system_id = $BettingRecords->tz_system_id;
        $lottery_type = $BettingRecords->lottery_type;
        if(in_array($tz_system_id, [1,2])){
            # 1、0898投注、2、99彩票网
            if($lottery_type == 5){ # 0898体系重庆
                $rst = NineNineBaseService::cancelOrder($bet_id, $BettingRecords->tz_system_id);
            }elseif ($lottery_type == 6){
                $rst = NineNineBaseService::cancelOrder($bet_id, $BettingRecords->tz_system_id);
            }
        }elseif(in_array($tz_system_id, [3, 7, 9])){
            # 3、重庆7时彩网
            if($lottery_type == 5){ # 7时彩重庆
                $rst = SevenService::cancelOrder($bet_id, $BettingRecords->tz_system_id);
            }elseif ($lottery_type == 8){
                $rst = Lucky5Service::cancelOrder($bet_id, $BettingRecords->tz_system_id);
            }
        }elseif(in_array($tz_system_id, [4])){
            # 4、7天彩票网
        }elseif(in_array($tz_system_id, [5])){
            # 5、希腊网
            if($lottery_type == 3) { # 希腊网 5分彩
            }
        }elseif(in_array($tz_system_id, [11])){ # 菊花网
            if($lottery_type == 5){
                $rst = JuHuaBaseService::cancelOrder($bet_id, $BettingRecords->tz_system_id);
            }else{
                $rst = JuHuaBaseService::cancelOrder($bet_id, $BettingRecords->tz_system_id);
            }
        }elseif(in_array($tz_system_id, [6])){
            # 6、会员网
            if($lottery_type == 5) { # 重庆时时彩
                $rst = HuiYuanService5::cancelOrder($bet_id, $BettingRecords->tz_system_id);
            }elseif($lottery_type == 7){ # 北京快乐8
            }

        }

        $rst['lottery_type'] = $lottery_type;
        if($rst['status'] == 200){
            $m->set($mkey, 1, 5);
        }


       return $rst;
    }

    /**
     * @desc 是否登录，判断标准，能正常获取用户信息、余额
     * @param int $uid
     * @param $tz_system_id
     * @return HuiYuanService5|KuaiLe8Service|LuckyBaseService|NineNineService6|SevenService|XlService
     */
    public static function isLogin($uid, $tz_system_id){
        if(in_array($tz_system_id, [1,2])){
            # 1、0898投注、2、99彩票网
            $flag = NineNineService6::isLogin($uid, $tz_system_id);
        }elseif(in_array($tz_system_id, [3, 6, 7, 9, 10])){
            # 3、3重庆7时彩网：重庆7时彩、幸运五星彩、6会员网
            $flag = Lucky5Service::isLogin($uid, $tz_system_id);
        }elseif(in_array($tz_system_id, [4])){
            # 4、7天彩票网
        }elseif(in_array($tz_system_id, [5])){
            # 5、希腊网
            $flag = XlService::isLogin($uid, $tz_system_id);
        }elseif(in_array($tz_system_id, [11])){
            # 11、菊花网
            $flag = JuHuaBaseService::isLogin($uid, $tz_system_id);
        }

        return (boolean)$flag;
    }

    /**
     * @desc 同步用户所有站点余额
     * @param $uid
     */
    public static function synUserAllBalance($uid){

        $TzSystemsUsers = TzSystemsUsers::findAll(['status'=>1, 'uid'=>$uid]);
        foreach ($TzSystemsUsers as $TzSystemsUser){
            $rst = self::synBalance($uid, $TzSystemsUser->tz_system_id);
        }

        return $rst;
    }

    /**
     * @decription 投注
     * @param $cookie
     * @param $playway
     * @param $code
     * @param $single
     * @param $qihao
     * @return mixed
     */
    public function betting($playway, $code, $single, $qihao){

    }

    public static function getData(){

    }

    public static function doCurl($url, $betData){
        $rst = CurlService::httpPost($url, $betData);

        return $rst;
    }

    # 1、投注方式，playway

    # 2、投注站点

    # 3、号码，根据投注站点生成站点不同的号码格式（0898、七星娱乐）

    # 4、POST请求

    #


    /**
     * @desc 获取7时彩的投注类型id
     * @param $playway
     * @param $tz_type
     * @return array|mixed
     */
    public static function get_dict_no_type_id($position = '1,2,3,4'){
        $data = [
            '1,2' => 1,
            '1,3' => 2,
            '1,4' => 3,
            '3,1' => 4,
            '2,3' => 5,
            '3,4' => 6,
            '1,2,3' => 7,
            '1,2,4' => 8,
            '1,3,4' => 9,
            '2,3,4' => 10,
            '1,2,3,4' => 11,
        ];

        if(!isset($data[$position])) return $data;

        return $data[$position];
    }

    /**
     * @desc 根据tz_type获取playway
     * @param $tz_type
     * @return int
     */
    public static function getPlaywayByTzType($tz_type){

        $playway = TzTypes::findOne(['type'=>$tz_type])->playway;
        !$playway && $playway = 3;

        return $playway;
    }


    /**
     * @desc 计算遗漏获取数据类型 1取本表数据做变更0扫表重新计算数据（比如：遗漏、数量等统计）
     * @return int
     */
    public static function getConfig($key = 'getDataType'){
        $m = \Yii::$app->cache;
        $mkey = 'CONFIG_TYPE_'.$key;
        //if($val = $m->get($mkey)) return $val;

        $val = SystemConfig::findOne(['key'=>$key])->value;
        $m->set($mkey, $val, \Yii::$app->params['BASE_DATA_CACHE_TIME']);

        return $val;
    }


    /**
     * @desc 获取投注缓存时间，一般为开奖时间频率
     * @param int $lottery_type
     * @param $qihao
     * @return float|int|string
     */
    public static function getBetCacheTime($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao){
        $lottery = LotteryType::findOne(['lottery_type'=>$lottery_type]);
        $cacheTime = $lottery->data_ftime;
        switch ($lottery_type){
            case 1: # 希腊1.5分彩
                break;
            case 2: # 希腊3分彩
                break;
            case 3: # 希腊5分彩
                break;
            case 4: # 希腊10分彩
                if(substr($qihao,6) == '01') $cacheTime = 60 * 60 * 8 + 10 * 60; # 8小时20分
                break;
            case 5: # 重庆时时彩
                if(substr($qihao,6) == '010') $cacheTime = 60 * 60 * 4; # 4小时
                if(substr($qihao,6) == '001') $cacheTime = 30 * 60; # 30分钟
                break;
            case 6: # 新疆时时彩
                break;
            case 7: # 北京快乐8
                $cacheTime = 5 * 60;
                break;
            case 8: # 幸运五星彩
                $cacheTime = 5 * 60;
                break;
            case 8: # 台湾宾果
                $cacheTime = 5 * 60;
                break;
        }

        return $cacheTime ? $cacheTime : 1200;
    }

    /**
     * @desc 根据投注类型返回名称
     * @param $tz_type
     */
    public static function getTypeNameByTzType($tz_type){

        $m = \Yii::$app->cache;
        $mkey = 'TZ_TYPE_NAME_'.$tz_type;
        if(!$typeName = $m->get($mkey)){
            $typeName = TzTypes::findOne(['type'=>$tz_type])->type_name;

            $m->set($mkey, $typeName, 60*60);
        }

        return $typeName;
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
     * @desc 记录投注记录
     * @param $plan_id
     * @param $qihao
     * @param $codes
     * @param int $lottery_type
     * @return bool
     */
    public static function _logRecordsByPlandId($plan_id, $qihao, $codes, $lottery_type = DEFAULT_LOTTERY_TYPE, $is_test = 0, $sn='888888', $snid='888888id'){
        //p([$plan_id, $qihao, $codes, $lottery_type = DEFAULT_LOTTERY_TYPE, $is_test, $sn, $snid],0);
        $UserSysPlans = UserSysPlans::findOne($plan_id);
        if($UserSysPlans->tz_type == 18) {
            $count = strlen(str_replace(',', '', $codes));
        }elseif($UserSysPlans->tz_type == 22){
            $codesArr = Lucky5Service::getBetCodes($codes, $UserSysPlans->single, $UserSysPlans->playway);
            $count = count($codesArr);
        }else{
            $count = count(explode('@', $codes));
        }
        $totalmoney = $count * $UserSysPlans->single;
        $insertData = [
            'playway'=> $UserSysPlans->playway,  // 投注方式
            'tz_type'=> $UserSysPlans->tz_type,  // 投注类型
            'buy_type'=> 1,  // 购买方向类型
            'uid'=> $UserSysPlans->uid,  // 投注账号id
            'lottery_type' => $lottery_type, # 彩种
            'account' => $UserSysPlans->account,
            'plan_id' => $plan_id, # 计划id
            'codes' => (string)$codes,  // 投注号码
            'qihao' => $qihao,  // 投注期号
            'tz_system_id' => '',  // 投注系统tz_systems .id
            'sn'=>$sn ? $sn : '888888',
            'snid'=>$snid ? $snid : '888888id',
            'order_type'=>$UserSysPlans->playway, # 单双三字定
            'is_simulate' => $is_test,  // 是否模拟投注
            'single' => $UserSysPlans->single,  // 投注倍数
            'betting_money'=> $totalmoney,  // 投注金额
        ];
        //p($insertData,0);
        $insertRst = BetService::_logRecords($insertData);

        return $insertRst;
    }


    /**
     * @desc 获取相反号码
     * @param $codesArr
     * @param $code_type
     * @return array
     */
    public static function getInverseCodes($codesArr, $code_type){
        if(!is_array($codesArr)) return [];
        $where = ['AND', ['=', 'code_type', $code_type], ['NOT IN', 'code', $codesArr]];
        $Num4Type = Num4Type::find()->where($where)->asArray()->all();
        $data = ArrayHelper::getColumn($Num4Type, 'code');

        return $data;
    }

    /**
     * @desc 获取针对用户的号码缓存 plan_id 的key，用于号码量大的计划异步抓回
     * @param $uid
     * @param $codes
     * @return string
     */
    public static function getBetCodesKey($uid, $codes){
        return md5('getBetCodesKey_'.$uid.'_'.$codes);
    }








}