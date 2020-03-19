<?php
/**
 * Created by PhpStorm.
 * User:wangyegao
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\test\controllers;

use backend\models\Admin;
use backend\models\SscKjData;
use backend\models\TzSystemsUsers;
use backend\service\BetService;
use backend\service\huiyuan\HuiYuanService5;
use backend\service\KuaiLe8Service;
use backend\service\Lucky5\LuckyBaseService;
use backend\service\NineNine\NineNineBaseService;
use backend\service\NumService;
use backend\service\qilin\QiLinBaseService;
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
use common\kj\ssc\Lucky5;
use common\kj\xjssc\XjSsc;
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

    /**
     * @desc 测试投注
     */
    public function actionTestBet(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $playway = $post['playway'];
        if(!$codes = $post['codes']){
            return ['status'=>300, 'msg'=>'投注号码不能为空'];
        }
        $single = $post['single'] ? $post['single'] : 0.1;
        $lottery_type = $post['lottery_type'] ? $post['lottery_type'] : 5;

        $rst = HN0898Service::postBet($uid = 2, $playway, $single, $codes, $lottery_type);

        return $rst;
    }

    public function actionDw(){
        $loginRst = BaseService::login($id = 44);p($loginRst);

        $time = BetService::getBetCacheTime($lottery_type=5, $qihao = '200319036'); p($time);# 投注之后缓存时间
        $rst = SscDataService::getPlanNextSingle(52, 0, $next_single_key, $lottery_type=5);p($rst);
        $qs = SscDataService::getLossQs(52);p($qs);
        $rst = BetService::isLogin($uid = 18, $tz_system_id = 9);d($rst);
        $rst = BetService::isLogin($uid = 20, $tz_system_id = 9);d($rst);

        $id = 38; $rst = BaseService::login($id);p($rst);
        $rst = SscDataService::insertCodeType2();p($rst);

        $testData = [
            '千12345百12345十67890',
            '头尾12345各1',
            '头百尾23456各0.1',
            '023468头尾各0.1',
            '千12百345四字定两数合45值范围15-35除个1234除双重除二兄弟各0.1',
            '千12百345四字定两数合45值范围15-35除个1234除双重除二兄弟',
            '千02百1十08,千35百2十48',
            '千13百2十89,千48百3十57',
            //'千0123456789百13579十02468,千0123456789百13579个02468',
            //'千0123456789百13579十02468,千0123456789百13579个02468,千0123456789百02468十13579,千0123456789十13579个02468,千0123456789百02468个13579,千0123456789十02468个13579',
            //'千0123456789百12345十67890,千0123456789百12345个67890,千0123456789百67890十12345,千0123456789十67890个12345,千0123456789百67890个12345,千0123456789十12345个67890',
        ];
        $rst = NumService::getCodesByDesc($testData[6]);p($rst);
        $rst = NumService::getCodesHzByDesc($testData[6]);p($rst);
        $rst = NumService::getCodesHzByDesc("千12百345四字定两数合45值范围15-35除个1234除双重除二兄弟");p($rst);
        $rst = NumService::getSingleByDesc("千12百345四字定两数合45值范围15-35除个1234除双重除二兄弟各0.1");p($rst);
        $rst = md5(md5('0n8J5h9sfkxofRI9wy010203'));p($rst);
        $qihao = HN0898Service::getQihao($lottery_type = 8);p(['即将开奖期号'=>$qihao, 'lottery_type'=>$lottery_type]);
        $rst = OpKjService::opKjData4('3,X,2,9@X,2,4,9@3,9,X,9@3,9,7,X','3,9,7,9,5');p($rst);
        $rst = OpKjService::opKjData4('3,X,X,X,5@X,X,X,9,5@X,X,7,X,5@3,9,X,X,X','3,9,7,9,5');p($rst);
        $rst = SscDataService::insertCodeType5();p($rst);
        $rst = KjDataGet::updateNullCode($num = 1000, $lottery_type = 5);p($rst);
        $rst = SscDataService::insertCodeType();p($rst);
        $str = '0,9,1,0';
        $rst = CommonService::isCodeType22b($str);p($rst);
        $miss = SscDataService::getSdHzYlHistoryMiss([32], $lottery_type = 5, 80000);p($miss);

        $miss = SscDataService::getSdHzYlHistoryMiss([1], $lottery_type = 5, 900000);p($miss);

        $rst = SscDataService::insertCodeType3();p($rst);
        $rst = CommonService::isCodeType2b('9,1,1,X');p($rst);
        $rst = BetService::tzByPlanId(24, 0);p($rst); # 投注
        $rst = SevenService::synBalance(29);p($rst);
        $rst = LuckyBaseService::synBalance($tz_system_users_id = 29); p($rst);# 同步余额

        $data = LuckyBaseService::login($uid = 18, $tz_system_id = 7);p($data);
        $rst = bin2hex("Shanghai");p($rst);
        $rst = QiLinBaseService::synBalance(26);p($rst);
        $rst = QiLinBaseService::userInfo($uid = 18, $tz_system_id = 8);p($rst);
        p(microtime(true));
        $rst = NineNineBaseService::getRemoteHzRecords($uid = 11, $tz_system_id = 2, $lottery_type = 6);p($rst);
        $rst = StaticService::getStaticCodeType2($lottery_type = 5); p($rst);
        $num = ['1122', '1212', '1221', '2112', '2121', '2211'];
        $miss = SscDataService::getDsHistoryMiss($num, '1,2,3,4', $lottery_type=5, 5000);p($miss); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];

        $rst = StaticService::opAllCodeTypeYl();p($rst);
        $codes = '';
        for($i=0; $i<10; $i++){
            for($x=0; $x<10; $x++){
                $codes .= $i.','.$x.',X,X@';
            }
        }
        p(trim($codes, '@'));
        $rst = KjDataGet::getBeforeQihaoByQihao('20191112001',8);p($rst);
        $rst = StaticService::staticSDHzPerDateProfits($lottery_type = 5); p($rst);
        $rst['opProfitsPlans'] = SscDataService::opProfitsPlans($lottery_type = 8);p($rst);
        p(3%5);
        $rst['updateCodeTypeYLs5'] = SscDataService::updateCodeTypeYLs($type = 5, $lottery_type = 5);p($rst); # 70s
        $rst = SscDataService::insertCode($type = 5);p($rst); # 插入三字现、四字现
        $rst = StaticService::staticHzPerDateProfits('2019-10-31', $lottery_type = 5); p($rst);
        $rst = CommonService::isCodeType_2($codes = '3,3,3,X');p($rst);
        $rst = NumService::delByValue(['1', 'X', '3', 'X'], 'X');p($rst);
        $rst = BetService::isCanBet($lottery_type = 5);p($rst);
        $rst = KjDataGet::updateNullCode();p($rst);
        $rst = SscDataService::updateCodeTypeYL($type = 2, $lottery_type = 6);p($rst); # 号码类型遗漏
        $rst = CommonService::isCodeType3n2b('0,0,5,6');p($rst);
        $rst = CommonService::isCodeType3n2b('1,2,3,4');p($rst); # 三现:双重+兄弟
        $rst = UserSysPlansService::getCodeTypes();p($rst);
        $miss = SscDataService::getCodeTypeHistoryMiss('type_4b', $lottery_type = 5, $static_nums = 20000);p($miss); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
        $rst['allDateStatic3nPerMonth'] = StaticService::allDateStatic3nPerMonth($lottery_type = 6);p($rst); # 三现每月统计
        $rst = StaticService::staticKj3NCounts('2019-10', $lottery_type = 5);p($rst);
        $rst['allDateStatic4nPerMonth'] = StaticService::allDateStatic4nPerMonth($lottery_type = 5); # 部分四现每月统计
        $rst = StaticService::getCreateCodeType3nSql($lottery_type = 5);p($rst);
        $rst = StaticService::getCreateCodeType4nSql($lottery_type = 5);p($rst);
        $miss = SscDataService::getCodeTypeYlHistoryMiss('555', $lottery_type = 5, 20000);p($miss);
        $rst = SscDataService::updateCodeTypeYL($type = 2, $lottery_type = 5);p($rst); # 号码类型遗漏
        $miss = SscDataService::getSdHzYlHistoryMiss([26], $lottery_type = 6, 20000);p($miss);
        $rst = SscDataService::getLastIndexId(6);p($rst);
        $rst['updateCodeTypeYLs4'] = SscDataService::updateCodeTypeYLs($type = 3, $lottery_type = 8);p($rst);
        $rst = StaticService::static2NumsYl($lottery_type = 8);p($rst);
        $rst['updateDsYL'] = SscDataService::updateDsYL($lottery_type=8); p($rst);// 单双遗漏
        for ($i=0; $i<50; $i++){
            $rst['updateDs'] = SscDataService::updateDsData($lottery_type=8); // 每期开奖遗漏 -- 新开
        }
        p($rst);
        $lottery_types = StaticService::getLotteryTypes();
        foreach ($lottery_types as $lottery_type) {
            /* 处理系统投注计划 add 2019-01-21 */
            $rst[] = KjDataGet::afterKj($lottery_type); # 处理系统投注计划，更新统计数据
            /* 处理系统投注计划 add 2019-01-21 */
        }
        p($rst);
        //$str = '{"Status":1,"Data":{"CompletedStatus":1,"LackStatus":0}}'; //p(json_decode($str, true)); d(strpos($str, "\"Status\":1") !== false);
        $rst = SevenService::login(19, 3);p($rst);
        $rst = SevenService::synBalance(5);p($rst);
        $rst = KjDataGet::insertKjData('20191009224', 8, '9,5,0,8,7');p($rst);
        $data = Lucky5::batch(); $kjDatas = array_reverse($data); //p($kjDatas);
        foreach ($kjDatas as $key=>$dataInfo){
            $rst = KjDataGet::insertKjData($dataInfo['expect'], 8, $dataInfo['opencode']);
        }p($rst);
        $rst['kj'] = KjDataGet::grabOne();p($rst);
        $data = Lucky5::getLotteryLucky();p($data);
        $rst = TzService::insertLuckyDataTime(); p($rst);
        p(unserialize('a:3:{s:4:"time";i:1570224883;s:3:"ttl";i:3600000;s:4:"data";a:0:{}}'));
        $rst = CqsscKcw::getLotteryNoZhiBo();d($rst);
        $data = CqsscKcw::getLotteryNoOneNineNineEight($type='xml');p($data);
        $profits = SscDataService::getSomeDatesBeforedProfits($lottery_type = 5);p($profits);
        $profits = SscDataService::getProfitsBeforeProfitsByQihao($qihao='190929001', $beforeQishus = 400, $lottery_type = 5);p($profits);
        $rst = TzService::tz(); p($rst);// 计划投注
        $codesArr = NumService::getNotLatelyCodes(['lately_start'=>0, 'lately_end'=>400]);p($codesArr);
        $rst = SscDataService::calulateBeforeProfits();p($rst); # 统计前面多少期号码的中奖利润
        $msg = KjDataGet::insertKjData('2019092548', $lottery_type = 6, $kjData = '3,9,9,7,1');p($msg);
        $rst['updateDsYL'] = SscDataService::updateSdHzYl($lottery_type = 5); p($rst);// 更新和值遗漏
        $rst = SscDataService::update3NumYL($lottery_type = 6);$end_time = time(true); $time_consume = ($end_time-$start_time).'s';p([$rst,$time_consume]);
        $rst[] = StaticService::static4dPerDateProfits($lottery_type = 5);p($rst); # 每天四定利润统计，四定类型详见：StaticService::$typeArr
        $rst = StaticService::staticSDPerDateProfits(date('Y-m-d'));p($rst);
        $rst = NumService::getCodesKuaiXuan(['type_4'=>0, 'type_2'=>1, 'type_4d'=>1]);p($rst);


        $rst['opStaticSdProfitsMonth'] = StaticService::opStaticSdProfitsMonth($lottery_type = 6); p($rst);# 单双利润统计(month)
        $rst['staticHzMonthsProfits'] = StaticService::staticHzMonthsProfits($lottery_type=6); p($rst);# 每月四定和值利润统计
        $rst = StaticService::static4dMonthsProfits($lottery_type = 6);p($rst); # 每月四定单双利润统计，有点慢，四定类型详见：StaticService::$typeArr
        $rst = StaticService::allHzStaticProfitsPerdate($lottery_type = 6);p($rst);# 循环计算每天每个和值利润统计
        $rst = TzService::opSystemBetPlans(6);p($rst);// 定制化投注计划
        $rst = KjDataGet::getBeforeQihaoByQihao('2019052501',6);p($rst);
        $rst = StaticService::staticAll2NumsYl();p($rst ); # 统计所有二字现遗漏
        $qihao = HN0898Service::getCurrentQihao($lottery_type = 6);p($qihao);
        //$rst = KjDataGet::insertKjData('', $kjConfig->lottery_type, $dataInfo['opencode']);
        $rst = BetService::bet(); p($rst);// 用户新计划投注，可正买可反买
        $data = XjSsc::batchSevenDay();p($data);
        $rst = BaseNumService::getRepeat4Codes22();p($rst);

        $rst[] = StaticService::static4dPerDateProfits($lottery_type = 6);p($rst); # 每天四定利润统计，四定类型详见：StaticService::$typeArr
        $rst['updateDsYL'] = SscDataService::updateSdHzYl($lottery_type = 5); p($rst);// 更新和值遗漏
        $snid = NineNineBaseService::getSnidBySn('JXSSC1909201535157573FFE1', $lottery_type = 6); p($snid);// 获取方案内容
        $rst = HN0898Service::getRemoteHzRecords(3, 2);p($rst);
        for ($i=0;$i<20;$i++){ # 统计数据
            //$rst['allDateStaticCodeTypePerDate'] = StaticService::allDateStaticCodeTypePerDate($lottery_type = 6); //p($rst);# 号码类型每天数量统计
            $rst['allDateStaticHzPerDate'] = StaticService::allDateStaticHzPerDate($lottery_type = 6); //p($rst);# 和值每天数量统计
        }p($rst);
        self::_init();
        $kjDatas = XjSsc::getLotteryNoBatch();
        $kjDatas = array_reverse($kjDatas);
        p($kjDatas,0);
        foreach ($kjDatas as $key=>$dataInfo){
            $rst = KjDataGet::insertKjData($dataInfo['expect'], 6, $dataInfo['opencode']);
            p($rst);
        }

        $data = XjSsc::getLotteryNoBatch();
        $data = array_reverse($data);p($data);
        $data = XjSsc::getLotteryNoZhiBo();p($data);
        $data = XjSsc::getLotteryNoSevenDay();p($data);
        $data = XjSsc::getLotteryNo99();p($data);
        for($i=1; $i<=59; $i++){
            $qihao = 190917000 + $i;
            $rst = SscDataService::insertSscKjDataDs($qihao);//p($rst);
        }
        p($rst);
        $rst['opStaticSdProfitsMonth'] = StaticService::opStaticSdProfitsMonth($lottery_type = 5); p($rst);# 单双利润统计(month)
        $rst['allDateStatic3NumsPerDate'] = StaticService::allDateStatic3NumsPerDate($lottery_type = 7);p($rst); # 上奖三字现
        $rst = StaticService::get2NumsYlRecords('66', $lottery_type = 7);p($rst);
        $post = \Yii::$app->request->post();
        p($post);
        $statics = StaticService::staticKj3NumCounts($date='2019-09-01', $lottery_type=5);p($statics);
        set_time_limit(0);
        # 号码类型：双重、双双重、四重、三兄弟、四兄弟
        $rst['updateCodeTypeYL'] = SscDataService::updateCodeTypeYL($type = 2, $lottery_type = 5);p($rst);
        # 三字现带双重
        //$rst['updateCodeTypeYLs5'] = SscDataService::updateCodeTypeYLs($type = 5, $lottery_type = 5); p($rst);
        $rst = CommonService::getLotteryName();p($rst);
        $arr = 0;
        p(empty($arr));
        $arr = ['海南省内包邮'];
        //$str = 'a:1:{i:0;s:18:"海南省内包邮"}';
        p(serialize($arr));
        $rst = SscDataService::insertStaticVal();p($rst);
        $rst = HuiYuanService5::login(3, 6);p($rst);
        $rst = HuiYuanService5::loginNew(18, 6);p($rst);
        $rst = HuiYuanService5::synBalance(13);p($rst);
        $rst = NumService::getCodesKuaiXuan(['type_log'=>'1']);p($rst);
        p($rst);
        $rst = HN0898Service::getCurrentQihao( 7 );p($rst);
        $rst = HN0898Service::getQihao( 7 );p($rst);
        $rst = SscDataService::clearDataTables();p($rst);
        $rst = HN0898Service::getDifferentNums();p($rst);
        $rst = TzService::insertKuaiLe8DataTime();p($rst);
        $qihao = HN0898Service::getQihao($lottery_type=5);p($qihao);
        $rst = StaticService::getNiceCodes(5);p(['最优号码[四现不带双]'=>$rst]);
        $rst['opStaticSdProfitsDay'] = StaticService::opStaticSdProfitsDay();p($rst); # 单双利润统计(day)
        $rst = SscDataService::getCodesDS('1,2,3,4,5');p($rst);
        $rst[] = StaticService::opAllStaticProfits(); p($rst);# 利润统计
        $rst = StaticService::allHzStaticProfits($lottery_type = 5);p($rst); # 每个月份每个和值利润统计
        $rst = StaticService::staticPerHzProfits('2019-03');p($rst); # 某月份每个和值利润统计
        if($status = StaticService::isCanOpStatic($lottery_type=5, $mkey = 'opStatic')) {
            p('xxxx');
        }
        p(rand());
        $rst = StaticService::staticSdHzProfitsPerdate(); p($rst); # 每天每个和值利润统计

        # 三字现带双重
        $rst['updateCodeTypeYLs'] = SscDataService::updateCodeTypeYLs($type = 4);p($rst);


        $rst = NumService::getCodesArise(['003']);p($rst); //2+3+1+2+2
        $codesArr = [9, 7, 9, 8];
        $code_3n = CommonService::get3n($codesArr);p($code_3n);
        $rst = StaticService::staticHzCounts('2019-06-12', $lottery_type = 5); p($rst);
        //$data = '{"type_3":"1","type_22":"0","type_2b":"1","type_4b":"1","arise":"12345","p1":"3456","p2":"345679","p3":"89734","p4":"56092"}';
        //$rst = NumService::getCodesKuaiXuan(['type_2'=>1, 'type_3'=>1, 'hz'=>[30,31,32,33,34,35]]);p($rst);
        $domain = BaseKj::getApiHost(8);p($domain);
        p('xxx');
        $beforeQihao = KjDataGet::getBeforeQihaoByQihao('190525001');p($beforeQihao);
        $rst = NumService::getCodesArise(['9377']);p(count($rst));
        $arr = ['type_2b'=>1, 'hz'=>[11,12,13,14,15,16,24]]; p(json_encode($arr));
        $rst = NumService::getCodesKuaiXuan(['type_2'=>1, 'hz'=>[8,28]]);p($rst);
        $qihao = HN0898Service::getQihao($lottery_type = 6);p($qihao);
        $rst = TzService::insertSscDataTime(6); p($rst);
        $account = AdminModel::findOne(11)['username'];p($account);
        $rst = StaticService::calculate2bProfits($lottery_type = DEFAULT_LOTTERY_TYPE, $start_date = '2019-05-01', $end_date = '2019-05-15'); p($rst);
        for($i=0;$i<100;$i++){
            $rst['update3NumData'] = SscDataService::update3NumData($lottery_type=5);
        }
        p($rst); // 每期开奖遗漏
        $qihao = HN0898Service::getQihao(5);
        $rst = BetService::getBetCacheTime($lottery_type = 5, $qihao); p($rst);# 投注之后缓存时间
        $qihao = HN0898Service::getQihao(5);p($qihao);
        $rst['update3NumData'] = SscDataService::update3NumData(5);p($rst); // 每期开奖遗漏
        $rst = NumService::getCodesArise(['1234589']);p(count($rst));

        $rst = NumService::filterLaterCodesAnd2bcode(5, $qihao = '190516056');p($rst);
        $rst = NumService::getRecentlyCodes(5);p($rst);
        $rst = UserSysPlansService::userSysPlanChange(2);p($rst);
        $rst = StaticService::getYlByCodes('02468,13579,X,X', 2, 18);p($rst);
        $rst = HN0898Service::insertDsYl();p($rst);
        $rst = OpKjService::opSscKjData(2); p($rst); # 处理投注数据
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
        //$rst = NumService::getCodesArise_bak(['12345']);p($rst);
        $rst = StaticService::staticKj3NumCounts();p($rst);
        $arr = [['reach_val'=>100, 'reduce_val'=>10], ['reach_val'=>300, 'reduce_val'=>50]];p(json_encode($arr));
        $codes = BetService::getCodes(2, 3, 20, 1, 0.1, 1, '35,36');p($codes);
        $rst = SevenService::sscIndex(3, 3);p($rst); # 用户信息
        $rst = BetService::userSysPlansTzNow(81, 3); p($rst);
        $rst = SevenService::getSn(3, 3);p($rst); # 用户信息
        $rst = CqsscSevenDay::getLotteryNo(); p($rst);
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
        $rst = WxService::sendMsg();p($rst); # 群发微信消息
        $rst = CqsscKcw::getLotteryNo();p($rst);
        $rst = HN0898Service::getQihao();p($rst);
        $rst = SscDataService::calcDsProfit();p($rst); // 单双遗漏计算
        $rst = TzService::tz();p($rst); // 计划投注
        $rst = SscDataService::calTzTotalMoney('02468,X,13579,13579', 0.1, 2); p($rst);
        $rst = UserCustomPlansService::joinDs3DwPlans();p($rst);
        $rst = CommonService::getAwardNumberByQihao('181106022'); p($rst);
        $rst = SscDataService::getSscKjData0898('181106021');p($rst); // 每期开奖遗漏
        $m = \Yii::$app->cache;
        $mkey = 'TZ_SWITCH_STATUS_181029073';
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
        $nums = [4,5,6,6];
        $rst = CommonService::get3x($nums);p($rst);
        $rst = CommonService::get3x($nums);p($rst);
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

    public static function dealTime($a){
        $time = strtotime($a);
        return $time;
        $year = substr($a,0,4);
        $month = substr($a,4,2);
        $day = substr($a,6,2);
        $hour = substr($a,8,2);
        $min = substr($a,10,2);
        $sec = substr($a,12,2);
        return mktime($hour,$min,$sec,$month,$day,$year);
    }




















}