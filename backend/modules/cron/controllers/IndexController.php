<?php
/**
 * Created by PhpStorm.
 * User:wangyegao
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\cron\controllers;

use backend\models\SystemConfig;
use backend\models\SystemType;
use backend\models\TzSystems;
use backend\models\TzSystemsUsers;
use backend\models\User;
use backend\service\BaseService;
use backend\service\huiyuan\HuiYuanBaseService;
use backend\service\Juhua\JuHuaBaseService;
use backend\service\KuaiLe8Service;
use backend\service\Lucky5\LuckyBaseService;
use backend\service\McKeyService;
use backend\service\NineNine\NineNineBaseService;
use backend\service\NumService;
use backend\service\plans\BetErrorPlansTaskService;
use backend\service\PoxyIPService;
use backend\service\qilin\QiLinBaseService;
use backend\service\SevenService;
use backend\service\sports\TennisSportsService;
use backend\service\SscDataService;
use backend\service\TzService;
use common\service\CommonService;
use Yii;
use backend\models\SscKjData;
use backend\service\OpKjService;
use common\tools\KjDataGet;
use yii\web\Controller;
use backend\service\HN0898Service;
use backend\models\BettingRecords;
use common\tools\Tool_Common;
use backend\service\BetService;
use backend\service\StaticService;


class IndexController extends Controller
{
    private static $staticStatus = 0;
    private static function _init()
    {
        self::$staticStatus = SystemConfig::findOne(['key'=>'static_status'])->value;
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    }

    /**
     * @desc 时时彩：抓取全量开奖数据
     * @return bool
     */
    public function actionGrabKjData()
    {
        ini_set('memory_limit','1024M'); //升级为1024M内存
        self::_init();
        $rst = KjDataGet::grab($date_start = '20180101');

        return $rst;
    }

    /**
     * @desc 时时彩：逐期获取开奖数据
     * @return array
     */
    public function actionGrabKjDataOne(){
        self::_init();
        $post = \Yii::$app->request->post();
        if($post['lottery_types'][0] == 10){
            $for_times = 33;
            $sleep_time = 2;
        }else{
            $for_times = 8;
            $sleep_time = 5;
        }
        for ($i = 1; $i<$for_times; $i++) {
            $rst['kj'] = KjDataGet::grabOne($post['lottery_types']);
            sleep($sleep_time);
        }

        return $rst;
    }

    /**
     * @desc 统计
     * @return array
     */
    public function actionOpStatic(){
        self::_init();
        if(!self::$staticStatus) return ['status'=> 300, 'msg'=>'数据统计开关已关闭'];
        $start_time = microtime(true);
        $rst[] = StaticService::staticAll2NumsYl(); # 统计所有二字现遗漏
        $log_time = microtime(true);

        $rst['consume_time'] = ($log_time - $start_time).'s';

        return $rst;
    }

    /**
     * @desc 四定单双利润统计
     * @return array
     */
    public function actionStaticSdProfits(){
        self::_init();
        if(!self::$staticStatus) return ['status'=> 300, 'msg'=>'数据统计开关已关闭'];

        $rst = StaticService::opAllStaticProfits();

        return $rst;
    }

    /**
     * @desc 号码类型遗漏更新
     * @return array|mixed
     */
    public function actionUpdateCodeTypeYl(){
        self::_init();
        if(!self::$staticStatus) return ['status'=> 300, 'msg'=>'数据统计开关已关闭'];

        $post = \Yii::$app->request->post();
        $rst = StaticService::opAllCodeTypeYl($post['lottery_types']);

        return $rst;
    }

    /**
     * @desc 四定和值利润统计
     * @return array
     */
    public function actionStaticHzProfits(){
        self::_init();
        if(!self::$staticStatus) return ['status'=> 300, 'msg'=>'数据统计开关已关闭'];
        $post = \Yii::$app->request->post();
        $rst = StaticService::opStatic($post['lottery_types']); # 和值、四定利润统计

        return $rst;
    }

    /**
     * @desc 七星彩：逐期获取开奖数据
     * @return bool
     */
    public function actionGrabQxcKjData(){
        self::_init();
        for ($i = 1; $i<10; $i++){
            $rst = KjDataGet::grabQxc($is_all = 0);
            sleep(3*60);
        }

        return $rst;
    }

    /**
     * @desc 更新开奖表数据
     * @return array
     */
    public function actionUpdateNullCodes (){
        self::_init();
        set_time_limit(0);
        //$rst = NumService::staticPlansProfits();
        for ($i = 0; $i<10; $i++){
            $rst = KjDataGet::updateNullCode();
            /*
            $lottery_types = StaticService::getLotteryTypes();
            foreach ($lottery_types as $lottery_type) {
                $rst['updateDs'] = SscDataService::updateDsData($lottery_type); // 每期开奖遗漏 - 临时
                $rst['update3NumData'] = SscDataService::update3NumData($lottery_type); // 每期开奖遗漏 - 临时
            }
            */
            //sleep(2);
        }

        return $rst;
    }

    /**
     * @desc 号码和值投注记录
     * @return array
     */
    public function actionGrabTzHzList(){
        self::_init();
        $lottery_types = StaticService::getLotteryTypes();
        $TzSystemsUsers = TzSystemsUsers::findAll(['status'=>1, 'tz_system_id'=>[1, 2]]);
        foreach ($lottery_types as $lottery_type){

            foreach ($TzSystemsUsers as $TzSystemsUser){
                switch ($TzSystemsUser->tz_system_id){
                    case 1: # 0898
                    case 2: # 99 彩票网
                        if(in_array($lottery_type, [5, 6]))
                            //$rst = NineNineBaseService::getRemoteHzRecords($TzSystemsUser->uid, $TzSystemsUser->tz_system_id, $lottery_type); # 抓取号码
                        break;
                    case 3:
                        break;
                    case 4: # 北京快乐8
                        break;
                }
            }
        }
        return $rst;
    }

    /**
     * @desc 用户计划投注
     * @return array
     */
    public function actionBet(){
        self::_init();
        $time = date("H:i");
        /*
        if(\Yii::$app->params['ssc_kj_time_start'] <= $time && $time <= \Yii::$app->params['ssc_kj_time_end'] ){
            $rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
            return $rst;
        }
        */
        $rst['bet'] = BetService::bet(); // 用户新计划投注，可正买可反买

        return $rst;
    }

    /**
     * @desc 多线程跑用户计划
     * @return mixed
     */
    public static function actionBetByUid(){
        self::_init();
        $post = \Yii::$app->request->post();
        $uid = $post['uid'];
        if(in_array($uid, [20])){
            $for_times = 14;
            $sleep_time = 5;
        }else{
            $for_times = 8;
            $sleep_time = 5;
        }
        for($i=0; $i<$for_times; $i++){
            $rst['bet'] = BetService::betByUid($uid); // 用户新计划投注，可正买可反买
            sleep($sleep_time);
        }

        return $rst;
    }

    /**
     * @desc 多线程跑用户计划
     * @return mixed
     */
    public static function actionBetByUidXy(){
        self::_init();
        $post = \Yii::$app->request->post();
        $uid = $post['uid'];
        for($i=0; $i<10; $i++){
            $rst['bet'] = BetService::betByUidXy($uid); // 用户新计划投注，可正买可反买
            sleep(5);
        }

        return $rst;
    }

    /**
     * @desc 访问首页
     * @return mixed
     */
    public function actionVisitHomePage(){
        self::_init();
        $lottery_types = StaticService::getLotteryTypes();
        $TzSystemsUsers = TzSystemsUsers::findAll(['status'=>1]);

        foreach ($lottery_types as $lottery_type){
            foreach ($TzSystemsUsers as $tzSystemsUser){

                if($tzSystemsUser->tz_system_id == 11){
                    if($lottery_type != 9) continue;
                    # 是否有激活的计划
                    $hasActivePlan = CommonService::hasPlansActiveSys($tzSystemsUser->tz_system_id);
                    if(!$hasActivePlan){
                        return false;
                    }
                    $flag1 = JuHuaBaseService::getHomePage($tzSystemsUser->uid, $tzSystemsUser->tz_system_id, $lottery_type);
                    $flag2 = JuHuaBaseService::selectLottery($tzSystemsUser->uid, $tzSystemsUser->tz_system_id, $lottery_type);
                }
            }
        }

        return ['flag1'=>$flag1, 'flag2'=>$flag2];
    }

    /**
     * @desc 补打失败计划
     * @return array
     */
    public function actionRepeatBetErrorPlans(){
        self::_init();
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        $post = \Yii::$app->request->post();
        for ($i=0; $i<6; $i++){
            $rst[$i]['rst'] = BetService::repeatErrorBet($post['lottery_types']);
            sleep(3);
        }

        return $rst;
    }

    /**
     * @desc 统计数据处理
     * @return bool
     */
    public function actionInsertSscDwsHzNums(){
        exit; # 二字定数据太多，禁用

        for ($i=0;$i<1;$i++) {
            $start_time = microtime(true);
            $rst = SscDataService::sscDwsHzNums();
            $end_time = microtime(true);
            $time_consume = ($end_time-$start_time).'s';
            $logArr = ['start_time'=>$start_time,'end_time'=>$end_time, 'time_consume'=>$time_consume];
            Tool_Common::log('actionInsertSscDwsHzNums','INFO','统计区间某和值出现次数-执行时间', $logArr);
        }

        return $rst;
    }

    /**
     * @description 本地处理开奖数据
     * @return array
     */
    public function actionOpKj(){
        self::_init();
        $time = date("H:i");
        if(\Yii::$app->params['ssc_kj_time_start'] < $time && $time < \Yii::$app->params['ssc_kj_time_start'] ){
            $rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
            return $rst;
        }
        $lottery_types = StaticService::getLotteryTypes();
        foreach ($lottery_types as $lottery_type) {
            $rst = OpKjService::opSscKjData($lottery_type); # 在抓取完开奖数据已经调用 KjDataGet::grabOne
            //sleep(2);
        }

        return $rst;
    }

    /**
     * @desc 多线程跑用户计划
     * @return mixed
     */
    public static function actionReBetErrorPlans(){
        self::_init();

        $rst = BetErrorPlansTaskService::reBetErrorPlans();

        return $rst;
    }

    /**
     * @description 同步余额
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionSynBalance(){
        self::_init();
        $rst = ['status'=>200, 'msg'=>'同步余额'];
        $TzSystemsUsers = TzSystemsUsers::findAll(['status'=>1]);

        foreach ($TzSystemsUsers as $TzSystemsUser){
            $rst['rst'][$TzSystemsUser->id] = BaseService::synBalance($TzSystemsUser->id);
        }
        return $rst;
    }

    /**
     * @desc 自动登录
     */
    public function actionAutoLogin(){
        self::_init();
        $TzSystemsUsers = TzSystemsUsers::find()->where(['AND',['=', 'status', 1], ['<>', 'ssc_domain', '']])->all();

        foreach ($TzSystemsUsers as $TzSystemsUser){
            $rst[$TzSystemsUser->id] = BaseService::login($TzSystemsUser->id);
        }
        return $rst;
    }

    /**
     * @desc 缓存代理IP数据
     * @return array
     */
    public function actionCacheProxyIp(){
        self::_init();
        for ($i=0; $i<15; $i++){
            $multi_status = BetService::getConfig('MULTI_PROXY_STATUS');
            $mol_uid = $multi_status ? $i : 0;
            $rst = PoxyIPService::preGetValidIp($mol_uid);
            sleep(3);
        }

        return $rst;
    }

    /**
     * @desc 状态的开启跟关闭
     */
    public function actionSwitchStatus(){

    }

    /**
     * @desc 抓取网球赛事
     * @return array
     */
    public function actionGrabSportsGames(){
        self::_init();

        $rst = TennisSportsService::grabTennisSportsGame();

        return $rst;
    }

}