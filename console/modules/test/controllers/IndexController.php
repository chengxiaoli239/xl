<?php
namespace console\modules\test\controllers;

use backend\models\BettingRecords;
use backend\models\DataDealStatus;
use backend\models\open\PlatformGroup;
use backend\models\open\PlatformRobot;
use backend\models\searchs\wechat\Bets;
use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use backend\models\VBets;
use backend\service\agent\AgentService;
use backend\service\agent\AgentUsersService;
use backend\service\BetService;
use backend\service\clients\AgentClientsService;
use backend\service\HN0898Service;
use backend\service\numbers\NumCodeService;
use backend\service\NumService;
use backend\service\SscDataService;
use backend\service\statics\statics_3d\Statics3dUserDataService;
use backend\service\statics\yl\OneNumYl;
use backend\service\StaticService;
use common\helpers\lottery\LotteryBet;
use common\helpers\LotteryType;
use common\kj\qxc\QxcTcw;
use common\kj\ssc\Aozhou;
use common\service\cache\CacheKeyService;
use common\service\CommonService;
use common\service\helpers\ThirdD;
use common\service\lottery\aozhou5\AoZhou5BetService;
use common\service\lottery\aozhou5\jobs\AoZhou5BetJobs;
use common\service\lottery\LotteryTypeService;
use common\service\open\ActionBaseService;
use common\service\open\actions\PlatformRobotService;
use common\service\open\telegram\AoZhouKjService;
use common\service\ssc\QihaoService;
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
use common\tools\Util;
use DateTime;
use Yii;
use yii\base\Controller;
use yii\helpers\Json;

