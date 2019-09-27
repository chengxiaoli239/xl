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
use backend\service\huiyuan\HuiYuanBaseService;
use backend\service\KuaiLe8Service;
use backend\service\McKeyService;
use backend\service\NineNine\NineNineBaseService;
use backend\service\SevenService;
use backend\service\SscDataService;
use backend\service\TzService;
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
        $date_time = date('H:i');
        if('03:30' < $date_time && $date_time < '07:10'){
            $rst = ['status'=>300, 'msg'=>'自动化投注时间关闭[03:10~07:10]'];
        }else{
            for ($i = 1; $i<5; $i++) {
                $rst['kj'] = KjDataGet::grabOne();
            }
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

        $rst = StaticService::opAllCodeTypeYl();

        return $rst;
    }

    /**
     * @desc 四定和值利润统计
     * @return array
     */
    public function actionStaticHzProfits(){
        self::_init();
        if(!self::$staticStatus) return ['status'=> 300, 'msg'=>'数据统计开关已关闭'];
        $rst = StaticService::opStatic(); # 和值、四定利润统计

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
        for ($i = 0; $i<10; $i++){
            $rst = KjDataGet::updateNullCode();
            /*
            $lottery_types = StaticService::getLotteryTypes();
            foreach ($lottery_types as $lottery_type) {
                $rst['updateDs'] = SscDataService::updateDsData($lottery_type); // 每期开奖遗漏 - 临时
                $rst['update3NumData'] = SscDataService::update3NumData($lottery_type); // 每期开奖遗漏 - 临时
            }
            */
            sleep(2);
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
                        $rst = NineNineBaseService::getRemoteHzRecords($TzSystemsUser->uid, $TzSystemsUser->tz_system_id, $lottery_type); # 抓取号码
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
        if(\Yii::$app->params['ssc_kj_time_start'] <= $time && $time <= \Yii::$app->params['ssc_kj_time_end'] ){
            $rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
            return $rst;
        }
        $rst['bet'] = BetService::bet(); // 用户新计划投注，可正买可反买

        return $rst;
    }
    /**
     * @desc 自主研发方案系统测试自动化投注入口
     * @return array
     */
    public function actionTz(){
        self::_init();
        $time = date("H:i");
        if(\Yii::$app->params['ssc_kj_time_start'] < $time && $time < \Yii::$app->params['ssc_kj_time_end'] ){
            $rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
            return $rst;
        }
        for ($i=0; $i<5; $i++){
            $rst['tz'] = TzService::tz(); // 计划投注
            sleep(2);
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
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/actionInsertSscDwsHzNums','INFO','统计区间某和值出现次数-执行时间', $logArr);
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
            sleep(2);
        }

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
            $TzSystems = TzSystems::findOne($TzSystemsUser->tz_system_id);
            switch ($TzSystems->system_type_id){
                case 1:
                    $rst = HN0898Service::synBalance($TzSystemsUser->id);
                    break;
                case 2:
                    $rst = SevenService::synBalance($TzSystemsUser->id);
                    break;
                case 3:
                    break;
                case 4: # 北京快乐8
                    $rst = HuiYuanBaseService::synBalance($TzSystemsUser->id);
                    break;
            }
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
            $tz_system_id = $TzSystemsUser->tz_system_id;
            if(in_array($tz_system_id, [1,2])){
                # 1、0898投注、2、99彩票网
                $rst = HN0898Service::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
            }elseif(in_array($tz_system_id, [3])){
                # 3、重庆7时彩网
                $rst = SevenService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
            }elseif(in_array($tz_system_id, [4])){
                # 4、7天彩票网
            }elseif(in_array($tz_system_id, [5])){
                # 5、希腊网
            }elseif(in_array($tz_system_id, [6])){
                # 6、会员网
                $rst = HuiYuanBaseService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
            }
        }
        return $rst;
    }

    /**
     * @desc 状态的开启跟关闭
     */
    public function actionSwitchStatus(){

    }


}