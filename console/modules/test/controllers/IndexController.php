<?php
namespace console\modules\test\controllers;

use backend\models\searchs\wechat\Bets;
use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use backend\service\BetService;
use backend\service\clients\AgentClientsService;
use backend\service\numbers\NumCodeService;
use backend\service\NumService;
use backend\service\SscDataService;
use backend\service\statics\statics_3d\Statics3dUserDataService;
use backend\service\StaticService;
use common\kj\qxc\QxcTcw;
use common\service\cache\CacheKeyService;
use common\service\helpers\ThirdD;
use common\service\thirdD\match\MatchCodeService;
use common\service\thirdD\MethodMatchService;
use common\service\thirdD\OperateLotteryService;
use common\service\thirdD\sx\Ssxx3dBetService;
use common\service\thirdD\sx\Sx3dUserService;
use common\service\thirdD\ThirdDTypeService;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\service\wechat\WechatUserService;
use common\tools\KjDataGet;
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
            $r = \Yii::$app->db->getSchema()->refreshTableSchema('{{%lottery_data_deal_status}}'); p($r);
            //$data = \common\service\ssc\QihaoService::getKjQiHao(8);p($data);
            $plan = UserSysPlans::findOne(7995);
            $data = QxcTcw::getOfficialCode($type='json', $is_auto=1, $lottery_type=27);p($data);

            $data = QxcTcw::getNineNineLottery($type='json', $is_auto=2, $lottery_type=27);
            $Thirdd = new \common\kj\ssc\Thirdd();
            $data = $Thirdd->getFuCai3d($type='json', 2);p($data);
            $rst = KjDataGet::grabOneLotteryKjData($lottery_type=26);p($rst); # 开奖
            $kjData = \common\kj\ssc\Thirdd::getCurrentKjData($lottery_type=26, $current_qihao);
            p([$kjData, $current_qihao]);
            $kdCodes = \backend\service\NumService::getKuduCodes([2,5,8,7], $kd=3);p($kdCodes);
            $qihao = substr(QxcTcw::getNineNineQihao($lottery_type=26, 2), 2);p($qihao);# 期号
            $MessageService = new EYunMessageOperateService($user_id=22);
            $rst = $MessageService->searchUser(''); p($rst);
            $lottery_types = StaticService::getGrabDataLotteryTypes($useCache=0);
            p($lottery_types);
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
            $logData_str = '{"Status":1,"Data":{"PageSize":40,"PageIndex":1,"RecordCount":6782,"PageCount":170,"Rows":[{"log_member_quick_select_id":"33710399","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5553","bet_money":"1110.6","operation_content":"[四定位]，配数“[取]”：第3位：[02345]，固定合分除值：第[1]位选中，第[2]位选中，第[3]位选中，内容：[9]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，固定位置：第[1]位，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 12:01:53","time_value":"2024/2/24 12:01:53","operation_ip":"112.67.*.*","ip_value":"112.67.83.169","operation_ip_extension":"112.67.83.169","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33710005","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5580","bet_money":"1116","operation_content":"[四定位]，配数“[取]”：第3位：[02345]，固定合分除值：第[1]位选中，第[2]位选中，内容：[9]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，固定位置：第[1]位，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 11:59:06","time_value":"2024/2/24 11:59:06","operation_ip":"112.67.*.*","ip_value":"112.67.83.169","operation_ip_extension":"112.67.83.169","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33708657","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5580","bet_money":"1116","operation_content":"[四定位]，配数“[取]”：第3位：[02345]，固定合分除值：第[1]位选中，第[2]位选中，内容：[9]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，固定位置：第[1]位，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 11:51:32","time_value":"2024/2/24 11:51:32","operation_ip":"112.67.*.*","ip_value":"112.67.83.169","operation_ip_extension":"112.67.83.169","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33687500","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5580","bet_money":"1116","operation_content":"[四定位]，配数“[取]”：第3位：[02345]，固定合分除值：第[1]位选中，第[2]位选中，内容：[9]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，固定位置：第[1]位，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 03:58:06","time_value":"2024/2/24 3:58:06","operation_ip":"36.1.*.*","ip_value":"36.1.200.111","operation_ip_extension":"36.1.200.111","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33686869","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5580","bet_money":"1116","operation_content":"[四定位]，配数“[取]”：第3位：[02345]，固定合分除值：第[1]位选中，第[2]位选中，内容：[9]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，固定位置：第[1]位，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 03:53:36","time_value":"2024/2/24 3:53:36","operation_ip":"36.1.*.*","ip_value":"36.1.200.111","operation_ip_extension":"36.1.200.111","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33686074","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5559","bet_money":"1111.8","operation_content":"[四定位]，配数“[取]”：第3位：[02345]，固定合分除值：第[1]位选中，第[2]位选中，内容：[8]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，固定位置：第[1]位，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 03:47:44","time_value":"2024/2/24 3:47:44","operation_ip":"36.1.*.*","ip_value":"36.1.200.111","operation_ip_extension":"36.1.200.111","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33685455","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5607","bet_money":"560.7","operation_content":"[四定位]，配数“[取]”：第3位：[02345]，固定合分除值：第[1]位选中，第[2]位选中，内容：[3]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，固定位置：第[1]位，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 03:43:34","time_value":"2024/2/24 3:43:34","operation_ip":"36.1.*.*","ip_value":"36.1.200.111","operation_ip_extension":"36.1.200.111","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33684624","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5586","bet_money":"558.6","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[6]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 03:38:15","time_value":"2024/2/24 3:38:15","operation_ip":"36.1.*.*","ip_value":"36.1.200.111","operation_ip_extension":"36.1.200.111","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33682837","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5586","bet_money":"1117.2","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[6]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 03:27:29","time_value":"2024/2/24 3:27:29","operation_ip":"36.1.*.*","ip_value":"36.1.200.111","operation_ip_extension":"36.1.200.111","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33681330","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5586","bet_money":"1117.2","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[6]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 03:19:14","time_value":"2024/2/24 3:19:14","operation_ip":"36.1.*.*","ip_value":"36.1.200.111","operation_ip_extension":"36.1.200.111","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33678441","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5586","bet_money":"558.6","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[6]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 03:03:45","time_value":"2024/2/24 3:03:45","operation_ip":"36.1.*.*","ip_value":"36.1.200.111","operation_ip_extension":"36.1.200.111","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33677100","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5586","bet_money":"1117.2","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[6]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 02:57:29","time_value":"2024/2/24 2:57:29","operation_ip":"36.1.*.*","ip_value":"36.1.200.111","operation_ip_extension":"36.1.200.111","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33675857","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5586","bet_money":"2234.4","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[6]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 02:51:42","time_value":"2024/2/24 2:51:42","operation_ip":"36.1.*.*","ip_value":"36.1.200.111","operation_ip_extension":"36.1.200.111","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33674735","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5586","bet_money":"2234.4","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[6]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 02:46:33","time_value":"2024/2/24 2:46:33","operation_ip":"36.1.*.*","ip_value":"36.1.200.111","operation_ip_extension":"36.1.200.111","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33673847","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5586","bet_money":"2234.4","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[6]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 02:42:03","time_value":"2024/2/24 2:42:03","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33673282","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5460","bet_money":"1092","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[8]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 02:38:58","time_value":"2024/2/24 2:38:58","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33670581","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5584","bet_money":"2233.6","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[1]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 02:26:52","time_value":"2024/2/24 2:26:52","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33668455","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5560","bet_money":"2224","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[5]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 02:16:56","time_value":"2024/2/24 2:16:56","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33667471","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5506","bet_money":"1101.2","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[3]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 02:12:16","time_value":"2024/2/24 2:12:16","operation_ip":"112.66.*.*","ip_value":"112.66.17.245","operation_ip_extension":"112.66.17.245","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33666118","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5506","bet_money":"1101.2","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[3]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 02:06:40","time_value":"2024/2/24 2:06:40","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33665559","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5460","bet_money":"2184","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[8]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 02:03:30","time_value":"2024/2/24 2:03:30","operation_ip":"112.66.*.*","ip_value":"112.66.18.56","operation_ip_extension":"112.66.18.56","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33664359","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5460","bet_money":"2184","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[3]位选中，内容：[8]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 01:58:21","time_value":"2024/2/24 1:58:21","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33662463","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5460","bet_money":"2184","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[3]位选中，内容：[8]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 01:51:17","time_value":"2024/2/24 1:51:17","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33661696","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5460","bet_money":"1092","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[3]位选中，内容：[8]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 01:47:34","time_value":"2024/2/24 1:47:34","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33659991","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5584","bet_money":"558.4","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[3]位选中，内容：[1]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 01:40:53","time_value":"2024/2/24 1:40:53","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33659206","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"4820","bet_money":"482","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[3]位选中，内容：[1]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，二兄弟“[取]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 01:36:39","time_value":"2024/2/24 1:36:39","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33657940","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"4930","bet_money":"493","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[3]位选中，内容：[8]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，二兄弟“[取]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 01:31:19","time_value":"2024/2/24 1:31:19","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33656933","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5460","bet_money":"1092","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[3]位选中，内容：[8]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 01:26:35","time_value":"2024/2/24 1:26:35","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33655759","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"4827","bet_money":"965.4","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，第[3]位选中，内容：[8]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，二兄弟“[取]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 01:21:31","time_value":"2024/2/24 1:21:31","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33654442","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"4888","bet_money":"977.6","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，第[3]位选中，内容：[3]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，二兄弟“[取]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 01:16:00","time_value":"2024/2/24 1:16:00","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33653585","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"4837","bet_money":"483.7","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，第[3]位选中，内容：[9]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，二兄弟“[取]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 01:11:41","time_value":"2024/2/24 1:11:41","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33652987","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5470","bet_money":"1094","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，第[3]位选中，内容：[9]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 01:08:35","time_value":"2024/2/24 1:08:35","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33651947","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5470","bet_money":"1094","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，第[3]位选中，内容：[9]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 01:03:41","time_value":"2024/2/24 1:03:41","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33650274","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"4837","bet_money":"483.7","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，第[3]位选中，内容：[9]；，不定合分值(两数合)：[01234]，合分值范围：[8-28]，包含“[取]”数：[01469]，二兄弟“[取]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 00:56:32","time_value":"2024/2/24 0:56:32","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33649053","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"4810","bet_money":"481","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[9]；，不定合分值(两数合)：[0248]，合分值范围：[8-28]，包含“[取]”数：[01469]，二兄弟“[取]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 00:51:11","time_value":"2024/2/24 0:51:11","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33648314","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"4810","bet_money":"481","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[1]位选中，第[2]位选中，内容：[9]；，不定合分值(两数合)：[0248]，合分值范围：[8-28]，包含“[取]”数：[01469]，二兄弟“[取]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 00:47:22","time_value":"2024/2/24 0:47:22","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33647587","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"4810","bet_money":"481","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[3]位选中，第[4]位选中，内容：[9]；，不定合分值(两数合)：[0248]，合分值范围：[8-28]，包含“[取]”数：[01469]，二兄弟“[取]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 00:43:53","time_value":"2024/2/24 0:43:53","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33643995","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"4810","bet_money":"962","operation_content":"[四定位]，配数“[取]”：第2位：[24789]，第3位：[01356]，固定合分除值：第[3]位选中，第[4]位选中，内容：[9]；，不定合分值(两数合)：[0248]，合分值范围：[8-28]，包含“[取]”数：[01469]，二兄弟“[取]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 00:28:20","time_value":"2024/2/24 0:28:20","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33639850","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"4906","bet_money":"981.2","operation_content":"[四定位]，配数“[取]”：第2位：[023568]，第3位：[023568]，固定合分除值：第[1]位选中，第[2]位选中，内容：[2]；，不定合分值(两数合)：[0248]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-24 00:11:00","time_value":"2024/2/24 0:11:00","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"},{"log_member_quick_select_id":"33636236","member_id":"5356","account":"aa123123A","nickname":"","fix_num":"40","bet_count":"5011","bet_money":"501.1","operation_content":"[四定位]，配数“[取]”：第2位：[023568]，第3位：[023568]，固定合分除值：第[1]位选中，第[2]位选中，内容：[3]；，不定合分值(两数合)：[0248]，合分值范围：[8-28]，包含“[取]”数：[01469]，三兄弟“[除]”操作，四兄弟“[除]”操作","operation_datetime":"02-23 23:55:57","time_value":"2024/2/23 23:55:57","operation_ip":"36.101.*.*","ip_value":"36.101.55.17","operation_ip_extension":"36.101.55.17","is_package":"0","log_type":"102"}],"Extra":[]}}';
            $rst = AgentClientsService::syncMemberBetLogs(Json::decode($logData_str), $access_token='eb70910c92f134bd54a3837d978f055b', $from_type='api', $from='kuaixuan', $lottery_type=8);
            p($rst);
            $historyKjData = NumCodeService::getKjData($qihao='20240224120', $lottery_type=8);p($historyKjData);
            $plan = UserSysPlans::findOne(8084);
            $codes = BetService::getCodes($plan->tz_type, $plan->buy_type, $plan->hz_Arr, $plan->id);p(count(explode('@', $codes)));
            $codes = \backend\service\NumService::getBeforeKjCodesDynamic($plan);p(count($codes));
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
