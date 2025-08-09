<?php

/**
 * Created by PhpStorm.
 *   
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\DataDealStatus;
use backend\models\DataTime;
use backend\models\SscKjData;
use backend\models\SysPlansCodes;
use backend\models\SystemConfig;
use backend\models\TzSystemsAuth;
use backend\models\UserCustomPlans;
use common\service\jobs\kj_data\AfterRunSysPlansJob;
use common\service\jobs\kj_data\UserBetTaskRecordJob;
use common\tools\KjDataGet;
use common\tools\Tool_Common;
use backend\models\User;
use backend\models\UserFollowData;
use backend\models\TzSystems;
use backend\models\UserSysPlans;
use  yii;

class TzService extends BaseService {


    /**
     * @decription Yii 控制器初始化方法
     */
    public static function _init(){
        #\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $time = date("H:i");
        if(\Yii::$app->params['ssc_kj_time_start'] < $time && $time < \Yii::$app->params['ssc_kj_time_start'] ){
            $rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
            return $rst;
        }
    }

    /**
     * @desc 1.1 投注：投注之前业务逻辑判断
     * @param $qihao
     * @param string $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     */
    public static function beforeBet($lottery_type = DEFAULT_LOTTERY_TYPE, &$activeQihao=''){
        $m = Yii::$app->cache;
        $rst = ['status'=>200, 'msg'=>'可以投注~'];
        $qihao = HN0898Service::getQihao($lottery_type);
        $activeQihao = $qihao;
        $mkey = TzService::buildNextKey($lottery_type, $qihao);
        $tzStatus = $m->get($mkey);

        # 判断当期开奖数据处理是否完成，未完成则不能下一期的投注
        if(!$tzStatus){
            $rst = ['status'=>300, 'msg'=>'投注开关未开启，有未处理完成的数据-2','mkey'=>$mkey,'tzStatus'=>$tzStatus];
            Tool_Common::log('0898tzCron','INFO','0898投注记录', $rst);
        }

        return $rst;
    }

    /**
     * @desc 执行计划前判断
     * @param $qihao
     * @param int $lottery_type
     * @return array
     */
    public static function beforeRunSysPlans($qihao, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = Yii::$app->cache;
        $pkey = BetService::buildPlanSwitchKey($lottery_type, $qihao);
        $isExists = SscKjData::find()->select(['id'])->where(['lottery_type'=>$lottery_type,'qihao'=>$qihao])->asArray()->limit(1)->one();
        $planStatus = $m->get($pkey);
        if($isExists && $planStatus){
            return ['status'=>300, 'pkey'=>$pkey, 'msg'=>'投注计划已经处理过了~'];
        }

        return ['status'=>200, 'msg'=>'系统计划可以处理'];
    }

    /**
     * @desc 处理系统投注计划
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @param string $qihao - 要处理的开奖期号
     * @param int $ignore
     * @return array
     * @throws \ErrorException
     */
    public static function operateSystemBetPlans(int $lottery_type = DEFAULT_LOTTERY_TYPE, $qihao='', $ignore = 0){
        self::_init();
        $rst = ['status'=>200, 'msg'=>'操作成功!'];

        $rstLog = [];
        try {
            $time0 = microtime(true);
            //$qihao = KjDataGet::getEndQihao($lottery_type);
            $qihao = $qihao?:HN0898Service::getCurrentQihao($lottery_type);
            $statusRst = self::beforeRunSysPlans($qihao, $lottery_type);
            if($statusRst['status'] != 200){
                return $statusRst;
            }
            if(!$ignore && !StaticService::isCanOpStatic($lottery_type, $qihao, 'opSystemBetPlans')){
                throw new \Exception('不可操作统计数据，还没到开奖时间');
            }
            $rst['qihao'] = $qihao;
            $rst['lottery_type'] = $lottery_type;

            $time1 = microtime(true);
            # 1、处理系统投注计划号码
            //$rst['opSystemCodesService'] = OpSystemCodesService::sysPlansCodes($lottery_type, $qihao); # 三定暂时不处理
            # 1、定位和值
            //$rst['heZhiStatics'] = SscDataService::heZhiStatics(); // 更新定位和值汇总表
            //$rst['updateHeZhiYL'] = SscDataService::updateHeZhiYL(); // 更新定位和值遗漏表
            # 每天四定利润统计，四定类型详见：StaticService::$typeArr
            $rstLog['static4dPerDateProfits'] = StaticService::static4dPerDateProfits($lottery_type);
            $time2 = microtime(true);

            # 2、单双
            $rstLog['updateDs'] = SscDataService::updateDsData($lottery_type); // 每期开奖单双数据
            $time3 = microtime(true);
            $rstLog['updateDsYL'] = SscDataService::updateDsYL($lottery_type); // 单双遗漏 耗时4s -- 耗时长，需剥离优化 2022.04.09
            $time4 = microtime(true);

            # 3、三字现
            //$rst['update3NumData'] = SscDataService::update3NumData($lottery_type); // 每期开奖遗漏 已写开奖表 三字现遗漏表暂时不写了
            $time5 = microtime(true);
            $rstLog['update3NumYL'] = SscDataService::update3NumYL($lottery_type); # 耗时 6-7s - 30s -- 耗时长，需剥离优化 2022.04.09
            $time6 = microtime(true);

            # 4、四定和值遗漏
            $rstLog['updateSdHzYL'] = SscDataService::updateSdHzYl($lottery_type); // 单双遗漏 耗时3.5s
            $time7 = microtime(true);

            //p([$time1, $time2, $time3, $time4, $time5, $time6, $time7, $lottery_type]);

            //$rst['opStaticSdProfitsMonth'] = StaticService::opStaticSdProfitsMonth(); # 单双利润统计(month)
            //$rst['opStaticSdProfitsDay'] = StaticService::opStaticSdProfitsDay(); # 单双利润统计(day)
            //$rst['tz'] = TzService::tz(); // 计划投注

            //$rst['synUsersBalance'] = HN0898Service::synBalance(); // 同步用户的余额

            # 计划方案倍数、投注号码或者投注状态修改
            //$rst['userSysPlanChange'] = UserSysPlansService::userSysPlanChange($lottery_type);

            # 止盈止损、倍投计划处理
            if($isCanOpStaticStatus = StaticService::isCanOpStatic($lottery_type, $qihao, $mkey = 'opProfitsPlans')) {
                $rstLog['opProfitsPlans'] = SscDataService::operateProfitsPlans($lottery_type, $qihao); # 处理止盈止损、倍投等计划
                StaticService::afterOpStatic($lottery_type, $qihao, 'opProfitsPlans');
            }
            $time8 = microtime(true);

            $rst['consume_time0'] = ($time1 - $time0).'s';
            $rst['consume_time1'] = ($time2 - $time1).'s';
            $rst['consume_time2'] = ($time3 - $time2).'s';
            $rst['consume_time3'] = ($time4 - $time3).'s';
            //$rst['consume_time4'] = ($time5 - $time4).'s';
            $rst['consume_time5'] = ($time6 - $time5).'s';
            $rst['consume_time6'] = ($time7 - $time6).'s';
            $rst['consume_time7'] = ($time8 - $time7).'s';
            $rst['isCanOpStaticStatus'] = $isCanOpStaticStatus;
            StaticService::afterOpStatic($lottery_type, $qihao, 'opSystemBetPlans');
            #$rst['afterRunSysPlans'] = TzService::afterRunSysPlans($qihao, $lottery_type); # 开关的开启或关闭
            Tool_Common::log('/static/'.__FUNCTION__,'INFO','处理系统投注计划', ['rst'=>$rst, 'rstLog'=>$rstLog]);
            push_queue(\common\service\jobs\kj_data\AfterRunSysPlansJob::class, ['lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'business_id'=>$qihao]);
        }catch (\Exception $e){
            StaticService::afterOpStatic($lottery_type, $qihao, 'opSystemBetPlans');
            Tool_Common::log('/static/'.__FUNCTION__, 'INFO', '数据统计异常', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
            throw new \ErrorException($e->getMessage());
        }

        return $rst;
    }

    /**
     * @desc 系统计划处理后，开关的开启或关闭
     * @param $qihao
     */
    public static function afterRunSysPlans($qihao, $lottery_type = DEFAULT_LOTTERY_TYPE){

        try {
            Tool_Common::log('/data/'.__FUNCTION__,'INFO','系统计划处理开关处理前', ['qihao'=>$qihao, 'lottery_type'=>$lottery_type]);
            $m = Yii::$app->cache;
            $next_qihao = KjDataGet::getNextQihaoByQihao($qihao, $lottery_type);
            $next_time = \Yii::$app->params['TZ_LOCK_TIME'];

            $where = ['AND',['=', 'lottery_type', $lottery_type], ['=', 'status', 1], ['=', 'is_parent', 1]];
            $plans = UserSysPlans::find()->where($where)->orderBy(['tz_sort'=>SORT_ASC])->all();
            foreach ($plans as $plan){
                # 处理完计划后,下一期投注开关开启(value:1) start
                $next_mkey = BetService::buildBeforeAndAfterBetKey($lottery_type, $next_qihao, $plan->uid);
                $m->set($next_mkey,1,$next_time); # 真实
            }
            $next_simulate_mkey = TzService::buildNextKey($lottery_type, $next_qihao);
            $m->set($next_simulate_mkey,1,$next_time); # 模拟
            # 处理完计划后,下一期投注开关开启(value:1) end

            # 计划任务是否处理完成后锁住(value:1)，避免重复处理 start
            $pkey = BetService::buildPlanSwitchKey($lottery_type, $qihao);
            $time = 1080;
            if($lottery_type == 5 && substr($qihao,6) == '010'){
                $time = 60*60*4; # 4小时
            } elseif($lottery_type == 9){
                $time = 60*60*7; # 台湾宾果 7小时
            }
            $m->set($pkey,1,$time);
            $rst = DataDealStatus::updateAll(['next_qihao'=>(string)$next_qihao], ['lottery_type'=>$lottery_type, 'qihao'=>$qihao]);

            $logData = ['lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'next_qihao'=>$next_qihao, 'updateRst'=>$rst];
            Tool_Common::log('/data/'.__FUNCTION__,'INFO','系统计划处理后', $logData);
            push_queue(UserBetTaskRecordJob::class, ['lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'next_qihao'=>$next_qihao, 'business_id'=>$qihao]);
        }catch (\Exception $e){
            Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '开关处理异常', ['lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'next_qihao'=>$next_qihao, 'err_msg'=>$e->getMessage()]);
        }

        return true;
    }

    /**
     * @desc 下一期开启开关缓存
     * @param int $lottery_type
     * @param string $next_qihao
     * @return string
     */
    public static function buildNextKey($lottery_type = DEFAULT_LOTTERY_TYPE, $next_qihao=''){
        $next_mkey = \Yii::$app->params['TZ_SWITCH_SIMULATE_KEY'].'_'.$lottery_type.'_'.$next_qihao;

        return $next_mkey;
    }

    /**
     * @param $plans_id
     */
    public static function getCustomPlansTzStatus($plans_id){
        $UserCustomPlan = UserCustomPlans::findOne($plans_id);
        $status = $UserCustomPlan->status;
        $codes = $UserCustomPlan->codes;
        if($status){
            $playway = $UserCustomPlan->playway;
            $threshold_open = $UserCustomPlan->threshold_open; // 开启遗漏阈值
            $threshold_close = $UserCustomPlan->threshold_close; // 关闭遗漏阈值
            # 判断遗漏是否大于阈值，如果大于等于阈值，则开启投注
            $m = \Yii::$app->cache;
            switch ($playway){
                # 计算出各种投注方式的当前遗漏
                case 1: //  二字定
                    break;
                case 2:
                case 3:
                    $current_miss = BaseNumService::getCodesYL($codes, $playway);
                    //if($threshold_open <= $current_miss && $current_miss <= $threshold_close){
                    if(in_array($current_miss, [0,1,2,3,4,5,6,7])){
                        $status = 1;
                    }else{
                        $status = 0;
                    }
                    break;
                case 10:
                    break;
                default:
                    $status = $UserCustomPlan->status;
            }
        }

        return $status;
    }


    /**
     * @desc 号码投注状态
     * @param int $playway
     * @param $codes
     * @param $lottery_type
     * @return int
     */
    public static function getSysTemPlansBetStatus($playway = 2, $codes, $lottery_type = DEFAULT_LOTTERY_TYPE){
        switch ($playway){
            # 计算出各种投注方式的当前遗漏
            case 1: //  二字定
                break;
            case 2:
            case 3:
                $current_miss = BaseNumService::getCodesYL($codes, $playway, $lottery_type);
                //if($threshold_open <= $current_miss && $current_miss <= $threshold_close){
                # system_config 配置：playway_yl_2_3
                $system_config= SystemConfig::findOne(['key'=>'playway_yl_2_3'])->value;
                $values = explode(',',$system_config);
                if(in_array($current_miss, $values)){ # [0,1,2,3,4,5,6,7] ,频率最高：0,1,2
                    $status = 1;
                }else{
                    $status = 0;
                }
                break;
            case 10:
                break;
            default:
                $status = 0;
        }

        return $status;
    }

    /**
     * @description 某组合的一个或多个一定区间出现次数动态数值
     * @param string $positions
     * @param string $hezhis
     * @param int $interval
     * @return int
     */
    public static function getTimesByQishus($positions = '1,2|1,3|1,4|2,3|2,4|3,4', $hezhis='8,9', $interval = 20){
        //p([$positions,$hezhis,$interval],0);
        $positionsArr = explode('|',$positions);
        $fields = ['id'];
        foreach ($positionsArr as $position){
            $fields[] = 'code_'.str_replace(',','_',$position);
        }
        $last = SscKjData::find()->select(['max(id) as last_id'])->asArray()->one();
        $max_id = $last['last_id'] - $interval;
        $hezhisArr = explode(',',$hezhis);
        $times = 0;
        foreach ($hezhisArr as $key=>$zhi){
            $SscKjDatas = SscKjData::find()->select($fields)->where('id>'.$max_id)->orderBy('id DESC')->limit($interval)->asArray()->all();
            //p($SscKjDatas,0);
            foreach ($SscKjDatas as $sscKjData){
                foreach ($fields as $k=>$field){
                    if($field == 'id') continue;
                    if($sscKjData[$field] == $zhi){
                        $times += 1;
                    }
                }
            }
        }

        return $times;
    }

    /**
     * @description 获取投注状态
     */
    public static function getTzStatus(){

    }

    /**
     * @description 定制化表预处理计划表数据
     * @param $UserCustomPlanId
     * @return array
     */
    public static function opPreUserFollowData($UserCustomPlanId){
        $UserCustomPlan = UserCustomPlans::findOne($UserCustomPlanId);
        $playway = $UserCustomPlan->playway;
        $account = $UserCustomPlan->account;
        $single = $UserCustomPlan->single;
        $positions = $UserCustomPlan->positions;
        $codes = $UserCustomPlan->codes;
        $is_simulate = $UserCustomPlan->is_simulate;
        $opData = [
            'position' => $positions,
            'playway' => $playway,
            'account' => $account,
            'single'=>$single,
            'code' => $codes,
            'is_simulate' => $is_simulate,
            'plan_type' => 3,
            'from_id'=>$UserCustomPlan->id,
        ];
        switch ($playway){
            case 1:
                $hezhis = $UserCustomPlan->hezhis;
                $opData['codes_hezhi'] = $hezhis;
                break;
            case 2:
                break;
            default:;
        }
        return $opData;
    }

    /**
     * @description 新添加计划是否存在
     * @param $account
     * @param string $positions
     * @param string $hezhis
     */
    public static function customPlanIsExist($account, $positions = '2,3', $hezhis = '8,9'){
        $where = ['account'=>$account,'positions'=>$positions, 'hezhis'=>$hezhis];
        $UserCustomPlans = UserCustomPlans::findOne($where);
        if($UserCustomPlans){
            return true;
        }
        return false;
    }

    /**
     * @desc 获取投注系统 getTzPlanTypes
     * @return mixed
     */
    public static function getTzPlanTypes($type = ''){

        $data = SscDataService::PLAN_TYPE_OPTIONS;
        /*
        $data = [
            # 计划类型:0正常1止盈止损计划
            0=>'正常',
            //1=>'止盈止损',
            2=>'倍投',
            //3=>'倍投&止盈止损',
            //4=>'倍投&号码切换',
            //5=>'倍投&号码切换止盈止损',
            6=>'中则投',
            7=>'中则投否则反买',
            8=>'遗漏投',  # 遗漏x期数则开始投，投中了后就再等遗漏x期再继续投
            9=>'遗漏倍投', # 遗漏x期数则开始倍投，投中了后就回到第一个倍数再等遗漏x期再继续倍投
            10=>'中则波推倍投',
            //11=>'中则交叉正反',
            12=>'A出x次B出y次投B',
            13=>'A出x次B出y次投B_2',
            14=>'区间遗漏投',
            15=>'中则倍投',
            16=>'遗漏倍投2',
            17=>'遗漏中则倍投',
            18=>'遗漏x期投y期',
        ];
        */
        if(isset($data[$type])) return $data[$type];

        return $data;
    }

    /**
     * @desc 获取投注系统 getTzPlanTypes
     * @return mixed
     */
    public static function getTzSites($admin_id = ''){
        $where = ['status'=>1];
        if($admin_id){
            $tz_systems_ids = TzSystemsAuth::findOne(['uid'=>$admin_id])->tz_systems_ids;
            $tz_systems_ids_Arr['id'] = explode(',', $tz_systems_ids);
            $where = array_merge($where, $tz_systems_ids_Arr);
        }
        //p([$where,$uid]);
        $sites = TzSystems::find()->where($where)->asArray()->all();
        $tz_sites = [];
        foreach ($sites as $key=>$site){
            $tz_sites[$site['id']] = $site['name'];
        }


        return $tz_sites;
    }

    /**
     * @desc 开启、关闭用户计划投注状态
     */
    public static function switchStatus(){

    }


    /**
     * @desc 更新时时彩开奖时间 1、1.5分彩 2、3分 3、5分彩 4、10分彩
     * @param $lottery_type
     */
    public static function insertSscDataTime($lottery_type = 1){

        switch ($lottery_type){
            case 1:
            case 2:
            case 3:
            case 4:
                $typeArr = [1=>1.5, 2=>3, 3=>5, 4=>10];
                $time_int = 24 * 3600;
                $actionNums = $time_int / ($typeArr[$lottery_type]*60);
                //p($actionNums);
                $setData = [];
                $dateTime = strtotime('2019-04-30 00:00:00');
                for ($i=1; $i<=$actionNums; $i++){
                    $setData['type'] = $lottery_type;
                    $setData['actionNo'] = $i;
                    $where = ['type'=>$lottery_type, 'actionNo'=>$i];
                    //p(date('Y-m-d H:i:s', $dateTime), 0);
                    if(!$DataTime = DataTime::findOne($where)){
                        $DataTime = new DataTime();
                    }
                    $dateTime = $dateTime + $typeArr[$lottery_type] * 60;

                    $HIS = date('H:i:s', $dateTime);
                    $setData['actionTime'] = $HIS;
                    $setData['stopTime'] = $HIS;
                    //p($i.'='.$HI, 0);
                    $DataTime->setAttributes($setData);
                    $rst = $DataTime->save();
                }
                break;
            case 5:
                $rst = self::insertCqSscDataTime();
                break;
            case 6:
                $rst = self::insertXjSscDataTime();
                break;
            case 8:
                $rst = self::insertXjSscDataTime();
                break;
        }
        return ['status'=>200, 'msg'=>'更新时时彩开奖', 'rst'=>$rst, 'nums'=>$actionNums];
    }


    /**
     * @desc 更新时时彩开奖时间
     */
    public static function insertCqSscDataTime($lottery_type = 5){
        $actionNo = 59;
        $setData = [];
        $dateTime = strtotime('2019-02-15 00:10:00');
        for ($i=1; $i<=$actionNo; $i++){
            $setData['type'] = $lottery_type;  # lottery_type 5 重庆时时彩
            $setData['actionNo'] = $i;
            $where = ['type'=>$lottery_type, 'actionNo'=>$i];
            //p(date('Y-m-d H:i:s', $dateTime), 0);
            if(!$DataTime = DataTime::findOne($where)){
                $DataTime = new DataTime();
            }
            if($i==9) {
                $dateTime = $dateTime + 4 * 60 * 60 + 20 * 60;
            }else{
                $dateTime = $dateTime + 20 * 60;
            }
            //p($dateTime);
            $HIS = date('H:i:s', $dateTime);
            $setData['actionTime'] = $HIS;
            $setData['stopTime'] = $HIS;
            //p($i.'='.$HI, 0);
            $DataTime->setAttributes($setData);
            $rst = $DataTime->save();
        }
        return ['status'=>200, 'msg'=>'更新时时彩开奖', 'rst'=>$rst];
    }

    /**
     * @desc 更新时时彩开奖时间
     */
    public static function insertXjSscDataTime($lottery_type = 6){
        $actionNo = 48;
        $setData = [];
        $dateTime = strtotime('2019-02-15 10:00:00');
        for ($i=1; $i<=$actionNo; $i++){
            $setData['type'] = $lottery_type;  # lottery_type 5 重庆时时彩
            $setData['actionNo'] = $i;
            $where = ['type'=>$lottery_type, 'actionNo'=>$i];
            //p(date('Y-m-d H:i:s', $dateTime), 0);
            if(!$DataTime = DataTime::findOne($where)){
                $DataTime = new DataTime();
            }

            $dateTime = $dateTime + 20 * 60;
            //p($dateTime);
            $HIS = date('H:i:s', $dateTime);
            $setData['actionTime'] = $HIS;
            $setData['stopTime'] = $HIS;
            //p($i.'='.$HI, 0);
            $DataTime->setAttributes($setData);
            //p($DataTime->attributes);
            $rst = $DataTime->save();
        }
        return ['status'=>200, 'msg'=>'更新时时彩开奖', 'rst'=>$rst];
    }


    /**
     * @desc 更新时时彩开奖时间 - 幸运五星彩
     */
    public static function insertLuckyDataTime($lottery_type = 8){
        $actionNo = 288;
        $setData = [];
        $dateTime = strtotime('2019-02-15 00:00:00');
        for ($i=1; $i<=$actionNo; $i++){
            $setData['type'] = $lottery_type;  # lottery_type 5 重庆时时彩
            $setData['actionNo'] = $i;
            $where = ['type'=>$lottery_type, 'actionNo'=>$i];
            //p(date('Y-m-d H:i:s', $dateTime), 0);
            if(!$DataTime = DataTime::findOne($where)){
                $DataTime = new DataTime();
            }

            $dateTime = $dateTime + 5 * 60;
            //p($dateTime);
            $HIS = date('H:i:s', $dateTime);
            $setData['actionTime'] = $HIS;
            $setData['stopTime'] = $HIS;
            $DataTime->setAttributes($setData);
            $rst = $DataTime->save();
        }
        return ['status'=>200, 'msg'=>'更新时时彩开奖', 'rst'=>$rst];
    }













}