class IndexController extends Controller
{
    /**
     * @desc 测试
     * /www/server/php/74/bin/php /www/wwwroot/lottery_xl/yii test/index/dw
     */
    public function actionDw(): array
    {
        $dateString = '20231114002';
        try {
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
            $logData = Json::decode('{"log_member_quick_select_id":"24483351","member_id":"21821","account":"Ldl158158","nickname":"","fix_num":"50","bet_count":"49","bet_money":"2940","operation_content":"[五位二定]，定位置“[取]”：个=[2345678]，五=[2341678]","operation_datetime":"05-29 16:51:34","time_value":"2024/5/29 16:51:34","operation_ip":"36.1.*.*","ip_value":"36.1.146.170","operation_ip_extension":"36.1.146.170","is_package":"0","log_type":"102"}');
            list($code, $qihao) = AgentClientsService::operateOneBetLog($logData, $access_token='eb70910c92f134bd54a3837d978f055b');
            p([$code, $qihao, $logData]);
            $tzSystemUser = TzSystemsUsers::findOne(68);
            $userInfo = (new ActionBaseService())->login($tzSystemUser, $isAuto=1);p($userInfo);
            //$rst = BetService::pushTasksBetRst($plan_id=7953, $qihao=20240203238, ['bet_rst'=>1,'time_consume'=>2], $access_token='g5843e29ac8dd191e894c7dcea547792', $lottery_type=LotteryType::LUCKY_5); p($rst);
            $rst = BetService::pushTasksBetRst($plan_id=AoZhou5BetService::TEST_BET_ID, $qihao=51108412, ['bet_rst'=>1,'task_status'=>2,'time_consume'=>2], $access_token='g5843e29ac8dd191e894c7dcea547792', $lottery_type=LotteryType::AZ_LUCKY_5);
            p($rst);
            $betRow['qihao'] = DataDealStatus::find()->select('next_qihao')->where(['lottery_type'=>28])->limit(1)->orderBy(['id'=>SORT_DESC])->scalar();
            p($betRow['qihao']);
            $rst = AoZhou5BetService::getBetTasks($id=AoZhou5BetService::TEST_BET_ID);p($rst);
            $r = \Yii::$app->db->getSchema()->refreshTableSchema('{{%user}}'); p($r);
            foreach ([8] as $lotteryType){
                //$r = (new LotteryBet())->checkLotteryStatus($lotteryType);//p($r);
                $r = (new LotteryBet())->checkLotteryStatus($lotteryType);//p($r);
                $rst[$lotteryType] = $r;
            }
            p(['rst'=>$rst]);
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
            (new AoZhouKjService())->operateSendKjData();p('dddd');
            $groups = PlatformGroup::getGroups($userId=21);p($groups);
            $model = PlatformRobot::findOne(['platform_robot_id'=>'6744049574']);
            $rst = PlatformRobotService::getUpdates($model); p($rst);# 添加之后立马获取群聊消息，记录群ID
            $r = OneNumYl::yl($lotteryType=8);p($r);
            $tzSystemUser = TzSystemsUsers::findOne(68);
            #$r = (new ActionBaseService())->login($tzSystemUser);p($r);
            $r = (new ActionBaseService())->getUserInfo($tzSystemUser);p($r);
            $rst = AgentUsersService::userFlowsCheck(['id'=>16791, 'status'=>1], 21, '管理员消息回复处理');p($rst);
            $rst = [];
            list($entertainedStatus, $grabStatus) = LotteryBet::isEntertained(LotteryType::LUCKY_5);p([$entertainedStatus, $grabStatus]);
            list($entertainedStatus, $grabStatus) = LotteryBet::isEntertained(LotteryType::AZ_LUCKY_5);p([$entertainedStatus, $grabStatus]);
            list($lotteryType, $lotteryName) = [LotteryType::AZ_LUCKY_5, LotteryType::TYPE_OPTIONS[LotteryType::AZ_LUCKY_5]];
            list($currentKjQiHao, $qiHao) = QihaoService::getKjQiHao($lotteryType);
            p([$currentKjQiHao, $qiHao]);
            $params = Json::decode('{"user_id":21,"business_id":6830978835,"token":"6902259997:AAEsg51soXNS1MYPdmHNnpj0YWBo6J3aeyo","update_id":840228241,"message":{"message_id":27,"from":{"id":6830978835,"is_bot":false,"first_name":"破局","last_name":"Mr","language_code":"zh-hans"},"chat":{"id":6830978835,"first_name":"破局","last_name":"Mr","type":"private"},"date":1709564365,"text":"1正/20"}}');
            $params = Json::decode('{"update_id":840228414,"message":{"message_id":776,"from":{"id":6830978835,"is_bot":false,"first_name":"破局","last_name":"Mr","language_code":"zh-hans"},"chat":{"id":6830978835,"first_name":"破局","last_name":"Mr","type":"private"},"date":1712280539,"text":"查"},"business_id":6830978835,"user_id":"21","token":"6902259997:AAEsg51soXNS1MYPdmHNnpj0YWBo6J3aeyo","queue_open":true}');
            $d = \common\service\jobs\telegram\MessageReceiveJobs::handle($params);
            $rst = KjDataGet::grabOneLotteryKjData($lottery_type=28);p($rst); # 开奖
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
            list($lastQihao, $lastIndexId, $lastId, $nextQihao) = SscDataService::getKjDataLastIndexId($lottery_type=8);
            p([$lastQihao, $lastIndexId, $lastId, $nextQihao]);
            //$rst['updateDsYL'] = SscDataService::updateSdHzYl($lottery_type = 17); p($rst);// 更新和值遗漏
            $r = push_queue(\common\service\jobs\kj_data\OperateBetPlans::class, ['lottery_type'=>1, 'lottery_name'=>'七星彩', 'business_id'=>'2024016', 'ignore'=>1]);
            $r = push_queue(\common\service\jobs\kj_data\OperateBetPlans::class, ['lottery_type'=>17, 'lottery_name'=>'排列五', 'business_id'=>'2024038', 'ignore'=>1]);
            p($r);
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

            ########### 组六、组三 #############
            $text = '福一元组三二元组六 369'; # --
            $text = '福组三一元组六二元 369'; # --
            $text = '福1元组三2元组六 369';
            $text = '福直1元组2元 369';

            $text = '福1倍组三2倍组六 369';
            $text = '福2倍组六1倍组三 369';
            $text = '福一倍组三二倍组六 369'; # --
            $text = '福二倍组六一倍组三 369'; # --

            $text = '福一元组三二元组六 369'; # --
            $text = '福组三一元组六二元 369'; # --
            $text = '福一倍组三二倍组六 369'; # --
            $text = '福二倍组六一倍组三 369'; # --
            ########### 组六、组三 #############

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
    public function actionDw1(){
        try {
            //$data = Aozhou::getLucky5($type='json', $is_auto=2);p($data);

            $plan = UserSysPlans::findOne(8387);
            $codes = \backend\service\NumService::getBeforeKjCodesDynamic($plan, $filter_dynamic_types=[181]);p(count($codes));
            $r = \backend\service\BetService::getTypeNameByTzType($tz_type=25);p($r);
            $codes = BetService::getCodes($plan->tz_type, $plan->buy_type, $plan->hz_Arr, $plan->id);p(count(explode('@', $codes)));
            $historyKjData = NumCodeService::getKjData($qihao='20240224120', $lottery_type=8);p($historyKjData);
            $MessageService = new EYunMessageOperateService($user_id=21);
            $rst = $MessageService->receive(['content'=>'体组六组三 1拖2345、23456 各10元', 'fromUser'=>'wxid_875i1kgd38x122']); p($rst);
        }catch (\Exception $e){
            p($e->getMessage());
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
