<?php
/**
 * Created by PhpStorm.
 * User:wangyegao
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\test\controllers;

use backend\models\SscKjData;
use backend\service\BetService;
use backend\service\NumService;
use backend\service\SevenService;
use backend\service\StaticService;
use backend\service\TestService;
use backend\service\UserCustomPlansService;
use backend\service\UserSysPlansService;
use backend\service\WxService;
use backend\service\XlService;
use backend\tools\Tools;
use common\kj\BaseKj;
use common\kj\cqssc\CqsscKcw;
use common\kj\cqssc\CqsscSevenDay;
use common\models\AdminModel;
use common\service\CommonService;
use common\tools\KjDataGet;
use backend\service\BaseNumService;
use backend\service\BaseService;
use backend\service\CurlService;
use backend\service\FormDataService;
use backend\service\HN0898Service;
use backend\service\OpKjService;
use backend\models\UserFollowData;
use backend\service\RemoteHtmlService;
use backend\models\BettingRecords;
use backend\models\User;
use Yii;
use yii\web\Controller;
use backend\service\SscDataService;
use backend\service\TzService;


class IndexController extends Controller
{

    private static function _init()
    {
        header("Content-type: text/html; charset=utf-8");
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    }

    public function actionGetmoney(){
        p(rand());
        $cookie['ASP.NET_SessionId'] = 'woh4v445d2kzkg55wdc3il55';
        p($cookie);
    }

   /**
     * @description 0-9选3个数，三字现
     */
    public function actionTestNum()
    {
        $insertData = [];
        $count = 0;
        $field = ['code'];
        for ($i = 0; $i <= 9; $i++) {
            for ($m = $i+1; $m <= 9; $m++) {
                if($m == $i) continue;
                for ($n = $m+1; $n <= 9; $n++) {
                    if ($m == $n || $n == $i) continue;
                    $insertData[] = [$i.$m.$n];
                    $count++;
                }
            }
        }
        $totalnum = \Yii::$app->db->createCommand()->batchInsert("{{%three_num}}",$field,$insertData)->execute();


        echo '<br>======'.$count.'======'.$totalnum.'=========';
    }

    public function actionGenNum(){
        $field = ['code'];
        $insertData[] = ['123'];
        $totalnum = \Yii::$app->db->createCommand()->batchInsert("{{%three_num}}",$field,$insertData)->execute();
        $nums = [1,2,3,4,5,6,7];
        sort($nums);
        foreach ($nums as $key1=>$num1){
            unset($nums[$key1]);
            foreach ($nums as $key2=>$num2){
                if($num1 >= $num2) continue;
                foreach ($nums as $key3=>$num3){
                    if($num1 >= $num3 OR $num3 <= $num2) continue;
                    $datas[] = $num1.$num2.$num3;
                }
            }
        }
        p($datas);
    }

    public function actionDw(){
        $rst = SscDataService::insertCodeType();p($rst);
        $rst = SevenService::login(12, 3);p($rst); # 7时登录
        $rst = SevenService::synBalance(15);p($rst);

        $rst = StaticService::staticAll2NumsYl();p($rst); # 统计所有二字现遗漏
        $rst = SscDataService::updateCodeTypeYL();p($rst); # 号码类型遗漏
        $rst = NumService::getCodesArise(['003']);p($rst); //2+3+1+2+2
        $rst = StaticService::staticAll2NumsYl();p($rst); # 统计所有二字现遗漏
        $rst = StaticService::static4dMonthsProfits();p($rst); # 每月四定单双利润统计，有点慢，四定类型详见：StaticService::$typeArr
        $domain = BaseKj::getApiHost(8);p($domain);
        $rst['updateDsYL'] = SscDataService::updateDsYL($lottery_type=5); p($rst);// 单双遗漏
        $rst['updateDsYL'] = SscDataService::updateSdHzYl(); p($rst);// 单双遗漏
        $miss = SscDataService::getCodeTypeHistoryMiss('type_2,type_2b', $lottery_type = 5, $static_nums = 80);p($miss); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
        p('xxx');
        $rst['updateDs'] = SscDataService::updateDsData($lottery_type=5); // 每期开奖遗漏
        $rst = TzService::opSystemBetPlans(5);p($rst);
        $rst = KjDataGet::getNextQihaoByQihao('190525039',5);p($rst);
        $rst['updateDsYL'] = SscDataService::updateDsYL(); // 单双遗漏
        $beforeQihao = KjDataGet::getBeforeQihaoByQihao('190525001');p($beforeQihao);
        $rst = NumService::getCodesArise(['9377']);p(count($rst));
        $arr = ['type_2b'=>1, 'hz'=>[11,12,13,14,15,16,24]]; p(json_encode($arr));
        $rst = NumService::getCodesKuaiXuan(['type_2b'=>1, 'hz'=>[11,24], 'hz_v'=>1]);p($rst);
        $rst = NumService::getCodesKuaiXuan(['type_2'=>1, 'hz'=>[8,28]]);p($rst);
        $qihao = HN0898Service::getQihao($lottery_type = 6);p($qihao);
        $rst = TzService::insertSscDataTime(6); p($rst);
        $rst = StaticService::allHzStaticProfitsPerdate();p($rst);# 循环计算每天每个和值利润统计
        $rst[] = StaticService::static4dPerDateProfits();p($rst); # 每天四定利润统计，四定类型详见：StaticService::$typeArr
        $rst = StaticService::staticSdHzProfitsPerdate(); p($rst); # 每天每个和值利润统计
        $rst = StaticService::staticSDHzPerDateProfits(); p($rst);
        $rst[] = StaticService::opAllStaticProfits(); p($rst);# 利润统计
        $account = AdminModel::findOne(11)['username'];p($account);
        $rst = StaticService::calculate2bProfits($lottery_type = DEFAULT_LOTTERY_TYPE, $start_date = '2019-05-01', $end_date = '2019-05-15'); p($rst);
        for($i=0;$i<100;$i++){
            $rst['update3NumData'] = SscDataService::update3NumData($lottery_type=5);
        }
        p($rst); // 每期开奖遗漏
        $rst = BetService::tzByPlanId(7, 1);p($rst); # 投注
        $qihao = HN0898Service::getQihao(5);
        $rst = BetService::getBetCacheTime($lottery_type = 5, $qihao); p($rst);# 投注之后缓存时间
        $qihao = HN0898Service::getQihao(5);p($qihao);
        $rst['update3NumData'] = SscDataService::update3NumData(5);p($rst); // 每期开奖遗漏
        $rst = NumService::getCodesArise(['1234589']);p(count($rst));

        $rst = NumService::filterLaterCodesAnd2bcode(5, $qihao = '190516056');p($rst);
        $rst = NumService::getRecentlyCodes(5);p($rst);
        $rst = UserSysPlansService::userSysPlanChange(2);p($rst);
        $rst = StaticService::getYlByCodes('02468,13579,X,X', 2, 18);p($rst);
        $rst = StaticService::opAllStaticProfits();p($rst);
        $rst['updateDs'] = SscDataService::updateDsData(3); p($rst);// 每期开奖遗漏
        $rst = HN0898Service::insertDsYl();p($rst);
        $rst = BetService::bet(); p($rst);// 用户新计划投注，可正买可反买
        $rst = OpKjService::opSscKjData(2); p($rst); # 处理投注数据
        $rst = TzService::tz(); p($rst);// 计划投注
        $rst = KjDataGet::grabOne();p($rst);
        $captchaCodeRst = Tools::getCaptchaCode(10, 5, '2x2tdrnawlpbli554jlsuf2c');p($captchaCodeRst); # 真实调用验证码接口，收费
        $rst = XlService::login(10, 5);p($rst); # 7时登录
        $rst = HN0898Service::login(10, 5);p($rst); # 7时登录
        $rst = XlService::formCodesStyle('13579,X,X,X@02468,X,X,X', 4); p($rst); # 格式化希腊号码
        $rst = XlService::formCodesStyle('13579,,13579,,@13579,,13579,,', 1); p($rst); # 格式化希腊号码
        $rst = XlService::getQihaoInfo(10, 5);p($rst);
        $rst = HN0898Service::getQihao(2);p($rst);
        $rst = KjDataGet::getBeforeQihaoByQihao('191231960',2);p($rst);
        $bettingRecords = BettingRecords::find()->alias('bet')->where(['bet.status'=>0])->distinct('qihao')->orderBy('bet.qihao ASC')->limit(20)->all();p($bettingRecords);
        $rst = CqsscKcw::getLotteryNoXl();p($rst);
        $rst = HN0898Service::getQihao(); p($rst);
        //$rst = NumService::getCodesArise(['289','125','046','456','589','467']);p($rst); //2+3+1+2+2
        $rst = StaticService::get2NumsYlRecords('12');p($rst);
        $rst = StaticService::static2NumsYl();p($rst);
        //$rst = NumService::getCodesArise_bak(['12345']);p($rst);
        $rst = StaticService::staticKj3NumCounts();p($rst);
        $arr = [['reach_val'=>100, 'reduce_val'=>10], ['reach_val'=>300, 'reduce_val'=>50]];p(json_encode($arr));
        for ($i=0;$i<5; $i++){
            $rst = SscDataService::updateDsData();//p($rst); // 每期开奖单双
        }
        $codes = BetService::getCodes(2, 3, 20, 1, 0.1, 1, '35,36');p($codes);
        $rst = SevenService::sscIndex(3, 3);p($rst); # 用户信息
        $rst = BetService::userSysPlansTzNow(81, 3); p($rst);
        $rst = SevenService::getSn(3, 3);p($rst); # 用户信息
        $rst = HN0898Service::getRemoteHzRecords(3, 2);p($rst);
        $rst = CqsscSevenDay::getLotteryNo(); p($rst);
        $rst = StaticService::staticHzPerDateProfits('2019-04-09'); p($rst);
        $rst = BetService::getCodes(1, 3, 22, 1, 0.1, 1, [1221,1222,2111]);p($rst);
        $rst = StaticService::getSameCodes('1221', 1);p($rst);
        $rst = SevenService::synBalance(7); p($rst); # 同步余额
        //p(base64_decode('1324%E5%85%A8%E5%80%92%E5%9B%9B%E5%AE%9A%E5%90%840.1'));
        $rst = BetService::getPlansAllCodesType2(3, 4); p($rst);
        $rst = SevenService::userInfo(2, 3);p($rst);

        p([base64_decode('OTA1Mjg2MTM1MzI3Ng=='), base64_decode( 'MjI5OTE2MTM0MTQ2MQ==') ,base64_decode( 'MjA4ODY2MTM1MzI4Nw==')]);
        //$rst = HN0898Service::getTzList(3, 2);p($rst);
        $rst = NumService::get2bCodeArr();p($rst);
        $rst = StaticService::static4DHzProfits('2019-03-01','2019-03-29', 6); p($rst);
        $rst = NumService::getSystemTzHz(6, '190401023', 1); p($rst);
        $rst = [];
        for ($i = 190329001; $i<=190329019; $i++){
            $rst[$i] = NumService::getRemoveCodes($i,2000);
        }
        p($rst);
        $rst = BetService::getPlansAllCodesType1(3, 14); p($rst);
        $rst = BetService::getHzCodes(20, '25,26');p($rst);
        $rst = StaticService::staticSDPerDateProfits(date('Y-m-d'));p($rst);
        $rst = StaticService::static4DdsLastTime();p($rst);
        $rst = StaticService::opStaticProfits();p($rst);
        $post = \Yii::$app->request->post();
        $rst = SscDataService::getSDYL();p($rst);
        $rst = SscDataService::countZj();p($rst);
        $rst = SscDataService::countCodes();p($rst);
        $rst = UserCustomPlansService::insertSDPlans(); p($rst);
        //$rst = StaticService::allMonthStaticProfits();p($rst); # 利润统计
        $rst = SscDataService::insert4dDsZHData();p($rst);
        $m = \Yii::$app->cache;
        $qihao = HN0898Service::getQihao();
        $mkey = \Yii::$app->params['TZ_SWITCH_SIMULATE_KEY'].'_'.$qihao;
        $r = $m->set($mkey, 1, 10*60);
        //$rst = StaticService::staticSDProfits();p($rst); # 利润统计
        $rst = StaticService::staticProfits($playway = 3, 3600 * 3, 0);p($rst);
        $rst = OpKjService::opKjData4('01234,56789,56789,56789@01234,45678,56789,56789','3,4,5,7');p($rst);
        $rst = WxService::sendMsg();p($rst); # 群发微信消息
        $rst = CqsscKcw::getLotteryNo();p($rst);
        $rst = HN0898Service::getQihao();p($rst);
        $rst = HN0898Service::getCurrentQihao();p($rst);
        $rst = HN0898Service::synBalance(1);p($rst);
        $rst = SscDataService::calcDsProfit();p($rst); // 单双遗漏计算
        $rst = TzService::tz();p($rst); // 计划投注
        $rst = SscDataService::calTzTotalMoney('02468,X,13579,13579', 0.1, 2); p($rst);
        $rst = UserCustomPlansService::joinDs3DwPlans();p($rst);
        $rst = CommonService::getAwardNumberByQihao('181106022'); p($rst);
        $rst = SscDataService::getSscKjData0898('181106021');p($rst); // 每期开奖遗漏
        $m = \Yii::$app->cache;
        $mkey = 'TZ_SWITCH_STATUS_181029073';
        $rst = TzService::opSystemBetPlans();p($rst); // 定制化投注计划
        $rst = $m->get($mkey); p($rst);
        $rst = UserCustomPlansService::joinDs3DwPlans();p($rst); // 用户加入三字定单双计划
        $rst = SscDataService::calcDsProfit();p($rst); // 所有遗漏中每组单双遗漏次数计算
        $qihao = HN0898Service::getQihao();p($qihao);
        $qihao = '180922014';
        $date = '2018-'.substr($qihao,2,2).'-'.substr($qihao,4,2); p($date);
        $rst = TestService::getWeek257('2017');p($rst);
        $rst = TzService::getCustomPlansTzStatus(12);p($rst);   // 获取投注状态
        $rst = TzService::opPreUserFollowData(3);p($rst);  // 预处理插入计划表的数据
        set_time_limit(0);
        //$flag = SscDataService::insertSscKjDataDs('180813023');p($flag);
        //$rst = SscDataService::update3NumData();p($rst); // 每期开奖三字现统计
        $start_time = time(true);
        //$rst = SscDataService::update3NumData();p($rst); // 每期开奖遗漏
        $rst = SscDataService::update3NumYL();$end_time = time(true); $time_consume = ($end_time-$start_time).'s';p([$rst,$time_consume]);
        $nums = [4,5,6,6];
        $rst = CommonService::get3x($nums);p($rst);
        $rst = CommonService::get3x($nums);p($rst);
        $rst = SscDataService::insertSscKjDataDs('180808115');p($rst);
        $interval = 20;
        $rst[$interval] = SscDataService::dsYLStatic($interval);p($rst);
        $zuHes = [
            //[1,2],
            [1,3],
            [1,4],
            [2,3],
            [2,4],
            //[3,4],
        ];

        $data = [];
        //$rst = BaseNumService::startAndEndNumHeZhi();
        //$zuHeCode = BaseNumService::heZhiByPosition($qihao); // 某一期
        //$rst1 = BaseNumService::getHeZhiByPosition(20,2,4);
        if(true){
            $numsArr = [6,8,9];//[8,9,10,11,12,13];
            foreach ($zuHes as $key => $zuHe) {
                foreach ($numsArr as $k1=>$num){
                    $data[$key]['code_'.$num.'_'.$zuHe[0].'_'.$zuHe[1]] = BaseNumService::dwZuHe([$zuHe[0],$zuHe[1]],[$num]);
                }
                $data[$key][implode(',', $zuHe) . '位120期和值汇总'] = BaseNumService::getHeZhiByPositionTotal(120, $zuHe, $numsArr)['data']; // 在近xxx期期间和值汇总
                $data[$key]['70期' . implode(',', $zuHe) . '位遗漏'] = BaseNumService::getHeZhiYL($zuHe, $numsArr, 70)['data']; // 和值为8、9在200期里边遗漏期数
            }
        }
        //$heZhi_yilou = BaseNumService::getHeZhiYL([3,4],[11])['data']; p($heZhi_yilou); // 和值为8、9在200期里边遗漏期数
        //$bestTzCodes = OpKjService::getBestTzCodes('180604114','2,3','8,9,10'); p($bestTzCodes);
        //$bestTzCodes = OpKjService::changeTzCodes('180603053','gaozi2017',1, 1);
        //$UserFollowData = UserFollowData::findOne(['account'=>'gaozi2017','playway'=>1, 'is_simulate'=>0]);
        //$rst4 = BaseNumService::dwZuHe([1,3],[8]);p($rst4); // 某两个位置组合
        //$changeStatus = OpKjService::changeTzCodes('gaozi2017',1); p($changeStatus);
        //$rst4 = BaseNumService::dwZuHe([1,2],[11]);p($rst4); // 某两个位置组合 p($rst4);
        //$rst5 = KjDataGet::getSscGrupTime();
        //$rst6 = OpKjService::opSscKjData();
        //$HN0898Service = new HN0898Service('gaozi2017', 10, 0.1, 1); $rst7 = $HN0898Service->getSnidBySn('SSC18060701220111649660C9'); p($rst7); // 获取方案内容
        # 某个和值组合遗漏次数
        $rst7 = HN0898Service::dwHzZuHeYL([2,3], [8,9]); p($rst7);

        p($data);
    }





















}