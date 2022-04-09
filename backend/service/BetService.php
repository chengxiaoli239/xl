<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/12/10
 * Time: 17:28
 */

namespace backend\service;

use backend\models\BetErrorPlansTask;
use backend\models\DataDealStatus;
use backend\models\TzType;
use backend\service\clients\TzSystemUsersService;
use backend\service\huiyuan\HuiYuanService5;
use backend\models\LotteryType;
use backend\models\SystemConfig;
use backend\models\User;
use backend\service\huiyuan\KuaiLe8Service;
use backend\service\Juhua\JuHuaBaseService;
use backend\service\LeCai\ZhongFaService;
use backend\service\Lucky5\Lucky5Service;
use backend\service\NineNine\NineNineBaseService;
use backend\service\NineNine\NineNineNewService;
use backend\service\NineNine\NineNineService6;
use backend\service\qilin\BingDaoService;
use common\kj\cqssc\CqsscKcw;
use common\service\proxy\ProxyBaseService;
use common\tools\RedisLock;
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
use yii\db\Exception;
use yii\helpers\ArrayHelper;

abstract class BetService extends BaseBetService {
    protected $_nowTime = null;    // 当前时间戳
    protected $_operateTime = null;    // 当前时间戳的格式
    protected $_baseUrl = '';    // 当前时间戳的格式
    public static $maxQihaoArr = [1=>960, 2=>480, 3=>288, 4=>144, 5=>59, 6=>48, 7=>179, 8=>288]; # $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
    public static $static_sn = ['888888', '6666666666'];
    public static $test_static_sn = ['istest'];
    public static $static_snid = ['888888id', '6666666666'];
    public static $test_true_sn = '888888';
    public static $true_bet_sn = '6666666666';
    public static $test_true_snid = '888888id';

    protected function __construct() {
        parent::__construct();
    }

