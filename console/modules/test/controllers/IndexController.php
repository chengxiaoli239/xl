<?php
namespace console\modules\test\controllers;

use backend\models\BetErrorPlansTask;
use backend\models\BettingRecords;
use backend\models\DataDealStatus;
use backend\models\open\PlatformGroup;
use backend\models\open\PlatformRobot;
use backend\models\PlanStaticProfits;
use backend\models\searchs\wechat\Bets;
use backend\models\SscKjData;
use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use backend\models\VBets;
use backend\service\agent\AgentService;
use backend\service\agent\AgentUsersService;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\clients\AgentClientsService;
use backend\service\clients\TzSystemUsersService;
use backend\service\HN0898Service;
use backend\service\Lucky5\Lucky5Service;
use backend\service\NineNine\NineNineNewService;
use backend\service\numbers\DynamicFilterService;
use backend\service\numbers\NumCodeService;
use backend\service\NumService;
use backend\service\SscDataService;
use backend\service\statics\plan\OperatePlanService;
use backend\service\statics\statics_3d\Statics3dUserDataService;
use backend\service\statics\statics_qx\PositionDxDsService;
use backend\service\statics\statics_qx\StaticsQxMissService;
use backend\service\statics\yl\OneNumYl;
use backend\service\StaticService;
use backend\service\TzService;
use common\helpers\lottery\LotteryBet;
use common\helpers\LotteryType;
use common\kj\BaseKj;
use common\kj\qxc\QxcTcw;
use common\kj\ssc\Aozhou;
use common\kj\ssc\Lucky5;
use common\models\wechat\WechatUser;
use common\service\cache\CacheKeyService;
use common\service\CommonService;
use common\service\helpers\ThirdD;
use common\service\jobs\plan\OperateUserPlanKjJob;
use common\service\lottery\aozhou5\AoZhou5BetService;
use common\service\lottery\aozhou5\jobs\AoZhou5BetJobs;
use common\service\lottery\LotteryTypeService;
use common\service\open\ActionBaseService;
use common\service\open\actions\PlatformRobotService;
use common\service\open\aozhou5\ActionService;
use common\service\open\aozhou5\ActionYIFanService;
use common\service\open\telegram\AoZhouKjService;
use common\service\open\telegram\MessageOperateService;
use common\service\ssc\QihaoService;
use common\service\ssc\SscKjDataService;
use common\service\ssc\SscPlanService;
use common\service\thirdD\match\MatchCodeService;
use common\service\thirdD\MethodMatchService;
use common\service\thirdD\OperateLotteryService;
use common\service\thirdD\sx\Ssxx3dBetService;
use common\service\thirdD\sx\Sx3dUserService;
use common\service\thirdD\ThirdDTypeService;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\service\wechat\WechatUserService;
use common\tools\KjDataGet;
use common\tools\Timer;
use common\tools\Tool_Common;
use common\tools\Util;
use DateTime;
use Yii;
use yii\base\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class IndexController extends Controller
{
    /**
     * @desc 测试
     * /www/server/php/74/bin/php /www/wwwroot/lottery_xl/yii test/index/dw
     */
    public function actionDw(): array
    {
        $rst = TzService::operateSystemBetPlans($lottery_type=23, $qihao='250807428', $ignore=0); p($rst);# 处理系统投注计划，更新统计数据、
        $plans = UserSysPlans::find()->where(['uid'=>35, 'status'=>[1,0]])->orderBy(['id'=>SORT_ASC])->all(); // 被复制的用户userId,as06:25
        $planIds =[];
        foreach ($plans as $plan){
            var_dump($plan->id);
            $planIds[$plan->id] = SscPlanService::copyOnePlan($userId=37, $planId=$plan->id); // 这里userId是要复制的用户id, aa55:34、aa68:75
        };
        p(['planIds'=>$planIds, 'count'=>count($planIds)]);
        $result = SscPlanService::copyOnePlan($userId=37, $planId=17926);p($result);
        $rst = NineNineNewService::getSnidBySn($uid = 74, $tz_system_id = 12, $lottery_type = 24);
        p($rst);


        list($currentKjQiHao, $qiHao) = QihaoService::getKjQiHao($lottery_type=8); # 期号数据
        $result = BetService::insertRecord($plan_id=17712, $qiHao, $isAuto=2);
        //$result = BetService::insertRecord($plan_id=17718, '', $isAuto=2);
        p($result);
        $plan_id = 17447;
        $uid = 25;
        $where = ['AND', ['=', 'qihao', '20250311194'], ['=', 'plan_id', $plan_id], ['=', 'uid', $uid]];
        if(BettingRecords::find()->where($where)->limit(1)->exists()){
            throw_info('记录已经存在plan_id:'.$plan_id.'_uid:'.$uid);
        }
        $rst = OneNumYl::yl($lotteryType=8);p($rst);
        $where = ['lottery_type'=>8];
        $field = 'code1';
        $t1 = microtime(true);
        # 本周时间
        list($start, $end) = Timer::thisWeekTime();//p([date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end)]); # 本周时间
        list($thisWeekMiss, $thisWeekAllCount) = OneNumYl::getZoneCodeYlInfo($field, $start, $end, $where);
        $t2 = microtime(true);
        # 本月时间
        list($start, $end) = Timer::thisMonthTime();//p([date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end)]); # 本月时间
        list($thisMonthMiss, $thisMonthAllCount) = OneNumYl::getZoneCodeYlInfo($field, $start, $end, $where);
        $t3 = microtime(true);
        p([
            'thisWeekMiss' => $thisWeekMiss,
            'thisWeekAllCount' => $thisWeekAllCount,
            'thisMonthMiss'=>$thisMonthMiss,
            'thisMonthAllCount'=>$thisMonthAllCount,
            'c1'=>($t2-$t1).'s',
            'c2'=>($t3-$t2).'s',
        ]);

        $statics = StaticService::staticAllSdProfitsMonth($month='2025-02', $lottery_type=8);
        p($statics);

        $BettingRecord = BettingRecords::findOne(['plan_id'=>16798]);
        $lottery_type = 8;
        $params = ['business_id'=>$BettingRecord->qihao, 'lottery_type'=>$lottery_type, 'bet_id'=>$BettingRecord->id];
        push_queue_open(OperateUserPlanKjJob::class, $params);
        p('dddddd');
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type=8);
        $planId = 16742;
        $where = ['AND', ['=', 'qihao', $nextQiHao], ['=', 'plan_id', $planId], ['=', 'uid', 50]];
        if(BettingRecords::find()->where($where)->exists()){
            throw_info('yx表已记录1...');
        }
        p('dddd');
        $where = ['AND', ['=', 'lottery_type', $lottery_type]];
        if(!empty($accountOrId)){
            $where[] = [ 'OR', ['=', 'account', $accountOrId], ['=', 'id', $accountOrId]];
        }else{
            # is_batch_simulate:0正常1批量模拟历史记录
            $where  = array_merge($where, [['=', 'status', 1], ['=', 'is_batch_simulate', 0]]);
        }
        $rst = [];
        $plansQuery = UserSysPlans::find()
            //->select(['id', 'uid', 'status'])
            ->where($where); // ->all();
        $t1 = microtime(true);
        foreach ($plansQuery->each(20) as $plan){
            try {
                $planId = $plan->id;
                $uid = $plan->uid;
                $status = $plan->status;
                print_r(['planId'=>$planId, 'uid'=>$uid, 'status'=>$status]);
                $rst['data'] = ['activeQiHao'=>$nextQiHao, 'plan_id'=>$plan->id, 'msg'=>'正常'];
            }catch (\Exception $e){
                if($e->getCode()<40000){
                    $logArr = ['uid'=>$plan->uid, 'plan_id'=>$planId, 'lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage(), 'errCode'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()];
                    Tool_Common::log('/bet/'.__FUNCTION__, 'ERR', '插入计划2-异常', $logArr);
                }
                $rst['data']['plan_id'] = ['plan_id'=>$planId, 'msg'=>$e->getMessage()];
            }
        }
        $t2 = microtime(true);
        $rst['time_consume'] = ($t2-$t1).'s';
        p($rst);

        $preInsertLockKey = CacheKeyService::preInsertPlanTaskKey($id=5, $activeQiHao='111');
        commonRedis()->setex($preInsertLockKey, BetService::getBetCacheTime($lottery_type=8, $activeQiHao), 1);# 投注之后缓存时间
        $r = commonRedis()->del($preInsertLockKey);
        p([$r]);
        $r = \Yii::$app->db->getSchema()->refreshTableSchema('{{%lt_bet_error_plans_task}}'); p($r);
        # is_batch_simulate:0正常1批量模拟历史记录
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType=8);
        $currentKjQiHao = LotteryType::getBeforeNQiHao($currentKjQiHao, $qiNum=1);
        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType);
        p($historyKjData);

        //p(date('Y-m-d', time()-1*86400));
        $i = 1;
        for($i=0; $i<60; $i++){
            $rst = StaticService::staticCodeTypeArisePerData($dates=[
                date('Y-m-d', time()-$i*86400),
            ],
            $lottery_type = 8);
        }
        p($rst);
        $statics = StaticService::staticCodeTypeCounts($date=date('Y-m-d'), $lottery_type=8);p($statics);
        $TzSystemsUser = TzSystemsUsers::findOne(41);
        $snId = Lucky5Service::getSnId($TzSystemsUser); p($snId);
        $groups = \backend\service\numbers\NumCodeService::getRandNumGroups(0);
        p($groups);

        list($code, $hfx) = \backend\service\numbers\NumCodeService::getRandCode($planId=11496, $qiHao='20241103224', $type=4);
        p([$code, $hfx]);
        $qiHao = LotteryType::getBeforeNQiHao($currentKjQiHao='20241111123', $n=2, $lotteryType=8);
        p([$currentKjQiHao, $qiHao]);
        $currentKjQiHao = KjDataGet::getBeforeQiHaoByQiHao($currentKjiHao='20241111123', $lottery_type=8);
        p([$currentKjQiHao, $lottery_type]);
        $p = '123';
        $pos = str_split($p);
        p($pos);

        for ($i=0; $i<15; $i++){
            $date = date('Y-m-d', strtotime('2024-09-23')+$i*86400);
            if($date>date('Y-m-d')) return false;
            $rst = PositionDxDsService::staticPositionDxDs(8, $date);
            print_r(['date'=>$date, 'rst'=>$rst]);
        }
        p($rst);
        $codes = SscKjDataService::getRecentlyPosCodes($lotteryType=8, $positions=[3], $num = 4);p($codes);
        $codeTypes = StaticService::getAllCodeTypes($type = 2); p($codeTypes);# 统计基础号码类型筛选,类型：1和值2号码类型[例如:双双重、三重]
        $HI = date('H:i');
        if('21:00'<$HI AND $HI<'22:00'){
            print_r('rrrr');
        }else{
            print_r('bbbbb');
        }
        p('dddd');

        $sscData = SscKjData::find()->where(['date'=>'2024-09-15', 'lottery_type'=>8])->asArray()->all();
        p(array_column($sscData, 'qihao'));
        $status = (new LotteryBet())->checkLotteryStatus($lottery_type=1); p([$lottery_type, $status]);# 是否封盘, 封盘之时即是抓取之时
        $rst['kj'] = KjDataGet::grabKjData();
        p($rst);
        $countArr = SscDataService::getAriseCounts($val='type_log', $count=4360, $lottery_type=8);p($countArr);
        # 三字现带双重
        $t1 = microtime(true);
        $rst = SscDataService::updateCodeTypeYL($type = 2);
        $t2 = microtime(true);
        p(['rst'=>$rst, 'time_consume'=>($t2-$t1).'s']);

        list($lastQihao, $lastIndexId, $lastId, $nextQihao) = SscDataService::getKjDataLastIndexId($lottery_type=8);
        p([$lastQihao, $lastIndexId, $lastId, $nextQihao]);
        $t1 = microtime(true);
        $rstLog['updateSdHzYL'] = SscDataService::updateSdHzYl($lottery_type=8);$t2 = microtime(true);
        $rstLog['time_consume']=($t2-$t1).'s';
        p($rstLog);// 单双遗漏 耗时3.5s
        # 所有的和值集合，作为字符串
        $values = [
            "0,1,2,3,4,5,6",
            "5,6,7,8,9,10",
            "11,12,13,14,15",
            "16,17,18,19",
            "20,21,22,23,24",
            "25,26,27,28,29",
            "30,31,32,33,34,35,36",
            "5,7,9,11,13,15",
            "6,8,10,12,14,16",
            "17,19,21,23,25,27",
            "18,20,22,24,26,28",
            "35",
            "34",
            "33",
            "32",
            "31",
            "30",
            "29",
            "28",
            "27",
            "26",
            "25",
            "24",
            "23",
            "22",
            "21",
            "20",
            "19",
            "18",
            "17",
            "16",
            "15",
            "14",
            "13",
            "12",
            "11",
            "10",
            "9",
            "8",
            "7",
            "6",
            "5",
            "4",
            "3",
            "2",
            "1"
        ];

        $date_key = 'hz_'.date('Ymd');
        # 遍历每个字符串并更新哈希表
        foreach ($values as $value){
            commonRedis()->hincrby($date_key, $value, 1);
        }
        $data = commonRedis()->hgetall($date_key);
        $dataVal = [];
        foreach ($data as $count=>$key){
            //p($key.'=='.$count, 0);
            $dataVal[$key] = $count;
        }
        p(['date_key'=>$date_key, 'dataVal'=>$dataVal]);
        p(['data'=>$datax]);
        $codes_4nums_hz = SscKjData::find()->select(['*'])
            ->where(['lottery_type'=>8])->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->all();
        p($codes_4nums_hz);
        $planIdData = UserSysPlans::find()->select(['uid', 'id'])->where(['status'=>1])->asArray()->all();
        // 使用 ArrayHelper 提取 uid 和 id 的集合
        $uids = array_unique(ArrayHelper::getColumn($planIdData, 'uid'));
        $planIds = array_unique(ArrayHelper::getColumn($planIdData, 'id'));
        p([$uids, $planIds, $planIdData]);
        $planStaticProfitsData = PlanStaticProfits::find()->where(['plan_id'=>$planIds])->asArray()->indexBy('plan_id')->all();
        p([$planIds, 'planStaticProfitsData'=>$planStaticProfitsData]);
        $lottery_type = 8;
        $query = SscKjData::find()->select(['COUNT(id) AS nums'])->where(['date'=>date('Y-m-d'),'codes_4nums_hz'=>[16,17,18,19], 'lottery_type'=>$lottery_type])->limit(1);
        $sql = $query->createCommand()->getRawSql();p($sql);
        $today_nums = $query->asArray()->one()['nums'];
        $t1 = microtime(true);
        $miss = SscDataService::getSdHzYlHistoryMiss($zuHes=[16,17,18,19,20], $lottery_type=8, $static_nums=500); p($miss, 0);
        $t2 = microtime(true);
        $logArr = [
            't1' => ($t2-$t1).'s',
        ];
        p($miss, 0);
        p($logArr);
        $UserSysPlan = UserSysPlans::findOne(10184);
        $rst = OperatePlanService::operatePlans18($UserSysPlan, $current_kj_qihao='20240902131');
        p($rst);

        list($currentKjQiHao, $qiHao) = QihaoService::getKjQiHao($lotteryType=8);
        p([$currentKjQiHao, $qiHao]);
        $lottery_type = 8;
        $where = ['AND', ['=', 'status', 1], ['=', 'is_batch_simulate', 0], ['=', 'lottery_type', $lottery_type]];
        //$where[] = ['=', 'uid', 17]; # 测试

        $plans = UserSysPlans::find()->where($where); // ->all();
        Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '批量插入任务000', ['lottery_type'=>$lottery_type, 'counts'=>$plans->count()]);
        if($plans->isEmpty()){
            Tool_Common::log('/bet/'.__FUNCTION__, 'INFO', '投注计划', ['lottery_type'=>$lottery_type, 'msg'=>'没有开启的计划']);
            var_dump('kong');
        }
        p('dddd');
        $bets = SscDataService::isZjBeforeData(8);
        $flag = SscDataService::isZjBeforeNew($bets[941699]??[]);
        p([$flag, $bets]);
        $lottery_type = 8;
        # 止盈止损、翻倍止盈止损 计划
        $where = [
            'OR',
            [ 'AND', ['IN', 'plan_type', [0, 1, 3, 5]], ['=', 'status', 1], ['=', 'is_batch_simulate', 0] ],
            [ 'AND', ['>', 'take_profits', 0], ['>', 'stop_loss', 0], ['=', 'status', 1], ['=', 'is_batch_simulate', 0] ]
        ];
        Tool_Common::log('opProfitsPlans_'.$lottery_type, 'INFO', '处理止盈止损\倍投计划2', ['lottery_type'=>$lottery_type]);

        $logArr = [];
        $current_kj_qihao = HN0898Service::getCurrentQihao($lottery_type);

        $UserSysPlans = UserSysPlans::find()->where($where)->andWhere(['=', 'lottery_type', $lottery_type]);
        foreach ($UserSysPlans->each(10) as $UserSysPlan){
            p(['current_kj_qihao'=>$current_kj_qihao, 'UserSysPlan_id'=>$UserSysPlan->id]);
        }
        $difference = array_diff($otherArray=[1,2,3,4], $positions=[1,4,2]);
        p($difference);
        $bet_log = Lucky5Service::getBetLog($tz_type=25, $plan_id=	9621);p($bet_log);
        $userId = 21;
        $robotAdmin = WechatUser::find()->where(['user_id'=>$userId, 'is_admin'=>1])->asArray()->limit(1)->one();
        $messageService = new MessageOperateService($userId, $robotAdmin['userName']);
        p($messageService->robotInfo);
        p($messageService);
        $CurrentKjData = NumCodeService::getKjData($currentKjQiHao='20240718109', $lottery_type=8);p($CurrentKjData);
        $rst = KjDataGet::updateNullCode($lottery_type = 8); p($rst);
        $rst['operateProfitsPlans'] = SscDataService::operateProfitsPlans($lottery_type = 8);
        p($rst);
        $domain = BaseKj::getApiHostByRoute('/kj/lucky5/shi-xun-one');p($domain);
        $domain = BaseKj::getApiHostByRoute('/kj/qxc/nine-nine-plw');p($domain);
        $dateString = '20231114002';
        try {
            $logData = Json::decode('{"log_member_quick_select_id":"14873805","member_id":"9806","account":"Abc123bb","nickname":"","fix_num":"40","bet_count":"4993","bet_money":"1997.2","operation_content":"[四定位]，配数“[取]”：第2位：[01356]，第3位：[24789]，固定合分除值：第[1]位选中，第[2]位选中，第[3]位选中，内容：[2]；，不定合分值(两数合)：[01234]，不定合分值(三数合)：[012345]，合分值范围：[8-28]，包含“[取]”数：[43560]，三兄弟“[除]”操作，四兄弟“[除]”操作，对数“[除]”数：[49]，","operation_datetime":"07-05 22:23:58","time_value":"2024/7/5 22:23:58","operation_ip":"112.66.*.*","ip_value":"112.66.17.177","operation_ip_extension":"112.66.17.177","is_package":"0","log_type":"102"}');
            list($code, $qihao) = AgentClientsService::operateOneBetLog($logData, $access_token='eb70910c92f134bd54a3837d978f055b');
            p([$code, $qihao]);
            $rst = BaseService::login($id=58); p($rst);
            //$d = \common\kj\ssc\Thirdd::getCurrentKjData($lottery_type=8);p($d);

            $r = BaseKj::setKjDataCache($lottery_type=8, $expect='240701001', $kjData=['except'=>'240701001', 'opentime'=>date('Y-m-d H:i:s'), 'opencode'=>'1,2,3,4,5']);
            $mKey = CacheKeyService::lotteryOpenDataKey($lottery_type, $expect);
            p($mKey);
            $data = commonRedis()->get($mKey);
            p(['r'=>$r, 'data'=>$data]);
            foreach ([8] as $lotteryType){
                //$r = (new LotteryBet())->checkLotteryStatus($lotteryType);//p($r);
                $r = (new LotteryBet())->checkLotteryStatus($lotteryType, '2024-07-01 12:06:31');//p($r);
                $rst[$lotteryType] = $r;
            }
            p(['rst'=>$rst]);
            $r = NumCodeService::addBetDescRand($planId=1000, $dateString, '十位除1234');
            $members = NumCodeService::getRandBetDesc($planId, $dateString);
            p([$r, $members]);
            $status = KjDataGet::isCanGrab($lottery_type=8); p($status);
            $tzSystemUser = TzSystemsUsers::findOne(76);
            $userInfo = (new ActionYIFanService($tzSystemUser))->getUserData($isAuto=1);p($userInfo);
            $logData = Json::decode('{"log_member_quick_select_id":"14169549","member_id":"9806","account":"Abc123bb","nickname":"","fix_num":"40","bet_count":"5017","bet_money":"1003.4","operation_content":"[四定位]，配数“[取]”：第2位：[01356]，第3位：[24789]，固定合分除值：第[2]位选中，第[3]位选中，第[4]位选中，内容：[2]；，不定合分值(两数合)：[01234]，不定合分值(三数合)：[012345]，合分值范围：[8-28]，包含“[取]”数：[43560]，三兄弟“[除]”操作，四兄弟“[除]”操作，对数“[除]”数：[16]，","operation_datetime":"06-13 14:37:44","time_value":"2024/6/13 14:37:44","operation_ip":"112.66.*.*","ip_value":"112.66.28.70","operation_ip_extension":"112.66.28.70","is_package":"0","log_type":"102"}');
            list($code, $qihao) = AgentClientsService::operateOneBetLog($logData, $access_token='eb70910c92f134bd54a3837d978f055b');
            p([$code, $qihao, $logData]);
            $data = AgentService::getCalcMoney($userId=21);p($data);

            $mKey = CacheKeyService::lotteryBetPlanIdKey('aa30301', $qihao='1234568', $plan_id=1234);
            $lock = commonRedis()->setnx($mKey, 300);
            $value = commonRedis()->get($mKey);
            p([$lock, $value]);
            $betTasksQuery = BetsBackend::find()->select(['order_id']);
            $betTasksQuery->where(['user_id'=>21, 'push_status'=>BetsBackend::STATUS_WAIT])
                ->andWhere(['=', 'order_id', '118692'])
                ->andWhere(['<', 'created_at', time() - 60]); # 只1分钟内下注，超过则失败提示
            $orderIds = $betTasksQuery->asArray()->column();
            BetsBackend::updateAll(['push_status'=>BetsBackend::PUSH_STATUS_CANNOT, 'post_desc'=>['msg'=>'异常，请重新下注']], ['order_id'=>$orderIds, 'push_status'=>BetsBackend::PUSH_STATUS_WAIT]);
            foreach ($orderIds as $orderId){
                # 澳洲五客户端下注结果通知
                $pushData = ['orderId' => $orderId, 'business_id' => $orderId];
                push_queue_open(AoZhou5BetJobs::class, $pushData);
            }
            p($orderIds);
            //$rst = BetService::pushTasksBetRst($plan_id=7953, $qihao=20240203238, ['bet_rst'=>1,'time_consume'=>2], $access_token='g5843e29ac8dd191e894c7dcea547792', $lottery_type=LotteryType::LUCKY_5); p($rst);
            $rst = BetService::pushTasksBetRst($plan_id=AoZhou5BetService::TEST_BET_ID, $qihao=51108412, ['bet_rst'=>1,'task_status'=>2,'time_consume'=>2], $access_token='g5843e29ac8dd191e894c7dcea547792', $lottery_type=LotteryType::AZ_LUCKY_5);
            p($rst);
            $betRow['qihao'] = DataDealStatus::find()->select('next_qihao')->where(['lottery_type'=>28])->limit(1)->orderBy(['id'=>SORT_DESC])->scalar();
            p($betRow['qihao']);
            $rst = AoZhou5BetService::getBetTasks($id=AoZhou5BetService::TEST_BET_ID);p($rst);
            $text = '千23百34=1';
            list($code, $data, $err_msg) = AgentClientsService::getKuaiYiDescByOperationLogs($text);p([$code, $data, $err_msg]);
            $mKey = 'llllllll';
            #$result = \Yii::$app->redis->get($mKey);
            #$exists = \Yii::$app->redis->exists($mKey);
            $result = commonRedis()->get($mKey);
            $exists = commonRedis()->exists($mKey);
            print_r(['exists'=>$exists]);
            if(empty($result)){
                commonRedis()->setex($mKey, 10, ['num'=>333]);
                //\Yii::$app->redis->expire($mKey, 10);
                print_r('为空');
            }else{
                print_r(['不为空：', $result]);
            }
            p(['result'=>$result, 'date'=>date('Y-m-d H:i:s')], 0);
            p('设置结束'.date('Y-m-d H:i:s'));
            $data = Aozhou::getSiteLucky5($type='json');p($data);
            $groups = PlatformGroup::getGroups($userId=21);p($groups);
            $model = PlatformRobot::findOne(['platform_robot_id'=>'6744049574']);
            $rst = PlatformRobotService::getUpdates($model); p($rst);# 添加之后立马获取群聊消息，记录群ID
            $tzSystemUser = TzSystemsUsers::findOne(68);
            #$r = (new ActionBaseService())->login($tzSystemUser);p($r);
            $rst = AgentUsersService::userFlowsCheck(['id'=>16791, 'status'=>1], 21, '管理员消息回复处理');p($rst);
            $rst = [];
            list($entertainedStatus, $grabStatus) = LotteryBet::isEntertained(LotteryType::LUCKY_5);p([$entertainedStatus, $grabStatus]);
            list($lotteryType, $lotteryName) = [LotteryType::AZ_LUCKY_5, LotteryType::TYPE_OPTIONS[LotteryType::AZ_LUCKY_5]];
            list($currentKjQiHao, $qiHao) = QihaoService::getKjQiHao($lotteryType);
            p([$currentKjQiHao, $qiHao]);
            $params = Json::decode('{"user_id":21,"business_id":6830978835,"token":"6902259997:AAEsg51soXNS1MYPdmHNnpj0YWBo6J3aeyo","update_id":840228241,"message":{"message_id":27,"from":{"id":6830978835,"is_bot":false,"first_name":"破局","last_name":"Mr","language_code":"zh-hans"},"chat":{"id":6830978835,"first_name":"破局","last_name":"Mr","type":"private"},"date":1709564365,"text":"1正/20"}}');
            $params = Json::decode('{"update_id":840228414,"message":{"message_id":776,"from":{"id":6830978835,"is_bot":false,"first_name":"破局","last_name":"Mr","language_code":"zh-hans"},"chat":{"id":6830978835,"first_name":"破局","last_name":"Mr","type":"private"},"date":1712280539,"text":"查"},"business_id":6830978835,"user_id":"21","token":"6902259997:AAEsg51soXNS1MYPdmHNnpj0YWBo6J3aeyo","queue_open":true}');
            $d = \common\service\jobs\telegram\MessageReceiveJobs::handle($params);
            $bet = Bets::findOne(32126);
            $r = \common\service\lottery\aozhou5\AoZhou5Service::opOneBettingRecord($bet->id, $bet);p($r);
            $rst = CommonService::getVoteCode(); p($rst);
            list($code, $data, $msg) = AoZhou5BetService::postToSite($betRowId=32123);p([$code, $data, $msg]);
            $current_qihao = HN0898Service::getCurrentQihao($lottery_type = 28); # 针对哪一期过滤，默认为：当前期号
            p($current_qihao);
            LotteryTypeService::getLotteryTypeData($grabDataStatus=1, $useCache=0);
            $lottery_types = StaticService::getGrabDataLotteryTypes($useCache=0);
            p($lottery_types);
            $lottery_types = \backend\service\UserSysPlansService::getMyLotteryTypes($user_id=40);//p($lottery_types);

            $mkey = CacheKeyService::userLotteryTypes($user_id=40);
            $lottery_types1 = commonRedis()->get($mkey);
            p([$lottery_types, $lottery_types1]);
            //$rst['updateDsYL'] = SscDataService::updateSdHzYl($lottery_type = 17); p($rst);// 更新和值遗漏
            # 测试回滚
            # 测试回滚2
            $rst['updateDs'] = SscDataService::updateDsData($lottery_type = 17);p($rst); // 每期开奖遗漏 -- 新开
            $r = SscDataService::openOnePlanBetStatus($plan_id=120, $next_qihao='2024038');
            $rst = SscDataService::isCanBet($plan_id, $next_qihao); p($rst);
            //$data = \common\service\ssc\QihaoService::getKjQiHao(8);p($data);
            $plan = UserSysPlans::findOne(7995);
            $data = QxcTcw::getOfficialCode($type='json', $is_auto=1, $lottery_type=27);p($data);

            $data = QxcTcw::getNineNineLottery($type='json', $is_auto=2, $lottery_type=27);
            $Thirdd = new \common\kj\ssc\Thirdd();
            $data = $Thirdd->getFuCai3d($type='json', 2);p($data);
            $kjData = \common\kj\ssc\Thirdd::getCurrentKjData($lottery_type=26, $current_qihao);
            p([$kjData, $current_qihao]);
            $kdCodes = \backend\service\NumService::getKuduCodes([2,5,8,7], $kd=3);p($kdCodes);
            $qihao = substr(QxcTcw::getNineNineQihao($lottery_type=26, 2), 2);p($qihao);# 期号
            $MessageService = new EYunMessageOperateService($user_id=22);
            $rst = $MessageService->searchUser(''); p($rst);
            $betRow = Bets::findOne(26244	);
            list($code, $data, $msg) = OperateLotteryService::operateOne($betRow);
            p([$code, $data, $msg]);
            $str = 'http://47.107.58.222:8090/wechat/bets/index.html?Bets%5BwechatUserName%5D=wxid_ckgr7i2q9fr522&Bets%5Border_id%5D=&Bets%5Bplay_method%5D=&Bets%5Bqihao%5D=&Bets%5Bstatus%5D=&Bets%5Bpush_status%5D=&Bets%5Blottery_type%5D=';
            p(urldecode($str));
            list($code, $data, $msg) = Statics3dUserDataService::calculateUserDayData($wechat_user_id=250, $date='2023-12-23', [27, 26]);
            p([$code, $data, $msg]);
            $TzSystemsUser = TzSystemsUsers::findOne(42);
            $rst = Sx3dUserService::login($TzSystemsUser);p($rst);
            #$code = Json::decode('{"playedId":200,"playedName":"u76f4u9009","actionData":"213,234,879,342,324,456","bonusProp":900,"actionNum":6,"mode":"18"}');
            #list($localToSiteMethodInfo, $codeData) = MatchCodeService::apiMethodDataToLocalMethodData($code);
            #p([$localToSiteMethodInfo, $codeData]);
            #$betRow = Bets::findOne(6362);
            #list($code, $data, $msg) = OperateLotteryService::operateOne($betRow, $kjCode='2,5,4');p([$code, $data, $msg]);
            //$plan = UserSysPlans::findOne(7653);
            //$filter_dynamic_codes = NumService::getBeforeKjCodesDynamic($plan, [60]); p(count($filter_dynamic_codes));
            //$text = '百:1346,十:3689,个:6789';
            # 福彩 02349/ 组六组三 各20
            # 福彩 02349/ 12345..32457组六  组三各20
            $text = '福彩 02349/ 组六组三 各20';

            ##################### 直、组 #######################
            $text = '福一直二组 369';
            $text = '福直一组二 369';
            $text = '福1直2组 369';
            $text = '福直1组2 369';

            $text = '福一元直二元组 369';
            $text = '福直一元组二元 369';
            $text = '福1元直2元组 369';
            $text = '福直1元组2元 369';

            $text = '福1倍直2倍组 369';
            $text = '福2倍组1倍直 369';
            $text = '福一倍直二倍组 369';
            $text = '福二倍组一倍直 369';
            ##################### 直、组 #######################

            $text = '福 二码定百234个456 各10';
            $text = '单032.302=十五倍，';
            $text = '福 6拖12347组三 各9元';
            $text = '复式 123 ，2345，23456，3456789 各100元';
            //$text = "123456789直2倍";
            $text = "体组六组三 1拖2345、23456 各10元

体组六组三 2拖1345、13456 各10元";
            #$betTexts = EYunMessageOperateService::resetMethodText($text); p($betTexts);# 重置匹配文本
            //$betText = EYunMessageOperateService::resetText($text); p($betText);# 重置匹配文本
            //preg_match('/各([' . MethodMatchService::CN_SINGLE_TEXT . ']{1,3})/u', $text, $matches); p($matches);
            #list($code, $data, $msg) = EYunMessageOperateService::getOnePlayMethodG($text); p([$text, $code, $data, $msg]); # 单个规则文本匹配处理
            $MessageService = new EYunMessageOperateService($user_id=22);
            $rst = $MessageService->receive($text, $fromUser='wxid_875i1kgd38x122'); p($rst);
            $betCodes = Ssxx3dBetService::resetOneZhiXuanFuShi($betCodes='1246;5678');p($betCodes);
            $qihao = Util::getBeforeNumQihao($dateString, $n=2);
            list($code, $data, $msg) = Ssxx3dBetService::postToSite($betRowId=4183);p([$code, $data, $msg]);
            echo $qihao;
        } catch (\Exception $e) {
            p($e->getMessage());
        }

        return [];
    }

    /**
     * 测试5x
     * /www/server/php/74/bin/php /www/wwwroot/lottery_xl/yii test/index/dw1
     * @return void
     **/
    public function actionDw1($id=''){
        try {
            $plan = UserSysPlans::findOne(18500);
            $codes = DynamicFilterService::getFilterDynamic2($plan, []);p(count($codes));
            $next_qihao = KjDataGet::getNextQihaoByQihao($qihao = '250723144', $lottery_type = 24); p($next_qihao);
            $result = StaticService::isCanOpStatic($lottery_type=23, $qihao='', 'opSystemBetPlans');p(['result'=>$result]);
            $r = \backend\service\Lucky5\Lucky5Service::getBetNumsPer($uid=50); p($r);
            $plan = UserSysPlans::findOne(	17255);
            $codes = BetService::getCodes($plan->tz_type, $plan->buy_type, $plan->hz_Arr, $plan->id);p(count(explode('@', $codes)));
            $plan = UserSysPlans::findOne(16879);
            $codes = \backend\service\NumService::getBeforeKjCodesDynamic($plan);p(count($codes));
            // 2611213 //2611225
            //$userInfo = Lucky5Service::userInfo(50, 9);p($userInfo);
            $rst = Lucky5Service::cancelOrder($bet_id='2611225', $tz_system_id=9);
            p($rst);
            $plan = UserSysPlans::findOne(14351);
            $codes = DynamicFilterService::getFilterDynamic2($plan, []);p(count($codes));
            $where = ['uid'=>25, 'plan_id'=>'10892', 'qihao'=>'20241016200', 'lottery_type'=>8];
            $r = BetErrorPlansTask::find()->where($where)->orderBy(['status'=>SORT_ASC])
                ->orderBy('id DESC')->addOrderBy(['id'=>SORT_DESC])->one()->toArray();
            p($r);
            $rst = SscDataService::getLastIndexId(8, 7); p($rst);
            $r = OperatePlanService::operatePlans21($plan, $current_kj_qihao='20241011255'); p($r);
            $areaProfits = SscDataService::getPlanProfits($plan, ['>=', 'qihao', '20241011255'], 1); # 计划当前区间利润
            p($areaProfits);
            $codes = BetService::getCodesByPlan($plan);p(count(explode('@', $codes)));
            $AUTH_ACCESS_TOKENS = TzSystemUsersService::getAuthAccessTokens(2);p($AUTH_ACCESS_TOKENS);
            $rst = OperatePlanService::initPlanPerDate($lottery_type=8, 1);
            p($rst);


            $miss = StaticsQxMissService::getCodeTypeHistoryMiss('type_log', $lottery_type=8, $static_nums=470); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
            p($miss);
            //$data = Aozhou::getLucky5($type='json', $is_auto=2);p($data);

            p($codes);
            $r = \backend\service\BetService::getTypeNameByTzType($tz_type=25);p($r);
            $historyKjData = NumCodeService::getKjData($qihao='20240224120', $lottery_type=8);p($historyKjData);
            $MessageService = new EYunMessageOperateService($user_id=21);
            $rst = $MessageService->receive(['content'=>'体组六组三 1拖2345、23456 各10元', 'fromUser'=>'wxid_875i1kgd38x122']); p($rst);
        }catch (\Exception $e){
            p($e->getMessage().$e->getFile().'_'.$e->getLine());
        }
        $wcId = WechatUserService::getCurrentRobotWechat($user_id=22, $robot_wechat='wxid_v44jhsu1852p22');
        $rst = WechatUserService::syncWechatFriends($user_id=22);p($rst);
        $next_qihao = KjDataGet::getNextQihaoByQihao($qihao='20231229288', $lottery_type=8);
        p($next_qihao);
        /**
         * 确认订单：
         * 1、全部确认（除撤单的），管理员输入：全部代购
         * 2、指定单个订单确认，管理员输入：单号+已代购、已代购+单号
         *
         * 撤单：
         * 用户：单号+撤、撤+单号
         * 管理员：单号+撤、撤+单号
         */
        $MessageService = new EYunMessageOperateService($user_id=21);
        $data = ["toUser"=>"wxid_875i1kgd38x122", 'targetUser'=>'wxid_875i1kgd38x122','text'=>'全部代购',];
        $rst = $MessageService->receiveFromMyself($data);p($rst);
        $lottery_types = StaticService::getLotteryTypes();p($lottery_types);
    }
}
