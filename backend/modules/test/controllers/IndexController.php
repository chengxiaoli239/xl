<?php
/**
 * Created by PhpStorm.
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\test\controllers;

use backend\models\Admin;
use backend\models\AgentUsersBalanceFlows;
use backend\models\ImportPlanCodes;
use backend\models\SscKjData;
use backend\models\SscSdHzVal;
use backend\models\SscStaticVal;
use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use backend\models\wechat\Bets;
use backend\modules\cron\controllers\WeixinController;
use backend\modules\kj\controllers\BingDaoController;
use backend\service\agent\AgentUsersBalanceService;
use backend\service\agent\AgentUsersService;
use backend\service\baota\BaoTaService;
use backend\service\BetService;
use backend\service\ChatCommonBetService;
use backend\service\clients\AgentClientsService;
use backend\service\clients\TzSystemUsersService;
use backend\service\datas\DatasClearService;
use backend\service\FootBallService;
use backend\service\huiyuan\HuiYuanService5;
use backend\service\JinYing\JinYingService;
use backend\service\jobs\TestJob;
use backend\service\Juhua\JuHuaBaseService;
use backend\service\LeCai\ZhongFaService;
use backend\service\Lucky5\Lucky5Service;
use backend\service\Lucky5\LuckyBaseService;
use backend\service\McLockService;
use backend\service\NineNine\NineNineBaseService;
use backend\service\NineNine\NineNineNewService;
use backend\service\NumService;
use backend\service\plans\BetErrorPlansTaskService;
use backend\service\PoxyIPService;
use backend\service\BingDao\BingDaoService;
use backend\service\qilin\QiLinBaseService;
use backend\service\SevenService;
use backend\service\sports\TennisSportsService;
use backend\service\statics\plan\OperatePlanService;
use backend\service\statics\statics_base\BaseDataService;
use backend\service\statics\statics_base\DealDataService;
use backend\service\statics\statics_qx\StaticsQxMissService;
use backend\service\StaticService;
use backend\service\UserService;
use backend\service\wanbo\tennis\TennisService;
use backend\service\TestService;
use backend\service\UserCustomPlansService;
use backend\service\UserSysPlansService;
use backend\service\WxService;
use backend\service\XlService;
use backend\tools\Tools;
use common\kj\BaseKj;
use common\kj\cqssc\CqsscKcw;
use common\kj\cqssc\CqsscSevenDay;
use common\kj\indexes\DaoQiongSi;
use common\kj\indexes\NaSiDaKe;
use common\kj\lecai\LeCaiService;
use common\kj\qxc\QxcTcw;
use common\kj\ssc\BingDao;
use common\kj\ssc\JiaNaDa;
use common\kj\ssc\Lucky5;
use common\kj\ssc\TaiWanHuanLe;
use common\kj\ssc\Thirdd;
use common\kj\xjssc\XjSsc;
use common\models\AdminModel;
use common\models\eyun\EyunAuth;
use common\models\open\SsxxRequestLog;
use common\service\ChatService;
use common\service\CommonService;
use common\service\index\CrontabIndexService;
use common\service\jobs\robots\message\TextReceiveJobs;
use common\service\jobs\robots\user\WechatUserStatusJobs;
use common\service\proxy\ProxyBaseService;
use common\service\proxy\ProxyKuaiService;
use common\service\thirdD\OperateLotteryService;
use common\service\thirdD\sx\Ssxx3dBetService;
use common\service\webot\FriendsService;
use common\service\webot\LoginService;
use common\service\webot\MsgService;
use common\service\webot\SendMsgService;
use common\service\webot\WebotService;
use common\service\wechat\eyun\EYunBaseService;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\service\wechat\eyun\MessageSetService;
use common\service\wechat\RobotUserService;
use common\service\wechat\WechatUserService;
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
use common\tools\RedisLock;
use common\tools\Tool_Common;
use GuzzleHttp\Client;
use Yii;
use yii\helpers\Json;
use yii\web\Controller;
use backend\service\SscDataService;
use backend\service\TzService;


class IndexController extends Controller
{

    private static function _init()
    {
        header("Content-type: text/html; charset=utf-8");

        # 测试

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    }

    public function actionGetmoney()
    {
        p(rand());
        $cookie['ASP.NET_SessionId'] = 'woh4v445d2kzkg55wdc3il55';
        p($cookie);
    }

    /**
     * /test/index/api-log
     * @return array
     */
    public function actionApiLog(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $mkey = 'err_log_'.__FUNCTION__;
        $num = \Yii::$app->redis->incr($mkey);
        if($num>2){
            return ['status'=>200, 'msg'=>'接收成功'];
        }
        \Yii::$app->redis->expire($mkey, 900);

        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '用信息接口', ['post'=>$post]);

        return ['status'=>200, 'ms'=>'成功'];
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
            for ($m = $i + 1; $m <= 9; $m++) {
                if ($m == $i) continue;
                for ($n = $m + 1; $n <= 9; $n++) {
                    if ($m == $n || $n == $i) continue;
                    $insertData[] = [$i . $m . $n];
                    $count++;
                }
            }
        }
        $totalnum = \Yii::$app->db->createCommand()->batchInsert("{{%three_num}}", $field, $insertData)->execute();


        echo '<br>======' . $count . '======' . $totalnum . '=========';
    }

    /**
     * @desc 切换代理
     * @return bool
     */
    public static function actionChangePoxyIp()
    {
        self::_init();
        $uid = 10;
        $rst = PoxyIPService::clearProxyIpKey($uid);
        p($rst);

        return $rst;
    }

    public function actionGenNum()
    {
        $field = ['code'];
        $insertData[] = ['123'];
        $totalnum = \Yii::$app->db->createCommand()->batchInsert("{{%three_num}}", $field, $insertData)->execute();
        $nums = [1, 2, 3, 4, 5, 6, 7];
        sort($nums);
        foreach ($nums as $key1 => $num1) {
            unset($nums[$key1]);
            foreach ($nums as $key2 => $num2) {
                if ($num1 >= $num2) continue;
                foreach ($nums as $key3 => $num3) {
                    if ($num1 >= $num3 or $num3 <= $num2) continue;
                    $datas[] = $num1 . $num2 . $num3;
                }
            }
        }
        p($datas);
    }

    public function actionTestIp()
    {
        $ch = curl_init();
        //$url = "http://localhost/ser.php";
        $url = "http://120.77.157.40/test/index/test-bet";
        //声明伪造head请求头
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header = []);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $page_content = curl_exec($ch);
        curl_close($ch);
        echo $page_content;
    }

    public function actionTestPython()
    {
        self::_init();
        $post = \Yii::$app->request->post();
        $get = \Yii::$app->request->get();

        return ['status' => 200, 'data' => ['get' => $get, 'post' => $post], 'msg' => '操作成功'];
    }

    /**
     * @desc 测试投注
     */
    public function actionTestBet()
    {
        $logArr = ['test' => 'dw', '_SERVER' => $_SERVER, 'HTTP_CLIENT_IP' => getenv('HTTP_CLIENT_IP'), 'HTTP_X_FORWARDED_FOR' => getenv('HTTP_X_FORWARDED_FOR'), 'REMOTE_ADDR' => getenv('REMOTE_ADDR')];
        Tool_Common::log('/WORK/LOG/lottery_xl/' . date('Ymd') . '/dw', 'INFO', '测试windows计划', $logArr);
        p('xx');

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $playway = $post['playway'];
        if (!$codes = $post['codes']) {
            return ['status' => 300, 'msg' => '投注号码不能为空'];
        }
        $single = $post['single'] ? $post['single'] : 0.1;
        $lottery_type = $post['lottery_type'] ? $post['lottery_type'] : 5;

        $rst = HN0898Service::postBet($uid = 2, $playway, $single, $codes, $lottery_type);

        return $rst;
    }

    /**
     * @desc 网球测试
     */
    public function actionTennis()
    {
        $rst = TennisSportsService::grabTennisSportsGame();
        return $rst;
        $rst = \backend\service\pingbo\tennis\TennisService::login($uid = 18, $tz_system_id = 14);
        p($rst);
        $rst = \backend\service\Mbs188\tennis\TennisService::getGames();
        p($rst);
        $data = json_decode($json, true);
        //p($data);
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = [];
        $rst = TennisService::getGameType($data, $type = 1, $game_type = 33);
        return $rst;

    }

    /**
     * @desc 微信测试
     */
    public function actionTestWx()
    {
        $uuid = WxService::get_uuid();
        p($uuid);
        $callback = WxService::get_uri($uuid);
        p($callback);
    }

    public static function tst($fff = 'p')
    {
        function eee()
        {
            p(22222, 0);
        }

        $fff('xxxxx', 0);

        return eee();
    }

    public static function fff($str = '')
    {
        p($str . 'bbb');
    }

    /**
     * @inheritDoc
     */
    public static function encrypt(string $plaintext, string $key, string $iv = ''): string
    {
        #$ciphertext = openssl_encrypt($plaintext, 'aes-256-ecb', $key, OPENSSL_RAW_DATA, $iv = '');
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-ecb', $key);
        p(['plaintext'=>$plaintext, 'key'=>$key, 'ciphertext'=>$ciphertext], 1);

        if (false === $ciphertext) {
            throw new UnexpectedValueException('Encrypting the input $plaintext failed, please checking your $key and $iv whether or nor correct.');
        }

        return base64_encode($ciphertext);
    }

    /**
     * @inheritDoc
     */
    public static function decrypt(string $ciphertext, string $key, string $iv = ''): string
    {
        $plaintext = openssl_decrypt(base64_decode($ciphertext), 'aes-256-ecb', $key, OPENSSL_RAW_DATA, $iv = '');

        if (false === $plaintext) {
            throw new UnexpectedValueException('Decrypting the input $ciphertext failed, please checking your $key and $iv whether or nor correct.');
        }

        return $plaintext;
    }

    public static function getPermutations($array) {
        $results = [[]];
        foreach ($array as $element) {
            $tmp = [];
            foreach ($results as $result) {
                $count = count($result);
                for ($i = 0; $i <= $count; $i++) {
                    $copy = $result;
                    array_splice($copy, $i, 0, $element);
                    $tmp[] = $copy;
                }
            }
            $results = $tmp;
        }
        return $results;
    }
    public function actionDw1(){
        $loginRst = EYunBaseService::memberLogin($id=1);p($loginRst);
        //$betRow = Bets::findOne('1160');
        //list($code, $data, $msg) = Ssxx3dBetService::postToSite($betRow);p([$code, $data, $msg]);
        #$rst = \common\open\thirdD\methods\MethodsMap::insertMapMethods();p($rst);

        $headers = [
            #'Accept' => 'application/json, text/javascript, */*; q=0.01',
            #'Accept-Encoding' => 'gzip, deflate',
            #'Accept-Language' => 'zh-CN,zh;q=0.9',
            'Cookie' => 'PHPSESSID=ifuhjc1j5uctbm0pnupvg1odb0',
            #'Host' => 'af1.ssxx9999.com',
            #'Proxy-Connection' => 'keep-alive',
            #'Referer' => 'http://af1.ssxx9999.com/index/appprint.html',
            #'X-Requested-With' => 'XMLHttpRequest',
        ];

        #$rst = \common\open\thirdD\api\SiteUserApi::getUserInfo('http://af1.ssxx9999.com', $headers); p($rst);

        #list($code, $data, $msg) = OperateLotteryService::operateOne($betRow); p([$code, $data, $msg]);
        #$user_id = EYunBaseService::getRobotUserIdByWechatId($RobotWechatId='wxid_ckgr7i2q9fr522');p($user_id);
        #$code_2n = CommonService::get2n($codesArr=[9, 9, 3], $lottery_type=26); p($code_2n);
        #$sort_codes = CommonService::reSortCodes($codesArr=[12, 43, 796]); p($sort_codes);
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        #$num = \common\service\helpers\ThirdD::cn2num('三百五十二');p($num);
        $user_id = 21;
        $e = new EYunBaseService($user_id);
        #$loginRst = $e->memberLogin($id=1); p($loginRst);
        #$rst = RobotUserService::switchWechat($user_id, $post);p($rst);

        $MessageService = new EYunMessageOperateService($user_id);
        set_time_limit(0);
        if(isset($post['texts'])){
            $fromUser='wxid_875i1kgd38x122';
            $texts = explode('、', $post['texts']);
            $texts = array_filter($texts);
            foreach ($texts as $text){
                #$rst[] = $MessageService->receive($user_id, $text, $fromUser='wxid_875i1kgd38x122');
                push_queue_open(TextReceiveJobs::class, ['user_id'=>$user_id, 'text'=>$text, 'fromUser'=>$fromUser]);
            }
        }else{
            $rst = $MessageService->receive($post['text'], $fromUser='wxid_875i1kgd38x122'); p($rst);
        }
        return $rst;
        #$rst = $e->localIPadLogin(); p($rst);# 第二步
        #$rst = $e->getIPadLoginInfo();p($rst); # 第三步
        #$rst = $e->initAddressList(); # 第四步
        #$rst = $e->getAddressList(); p($rst); # 第四步

        $e = new EYunMessageOperateService($user_id);
        $rst = $e->send($wcId='wxid_875i1kgd38x122', $content='晚上好，早点睡，明天再聊'); p($rst); # 第四步 wangyegao2012

        p(['rst'=>$rst]);
    }

    public function actionDw()
    {
        $r = Yii::$app->db->getSchema()->refreshTableSchema('{{%lottery_data_deal_status}}'); p($r);
        $d = Thirdd::getCurrentKjData($lottery_type=26);p($d);
        $Thirdd = new Thirdd();
        $data = $Thirdd->getFuCai3d($type='json', 2);p($data);
        $lottery_types = StaticService::getLotteryTypes();
        p($lottery_types);
        $str = 'fTtrNuJ2---sSYXaQFRUjChzqbBn7Od4SRDBvZp7hL4';
        p(base64_decode($str));
        p(['logData'=>$logData], 0);
        p($logData);
        $data = Lucky5::getLotteryLucky($type = 'json', $test = 2);
        d($data);
        $post = [
            'access_token' => '4b843e29ac8dd191e894c7dcea547815',

        ];
        $rst = TzSystemUsersService::getActivePlanTasks($post['access_token'], $post['current_qihao'], $post['lottery_type']);
        p($rst);
        $access_token = '4b843e29ac8dd191e894c7dcea547815';
        $post = [
            'access_token' => '4b843e29ac8dd191e894c7dcea547815',
            'from_type' => 'kuaixuan',
            'from' => 'api',
        ];
        $access_token = '00e9146df95b0dfb1b9557790acbbfc8';
        $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
        AgentClientsService::checkProfits($TzSystemsUsers);
        p($TzSystemsUsers);
        $rst = \backend\service\SscDataService::getUserBetProfits($uid=10); p($rst);
        $rst = UserService::staticUserProfits($uid=17); p($rst);
        $r = Yii::$app->db->getSchema()->refreshTableSchema('{{%tz_systems_users}}');p($r);
        $sku_set = ['8908764518923','4013197767010','8908764518923'];
        $sku_set = array_unique($sku_set);
        $ignore_sku_set = ['8908764518923'];
        p(['sku_set'=>$sku_set, 'ignore_sku_set'=>$ignore_sku_set, 'diff'=>array_diff($sku_set, $ignore_sku_set)]);
        # /App/Index?_=1687589343561#!log.select|select?link=select
        //p(urldecode('https://b1.w3vk8275.xyz/App/Index?_=1687589343561#!log.select%7Cselect%3Flink%3Dselect'));
        $text = '四定位，复式“取”数：123'; # 正：4992组  反：5008
        $text = str_replace(['[', ']'], '', $text);
        list($code, $data, $err_msg) = AgentClientsService::getKuaiYiDescByOperationLogs($text);
        $data['codes_hz'] = Json::decode('{"p1":"0123456789","p2":"0123456789","p3":"0123456789","fixed_sel_pos":"4","is_filter_dynamic":1,"filter_dynamic_types":["70"],"filters":[]}');
        $data['tz_type'] = 29;
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>20]);
        list($code, $bet_single) = AgentUsersService::getFlowSingle($TzSystemsUsers, $single=0.5, $buy_type=0);
        p(['code'=>$code, 'bet_single'=>$bet_single]);
        //p([$code, $data, $err_msg]);
        $rst = self::getPermutations([1, 2, 3]);p($rst);
        p($codes);
        list($type_dx, $type_4dx, $type_dx_str) = CommonService::getTypeDx('5,1,1,0,8');
        p([$type_dx, $type_4dx, $type_dx_str]);
        $current_proxy_addr = ProxyBaseService::getCurrentValidProxyIp(1, 2);
        p($current_proxy_addr);
        $SscKjData = SscKjData::find()->select(['qihao'])->where(['lottery_type'=>8])->asArray()->orderBy(['id'=>SORT_DESC])->limit(1)->one();
        p($SscKjData);
        $kjData = SscKjData::findOne(['qihao'=>'20230417102', 'lottery_type'=>8])->code_str;
        $query2 = SscKjData::find()->select(['code_str'])->where(['qihao'=>'20230417102', 'lottery_type'=>8])->asArray()->limit(1);
        $kjData2 = $query2->limit(1)->one()['code_str'];
        p([$kjData, $kjData2, $query2->createCommand()->getRawSql()]);
        $isExists = SscKjData::findOne(['lottery_type'=>8,'qihao'=>'20230417102']);
        $isExists2 = SscKjData::find()->where(['lottery_type'=>8,'qihao'=>'20230417102'])->one();
        p([$isExists, $isExists2]);
        $rst = StaticService::static4dPerDateProfits($lottery_type = 8);
        p($rst); # 每天四定利润统计，四定类型详见：StaticService::$typeArr
        $rst = Lucky5Service::login($uid = 12, $tz_system_id = 9);
        p($rst);
        $rst = ProxyBaseService::preGetValidIp($proxy_type=1, $is_auto = 0);
        p($rst);
        $rst = SevenService::synBalance(17);
        p($rst);
        $params = [];
        $is_auto_audit = $params['is_auto_audit'] ?? 1;
        p($is_auto_audit);
        $data = BetService::getLotteryName(8);
        p($data);
        $a = [0,1,2,3,4,5,6,7,8,9];
        $b = [1,2,2,3];
        $intersection = array_intersect($a, $b);
        p($intersection);
        $rst = TzSystemUsersService::getActiveQihao($lottery_type=8);
        p($rst);
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        echo json_encode(['code'=>200, 'msg'=>'操作成功', 'data'=>[]], JSON_FORCE_OBJECT|320);exit();
        $UserSysPlan = UserSysPlans::findOne('5975');
        $rst = \backend\service\SscDataService::recordOnePlanProfits($UserSysPlan);
        p($rst);
        $end_qihao = \backend\service\NumService::getHasOpenEndQihao($lottery_type=1);
        p($end_qihao);
        # 加密
        $name = '马氏三角杀';
        $key = '13ddeaa877751c999e5b2ef96fbcf2355edc4e10c2256ea0ab19478e5caadca4';
        $key = hex2bin($key);
        $data = openssl_encrypt($name, 'AES-256-ECB', $key, OPENSSL_RAW_DATA);
        $data = bin2hex($data);

        # 解密
        $name_encrypt = 'e6d253f3a70bdbd4fe46a543040ccca9';
        $name = openssl_decrypt(hex2bin($name_encrypt), 'AES-256-ECB', $key, OPENSSL_RAW_DATA);
        $id_encrypt = '16796b7ed5ad8ed8801633e3516ab724b9a2775a32c1ca575a386517a06b24cb';
        $idcard = openssl_decrypt(hex2bin($id_encrypt), 'AES-256-ECB', $key, OPENSSL_RAW_DATA);
        p(['机密信息'=>$data, 'name'=>$name, 'idcard'=>$idcard]);
        $x = ( (0.1+0.7) * 10 );
        p(['x'=>$x, 'intx'=>(int)$x] ); # 7
        $a = 4;
        $b = '4';
        d($a!=$b);
        $appKey = '13ddeaa877751c999e5b2ef96fbcf2355edc4e10c2256ea0ab19478e5caadca4';
        $appKey = json_encode($appKey);
        $name = '马氏三角杀';
        p(['e'=>self::encrypt($name, $appKey), 'd'=>self::decrypt('kl0eeBIVQSLkmQD5iz1c2w==', $appKey)]);

        d(strpos('120341234888', '1234'));
        p(base64_decode("letWC2p_t2X835-hS-3637vZD9Wx49oD15hti5J93RY="));
        $rst = StaticService::staticOnePlanProfits($plan_id=5885);
        p($rst);
        $UserSysPlans = UserSysPlans::findOne($plan_id='5834');
        $current_qihao = NumService::getPlanBetCurrentQihao($UserSysPlans, $lottery_type = 17);
        p($current_qihao);
        $miss = SscDataService::staticPeiShuDate($lottery_type = 8);
        p($miss);
        $rst['kj'] = KjDataGet::grabKjData();
        p($rst);
        $lottery_type = 8;
        $lottery_name = \common\service\CommonService::getLotteryName($lottery_type);

        $data = [
            'url' => 'http://example.com/image.jpg',
            'file' => '/tmp/image.jpg',
            'txt' => '备注文案',
            'fast' => '1',
            'name' => '1xxxxxxxxxxx',
            'business_id' => '1xxxxxxx777',
        ];
        push_queue(\backend\service\jobs\TestJob::class, $data);
        #push_queue(TestJob::class, ['order_sn'=>'2390u3405344444444444', 'slow'=>true]);
        //Yii::$app->queue->push(new TestJob($data));
        p('kljasdlf');
        $UserSysPlan = UserSysPlans::findOne(5754);
        $lottery_type = 8;
        $hzArr = json_decode($UserSysPlan->hz_Arr, true);
        if(isset($hzArr['change_per']) && $hzArr['change_per'] == 1){ # 每期轮换
            $turn_key = \Yii::$app->params['IMPORT_CODES_TURN'] - 1;
            if (isset($hzArr['change_turn_pos']) && $hzArr['change_turn_pos']>0){
                # 指定位置号码数字，决定号码组数
                $newKjCodesStr = SscKjData::find()->where(['lottery_type'=>$lottery_type])->asArray()->orderBy(['id'=>SORT_DESC])->limit(1)->one()['code_str'];
                $newKjCodes = explode(',', $newKjCodesStr);
                $next_key = $newKjCodes[$hzArr['change_turn_pos']-1];
            }elseif(($hzArr['change_per']===0 OR ($hzArr['change_per'] == 1 && $hzArr['turn_key']>=$turn_key))) {
                $next_key = 0;#非轮换0，轮换:turn_key+1
            }else{
                $imports = ImportPlanCodes::find()->select(['uid', 'plan_id', 'plan_id_sort_key'])->where(['AND', ['=', 'plan_id', $UserSysPlan->id], ['!=', 'codes', '']])->asArray()->all();
                $sortKeys = yii\helpers\ArrayHelper::getColumn($imports, 'plan_id_sort_key');
                $current_key = array_search($hzArr['turn_key'], $sortKeys);
                $next_key = ($current_key+1>count($sortKeys)) ? 0 : $current_key+1;
                $next_key = $sortKeys[$next_key];
            }
            $hzArr['turn_key'] = $sortKeys[$next_key];
            $HI = date('H:i');
            if('03:59'<=$HI && $HI<'09:05'){
                $hzArr['turn_key'] = 0;
            }
        }
        p($hzArr);
        $date_time = '2022-10-01 04:00:37';
        $t = strtotime($date_time);
        $HI = date('H:i', $t);
        if('03:59'<=$HI && $HI<'09:00'){
            $hzArr['turn_key'] = 0;
        }
        p('klajsdf');
        $session_id = '4a62huo5ev61crrelqhnbnbn5l';
        $r = UserService::delUserOneSessionId('10', $session_id);
        p($r);
        $data = Lucky5::getBeforeKjCodesFromSite($num=1000);p($data);
        $data = Lucky5::getCodesByBeforeDate('2022-07-16', $num=288);p($data);
        $rst = DatasClearService::clearBettingRecords(); p($rst);
        $t = '[366/365.51]商品付款金额合计与成交金额不平衡 ';
        $t = str_replace('[', '', $t);
        $tArr = explode('/', explode(']', $t)[0]);
        p($tArr);
        $a=array("Name"=>"Peter","Age"=>"41","Country"=>"USA");
        p(array_values($a));
        $count = 5;
        $t = 5;
        p($t%($count));
        $batch_simulate_data = BetService::batchSimulateBet($lottery_types=[23], $uid=11, $is_auto=2);p($batch_simulate_data);
        $rst = BetService::lotteryBet($uid=17);p($rst);
        $codes = '5,8,6,4,8';
        $codesArr = explode(',', $codes);
        p(array_pop($codesArr));
        p($codesArr);
        $plan = UserSysPlans::findOne(5119);
        $codes_hz_data = json_decode($plan->hz_Arr, true);
        //p($codes_hz_data, 0);
        $BetService = new NineNineNewService();
        $betRst = $BetService->repeatErrorBet($task_id=427940);
        p($betRst);
        $rst = Lucky5Service::synBalance($id = 11); p($rst);# 同步余额
        $rst = Lucky5Service::userInfo($uid, $tz_system_id);
        $UserSysPlan = UserSysPlans::findOne(4624);
        $data = SscDataService::get_area_arise_qishus($UserSysPlan, $recent_qishus=15, $start_qihao='220401099', $area_bet_type=1);p($data);
        $plan = UserSysPlans::findOne(4827);
        $codes_hz = '{"area_all_qishus":"13","area_yl_qishus":"10","area_profits":"450","area_loss":"3600","arise_A_times":0,"arise_B_times":0,"filters":{"filter_type":"2","filter_nums":"","test_period_days":10,"playway":"1","filter_poses":"","start_qihao":"220411014","lottery_type":"23"},"filter_dates":[],"filter_qihaos":[],"singles_key":0,"areaBetStatus":0,"area_arise_qishus":0}';
        $codes_hz_data = json_decode($codes_hz);

        p(array_keys(DealDataService::$dealDataStatusFields));
        $r = DealDataService::insertLotteryDealDataStatus($lottery_type=17);p($r);
        $r = SscDataService::insertDealDataTask($lottery_type=23);p($r);
        p(urldecode('%E6%97%85%E6%8A%95%E9%BB%91%E8%99%8E'));
        p(base64_decode('MjEyNV9pbTFidGdhb3pfNHB0dHhhaDF5Z181,MTczNV9pbTEwdzI4MjIyMDIyMDMxMTgzMTk2,'));
        $rst = FootBallService::getSorceFromUnibet(); p($rst);# 群发微信消息

        $data = NaSiDaKe::getLotteryNo($type='json', $is_auto=2, $lottery_type=25);p($data);
        $rst = OperatePlanService::opProfitsPlans12_13($lottery_type = 8);
        p($rst);# A出x次B出y次投B 计划处理
        $rst = BaseService::synBalance($id = 10);
        p($rst); # 同步余额
        $ssl_uids = BaseService::getSslVersionUids();
        p($ssl_uids);
        p(urldecode('https://m.kuajing0898.com/wechatlogin?access_token=54_Ta8VVjILPFM8kbloyNv2DQqMIowaly3_8iExGHW_YzMijrtSUFIl8iWzpNpsnE-8ZZzegMXxWmP-ubqH6sRMMg&openid=opAKq1LHH1lqc1eC8zZgh5nOPUZw&r_url=https%3A%2F%2Fmk.kuajing0898.com%2Fecpage%3Fcode%3Dea561700637319f5'));

        $post = \Yii::$app->request->post();
        p($post);
        $pos = [1, 2, 3];
        $pos_to_desc = NumService::$pos_to_desc;
        p($pos_to_desc);
        $qihao = NumService::getQihaoByDaysBefore($test_period_days = 7, $lottery_type = 8);
        p($qihao);
        $arr = ['filter_type' => 1, 'filter_nums' => 1, 'playway' => 1, 'start_qihao' => '20211224163', 'filter_poses' => [1, 2], 'lottery_type' => DEFAULT_LOTTERY_TYPE]; # 过滤条件
        $fitlers = json_decode('{"filter_type":1,"filter_nums":1,"playway":1,"filter_poses":[2,4],"lottery_type":8}', true);
        $UserSysPlan = UserSysPlans::findOne(508);
        $filter_codes = NumService::getCodesByCodesHz($fitlers, $UserSysPlan, 8); # 过滤的号码
        p($filter_codes);
        //$rst['batch_simulate_data'] = BetService::batchSimulateBet($lottery_types = [8], $uid=2);p($rst);

        return self::tst('p');
        $url = 'https://www.ixigua.com/api/searchv2/user/%E5%BD%B1%E8%A7%86%E8%A7%A3%E8%AF%B4/10?search_id=202112211121000102121660980145893C&debug_model=false&_signature=_02B4Z6wo00f01BXRRLQAAIDAldO-94j5e-AV9UAAAGS76a';
        p(urldecode($url));
        $t = rand(1, 10);
        p($t);
        $isValidRst = ProxyKuaiService::kuaiIPValidTime(['219.128.35.247:19054']);
        p($isValidRst);
        $hasPlansActiveLottery = CommonService::hasPlansActiveLottery(\Yii::$app->params['NEED_PROXY_LOTTERYS']);
        p($hasPlansActiveLottery);
        $ip_addr = PoxyIPService::getCurrentValidProxyIp();
        p($ip_addr); # 获取当前可用的代理IP
        $rst = PoxyIPService::getRemoteProxyIp($type = 1);
        p($rst);
        p($rst);
        $rst = BaoTaService::syncBaoTaCrontabs($id = 1);
        p($rst);
        $testData = [
            # 二定
            '头尾12345各1', # 1.已校验 - 两定
            '头尾12345除双重除兄弟各1', # 1.已校验 - 两定
            '023468头尾各0.1', # 3.已校验 - 两定

            # 三定
            '千12345百12345十67890各0.1', # 0.已校验 - 三定
            '千12345百12345十67890三数合分2345各0.1', # 0.已校验 - 三定
            '头百尾23456789除各0.1', # 2.已校验 - 三定
            '头百尾23456789除双重取两兄弟各0.1', # 2.已校验 - 三定

            # 四定
            '千12345678百03456789四字定除双重除二兄弟', # 4.已校验 - 四定
            '千12345678百03456789四字定合值10-25除双重取两兄弟', # 5.已校验 - 四定
            '千12345678百03456789四字定合值10-25除两双重取兄弟除四重除三重', # 6.已校验 - 四定

            # 倒格式
            '1234倒四定各1', # 9 四定 -  已校验
            '123倒三定各1', # 10 三定 -  已校验
            '123倒两定各1', # 11 二定 -  已校验
            '123458倒两定各1', # 12 二定 -  已校验
            '123456倒三定各1', # 13 三定 -  已校验

            # 现、 走移 - 暂未完成 - 待实战加强
            '123千走345两定各1元', # 15 两定 - 已校验
            '123百走678两定各1元', # 16 两定 - 已校验
            '123百567千走678三定各1元', # 17 三定 - 已校验
            '123百走56790三定各1元', # 18 三定 - 已校验

            '千12345678百03456789四字定两数合45值范围15-35除双重除二兄弟', # 待定
            '千123456789百3456789四字定两数合45值范围15-35除双重除两二弟各0.1', # 待定
            '千02百1十08,千35百2十48',
            '千13百2十89,千48百3十57',
            //'千0123456789百13579十02468,千0123456789百13579个02468',
            //'千0123456789百13579十02468,千0123456789百13579个02468,千0123456789百02468十13579,千0123456789十13579个02468,千0123456789百02468个13579,千0123456789十02468个13579',
            //'千0123456789百12345十67890,千0123456789百12345个67890,千0123456789百67890十12345,千0123456789十67890个12345,千0123456789百67890个12345,千0123456789十12345个67890',
            //'千12345678百03456789四字定值15,16,19,20,21,23除双重除二兄弟', # 7待定 不支持和值用逗号隔离开，英文都好为不同组的分隔符
        ];
        $rst = NumService::getCodesByDesc($testData[18]);
        p($rst); # 主要入口
        $rst = NumService::getCodesHzByDesc($testData[6]);
        p($rst);
        $rst = NumService::getCodesHzByDesc("千12百345四字定两数合45值范围15-35除个1234除双重除二兄弟");
        p($rst);
        $rst = NumService::getSingleByDesc("千12百345四字定两数合45值范围15-35除个1234除双重除二兄弟各0.1");
        p($rst);
        p(date('w', strtotime('2021-06-30')));
        $rst = NumService::getCodesArrByNum($codes_str = '12345678', $num = 4);
        p($rst);
        $qihao = QxcTcw::getNineNineQihao($lottery_type = 1, $is_auto = 1) + 1;# 期号
        p($qihao);
        $data = NaSiDaKe::getLotteryNo($type, $is_auto = 1, $lottery_type = 22);
        $data = DaoQiongSi::getLotteryNo();
        p($data);
        $data = NaSiDaKe::getLotteryNo($type);
        p($data);
        $rst['updateDsYL'] = SscDataService::updateDsYL($lottery_type = 17);
        p($rst);// 更新单双遗漏
        $rst = BaseDataService::insertDsTypeDatas($lottery_type = 17);
        p($rst);
        $data = QxcTcw::getTcwOne($returnType = 'json', $is_auto = 0);
        p($data);
        $rst['bet'] = BetService::betByUidNew($uid = 11);
        p($rst); // 用户新计划投注，可正买可反买
        $data = ZhongFaService::userInfo($uid = 14, $tz_system_id = 16);
        p($data);
        $data = LeCaiService::getLotteryBatchGw($lottery_type = 18);
        p($data);
        $data = LeCaiService::getLotteryBatch($lottery_type = 18);
        $kjDatas = array_reverse($data);
        p($kjDatas);
        foreach ($kjDatas as $key => $dataInfo) {
            $rst = KjDataGet::insertKjData($dataInfo['expect'], 8, $dataInfo['opencode']);
        }
        p($rst);
        $data = LeCaiService::getLotteryByUser($type = 'json', $lottery_type = 18, $is_auto = 2);
        p($data);
        $params = ['_nowTime' => 1621735076794, '_uri' => '/orders/cancel-by-no'];
        $params = ['vol' => ''];
        $sign = ZhongFaService::getSign($params);
        p($sign);
        $rst = ZhongFaService::cancelOrder($bet_id = 31, $tz_system_id = 16);
        p($rst);
        $ZhongFaService = new ZhongFaService();
        $snInfo = ZhongFaService::getSn($uid = 14, $tz_system_id = 16, $lottery_type = 18);// 用户信息 Array ( [sn] => 403054677338701312 [qihao] => 190412023 [snid] => 31724311|1,31724312|1 )
        p($snInfo);
        $betRst = $ZhongFaService->repeatErrorBet($id = 14);
        p($betRst);
        $loginRst = ZhongFaService::login($uid = 14, $tz_system_id = 16);
        p($loginRst);
        $betService = new ZhongFaService();
        $betService->postBatchBetInsert('20210522203', $plan_id = 1805, '2,3,4,5@3,4,5,6');
        $rst = ZhongFaService::getCookie($uid = 14, $tz_system_id = 16);
        p($rst);
        $rst = ZhongFaService::synBalance(5);
        $params = ['_nowTime' => 1621657296359, '_uri' => '/session-user'];
        $sign = ZhongFaService::getSign($params);
        p($sign);
        $data = LeCaiService::getLotteryK5($type = 'json', $lottery_type = 18, $is_auto = 2);
        p($data);
        $lottery_type = 18;
        $qihao_1 = HN0898Service::getCurrentQihao($lottery_type);
        $qihao_2 = HN0898Service::getQihao($lottery_type);
        p([$qihao_1, $qihao_2]);
        p($rst);
        preg_match("/robot7=([^\r\n]*); Seven/i", $cookie, $matches);
        $new_cookie = str_replace($matches[1], $roboot_id, $cookie);
        p(['str' => $str, 'roboot_id' => $roboot_id, 'old_cookie' => $cookie, 'matches' => $matches, 'new_cookie' => $new_cookie]);
        p($roboot_id);
        p($_SERVER);
        $rst = StaticService::queryCodeTypeStatic($post);
        p($rst);
        $poxy_ip_data = '115.226.68.53:22598'; #
        $rst = PoxyIPService::isValid($ips = [$poxy_ip_data], $is_auto = 0);
        d($rst);
        $isValidRst = PoxyIPService::kuaiIPValidTime([$poxy_ip_data]);
        p($isValidRst);
        $m = \Yii::$app->cache;
        $mkey = PoxyIPService::builProxyIpKey($mod_uid = 0);
        $rst = $m->get($mkey);
        p($rst);
        //$rst = $m->delete($mkey);p($rst);
        $str = '<script language=\'javascript\'>document.domain = document.domain; parent.onprogress(1, \'0\')</script><script language=\'javascript\'>document.domain = document.domain; parent.onprogress(1, \'0\')</script><script language=\'javascript\'>document.domain = document.domain; parent.onprogress(0, \'end\')</script><script language=\'javascript\'>document.domain = document.domain; parent.onprogress(0, \'end\')</script>{"Status":1,"Data":{"CompletedStatus":1,"LackStatus":0}}';
        $rst = WxService::syncCheckTask($uid = 18);
        p($rst);
        $miss = SscDataService::staticPeiShuDateProfits($lottery_type = 8, '2021-01-28');
        p($miss);
        $codes_hz = ['ps_1' => 147, 'ps_2' => 369];
        $codes = NumService::getCodesKuaiXuan($codes_hz, $code_type = 4);
        p($codes);

        $rst = StaticService::getCreatePeiShuCodeTypeSql($type = 1);
        p($rst);
        $rst = StaticService::getCreatePeiShuTrueFalseSql();
        p($rst);
        $rst = Lucky5Service::getQihaoInfo($uid = 10, $tz_system_id = 9, $lottery_type = 8);
        p($rst);
        $str = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx16d295b4421899d4&redirect_uri=http%3A%2F%2Fgoapi.hngoshare.com%2Fpay%2Findex%2Fmiddle-redirect%3Fparams%3D%252Fweixin%252Fwechat.html%253Forder_sn%253D2021011610424878811%2526&response_type=code&scope=snsapi_base&state=STATE&connect_redirect=1#wechat_redirect";
        p(urldecode($str));
        $data = QxcTcw::QixingCaiBatch($type = 'json', $post['is_auto'] = 0);
        p($data);
        $rst = BaoTaService::updateUserBetStatus($id = 86, $is_auto = 2);
        p($rst);

        $rst = BaoTaService::updateCrontabStatus($id = 46);
        p($rst);
        //$rst = BaoTaService::btLogin($id=1);p($rst);
        //$rst = BaoTaService::getCronTabs($id=1);p($rst);
        $rst = BaoTaService::getUserInfoByCrontabName('用户【id:30-as07】- 男同学 - 投注计划');
        p($rst);
        $rst['visitIndex'] = BaoTaService::visitHomePage($id = 1);
        p($rst);
        $rst = BaoTaService::getCronTabs();
        p($rst);
        $snInfo = JuHuaBaseService::getSn($uid = 35, $tz_system_id = 11);
        p($snInfo);// 用户信息 Array ( [sn] => 403054677338701312 [qihao] => 190412023 [snid] => 31724311|1,31724312|1 )
        $data = JiaNaDa::getLotteryCanada($type = 'json', $is_auto = 2);
        p($data);
        $data = JiaNaDa::getLottery($type = 'json', $is_auto = 2);
        p($data);
        //PoxyIPService::delProxyUidsKey();
        $rst = PoxyIPService::getProxyUids();
        p($rst);
        p($rst);
        $http = 'http://120.77.157.40:8090/forum/user/';
        p(trim($http, '/'));
        $data = BingDao::getLotteryOne($type = 'json', $l_type = 6);
        p($data);
        $data = CqsscKcw::getLotteryBg($type = 'json', $is_auto = 0);
        p($data, 0);
        $snInfo = BingDaoService::getSn($uid = 12, $tz_system_id = 13, $lottery_type = 13);
        p($snInfo);// 用户信息 Array ( [sn] => 403054677338701312 [qihao] => 190412023 [snid] => 31724311|1,31724312|1 )
        $rst = BingDaoService::login($uid = 12, $tz_system_id = 13);
        p($rst);

        set_time_limit(0);
        $rst = JinYingService::getBalance('18', '15');
        p($rst);
        $rst['rst'] = BaseService::synBalance($tz_system_users_id = 66);
        p($rst);
        $rst['updateDsYL'] = SscDataService::updateDsYL($lottery_type = 1);
        p($rst);// 更新单双遗漏
        $rst = HN0898Service::insertDsYl($lottery_type = 1);
        p($rst);# 和值、四定利润统计
        for ($i = 0; $i < 1000; $i++) {
            $rst['updateDs'] = SscDataService::updateDsData($lottery_type = 6); // 每期开奖遗漏 -- 新开
        }
        p($rst);
        $data = QxcTcw::getTcwOne();
        p($data);

        $data = XjSsc::cg($type = 'json', $is_auto = 0);
        p($data);
        $data = XjSsc::huangGuan($type = 'json', $is_auto = 0);
        p($data);
        $data = XjSsc::fuLiCai($type = 'json', $is_auto = 0);
        p($data);
        $data = XjSsc::NineNineNew();
        p($data);
        $m = \Yii::$app->cache;
        $mkey = BetService::buildBeforeAndAfterBetKey($lottery_type = 6, $qihao = '2020120603', $uid = 11);
        $r = $m->get($mkey);
        p([$mkey, $r]);
        $rst = NineNineNewService::getBalance($uid = 18, $tz_system_id = 12);
        p($rst);
        $loginRst = NineNineNewService::login($id = 18, $tz_system_id = 12);
        p($loginRst);
        $redis = \Yii::$app->redis;
        p($redis);

        $rst = NineNineNewService::synBalance($tz_system_users_id = 31);
        p($rst);
        d(false === '');
        $kjData = BingDao::getLotteryOne('json', $l_type = 7);
        p($kjData);
        $rst = BingDaoService::synBalance($TzSystemsUser_id = 66);
        p($rst);
        $rst = BingDaoService::userInfo($uid = 20, $tz_system_id = 13);
        p($rst);
        $varifyCode = BingDaoService::getVerifyCodeByCaptchCodeRst($captchaCodeRst = '0129487653', $code = '463');
        p($varifyCode);
        $balance = BingDaoService::getBalance($uid = 20, $tz_system_id = 13);
        p($balance);
        $data = CqsscKcw::getLotteryTaiwanBinguo();
        p($rst); # 开奖抓取
        $lottery_types = StaticService::getLotteryTypes();

        p($rst);
        $rst = JuHuaBaseService::getHomePage($tz_sites = 18, $uid = 11, $lottery_type = 9);
        p($rst);
        $rst = JuHuaBaseService::selectLottery($tz_sites = 18, $uid = 11, $lottery_type = 9);
        p($rst);
        $qihao = HN0898Service::getCurrentQihao($lottery_type = 9);
        p($qihao);
        $data = CqsscKcw::getLotteryKuaiLe8Eight();
        p($data);

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $rst = StaticService::static4dYlCode();
        return $rst;
        $data = CqsscKcw::getLotteryKuaiLe8NineNine();
        p($data);
        $str = '__cfduid=d15af3e060312ef9820a04d9b033fdf901601979639; MC=c7b87edc5f6ad9803b363967dba8f83f; MCLIST=f4-c7b87edc5f6ad9803b363967dba8f83f%7Cf5-1001c9d2b2ca3fe6486276f3084f2536%7Cf2-505955572a46981f3a34eb2b10f8d9d9%7Cf1-dd065cafe26982584e35b83652a7b855%7Cf3-6e8a6c621ac150f3146ea1b415f5c079; LC=f3%7Cf1%7Cf2%7Cf5%7Cf4; sel_lotdefid=8';
        preg_match('/sel_lotdefid\=[1-9]/i', $str, $mathes);
        p($mathes);
        $rst = BaseService::synBalance(51);
        p($rst);
        $rst = TennisSportsService::grabTennisSportsGame();
        p($rst);
        $rst = \backend\service\Mbs188\tennis\TennisService::getGames();
        p($rst);
        $rst = StaticService::staticCodeTypeProfitsDate($date = '2020-09-08', $lottery_type = 5);
        p($rst);
        $rst = NineNineNewService::getDifferentNums();
        p($rst);
        $rst = StaticService::static2NumsYl($lottery_type = 5);
        p($rst);
        $rst = SscDataService::update3NumYL($lottery_type = 6);
        p($rst);
        $dates = [];
        $tmp_date = strtotime('2020-07-01 00:00:00');
        $i = 0;
        for ($i; $i < 26; $i++) {
            $dates[] = date('Y-m-d', $tmp_date + $i * 86400);
        }
        $rst = SscDataService::getPlaywayByCodes();
        p($rst);// 单双遗漏
        $rst = SscDataService::getBetNums();
        p($rst);// 单双遗漏
        $rst['updateDsYL'] = SscDataService::updateDsYL($lottery_type = 8);
        p($rst);// 单双遗漏
        $miss = StaticsQxMissService::getDsHistoryMiss($num, '1,2,3,4', $lottery_type = 5, 5000);
        p($miss); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
        $UserSysPlans = UserSysPlans::findOne($plan_id = 6);
        $flag = BetService::getIsBetTrue($UserSysPlans);
        d($flag);
        $flag = BetSe($plan_id = 6, $istest = 0);
        d($flag);# 上期是否中奖，第一次下注认为是上期不中 中则投
        $flag = SscDataService::isZjBefore($plan_id = 6);
        d($flag);# 上期是否中奖，第一次下注认为是上期不中 中则投
        p(urldecode('czt_openinfo=%257B%2522uid%2522%253A%252211599625%2522%252C%2522token%2522%253A%25224584d583f1730a193e6d0ccc3f8a8cad%2522%257D'));
        $str = '01234,13579,X,01234@01234,X,13579,01234@01234,13579,X,02468@01234,X,13579,02468@02468,X,13579,01234@02468,13579,X,01234@02468,13579,X,02468@02468,X,13579,02468';
        p(substr_count($str, 'X'));
        $codes_hz = '{"p1":"123","p2":"345","p3":"569","p4":"6589","p5":"1234"}';
        $codesArr = NumService::getOneFixedCode(json_decode($codes_hz, true));
        p($codesArr);
        $rst = NumService::staticPlansProfits();
        p($rst);

        $domain = 'f2.ww835566.xyz';
        // ping域名
        p(Tools::getPingAddressInfo($domain));
        p(Tools::getTelnetAddressInfo($domain));
        d(Tools::pingAddress($domain));
        // ping IP
        //var_dump(pingAddress('45.33.36.121'));

        if (Tools::pingAddress($domain) == true) {
            $ip = gethostbyname($domain);//获取域名ip
        }
        p($ip);
        //system('/tmp/cron/test.sh');p('xx');

        $s = 0;
        if (empty($s)) p('xx'); else p('yy');
        $str = '1234   3346 1267 9021
        2356
        2345 9234';
        //$str = '1234 2354 6457 1226';
        $codesData = $str;
        $rst = preg_replace('#\s+#', ' ', $str);
        p($rst);
        $rst = PoxyIPService::kuaiIPValidTime(['116.115.210.176:16092', '121.56.39.180:20749']);
        p($rst);
        $data = XjSsc::getLotteryNoNineNum();
        p($data);
        $str = "/App/ClearSession?errMsg=%e6%82%a8%e7%9a%84%e8%b4%a6%e5%8f%b7%e5%b7%b2%e5%9c%a8%e5%88%ab%e5%a4%84%e7%99%bb%e5%bd%95%e3%80%82";
        p(urldecode($str));
        $rst = UserSysPlansService::getYLByPlanId($plan_id = 934);
        p($rst);
        $snInfo = LuckyBaseService::getSn($user_id = 17, $tz_system_id = 9);
        p($snInfo);// 用户信息 Array ( [sn] => 403054677338701312 [qihao] => 190412023 [snid] => 31724311|1,31724312|1 )
        $mcLock = new McLockService();
        $flag = $mcLock->Lock('dw');
        d($flag);
        $flag = $mcLock->isLock('dw');
        d($flag, 0);

        $mcLock->Lock('dw');
        $flag = $mcLock->isLock('dw');
        d($flag);
        $rst = PoxyIPService::kuaiPoxyExpire();
        p($rst);

        $id = 43;
        $rst = SevenService::synBalance($id);
        p($rst);
        $rst = strpos('上100', '上');
        d($rst === 0);
        $rst = ChatCommonBetService::getLotteryTypeByToken($token = '784bfe044b30');
        p($rst);
        $desc = '上50';
        $type = ChatCommonBetService::getTypeByDesc($desc);
        p($type);

        $rst = ChatCommonBetService::upOrDownBalance($desc);
        p($rst);
        $rst = ChatCommonBetService::betByDesc($token = 'e221d63e7d00', $desc = '千123456789百123456789十123456789个123456789各0.1');
        p($rst);
        $rst = BaseDataService::insertCodeType();
        p($rst);
        $rst = JuHuaBaseService::getBetCodes(['2123', '3457', '7892', '3029', '3X09', '3424'], $single = 0.1, $playway = 3);
        p($rst); # 同步余额
        $rst = JuHuaBaseService::getBetCodes(['X123', 'X457', 'X892', '30X9', '3X09', '34X4'], $single = 0.1, $playway = 2);
        p($rst); # 同步余额
        $rst = JuHuaBaseService::synBalance(21);
        p($rst); # 同步余额
        //$data['rst'] = ChatService::send();p($data);
        $loginRst = BaseService::login($id = 47);
        p($loginRst);
        $rst = NumService::getCodesArise(['0144']);
        p($rst);
        $rst = SevenService::userInfo(18, 3);
        p($rst);
        $rst = SevenService::getSn(18, 3);
        p($rst); # 用户信息

        $time = BetService::getBetCacheTime($lottery_type = 5, $qihao = '200319036');
        p($time);# 投注之后缓存时间
        $rst = OperatePlanService::getPlanNextSingle(52, 0, $next_single_key, $lottery_type = 5);
        p($rst);
        $qs = SscDataService::getLossQs(52);
        p($qs);
        $rst = BetService::isLogin($uid = 20, $tz_system_id = 9);
        d($rst);

        $rst = BaseDataService::insertCodeType2();
        p($rst);


        $rst = md5(md5('0n8J5h9sfkxofRI9wy010203'));
        p($rst);
        $qihao = HN0898Service::getQihao($lottery_type = 8);
        p(['即将开奖期号' => $qihao, 'lottery_type' => $lottery_type]);
        $rst = BaseDataService::insertCodeType5();
        p($rst);
        $str = '0,9,1,0';
        $rst = CommonService::isCodeType22b($str);
        p($rst);

        $rst = BaseDataService::insertCodeType3();
        p($rst);
        $rst = CommonService::isCodeType2b('9,1,1,X');
        p($rst);
        $rst = BetService::tzByPlanId(24, 0);
        p($rst); # 投注
        $rst = SevenService::synBalance(29);
        p($rst);
        $rst = LuckyBaseService::synBalance($tz_system_users_id = 29);
        p($rst);# 同步余额

        $rst = bin2hex("Shanghai");
        p($rst);
        $rst = QiLinBaseService::synBalance(26);
        p($rst);
        $rst = QiLinBaseService::userInfo($uid = 18, $tz_system_id = 8);
        p($rst);
        p(microtime(true));
        $rst = NineNineBaseService::getRemoteHzRecords($uid = 11, $tz_system_id = 2, $lottery_type = 6);
        p($rst);
        $rst = StaticService::getStaticCodeType2($lottery_type = 5);
        p($rst);
        $num = ['1122', '1212', '1221', '2112', '2121', '2211'];

        $codes = '';
        for ($i = 0; $i < 10; $i++) {
            for ($x = 0; $x < 10; $x++) {
                $codes .= $i . ',' . $x . ',X,X@';
            }
        }
        p(trim($codes, '@'));
        p(3 % 5);
        $rst = BaseDataService::insertCode($type = 5);
        p($rst); # 插入三字现、四字现
        $rst = StaticService::staticHzPerDateProfits('2019-10-31', $lottery_type = 5);
        p($rst);
        $rst = CommonService::isCodeType_2($codes = '3,3,3,X');
        p($rst);
        $rst = NumService::delByValue(['1', 'X', '3', 'X'], 'X');
        p($rst);
        $rst = BetService::isCanBet($lottery_type = 5);
        p($rst);
        $rst = CommonService::isCodeType3n2b('0,0,5,6');
        p($rst);
        $rst = CommonService::isCodeType3n2b('1,2,3,4');
        p($rst); # 三现:双重+兄弟
        $rst = UserSysPlansService::getCodeTypes();
        p($rst);
        $miss = SscDataService::getCodeTypeHistoryMiss('type_4b', $lottery_type = 5, $static_nums = 20000);
        p($miss); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
        $rst['allDateStatic3nPerMonth'] = StaticService::allDateStatic3nPerMonth($lottery_type = 6);
        p($rst); # 三现每月统计
        $rst = StaticService::staticKj3NCounts('2019-10', $lottery_type = 5);
        p($rst);
        $rst['allDateStatic4nPerMonth'] = StaticService::allDateStatic4nPerMonth($lottery_type = 5); # 部分四现每月统计
        $rst = StaticService::getCreateCodeType3nSql($lottery_type = 5);
        p($rst);
        $rst = StaticService::getCreateCodeType4nSql($lottery_type = 5);
        p($rst);
        $miss = SscDataService::getCodeTypeYlHistoryMiss('555', $lottery_type = 5, 20000);
        p($miss);
        $rst = StaticService::static2NumsYl($lottery_type = 8);
        p($rst);

        //$str = '{"Status":1,"Data":{"CompletedStatus":1,"LackStatus":0}}'; //p(json_decode($str, true)); d(strpos($str, "\"Status\":1") !== false);

        $rst = TzService::insertLuckyDataTime();
        p($rst);
        p(unserialize('a:3:{s:4:"time";i:1570224883;s:3:"ttl";i:3600000;s:4:"data";a:0:{}}'));
        $rst = CqsscKcw::getLotteryNoZhiBo();
        d($rst);
        $data = CqsscKcw::getLotteryNoOneNineNineEight($type = 'xml');
        p($data);
        $profits = SscDataService::getSomeDatesBeforedProfits($lottery_type = 5);
        p($profits);
        $profits = SscDataService::getProfitsBeforeProfitsByQihao($qihao = '190929001', $beforeQishus = 400, $lottery_type = 5);
        p($profits);
        $rst = TzService::tz();
        p($rst);// 计划投注
        $codesArr = NumService::getNotLatelyCodes(['lately_start' => 0, 'lately_end' => 400]);
        p($codesArr);
        $rst = SscDataService::calulateBeforeProfits();
        p($rst); # 统计前面多少期号码的中奖利润
        $rst = StaticService::staticSDPerDateProfits(date('Y-m-d'));
        p($rst);
        $rst = NumService::getCodesKuaiXuan(['type_4' => 0, 'type_2' => 1, 'type_4d' => 1]);
        p($rst);


        $rst = StaticService::staticAll2NumsYl();
        p($rst); # 统计所有二字现遗漏
        $rst = BetService::bet();
        p($rst);// 用户新计划投注，可正买可反买
        $data = XjSsc::batchSevenDay();
        p($data);
        $rst = BaseNumService::getRepeat4Codes22();
        p($rst);

        $rst[] = StaticService::static4dPerDateProfits($lottery_type = 6);
        p($rst); # 每天四定利润统计，四定类型详见：StaticService::$typeArr
        $snid = NineNineBaseService::getSnidBySn('JXSSC1909201535157573FFE1', $lottery_type = 6);
        p($snid);// 获取方案内容
        $rst = HN0898Service::getRemoteHzRecords(3, 2);
        p($rst);
        for ($i = 0; $i < 20; $i++) { # 统计数据
            //$rst['allDateStaticCodeTypePerDate'] = StaticService::allDateStaticCodeTypePerDate($lottery_type = 6); //p($rst);# 号码类型每天数量统计
            $rst['allDateStaticHzPerDate'] = StaticService::allDateStaticHzPerDate($lottery_type = 6); //p($rst);# 和值每天数量统计
        }
        p($rst);
        self::_init();
        $kjDatas = XjSsc::getLotteryNoBatch();
        $kjDatas = array_reverse($kjDatas);
        p($kjDatas, 0);

        $data = XjSsc::getLotteryNoBatch();
        $data = array_reverse($data);
        p($data);
        $data = XjSsc::getLotteryNoZhiBo();
        p($data);
        $data = XjSsc::getLotteryNoSevenDay();
        p($data);
        $data = XjSsc::getLotteryNo99();
        p($data);
        for ($i = 1; $i <= 59; $i++) {
            $qihao = 190917000 + $i;
            $rst = SscDataService::insertSscKjDataDs($qihao);//p($rst);
        }
        p($rst);
        $rst['opStaticSdProfitsMonth'] = StaticService::opStaticSdProfitsMonth($lottery_type = 5);
        p($rst);# 单双利润统计(month)
        $rst['allDateStatic3NumsPerDate'] = StaticService::allDateStatic3NumsPerDate($lottery_type = 7);
        p($rst); # 上奖三字现
        $rst = StaticService::get2NumsYlRecords('66', $lottery_type = 7);
        p($rst);
        $post = \Yii::$app->request->post();
        p($post);
        $statics = StaticService::staticKj3NumCounts($date = '2019-09-01', $lottery_type = 5);
        p($statics);
        set_time_limit(0);
        # 三字现带双重
        //$rst['updateCodeTypeYLs5'] = SscDataService::updateCodeTypeYLs($type = 5, $lottery_type = 5); p($rst);
        $rst = CommonService::getLotteryName();
        p($rst);
        $arr = 0;
        p(empty($arr));
        $arr = ['海南省内包邮'];
        //$str = 'a:1:{i:0;s:18:"海南省内包邮"}';
        p(serialize($arr));
        $rst = BaseDataService::insertStaticVal();
        p($rst);
        $rst = HuiYuanService5::loginNew(18, 6);
        p($rst);
        $rst = NumService::getCodesKuaiXuan(['type_log' => '1']);
        p($rst);
        p($rst);
        $rst = HN0898Service::getCurrentQihao(7);
        p($rst);
        $rst = HN0898Service::getQihao(7);
        p($rst);
        $rst = SscDataService::clearDataTables();
        p($rst);
        $rst = HN0898Service::getDifferentNums();
        p($rst);
        $rst = TzService::insertKuaiLe8DataTime();
        p($rst);
        $qihao = HN0898Service::getQihao($lottery_type = 5);
        p($qihao);
        $rst = StaticService::getNiceCodes(5);
        p(['最优号码[四现不带双]' => $rst]);
        $rst = SscDataService::getCodesDS('1,2,3,4,5');
        p($rst);
        $rst = StaticService::staticPerHzProfits('2019-03');
        p($rst); # 某月份每个和值利润统计
        if ($status = StaticService::isCanOpStatic($lottery_type = 5, $mkey = 'opStatic')) {
            p('xxxx');
        }
        p(rand());
        $rst = StaticService::staticSdHzProfitsPerdate();
        p($rst); # 每天每个和值利润统计


        $rst = NumService::getCodesArise(['003']);
        p($rst); //2+3+1+2+2
        $codesArr = [9, 7, 9, 8];
        $code_3n = CommonService::get3n($codesArr);
        p($code_3n);
        $rst = StaticService::staticHzCounts('2019-06-12', $lottery_type = 5);
        p($rst);
        //$data = '{"type_3":"1","type_22":"0","type_2b":"1","type_4b":"1","arise":"12345","p1":"3456","p2":"345679","p3":"89734","p4":"56092"}';
        //$rst = NumService::getCodesKuaiXuan(['type_2'=>1, 'type_3'=>1, 'hz'=>[30,31,32,33,34,35]]);p($rst);
        $domain = BaseKj::getApiHost(8);
        p($domain);
        p('xxx');
        $rst = NumService::getCodesArise(['9377']);
        p(count($rst));
        $arr = ['type_2b' => 1, 'hz' => [11, 12, 13, 14, 15, 16, 24]];
        p(json_encode($arr));
        $rst = NumService::getCodesKuaiXuan(['type_2' => 1, 'hz' => [8, 28]]);
        p($rst);
        $qihao = HN0898Service::getQihao($lottery_type = 6);
        p($qihao);
        $rst = TzService::insertSscDataTime(6);
        p($rst);
        $account = AdminModel::findOne(11)['username'];
        p($account);
        $rst = StaticService::calculate2bProfits($lottery_type = DEFAULT_LOTTERY_TYPE, $start_date = '2019-05-01', $end_date = '2019-05-15');
        p($rst);
        for ($i = 0; $i < 100; $i++) {
            $rst['update3NumData'] = SscDataService::update3NumData($lottery_type = 5);
        }
        p($rst); // 每期开奖遗漏
        $qihao = HN0898Service::getQihao(5);
        $rst = BetService::getBetCacheTime($lottery_type = 5, $qihao);
        p($rst);# 投注之后缓存时间
        $qihao = HN0898Service::getQihao(5);
        p($qihao);
        $rst['update3NumData'] = SscDataService::update3NumData(5);
        p($rst); // 每期开奖遗漏

        $rst = NumService::filterLaterCodesAnd2bcode(5, $qihao = '190516056');
        p($rst);
        $rst = NumService::getRecentlyCodes(5);
        p($rst);
        $captchaCodeRst = Tools::getCaptchaCode(10, 5, '2x2tdrnawlpbli554jlsuf2c');
        p($captchaCodeRst); # 真实调用验证码接口，收费
        $rst = XlService::login(10, 5);
        p($rst); # 7时登录
        $rst = HN0898Service::login(10, 5);
        p($rst); # 7时登录
        $rst = XlService::formCodesStyle('13579,X,X,X@02468,X,X,X', 4);
        p($rst); # 格式化希腊号码
        $rst = XlService::formCodesStyle('13579,,13579,,@13579,,13579,,', 1);
        p($rst); # 格式化希腊号码
        $rst = XlService::getQihaoInfo(10, 5);
        p($rst);
        $rst = HN0898Service::getQihao(2);
        p($rst);
        $bettingRecords = BettingRecords::find()->alias('bet')->where(['bet.status' => 0])->distinct('qihao')->orderBy('bet.qihao ASC')->limit(20)->all();
        p($bettingRecords);
        $rst = CqsscKcw::getLotteryNoXl();
        p($rst);
        $rst = HN0898Service::getQihao();
        p($rst);
        //$rst = NumService::getCodesArise(['289','125','046','456','589','467']);p($rst); //2+3+1+2+2
        //$rst = NumService::getCodesArise_bak(['12345']);p($rst);
        $rst = StaticService::staticKj3NumCounts();
        p($rst);
        $arr = [['reach_val' => 100, 'reduce_val' => 10], ['reach_val' => 300, 'reduce_val' => 50]];
        p(json_encode($arr));
        $rst = BetService::userSysPlansTzNow(81, 3);
        p($rst);
        $rst = CqsscSevenDay::getLotteryNo();
        p($rst);
        $rst = StaticService::getSameCodes('1221', 1);
        p($rst);
        //p(base64_decode('1324%E5%85%A8%E5%80%92%E5%9B%9B%E5%AE%9A%E5%90%840.1'));

        p([base64_decode('OTA1Mjg2MTM1MzI3Ng=='), base64_decode('MjI5OTE2MTM0MTQ2MQ=='), base64_decode('MjA4ODY2MTM1MzI4Nw==')]);
        //$rst = HN0898Service::getTzList(3, 2);p($rst);
        $rst = NumService::get2bCodeArr();
        p($rst);
        $rst = StaticService::static4DHzProfits('2019-03-01', '2019-03-29', 6);
        p($rst);
        $rst = NumService::getSystemTzHz(6, '190401023', 1);
        p($rst);
        $rst = [];
        for ($i = 190329001; $i <= 190329019; $i++) {
            $rst[$i] = NumService::getRemoveCodes($i, 2000);
        }
        p($rst);
        $rst = StaticService::static4DdsLastTime();
        p($rst);
        $rst = StaticService::opStaticProfits();
        p($rst);
        $post = \Yii::$app->request->post();
        $rst = SscDataService::getSDYL();
        p($rst);
        $rst = SscDataService::countZj();
        p($rst);
        $rst = SscDataService::countCodes();
        p($rst);
        $rst = UserCustomPlansService::insertSDPlans();
        p($rst);
        //$rst = StaticService::allMonthStaticProfits();p($rst); # 利润统计
        $rst = BaseDataService::insert4dDsZHData();
        p($rst);
        $m = \Yii::$app->cache;
        $qihao = HN0898Service::getQihao();
        $mkey = \Yii::$app->params['TZ_SWITCH_SIMULATE_KEY'] . '_' . $qihao;
        $r = $m->set($mkey, 1, 10 * 60);
        //$rst = StaticService::staticSDProfits();p($rst); # 利润统计
        $rst = StaticService::staticProfits($playway = 3, 3600 * 3, 0);
        p($rst);
        $rst = WxService::sendMsg();
        p($rst); # 群发微信消息
        $rst = CqsscKcw::getLotteryNo();
        p($rst);
        $rst = HN0898Service::getQihao();
        p($rst);
        $rst = SscDataService::calcDsProfit();
        p($rst); // 单双遗漏计算
        $rst = TzService::tz();
        p($rst); // 计划投注
        $rst = SscDataService::calTzTotalMoney('02468,X,13579,13579', 0.1, 2);
        p($rst);
        $rst = UserCustomPlansService::joinDs3DwPlans();
        p($rst);
        $rst = CommonService::getAwardNumberByQihao('181106022');
        p($rst);
        $rst = SscDataService::getSscKjData0898('181106021');
        p($rst); // 每期开奖遗漏
        $m = \Yii::$app->cache;
        $mkey = 'TZ_SWITCH_STATUS_181029073';
        $rst = $m->get($mkey);
        p($rst);
        $rst = UserCustomPlansService::joinDs3DwPlans();
        p($rst); // 用户加入三字定单双计划
        $rst = SscDataService::calcDsProfit();
        p($rst); // 所有遗漏中每组单双遗漏次数计算
        $qihao = HN0898Service::getQihao();
        p($qihao);
        $qihao = '180922014';
        $date = '2018-' . substr($qihao, 2, 2) . '-' . substr($qihao, 4, 2);
        p($date);
        $rst = TestService::getWeek257('2017');
        p($rst);
        $rst = TzService::getCustomPlansTzStatus(12);
        p($rst);   // 获取投注状态
        $rst = TzService::opPreUserFollowData(3);
        p($rst);  // 预处理插入计划表的数据
        set_time_limit(0);
        //$flag = SscDataService::insertSscKjDataDs('180813023');p($flag);
        //$rst = SscDataService::update3NumData();p($rst); // 每期开奖三字现统计
        $start_time = time(true);
        //$rst = SscDataService::update3NumData();p($rst); // 每期开奖遗漏
        $nums = [4, 5, 6, 6];
        $rst = CommonService::get3x($nums);
        p($rst);
        $rst = CommonService::get3x($nums);
        p($rst);
        $interval = 20;
        $rst[$interval] = SscDataService::dsYLStatic($interval);
        p($rst);
        $zuHes = [
            //[1,2],
            [1, 3],
            [1, 4],
            [2, 3],
            [2, 4],
            //[3,4],
        ];

        $data = [];
        //$rst = BaseNumService::startAndEndNumHeZhi();
        //$zuHeCode = BaseNumService::heZhiByPosition($qihao); // 某一期
        //$rst1 = BaseNumService::getHeZhiByPosition(20,2,4);
        if (true) {
            $numsArr = [6, 8, 9];//[8,9,10,11,12,13];
            foreach ($zuHes as $key => $zuHe) {
                foreach ($numsArr as $k1 => $num) {
                    $data[$key]['code_' . $num . '_' . $zuHe[0] . '_' . $zuHe[1]] = BaseNumService::dwZuHe([$zuHe[0], $zuHe[1]], [$num]);
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

        p($data);
    }

    public static function dealTime($a)
    {
        $time = strtotime($a);
        return $time;
        $year = substr($a, 0, 4);
        $month = substr($a, 4, 2);
        $day = substr($a, 6, 2);
        $hour = substr($a, 8, 2);
        $min = substr($a, 10, 2);
        $sec = substr($a, 12, 2);
        return mktime($hour, $min, $sec, $month, $day, $year);
    }

    public function actionSetEmptyMobiles()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        Tool_Common::log('/jd/'.__FUNCTION__, 'INFO', '京东手机号 ', ['post'=>$post]);

        return ['code'=>200, 'msg'=>'操作成功'];
    }

    public function actionGetEmptyMobiles(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $data = [
            '293086688452',
        ];

        return ['code'=>200, 'data'=>$data, 'msg'=>'成功'];
    }

}