    /**
     * @desc 获取对象
     * @param $uid
     * @param $tz_system_id - 表lt_tz_systems.id
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return HN0898Service|KuaiLe8Service|SevenService|XlService|Lucky5Service|NineNineNewService|JuHuaBaseService|ZhongFaService
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
        }elseif(in_array($tz_system_id, [12])){
            # 九九新网
            $BetService = new \backend\service\NineNine\NineNineNewService($uid, $tz_system_id);
        }elseif(in_array($tz_system_id, [13])){
            # 13 冰岛
            $BetService = new \backend\service\BingDao\BingDaoService($uid, $tz_system_id);
        }elseif(in_array($tz_system_id, [16])){
            # 16 宝岛众发
            $BetService = new ZhongFaService($uid, $tz_system_id);
        }

        return $BetService;
    }

    /**
     * @desc 获取投注号码
     * @param $playway
     * @param $tz_type
     * @param $buy_type
     * @param float $single
     * @param $sel_same
     * @param array|string $hz_Arr
     * @return string
     */
    public static function getCodes($tz_type, $buy_type, $sel_same = 1, $hz_Arr = [], $plan_id = ''){
        $codes = BetService::getPlansAllCodesType1($tz_type, $buy_type, $sel_same, $hz_Arr, $plan_id);

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
                    $rst = ['status'=>300, 'msg'=>'投注开关未开启，有未处理完成的数据-1','mkey'=>$mkey,'tzStatus'=>$tzStatus];
                    Tool_Common::log('tzCron','INFO','不能投注', $rst);
                }
                break;
            default:
                break;
        }
        return $rst;
    }

    /**
     * @desc 用户投注基本入口 - 废弃
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
                $lotteryNameArr = CqsscKcw::getLotteryNameArr();
                $datas[] = ['qihao'=>$qihao, 'tzStatus'=>$tzStatus, 'lottery' => $lotteryNameArr[$lottery_type], 'tzRst'=>$tzRst];
                //p($datas);
                BetService::afterBetNow($plan->lottery_type, $qihao, $plan->uid); # 彩种投注结束锁
                $logArr[$lottery_type]['plans'] = $plans;
            }
            $count = count($plans);
            $logArr[$lottery_type]['ids'] = $ids;
            $logArr[$lottery_type]['qihao'] = $qihao;
            $logArr[$lottery_type]['msg'] = $count == 0 ? '无投注计划' : $count.'条计划';
        }
        Tool_Common::log('bet','INFO','用户真实投注-1', $logArr);

        return ['status'=>200, 'msg'=>'系统定制化投注处理完成~'];
    }

    /**
     * @desc 彩种名称缓存key
     * @return string
     */
    public static function buildLotteryNameKey(){
        return 'buildLotteryNameKey_1';
    }

    /**
     * @desc 彩票下注
     * @param $uid
     * @return array
     */
    public static function lotteryBet($uid){
        # 1、账号是否过期
        $status = UserService::accountIsExpire($uid, '', $TzSystemsUsers);
        if(!$status && !in_array($uid, [2, 11])){
            $Model = TzSystemsUsers::findOne(['uid'=>$uid]);
            Tool_Common::log('accountIsExpire', 'ERR', '账号过期提示', ['uid'=>$uid, 'account'=>$Model->account]);
            return ['status'=>301, 'msg'=>'账号过期提示'];
        }

        # 2、下注任务检测
        $where = ['AND', ['=', 'uid', $uid], ['IN', 'status', [0, 1]]]; # 可重推的状态0:未推送1推送失败可重推，不可重推:3
        $BetErrorPlansTasks = BetErrorPlansTask::find()->where($where)->orderBy(['id'=>SORT_DESC])->one();
        $tz_system_id = $BetErrorPlansTasks->tz_system_id;
        $task_id = $BetErrorPlansTasks->id;
        if(empty($BetErrorPlansTasks)){
            $Model = TzSystemsUsers::findOne(['uid'=>$uid]);
            Tool_Common::log('/repeatErrorBet/'.__FUNCTION__.'_not_task', 'INFO', '下注-没有可以下注的任务', ['uid'=>$uid, 'username'=>$Model->username]);
            return ['status'=>300, 'msg'=>'没有可以下注的任务'];
        }

        # 3、登陆检测
        $start_time = microtime(true);
        $flag = self::isLogin($uid, $tz_system_id, $r=2);
        $end_time = microtime(true);
        Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'INFO', '下注-登陆检测-1', ['uid'=>$uid, 'flag'=>$flag, 'consume_time'=>($end_time-$start_time).'s']);
        if(!$flag && $TzSystemsUsers->is_auto_login){
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);
            $loginRst = BaseService::login($TzSystemsUsers->id, $is_auto=2);
            Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'INFO', '网盘开盘状态-4-2', ['uid'=>$uid, 'task_id'=>$task_id, 'loginRst'=>$loginRst]);
            return ['status'=>302, 'msg'=>'未登录'];
        }

        # 4、下注
        $lottery_types = UserSysPlansService::getMyLotteryTypes($uid);
        foreach ($lottery_types as $data){
            if(in_array($data['lottery_type'], [8, 18])) { # 8、幸运五 18台湾快五
                $rst = BetService::betByUserUidTask([$data['lottery_type']], $uid);
            }else{
                $rst = BetService::betByUidNew($uid, $data['lottery_type']);
            }
        }

        return $rst;
    }

    /**
     * @desc 彩种名称
     * @param string $lottery_type
     */
    public static function getLotteryName($lottery_type = ''){
        $m = \Yii::$app->cache;
        $mkey = self::buildLotteryNameKey();
        if(!$datas = $m->get($mkey)){
            $datas = [];
            $LotteryTypes = LotteryType::find()->asArray()->all();
            foreach ($LotteryTypes as $lotteryType){
                $datas[$lotteryType['lottery_type']] = $lotteryType['title'];
            }

            $m->set($mkey, $datas, 4*3600);
        }
        if(!empty($lottery_type) && isset($datas[$lottery_type])){
            $datas = $datas[$lottery_type]; # 单个彩种名称
        }

        return $datas;
    }

    /**
     * @desc 彩种名称缓存key
     * @return string
     */
    public static function buildBetTypeNameKey(){
        return 'buildBetTypeNameKey_1';
    }

    /**
     * @desc 彩种名称
     * @param string $lottery_type
     */
    public static function getBetTypeName($tzType = ''){
        $m = \Yii::$app->cache;
        $mkey = self::buildBetTypeNameKey();
        if(!$datas = $m->get($mkey)){
            $datas = [];
            $TzTypes = TzTypes::find()->asArray()->all();
            foreach ($TzTypes as $tz_type){
                $datas[$tz_type['type']] = $tz_type['type_name'];
            }

            $m->set($mkey, $datas, 4*3600);
        }
        if(!empty($tzType) && isset($datas[$tzType])){
            $datas = $datas[$tzType]; # 单个彩种名称
        }

        return $datas;
    }

    /**
     * @desc 单用户下注
     */
    public static function betByUid($uid = 0){
        if(!$uid) return ['status'=>300, 'msg'=>'用户id不能为0'];
        $tzStatus = SystemConfig::findOne(['key'=>'tz_status'])->value;
        if(!$tzStatus) return ['status'=>300, 'msg'=>'投注开关未开启'];
        $lottery_types = StaticService::getUserLotteryTypes($uid);
        foreach ($lottery_types as $lottery_type) {

            $hasActivePlan = CommonService::hasPlansActive($lottery_type);
            if(in_array($lottery_type, [1, 8, 10, 11]) && !$hasActivePlan){
                continue;
            }
            $qihao = HN0898Service::getQihao($lottery_type);
            $tzStatus = BetService::isCanBet($lottery_type, $uid);
            Tool_Common::log('betByUid', 'INFO', '单用户下单-1', ['uid'=>$uid, 'lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'tzStatus'=>$tzStatus]);
            if (!$tzStatus) continue;
            $where = ['AND',['=', 'lottery_type', $lottery_type], ['=', 'status', 1], ['=', 'uid', $uid], ['=', 'is_parent', 1]];
            $plans = UserSysPlans::find()->where($where)->orderBy(['tz_sort'=>SORT_ASC])->all();
            Tool_Common::log('betByUid_plans', 'INFO', '计划数据', ['uid'=>$uid, 'lottery_type'=>$lottery_type, 'plans'=>$plans]);
            if ($plans) {
                $datas = [];
                $planIds = [];
                foreach ($plans as $key => $plan) {
                    $planIds[] = $plan->id;
                    $tzRst[$plan->id] = self::tzByPlanId($plan->id);
                }
                //BetService::synBalance($plan->uid, $plan->tz_sites);# 目前tz_sites 已变更为单个站点id
                BetService::afterBetNow($plan->lottery_type, $qihao, $plan->uid); # 彩种投注结束锁
                $lotteryNameArr = CqsscKcw::getLotteryNameArr();
                $datas[] = ['qihao'=>$qihao, 'tzStatus'=>$tzStatus, 'lottery' => $lotteryNameArr[$lottery_type], 'tzRst'=>$tzRst];
                $logArr[$lottery_type]['plansIds'] = $planIds;
                $count = count($plans);
                $logArr[$lottery_type]['qihao'] = $qihao;
                $logArr[$lottery_type]['datas'] = $datas;
                $logArr[$lottery_type]['msg'] = $count == 0 ? '无投注计划' : $count.'条计划';
            }
        }
        Tool_Common::log('bet','INFO','用户真实投注-2', $logArr);

        return ['status'=>200, 'msg'=>'系统定制化投注处理完成~'];
    }

    /**
     * @desc 单用户下注 - 新
     */
    public static function betByUidNew($uid = 0, $lottery_type=DEFAULT_LOTTERY_TYPE, $is_auto = 1){
        if(!$uid) return ['status'=>300, 'msg'=>'用户id不能为0'];
        $tzStatus = SystemConfig::findOne(['key'=>'tz_status'])->value;
        if(!$tzStatus) return ['status'=>300, 'msg'=>'投注开关未开启'];
        $lottery_types = $lottery_type ? [$lottery_type] : StaticService::getUserLotteryTypes($uid);
        foreach ($lottery_types as $lottery_type) {
            $hasActivePlan = CommonService::hasPlansActive($lottery_type);
            if(in_array($lottery_type, [1, 8, 10, 11, 17]) && !$hasActivePlan){
                continue;
            }
            $qihao = HN0898Service::getQihao($lottery_type);
            $tzStatus = BetService::isCanBet($lottery_type, $uid);
            Tool_Common::log('betByUid', 'INFO', '单用户下单-2', ['uid'=>$uid, 'lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'tzStatus'=>$tzStatus]);
            if (!$tzStatus) continue;

            $where = ['AND',['=', 'lottery_type', $lottery_type], ['=', 'status', 1], ['=', 'uid', $uid], ['=', 'is_parent', 1]];
            $plans = UserSysPlans::find()->where($where)->orderBy(['tz_sort'=>SORT_ASC])->all();
            foreach ($plans as $plan){
                $tzRst[$plan->id] = self::tzByPlanIdNew($plan->id, $is_auto);
            }
        }

        Tool_Common::log('betByUidNew','INFO','用户真实投注-3', ['uid'=>$uid, 'rst'=>$tzRst]);

        return ['status'=>200, 'msg'=>'系统定制化投注处理完成~'];
    }

    /**
     * @desc 用户计划下注脚本
     * @param array $lottery_types
     */
    public static function betByUserUidTask($lottery_types = [], $uid = ''){

        $lottery_types = $lottery_types ? : StaticService::getLotteryTypes();

        $m = \Yii::$app->cache;
        foreach ($lottery_types as $lottery_type){
            # status可重推的状态0:未推送1推送失败可重推，不可重推:3  is_local_bet:1客户本地0云服务器
            $where = ['AND', ['=', 'lottery_type', $lottery_type], ['IN', 'status', [0, 1]]];
            if($uid){
                $where = array_merge($where, [['=', 'uid', $uid]]);
            }
            $BetErrorPlansTasks = BetErrorPlansTask::find()->where($where)->orderBy(['id'=>SORT_DESC])->limit(5)->all();
            if(empty($BetErrorPlansTasks)){
                Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'ERR', '用户计划下注脚本-1', ['uid' => $uid, 'msg'=>'没有下注计划']);
                continue;
            }
            $first_tz_system_id = $BetErrorPlansTasks[0]->tz_system_id;
            if($uid){
                $activeQihao = BetService::getActivePostQiHao($uid, $first_tz_system_id, $lottery_type);
            }
            foreach ($BetErrorPlansTasks as $betErrorPlansTask){
                try {
                    $uid = $betErrorPlansTask->uid;
                    $is_local_bet = $betErrorPlansTask->is_local_bet;
                    $task_id = $betErrorPlansTask->id;
                    $tz_system_id = $betErrorPlansTask->tz_system_id;
                    $lottery_type = $betErrorPlansTask->lottery_type;
                    $playway = $betErrorPlansTask->playway;
                    $account = $betErrorPlansTask->account;
                    $plan_id = $betErrorPlansTask->plan_id;
                    $bet_sort_key = $betErrorPlansTask->bet_sort_key;

                    $qihao = $betErrorPlansTask->qihao;
                    Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'ERR', '用户计划下注脚本-2', ['task_id'=>$task_id, 'plan_id'=>$plan_id, 'uid'=>$uid, 'lottery_type'=>$lottery_type, 'account'=>$account, 'tz_system_id'=>$tz_system_id, 'activeQihao'=>$activeQihao, 'qihao'=>$qihao]);

                    $BetService = self::getBetObj($uid, $tz_system_id, $lottery_type);
                    if(false && $balance<$bet_money){
                        BetService::closeTask($task_id, $qihao, $activeQihao, $account, $msg='余额不足，不可重推'); # 关闭计划
                    }elseif($is_local_bet == 0 && $qihao == $activeQihao){ # 云服务
                        $betKey = BetService::buildLotteryBetKey($activeQihao, $plan_id, $bet_sort_key, $task_id);
                        if($lock = $m->get($betKey)){
                            Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'ERR', '用户计划下注脚本-3', ['task_id'=>$task_id,'betKey'=>$betKey]);
                            continue;
                        }

                        $time = BetService::getBetCacheTime($lottery_type, $activeQihao); # 投注之后缓存时间
                        $time = ($playway == 3) ? $time : ($time-240);
                        $m->set($betKey, 1, $time); # 减去三分钟缓存时间

                        $s_time = microtime(true);
                        Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'INFO', '用户计划下注脚本-4', ['task_id'=>$task_id]);
                        $betRst = $BetService->repeatErrorBet($task_id);
                        $e_time = microtime(true);
                        $t_rst = $betRst['data']['bet_rst'];
                        $rst[$lottery_type][$task_id]['repeatBetRst'] = $t_rst;
                        $current_ip_addr = ProxyBaseService::getCurrentValidProxyIp(); # 获取当前可用的代理IP
                        $logArr = ['uid' => $uid, 'qihao'=>$activeQihao, 'account'=>$account, 'plan_id'=>$plan_id, 'err_id'=>$task_id, 'tz_system_id' => $tz_system_id, 'rst'=>$betRst, 'betKey'=>$betKey, 'consume_time'=>($e_time-$s_time).'s', 'current_ip_addr'=>$current_ip_addr];

                        if(!empty($t_rst['snid'])){
                            # 记录方案号
                            $where = ['plan_id'=>$plan_id, 'qihao'=>$activeQihao, 'lottery_type'=>$lottery_type];
                            $BettingRecords = BettingRecords::find()->where($where)->one();
                            $BettingRecords->snid = trim($BettingRecords->snid.';'.$t_rst['snid'], ';');
                            $BettingRecords->sn = trim($BettingRecords->sn.';'.$t_rst['sn'], ';');
                            $flag = $BettingRecords->save();
                            if(!$flag){
                                $logArr1 = ['uid' => $uid, 'qihao'=>$activeQihao, 'account'=>$account, 'plan_id'=>$plan_id, 'err_id'=>$task_id, 'err_msg'=>$BettingRecords->getErrors()];
                                Tool_Common::log('/repeatErrorBet/'.__FUNCTION__.'_err', 'ERR', '下注结果保存失败', $logArr1);
                            }
                        }

                        Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'INFO', '用户计划下注成功-end', $logArr);
                    }elseif(!empty($activeQihao) && $qihao<$activeQihao){
                        BetService::closeTask($task_id, $qihao, $activeQihao, $account, $msg='未开盘或者已关盘[' . date('Y-m-d H:i:s') . ']'); # 关闭计划
                        $rst[$lottery_type][$betErrorPlansTask->id]['repeatBetRst'] = ['status' => 300, 'qihao'=>$qihao, 'activeQihao'=>$activeQihao, 'msg' => $msg];
                        Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'ERR', '用户计划下注脚本-5', ['uid'=>$uid,'account'=>$account,'tz_system_id'=>$tz_system_id, 'rst'=>$rst]);
                    }

                    $rst[$lottery_type][$betErrorPlansTask->id]['repeatBetInfo'] = $betErrorPlansTask;
                    $rnt = rand(1, 3);
                    sleep($rnt);
                }catch (\Exception $exception){
                    Tool_Common::log('/repeatErrorBet/'.__FUNCTION__.'_err', 'ERR', '下注错误', ['task_id'=>$task_id]);
                    $rst[$lottery_type][$task_id]['repeatBetRst'] = ['task_id'=>$task_id, 'err_msg'=>$exception->getMessage()];
                }
            }
        }

        return $rst;
    }

    /**
     * @desc 关闭计划
     * @param string $task_id
     * @param string $qihao
     * @param string $activeQihao
     * @param string $account
     * @param string $msg
     * @return bool
     */
    public static function closeTask($task_id='', $qihao='', $activeQihao='', $account='', $msg=''){
        $betErrorPlansTask = BetErrorPlansTask::findOne($task_id);
        if(empty($betErrorPlansTask)) return false;
        $data = ['Status'=>0,'qihao'=>$qihao, 'activeQihao'=>$activeQihao,'account'=>$account,'push_time'=>date('Y-m-d H:i:s'),'msg'=>'未开盘或者已关盘'];
        $betErrorPlansTask->post_desc = json_encode($data, 320);
        $betErrorPlansTask->status = 3; # 不可重推
        $flag = $betErrorPlansTask->save();

        return $flag;
    }

    /**
     * @desc 获取正在投注的期号
     * @param string $uid
     * @param string $tz_system_id
     * @param int $lottery_type
     * @return array|mixed|string
     */
    public static function getActivePostQiHao($uid='', $tz_system_id='', $lottery_type=DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $activeQihao_key = BetService::buildActiveQihaoKeyUid($uid, $tz_system_id, $lottery_type);
        $activeQihao = $m->get($activeQihao_key);
        try {
            if(empty($activeQihao) OR ($activeQihao['status']=='30200')){
                $activeQihao = BetService::getActiveQihao($uid, $tz_system_id, $lottery_type);
                if(!$activeQihao OR (isset($activeQihao['status']) && $activeQihao['status'] == '30200')){
                    Tool_Common::log('wang_pan_is_active', 'ERR', '网盘开盘状态-2', ['uid'=>$uid, 'tz_system_id'=>$lottery_type, 'activeQihao'=>$activeQihao]);
                    return ['status'=>300, 'msg'=>'未开盘或者已关盘['.date('Y-m-d H:i:s').']', 'activeQihao'=>$activeQihao];
                }
                if(is_string($activeQihao) && strpos($activeQihao, '020')){
                    $m->set($activeQihao_key, $activeQihao, 300);
                }
            }
            Tool_Common::log('wang_pan_is_active', 'INFO', '网盘开盘状态-3', ['uid'=>$uid, 'tz_system_id'=>$lottery_type, 'activeQihao'=>$activeQihao]);
        }catch (\Exception $exception){
            Tool_Common::log('/betService/'.__FUNCTION__, 'ERR', '网盘激活期号获取', ['lottery_type'=>$lottery_type, 'err_msg'=>$exception->getMessage()]);
            return false;
        }

        return $activeQihao;
    }

    public static function buildLotteryBetKey($qihao='', $plan_id='', $bet_sort_key=0, $task_id=''){
        return 'buildLotteryBetKey_'.$qihao.'_'.$plan_id.'_'.$bet_sort_key.'_'.$task_id;
    }

    public static function buildActiveQihaoKeyUid($uid='', $tz_system_id='', $lottery_type=DEFAULT_LOTTERY_TYPE ){
        $mkey = 'getActiveQihao_'.$uid.'_'.$tz_system_id.'_'.$lottery_type;

        return $mkey;
    }

    /**
     * @desc 获取激活的计划
     * @param string $uid
     * @param string $tz_system_id
     * @param int $lottery_type
     * @return array|string
     */
    public static function getActiveQihao($uid='', $tz_system_id='', $lottery_type=DEFAULT_LOTTERY_TYPE){
        $mkey = self::buildActiveQihaoKeyUid($uid, $tz_system_id, $lottery_type);

        $m = \Yii::$app->cache;
        $qihao = $m->get($mkey);
        if(!empty($qihao)) return $qihao;
        if($tz_system_id == 9){
            $qihao = Lucky5Service::getActiveQihao($uid, $tz_system_id, $lottery_type);
        }elseif($tz_system_id == 11){ # 菊花网
            $qihao = JuHuaBaseService::getActiveQihao($uid, $tz_system_id, $lottery_type);
        }elseif($tz_system_id == 16){ # 宝岛众发
            $qihao = ZhongFaService::getActiveQihao($uid, $tz_system_id, $lottery_type);
        }else{
            $qihao = HN0898Service::getQihao($lottery_type);
        }
        $m->set($mkey, $qihao, 5);

        return $qihao;
    }

    /**
     * @desc 激活的期号key
     * @param string $tz_system_id
     * @param int $lottery_type
     * @return string
     */
    public static function buildActiveQihaoKey($tz_system_id='', $lottery_type=DEFAULT_LOTTERY_TYPE){
        $mkey = 'getActiveQihao_'.$tz_system_id.'_'.$lottery_type;

        return $mkey;
    }

    /**
     * @desc 开启新的一期计划
     * @param string $access_token
     * @param string $qihao
     * @return bool|int
     */
    public static function openBetQihao($access_token='', $qihao='', $lottery_type=DEFAULT_LOTTERY_TYPE){
        if(empty($lottery_type)){
            $lottery_type = DEFAULT_LOTTERY_TYPE;
        }
        $m = \Yii::$app->cache;

        $flag = 0;
        $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken(trim($access_token));
        $mkey = BetService::buildActiveQihaoKey($TzSystemsUsers->tz_system_id, $lottery_type);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '开启计划', ['access_token'=>$access_token, 'lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'mkey'=>$mkey]);
        if(!empty($TzSystemsUsers)){

            $flag = $m->set($mkey, $qihao, 60);
        }

        return $flag;
    }


    /**
     * @desc 下注任务结果通知
     * @param string $access_token
     * @param string $qihao
     * @return bool|array
     */
    public static function pushTasksBetRst($plan_id, $qihao='', $betRst=[], $access_token='', $lottery_type=DEFAULT_LOTTERY_TYPE){
        if(empty($lottery_type)){
            $lottery_type = DEFAULT_LOTTERY_TYPE;
        }
        if(strpos($betRst['err_msg'], '您当前使用的浏览器不支持cookie') !== false){
            return ['status'=>300, 'msg'=>'下注失败，等待下注'];
        }

        $flag = 0;
        $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
        if(empty($TzSystemsUsers)){
            return ['status'=>404, 'msg'=>'用户信息找不到'];
        }

        $where = ['plan_id'=>$plan_id, 'qihao'=>$qihao, 'lottery_type'=>$lottery_type];
        $BetErrorPlansTask = BetErrorPlansTask::findOne($where);
        if(empty($BetErrorPlansTask)){
            return ['status'=>404, 'msg'=>'任务记录找不到'];
        }

        $BetErrorPlansTask->status = $betRst['task_status'];
        $BetErrorPlansTask->post_desc = json_encode($betRst, 320);
        $flag = $BetErrorPlansTask->save();

        return $flag;
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
        Tool_Common::log('isCanBet', 'INFO', '是否下注缓存值', ['lottery_type'=>$lottery_type, 'uid'=>$uid, 'pKey'=>$pkey, 'status'=>$status]);

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
        }elseif($lottery_type == 9){
            # 台湾宾果
            if('00:00:00'<$time && $time<'07:00:00'){
                $status = false;
            }
        }elseif(in_array($lottery_type, [16]) && '20:00:00'<$time && $time<'21:00:00'){
            $status = false;
        }elseif(in_array($lottery_type, [10, 11, 12, 13])){
            # 冰岛90s 3m 5m 10m
            if('03:00:00'<$time && $time<'08:00:00'){
                $status = false;
            }
        }elseif(in_array($lottery_type, [18])){
            # 台湾快五
            if('02:00:00'<$time && $time<'07:00:00'){
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
    public static function synBalance($uid, $tz_system_id, $is_auto=1){
        $TzSystemsUser = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id]);

        $rst = BaseService::synBalance($TzSystemsUser->id, $is_auto);

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
                }elseif(in_array($tz_type, \Yii::$app->params['IMPORT_CODES_TYPES'])) { # 二定 导入方案
                    $codesArr = UserSysPlansService::getImportCodes($plan_id, $code_type=2);
                }elseif(in_array($tz_type, [31])) { # 五位二定
                    $codesArr = NumService::getCodesKuaiXuan(json_decode($codes_hz, true), $code_type = 5);
                }elseif(in_array($tz_type, [33])) { # 二定号码翻倍切换
                    $codes_hz_arr = json_decode($codes_hz, true);
                    $codes_desc = $codes_hz_arr['status_val'] == 1 ? $codes_hz_arr['code1'] : $codes_hz_arr['code2'];
                    unset($codes_hz_arr['code1'], $codes_hz_arr['code2'], $codes_hz_arr['singles_key'], $codes_hz_arr['status_val']);
                    $codes_hz_desc = NumService::getCodesHzByDesc($codes_desc);
                    $codes_hz_desc = array_merge($codes_hz_arr, $codes_hz_desc);
                    $codesArr = NumService::getCodesKuaiXuan($codes_hz_desc, $code_type = 2);
                }
                break;
            case 2: # 三字定
                if(in_array($tz_type, \Yii::$app->params['IMPORT_CODES_TYPES'])) { # 导入方案
                    $codesArr = UserSysPlansService::getImportCodes($plan_id, $code_type=3);
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
            case 6: # 二、三、四字现
                $codesArr = explode(',', json_decode($codes_hz, 320)['codes']);
                break;
        }

        $before_count=count($codesArr);
        # 反买号码获取
        if(!in_array($tz_type, [22]) && in_array($tz_type, \Yii::$app->params['can_change_buy_type']) && $buy_type == 0){ # 22 四定单双
            $codesArr = self::getInverseCodes($codesArr, $code_type);
        }
        //p(['buy_type'=>$buy_type, 'before_count'=>$before_count, 'after_count'=>count($codesArr), 'codesArr'=>$codesArr]);

        $codes_hz_data = json_decode($codes_hz, true);
        if(isset($codes_hz_data['filters']['filter_type']) && $codes_hz_data['filters']['filter_type'] == 1){
            # 过滤号码，filter_type:1过滤前x期号码
            $filter_codes = NumService::getCodesByCodesHz($codes_hz_data['filters'], $plan_id);
            Tool_Common::log('/codes/'.__FUNCTION__, 'INFO', '过滤号码', ['plan_id'=>$plan_id, 'tz_type'=>$tz_type, 'filters'=>$codes_hz_data['filters'], 'codesArr'=>$codesArr, 'get_counts'=>count($codesArr), 'filter_codes'=>$filter_codes, 'filter_counts'=>count($filter_codes)]);
            //p(['codesArr'=>$codesArr, 'filter_codes'=>$filter_codes]);
            $codesArr = array_diff($codesArr, $filter_codes); # 返回$codes在$filter_codes中没有的号码
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
       if($UserSysPlans->plan_type == 14){
           unset($codes_hz['current_area_profits']);
           unset($codes_hz['start_qihao']);
       }

       $rstFlag = BettingRecords::updateAll(['is_profits_record'=>0, 'is_area_profits'=>0], ['plan_id'=>$id]);
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
     * @param $isAuto - 是否自动,默认自动
     * @return array
     */
    public static function tzByPlanIdNew($planId, $isAuto = 1){
        if(!$plan = UserSysPlans::findOne($planId)){
            return ['status'=>300, 'msg'=>'找不到对应记录'];
        }
        $m = \Yii::$app->cache;
        $redis = new RedisLock();
        $tz_system_id = trim($plan->tz_sites);
        $lottery_type = $plan->lottery_type;

        $rst = [];
        $system_type_id = TzSystems::findOne($tz_system_id)->system_type_id;

        $status = UserService::accountIsExpire($plan->uid, $tz_system_id); # 账号是否过期
        if(!$status && $plan->account != 'gaozi2018'){
            Tool_Common::log('accountIsExpire', 'ERR', '账号过期提示', ['uid'=>$plan->uid, 'account'=>$plan->account, 'tz_system_id'=>$tz_system_id]);
            return ['status'=>300, 'msg'=>'账号过期提示'];
        }

        $qihao = HN0898Service::getQihao($plan->lottery_type);

        # 4、投注号码 codes
        $codes = self::getCodes($plan->tz_type, $plan->buy_type, $plan->sel_same, $plan->hz_Arr, $planId);
        //p([$system_type_id, $plan->tz_type, $plan->buy_type, $plan->sel_same, $plan->hz_Arr, $codes]);

        $isAuto == 0 && BetService::beforeBetNow($plan->account, $tz_system_id, $plan->lottery_type, $qihao, $plan->id, $plan->uid); # 手动下注时，先删除缓存

        $is_test = $plan->is_test;
        list($sn, $snid) = BetService::getBetSnId($planId, $plan->plan_type, $is_test, $isAuto);

        if($is_test == 1 OR $plan->uid == 1){ # 模拟下注
            $mkey = self::buildBetPlanIdKey($plan->account, $qihao, $plan->id);
            $time = BetService::getBetCacheTime($plan->lottery_type, $qihao); # 投注之后缓存时间
            if($tzflag = $m->get($mkey)) return ['status'=>300, 'msg'=>'已经投注过了~'];
            $m->set($mkey, 1, $time);
            $tmpRst = self::_logRecordsByPlandId($planId, $qihao, $codes, $plan->lottery_type, $is_test = 1, $sn, $snid, $r=1); # 直接记录表
        }else{ # 正式下注

            $not_need_login_tz_system_ids = explode(',', $val = SystemConfig::findOne(['key'=>'not_need_login_tz_system_ids'])->value); # 无需登陆站点
            # 1、首先判断是否登录，否则登录之后再下注
            if(!in_array($tz_system_id, $not_need_login_tz_system_ids)){
                $lKey = 'bet_before_login_flag_'.$plan->uid;
                $RKey = $lKey.'_redis';
                if(!$flag = $m->get($lKey) && $redisLock = $redis->lock($RKey, 10)){
                    $flag = self::isLogin($plan->uid, $tz_system_id, $r=3);
                    $m->set($lKey, $flag, 15);
                }
                if(!$flag){
                    if(!$TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$plan->uid, 'tz_system_id'=>$tz_system_id, 'status'=>1])){
                        $msg = '账号已被禁用不能下注';
                        Tool_Common::log('tzByPlanId_isLogin','INFO','投注记录tzByPlanId', ['uid'=>$plan->uid,'account'=>$plan->account, 'msg'=>$msg]);
                        return ['status'=>400, 'msg'=>$msg];
                    }
                    $loginRst = BaseService::login($TzSystemsUsers->id);
                    Tool_Common::log('tzByPlanId_isLogin','INFO','投注记录tzByPlanId', ['loginRst'=>$loginRst, 'uid'=>$plan->uid, 'TzSystemsUsers_id'=>$TzSystemsUsers->id]);
                    if($loginRst['status'] != 200) return $loginRst;
                }
            }

            $activeQihao = BetService::getActiveQihao($plan->uid, $tz_system_id, $lottery_type);
            if(!$activeQihao OR (isset($activeQihao['status']) && $activeQihao['status'] == '30200')){
                return ['status'=>300, 'msg'=>'无可下注的期号或者代理IP获取异常'];
            }
            $mkey = self::buildBetPlanIdKey($plan->account, $activeQihao, $plan->id);
            if($tzflag = $m->get($mkey)) return ['status'=>300, 'msg'=>'已经投注过了~'];
            $time = BetService::getBetCacheTime($plan->lottery_type, $activeQihao); # 投注之后缓存时间
            $m->set($mkey, 1, $time);

            $logArr = ['uid'=>$plan->uid, 'planId'=>$planId, 'qihao'=>$qihao, 'activeQihao'=>$activeQihao, 'time'=>$time, 'mkey'=>$mkey, 'account'=>$plan->account, 'tz_system_id'=>$tz_system_id];
            Tool_Common::log('tzByPlanIdNew','INFO','投注记录tzByPlanIdNew', $logArr);
            # 5、投注请求
            $BetService = self::getBetObj($plan->uid, $tz_system_id, $plan->lottery_type);
            $tmpRst = $BetService->bet($activeQihao, $plan->id, $codes);
            $logArr = ['account'=>$plan->account, 'tz_sites'=>$tz_system_id,'codes'=>$codes, 'postRst'=>$tmpRst];
            Tool_Common::log('plan_bet_new','INFO','0898投注记录', $logArr);
            if($tmpRst === false){
                Tool_Common::log('/tz_err/tzByPlanId','INFO','投注记录 异常', $logArr);
                return ['status'=>301, 'msg'=>'投注异常', 'tmpRst'=>false];
            }

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
        is_array($tmpRst) && $tmpRst['lottery_type'] = $plan->lottery_type;
        $rst[] = $tmpRst;
        $logArr = ['tz_sites'=>$tz_system_id,'codes'=>$codes, 'postRst'=>$rst];
        Tool_Common::log('plan_bet','INFO','0898投注记录', $logArr);

        return $rst;
    }

    /**
     * @desc 获取方案号
     * @param $planId
     * @param $plan_type
     * @param $is_test
     * @param $isAuto
     * @return string[]
     */
    public static function getBetSnId($planId, $plan_type, &$is_test, $isAuto){
        $sn = BetService::$test_true_sn;
        $snid = BetService::$test_true_snid;

        if(in_array($plan_type, array_merge([6, 8, 9, 14], UserSysPlans::$A_x_arise_B_y_arise_bet_B_types))){ # 6中则投 8、9遗漏多少期投
            //j$flag = SscDataService::isZjBefore($planId); # 上期是否中奖，第一次下注认为是上期不中
            $flag = BetService::getIsBetTrue($planId);
            if(in_array($flag, [0, -1]) && $isAuto == 1){
                $is_test = 1;
                $sn = 'istest';
                $snid = 'istest_id';
            }
            Tool_Common::log('/plan/'.__FUNCTION__.'_is_bet_true', 'INFO', '是否真实下注计划', ['plan_id'=>$planId, 'flag'=>$flag, 'fh'=>(boolean)(in_array($flag, [0, -1]) && $isAuto == 1), 'sn'=>$sn, 'snid'=>$snid]);
        }

        return [$sn, $snid];
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
           $status = UserService::accountIsExpire($plan->uid, $tz_system_id); # 账号是否过期
           if(!$status && $plan->account != 'gaozi2018'){
               Tool_Common::log('accountIsExpire', 'ERR', '账号过期提示', ['uid'=>$plan->uid, 'account'=>$plan->account, 'tz_system_id'=>$tz_system_id]);
               return ['status'=>300, 'msg'=>'账号过期提示'];
           }
           # 4、投注号码 codes
           $codes = self::getCodes($plan->tz_type, $plan->buy_type, $plan->sel_same, $plan->hz_Arr, $planId);
           //p([$plan->tz_type, $plan->buy_type, $plan->sel_same, $plan->hz_Arr, $codes]);

           $isAuto == 0 && BetService::beforeBetNow($plan->account, $tz_system_id, $plan->lottery_type, $qihao, $plan->id, $plan->uid); # 手动下注时，先删除缓存

           if($tzflag = $m->get($mkey)) continue; # ['status'=>300, 'msg'=>'已经投注过了~'];
           $time = BetService::getBetCacheTime($plan->lottery_type, $qihao); # 投注之后缓存时间
           $m->set($mkey, 1, $time);

           $is_test = $plan->is_test;
           list($sn, $snid) = BetService::getBetSnId($planId, $plan->plan_type, $is_test, $isAuto);

           if($is_test == 1 OR $plan->uid == 1){ # 模拟下注
               $tmpRst = self::_logRecordsByPlandId($planId, $qihao, $codes, $plan->lottery_type, $is_test = 1, $sn, $snid, $r=2); # 直接记录表
           }else{ # 正式下注
               $not_need_login_tz_system_ids = explode(',', $val = SystemConfig::findOne(['key'=>'not_need_login_tz_system_ids'])->value); # 无需登陆站点
               # 1、首先判断是否登录，否则登录之后再下注
               if(!in_array($tz_system_id, $not_need_login_tz_system_ids) && !$flag = self::isLogin($plan->uid, $tz_system_id, $r=4)){
                   if(!$TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$plan->uid, 'tz_system_id'=>$tz_system_id, 'status'=>1])){
                       Tool_Common::log('tzByPlanId_isLogin','INFO','投注记录tzByPlanId', ['uid'=>$plan->uid,'account'=>$plan->account, 'msg'=>'账号已被禁用不能下注']);
                       continue;
                   }
                   $loginRst = BaseService::login($TzSystemsUsers->id);
                   Tool_Common::log('tzByPlanId_isLogin','INFO','投注记录tzByPlanId', ['loginRst'=>$loginRst, 'uid'=>$plan->uid, 'TzSystemsUsers_id'=>$TzSystemsUsers->id]);
                   if($loginRst['status'] != 200) return $loginRst;
               }

               $logArr = ['uid'=>$plan->uid, 'planId'=>$planId, 'qihao'=>$qihao, 'time'=>$time, 'mkey'=>$mkey, 'account'=>$plan->account, 'tz_system_id'=>$tz_system_id, 'tz_sites'=>$tz_sites];
               Tool_Common::log('tzByPlanId','INFO','投注记录tzByPlanId', $logArr);
               # 5、投注请求
               $BetService = self::getBetObj($plan->uid, $tz_system_id, $plan->lottery_type);
               $tmpRst = $BetService->bet($qihao, $plan->id, $codes, $isAuto);
               $logArr = ['tz_sites'=>$tz_sites,'codes'=>$codes, 'postRst'=>$rst];
               Tool_Common::log('plan_bet','INFO','0898投注记录', $logArr);
               if($tmpRst === false){
                   Tool_Common::log('/tz_err/tzByPlanId','INFO','投注记录 异常', $logArr);
                   return ['status'=>301, 'msg'=>'投注异常', 'tmpRst'=>false];
               }

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
           is_array($tmpRst) && $tmpRst['lottery_type'] = $plan->lottery_type;
           $rst[] = $tmpRst;
       }
       $logArr = ['tz_sites'=>$tz_sites,'codes'=>$codes, 'postRst'=>$rst];
       Tool_Common::log('plan_bet','INFO','0898投注记录', $logArr);

       return $rst;
    }

    /**
     * @desc 获取计划下期是否可以真实投注
     * @param string $plan_id
     * @return bool 0不中奖1中奖 -1最初添加计划未投注，可当作未中奖，等同于0
     */
    public static function getIsBetTrue($plan_id = ''){

        $plan = UserSysPlans::findOne($plan_id);
        $flag = SscDataService::isZjBefore($plan_id); # 上期是否中奖，第一次下注认为是上期不中 中则投
        $codes_hz = json_decode($plan->hz_Arr, true);
        if(in_array($plan->plan_type, [14])){
            return $codes_hz['areaBetStatus'];
        }else{
            if(in_array($plan->plan_type, [8, 9])){ # 遗漏多少期启投
                $flag = 0;
                if($codes_hz['current_miss']>=$codes_hz['bet_while_miss']){
                    $flag = 1;
                }
            }
            if(in_array($plan->plan_type, UserSysPlans::$A_x_arise_B_y_arise_bet_B_types)) { # A出x次B出y次投B
                $flag = 0;
                if($codes_hz["current_arise_A_times"]>=$codes_hz['arise_A_times'] && $codes_hz["current_arise_B_times"]==$codes_hz['arise_B_times']){
                    $flag = 1;
                }
            }
        }

        return (int)$flag;
    }

    /**
     * @desc 立即投注之前清除缓存锁
     * @param $account
     * @param $tz_system_id
     * @param $qihao
     * @param $lottery_type - 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     * @param $plan_id
     */
    public static function beforeBetNow($account, $tz_system_id, $lottery_type = DEFAULT_LOTTERY_TYPE, $qihao='', $plan_id = 0, $uid = ''){
        $m = \Yii::$app->cache;
        $mkey = BetService::buildBetKey($account, $tz_system_id, $lottery_type, $qihao, $plan_id);
        $m->delete($mkey);

        $pkey = BetService::buildBeforeAndAfterBetKey($lottery_type, $qihao, $uid);
        $betCacheTime = BetService::getBetCacheTime($lottery_type);
        $m->set($pkey, 1, $betCacheTime);

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
    public static function buildBeforeAndAfterBetKey($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao='', $uid=''){

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
     * @return bool|array
     */
    public static function _logRecords($data){
        if(!$data OR !is_array($data)) return false;
        $insertData = [
            'sn'=>$data['sn'] ? $data['sn'] : BetService::$test_true_sn, // 方案号
            'snid'=>$data['snid'] ? $data['snid'] : BetService::$test_true_snid,
            'is_profits_record'=> in_array($data['sn'], BetService::$test_static_sn) ? 0 : 1, # 是否计算盈利
            'is_area_profits'=> in_array($data['sn'], BetService::$test_static_sn) ? 0 : 1, # 是否计算盈利
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
            'post_desc' => $data['post_desc'],  // 投注文本
            'betting_money'=> $data['betting_money'],  // 投注金额
            'tz_system_id'=> $data['tz_system_id'],  // 投注系统tz_systems .id
            'lottery_type'=> $data['lottery_type'],  // 彩种
            'lotteryclass'=> 'ssc',  // 彩种
            'is_simulate' => $data['is_simulate'],  // 是否模拟投注
            'is_batch_simulate' => $data['is_batch_simulate'] ? 1 : 0,  // 是否模拟投注
            'position' => $data['position'],  // 是否模拟投注
            'order_type' => $data['order_type'],  // 订单来源
            'bonus' => 0.00,  // 奖金
            'status' => 0,  // 中奖状态：0:正常、1:中奖、2:未中奖
            'createtime' => time(),  // 下单时间 int
            'create_time' => date('Y-m-d H:i:s'),  // 下单时间 string
        ];
        //if($data['tz_type'] == 20) $insertData['codes'] = md5($insertData['codes']);
        $where = ['AND', ['=', 'qihao', $data['qihao']], ['=', 'plan_id', $data['plan_id']], ['=', 'uid', $data['uid']]];
        $flag = BettingRecords::find()->where($where)->one();
        if($flag){
            return ['status'=>200, 'msg'=>'写入成功'];
        }

        $bettingRecords = new BettingRecords();
        $bettingRecords->setAttributes($insertData);
        $rst = $bettingRecords->save();

        if(!$rst){
            Tool_Common::log('logRecords', 'INFO', '记录投注表', ['msg'=>$bettingRecords->getErrors()]);
            return ['status'=>300,'msg'=>current($bettingRecords->getErrors())];
        }

        return ['status'=>200, 'data'=>['record_id'=>$bettingRecords->id], 'msg'=>'操作成功'];
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
        }elseif(in_array($tz_system_id, [12])){ # 九九网
            if($lottery_type == 6){ # 新疆
                $rst = NineNineNewService::cancelOrder($bet_id, $BettingRecords->tz_system_id);
            }else{
                $rst = NineNineNewService::cancelOrder($bet_id, $BettingRecords->tz_system_id);
            }
        }elseif(in_array($tz_system_id, [13])){ # 冰岛
            $rst = \backend\service\BingDao\BingDaoService::cancelOrder($bet_id, $BettingRecords->tz_system_id);
        }elseif(in_array($tz_system_id, [11])){ # 菊花网
            if($lottery_type == 5){
                $rst = JuHuaBaseService::cancelOrder($bet_id, $BettingRecords->tz_system_id);
            }else{
                $rst = JuHuaBaseService::cancelOrder($bet_id, $BettingRecords->tz_system_id);
            }
        }elseif(in_array($tz_system_id, [6])){
            $rst = ZhongFaService::cancelOrder($bet_id, $BettingRecords->tz_system_id);
        }elseif(in_array($tz_system_id, [16])){
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
    public static function isLogin($uid, $tz_system_id, $r=''){
        $m = \Yii::$app->cache;
        $mkey = 'isLogin_'.$uid.'_'.$tz_system_id;
        $flag = $m->get($mkey);
        if($flag) return (boolean)$flag;

        $RedisLock = new RedisLock();
        $Rkey = 'IsLogin_redis_'.$uid.'_'.$tz_system_id;
        if(!$RedisLock->lock($Rkey.'_redis', 2)){
            return true;
        }

        Tool_Common::log('isLogin_REQ', 'INFO', '是否登陆', ['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'r'=>$r]);
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
        }elseif(in_array($tz_system_id, [12])){
            # 九九新网
            $flag = NineNineNewService::isLogin($uid, $tz_system_id);
        }elseif(in_array($tz_system_id, [11])){
            # 11、菊花网
            $flag = JuHuaBaseService::isLogin($uid, $tz_system_id);
        }elseif(in_array($tz_system_id, [13])){
            # 13、冰岛
            $flag = \backend\service\BingDao\BingDaoService::isLogin($uid, $tz_system_id);
        }elseif(in_array($tz_system_id, [16])){
            # 16、台湾快五
            $flag = ZhongFaService::isLogin($uid, $tz_system_id);
        }
        $flag = (boolean)$flag;
        $time = ($r==2 && $flag) ? 180 : 60;
        $m->set($mkey, $flag, $time);

        return $flag;
    }

    /**
     * @desc 用户刷新个人信息
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
        return SystemService::getConfig($key);
    }

    /**
     * @desc 获取投注缓存时间，一般为开奖时间频率
     * @param int $lottery_type
     * @param $qihao - 已经开奖的期号
     * @return float|int|string
     */
    public static function getBetCacheTime($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao=''){
        $lottery = LotteryType::findOne(['lottery_type'=>$lottery_type]);
        $cacheTime = $lottery->data_ftime;
        $now_HI = date('H:i:s');
        switch ($lottery_type){
            case 1: # 希腊1.5分彩
                $cacheTime = 86400*2;
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
                $cacheTime = 20 * 60;
                break;
            case 7: # 北京快乐8
                $cacheTime = 5 * 60;
                break;
            case 8: # 幸运五星彩
                $cacheTime = 6 * 60;
                $min_qihao = substr($qihao, -3);
                if(($min_qihao == '048') OR ('04:05:00'<$now_HI && $now_HI<'09:05:00')){
                    $cacheTime = 5 * 3600;
                }
                break;
            case 9: # 台湾宾果
                $cacheTime = 5 * 60;
                break;
            case 10: # 冰岛90s
                $cacheTime = 1.5 * 60;
                break;
            case 11: # 冰岛3分
            case 23: # 以太坊3分
                $cacheTime = 5 * 60;
                break;
            case 16: # 加拿大28   3.5分
                $cacheTime = 3.5 * 60;
                break;
            case 24: # 以太坊10分
                $cacheTime = 10.5 * 60;
                break;
            case 17: # 排列五
            case 25: # 江苏七位数
                $cacheTime = 86400;
                break;
            case 18: #  宝岛众发  台湾5分
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
     * @param int $is_test 0正常1测试2批量模拟
     * @param string $sn
     * @param string $snid
     * @return array|bool
     */
    public static function _logRecordsByPlandId($plan_id, $qihao, $codes, $lottery_type = DEFAULT_LOTTERY_TYPE, $is_test = 0, $sn='888888', $snid='888888id', $r=0){
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
            'sn'=>$sn ? $sn : BetService::$test_true_sn,
            'snid'=>$snid ? $snid : BetService::$test_true_snid,
            'order_type'=>$UserSysPlans->playway, # 单双三字定
            'is_simulate' => $is_test ? 1 : 0,  // 是否模拟投注
            'is_batch_simulate' => ($is_test==2) ? 1 : 0,  // 是否批量模拟
            'single' => $UserSysPlans->single,  // 投注倍数
            'betting_money'=> round($totalmoney,2),  // 投注金额
        ];
        //p($insertData,0);
        $insertRst = BetService::_logRecords($insertData);
        unset($insertData['codes']);
        Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '记录', ['insertRst'=>$insertRst, 'insertData'=>$insertData, 'r'=>$r]);

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
        $query = Num4Type::find()->where($where);
        $filter_poses = NumService::getFilterPosByCode($codesArr[0]); # 根据导入的号码判断要过滤的位置
        if(!empty($filter_poses)){
            foreach ($filter_poses as $pos){
                $query->andWhere(['<>', 'code_'.$pos, 'X']);
            }
        }
        $Num4Type = $query->asArray()->all();
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


    /**
     * @desc 批量填插入用户计划任务
     * @return array
     */
    public static function insertPlansTask($lottery_types = [], $isAuto=1){
        $rst = ['status'=>300, 'msg'=>'操作成功'];
        $lottery_types = $lottery_types ? : StaticService::getLotteryTypes();

        $m = \Yii::$app->cache;
        foreach ($lottery_types as $lottery_type){
            # is_batch_simulate:0正常1批量模拟历史记录
            $where = ['AND', ['=', 'status', 1], ['OR', ['=', 'is_batch_simulate', 0], ['IS', 'is_batch_simulate', NULL]], ['=', 'lottery_type', $lottery_type]];

            $plans = UserSysPlans::find()->where($where)->all();
            Tool_Common::log('/plans_tasks/'.__FUNCTION__, 'INFO', '批量插入任务000', ['lottery_type'=>$lottery_type, 'counts'=>count($plans)]);
            if(empty($plans)){
                Tool_Common::log('plan_is_active', 'INFO', '投注计划', ['lottery_type'=>$lottery_type, 'msg'=>'没有开启的计划', 'uid'=>$plans[0]->uid]);
                continue;
            }
            try {
                $m = \Yii::$app->cache;
                foreach ($plans as $plan){
                    $tz_system_id = $plan->tz_sites;
                    $lottery_type = $plan->lottery_type;
                    $uid = $plan->uid;
                    $qihao = HN0898Service::getQihao($lottery_type);

                    $insert_mkey = 'insertPlanTask_key_'.$lottery_type.'_'.$plan->id;
                    if($m->get($insert_mkey)){
                        continue;
                    }
                    $Task = BetErrorPlansTask::findOne(['plan_id'=>$plan->id, 'qihao'=>$qihao, 'lottery_type'=>$lottery_type]);
                    if($Task){
                        $logArr = ['status'=>200, 'msg'=>'已记录推送表'.$lottery_type.'_'.$qihao];
                        Tool_Common::log('insert_plan_task', 'ERR', '写入计划任务表', $logArr);
                        throw new Exception('已记录推送表'.$lottery_type.'_'.$qihao);
                    }
                    $next_qihao_is_active = TzService::beforeBet($lottery_type, $current_active_qihao);
                    $DataDealStatus = DataDealStatus::find()->where(['lottery_type'=>$lottery_type, 'qihao'=>$qihao])->asArray()->one();
                    Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '数据处理状态', ['DataDealStatus'=>$DataDealStatus]);
                    if($next_qihao_is_active['status'] != 200){
                        Tool_Common::log('next_qihao_is_active', 'INFO', '期号激活', ['lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'uid'=>$uid, 'plan_id'=>$plan->id, 'next_qihao_is_active'=>$next_qihao_is_active, 'msg'=>'期号未被激活'.$lottery_type.'_'.$qihao, 'current_active_qihao'=>$current_active_qihao]);
                        throw new \yii\base\Exception('期号未被激活'.$lottery_type.'_'.$qihao);
                    }

                    # 4、投注号码 codes
                    $codes = self::getCodes($plan->tz_type, $plan->buy_type, $plan->sel_same, $plan->hz_Arr, $plan->id);

                    $is_test = $plan->is_test;
                    list($sn, $snid) = BetService::getBetSnId($plan->id, $plan->plan_type, $is_test, $isAuto);

                    if($is_test == 1 OR $plan->uid == 1){ # 模拟下注
                        $testInsertRst = self::_logRecordsByPlandId($plan->id, $qihao, $codes, $plan->lottery_type, $is_test, $sn, $snid, $r=3); # 直接记录表
                        if($testInsertRst['status'] == 200){
                            $m->set($insert_mkey, 1, 120);
                        }
                    }else{
                        Tool_Common::log('insertPlansTask', 'INFO', '批量填插入用户计划任务-1', ['plan_id'=>$plan->id, 'lottery_type'=>$lottery_type, 'uid'=>$uid]);

                        $BetService = self::getBetObj($plan->uid, $tz_system_id, $lottery_type);
                        $activeQihao = BetService::getActiveQihao($uid, $tz_system_id, $lottery_type);
                        if(!$activeQihao OR (isset($activeQihao['status']) && $activeQihao['status'] == '30200')){
                            Tool_Common::log('accountIsExpire', 'ERR', '封盘或者未开盘-2', ['uid'=>$plan->uid, 'lottery_type'=>$lottery_type, 'account'=>$plan->account, 'tz_system_id'=>$tz_system_id, 'activeQihao'=>$activeQihao]);
                            throw new \yii\base\Exception('封盘或者未开盘-2');
                        }

                        if($current_active_qihao != $activeQihao){
                            throw new \yii\base\Exception('网盘期号未开盘_'.$lottery_type.'_'.$plan->id.'_'.$current_active_qihao.'_'.$activeQihao);
                        }

                        $status = UserService::accountIsExpire($plan->uid, $tz_system_id); # 账号是否过期
                        if(!$status && $plan->account != 'gaozi2018'){
                            Tool_Common::log('accountIsExpire', 'ERR', '账号过期提示-2', ['uid'=>$plan->uid, 'account'=>$plan->account, 'tz_system_id'=>$tz_system_id]);
                            throw new \yii\base\Exception('账号过期提示-2');
                        }

                        $preInsertLockKey = 'preInsertLockKey_'.$plan->id.'_'.$activeQihao;

                        if($lock = $m->get($preInsertLockKey))continue;
                        $time = BetService::getBetCacheTime($lottery_type, $activeQihao); # 投注之后缓存时间
                        $m->set($preInsertLockKey, 1, $time);

                        $insertRst = $BetService->postBatchBet($activeQihao, $plan->id, $codes);
                        $rst['data'][$plan->id] = $insertRst;
                        $logArr = ['uid'=>$uid, 'account'=>$plan->account, 'plan_id'=>$plan->id, 'activeQihao'=>$activeQihao, 'insertRst'=>$insertRst];
                        Tool_Common::log('insertPlansTask', 'INFO', '批量填插入用户计划任务-2', $logArr);
                    }
                }
            }catch (\Exception $e){
                Tool_Common::log('/bet/'.__FUNCTION__, 'ERR', '批量插入下注计划异常', ['uid'=>$uid, 'lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
                continue;
            }
        }

        return $rst;
    }

    /**
     * @param $plan_ids
     * @param $access_token
     * @return array
     */
    public static function getCodesByPlanIds($plan_ids, $access_token){
        $rst = ['status'=>200, 'data'=>[]];

        $TzSystemsUsers = TzSystemsUsers::findOne(['access_token'=>$access_token]);
        $uid = $TzSystemsUsers->uid;

        $Plans = UserSysPlans::find()->where(['uid'=>$uid, 'id'=>$plan_ids])->all();
        foreach ($Plans as $plan){
            $tmpData = ['plan_id'=>$plan->id];
            $tmpData['codes'] = self::getCodes($plan->tz_type, $plan->buy_type, $plan->sel_same, $plan->hz_Arr, $plan->id);
            Tool_Common::log('/codes/'.__FUNCTION__, 'INFO', '计划的号码', ['uid'=>$uid, 'id'=>$plan_ids, 'tmpData'=>$tmpData]);
            $rst['data'][] = $tmpData;
        }
        return $rst;
    }

    /**
     * @desc 批量模拟历史数据下注
     * @param array $lottery_types
     * @param int $isAuto
     */
    public static function batchSimulateBet($lottery_types = [], $uid='', $isAuto=1){

        $rst = ['status'=>200, 'msg'=>'操作成功'];
        $lottery_types = $lottery_types ? : StaticService::getLotteryTypes();
        $m = \Yii::$app->cache;
        $mkey = 'batchSimulateBet_'.$uid;
        $flag = $m->get($mkey);
        if($flag){
            return ['status'=>300, 'msg'=>'有正在执行的任务,请稍后...'];
        }
        $m->set($mkey, 1, 10);
        $RedisLock = new RedisLock();

        foreach ($lottery_types as $lottery_type) {
            $where = ['AND', ['=', 'status', 1], ['=', 'is_batch_simulate', 1], ['=', 'lottery_type', $lottery_type]]; # is_batch_simulate:0正常1批量模拟历史记录
            if(!empty($where)){
                $where[] = ['=', 'uid', $uid];
            }

            $plans = UserSysPlans::find()->where($where)->all();
            if (empty($plans)) {
                Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '投注计划', ['uid'=>$uid,'lottery_type' => $lottery_type, 'msg' => '没有开启的计划']);
                continue;
            }
            foreach ($plans as $plan) {
                $rst = ['status'=>200, 'data'=>['plan_id'=>$plan->id], 'msg'=>'操作成功'];
                try {
                    $lottery_type = $plan->lottery_type;
                    $plan_id = $plan->id;
                    $codes_hz_data = json_decode($plan->hz_Arr, true);
                    $filter_poses = $codes_hz_data['filters']['filter_poses'];
                    $x_poses = array_diff(NumService::$ALL_POSES, $filter_poses);
                    foreach ($x_poses as $x_pos){
                        $codes_hz_data['p'.$x_pos] = 'X';
                    }
                    $current_qihao = NumService::getPlanBetCurrentQihao($plan_id, $lottery_type);
                    $mkey_current = 'getPlanBetCurrentQihao_'.$plan_id.'_'.$current_qihao;
                    if(!$RedisLock->lock($mkey_current.'_redis', 60)){
                        $logArr = ['uid'=>$uid, 'plan_id'=>$plan_id, 'current_qihao'=>$current_qihao, 'err_msg'=>'频繁请求，缓存60秒'];
                        //Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '计划模拟-00', $logArr);
                        continue;
                    }

                    //p([$current_qihao, $codes_hz_data]);
                    $beforeQihao = KjDataGet::getBeforeQihaoByQihao($current_qihao, $lottery_type);
                    $before_record = BettingRecords::findOne(['qihao'=>$beforeQihao, 'plan_id'=>$plan_id]);
                    if(!empty($before_record) && $before_record->status==0){
                        return BetService::opOneBettingRecordAndHandlePlanStatic($before_record->id, $plan_id, $beforeQihao, $rst);
                    }

                    $isCanBet = SscDataService::isCanBet($plan_id, $current_qihao);
                    if(!empty($before_record) && $before_record->status!=1 && !$isCanBet){
                        $logArr = ['uid'=>$uid, 'plan_id'=>$plan_id, 'current_qihao'=>$current_qihao, 'beforeQihao'=>$beforeQihao, 'isCanBet'=>$isCanBet, 'before_record'=>!empty($before_record), 'err_msg'=>'暂时不可以下注'];
                        Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '计划模拟-01', $logArr);
                        continue;
                    }

                    # 4、投注号码 codes
                    $codes = self::getCodes($plan->tz_type, $plan->buy_type, $plan->sel_same, json_encode($codes_hz_data), $plan->id);

                    $is_test = $plan->is_test;
                    list($sn, $snid) = BetService::getBetSnId($plan->id, $plan->plan_type, $is_test, $isAuto);

                    Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '投注号码', ['flag'=>$flag, 'qihao'=>$current_qihao, 'plan_id'=>$plan_id]);

                    if ($is_test == 1 or $plan->uid == 1) { # 模拟下注
                        $insertRst = self::_logRecordsByPlandId($plan->id, $current_qihao, $codes, $plan->lottery_type, 2, $sn, $snid, $r=4); # 直接记录表
                        $rst['data'][$plan_id]['logRecord_rst'] = ['rst'=>$insertRst, 'qihao'=>$current_qihao];
                    }
                    Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '计划模拟-1', ['plan_id'=>$plan_id, 'rst'=>$rst]);
                    if($insertRst['status'] == 200){
                        BetService::opOneBettingRecordAndHandlePlanStatic($insertRst['data']['record_id'], $plan_id, $current_qihao, $rst); # 处理开奖和计划相关
                    }
                }catch (\Exception $exception){
                    Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '计划模拟失败', ['plan_id'=>$plan_id, 'err_msg'=>$exception->getMessage()]);
                }
            }
        }
        $m->delete($mkey);

        return $rst;
    }

    /**
     * @desc 处理开奖和计划相关
     * @param array $insertRst
     * @param string $plan_id
     * @param $qihao
     * @param array $rst
     * @return bool
     */
    public static function opOneBettingRecordAndHandlePlanStatic($record_id, $plan_id='', $qihao='', &$rst=[]){
        # 下注完、处理开奖
        if(!$record_id) return false;
        $opKjRst = OpKjService::opOneBettingRecord($record_id);
        $rst['data'][$plan_id]['opKjRst'] = $opKjRst;

        if($opKjRst['status'] == 200){
            $opHandlePlanRst = SscDataService::handleOnePlanStatic($plan_id, $qihao);
            $rst['data'][$plan_id]['opHandlePlanRst'] = $opHandlePlanRst;
        }

        return true;
    }

    /**
     * @desc 更换代理ip
     * @return bool
     */
    public static function changPoxyIp(){
        $m = \Yii::$app->cache;
        $mkey = PoxyIPService::builProxyIpKey($mod_uid=0);
        //$rst = $m->get($mkey);p($rst);
        $rst = $m->delete($mkey);
        return $rst;
    }


}