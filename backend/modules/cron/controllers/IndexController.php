<?php
/**
 * Created by PhpStorm.
 * User:wangyegao
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\cron\controllers;

use backend\models\SystemType;
use backend\models\TzSystems;
use backend\models\TzSystemsUsers;
use backend\models\User;
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
    private static function _init()
    {
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
     * @desc 系统投注利润统计
     * @return array
     */
    public function actionOpStaticProfits(){
        self::_init();
        $rst[] = StaticService::opStaticProfits(); # 利润统计
        $rst[] = StaticService::allDateStatic3NumsPerDate(); # 上奖三字现
        $rst[] = StaticService::static2NumsYl();

        return $rst;
    }

    /**
     * @desc 四定单双利润统计
     * @return array
     */
    public function actionStaticSdProfits(){
        self::_init();
        $rst[] = StaticService::static4dMonthsProfits(); # 每月四定单双利润统计，四定类型详见：StaticService::$typeArr
        $rst[] = StaticService::static4dPerDateProfits(); # 每天四定利润统计，四定类型详见：StaticService::$typeArr

        return $rst;
    }

    /**
     * @desc 四定和值利润统计
     * @return array
     */
    public function actionStaticHzProfits(){
        self::_init();

        $rst[] = StaticService::staticSDHzPerDateProfits(); # 每天四定和值利润统计
        $rst[] = StaticService::staticHzMonthsProfits(); # 每月四定和值利润统计
        $rst[] = StaticService::allHzStaticProfitsPerdate();//p($rst);# 循环计算每天每个和值利润统计

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
        for ($i = 0; $i<10; $i++){
            $rst = KjDataGet::updateNullCode();
            for($lottery_type = 1; $lottery_type<=4; $lottery_type++){
                $rst['updateDs'] = SscDataService::updateDsData($lottery_type); // 每期开奖遗漏 - 临时
                $rst['update3NumData'] = SscDataService::update3NumData($lottery_type); // 每期开奖遗漏 - 临时
            }
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
        $TzSystemsUsers = TzSystemsUsers::findAll(['status'=>1]);

        foreach ($TzSystemsUsers as $TzSystemsUser){
            switch ($TzSystemsUser->tz_system_id){
                case 1:
                case 2:
                    $rst = HN0898Service::getRemoteHzRecords($TzSystemsUser->uid, $TzSystemsUser->tz_system_id); # 抓取号码
                    break;
                default:;
            }
        }
        return $rst;
    }

    /**
     * @desc 更新code_str字段
     * @return array
     */
    public function actionUpdateCode(){
        set_time_limit(0);
        self::_init();
        $time = date("H:i");
        if(\Yii::$app->params['ssc_kj_time_start'] < $time && $time < \Yii::$app->params['ssc_kj_time_end'] ){
            $rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
            return $rst;
        }
        //$rst['updateCodeStr'] = KjDataGet::updateCodeStr(); // 空code_str补全
        //$rst['updateCodeHeZhi'] = KjDataGet::updateCodeHeZhi(); // 更新和值
        //$rst['updateUserFollowRefrenceCodes'] = KjDataGet::updateUserFollowRefrenceCodes(); // 更新用户投注参考码
        //$rst['updateSnid'] = KjDataGet::updateSnid(); // 更新用户投注参考码
        # 第一步：开奖后处理完预投注
        //$rst['customTz'] = TzService::opSystemBetPlans(); // 处理系统投注计划

        return $rst;
    }

    /**
     * @desc 用户计划投注
     * @return array
     */
    public function actionBet(){
        self::_init();
        $time = date("H:i");
        if(\Yii::$app->params['ssc_kj_time_start'] < $time && $time < \Yii::$app->params['ssc_kj_time_end'] ){
            $rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
            return $rst;
        }
        for ($i=0; $i<5; $i++){
            $rst['bet'] = BetService::bet(); // 用户新计划投注，可正买可反买
            sleep(2);
        }

        return $rst;
    }
    /**
     * @desc 自动化投注新入口
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
        for ($i=0;$i<1;$i++) {
            $start_time = microtime(true);
            $rst = SscDataService::sscDwsHzNums();
            $end_time = microtime(true);
            $time_consume = ($end_time-$start_time).'s';
            $logArr = ['start_time'=>$start_time,'end_time'=>$end_time, 'time_consume'=>$time_consume];
            Tool_Common::log('/WORK/LOG/lottery_xl/'.date('Ymd').'/actionInsertSscDwsHzNums','INFO','统计区间某和值出现次数-执行时间', $logArr);
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
        for ($lottery_type=2; $lottery_type<5; $lottery_type++) {
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
            $TzSystems = TzSystems::findOne($TzSystemsUser->tz_system_id);
            switch ($TzSystems->system_type_id){
                case 1:
                    $rst = HN0898Service::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
                    break;
                case 2:
                    $rst = SevenService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
                    break;
                case 3:
                    break;
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