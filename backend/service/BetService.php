<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/12/10
 * Time: 17:28
 */

namespace backend\service;

use backend\models\LotteryType;
use backend\models\User;
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
    public static $maxQihaoArr = [1=>960, 2=>480, 3=>288, 4=>144, 5=>59, 6=>48]; # $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分

    protected function __construct() {
        parent::__construct();
    }

    /**
     * @desc 获取对象
     * @param $uid
     * @param int $system_type_id
     * @param $tz_system_id
     * @return HN0898Service|SevenService
     */
    public static function getBetObj($uid, $system_type_id = 1, $tz_system_id){

        switch ($system_type_id){
            case 1:
                # 0898体系最终投注
                $BetService = new HN0898Service($uid, $tz_system_id);
                break;
            case 2:
                # 7时彩体系最终投注
                $BetService = new SevenService($uid, $tz_system_id);
                break;
            case 3:
                # 希腊时时彩
                $BetService = new XlService($uid, $tz_system_id);
                break;
            default:
                break;
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
    public static function getCodes($system_type_id, $tz_type, $buy_type, $sel_same = 1, $hz_Arr = []){
        //p([$system_type_id, $tz_type, $buy_type, $single, $sel_same, $hz_Arr]);
        switch ($system_type_id){ # system_type_id = lt_system_type.id
            case 1: # 重庆0898 系统
            case 3: # 希腊彩系统
                $codes = BetService::getPlansAllCodesType1($tz_type, $buy_type, $sel_same, $hz_Arr);
                break;
            case 2:
                # 7时彩
                $codes = BetService::getPlansAllCodesType2($tz_type, $buy_type, $sel_same, $hz_Arr);
                break;
            default: break;
        }

        return $codes;
    }

    /**
     * @desc 1.1 投注：投注之前业务逻辑判断
     * @param $qihao
     * @param string $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     */
    //public static function beforeBet($qihao, $tz_system_id, $playway, $account = 'gaozi2017', $lottery_type = 'ssc'){
    public static function beforeBet($qihao, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $rst = ['status'=>200, 'msg'=>'可以投注~'];
        switch ($lottery_type){
            case 1:
                //$mkey = \Yii::$app->params['TZ_SWITCH_KEY'].'_'.$qihao.'_'.$tz_system_id.'_'.$playway.'_'.$account;
                $mkey = \Yii::$app->params['TZ_SWITCH_KEY'].'_'.$lottery_type.'_'.$qihao;
                $tzStatus = $m->get($mkey);

                # 判断当期开奖数据处理是否完成，未完成则不能下一期的投注
                if(!$tzStatus){
                    $rst = ['status'=>300, 'msg'=>'投注开关未开启，有未处理完成的数据~','mkey'=>$mkey,'tzStatus'=>$tzStatus];
                    Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/tzCron','INFO','0898投注记录', $rst);
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

        $plans = UserSysPlans::find()->where(['AND', ['=', 'status', 1], ['>', 'uid', 0], ['=', 'is_parent', 1]])->all();
        if($plans){
            foreach ($plans as $key=>$plan){
                if($plan->children_plan_id>0){
                    $ids = explode(',', $plan->children_plan_id);
                }else{
                    $ids[] = $plan->id;
                }
                foreach ($ids as $id){
                    $tzRst[$id] = self::tzByPlanId($id);
                }
            }
        }
        $count = count($plans);
        $logArr = ['tzRst'=>$tzRst, 'msg'=>$count == 0 ? '无投注计划' : $count.'条计划'];
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/bet','INFO','用户真实投注', $logArr);

        return ['status'=>200, 'msg'=>'系统定制化投注处理完成~'];
    }

    /**
     * @desc 同步余额
     * @param $uid
     * @param $tz_system_id
     */
    public static function synBalance($uid, $tz_system_id){
        $TzSystems = TzSystems::findOne($tz_system_id);
        $TzSystemsUser = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
        switch ($TzSystems->system_type_id){
            case 1:
                $rst = HN0898Service::synBalance($TzSystemsUser->id);
                break;
            case 2:
                $rst = SevenService::synBalance($TzSystemsUser->id);
                break;
            case 3:
                break;
        }

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
    public static function getPlansAllCodesType1($tz_type = 1, $buy_type = 1, $sel_same = 0, $codes_hz = ''){
        $playway = BetService::getPlaywayByTzType($tz_type);
        //p([$tz_type, $buy_type, $sel_same, $codes_hz, $playway]);
        $m = \Yii::$app->cache;
        $qihao = HN0898Service::getQihao();
        $mkey = 'getPlansAllCodesType1_'.$playway.'_'.$tz_type.'_'.$buy_type.'_'.$qihao;
        //if($codes = $m->get($mkey)) return $codes;

        switch ($playway){
            case 4: # 一字定
            case 10: # 一字定
                $codesArr = explode('@', $codes_hz);
                break;
            case 2: # 三字定
                $params = ['playway'=>$playway, 'tz_type'=>$tz_type, 'status'=>$buy_type];
                $SysPlansCodes = SysPlansCodes::find()->where($params)->orderBy(['rand()' => SORT_DESC])->asArray()->all(); # ->limit($limit) 限制数量去掉
                $codesArr = [];
                foreach ($SysPlansCodes as $key=>$plan){
                    $codesArr[] = $plan['code'];
                }
                break;
            case 3: # 四字定
                if($tz_type == 20) {  # 四定和值
                    # 四定和值选号，默认排除：双双重、三重、四重、四兄弟、四单四双
                    //$where = ['codes_hz'=>explode(',', $codes_hz), 'type_22'=>0, 'type_3'=>0, 'type_4'=>0, 'type_4b'=>0, 'type_4ds'=>0];
                    $where = ['codes_hz' => explode(',', $codes_hz)]; // , 'type_4' => 0
                    $codesArr = Num4Type::find()->where($where)->asArray()->all();
                    $codesArr = ArrayHelper::getColumn($codesArr, 'code');
                }elseif($tz_type == 21){ # 四定两兄弟
                    $codesArr = NumService::get2bCodeArr();
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
                }elseif($tz_type == 25){ # 过滤
                    $codesArr = NumService::getCodesKuaixuan(json_decode($codes_hz, true));
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
    public static function getHzCodes($tz_type, $codes_hz){
        return self::getPlansAllCodesType1($tz_type, $buy_type = 1, $sel_same = 0, $codes_hz);
    }

    /**
     * @desc 7时彩
     * @param $playway
     * @param $tz_type 投注类型:1大小单双三字定2大小三字定3单双三字定
     * @param $buy_type
     * @param $limit int 默认获取注数
     * @return string
     */
    public static function getPlansAllCodesType2($tz_type = 1, $buy_type = 1, $sel_same = 1, $codes_hz = ''){
        $m = \Yii::$app->cache;

        $playway = TzTypes::findOne(['type'=>$tz_type])->playway;
        $qihao = HN0898Service::getQihao();
        $codes = self::getPlansAllCodesType1($tz_type, $buy_type, $sel_same, $codes_hz);
        //$codesDatas = explode('@', $codes);

        switch ($playway) {
            case 1: # 二定
                break;
            case 2: # 三定
                break;
            case 3: # 四定
                $position = '1,2,3,4';
                break;
            default:
                ;
        }
        $dict_no_type_id = self::get_dict_no_type_id($position);

        //$codes = '13579,02468,,@,23456,23465,';

        $mkey = 'getPlansAllCodesType1_'.$playway.'_'.$tz_type.'_'.$buy_type.'_'.$qihao;
        //if($codes = $m->get($mkey)) return $codes;

        //$m->set($mkey, $codes, 5*60);

        return $codes;
    }

    /**
     * @desc 是否批量投注，号码格式跟其它不一样
     * @param int $tz_type
     */
    public static function isBigNumsBet($tz_type = 20){
        if(in_array($tz_type, [20, 23, 24, 25])){
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
       $tz_sites = explode(',', $plan->tz_sites);
       $qihao = HN0898Service::getQihao($plan->lottery_type);
       $rst = [];
       foreach ($tz_sites as $tz_system_id){
           $system_type_id = TzSystems::findOne($tz_system_id)->system_type_id;

           # 4、投注号码 codes
           $codes = self::getCodes($system_type_id, $plan->tz_type, $plan->buy_type, $plan->sel_same, $plan->hz_Arr);

           # 5、投注请求
           $isAuto == 0 && BetService::beforeBetNow($plan->account, $tz_system_id, $plan->lottery_type, $qihao, $plan->id);
           $BetService = self::getBetObj($plan->uid, $system_type_id, $tz_system_id);
           $rst[] = $BetService->bet($qihao, $plan->id, $codes);
           $isAuto == 0 && BetService::afterBetNow($qihao);

           BetService::synBalance($plan->uid, $tz_system_id);
       }
       $logArr = ['tz_sites'=>$tz_sites,'codes'=>$codes, 'postRst'=>$rst];
       Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/plan_bet','INFO','0898投注记录', $logArr);

       return $rst;
    }

    /**
     * @desc 立即投注之前清除缓存锁
     * @param $account
     * @param $tz_system_id
     * @param $qihao
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     * @param $plan_id
     */
    public static function beforeBetNow($account, $tz_system_id, $lottery_type = DEFAULT_LOTTERY_TYPE, $qihao, $plan_id = 0){
        $m = \Yii::$app->cache;
        $mkey = BetService::buildBetKey($account, $tz_system_id, $lottery_type, $qihao, $plan_id);
        $m->delete($mkey);

        $pkey = \Yii::$app->params['TZ_SWITCH_KEY'].'_'.$qihao;
        $m->delete($pkey);
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
            'qihao' => $data['qihao'],  // 投注期号
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

        if(!$rst) return ['status'=>200,'msg'=>current($bettingRecords->getErrors())];

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
        if($isCanceled = $m->get($mkey)) return ['status'=>300, 'msg'=>'正在推单请稍等~'];
        $TzSystems = TzSystems::findOne($BettingRecords->tz_system_id);
        switch ($TzSystems->system_type_id){
            case 1:
                //$rst = HN0898Service::synBalance($TzSystemsUser->id);
                $rst = HN0898Service::cancelOrder($bet_id, $BettingRecords->tz_system_id);
                break;
            case 2:
                //$rst = SevenService::synBalance($TzSystemsUser->id);
                $rst = SevenService::cancelOrder($bet_id, $BettingRecords->tz_system_id);
                break;
            case 3:
                break;
        }
        if($rst['status'] == 200){
            $m->set($mkey, 1, 5);
        }


       return $rst;
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
                break;
                if(substr($qihao,6) == '01') $cacheTime = 60 * 60 * 8 + 10 * 60; # 8小时20分
            case 5: # 重庆时时彩
                if(substr($qihao,6) == '010') $cacheTime = 60 * 60 * 4; # 4小时
                break;
            case 6: # 新疆时时彩
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













}