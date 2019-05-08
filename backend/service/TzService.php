<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\DataTime;
use backend\models\SscKjData;
use backend\models\SysPlansCodes;
use backend\models\SystemConfig;
use backend\models\TzSystemsAuth;
use backend\models\UserCustomPlans;
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
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
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
    public static function beforeTz($lottery_type = 2){
        $m = Yii::$app->cache;
        $rst = ['status'=>200, 'msg'=>'可以投注~'];
        $qihao = HN0898Service::getQihao();
        $mkey = \Yii::$app->params['TZ_SWITCH_SIMULATE_KEY'].'_'.$lottery_type.'_'.$qihao;
        $tzStatus = $m->get($mkey);

        # 判断当期开奖数据处理是否完成，未完成则不能下一期的投注
        if(!$tzStatus){
            $rst = ['status'=>300, 'msg'=>'投注开关未开启，有未处理完成的数据~','mkey'=>$mkey,'tzStatus'=>$tzStatus];
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/0898tzCron','INFO','0898投注记录', $rst);
        }

        return $rst;
    }
    /**
     * @desc 系统自动化投注方法，投注号码为表：lt_sys_plans_codes，正买status=1 、自主研发公式模拟投注
     * @return array
     */
    public static function tz(){

        $where = ['AND', ['=', 'status', 1], ['=', 'uid', 0], ['=', 'is_parent', 1]];
        if($plans = UserSysPlans::find()->where($where)->groupBy('playway,tz_type')->all()) {
            // 1、投注前判断
            foreach ($plans as $plan){
                if($plan->children_plan_id>0){
                    $ids = explode(',', $plan->children_plan_id);
                    foreach ($ids as $id){
                        $tzRst[$id] = self::tzByPlanId($id);
                    }
                }else{
                    $tzRst[$plan->id] = self::tzByPlanId($plan->id);
                }
            }
        }
        $logArr = ['tzRst'=>$tzRst];
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/tz','INFO','投注记录(系统正买)', $logArr);
        return ['status'=>200, 'msg'=>'系统定制化模拟正买投注完成~', 'tzRst'=>$tzRst];
    }

    /**
     * @desc 投注计划 by plan_id
     * @param $plan_id
     * @return array
     */
    public static function tzByPlanId($plan_id){

        $plan = UserSysPlans::findOne($plan_id);
        # 0898体系投注号码，系统正买按照0898格式下单
        $codes = BetService::getPlansAllCodesType1($plan->tz_type, 1, $plan->sel_same, $plan->hz_Arr);

        # 期号
        $qihao = HN0898Service::getQihao($plan->lottery_type);

        # 0898体系最终投注
        $HN0898Service = new HN0898Service();
        $rst = $HN0898Service->betting($qihao, $plan->id, $codes);

        return $rst;
    }

    /**
     * @desc 投注完成之后业务处理
     */
    public static function afterTz($qihao){
        if(!$qihao) return false;
        $m = \Yii::$app->cache;

        $next_qihao = KjDataGet::getNextQihaoByQihao($qihao);
        $next_mkey = \Yii::$app->params['TZ_SWITCH_SIMULATE_KEY'].'_'.$next_qihao;
        $pkey = \Yii::$app->params['TZ_SWITCH_SIMULATE_KEY'].'_'.$qihao;
        $time = 20 * 60;
        if(substr($next_qihao,6) == '001') $time = 40 * 60; # 四十分钟
        if(substr($next_qihao,6) == '009') $time = 60 * 60 * 4; # 十小时
        $m->set($next_mkey,0,$time); # 投注完成下一期的投注关闭
        $m->set($pkey,0,$time); # 投注完成下一期的投注关闭
        return true;
    }

    /**
     * @desc 执行计划前判断
     * @param $qihao
     * @param int $lottery_type
     * @return array
     */
    public static function beforeRunSysPlans($qihao, $lottery_type = 2){
        $m = Yii::$app->cache;
        $pkey = \Yii::$app->params['PLAN_SWITCH_KEY'].'_'.$lottery_type.'_'.$qihao;
        if($planStatus = $m->get($pkey)){
            return ['status'=>300, 'pkey'=>$pkey, 'msg'=>'投注计划已经处理过了~'];
        }

        return ['status'=>200, 'msg'=>'系统计划可以处理'];
    }
    /**
     * @desc 处理系统投注计划
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array|bool
     */
    public static function opSystemBetPlans($lottery_type = 2){
        self::_init();
        $rst = ['status'=>200, 'msg'=>'操作成功!'];

        $qihao = KjDataGet::getEndQihao($lottery_type);
        $statusRst = self::beforeRunSysPlans($qihao, $lottery_type);
        if($statusRst['status'] != 200){
            return $statusRst;
        }

        # 1、处理系统投注计划号码
        $rst['opSystemCodesService'] = OpSystemCodesService::sysPlansCodes($qihao);

        for ($i=0;$i<2;$i++){
            # 1、定位和值
            //$rst['heZhiStatics'] = SscDataService::heZhiStatics(); // 更新定位和值汇总表
            //$rst['updateHeZhiYL'] = SscDataService::updateHeZhiYL(); // 更新定位和值遗漏表

            # 2、单双
            $rst['updateDs'] = SscDataService::updateDsData($lottery_type); // 每期开奖遗漏
            $rst['updateDsYL'] = SscDataService::updateDsYL($lottery_type); // 单双遗漏

            # 3、三字现
            $rst['update3NumData'] = SscDataService::update3NumData($lottery_type); // 每期开奖遗漏
            $rst['update3NumYL'] = SscDataService::update3NumYL($lottery_type);

            # 4、四定和值遗漏
            $rst['updateDsYL'] = SscDataService::updateSdHzYl($lottery_type); // 单双遗漏

            //$rst['tz'] = TzService::tz(); // 计划投注
            //$rst['synUsersBalance'] = HN0898Service::synBalance(); // 同步用户的余额


        }

        self::afterRunSysPlans($qihao, $lottery_type); # 开关的开启或关闭

        return $rst;
    }

    /**
     * @desc 系统计划处理后，开关的开启或关闭
     * @param $qihao
     */
    public static function afterRunSysPlans($qihao, $lottery_type = 2){
        $m = Yii::$app->cache;
        $next_qihao = KjDataGet::getNextQihaoByQihao($qihao, $lottery_type);

        # 处理完计划后,下一期投注开关开启(value:1) start
        $next_mkey = \Yii::$app->params['TZ_SWITCH_KEY'].'_'.$lottery_type.'_'.$next_qihao;
        $next_simulate_mkey = \Yii::$app->params['TZ_SWITCH_SIMULATE_KEY'].'_'.$lottery_type.'_'.$next_qihao;

        $next_time = \Yii::$app->params['TZ_LOCK_TIME'];
        $rst11 = $m->set($next_mkey,1,$next_time); # 真实
        $rst10 = $m->set($next_simulate_mkey,1,$next_time); # 模拟
        # 处理完计划后,下一期投注开关开启(value:1) end

        # 计划任务是否处理完成后锁住(value:1)，避免重复处理 start
        $pkey = \Yii::$app->params['PLAN_SWITCH_KEY'].'_'.$lottery_type.'_'.$qihao;
        //$simulate_pkey = \Yii::$app->params['PLAN_SWITCH_SIMULATE_KEY'].'_'.$qihao;
        $time = 1200;
        if(substr($qihao,6) == '010') $time = 60*60*4; # 4小时
        $rst21 = $m->set($pkey,1,$time);
        //$rst20 = $m->set($simulate_pkey,1,$time);
        # 计划任务是否处理完成后锁住(value:1)，避免重复处理 end

        $logData = [['pkey'=>$pkey,'rst10'=>$rst10, 'rst11'=>$rst11], ['next_key'=>$next_mkey, 'rst20'=>$rst20, 'rst21'=>$rst21]];
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/afterRunSysPlans','INFO','系统计划处理后', $logData);

        return true;
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
    public static function getSysTemPlansBetStatus($playway = 2, $codes, $lottery_type = 2){
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

    public static function filterTz($account = 'gaozi2017'){
        $HN0898Service = new HN0898Service($account);
        $data = [ 'code'=>$code, 'qihao'=>$qihao, 'playway'=>$playway, 'single'=>$single, 'is_simulate'=>$is_simulate,'order_type'=>$order_type,'position'=>$position ];
        $rst = $HN0898Service->tz($data);
    }

    /**
     * @desc 获取投注系统
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
     */
    public static function insertSscDataTime($type = 1){
        $typeArr = [1=>1.5, 2=>3, 3=>5, 4=>10];
        $time_int = 24 * 3600;
        $actionNums = $time_int / ($typeArr[$type]*60);
        //p($actionNums);
        $setData = [];
        $dateTime = strtotime('2019-04-30 00:00:00');
        for ($i=1; $i<=$actionNums; $i++){
            $setData['type'] = $type;
            $setData['actionNo'] = $i;
            $where = ['type'=>$type, 'actionNo'=>$i];
            //p(date('Y-m-d H:i:s', $dateTime), 0);
            if(!$DataTime = DataTime::findOne($where)){
                $DataTime = new DataTime();
            }
            $dateTime = $dateTime + $typeArr[$type] * 60;
            /*
            if($i==9) {
                $dateTime = $dateTime + 4 * 60 * 60 + $typeArr[$type] * 60;
            }else{
                $dateTime = $dateTime + $typeArr[$type] * 60;
            }
            */
            //p($dateTime);
            $HIS = date('H:i:s', $dateTime);
            $setData['actionTime'] = $HIS;
            $setData['stopTime'] = $HIS;
            //p($i.'='.$HI, 0);
            $DataTime->setAttributes($setData);
            $rst = $DataTime->save();
        }
        return ['status'=>200, 'msg'=>'更新时时彩开奖', 'rst'=>$rst, 'nums'=>$actionNums];
    }













}