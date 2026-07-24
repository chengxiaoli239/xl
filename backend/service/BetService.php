<?php
/**
 * Created by PhpStorm.
 *
 * Date: 2018/12/10
 * Time: 17:28
 */

namespace backend\service;

use backend\models\BetErrorPlansTask;
use backend\models\DataDealStatus;
use backend\models\PlanStaticProfits;
use backend\models\SscKjData;
use backend\models\thirdD\BetsBackend;
use backend\service\clients\AgentClientsService;
use backend\service\clients\TzSystemUsersService;
use backend\service\huiyuan\HuiYuanService5;
use backend\models\LotteryType;
use backend\models\SystemConfig;
use backend\service\huiyuan\KuaiLe8Service;
use backend\service\Juhua\JuHuaBaseService;
use backend\service\LeCai\ZhongFaService;
use backend\service\Lucky5\Lucky5Service;
use backend\service\NineNine\NineNineBaseService;
use backend\service\NineNine\NineNineNewService;
use backend\service\NineNine\NineNineService6;
use backend\service\numbers\DynamicFilterService;
use backend\service\numbers\NumCodeService;
use common\framework\Redis;
use common\kj\cqssc\CqsscKcw;
use common\service\cache\CacheKeyService;
use common\service\jobs\plan\UserPlanBetJob;
use common\service\jobs\plan\UserTaskBetJob;
use common\service\lottery\aozhou5\jobs\AoZhou5BetJobs;
use common\service\lottery\LotteryTypeService;
use common\service\proxy\ProxyBaseService;
use common\service\ssc\QihaoService;
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
use yii\helpers\Json;

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
    const CODES_FILTER_TYPES_2 = 2; # 过滤类型2
    const CODES_FILTER_TYPES_3 = 3; # 过滤类型3
    const STOP_BET_CODE = 30003; # 止盈止损code

    protected function __construct() {
        parent::__construct();
    }

    /**
     * 获取下注任务model
     * @param int $lotteryType
     * @return string BetsBackend::class|BetErrorPlansTask::class
     * @throws \common\exceptions\InfoException
     */
    public static function getBetModel(int $lotteryType = DEFAULT_LOTTERY_TYPE): string
    {
        $lotteryTypes = [
            \common\helpers\LotteryType::AZ_LUCKY_5 => BetsBackend::class,
            \common\helpers\LotteryType::LUCKY_5 => BetErrorPlansTask::class
        ];
        if(!isset($lotteryTypes[$lotteryType])){
            throw_info('未找到对应model');
        }
        return $lotteryTypes[$lotteryType];
    }

    /**
     * @desc 获取对象
     * @param $uid
     * @param $tz_system_id - 表lt_tz_systems.id
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return HN0898Service|SevenService|Lucky5Service|NineNineNewService
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
     * @param $buy_type 0反买1正买
     * @param float $single
     * @param array|string $hz_Arr
     * @return string
     */
    public static function getCodes($tz_type, $buy_type, $hz_Arr = [], $plan_id = ''){
        $codes = BetService::getPlansAllCodesType1($tz_type, $buy_type, $hz_Arr, $plan_id);

        return $codes;
    }

    /**
     * @desc 投注号码是否有效，避免基础号码表或筛选异常时写入空投注记录
     */
    public static function hasValidBetCodes($codes){
        if(is_array($codes)){
            foreach ($codes as $code){
                if(trim((string)$code) !== ''){
                    return true;
                }
            }
            return false;
        }

        return trim((string)$codes) !== '';
    }

    /**
     * @desc 远程盘口下注请求最多重试3次，仅处理网络/代理/超时/空响应类异常
     * @param callable $callback
     * @param array $context
     * @param int $maxAttempts
     * @param int $sleepSeconds
     * @return mixed
     */
    public static function requestBetWithRetry(callable $callback, array $context = [], int $maxAttempts = 3, int $sleepSeconds = 1)
    {
        $maxAttempts = max(1, $maxAttempts);
        $lastRst = null;
        $attempts = 0;

        for($attempt = 1; $attempt <= $maxAttempts; $attempt++){
            $attempts = $attempt;
            $startTime = microtime(true);
            try {
                $lastRst = $callback($attempt);
            }catch (\Exception $exception){
                $lastRst = self::buildRetryableBetResponse($exception->getMessage());
            }

            $lastRst = self::normalizeBetRetryResponse($lastRst);
            $retryable = self::isRetryableBetResponse($lastRst);
            $timeConsume = sprintf('%.3fs', microtime(true) - $startTime);
            $proxySwitchRst = $retryable ? self::switchProxyAfterBetFailure($lastRst, $context) : [];
            if(is_array($lastRst) && !empty($proxySwitchRst)){
                $lastRst['proxy_switch'] = $proxySwitchRst;
            }

            if(!$retryable || $attempt >= $maxAttempts){
                break;
            }

            Tool_Common::log('/repeatErrorBet/requestBetWithRetry', 'WARN', '远程盘口请求异常重试', array_merge($context, [
                'attempt' => $attempt,
                'max_attempts' => $maxAttempts,
                'time_consume' => $timeConsume,
                'proxy_switch' => $proxySwitchRst,
                'rst' => self::getBetRetryLogSummary($lastRst),
            ]));

            if($sleepSeconds > 0){
                sleep($sleepSeconds);
            }
        }

        if(is_array($lastRst)){
            $lastRst['bet_retry_attempts'] = $attempts;
        }

        return $lastRst;
    }

    /**
     * @desc 判断下注请求结果是否属于可重试异常
     * @param mixed $response
     * @return bool
     */
    public static function isRetryableBetResponse($response): bool
    {
        if($response === null || $response === false || $response === ''){
            return true;
        }
        if(is_array($response) && empty($response)){
            return true;
        }
        if(!is_array($response)){
            return self::containsRetryableBetText((string)$response);
        }

        if((isset($response['Status']) && (int)$response['Status'] === 1)
            || (isset($response['success']) && $response['success'] === true)
            || (isset($response['rstData']['code']) && (int)$response['rstData']['code'] === 200)){
            return false;
        }

        $code = isset($response['code']) ? (int)$response['code'] : 0;
        if(in_array($code, [302, 303, 304, 305, 306, 307, 310, 313, 314, 315], true)){
            return false;
        }
        if(!empty($response['proxy_policy_failure']) || !empty($response['proxy_required_stop'])){
            return false;
        }
        if(isset($response['errno']) && (int)$response['errno'] > 0){
            return true;
        }
        if(in_array($code, [309, 311, 312], true)){
            return true;
        }
        if(array_key_exists('rstData', $response) && empty($response['rstData'])){
            return true;
        }

        return self::containsRetryableBetText(json_encode($response, 320));
    }

    private static function normalizeBetRetryResponse($response)
    {
        if($response === null || $response === false || $response === '' || (is_array($response) && empty($response))){
            return self::buildRetryableBetResponse('远程盘口无响应');
        }

        return $response;
    }

    private static function buildRetryableBetResponse(string $message): array
    {
        return [
            'Status' => 0,
            'success' => false,
            'code' => 309,
            'errno' => 1,
            'msg' => $message ?: '远程盘口请求异常',
            'rstData' => [
                'code' => 500,
                'errorCode' => 'FAIL',
                'msg' => $message ?: '远程盘口请求异常',
            ],
        ];
    }

    private static function switchProxyAfterBetFailure($response, array $context = []): array
    {
        if(!self::isProxyFailureBetResponse($response)){
            return [];
        }
        if(is_array($response) && (!empty($response['proxy_policy_failure']) || !empty($response['proxy_required_stop']))){
            return [];
        }

        $uid = (int)($context['uid'] ?? ($response['uid'] ?? 0));
        $proxyType = $uid ? ProxyBaseService::getProxyTypeByUid($uid) : ProxyBaseService::getProxyType();
        $proxyIp = is_array($response) ? (string)($response['proxy_ip'] ?? '') : '';
        $responseText = is_array($response) ? json_encode($response, 320) : (string)$response;
        $isProxyText = stripos($responseText, '代理') !== false || stripos($responseText, 'Proxy') !== false;
        if($uid && !$proxyIp && !self::isRetryProxyOpen($uid)){
            return [];
        }
        if(!$uid && !$proxyIp && !$isProxyText){
            return [];
        }
        if(!$proxyIp){
            $currentProxyIp = ProxyBaseService::getCurrentValidProxyIp($proxyType, 2);
            $proxyIp = is_string($currentProxyIp) ? $currentProxyIp : '';
        }
        $clearRst = ProxyBaseService::clearCurrentProxyIp($proxyType, $proxyIp);

        Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'WARN', '代理IP异常切换', array_merge($context, [
            'uid'=>$uid,
            'proxy_type'=>$proxyType,
            'proxy_ip'=>$proxyIp,
            'clear_rst'=>$clearRst,
            'rst'=>self::getBetRetryLogSummary($response),
        ]));

        return [
            'status'=>200,
            'proxy_type'=>$proxyType,
            'old_proxy_ip'=>$proxyIp,
            'clear_rst'=>$clearRst,
        ];
    }

    private static function isRetryProxyOpen($uid): bool
    {
        if(!$uid){
            return false;
        }
        $rows = TzSystemsUsers::find()->where(['uid'=>(int)$uid, 'is_use_proxy'=>1])->all();
        foreach($rows as $TzSystemsUsers){
            if(BaseService::isProxySceneOpen($TzSystemsUsers, BaseService::PROXY_SCENE_BET)){
                return true;
            }
        }

        return false;
    }

    private static function isProxyFailureBetResponse($response): bool
    {
        if(!is_array($response)){
            return self::containsRetryableBetText((string)$response);
        }
        if(!empty($response['proxy_switch']) || !empty($response['proxy_policy_failure']) || !empty($response['proxy_required_stop'])){
            return false;
        }

        $code = isset($response['code']) ? (int)$response['code'] : 0;
        if(in_array($code, [309, 311, 312], true)){
            return true;
        }
        if(isset($response['errno']) && (int)$response['errno'] > 0){
            return true;
        }

        return self::containsRetryableBetText(json_encode($response, 320));
    }

    private static function containsRetryableBetText(string $text): bool
    {
        $nonRetryableKeywords = ['余额不足', '已关盘', '停押', '重复提交', '请重新登录', 'cookie', '维护中', 'Illegal X-Csrf-Token', '快代理拒绝连接当前盘口域名', '下注代理已开启，但代理IP获取失败'];
        foreach($nonRetryableKeywords as $keyword){
            if(stripos($text, $keyword) !== false){
                return false;
            }
        }

        $retryableKeywords = ['超时', '网络', '代理IP网络故障', 'Proxy Connect Error', 'Too Many Request', 'Too Many Requests', 'timeout', 'timed out', 'Failed to connect', 'Connection refused', 'Connection reset', 'Bad Gateway', 'Gateway Timeout', 'Empty reply', 'resolve host', 'SSL connect'];
        foreach($retryableKeywords as $keyword){
            if(stripos($text, $keyword) !== false){
                return true;
            }
        }

        return false;
    }

    private static function getBetRetryLogSummary($response)
    {
        if(!is_array($response)){
            $response = (string)$response;
            return strlen($response) > 500 ? substr($response, 0, 500).'...' : $response;
        }

        $text = json_encode($response, 320);
        return strlen($text) > 500 ? substr($text, 0, 500).'...' : $text;
    }

    /**
     * @desc 彩种名称缓存key
     * @return string
     */
    public static function buildLotteryNameKey(){
        return 'buildLotteryNameKey_1';
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
     * 单个任务下注
     * @param $taskId
     * @param string $activeQiHao
     * @return string
     */
    public static function betUserOneTask($taskId, string $activeQiHao=''): string
    {
        try {
            $betErrorPlansTask = BetErrorPlansTask::findOne($taskId);
            $lottery_type = $betErrorPlansTask->lottery_type;
            if(empty($activeQiHao)){
                list($currentKjQiHao, $activeQiHao) = QihaoService::getKjQiHao($lottery_type);
            }
            $uid = $betErrorPlansTask->uid;
            $is_local_bet = $betErrorPlansTask->is_local_bet;
            $task_id = $betErrorPlansTask->id;
            $tz_system_id = $betErrorPlansTask->tz_system_id;
            $playway = $betErrorPlansTask->playway;
            $account = $betErrorPlansTask->account;
            $plan_id = $betErrorPlansTask->plan_id;
            $bet_sort_key = $betErrorPlansTask->bet_sort_key;

            $qihao = $betErrorPlansTask->qihao;
            Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'ERR', '用户计划下注脚本-2', ['task_id'=>$task_id, 'plan_id'=>$plan_id, 'uid'=>$uid, 'lottery_type'=>$lottery_type, 'account'=>$account, 'tz_system_id'=>$tz_system_id, 'qihao'=>$qihao]);

            $BetService = self::getBetObj($uid, $tz_system_id, $lottery_type);
            if($qihao == $activeQiHao){ # 0:云服务
                $DataDealStatus = BetService::getDataDealStatus($lottery_type, $qihao, 'opProfitsPlans_status');
                Tool_Common::log('/plan/data_deal_status', 'INFO', '下注期号判断01', ['lottery_type'=>$lottery_type, 'plan_id'=>$plan_id, 'DataDealStatus'=>$DataDealStatus, 'task_qihao'=>$qihao]);
                if(empty($DataDealStatus) OR $DataDealStatus != 2){
                    Tool_Common::log('next_qihao_not_active', 'INFO', '计划未处理完成', ['lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'DataDealStatus'=>$DataDealStatus]);
                    throw new Exception('计划未处理完成'.$lottery_type.'_'.$qihao);
                }

                $s_time = microtime(true);
                Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'INFO', '用户计划下注脚本-4', ['task_id'=>$task_id]);
                $snId = $BetService->repeatErrorBet($task_id);
                $e_time = microtime(true);
                $logArr = ['uid' => $uid, 'qihao'=>$activeQiHao, 'account'=>$account, 'plan_id'=>$plan_id, 'task_id'=>$task_id, 'tz_system_id' => $tz_system_id, 'snId'=>$snId, 'consume_time'=>($e_time-$s_time).'s'];
                Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'INFO', '用户计划下注脚本-34', $logArr);

                if(!empty($snId)){
                    # 记录方案号
                    $where = ['plan_id'=>$plan_id, 'qihao'=>(string)$activeQiHao, 'lottery_type'=>$lottery_type];
                    $BettingRecords = BettingRecords::find()->where($where)->limit(1)->one();
                    $BettingRecords->snid = (string)$snId;
                    $BettingRecords->sn = trim($BettingRecords->sn.';'.$snId, ';'); // 这里幸运五是sn，暂时写snid
                    $r = $BettingRecords->save();
                    $errMsg = $r ? 'success' : Json::encode($BettingRecords->errors);
                    Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'INFO', '用户计划下注保存方案号', ['where'=>$where, 'snId'=>$snId, 'errMsg'=>$errMsg]);
                }

                Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'INFO', '用户计划下注成功-end', $logArr);
            }elseif(!empty($activeQiHao) && $qihao<$activeQiHao){ # 过期不下
                BetService::closeTask($task_id, $qihao, $activeQiHao, $account, $msg='未开盘或者已关盘[' . date('Y-m-d H:i:s') . ']'); # 关闭计划
                Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'ERR', '用户计划下注脚本-5', ['uid'=>$uid,'account'=>$account,'tz_system_id'=>$tz_system_id,  'qihao'=>$qihao, 'activeQiHao'=>$activeQiHao, 'msg' => $msg]);
            }
        }catch (\Exception $e){
            Tool_Common::log('/repeatErrorBet/'.__FUNCTION__.'_err', 'ERR', '下注错误', ['task_id'=>$task_id, $e->getMessage()]);
            return  $e->getMessage();
        }

        return '处理完成:'.($snId??'');
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

    public static function buildLotteryBetKey($qihao='', $plan_id='', $bet_sort_key=0, $task_id=''){
        return 'buildLotteryBetKey_'.$qihao.'_'.$plan_id.'_'.$bet_sort_key.'_'.$task_id;
    }

    public static function buildActiveQihaoKeyUid($uid='', $tz_system_id='', $lottery_type=DEFAULT_LOTTERY_TYPE ){
        $mkey = 'getActiveQihao_'.$uid.'_'.$tz_system_id.'_'.$lottery_type;

        return $mkey;
    }

    /**
     * @desc 获取正在下注的期号
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
        }

        if(empty($qihao)){
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
        try {
            $flag = 0;
            if(empty($lottery_type)){
                $lottery_type = DEFAULT_LOTTERY_TYPE;
            }
            $m = \Yii::$app->cache;

            $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken(trim($access_token));
            $mkey = BetService::buildActiveQihaoKey($TzSystemsUsers->tz_system_id, $lottery_type);
            Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '开启计划', ['access_token'=>$access_token, 'lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'mkey'=>$mkey]);
            if(!empty($TzSystemsUsers)){

                $flag = $m->set($mkey, $qihao, 60);
            }
        }catch (\Exception $e){

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
        try {
            if(empty($lottery_type)){
                $lottery_type = DEFAULT_LOTTERY_TYPE;
            }
            if(is_string($betRst)){
                $tmpBetRst = json_decode($betRst, true);
                $betRst = is_array($tmpBetRst) ? $tmpBetRst : ['task_status'=>BetErrorPlansTask::STATUS_FAIL, 'msg'=>$betRst];
            }
            if(!is_array($betRst)){
                $betRst = ['task_status'=>BetErrorPlansTask::STATUS_FAIL, 'msg'=>(string)$betRst];
            }

            $betMsg = (string)($betRst['msg'] ?? $betRst['err_msg'] ?? '');
            if(strpos($betMsg, '您当前使用的浏览器不支持cookie') !== false){
                throw_info('下注失败，等待下注');
            }

            $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
            if(empty($TzSystemsUsers)){
                throw_info('用户信息找不到');
            }

            #$BetErrorPlansTask = BetErrorPlansTask::findOne($where);
            $class = self::getBetModel($lottery_type);
            if($lottery_type == \common\helpers\LotteryType::LUCKY_5){
                $where = ['uid'=>$TzSystemsUsers->uid, 'plan_id'=>$plan_id, 'qihao'=>$qihao, 'lottery_type'=>$lottery_type];
                $model = $class::find()->where($where)->orderBy(['id'=>SORT_DESC])->orderBy('status asc')->addOrderBy(['id'=>SORT_DESC])->one();
                if(empty($model)){
                    throw_info('任务记录找不到');
                }
            }else{
                $where = ['user_id'=>$TzSystemsUsers->uid, 'order_id'=>$plan_id, 'qihao'=>$qihao, 'lottery_type'=>$lottery_type];
                $model = $class::findOne($where);
                if(empty($model)){
                    throw_info('任务记录找不到');
                }
            }

            $task_status = $betRst['task_status'] ?? BetErrorPlansTask::STATUS_FAIL;
            $snId = '';
            try {
                if(isset($betRst['Data']['SerialNo'])){
                    $snId = $betRst['Data']['SerialNo'];
                    BettingRecords::updateAll(['snid'=>$snId], ['qihao'=>$qihao, 'plan_id'=>$plan_id]);
                }
            }catch (\Exception $e){}
            //p(['class'=>$class, 'model'=>$model]);
            $m = \Yii::$app->cache;
            $mkey = __FUNCTION__.'_'.$plan_id."_".$qihao;
            $lock = 0;
            $retryKey = __FUNCTION__.'_retry_'.$model->id;
            if((int)$task_status !== BetErrorPlansTask::STATUS_SUCCESS && self::isRetryableBetResponse($betRst)){
                $retryAttempts = (int)\Yii::$app->redis->incr($retryKey);
                \Yii::$app->redis->expire($retryKey, 300);
                $betRst['bet_retry_attempts'] = $retryAttempts;
                $betRst['bet_retry_max_attempts'] = 3;
                if($retryAttempts < 3){
                    $task_status = BetErrorPlansTask::STATUS_WAIT;
                    $betRst['task_status'] = $task_status;
                    $betRst['retry_msg'] = '远程盘口请求异常，等待第'.($retryAttempts + 1).'次重试';
                }else{
                    $task_status = BetErrorPlansTask::STATUS_CAN_NOT_RE_PUSH;
                    $betRst['task_status'] = $task_status;
                    $betRst['retry_msg'] = '远程盘口请求异常，已重试3次';
                }
                Tool_Common::log('/client_xy/'.__FUNCTION__, 'WARN', '本地下注请求异常重试', ['username'=>$TzSystemsUsers->username, 'plan_id'=>$plan_id, 'qihao'=>$qihao, 'task_id'=>$model->id, 'retry_attempts'=>$retryAttempts, 'task_status'=>$task_status, 'betRst'=>$betRst]);
            }elseif((int)$task_status === BetErrorPlansTask::STATUS_SUCCESS){
                \Yii::$app->redis->del($retryKey);
            }
            if($task_status == 3 && strpos($betRst['err_msg'] ?? '', '短时间内重复提交') !== false){
                $num = \Yii::$app->redis->incr($mkey);
                Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '重复提交', ['username'=>$TzSystemsUsers->username, 'plan_id'=>$plan_id, 'qihao'=>$qihao, 'betRst'=>$betRst]);
                \Yii::$app->redis->expire($mkey, 120);
                if($num<2){
                    $task_status = 0;
                }
            }
            if($lottery_type == \common\helpers\LotteryType::LUCKY_5){
                if($model->status == 2){
                    throw_info('已经下注成功无需修改');
                }
                $model->status = $task_status;
                $model->post_desc = json_encode($betRst, 320);
                $flag = $model->save();
            }else{
                $task_status = $betRst['task_status']??BetsBackend::PUSH_STATUS_FAIL;
                $updateData = [
                    'push_status' => ($task_status==3) ? BetsBackend::PUSH_STATUS_CANNOT : $task_status,
                    'post_desc' => json_encode($betRst, 320),
                ];
                $flag = $class::updateAll($updateData, $where);

                $mKey = CacheKeyService::lotteryBetPlanIdKey($TzSystemsUsers->account, $qihao, $plan_id);
                $lock = commonRedis()->setnx($mKey, 1);
                if($lock){
                    commonRedis()->expire($mKey, 300);
                    # 澳洲五客户端下注结果通知
                    $pushData = ['orderId'=>$plan_id, 'business_id' => $model->order_id, 'betRst'=>$betRst];
                    push_queue_open(AoZhou5BetJobs::class, $pushData);
                }
            }
            $m->set($mkey, 1, 40);
            Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '更新计划状态', ['flag'=>$flag, 'lock'=>$lock, 'where'=>$where, 'betRst'=>$betRst, 'snId'=>$snId, 'lottery_type'=>$lottery_type]);
        }catch (\Exception $e){
            return ['status'=>300, 'data'=>[], 'msg'=>$e->getMessage()];
        }

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
        $lotteryTypeData = LotteryTypeService::getLotteryTypeData();
        $openingTime = $lotteryTypeData[$lottery_type]['opening_time'];
        $closingTime = $lotteryTypeData[$lottery_type]['closing_time'];

        $time = date('H:i:s');
        if($lottery_type == 5){
            if(
                (\Yii::$app->params['ssc_kj_time_start'] <= $time && $time <= \Yii::$app->params['ssc_kj_time_end']) OR
                ('23:50:00' <= $time && $time <= '23:59:59') OR ('00:00:00' <= $time && $time <= '00:12:00')
            ){
                //$rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
                $status = false;
            }
        }elseif($lottery_type == 6){ # 新疆
            if(\Yii::$app->params['LOTTERY_TYPE_6_STOP_START_TIME'] < $time && $time < \Yii::$app->params['LOTTERY_TYPE_6_STOP_END_TIME']){
                $status = false;
            }
        }elseif($lottery_type == 8){ # 幸运五星
            if($closingTime < $time && $time < $openingTime){
                $status = false;
            }
        }elseif($lottery_type == 9){
            # 台湾宾果
            if('00:00:00'<$time && $time<'07:00:00'){
                $status = false;
            }
        }elseif($lottery_type == 16 && '20:00:00'<$time && $time<'21:00:00'){
            $status = false;
        }elseif(in_array($lottery_type, [10, 11, 12, 13])){
            # 冰岛90s 3m 5m 10m
            if('03:00:00'<$time && $time<'08:00:00'){
                $status = false;
            }
        }elseif($lottery_type == 18){
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
     * @$playway 为3的时候，四字定： $kArr = [0=>'所有', 1=>'一双三单、一单三双', 2=>'两双两单', 3=>'四双四单', 4=>'一单三双', 5=>'一双三单', 6=>'一单三双|四双', 7=>'一双三单|四单', 8=>'四双', 9=>'四单', 10=>'单数量', 11=>'双数量', 12=>'一单三双|四单', 13=>'一双三单|四双', 14=>'一单三双|四单|四双', 15=>'一双三单|四单|四双'];
     * @param int $tz_type 投注类型(三字定):1大小单双三字定2大小三字定3单双三字定
     * @param $buy_type 1正买0反买，默认正买
     * @param string $codes_hz
     * @param string $plan_id
     * @return string
     */
    public static function getPlansAllCodesType1($tz_type = 1, $buy_type = 1, $codes_hz = '', $plan_id = ''){
        $playway = BetService::getPlaywayByTzType($tz_type);
        //p([$tz_type, $buy_type, $sel_same, $codes_hz, $playway]);
        $m = \Yii::$app->cache;
        $codes_hz_data = json_decode($codes_hz, true);
        $codes_hz_data = is_array($codes_hz_data) ? $codes_hz_data : [];
        $codesArr = [];
        # 排除类型
        BetService::getDynamicsHzArr($codes_hz_data, $plan_id);
        //p($codes_hz_data);
        $codes_hz = json_encode($codes_hz_data);

        if(!empty($plan_id)){
            $plan = UserSysPlans::findOne($plan_id);
        }
        switch ($playway){
            case 4: # 一字定
                // {"p1":"123","p2":"345","p3":"569","p4":"6589","p5":"1234"}
                $codesArr = NumService::getOneFixedCode(json_decode($codes_hz, true));
                $n = rand(0, 9);
                $codesArr[0] = str_replace($n, '', $codesArr[0]);
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
                        foreach ($SysPlansCodes as $key=>$sPlan){
                            $codesArr[] = $sPlan['code'];
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
                    $baseCodes = !empty($plan['base_codes'])?explode(',', $plan->base_codes??''):[];
                    $codesArr = NumService::getCodesKuaiXuan(json_decode($codes_hz, true), $code_type, $baseCodes);
                }elseif($tz_type == 26){ # 去除近xxx期号码
                    $codesArr = NumService::getNotLatelyCodes(json_decode($codes_hz, true));
                }elseif($tz_type == 28){ # 系统快捷
                    $baseCodes = explode(',', $plan->base_codes??'');
                    $codesArr = NumService::getCodesKuaiXuan(json_decode($codes_hz, true), $code_type, $baseCodes);
                }

                break;
            case 6: # 二、三、四字现
                $codesArr = explode(',', json_decode($codes_hz, 320)['codes']);
                break;
        }

        # 动态过滤1
        $filterDynamicTypes = $codes_hz_data['filter_dynamic_types'] ?? [];
        if(isset($codes_hz_data['is_filter_dynamic']) && $codes_hz_data['is_filter_dynamic']==1 && is_array($filterDynamicTypes) && count($filterDynamicTypes)>0){
            $filter_dynamic_codes = NumService::getBeforeKjCodesDynamic($plan);
            if(!empty($filter_dynamic_codes)){
                $codesArr = array_intersect($codesArr, $filter_dynamic_codes); # 返回$codesArr和$filter_dynamic_codes交集
            }
        }
        # 动态过滤2
        $filterDynamicTypes2 = $codes_hz_data['filter_dynamic_types2'] ?? [];
        if(is_array($filterDynamicTypes2) && !empty($filterDynamicTypes2)){
            $filter_dynamic_codes2 = DynamicFilterService::getFilterDynamic2($plan);
            if(!empty($filter_dynamic_codes2)){
                $codesArr = array_intersect($codesArr, $filter_dynamic_codes2); # 返回$codesArr和$filter_dynamic_codes交集
            }
        }

        $codesArr = is_array($codesArr) ? $codesArr : [];
        $before_count=count($codesArr);
        # 反买号码获取
        if($buy_type==0 && !in_array($tz_type, [22])){ # 22 四定单双
            $codesArr = self::getInverseCodes($codesArr, $code_type);
        }

        //p(['buy_type'=>$buy_type, 'before_count'=>$before_count, 'after_count'=>count($codesArr), 'codesArr'=>$codesArr]);
        //p($codes_hz_data);

        $codes_hz_data = json_decode($codes_hz, true);
        if(isset($codes_hz_data['filters']['filter_type']) && in_array($codes_hz_data['filters']['filter_type'], [1])){
            # 过滤号码，filter_type:1过滤前x期号码
            $filter_codes = NumService::getCodesByCodesHz($codes_hz_data['filters'], $plan);
            Tool_Common::log('/codes/'.__FUNCTION__, 'INFO', '过滤号码', ['plan_id'=>$plan_id, 'tz_type'=>$tz_type, 'filters'=>$codes_hz_data['filters'], 'codesArr'=>$codesArr, 'get_counts'=>count($codesArr), 'filter_codes'=>$filter_codes, 'filter_counts'=>count($filter_codes)]);
            //p(['codesArrCount'=>count($codesArr), 'codesArr'=>$codesArr, 'filter_codes_count'=>count($filter_codes),  'filter_codes'=>$filter_codes]);
            $codesArr = array_diff($codesArr, $filter_codes); # 返回$codes在$filter_codes中没有的号码
        }

        $codes = implode('@', $codesArr);

        return $codes;
    }

    /**
     * @desc 和值号码读取
     * @param $tz_type
     * @param $codes_hz
     * @param int $buy_type 1正买0反买 默认正买
     * @param string $plan_id
     * @return string
     */
    public static function getHzCodes($tz_type, $codes_hz, $buy_type=1, $plan_id = ''){
        return self::getPlansAllCodesType1($tz_type, $buy_type, $codes_hz, $plan_id);
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
    public static function buildBetKey($account = 'gaozi2017', $tz_system_id=9, $lottery_type = DEFAULT_LOTTERY_TYPE, $qihao='', $plan_id = 0){
        $mkey = \Yii::$app->params['TZ_SWITCH_KEY'].'_'.$account.'_'.$tz_system_id.'_'.$lottery_type.'_'.$qihao.'_'.$plan_id;

        return $mkey;
    }

    /**
     * @desc 计划任务列表 - 立即下注
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
        //$codes_hz['current_miss'] = 0; # 当前遗漏
        //$codes_hz['singles_key'] = 0; # 倍数key
        //$codes_hz['is_init'] = 1; # 是否最初
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
        PlanStaticProfits::updateAll(['cut_profits'=>0.00], ['plan_id'=>$id, 'uid'=>$uid]); # 利润表归零

        return $rst;
    }

    /**
     * @desc 盈利归零
     * @param $uid
     */
   public static function reCalculateAllBettingRecords($uid){
       try {
           $m = \Yii::$app->cache;
           $mkey = 'reCalculateAllBettingRecords_'.$uid;
           if($r = $m->get($mkey))
               throw_info('已经归零过了，请稍后');

           $rstFlag = BettingRecords::updateAll(['is_profits_record'=>0, 'is_area_profits'=>0], ['uid'=>$uid, 'status'=>1]);
           $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid]);
           $TzSystemsUsers->desc = '';
           $TzSystemsUsers->current_profits = 0.00;
           $r = $TzSystemsUsers->save();
           if(!$r){
               throw_info(Json::encode($TzSystemsUsers->getErrors(), 320));
           }
           TzSystemUsersService::getTzSystemsUsersByAccessToken($TzSystemsUsers->access_token, $is_auto=2);

           $m->set($mkey, 1, 10);
           $rst['flag'] = $rstFlag;
           Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '归零正常', ['user_id'=>$uid, 'username'=>$TzSystemsUsers->username, 'rst'=>$rst, 'r'=>$r]);
       }catch (\Exception $e){
           Tool_Common::log('/user/'.__FUNCTION__, 'ERR', '重算盈利异常', ['user_id'=>$uid, 'username'=>$TzSystemsUsers->username, 'err_msg'=>$e->getMessage()]);
           return $e->getMessage();
       }
       #$rst['lottery_type'] = DEFAULT_LOTTERY_TYPE;

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
     * @desc 获取方案号
     * @param $planId
     * @param $plan_type
     * @param $is_test
     * @param $isAuto
     * @return string[]
     */
    public static function getBetSnId(object $UserSysPlans, $plan_type, &$is_test, $isAuto){
        $sn = BetService::$test_true_sn;
        $snid = BetService::$test_true_snid;

        #$UserSysPlans = UserSysPlans::findOne($planId);
        $planId = $UserSysPlans->id;
        $hzArr = json_decode($UserSysPlans->hz_Arr, true);
        if(in_array($plan_type, array_merge([
            SscDataService::PLAN_TYPE_SINGLES_BET_WIN,
            SscDataService::PLAN_TYPE_YL_BET,
            SscDataService::PLAN_TYPE_YL_BET_SINGLES,
            SscDataService::PLAN_TYPE_BT_SINGLES_BET,
            SscDataService::PLAN_TYPE_AREA_SINGLES_BET,
            SscDataService::PLAN_TYPE_SINGLES_BET_2,
            SscDataService::PLAN_TYPE_YL_BET_SINGLES_2,
            SscDataService::PLAN_TYPE_YL_ZZ_SINGLES_BET,
            SscDataService::PLAN_TYPE_YL_BET_SINGLES_NUM,
            SscDataService::PLAN_TYPE_YL_START_BET_SINGLES,
            SscDataService::PLAN_TYPE_ZZ_BET_SINGLES_2,
            SscDataService::PLAN_TYPE_LOSS_MONEY_BET_SINGLES,
        ], UserSysPlans::$A_x_arise_B_y_arise_bet_B_types))){ # 6中则投 8、9遗漏多少期投
            //j$flag = SscDataService::isZjBefore($planId); # 上期是否中奖，第一次下注认为是上期不中
            $flag = BetService::getIsBetTrue($UserSysPlans);
            if(in_array($flag, [0, -1]) && $isAuto == 1){
                $is_test = 1;
                $sn = 'istest';
                $snid = 'istest_id';
            }
            Tool_Common::log('/plan/'.__FUNCTION__.'_is_bet_true', 'INFO', '是否真实下注计划', ['plan_id'=>$planId, 'flag'=>$flag, 'fh'=>(boolean)(in_array($flag, [0, -1]) && $isAuto == 1), 'sn'=>$sn, 'snid'=>$snid]);
        }elseif(in_array($hzArr['filters']['filter_type'] ?? 0, BetService::getCodesNewFilterTypes())){
            $BettingRecords = BettingRecords::find()->where(['lottery_type'=>$UserSysPlans->lottery_type, 'plan_id'=>$planId])->orderBy(['id'=>SORT_DESC])->limit(1)->one();
            if(empty($BettingRecords)){
                return [$sn, $snid];
            }
            $codesArr = explode(',', $BettingRecords->kj_codes);
            array_pop($codesArr);
            $codesArr = array_unique($codesArr);

            if($UserSysPlans->is_batch_simulate == 1){
                $is_test = 1;
                if(count($codesArr) < 4){
                    $sn = 'istest';
                    $snid = 'istest_id';
                }
            }
        }

        return [$sn, $snid];
    }

    /**
     * @desc 根据计划id投注 - 立即投注(手动)
     * @param $planId
     * @param $isAuto - 是否自动,默认自动
     * @return array
     */
    public static function tzByPlanId($planId, $isAuto = 1){
       if(!$plan = UserSysPlans::findOne($planId)){
            return ['status'=>300, 'msg'=>'找不到对应记录'];
       }
       $m = \Yii::$app->cache;
       $tz_sites = explode(',', trim($plan->tz_sites));
       $qihao = HN0898Service::getQihao($plan->lottery_type);
       $mkey = CacheKeyService::lotteryBetPlanIdKey($plan->account, $qihao, $plan->id);
       $rst = [];
       foreach ($tz_sites as $tz_system_id){
           $status = UserService::accountIsExpire($plan->uid, $tz_system_id); # 账号是否过期
           if(!$status && $plan->account != 'gaozi2018'){
               Tool_Common::log('accountIsExpire', 'ERR', '账号过期提示', ['uid'=>$plan->uid, 'account'=>$plan->account, 'tz_system_id'=>$tz_system_id]);
               return ['status'=>300, 'msg'=>'账号过期提示'];
           }
           # 4、投注号码 codes
           $codes = self::getCodes($plan->tz_type, $plan->buy_type, $plan->hz_Arr, $planId);
           //p([$plan->tz_type, $plan->buy_type, $plan->sel_same, $plan->hz_Arr, count(explode('@', $codes))]);

           $isAuto == 0 && BetService::beforeBetNow($plan->account, $tz_system_id, $plan->lottery_type, $qihao, $plan->id, $plan->uid); # 手动下注时，先删除缓存

           if(commonRedis()->get($mkey)) continue; # ['status'=>300, 'msg'=>'已经投注过了~'];
           $time = BetService::getBetCacheTime($plan->lottery_type, $qihao); # 投注之后缓存时间
           commonRedis()->setex($mkey, $time, 1);

           $is_test = $plan->is_test;
           list($sn, $snid) = BetService::getBetSnId($plan, $plan->plan_type, $is_test, $isAuto);

           if($is_test == 1 OR $plan->uid == 1){ # 模拟下注
               $tmpRst = self::_logRecordsByPlandId($plan, $qihao, $codes, $plan->lottery_type, $is_test = 1, $sn, $snid, $plan->hz_Arr, $r=2); # 直接记录表
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
               $tmpRst = $BetService->postBatchBet($qihao, $plan, $codes, $is_task=0); #  立即投注
               $logArr = ['tz_sites'=>$tz_sites,'codes'=>$codes, 'postRst'=>$rst];
               Tool_Common::log('/plan/'.__FUNCTION__,'INFO','0898投注记录', $logArr);
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
    public static function getIsBetTrue(object $plan){

        #$plan = UserSysPlans::findOne($plan_id);
        $flag = SscDataService::isZjBefore($plan->id); # 上期是否中奖，第一次下注认为是上期不中 中则投
        $codes_hz = json_decode($plan->hz_Arr, true);
        if(in_array($plan->plan_type, [14])){
            return $codes_hz['areaBetStatus'];
        }else{
            if(in_array($plan->plan_type, [8, 9, 16])){ # 遗漏多少期启投
                $flag = 0;
                if($codes_hz['current_miss']>=$codes_hz['bet_while_miss']){
                    $flag = 1;
                }
            }else if(in_array($plan->plan_type, [
                SscDataService::PLAN_TYPE_SINGLES_BET_WIN,
                SscDataService::PLAN_TYPE_SINGLES_BET_2,
                SscDataService::PLAN_TYPE_BT_SINGLES_BET,
                SscDataService::PLAN_TYPE_YL_ZZ_SINGLES_BET,
                SscDataService::PLAN_TYPE_YL_BET_SINGLES_NUM,
                SscDataService::PLAN_TYPE_YL_START_BET_SINGLES,
                SscDataService::PLAN_TYPE_ZZ_BET_SINGLES_2,
                SscDataService::PLAN_TYPE_LOSS_MONEY_BET_SINGLES,
            ])) { # 中则投、中则投+翻倍梯度倍投、遗漏中则倍投
                $flag = ($codes_hz['betStatus'] == SscDataService::PLAN_BET_STATUS_BETTING) ? 1 : 0;
            }else if($plan->plan_type == 13) {
                $flag = ((int)($codes_hz['A_x_B_y_status'] ?? 0) === \backend\service\PlanType13Service::STATUS_BET) ? 1 : 0;
            }else if($plan->plan_type == 12) { # A出x次B出y次投B
                $flag = 0;
                if($codes_hz["current_arise_A_times"]>=$codes_hz['arise_A_times'] && $codes_hz["current_arise_B_times"]==$codes_hz['arise_B_times']){
                    $flag = 1;
                }
            }
            if($codes_hz['filters']['filter_type'] == 2){
                $BettingRecords = BettingRecords::find()->select(['id', 'kj_codes', 'qihao'])->where(['plan_id'=>$plan->id])->limit(1)->orderBy(['id'=>SORT_DESC])->one();
                $kj_codes = $BettingRecords->kj_codes;
                $codesArr = explode(',', $kj_codes);
                $end_num = array_pop($codesArr); # 以太坊去掉最后一个0
                $countCodes = count($codesArr);
                if(in_array($plan->lottery_type, [23, 24])){
                    $flag = ($countCodes == 4) ? 1 : 0;
                }else{
                    if($countCodes<4){
                        $codesArr[] = $end_num;
                    }
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

        $mkey = CacheKeyService::lotteryBetPlanIdKey($account, $qihao, $plan_id);
        commonRedis()->del($mkey);
        $m->delete($mkey);
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
        if(!self::hasValidBetCodes($data['codes'] ?? '')){
            $msg = '生成投注号码为空，已阻止写入投注记录';
            Tool_Common::log('logRecords', 'ERR', '投注号码为空', [
                'plan_id'=>$data['plan_id'] ?? '',
                'uid'=>$data['uid'] ?? '',
                'qihao'=>$data['qihao'] ?? '',
                'tz_type'=>$data['tz_type'] ?? '',
                'playway'=>$data['playway'] ?? '',
                'lottery_type'=>$data['lottery_type'] ?? '',
                'post_desc'=>$data['post_desc'] ?? '',
            ]);
            return ['status'=>300, 'data'=>[], 'msg'=>$msg];
        }
        try{
            $transaction = Yii::$app->db->beginTransaction();
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
                'qihao' => (string)trim($data['qihao']),  // 投注期号
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
            if(BettingRecords::find()->where($where)->limit(1)->exists()){
                throw_info('记录已经存在plan_id:'.$data['plan_id'].'_uid:'.$data['uid']);
            }

            $bettingRecords = new BettingRecords();
            $bettingRecords->setAttributes($insertData);
            $rst = $bettingRecords->save();

            if(!$rst){
                throw_info(Json::encode($bettingRecords->getErrors(), 320));
            }
            $transaction->commit();
        }catch(\Exception $e){
            $transaction->rollBack();
            $err_msg = $e->getMessage();
            Tool_Common::log('logRecords', 'INFO', '记录投注表', ['plan_id'=>$data['plan_id'], 'msg'=>$err_msg]);
            return ['status'=>300, 'data'=>[], 'msg'=>$err_msg];
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
        if((int)$BettingRecords->is_simulate === 1 || empty($BettingRecords->snid) || $BettingRecords->snid === self::$test_true_snid){
            return ['status'=>300, 'msg'=>'模拟记录或测试单号无需盘口撤单', 'lottery_type'=>$BettingRecords->lottery_type];
        }
        $m = \Yii::$app->cache;
        $mkey = 'ORDER_CANCEL_'.$bet_id;
        if($isCanceled = $m->get($mkey)) return ['status'=>300, 'msg'=>'正在退单请稍等~'];
        $m->set($mkey, 1, 15);

        $tz_system_id = $BettingRecords->tz_system_id;
        if(!$tz_system_id){
            $plan = UserSysPlans::findOne($BettingRecords->plan_id);
            if($plan && trim((string)$plan->tz_sites) !== ''){
                $tz_system_id = (int)trim((string)$plan->tz_sites);
                $BettingRecords->tz_system_id = $tz_system_id;
                $BettingRecords->save(false, ['tz_system_id']);
            }
        }
        $lottery_type = $BettingRecords->lottery_type;
        $rst = ['status'=>300, 'msg'=>'暂不支持该盘口撤单', 'lottery_type'=>$lottery_type];
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
            $rst = NineNineNewService::cancelOrder($bet_id, $BettingRecords->tz_system_id);
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

        if(!is_array($rst)){
            $rst = ['status'=>300, 'msg'=>is_scalar($rst) ? (string)$rst : '撤单失败'];
        }
        $rst['lottery_type'] = $lottery_type;
        if(($rst['status'] ?? 0) == 200){
            $m->set($mkey, 1, 5);
        }else{
            $m->delete($mkey);
        }


       return $rst;
    }

    /**
     * @desc 是否登录，判断标准，能正常获取用户信息、余额
     * @param int $uid
     * @param $tz_system_id
     * @return bool
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
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        $TzSystemsUsers = TzSystemsUsers::findAll(['status'=>1, 'uid'=>$uid]);
        foreach ($TzSystemsUsers as $TzSystemsUser){
            if(!$TzSystemsUser->is_local_bet){
                $rst = self::synBalance($uid, $TzSystemsUser->tz_system_id);
            }
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
                $lotteryTypeData = LotteryTypeService::getLotteryTypeData();
                $openingTime = $lotteryTypeData[$lottery_type]['opening_time'];
                $closingTime = $lotteryTypeData[$lottery_type]['closing_time'];
                $cacheTime = 6 * 60;
                if($closingTime<$now_HI && $now_HI<$openingTime){
                    $cacheTime = 3 * 3600;
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
                $cacheTime = 3 * 60;
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

        return $cacheTime ?: 1200;
    }

    /**
     * @desc 根据投注类型返回名称
     * @param $tz_type
     */
    public static function getTypeNameByTzType($tz_type){

        $mkey = CacheKeyService::lotteryTzType($tz_type);
        if(!$typeName = commonRedis()->get($mkey)){
            $typeName = TzTypes::findOne(['type'=>$tz_type])->type_name;

            commonRedis()->setex($mkey,60*60, $typeName);
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
    public static function _logRecordsByPlandId($UserSysPlans, $qihao, $codes, $lottery_type = DEFAULT_LOTTERY_TYPE, $is_test = 0, $sn='888888', $snid='888888id', $post_desc='', $r=0){
        //p([$plan_id, $qihao, $codes, $lottery_type = DEFAULT_LOTTERY_TYPE, $is_test, $sn, $snid],0);
        //$UserSysPlans = UserSysPlans::findOne($plan_id);
        $plan_id = $UserSysPlans->id;
        if(!self::hasValidBetCodes($codes)){
            $msg = '生成投注号码为空，已阻止写入投注记录plan_id:'.$plan_id.'_uid:'.$UserSysPlans->uid;
            Tool_Common::log('/bet/empty_codes', 'ERR', '投注号码为空', [
                'plan_id'=>$plan_id,
                'uid'=>$UserSysPlans->uid,
                'account'=>$UserSysPlans->account,
                'qihao'=>$qihao,
                'tz_type'=>$UserSysPlans->tz_type,
                'playway'=>$UserSysPlans->playway,
                'lottery_type'=>$lottery_type,
                'post_desc'=>$post_desc,
                'r'=>$r,
            ]);
            return ['status'=>300, 'data'=>[], 'msg'=>$msg];
        }

        $where = ['AND', ['=', 'qihao', $qihao], ['=', 'plan_id', $plan_id], ['=', 'uid', $UserSysPlans->uid]];
        $flag = BettingRecords::find()->select(['id'])->where($where)->limit(1)->one();
        if($flag){
            return ['status'=>300, 'data'=>[], 'msg'=>'记录已经存在plan_id:'.$plan_id.'_uid:'.$UserSysPlans->uid];
        }

        if($UserSysPlans->tz_type == 18) {
            $count = strlen(str_replace(',', '', $codes));
        }elseif($UserSysPlans->tz_type == 22){
            $codesArr = Lucky5Service::getBetCodes($codes, $UserSysPlans->single, $UserSysPlans->playway);
            $count = count($codesArr);
        }else{
            $count = count(explode('@', $codes));
        }
        $post_desc = ($post_desc && is_json($post_desc))?Json::decode($post_desc):[];
        $pDesc = NumCodeService::getRandBetDesc($UserSysPlans->id, $qihao);
        if(!empty($pDesc)){
            $post_desc['下注描述'] = $pDesc;
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
            'qihao' => trim($qihao),  // 投注期号
            'tz_system_id' => '',  // 投注系统tz_systems .id
            'sn'=>$sn ? $sn : BetService::$test_true_sn,
            'snid'=>$snid ? $snid : BetService::$test_true_snid,
            'order_type'=>$UserSysPlans->playway, # 单双三字定
            'is_simulate' => $is_test ? 1 : 0,  // 是否模拟投注
            'post_desc' => Json::encode($post_desc),  // 下注描述
            'is_batch_simulate' => ($is_test==2) ? 1 : 0,  // 是否批量模拟
            'single' => floatval($UserSysPlans->single),  // 投注倍数
            'betting_money'=> round($totalmoney,2),  // 投注金额
        ];
        //p($insertData,0);
        $insertRst = BetService::_logRecords($insertData);
        unset($insertData['codes']);
        Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '记录', ['insertRst'=>$insertRst, /*'insertData'=>$insertData,*/ 'r'=>$r]);

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

        if(
            ($code_type==3 && count($codesArr)<1000) OR
            ($code_type==2 && count($codesArr)<100)
        ){
            $filter_poses = NumService::getFilterPosByCode(current($codesArr)); # 根据导入的号码判断要过滤的位置
            if(!empty($filter_poses)){
                foreach ($filter_poses as $pos){
                    $query->andWhere(['<>', 'code_'.$pos, 'X']);
                }
            }
        }
        //p($query->createCommand()->getRawSql());
        $Num4Type = $query->asArray()->all();
        $data = ArrayHelper::getColumn($Num4Type, 'code');

        return $data;
    }

    /**
     * @desc 获取相反号码n
     * @param $codesArr 无逗号
     * @param $code_type
     * @return array
     */
    public static function getInverseCodesN($codesArr, $code_type){
        if(!is_array($codesArr)) return [];
        $where = ['AND', ['=', 'code_type', $code_type], ['NOT IN', 'code_n', $codesArr]];
        $query = Num4Type::find()->where($where);
        if($code_type == 3){

        }elseif($code_type == 2){

        }
        #$sql = $query->createCommand()->getRawSql(); p($sql);
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
     * @return string|array
     */
    public static function insertPlansTask($lottery_type = DEFAULT_LOTTERY_TYPE, $qiHao='', $isJob=0, $accountOrId=''){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        $HI = date('H:i');
        if($lottery_type == 8 && '04:59'<$HI && $HI<'08:00'){
            Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '批量插入任务100', ['lottery_type'=>$lottery_type, 'err_msg'=>'幸运五非开盘时间']);
            return '幸运五非开盘时间';
        }
        if(empty($qiHao)){
            list($currentKjQiHao, $qiHao) = QihaoService::getKjQiHao($lottery_type); # 期号数据
        }
        $DataDealStatus = BetService::getDataDealStatus($lottery_type, $qiHao, 'opProfitsPlans_status');
        if(empty($DataDealStatus) OR $DataDealStatus != 2){
            Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '投注计划', ['lottery_type'=>$lottery_type, 'msg'=>$qiHao.'计划未处理完成']);
            return '计划未处理完成';
        }

        $where = ['AND', ['=', 'lottery_type', $lottery_type]];
        if(!empty($accountOrId)){
            $where[] = [ 'OR', ['=', 'account', $accountOrId], ['=', 'id', $accountOrId]];
        }else{
            # is_batch_simulate:0正常1批量模拟历史记录
            $where  = array_merge($where, [['=', 'status', 1], ['=', 'is_batch_simulate', 0]]);
        }
        //$where[] = ['=', 'uid', 17]; # 测试

        $plansQuery = UserSysPlans::find()->select(['id', 'uid'])->where($where); // ->all();
        // 记录总数（可选）
        $totalCount = $plansQuery->count();
        Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '批量插入任务000', ['lottery_type'=>$lottery_type, 'counts'=>$totalCount]);
        if($totalCount==0){
            Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '投注计划', ['lottery_type'=>$lottery_type, 'msg'=>'没有开启的计划']);
            return '没有开启的计划';
        }

        $TzSystemsUsersData = TzSystemsUsers::find()->where(['status'=>1])->indexBy('uid')->all();
        $isCanBetUids = [];
        foreach ($TzSystemsUsersData as $uid=>$TzSystemsUsers){
            list($code, $current_profits) = UserService::updateUserProfits($TzSystemsUsers);
            try {
                AgentClientsService::checkProfits($TzSystemsUsers);
                $isCanBetUids[$uid] = true;
            }catch (\Exception $e){
                Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '插入真实计划任务-止盈止损', [
                    'user_id' => $uid,
                    'err_msg' => $e->getMessage(),
                ]);
                $isCanBetUids[$uid] = false;
                //throw_info($e->getMessage());
            }
        }
        foreach ($plansQuery->each(20) as $plan){
            $planId = $plan->id;
            $preInsertLockKey = null;
            $lockAcquired = false;
            try {
                $isCanBet = $isCanBetUids[$plan->uid]??1;
                if(!$isCanBet){
                    throw_info('当前情况不能下注');
                }
                $preInsertLockKey = CacheKeyService::preInsertPlanTaskKey($planId, $qiHao);
                // 增加锁的过期时间到60秒，确保有足够时间处理，即使异常也会自动过期
                if(!(Redis::lock($preInsertLockKey, 60))){
                    throw_info('业务处理中，请稍后...planId:'.$planId, 40001);
                }
                $lockAcquired = true;

                $where = ['AND', ['=', 'qihao', $qiHao], ['=', 'plan_id', $planId]];
                if(BettingRecords::find()->where($where)->exists()){
                    throw_info('yx表已记录2...', 40001);
                }
                push_queue_fast(UserPlanBetJob::class, ['plan_id'=>$planId, 'isJob'=>$isJob, 'qiHao'=>$qiHao, 'business_id'=>$qiHao, 'msg'=>'写入计划']);
                $rst['data'] = ['activeQiHao'=>$qiHao, 'plan_id'=>$plan->id, 'msg'=>'正常'];
            }catch (\Exception $e){
                var_dump($e->getMessage());
                if($e->getCode()<40000){
                    $logArr = [
                        'uid'=>$plan->uid,
                        'plan_id'=>$planId,
                        'isJob'=>$isJob,
                        'lottery_type'=>$lottery_type,
                        'err_msg'=>$e->getMessage(),
                        'errCode'=>$e->getCode(),
                        'file'=>$e->getFile(),
                        'line'=>$e->getLine()
                    ];
                    Tool_Common::log('/bet/'.__FUNCTION__, 'ERR', '插入计划-异常', $logArr);
                }
                $rst['data']['plan_id'] = ['plan_id'=>$planId, 'msg'=>$e->getMessage()];
            }finally{
                // 确保锁在异常时也能释放，避免锁一直占用导致后续无法处理
                if($lockAcquired && $preInsertLockKey){
                    try {
                        Redis::clearLock($preInsertLockKey);
                        Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '释放锁', ['plan_id'=>$planId, 'lock_key'=>$preInsertLockKey]);
                    }catch (\Exception $e){
                        Tool_Common::log('/bet/'.__FUNCTION__, 'ERR', '释放锁失败', ['plan_id'=>$planId, 'lock_key'=>$preInsertLockKey, 'err_msg'=>$e->getMessage()]);
                    }
                }
            }
        }
        $err_post_desc = Json::encode(['Status'=>0, 'msg'=>'过期未下单', 'time'=>date('Y-m-d H:i:s')]);
        BetErrorPlansTask::updateAll(['status'=>3, 'post_desc'=>$err_post_desc], 'created_at<'.(time()-300).' AND status=0');

        return $rst;
    }

    /**
     * 任务计划写入
     * @param $planId
     * @param $qiHao
     * @param $isAuto
     */
    public static function insertRecord($planId, $qiHao, $isAuto=1)
    {
        try {
            $t1 = microtime(true);

            $plan = UserSysPlans::findOne($planId);
            if($isAuto == 1 && $plan->status != 1){
                throw_info('计划未开启...');
            }
            if(!DataDealStatus::find()->where(['next_qihao'=>$qiHao, 'status'=>2])->exists()){
                throw_info('数据未处理完成');
            }
            $where = ['AND', ['=', 'qihao', $qiHao], ['=', 'plan_id', $planId], ['=', 'uid', $plan->uid]];
            if($isAuto == 1 && BettingRecords::find()->where($where)->exists()){
                throw_info('yx表已记录3...');
            }
            $where = ['uid'=>$plan->uid, 'lottery_type'=>$plan->lottery_type, 'qihao'=>$qiHao, 'plan_id'=>$planId];
            if(BetErrorPlansTask::find()->where($where)->exists()){
                throw_info('任务表已记录2...');
            }

            $lottery_type = $plan->lottery_type;
            $tz_system_id = trim((string)$plan->tz_sites);
            $insert_mkey = CacheKeyService::insertPlanTaskKey($lottery_type, $qiHao, $plan->id);

            $TzSystemsUsersData = TzSystemsUsers::find()->where(['status'=>1])->indexBy('uid')->all();
            $TzSystemsUsers = $TzSystemsUsersData[$plan->uid]??[];
            # 4、投注号码 codes
            $codes = BetService::getCodesByPlan($plan);
            $t2 = microtime(true);
            $time_consume1 = ($t2-$t1).'s';
            //p(['codes'=>count(explode('@', $codes)), 'time_consume1'=>$time_consume1, 'hz_Arr'=>Json::decode($plan->hz_Arr)]);

            $is_test = $plan->is_test;
            list($sn, $snid) = BetService::getBetSnId($plan, $plan->plan_type, $is_test, $isAuto);

            if($is_test == 1 OR $plan->uid == 1){ # 模拟下注
                $testInsertRst = self::_logRecordsByPlandId($plan, $qiHao, $codes, $plan->lottery_type, $is_test, $sn, $snid, $plan->hz_Arr, $r=3); # 直接记录表
                if($testInsertRst['status'] == 200){
                    commonRedis()->setex($insert_mkey, 300, 1);
                }
            }else{
                $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$plan->uid, 'tz_system_id'=>$tz_system_id, 'status'=>1]);
                if(!$TzSystemsUsers){
                    throw_info('找不到盘口账号配置:uid='.$plan->uid.',tz_system_id='.$tz_system_id);
                }

                $logArr = ['plan_id'=>$plan->id, 'is_local_bet'=>$TzSystemsUsers->is_local_bet, 'account'=>$TzSystemsUsers->account, 'lottery_type'=>$lottery_type, 'uid'=>$plan->uid, 'tz_system_id'=>$tz_system_id];
                Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '插入真实计划任务-1', $logArr);

                $BetService = self::getBetObj($plan->uid, $tz_system_id, $lottery_type);
                $insertRst = $BetService->postBatchBet($qiHao, $plan, $codes); # 计划任务写入
                if($TzSystemsUsers->is_local_bet === 0){
                    $params = ['task_id'=>$insertRst['task_id'], 'user_id'=>$plan->uid, 'business_id'=>$planId, 'qihao'=>$qiHao];
                    if($lottery_type == \common\helpers\LotteryType::ETH_10M){
                        $params['queue_delay_time'] = 30;
                    }
                    push_queue_fast(UserTaskBetJob::class, $params);
                }

                $t3 = microtime(true);
                $time_consume2 = ($t3-$t2).'s';
                $logArr = ['uid'=>$plan->uid, 'account'=>$plan->account, 'plan_id'=>$plan->id, 'activeQiHao'=>$qiHao, 'insertRst'=>$insertRst, 'time_consume1'=>$time_consume1, 'time_consume2'=>$time_consume2];
                Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '插入计划任务结束', $logArr);
                commonRedis()->setex($insert_mkey, 120, 1);
            }
        }catch (\Exception $e){
            $t2 = microtime(true);
            $time_consume = ($t2-$t1).'s';
            $logArr = ['planId'=>$planId, 'activeQiHao'=>$qiHao, 'err_msg'=>$e->getMessage(), 'file'=>$e->getFile().'_'.$e->getLine(), 'time_consume'=>$time_consume];
            Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '插入计划任务-异常', $logArr);
            return $logArr;
        }

        return '计划操作完成';
    }

    /**
     * @param object $plan
     * @return string
     */
    public static function getCodesByPlan(object $plan){
        # 4、投注号码 codes
        $codes = self::getCodes($plan->tz_type, $plan->buy_type, $plan->hz_Arr, $plan->id);

        return $codes;
    }

    /**
     * @param $lottery_type
     * @param string $qihao
     * @param string $status_key
     * @return array|DataDealStatus|null
     */
    public static function getDataDealStatus($lottery_type, $qihao='', $status_key='opProfitsPlans_status'){

        $m = \Yii::$app->cache;
        $mkey = CacheKeyService::lotteryDealStatus($lottery_type, $qihao, $status_key);
        $status = $m->get($mkey);
        if(empty($status)){
            $DataDealStatus = DataDealStatus::find()->where(['lottery_type'=>$lottery_type, 'next_qihao'=>$qihao])->limit(1)->one();
            Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '数据处理状态', ['lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'DataDealStatus'=>$DataDealStatus->attributes]);
            $status = 0;
            if(!empty($DataDealStatus)){
                $status = $DataDealStatus->$status_key;
                commonRedis()->setex($mkey, 5, $status);
            }
        }

        return $status;
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
            $tmpData['codes'] = self::getCodes($plan->tz_type, $plan->buy_type, $plan->hz_Arr, $plan->id);
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
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '投注计划001', ['uid'=>$uid,'lottery_types'=>$lottery_types]);

        $RedisLock = new RedisLock();

        foreach ($lottery_types as $lottery_type) {
            $where = ['AND', ['=', 'status', 1], ['=', 'is_batch_simulate', 1], ['=', 'lottery_type', $lottery_type]]; # is_batch_simulate:0正常1批量模拟历史记录
            if(!empty($where)){
                $where[] = ['=', 'uid', $uid];
            }

            $query = UserSysPlans::find()->where($where);
            $sql = $query->createCommand()->getRawSql();
            $plans = UserSysPlans::find()->where($where)->all();
            Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '投注计划002', ['uid'=>$uid,'lottery_type'=>$lottery_type, 'count'=>count($plans), 'sql'=>$sql]);
            if (empty($plans)) {
                Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '投注计划', ['uid'=>$uid,'lottery_type' => $lottery_type, 'msg' => '没有开启的计划']);
                continue;
            }
            foreach ($plans as $plan) {
                $rst = ['status'=>200, 'data'=>['plan_id'=>$plan->id], 'msg'=>'操作成功'];

                try {
                    $plan_id = $plan->id;
                    Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '投注计划00', ['uid'=>$uid, 'plan_id'=>$plan_id, 'lottery_type'=>$lottery_type]);
                    $next_qihao = NumService::getPlanBetCurrentQihao($plan, $lottery_type); # 获取当前模拟计划即将下注的期号
                    Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '投注计划01', ['uid'=>$uid, 'plan_id'=>$plan_id, 'lottery_type'=>$lottery_type, 'current_qihao'=>$next_qihao]);

                    $mkey = 'batchSimulateBet_x0_'.$lottery_type.'_'.$uid.'_'.$plan_id.'_'.$next_qihao;
                    $is_exist = $RedisLock->sadd($mkey, $next_qihao);
                    if(!$is_exist){
                        //return ['status'=>301, 'msg'=>'有正在执行的任务,请稍后...'];
                        throw new \Exception('有正在执行的任务,请稍后...');
                    }
                    \Yii::$app->redis->expire($mkey, 120);

                    $start_time1 = microtime(true);
                    $lottery_type = $plan->lottery_type;
                    $codes_hz_data = json_decode($plan->hz_Arr, true);

                    if(empty($next_qihao)){
                        throw new \Exception('即将下注的期号为空');
                    }

                    $end_time1 = microtime(true);
                    Tool_Common::log('/datas/'.__FUNCTION__.'_step', 'INFO', '下注步骤1', ['plan_id'=>$plan_id, 'next_qihao'=>$next_qihao, 'cs_time'=>($end_time1-$start_time1).'s']);
                    //p([$current_qihao, $codes_hz_data]);
                    $beforeQihao = KjDataGet::getBeforeQiHaoByQiHao($next_qihao, $lottery_type);
                    $before_record = BettingRecords::findOne(['qihao'=>(string)$beforeQihao, 'plan_id'=>$plan_id]);
                    if(!empty($before_record) && $before_record->status==0){
                        return BetService::opOneBettingRecordAndHandlePlanStatic($before_record->id, $plan_id, $beforeQihao, $rst);
                    }

                    $isCanBet = SscDataService::isCanBet($plan_id, $next_qihao);
                    $end_time2 = microtime(true);
                    Tool_Common::log('/datas/'.__FUNCTION__.'_step', 'INFO', '下注步骤2', ['plan_id'=>$plan_id, 'next_qihao'=>$next_qihao,'isCanBet'=>$isCanBet, 'cs_time'=>($end_time2-$end_time1).'s']);
                    if(!empty($before_record) && $before_record->status!=1 && !$isCanBet){
                        $logArr = ['uid'=>$uid, 'plan_id'=>$plan_id, 'next_qihao'=>$next_qihao, 'beforeQihao'=>$beforeQihao, 'isCanBet'=>$isCanBet, 'before_record'=>!empty($before_record), 'err_msg'=>'暂时不可以下注'];
                        Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '计划模拟-01', $logArr);
                        throw new \Exception('暂时不可以下注1');
                        //continue;
                    }
                    Tool_Common::log('/datas/'.__FUNCTION__.'_step', 'INFO', '下注步骤3', ['plan_id'=>$plan_id, 'next_qihao'=>$next_qihao]);

                    # 4、投注号码 codes
                    $codes = self::getCodes($plan->tz_type, $plan->buy_type, json_encode($codes_hz_data), $plan->id);
                    Tool_Common::log('/datas/'.__FUNCTION__.'_step', 'INFO', '下注步骤31', ['plan_id'=>$plan_id, 'next_qihao'=>$next_qihao, 'len'=>strlen($codes)]);

                    $is_test = max($plan->is_test, $plan->is_batch_simulate);
                    //p([$is_test, $plan->is_batch_simulate], 0);
                    list($sn, $snid) = BetService::getBetSnId($plan, $plan->plan_type, $is_test, $isAuto);
                    //p([$is_test, $plan->plan_type, $plan->id, $current_qihao]);

                    $end_time3 = microtime(true);
                    Tool_Common::log('/datas/'.__FUNCTION__.'_step', 'INFO', '下注步骤4', ['next_qihao'=>$next_qihao, 'is_test'=>$is_test, 'plan_id'=>$plan_id, 'cs_time'=>($end_time3-$end_time2).'s']);

                    if ($is_test == 1 or $plan->uid == 1) { # 模拟下注
                        $insertRst = self::_logRecordsByPlandId($plan, $next_qihao, $codes, $plan->lottery_type, 2, $sn, $snid, $hzArr, $r=4); # 直接记录表
                        $rst['data'][$plan_id]['logRecord_rst'] = ['rst'=>$insertRst, 'next_qihao'=>$next_qihao];
                    }
                    $end_time4 = microtime(true);
                    Tool_Common::log('/datas/'.__FUNCTION__.'_step', 'INFO', '下注步骤5', ['plan_id'=>$plan_id, 'rst'=>$rst, 'cs_time'=>($end_time4-$end_time3).'s']);
                    if($insertRst['status'] == 200){
                        $planStaticRst = BetService::opOneBettingRecordAndHandlePlanStatic($insertRst['data']['record_id'], $plan_id, $next_qihao, $rst); # 处理开奖和计划相关
                    }
                    $end_time5 = microtime(true);
                    Tool_Common::log('/datas/'.__FUNCTION__.'_step', 'INFO', '下注处理结束', ['plan_id'=>$plan_id, 'rst'=>$rst, 'planStaticRst'=>$planStaticRst, 'cs_time'=>($end_time5-$end_time4).'s', 'all_cs_time'=>($end_time5-$start_time1).'s']);
                    #$RedisLock->unlock($mkey);
                    $RedisLock->srem($mkey, $next_qihao);
                }catch (\Exception $exception){
                    Tool_Common::log('/datas/'.__FUNCTION__."_e", 'ERR', '计划模拟失败', ['err_code'=>$exception->getCode(), 'plan_id'=>$plan_id, 'next_qihao'=>$next_qihao, 'lottery_type'=>$lottery_type, 'err_msg'=>$exception->getMessage()]);
                    sleep(2);
                    $rst = ['status'=>301, 'msg'=>$exception->getMessage()];
                    #$RedisLock->unlock($mkey);
                    if($exception->getCode()<40000){
                        $RedisLock->srem($mkey, $next_qihao);
                    }
                }
            }
        }

        return $rst;
    }

    /**
     * @desc 动态过滤号码
     * @param array $hzArr
     * @param string $plan_id
     * @param int $lottery_type
     */
    public static function getDynamicsHzArr(&$hzArr = [], $plan_id){
        if(!is_array($hzArr)){
            return;
        }

        $filters = $hzArr['filters'] ?? [];
        if(!is_array($filters) || empty($filters['filter_type'])){
            return;
        }

        $filter_type = (int)$filters['filter_type'];
        if($filter_type == 1){
            $filter_poses = $filters['filter_poses'] ?? [];
            if(!is_array($filter_poses)){
                $filter_poses = array_filter(explode(',', (string)$filter_poses), 'strlen');
            }
            $x_poses = array_diff(NumService::$ALL_POSES, $filter_poses);
            foreach ($x_poses as $x_pos){
                $hzArr['p'.$x_pos] = 'X';
            }
        }elseif(in_array($filter_type, BetService::getCodesNewFilterTypes())){
            $plan = UserSysPlans::findOne($plan_id);
            if(empty($plan)){
                return;
            }
            $lottery_type = $plan->lottery_type;
            if(empty($filters['current_kj_qihao'])){
                $lastBetRecord = BettingRecords::find()->where(['lottery_type'=>$lottery_type, 'plan_id'=>$plan_id])->limit(1)->one();
                $filterNumsQihao = $lastBetRecord->qihao ?? ($filters['start_qihao'] ?? '');
            }else{
                $filterNumsQihao = $filters['current_kj_qihao']; # 上期开奖之后记录开奖当期期号
            }
            if(empty($filterNumsQihao)){
                return;
            }
            $qh_where = [
                'AND',
                ['=', 'lottery_type', $lottery_type],
                ['=', 'qihao', $filterNumsQihao],
            ];
            $remove_types = $hzArr['remove_types'] ?? [];

            $SscKjData = SscKjData::find()->where($qh_where)->limit(1)->one();
            if(empty($SscKjData)){
                return;
            }
            # 兄弟
            if($SscKjData->type_2b == 0){
                $hzArr['type_2b'] = 1;
            }

            # 三兄弟
            if($SscKjData->type_3b == 1){
                //$hzArr['type_3b'] = 0; # 如果上三兄弟就排除三兄弟
                $remove_types[] = 6; # 排除三兄弟
            }
            # 双两兄弟
            if($SscKjData->type_22b == 1){
                $remove_types[] = 8; # 排除双两兄弟
            }
            # 三现+两兄弟
            if($SscKjData->type_3n_2b == 1){
                //$hzArr['type_3n_2b'] = 0; # 如果上三兄弟就
                $remove_types[] = 1; # 排除三现+两兄弟
            }

            if($filter_type == BetService::CODES_FILTER_TYPES_2){
                # 排除类型1
                if(isset($hzArr['type_2b']) && $hzArr['type_2b'] == 1){
                    $remove_types[] = 1; # 排除三现+两兄弟
                    $remove_types[] = 8; # 排除双两兄弟
                }
            }elseif ($filter_type == BetService::CODES_FILTER_TYPES_3){
                # 排除类型1
                # 单双排除
                $n_code_type = $SscKjData->code_1_2_3_4;
                $type_ds_details = ["2222","1111","1112","1121","1211","2111","1122","1212","1221","2112","2121","2211","1222","2122","2212","2221"];
                $type_key = array_keys($type_ds_details, $n_code_type);
                if(isset($type_key[0])){
                    unset($type_ds_details[$type_key[0]]);
                }
                $hzArr['type_ds_details'] = $type_ds_details;

                if(isset($hzArr['type_2b']) && $hzArr['type_2b'] == 1){
                    $remove_types[] = 1; # 排除三现+两兄弟
                }
            }

            if($SscKjData->type_3b == 1){
                $hzArr['type_22b'] = 0;
            }
            $hzArr['remove_types'] = $remove_types;

            $c_codes = array_unique(explode(',', $SscKjData->code_4n_str));
            if(count($c_codes) < 4){
                $plan->is_test = 1;
            }else{
                $hzArr['arb_pos_isbaohan'] = 1; # 是否包含
                $hzArr['arb_pos_nums'] = 2; # 过滤号码至少包含2个号码
                $hzArr['arb_pos_codes'] = implode('', array_diff([0,1,2,3,4,5,6,7,8,9], $c_codes));
            }
        }
    }

    /**
     * @desc 处理开奖和计划相关
     * @param array $insertRst
     * @param string $plan_id
     * @param $qihao 已经投注待处理开奖的期号
     * @param array $rst
     * @return bool
     */
    public static function opOneBettingRecordAndHandlePlanStatic($record_id, $plan_id='', $qihao='', &$rst=[]){
        # 下注完、处理开奖
        if(!$record_id) return false;
        $opKjRst = OpKjService::opOneBettingRecord($record_id);
        $rst['data'][$plan_id]['opKjRst'] = $opKjRst;

        if($opKjRst['status'] == 200){
            $opHandlePlanRst = SscDataService::handleOnePlanStatic($plan_id, $qihao, $is_simulate_bet=1);
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

    /**
     * @desc 获取所有过滤类型
     * @return int[]
     */
    public static function getCodesNewFilterTypes(){
        return [
            self::CODES_FILTER_TYPES_2,
            self::CODES_FILTER_TYPES_3,
        ];
    }

}
