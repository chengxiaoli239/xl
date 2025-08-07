<?php

/**
 * Created by PhpStorm.
 *   
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\BettingRecords;
use backend\models\LotteryType;
use backend\models\Num4Type;
use backend\models\Ssc2numsVal;
use backend\models\Ssc2numsYl;
use backend\models\SscKjData;
use backend\models\SscKjData3num;
use backend\models\SscKjDataDs;
use backend\models\SscStaticVal;
use backend\models\Static3numArisePerdate;
use backend\models\Static4dProfits;
use backend\models\Static4dProfitsDay;
use backend\models\Static4dProfitsMonth;
use backend\models\Static4dProfitsPerdate;
use backend\models\StaticCode3nAriseMonth;
use backend\models\StaticCode4nAriseMonth;
use backend\models\StaticCodeTypeArisePerdate;
use backend\models\StaticCodeTypeProfitsMonth;
use backend\models\StaticCodeTypeProfitsPerdate;
use backend\models\StaticHzArisePerdate;
use backend\models\StaticHzProfits;
use backend\models\SscStaticYl;
use backend\models\StaticHzProfitsPerdate;
use backend\models\StaticPerHzPerdateProfits;
use backend\models\StaticPerHzProfits;
use backend\models\StaticProfits;
use backend\models\SystemConfig;
use backend\models\ThreeNum;
use backend\models\TzSystemsAuth;
use backend\models\TzTypes;
use backend\models\UserSysPlans;
use backend\service\numbers\DynamicFilterService;
use backend\service\statics\statics_base\DealDataService;
use backend\service\statics\statics_qx\StaticsQxMissService;
use backend\tools\Util;
use common\service\CommonService;
use common\service\lottery\LotteryTypeService;
use common\tools\KjDataGet;
use common\tools\Tool_Common;
use common\tools\Tools;
use yii\helpers\ArrayHelper;
use  yii;
use yii\helpers\BaseStringHelper;

class StaticService extends BaseService {

    public static $typeArr = [
        0 => ['1111', '1112', '1121', '1122', '1211', '1212', '1221', '1222', '2111', '2112', '2121', '2122', '2211', '2212', '2221', '2222' ],
        1 => ['1112', '1121', '1211', '2111', '1222', '2122', '2212', '2221'],
        2 => ['1122', '1212', '1221', '2112', '2121', '2211'],
        3 => ['1111', '2222'],
        4 => ['1222', '2122', '2212', '2221'],
        5 => ['2111', '1211', '1121', '1112'],
        6 => ['1222', '2122', '2212', '2221', '2222'],
        7 => ['2111', '1211', '1121', '1112', '1111'],
        8 => ['2222'],
        9 => ['1111'],
        10 => [1],
        11 => [2],
        12 => ['1222', '2122', '2212', '2221', '1111'],
        13 => ['2111', '1211', '1121', '1112', '2222'],
        14 => ['1222', '2122', '2212', '2221', '1111', '2222'],
        15 => ['2111', '1211', '1121', '1112', '2222', '1111'],
    ];

    # 单双类型 ：tz_type
    public static  $kArr = [
        0=>'所有',
        1=>'一双三单、一单三双',
        2=>'两双两单',
        3=>'四双四单',
        4=>'一单三双',
        5=>'一双三单',
        6=>'一单三双|四双',
        7=>'一双三单|四单',
        8=>'四双',
        9=>'四单',
        10=>'单数量',
        11=>'双数量',
        12=>'一单三双|四单',
        13=>'一双三单|四双',
        14=>'一单三双|四单|四双',
        15=>'一双三单|四单|四双',
        20=>'四定和值',
        21=>'和值范围四定',
        22=>'单双'
    ];

    # 和值类型
    public static $typeHzArr = [
        'hz_0_4' => [0, 1, 2, 3, 4],
        'hz_1_6' => [1, 2, 3, 4, 5, 6],
        'hz_5_10' => [5, 6, 7, 8, 9, 10],
        'hz_11_15' => [11, 12, 13, 14, 15],
        'hz_16_19' => [16, 17, 18, 19],
        'hz_20_24' => [20, 21, 22, 23, 24],
        'hz_25_29' => [25, 26, 27, 28, 29],
        'hz_30_35' => [30, 31, 32, 33, 34, 35],
    ];



    /**
     * @decription Yii 控制器初始化方法
     */
    public static function _init(){
        set_time_limit(0);
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $time = date("H:i");
        if(\Yii::$app->params['ssc_kj_time_start'] < $time && $time < \Yii::$app->params['ssc_kj_time_start'] ){
            $rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
            return $rst;
        }
    }

    /**
     * @desc 利润统计
     * @param  int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     */
    public static function opStaticProfits($lottery_type = DEFAULT_LOTTERY_TYPE ){
        exit; # 暂时不统计投注利润
        $m = \Yii::$app->cache;
        $qihao = HN0898Service::getQihao($lottery_type);
        $mkey = 'OP_STATIC_PROFITS_ID_19_'.$lottery_type.'_'.$qihao;
        if(!$id = $m->get($mkey)) $id = 0;
        $where = ['AND', ['>', 'id', $id], ['=', 'lottery_type', $lottery_type]];
        $BettingRecords = BettingRecords::find()->where($where)->orderBy(['id'=>SORT_ASC])->limit(50)->all();
        foreach ($BettingRecords as $bettingRecord){
            //sleep(2);
            $upData = $where = ['uid'=>(int)$bettingRecord->uid, 'qihao'=>$bettingRecord->qihao, 'playway'=>$bettingRecord->playway, 'lottery_type'=>$lottery_type];
            if(!$StaticProfits = StaticProfits::findOne($where)){
                $StaticProfits = new StaticProfits();
                $upData['created_at'] = time();
                $upData['updated_at'] = time();
                $upData['tz_time'] = $bettingRecord['create_time'];
                $upData['lottery_type'] = $lottery_type;
                $upData['tz_money'] = $bettingRecord->betting_money; # 投注金额
                $upData['profits'] = $bettingRecord->profits; # 利润
                $upData['zj_bouns'] = $bettingRecord->bonus; # 中奖金额
                //p($bettingRecord->attributes,0);

                # 上一期记录
                $where = ['uid'=>(int)$bettingRecord->uid, 'playway'=>$bettingRecord->playway, 'lottery_type'=>$lottery_type];
                $LastStaticProfits = StaticProfits::find()->where($where)->orderBy(['id'=>SORT_DESC])->limit(1)->one();
                //p([$where, $LastStaticProfits],0);

                $upData['cut_profits'] = floatval($LastStaticProfits->cut_profits) + floatval($bettingRecord->profits); # 截止利润
                $StaticProfits->setAttributes($upData);
                //if($bettingRecord->profits){
                $StaticProfits->save();
                //}
            }
            $time = 10 * 60; # 10分钟
            if(substr($bettingRecord->qihao,6) == '023') $time = 60*60*10; # 十小时
            //$cacheTime = BetService::
            $m->set($mkey, $bettingRecord->id, $time);
        }

        return ['status'=>200, 'msg'=>'处理成功opStaticProfits！'];
    }

    /**
     * @desc 每天单双利润统计
     * @param int $lottery_type
     * @return bool
     * @Time 2019-07-11
     */
    public static function opStaticSdProfitsDay($lottery_type = DEFAULT_LOTTERY_TYPE){
        if(in_array($lottery_type, [1])){
            return true;
            //return ['status'=>200, 'msg'=> '七星彩低频彩不需统计天利润'];
        }
        $rst = true;
        $m = \Yii::$app->cache;
        $mkey = 'opStaticSdProfitsDay_'.$lottery_type;

        for($s=0; $s<1; $s++) {
            $StaticTables = Static4dProfitsDay::find()->all();
            $flag = count($StaticTables);
            if (!$flag) $beforeDays = 120; # 数据表为空时默认统计前120前的数据
            if ($beforeDays == 120 OR !$time = $m->get($mkey)) {
                $time = strtotime('-120 days');
            } else {
                $time = $time + 24 * 3600;
            }

            $date = date('Y-m-d', $time);
            $date = min([date('Y-m-d'), $date]);
            if ($date > date('Y-m-d')) break;
            if ($statics = StaticService::staticAllSdProfitsDay($date, $lottery_type)) {
                $setData = [];
                if(!$Static4dProfitsDay = Static4dProfitsDay::find()->where(['lottery_type'=>$lottery_type, 'date'=>$date])->one()){
                    $Static4dProfitsDay = new Static4dProfitsDay();
                    $setData = array_merge($setData,[
                        'date' => $date,
                        'created_at' => time(),
                        'lottery_type' => $lottery_type,
                    ]);
                }
                $setData['updated_at'] = time();
                foreach ($statics as $key=>$profits){
                    $setData['codes_'.$key] = $profits;
                }

                $Static4dProfitsDay->setAttributes($setData);
                $rst = $Static4dProfitsDay->save();
            }
            $m->set($mkey, $time, 7*24*3600);
        }

        return $rst;
    }

    /**
     * @desc 每月单双利润统计
     * @param int $lottery_type
     * @return bool
     * @Time 2019-07-11
     */
    public static function opStaticSdProfitsMonth($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = true;
        $m = \Yii::$app->cache;
        $mkey = 'opStaticSdProfitsMonth_'.$lottery_type;

        for($s=0; $s<1; $s++) {
            $StaticTables = Static4dProfitsMonth::find()->all();
            $flag = count($StaticTables);
            if (!$flag) $beforeDays = 12; # 数据表为空时默认统计前120前的数据
            if ($beforeDays == 12 OR !$time = $m->get($mkey)) {
                $time = strtotime('-12 months');
            } else {
                $time = strtotime('+1 months', $time);
            }

            $month = date('Y-m', $time);
            $month = min([date('Y-m'), $month]);
            if ($month > date('Y-m')) break;
            if ($statics = StaticService::staticAllSdProfitsMonth($month, $lottery_type)) {
                $setData = [];
                if(!$Static4dProfitsMonth = Static4dProfitsMonth::find()->where(['lottery_type'=>$lottery_type, 'month'=>$month])->one()){
                    $Static4dProfitsMonth = new Static4dProfitsMonth();
                    $setData = array_merge($setData,[
                        'month' => $month,
                        'created_at' => time(),
                        'lottery_type' => $lottery_type,
                    ]);
                }
                $setData['updated_at'] = time();
                foreach ($statics as $key=>$profits){
                    $setData['codes_'.$key] = $profits;
                }

                $Static4dProfitsMonth->setAttributes($setData);
                $rst = $Static4dProfitsMonth->save();
            }
            $m->set($mkey, strtotime($month), 30*24*3600);
        }

        return $rst;
    }

    /**
     * @desc 历史统计利润
     * @param int $num
     * @param int $type 0:全部 1:一单三双或一双三单 2:两双两单 3:四单\四双 4:一单三双 5:一双三单
     * @return array|mixed
     */
    public static function staticProfits($playway, $num = 20, $fx = 0){
        $num = $num + 1;
        self::_init();
        switch ($playway){
            case 1: # 二字定
                break;
            case 2: # 三字定
                break;
            case 3: # 四字定
                $profits = self::static4DProfits($num, $fx);
                break;
        }

        return ['status'=>200, 'profits'=>$profits];
    }

    /**
     * @desc 四定利润统计
     */
    public static function static4DProfits($num = 101, $fx = 0)
    {
        $m = \Yii::$app->cache;
        $mkey = 'mkey_staticSDProfits';
        if (!$staticId = $m->get($mkey)) {
            $id = 1;
        }
        $where = ['>=', 'id', $id];
        $SscKjDatas = SscKjData::find()->select(['id', 'index_id', 'kj_code', 'qihao', 'code_str'])->where($where)->limit($num)->all();
        $allCost = 0.00; # 成本
        $allZjBonus = 0.00; # 中奖金额
        $allProfits = 0.00; # 利润
        $zjCount = 0;
        foreach ($SscKjDatas as $codeKey => $SscKjData) {
            if ($codeKey == ($num - 1)) break;
            //$kjData = $SscKjData->code_str;
            $kjData = $SscKjData['kj_code'];
            if ($fx == 0) {
                $resultCodes = self::getDiffCodes($kjData);
            } else {
                $resultCodes = self::getSameCodes($kjData, 1);
            }
            $kjCodes = substr($SscKjDatas[$codeKey + 1]['code_str'], 0, 7);
            $zjTimes = OpKjService::opKjData4($resultCodes, $kjCodes);
            //p([$kjData, $resultCodes, $kjCodes, $rst],0); //p($rst);

            $cost = 9 * 62.5;
            $zjBonus = 999.5 * $zjTimes;
            if ($zjBonus > 0) $zjCount = $zjCount + 1;
            $profits = $zjBonus - $cost;

            $allCost += $cost;
            $allZjBonus += $zjBonus;
            $allProfits += $profits;
        }
        return ['staticQihao'=>$SscKjData['qihao'], 'zjCount'=>$zjCount, 'allCost'=>$allCost, 'allZjBonus'=>$allZjBonus, 'allProfits'=>$allProfits];
    }

    /**
     * @desc 计算指定日期的四定单双利润 - 以月份为维度
     * @param string $date
     * @param int $num
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array|mixed
     */
    public static function staticSDProfits($month = '2018-11', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'MONTH_STATIC_DATA_1'.$lottery_type.'_'.$month;
        $typeArr = self::$typeArr;
        if($month != date('Y-m') && $allStatic = $m->get($mkey)){
            return $allStatic;
        }
        $where = ['AND', ['=', 'lottery_type', $lottery_type], ['>=', 'date', $month.'-01'], ['<=', 'date', $month.'-31']];
        $static = [ 0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0, 10=>0, 11=>0]; # 统计每种组合出现次数
        //$SscKjDatas = SscKjData::find()->where($where)->limit($num)->all();
        $SscKjDataDs = SscKjDataDs::find()->where($where)->orderBy(['id'=>SORT_DESC])->all();
        $num = count($SscKjDataDs);
        //p($SscKjDataDs);
        foreach ($SscKjDataDs as $SscKjData){
            $ds = $SscKjData->code_1_2_3_4; # 四定单双值
            $oneCodes = [$SscKjData->code_1, $SscKjData->code_2, $SscKjData->code_3, $SscKjData->code_4];
            foreach ($typeArr as $key=>$types){
                if(in_array($ds, $types)){
                    $static[$key] = $static[$key] + 1;# 统计每种组合出现次数
                }
                foreach ($oneCodes as $code){
                    in_array($code, $types) && $static[$key] = $static[$key] + 1;# 统计每种组合出现次数
                }
            }
        }
        //p([$static, count($SscKjDataDs)]);

        $allStatic = [];
        foreach ($typeArr as $k=>$arr){
            $profits = $static[$k] * 999.5 - $num * count($arr) * 62.5;
            if(in_array($arr, [[1], [2]])) $profits = $static[$k] * 0.95 - $num * 0.1 * 4;
            $allStatic[$k] = [
                'name' => StaticService::$kArr[$k],
                'zjZhus' => $static[$k],
                'qs' => $num,
                'lottery_type' => $lottery_type,
                'count' => count($arr),
                //'code' => $arr,
                'profits' => $profits
            ];
        }
        if($month != date('Y-m')){
            $m->set($mkey, $allStatic, 7*24*3600);
        }

        //echo $date.'月份：';
        return $allStatic;
    }

    /**
     * @desc 获取已开奖期数
     * @param string $date
     * @param int $lottery_type
     * @return int|string
     */
    public static function getQishuByMonth($month = '', $lottery_type = DEFAULT_LOTTERY_TYPE){

        $m = \Yii::$app->cache;
        $mkey = 'getQishuByMonth_'.$lottery_type.'_'.$month;
        if($count = $m->get($mkey)) return $count;
        $count = SscKjData::find()->where(['LEFT(date,7)'=>$month, 'lottery_type'=>$lottery_type])->count('id');
        if(!$count) $count = 0;

        $m->set($mkey, $count, 10*60);

        return $count;
    }

    /**
     * @desc 获取已开奖期数
     * @param string $date
     * @param int $lottery_type
     * @return int|string
     */
    public static function getQishuByDate($date = '', $lottery_type = DEFAULT_LOTTERY_TYPE){

        $m = \Yii::$app->cache;
        $mkey = 'getQishuByDate_'.$lottery_type.'_'.$date;
        if($count = $m->get($mkey)) return $count;
        $count = SscKjData::find()->where(['date'=>$date, 'lottery_type'=>$lottery_type])->count('id');
        if(!$count) $count = 0;

        $m->set($mkey, $count, 10*60);

        return $count;
    }

    /**
     * @desc 获取统计月份
     * @param int $lottery_type
     * @param string $staticModel
     * @return mixed
     */
    public static function getStaticMonth($lottery_type = DEFAULT_LOTTERY_TYPE, $staticModel = 'StaticCodeTypeProfitsMonth'){

        $staticModel = 'backend\\models\\'.$staticModel;
        if(!$static_month = $staticModel::find()->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->one()['month']){
            $month = SscKjData::find()->select(['month'=>'LEFT(date,7)'])->where(['lottery_type'=>$lottery_type])->asArray()->orderBy(['id'=>SORT_ASC])->one()['month']; # 历史开奖第一期日期
        }else{
            $last_month = SscKjData::find()->select(['month'=>'LEFT(date,7)', 'qihao'])->where(['lottery_type'=>$lottery_type])->asArray()->orderBy(['id'=>SORT_DESC])->one()['month']; # 截止目前开奖日期
            if($static_month<$last_month){
                $month = Tools::getNextMonth($static_month, '-');
            }else{
                $month = $last_month;
            }
        }

        return $month;
    }

    /**
     * @desc 获取统计日期
     * @param int $lottery_type
     * @param string $staticModel
     * @return mixed
     */
    public static function getStaticDate($lottery_type = DEFAULT_LOTTERY_TYPE, $staticModel = 'StaticCodeTypeProfitsPerdate'){

        $staticModel = 'backend\\models\\'.$staticModel;
        if(!$static_date = $staticModel::find()->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->one()['date']){
            $date = SscKjData::find()->select(['date'])->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_ASC])->one()['date']; # 历史开奖第一期日期
        }else{
            $last_date = SscKjData::find()->select(['date', 'qihao'])->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->limit(1)->one()['date']; # 截止目前开奖日期
            if($static_date<$last_date){
                $date = Tools::getNextDate($static_date, '-');
            }else{
                $date = $last_date;
            }
        }

        return $date;
    }

    /**
     * @desc 统计号码类型利润(每月)
     * @param int $lottery_type
     * @return array
     */
    public static function staticCodeTypeProfitsMonth_parent($lottery_type = DEFAULT_LOTTERY_TYPE){

        $month = self::getStaticMonth($lottery_type, 'StaticCodeTypeProfitsMonth');
        $rst = self::staticCodeTypeProfitsMonth($month, $lottery_type);
        if($month<date('Y-m')){
            for ($i=0; $i<12;$i++){
                $date = self::getStaticMonth($lottery_type, 'StaticCodeTypeProfitsMonth');
                $month = substr($date, 0,7);
                $rst = self::staticCodeTypeProfitsMonth($month, $lottery_type);
            }
        }

        return $rst;
    }

    /**
     * @desc 统计号码类型利润(每天)
     * @param int $lottery_type
     * @return array
     */
    public static function staticCodeTypeProfitsDate_parent($lottery_type = DEFAULT_LOTTERY_TYPE){

        $date = self::getStaticDate($lottery_type, 'StaticCodeTypeProfitsPerdate');
        $rst = self::staticCodeTypeProfitsDate($date, $lottery_type);
        if($date<date('Y-m-d')){
            for ($i=0; $i<50;$i++){
                $date = self::getStaticDate($lottery_type, 'StaticCodeTypeProfitsPerdate');
                $rst = self::staticCodeTypeProfitsDate($date, $lottery_type);
            }
        }

        return $rst;
    }

    /**
     * @desc 号码类型利润统计 - 每月
     * @param $month
     * @param int $lottery_type
     * @return array
     */
    public static function staticCodeTypeProfitsMonth($month, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $datas = [];

        $code_types = ['type_2', 'type_3', 'type_22', 'type_2b', 'type_3b', 'type_4b', 'type_2,type_2b', 'type_2,type_3b', 'type_3n_2b'];
        foreach ($code_types as $code_type){
            $datas[str_replace(',', '_', $code_type)] = self::staticCodeTypeProfitsMonth_son($month, $code_type, $lottery_type);
        }

        $where = ['lottery_type'=>$lottery_type, 'month'=>$month];
        $setData = [];
        if(!$StaticCodeTypeProfitsMonth = StaticCodeTypeProfitsMonth::findOne($where)){
            $StaticCodeTypeProfitsMonth = new StaticCodeTypeProfitsMonth();
            $setData = array_merge($setData, ['month'=>$month, 'lottery_type'=>$lottery_type, 'created_at'=>time()]);
        }
        foreach ($datas as $key=>$data){
            $key = str_replace('', '', $key);
            $setData[$key] = $datas[$key]['profits'];
        }
        $setData['updated_at'] = time();
        $StaticCodeTypeProfitsMonth->setAttributes($setData);
        $saveFlag = $StaticCodeTypeProfitsMonth->save();

        return ['saveFlag'=>$saveFlag, 'datas'=>$datas];
    }

    /**
     * @desc 号码类型利润统计 - 每天
     * @param $date
     * @param int $lottery_type
     * @return array
     */
    public static function staticCodeTypeProfitsDate($date, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $datas = [];

        $code_types = ['type_2', 'type_3', 'type_22', 'type_22b', 'type_2b', 'type_3b', 'type_4b', 'type_2,type_2b', 'type_2,type_3b', 'type_3n_2b'];
        foreach ($code_types as $code_type){
            $datas[str_replace(',', '_', $code_type)] = self::staticCodeTypeProfitsDate_son($date, $code_type, $lottery_type);
        }

        $where = ['lottery_type'=>$lottery_type, 'date'=>$date];
        $setData = [];
        if(!$StaticCodeTypeProfitsPerdate = StaticCodeTypeProfitsPerdate::findOne($where)){
            $StaticCodeTypeProfitsPerdate = new StaticCodeTypeProfitsPerdate();
            $setData = array_merge($setData, ['date'=>$date, 'lottery_type'=>$lottery_type, 'created_at'=>time()]);
        }
        foreach ($datas as $key=>$data){
            $key = str_replace('', '', $key);
            $setData[$key] = $datas[$key]['profits'];
        }
        $setData['updated_at'] = time();
        $StaticCodeTypeProfitsPerdate->setAttributes($setData);
        $saveFlag = $StaticCodeTypeProfitsPerdate->save();

        return ['saveFlag'=>$saveFlag, 'datas'=>$datas];
    }

    /**
     * @desc 计算单个号码类型组数、中奖次数、利润
     * @param string $code_type
     * @param string $month
     * @param int $lottery_type
     * @return array
     */
    public static function staticCodeTypeProfitsMonth_son($month = '', $code_type = 'type_2b', $lottery_type = DEFAULT_LOTTERY_TYPE){

        $qishu = self::getQishuByMonth($month, $lottery_type); # 已开奖期数
        $where = ['lottery_type'=>$lottery_type, 'LEFT(date,7)'=>$month];

        $code_types = explode(',', $code_type);
        foreach ($code_types as $val){
            $where_son[$val] = 1;
        }

        $static = SscKjData::find()->where(array_merge($where, $where_son))->count('id'); # 双重 中奖次数
        $count = self::getCodeTypeCount($code_type); # 号码类型组数
        $profits = self::calProfits($count, $qishu, $static);

        $data = ['month'=>$month, 'static'=>$static, 'qishu'=>$qishu, 'count'=>$count, 'profits'=>$profits];

        return $data;
    }


    /**
     * @desc 计算单个号码类型组数、中奖次数、利润
     * @param string $code_type
     * @param string $date
     * @param int $lottery_type
     * @return array
     */
    public static function staticCodeTypeProfitsDate_son($date = '', $code_type = 'type_2b', $lottery_type = DEFAULT_LOTTERY_TYPE){

        $qishu = self::getQishuByDate($date, $lottery_type); # 已开奖期数
        $where = ['lottery_type'=>$lottery_type, 'date'=>$date];

        $code_types = explode(',', $code_type);
        foreach ($code_types as $val){
            $where_son[$val] = 1;
        }

        $static = SscKjData::find()->where(array_merge($where, $where_son))->count('id'); # 双重 中奖次数
        $count = self::getCodeTypeCount($code_type); # 号码类型组数
        $profits = self::calProfits($count, $qishu, $static);

        $data = ['date'=>$date, 'static'=>$static, 'qishu'=>$qishu, 'count'=>$count, 'profits'=>$profits];

        return $data;
    }

    /**
     * @desc 获取号码类型组数
     * @param string $code_type
     * @return int|mixed
     */
    public static function getCodeTypeCount($code_type = 'type_2b'){

        $m = \Yii::$app->cache;
        $mkey = 'getCodeTypeCount_'.$code_type;
        if($count = $m->get($mkey)) return $count;

        $count = SscStaticVal::find()->select(['count'])->where(['val'=>$code_type])->limit(1)->one()['count'];
        if(!$count) $count = 10000;
        $m->set($mkey, $count, 20*60);

        return $count;
    }

    /**
     * @desc 计算利润
     * @param $count 组数
     * @param int $qishu 已开奖期数
     * @param int $zjCounts 中奖次数
     * @return float
     */
    public static function calProfits($count, $qishu = 59, $zjCounts = 1){

        $profits = $zjCounts * 995 - $qishu * $count * 0.1;
        return $profits;
    }

    /**
     * @desc 计算指定日期的四定和值利润 - 以月份为维度
     * @param string $date
     * @param int $num
     * @param int $type
     * @return array|mixed
     */
    public static function staticSdHzProfits($month = '2018-11', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'MONTH_STATIC_DATA_01_'.$lottery_type.'_'.$month;
        $typeArr = self::$typeHzArr;
        if($month != date('Y-m') && $allStatic = $m->get($mkey)){
            return $allStatic;
        }
        $where = ['LEFT(date, 7)'=>$month, 'lottery_type'=>$lottery_type];
        $allCounts = SscKjData::find()->select(['month'=>'LEFT(date, 7)', 'nums'=>'COUNT(id)'])->where($where)->orderBy(['id'=>SORT_DESC])->asArray()->count();
        //p($SscKjData);

        $allStatic = [];
        foreach ($typeArr as $k=>$hzArr){

            $where = ['LEFT(date, 7)'=>$month, 'codes_4nums_hz'=> $hzArr, 'lottery_type'=>$lottery_type];
            $zJcounts = SscKjData::find()->select(['id'])->where($where)->orderBy(['id'=>SORT_ASC])->count('id'); # 中奖次数
            $where = ['codes_hz'=>$hzArr, 'code_type'=>4];
            $NumCounts = Num4Type::find()->where($where)->orderBy(['id'=>SORT_ASC])->count('id'); # 期数

            $profits = $zJcounts * 995 - $allCounts * $NumCounts * 0.1;
            //p([$zJcounts, $allCounts, $NumCounts, $profits]);

            $allStatic[$k] = [
                'month' => $month,
                'name' => $k,
                'zjZhus' => $zJcounts,
                'qs' => $allCounts,
                'count' => $NumCounts,
                'profits' => $profits,
            ];
        }
        if($month != date('Y-m')){
            $m->set($mkey, $allStatic, 7*24*3600);
        }

        //echo $date.'月份：';
        return $allStatic;
    }

    /**
     * @desc 每天四定单双利润
     * @param string $date
     * @param $code 利润：2112
     * @return array
     */
    public static function staticAllSdProfitsDay($date = '2019-07-11', $lottery_type = DEFAULT_LOTTERY_TYPE){

        $key = 'staticAllSdProfitsMonth_'.$date;
        $m = \Yii::$app->cache;
        if($data = $m->get($key)) return $data;

        $count = StaticService::getQishuCounts($date, $lottery_type); # 当日开奖期数
        $where = ['date'=>$date, 'lottery_type'=>$lottery_type];
        $SscKjDatas  = SscKjData::find()->where($where)->all();
        $tmpCodeCounts = [];
        foreach ($SscKjDatas as $SscKjData){
            if(!$tmpCodeCounts[$SscKjData->code_1_2_3_4]){
                $tmpCodeCounts[$SscKjData->code_1_2_3_4] = 0;
            }
            $tmpCodeCounts[$SscKjData->code_1_2_3_4] = $tmpCodeCounts[$SscKjData->code_1_2_3_4] + 1;
        }
        $allDs = explode(',',\Yii::$app->params['ALL_DS']);
        foreach ($allDs as $ds){
            if(!isset($tmpCodeCounts[$ds])) $tmpCodeCounts[$ds] = 0;
        }
        $data = [];
        foreach ($tmpCodeCounts as $key=>$tmpCodeCount){
            $profits = $tmpCodeCount * 995 - $count * 62.5;
            $data[$key] = $profits;
        }
        if($date != date('Y-m-d')){
            $m->set($key, $data, 7*24*3600);
        }

        return $data;
    }

    /**
     * @desc 每月四定单双利润
     * @param string $Month
     * @param int $lottery_type
     * @return array
     */
    public static function staticAllSdProfitsMonth($Month = '2019-07', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $count = StaticService::getQishuCounts($Month, $lottery_type); # 当月开奖期数

        $key = 'staticAllSdProfitsMonth_'.$Month;
        $m = \Yii::$app->cache;
        if($data = $m->get($key)) return $data;

        $tmpCodeCounts = SscKjData::find()
            ->select(['COUNT(*) AS count'])
            ->where(['AND', ['LIKE', 'LEFT(date,7)', $Month], ['=', 'lottery_type', $lottery_type]])
            ->groupBy('code_1_2_3_4')
            ->indexBy('code_1_2_3_4')
            ->asArray()
            ->column();
        $allDs = explode(',',\Yii::$app->params['ALL_DS']);
        foreach ($allDs as $ds){
            if(!isset($tmpCodeCounts[$ds])) $tmpCodeCounts[$ds] = 0;
        }
        $data = [];
        foreach ($tmpCodeCounts as $key=>$tmpCodeCount){
            $profits = $tmpCodeCount * 995 - $count * 62.5;
            $data[$key] = $profits;
        }
        if($Month != date('Y-m')){
            $m->set($key, $data, 7*24*3600);
        }

        return $data;
    }

    /**
     * @desc 返回总期数
     * @param string $date
     * @param int $lottery_type
     * @return int|string
     */
    public static function getQishuCounts($date = '2019-07-11', $lottery_type = DEFAULT_LOTTERY_TYPE){
        if(strlen($date) == 10){
            $where = ['date'=>$date, 'lottery_type'=>$lottery_type];
        }else{
            $where = ['AND', ['LIKE', 'LEFT(date,7)', $date ], ['=', 'lottery_type', $lottery_type]];
        }
        $counts = SscKjData::find()->where($where)->count('id'); # 总期数

        return $counts;
    }

    /**
     * @desc 计算指定日期的四定单双利润 - 以每天日期为单位
     * @param string $date
     * @param int $num
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array|mixed
     */
    public static function staticSDPerDateProfits($date = '2018-08-05', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'DATE_STATIC_DATA_'.$lottery_type.'_'.$date;
        $typeArr = self::$typeArr;
        $where = ['lottery_type'=>$lottery_type, 'date' => $date];
        $static = [ 0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0, 10=>0, 11=>0]; # 统计每种组合出现次数
        # 开奖表，type_4ds : 四定单双:0保留1四单2四双3两单两双4一单三双5一双三单
        //$SscKjDatas = SscKjData::find()->where($where)->limit($num)->all();
        $num = SscKjData::find()->where($where)->count('id');

        $datas = SscKjData::find()->select(['type_4ds', '4ds'=>'COUNT(id)'])->where($where)->groupBy(['type_4ds'])->orderBy(['id'=>SORT_ASC])->asArray()->all();
        //p($datas);
        foreach ($datas as $data){
            $type_4ds = $data['type_4ds'];

            # 四定单双:0保留1四单2四双3两单两双4一单三双5一双三单
            if($type_4ds == 1){ # 四单
                $static[10] = $static[10] + $data['4ds'] * 4;
                $static[11] = $static[11] + $data['4ds'] * 0;
                $sTypes = [0,3,7,9,12,14,15];
            }elseif ($type_4ds == 2){ # 四双
                $static[10] = $static[10] + $data['4ds'] * 0;
                $static[11] = $static[11] + $data['4ds'] * 4;
                $sTypes = [0,3,6,8,13,14,15];
            }elseif ($type_4ds == 3){ #  两单两双
                $static[10] = $static[10] + $data['4ds'] * 2;
                $static[11] = $static[11] + $data['4ds'] * 2;
                $sTypes = [0,2];
            }elseif ($type_4ds == 4){ # 一单三双
                $static[10] = $static[10] + $data['4ds'] * 1;
                $static[11] = $static[11] + $data['4ds'] * 3;
                $sTypes = [0,1,4,6,12,14];
            }elseif ($type_4ds == 5){ # 一双三单
                $static[10] = $static[10] + $data['4ds'] * 3;
                $static[11] = $static[11] + $data['4ds'] * 1;
                $sTypes = [0,1,5,7,13,15];
            }
            foreach ($sTypes as $sType){
                $static[$sType] = $static[$sType] + $data['4ds'];
            }
        }
        //p([$static, $num, $lottery_type]);

        //if($lottery_type && $static[$lottery_type]) return $static[$lottery_type];
        $allStatic = [];
        foreach ($typeArr as $k=>$arr){
            $profits = $static[$k] * 999.5 - $num * count($arr) * 62.5;
            if(in_array($arr, [[1], [2]])) $profits = $static[$k] * 0.95 - $num * 0.1 * 4;
            $allStatic[$k] = [
                'name' => StaticService::$kArr[$k],
                'zjZhus' => $static[$k],
                'qs' => $num,
                'count' => count($arr),
                //'code' => $arr,
                'profits' => $profits
            ];
        }
        if($date != date('Y-m-d')){
            $m->set($mkey, $allStatic, 7*24*3600);
        }
        //p($allStatic);

        //echo $date.'月份：';
        return $allStatic;
    }

    /**
     * @desc 计算指定日期的四定和值利润 - 以每天日期为单位
     * @param string $date
     * @param int $num
     * @param int $type
     * @return array|mixed
     */
    public static function staticHzPerDateProfits($date = '2018-08-05', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'DATE_STATIC_HZ_DATA_0_'.$lottery_type.'_'.$date;
        $typeArr = self::$typeHzArr;

        if($allStatic = $m->get($mkey)) return $allStatic;

        $allCounts = SscKjData::find()->where(['date'=>$date, 'lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_ASC])->count();
        if(!$allCounts) return [];
        $allStatic = [];
        foreach ($typeArr as $k=>$hzArr){
            $where = ['date' => $date, 'lottery_type'=>$lottery_type, 'codes_4nums_hz'=> $hzArr];
            $zJcounts = SscKjData::find()->where($where)->orderBy(['id'=>SORT_ASC])->count(); # 中奖次数
            $where = ['codes_hz'=>$hzArr, 'code_type'=>4];
            $NumCounts = Num4Type::find()->where($where)->orderBy(['id'=>SORT_ASC])->count();

            $profits = $zJcounts * 999.5 - $allCounts * $NumCounts * 0.1;
            //p([$zJcounts, $NumCounts, $profits]);
            $allStatic[$k] = [
                'date' => $date,
                'name' => $k,
                'zjZhus' => $zJcounts,
                'qs' => $allCounts,
                'lottery_type' => $lottery_type,
                'count' => count($hzArr),
                //'code' => $arr,
                'profits' => $profits
            ];
        }
        //p($allStatic);
        $now_time = date('H:i');
        if($date != date('Y-m-d') && $now_time>'03:30'){
            $m->set($mkey, $allStatic, 7*24*3600);
        }

        return $allStatic;
    }

    /**
     * @desc 每个和值出现每天利润统计 add at 2019-04-27
     * @return array
     */
    public static function staticSdHzProfitsPerdate($date = '2019-02-11', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $hzArr = [];
        for ($i=1; $i<=36; $i++){
            $hzArr[$i] = 0;
        }

        $m = \Yii::$app->cache;
        $mkey = 'PERDATE_STATIC_HZ_DATA_'.$lottery_type.'_'.$date;

        if($allStatic = $m->get($mkey)) return $allStatic;

        //$allCounts = SscKjData::find()->where(['date'=>$date])->orderBy(['id'=>SORT_ASC])->count();
        $SscKjDatas = SscKjData::find()->where(['date'=>$date, 'lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_ASC])->all(); # 中奖次数
        $allQishus = count($SscKjDatas);
        $allStatic = [];
        foreach ($SscKjDatas as $SscKjData){
            if(!isset($allStatic[$SscKjData->codes_4nums_hz]) OR !$allStatic[$SscKjData->codes_4nums_hz]){
                //$hzArr[$SscKjData->codes_4nums_hz] = 0;
            }
            $hzArr[$SscKjData->codes_4nums_hz]++;
        }
        //p($hzArr);
        foreach ($hzArr as $hz=>$zjCounts){
            $where = ['codes_hz'=>$hz, 'code_type'=>4];
            $NumCounts = Num4Type::find()->where($where)->orderBy(['id'=>SORT_ASC])->count(); # 该和值号码组数

            $tzMoney = $allQishus * $NumCounts * 0.1; # 投注本金
            $profits = $zjCounts * 999.5 - $tzMoney; # 利润/天
            //p([$zJcounts, $NumCounts, $profits]);
            $allStatic[$hz] = [
                'date' => $date,
                'name' => $hz,
                'zjZhus' => $zjCounts,
                'qs' => $allQishus,
                'count' => $NumCounts,
                'lottery_type' => $lottery_type,
                'tzMoney' => $tzMoney,
                //'code' => $arr,
                'profits' => $profits
            ];
        }
        //p($allStatic);
        if($date != date('Y-m-d')){
            $m->set($mkey, $allStatic, 7*24*3600);
        }

        return $allStatic;
    }

    /**
     * @desc 计算指定月份的四定和值利润
     * @param string $month
     * @param int $num
     * @param int $type
     * @return array|mixed
     */
    public static function staticPerHzProfits($month = '2018-11', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'staticPerHzProfits_01_'.$lottery_type.'_'.$month;
        $hzArr = [];
        for ($i=1; $i<=36; $i++){
            $hzArr[$i] = 0;
        }
        if($month != date('Y-m') && $allStatic = $m->get($mkey)){
            //return $allStatic;
        }
        $where = ['LEFT(date, 7)'=>$month, 'lottery_type'=>$lottery_type];
        $allCounts = SscKjData::find()->select(['month'=>'LEFT(date, 7)', 'nums'=>'COUNT(id)'])->where($where)->orderBy(['id'=>SORT_DESC])->asArray()->count();

        $allStatic = [];
        foreach ($hzArr as $k=>$hz){

            $where = ['LEFT(date, 7)'=>$month, 'codes_4nums_hz'=> $k, 'lottery_type'=>$lottery_type];
            $zJcounts = SscKjData::find()->where($where)->orderBy(['id'=>SORT_ASC])->count('id'); # 中奖次数
            $where = ['codes_hz'=>$k, 'code_type'=>4];
            $NumCounts = Num4Type::find()->where($where)->orderBy(['id'=>SORT_ASC])->count('id'); # 期数

            $profits = $zJcounts * 995 - $allCounts * $NumCounts * 0.1;
            //p([$zJcounts, $allCounts, $NumCounts, $profits],0);

            $allStatic[$k] = [
                'month' => $month,
                'name' => $k,
                'zjZhus' => $zJcounts,
                'qs' => $allCounts,
                'count' => $NumCounts,
                'profits' => $profits,
            ];
        }
        if($month != date('Y-m')){
            $m->set($mkey, $allStatic, 7*24*3600);
        }

        //echo $date.'月份：';
        return $allStatic;
    }

    /**
     * @desc 每月每个和值利润统计 add at 2019-06-23
     * @return array
     */
    public static function allHzStaticProfits($lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'allHzStaticProfits_01_'.$lottery_type;

        $where = ['lottery_type'=>$lottery_type];
        $count = StaticPerHzProfits::find()->where($where)->count('id');
        $i = !$count ? 12 : 0;

        $months = [];
        for ($i; $i>=0; $i--){
            $months[] = date('Y-m', strtotime('-'.$i.' months'));
        }

        foreach ($months as $month){
            if($statics = self::staticPerHzProfits($month, $lottery_type)){
                //p(['statics'=>$statics]);
                $setData = ['month'=>$month, 'lottery_type'=>$lottery_type];
                foreach ($statics as $k=>$staticData) {
                    //$tzMoney = $staticData['tzMoney'];
                    $setData['codes_' . $staticData['name']] = $staticData['profits'];
                }
                if(!$StaticPerHzProfits = StaticPerHzProfits::findOne(['month'=>$month, 'lottery_type'=>$lottery_type])){
                    $StaticPerHzProfits = new StaticPerHzProfits();
                    $setData['created_at'] = time();
                }
                $setData['lottery_type'] = $lottery_type;
                $setData['updated_at'] = time();
                $StaticPerHzProfits->setAttributes($setData);

                $rst = $StaticPerHzProfits->save();
                if($rst){
                    $allStatic[$month] = $StaticPerHzProfits->attributes;
                }
                //p($StaticPerHzPerdateProfits->getFirstErrors());
            }
            if(date('Y-m') != $month) $m->set($mkey, $allStatic, 7*24*3600);
        }

        return $allStatic;
    }


    /**
     * @desc 所有月份4定利润统计 - 效率有点慢，待优化 2019-09-20
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function allMonthStaticProfits($lottery_type = DEFAULT_LOTTERY_TYPE){
        $months = [];
        for ($i=7; $i>=0; $i--){
            $months[] = date('Y-m', strtotime('-'.$i.' months'));
        }
        $allStatic = [];
        foreach ($months as $month){
            $statics = self::staticSDProfits($month, $lottery_type);
            if(!$statics) continue;
            foreach ($statics as $k=>$staticData){
                $kArr = self::$kArr;
                $kName = $kArr[$k];
                $allStatic[$kName][$month] = $staticData['profits'];
                if(in_array($k, [10, 11])){
                    $allStatic[$kName][$month] = $staticData['zjZhus'];
                }
            }
        }

        return $allStatic;
    }

    /**
     * @desc 所有月份4定和值利润统计
     * @return array
     */
    public static function allMonthSdHzStaticProfits($lottery_type = DEFAULT_LOTTERY_TYPE){
        $months = [];
        $n = 1;
        if(date('d') == '01' && ('00:01'<date('H:i') && date('H:i')<'00:08')){
            $n = 2;
        }
        for ($i=$n; $i>=0; $i--){
            $months[] = date('Y-m', strtotime('-'.$i.' months'));
        }
        $allStatic = [];
        foreach ($months as $month){
            $statics = self::staticSdHzProfits($month, $lottery_type);
            foreach ($statics as $k=>$staticData){
                $allStatic[$k][$month] = $staticData['profits'];
            }
        }

        return $allStatic;
    }

    /**
     * @desc 获取有一组相同的组合号码
     * @param array $codesArr1
     * @param array $codesArr2
     * @return string
     */
    public static function getSameCodes($kjData = '1234', $self = 0){
        $codes1 = '';
        $codes2 = '';
        $first2 = [$kjData[0], $kjData[1]];
        $after2 = [$kjData[2], $kjData[3]];

        $f2OtherCodes = self::getOtherCodes($after2);
        foreach ($f2OtherCodes as $code){
            $tmpCodes1 = '';
            $tmpCodes1 .= ($first2[0] % 2 == 0) ? '02468' : '13579';
            $tmpCodes1 .= ($first2[1] % 2 == 0) ? ',02468' : ',13579';

            $codes1 .= $tmpCodes1.','.$code.'@';
        }
        $codes1 = trim($codes1, '@');

        //p($f2OtherCodes);
        $a2OtherCodes = self::getOtherCodes($first2);
        foreach ($a2OtherCodes as $code){
            $tmpCodes2 = '';
            $tmpCodes2 .= ($after2[0] % 2 == 0) ? '02468' : '13579';
            $tmpCodes2 .= ($after2[1] % 2 == 0) ? ',02468' : ',13579';

            $codes2 .= $code.','.$tmpCodes2.'@';
        }
        $codes2 = trim($codes2, '@');

        return $codes1.'@'.$codes2;
    }

    /**
     * @desc 获取前两位或者后两位不一样的四定组合
     * @param string $kjData
     * @return string
     */
    public static function getDiffCodes($kjData = '1234'){

        $first2 = [$kjData[0], $kjData[1]];
        $f2Codes = self::getOtherCodes($first2);
        $after2 = [$kjData[2], $kjData[3]];
        $a2Codes = self::getOtherCodes($after2);
        //p([$f2Codes, $a2Codes]);
        $resultCodes = '';
        foreach ($f2Codes as $f2){
            foreach ($a2Codes as $a2){
                $resultCodes .= $f2.','.$a2.'@';
            }
        }
        $resultCodes = trim($resultCodes,'@');

        return $resultCodes;
    }


    /**
     * @desc 获取给定的值其它单双组合, 默认取与前两位或者后两位的相反的号码组合
     * @param array $codesArr
     * @param integer $fx
     */
    public static function getOtherCodes($codesArr = [3,6], $fx = 0){
        if(empty($codesArr)) return false;
        $tmp = [];
        $ds_Arr = [[1,1], [1,2], [2,1], [2,2]];
        foreach ($codesArr as $code){
            $tmp[] = $code % 2 == 0 ? 2 : 1;
        }

        foreach ($ds_Arr as $key=>$Arr) {
            if($Arr == $tmp) unset($ds_Arr[$key]);
        }
        $codes = [];
        foreach ($ds_Arr as $ds){
            $c1 = ($ds[0] == 1) ? '13579' : '02468';
            $c2 = ($ds[1] == 1) ? '13579' : '02468';
            $codes[] = $c1.','.$c2;
        }

        //p([$codesArr, $ds_Arr, $codes]);
        return $codes;
    }

    /**
     * @desc 记录每天的四定利润统计 - 写表
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function static4dPerDateProfits($lottery_type = DEFAULT_LOTTERY_TYPE, $s_date = ''){
        $rst = ['status'=>200, 'msg'=>'处理成功'];

        $start_time = microtime(true);
        try {
            Tool_Common::log('/data/'.__FUNCTION__, "INFO", '四定利润统计状态', ['lottery_type'=>$lottery_type, 'fun'=>__FUNCTION__]);
            $DataDealStatus = DealDataService::judgeDealTaskStatus($lottery_type, '', $field='static4dPerDateProfits_status');
            if($DataDealStatus->$field == SscDataService::DEAL_DATA_STATUS_NOT_NEED_DEAL){
                throw_info('未开启统计：'.DealDataService::$dealDataStatusFields[$field], 40001);
            }

            $allStaticProfits = self::allDateStaticProfits($lottery_type, $s_date);
            $tmpProfits = [];
            foreach ($allStaticProfits as $key=>$allStaticProfit){
                $tmpProfits[] = $allStaticProfit;
            }

            foreach ($tmpProfits as $tmpProfit){
                foreach ($tmpProfit as $date=>$tmp){
                    //if($date != date('Y-m-d')) continue;
                    if($date <= '2019-02-10') continue;
                    $setData = [];
                    if(!$Static4dProfits = Static4dProfitsPerdate::findOne(['date'=>$date, 'lottery_type'=>$lottery_type])){
                        $Static4dProfits = new Static4dProfitsPerdate();
                        $setData['created_at'] = time();
                    }
                    $setData['updated_at'] = time();
                    $setData['date'] = $date;
                    $setData['codes_4d_all'] = $tmpProfits[0][$date]; # 所有号码
                    $setData['codes_13_31'] = $tmpProfits[1][$date]; # 一双三单||一单三双
                    $setData['codes_22_22'] = $tmpProfits[2][$date]; # 两双两单
                    $setData['codes_1111_2222'] = $tmpProfits[3][$date]; # 四双四单
                    $setData['codes_13'] = $tmpProfits[4][$date]; # 一单三双
                    $setData['codes_31'] = $tmpProfits[5][$date]; # 一双三单
                    $setData['codes_13_2222'] = $tmpProfits[6][$date]; # 一单三双||四双
                    $setData['codes_31_1111'] = $tmpProfits[7][$date]; # 一双三单||四单
                    $setData['codes_2222'] = $tmpProfits[8][$date]; # 四双
                    $setData['codes_1111'] = $tmpProfits[9][$date]; # 四单
                    $setData['codes_13_1111'] = $tmpProfits[12][$date]; # 一单三双||四单
                    $setData['codes_31_2222'] = $tmpProfits[13][$date]; # 一双三单||四双
                    $setData['codes_13_1111_2222'] = $tmpProfits[14][$date]; # 一单三双||四单
                    $setData['codes_31_2222_1111'] = $tmpProfits[15][$date]; # 一双三单||四双
                    $setData['codes_1_nums'] = $tmpProfits[10][$date]; # 单数量
                    $setData['codes_2_nums'] = $tmpProfits[11][$date]; # 双数量
                    $setData['lottery_type'] = $lottery_type;
                    $Static4dProfits->setAttributes($setData);

                    if(!$Static4dProfits->save()){
                        throw new \Exception(json_encode($Static4dProfits->getErrors(), 320));
                    }
                }
            }
            $dealStatus = 2;
        }catch (\Exception $e){
            $dealStatus = (strpos($e->getMessage(), '已经处理') !== false) ? 2 : ($e->getCode()>40000? 4: 3);
            Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '数据处理异常6', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
        }

        $end_time = microtime(true);
        DealDataService::dealDataRecord($DataDealStatus, $field, $dealStatus, $dealDesc = ['time_consume'=>($end_time-$start_time).'s', 'deal_time'=>date('Y-m-d H:i:s')]);

        return ['status'=>200, 'data'=>$setData, 'rst'=>$rst];
    }

    /**
     * @desc 利润统计
     * @return array
     */
    public static function opStatic($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao=''){
        $status = StaticService::isCanOpStatic($lottery_type, $qihao, $mkey = 'opStatic');
        if($status) {
            $t1 = microtime(true);
            $rst['staticSDHzPerDateProfits'] = StaticService::staticSDHzPerDateProfits($lottery_type); # 每天四定和值利润统计
            $t2 = microtime(true);
            Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '数据统计', ['lottery_type'=>$lottery_type, 'err_msg'=>'1每天四定和值利润统计', 't7' => ($t2-$t1).'s']);
            $rst['staticHzMonthsProfits'] = StaticService::staticHzMonthsProfits($lottery_type); # 每月四定和值利润统计
            $t3 = microtime(true);
            Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '数据统计', ['lottery_type'=>$lottery_type, 'err_msg'=>'2每月四定和值利润统计', 't7' => ($t3-$t2).'s']);
            $rst['allHzStaticProfits'] = StaticService::allHzStaticProfits($lottery_type); # 每个月份每个和值利润统计
            $t4 = microtime(true);
            Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '数据统计', ['lottery_type'=>$lottery_type, 'err_msg'=>'3每个月份每个和值利润统计', 't7' => ($t4-$t3).'s']);
            $rst['allHzStaticProfitsPerdate'] = StaticService::allHzStaticProfitsPerdate($lottery_type);//p($rst);# 循环计算每天每个和值利润统计
            $t5 = microtime(true);
            Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '数据统计', ['lottery_type'=>$lottery_type, 'err_msg'=>'4循环计算每天每个和值利润统计', 't7' => ($t5-$t4).'s']);
            $rst['opStaticSdProfitsMonth'] = StaticService::opStaticSdProfitsMonth($lottery_type); # 单双利润统计(month)
            $t6 = microtime(true);
            Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '数据统计', ['lottery_type'=>$lottery_type, 'err_msg'=>'5单双利润统计(month)', 't7' => ($t6-$t5).'s']);
            $rst['opStaticSdProfitsDay'] = StaticService::opStaticSdProfitsDay($lottery_type); # 单双利润统计(day)

            $t7 = microtime(true);
            Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '数据统计', [
                'lottery_type'=>$lottery_type,
                'err_msg'=>'6单双利润统计(day)',
                't7' => ($t7-$t6).'s',
            ]);
            StaticService::afterOpStatic($lottery_type, $qihao, 'opStatic');
        }

        return $rst;
    }

    /**
     * @desc 记录每天四定和值利润统计 - 写表
     * @return array
     */
    public static function staticSDHzPerDateProfits($lottery_type = DEFAULT_LOTTERY_TYPE){
        if(in_array($lottery_type, [1])){
            return ['status'=>200, 'msg'=> '七星彩低频彩不需统计天利润'];
        }
        $rst = ['status'=>200, 'msg'=>'处理成功'];
        $allStaticProfits = self::allDateHzStaticProfits($lottery_type);

        //p($allStaticProfits);

        $tmpProfits = $allStaticProfits;
        foreach ($tmpProfits as $key=>$tmpProfit){
            foreach ($tmpProfit as $k=>$tmp){
                $date = $k;
                //if($date != date('Y-m-d')) continue;
                if($date <= '2019-02-10') continue;
                $setData = [];
                if(!$Static4dProfits = StaticHzProfitsPerdate::findOne(['date'=>$date, 'lottery_type'=>$lottery_type])){
                    $Static4dProfits = new StaticHzProfitsPerdate();
                    $setData['created_at'] = time();
                    $setData['lottery_type'] = $lottery_type;
                }
                $setData['updated_at'] = time();
                $setData['date'] = $date;
                $setData['hz_0_4'] = $tmpProfits['hz_0_4'][$date]; # 0-4 和值
                $setData['hz_1_6'] = $tmpProfits['hz_1_6'][$date]; # 1-6 和值
                $setData['hz_5_10'] = $tmpProfits['hz_5_10'][$date]; # 5-10 和值
                $setData['hz_11_15'] = $tmpProfits['hz_11_15'][$date]; # 11 - 15
                $setData['hz_16_19'] = $tmpProfits['hz_16_19'][$date]; # 16 - 19
                $setData['hz_20_24'] = $tmpProfits['hz_20_24'][$date]; # 20 - 24
                $setData['hz_25_29'] = $tmpProfits['hz_25_29'][$date]; # 25 - 29
                $setData['hz_30_35'] = $tmpProfits['hz_30_35'][$date]; # 30 - 35
                $Static4dProfits->setAttributes($setData);

                $rst = $Static4dProfits->save();
            }

        }

        return ['status'=>200, 'data'=>$setData, 'rst'=>$rst];
    }

    /**
     * @desc 所有月份4定利润统计
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function allDateStaticProfits($lottery_type = DEFAULT_LOTTERY_TYPE, $s_date = ''){
        $m = \Yii::$app->cache;
        $mkey = 'allDateStaticProfits_PERDATE_'.$lottery_type.'_25';

        $flag = Static4dProfitsPerdate::find()->where(['lottery_type'=>$lottery_type])->count();
        $allStatic = [];
        static $i = 5;
        for($s=0; $s<$i; $s++){
            if(!$flag OR !$time = $m->get($mkey)) {
                $staticsStarTime = self::getStaticStartTime($lottery_type); # 获取统计开始时间
                $time = $staticsStarTime;
                /*
                for ($i=0; $i<20; $i++){
                    self::allDateStaticProfits($lottery_type);
                }
                */
            }else{
                $i = 5;
                $time = $time + 24 * 3600;
            }

            if($s_date){
                $date = $s_date;
            }else{
                $date = date('Y-m-d', $time);
                $date = min([date('Y-m-d'), $date]);
                if($date>date('Y-m-d')) break;
            }
            if($lottery_type == 6 && '00:00'<date('H:i:s') && date('H:i:s')<'10:00'){
                $date = date('Y-m-d', time()-86400);
            }
            //$date = '2019-09-20'; $time = strtotime($date);
            if($statics = self::staticSDPerDateProfits($date, $lottery_type)){
                foreach ($statics as $k=>$staticData){
                    $kArr = self::$kArr;
                    $kName = $kArr[$k];
                    $allStatic[$kName][$date] = $staticData['profits'];
                    if(in_array($k, [10, 11])){
                        $allStatic[$kName][$date] = $staticData['zjZhus'];
                    }
                }
            }
            $m->set($mkey, $time, 7*24*3600);
        }

        return $allStatic;
    }

    /**
     * @desc 所有月份4定利润统计
     * @return array
     */
    public static function allDateHzStaticProfits($lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'allDateHzStaticProfits_PERDATE_10_'.$lottery_type;

        $allStatic = [];
        $flag = StaticHzProfitsPerdate::find()->where(['lottery_type'=>$lottery_type])->count();
        $i = 3;
        for($s=0; $s<$i; $s++){
            if(!$flag OR !$time = $m->get($mkey)) {
                $staticsStarTime = self::getStaticStartTime($lottery_type); # 获取统计开始时间
                $time = $staticsStarTime;
                /*
                for ($i=0; $i<20; $i++){
                    self::allDateHzStaticProfits($lottery_type);
                }
                */
            }else{
                $i = 2;
                $time = $time + 24 * 3600;
            }

            $date = date('Y-m-d', $time);
            $date = min(date('Y-m-d'), $date);
            if($date>date('Y-m-d')) break;
            $now_time = date('H:i');
            if($lottery_type == 6 && $now_time > '00:00' && $now_time < '02:10'){
                $date = date('Y-m-d', time()-86400);
            }
            if($statics = self::staticHzPerDateProfits($date, $lottery_type)){
                foreach ($statics as $k=>$staticData){
                    $allStatic[$k][$date] = $staticData['profits'];
                }
            }
            if($date != date('Y-m-d')){
                $m->set($mkey, $time, 7*24*3600);
            }
        }

        return $allStatic;
    }

    /**
     * @desc 记录每个月的四定统计 - 写表 待优化
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function static4dMonthsProfits( $lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = ['status'=>200, 'msg'=>'处理成功'];
        $allMonthStaticProfits = self::allMonthStaticProfits($lottery_type);
        $tmpProfits = [];
        foreach ($allMonthStaticProfits as $key=>$allMonthStaticProfit){
            $tmpProfits[] = $allMonthStaticProfit;
        }
        //p($tmpProfits);

        foreach ($tmpProfits as $tmpProfit){
            foreach ($tmpProfit as $month=>$tmp){
                //if($month != date('Y-m')) continue;
                $setData = [];
                if(!$Static4dProfits = Static4dProfits::findOne(['month'=>$month, 'lottery_type'=>$lottery_type])){
                    $Static4dProfits = new Static4dProfits();
                    $setData['created_at'] = time();
                }
                $setData['updated_at'] = time();
                $setData['month'] = $month;
                $setData['codes_4d_all'] = $tmpProfits[0][$month]; # 所有号码
                $setData['codes_13_31'] = $tmpProfits[1][$month]; # 一双三单||一单三双
                $setData['codes_22_22'] = $tmpProfits[2][$month]; # 两双两单
                $setData['codes_1111_2222'] = $tmpProfits[3][$month]; # 四双四单
                $setData['codes_13'] = $tmpProfits[4][$month]; # 一单三双
                $setData['codes_31'] = $tmpProfits[5][$month]; # 一双三单
                $setData['codes_13_2222'] = $tmpProfits[6][$month]; # 一单三双||四双
                $setData['codes_31_1111'] = $tmpProfits[7][$month]; # 一双三单||四单
                $setData['codes_13_1111'] = $tmpProfits[12][$month]; # 一单三双||四单
                $setData['codes_31_2222'] = $tmpProfits[13][$month]; # 一双三单||四双
                $setData['codes_13_1111_2222'] = $tmpProfits[14][$month]; # 一单三双||四单||四双
                $setData['codes_31_2222_1111'] = $tmpProfits[15][$month]; # 一双三单||四双||四单
                $setData['codes_2222'] = $tmpProfits[8][$month]; # 四双
                $setData['codes_1111'] = $tmpProfits[9][$month]; # 四单
                $setData['codes_1_nums'] = $tmpProfits[10][$month]; # 单数量
                $setData['codes_2_nums'] = $tmpProfits[11][$month]; # 双数量
                $setData['lottery_type'] = $lottery_type; # 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
                $Static4dProfits->setAttributes($setData);

                $rst = $Static4dProfits->save();
            }

        }

        return ['status'=>200, 'data'=>$setData, 'rst'=>$rst];
    }

   /**
    * @desc 记录每个月的四定和值统计 - 写表
    * @return array
    */
   public static function staticHzMonthsProfits($lottery_type = DEFAULT_LOTTERY_TYPE){
       $rst = ['status'=>200, 'msg'=>'处理成功'];
       $allMonthStaticProfits = self::allMonthSdHzStaticProfits($lottery_type);

       $tmpProfits = $allMonthStaticProfits;
       foreach ($tmpProfits as $tmpProfit){
           foreach ($tmpProfit as $month=>$tmp){
               //if($month != date('Y-m')) continue;
               $setData = [];
               if(!$StaticHzProfits = StaticHzProfits::findOne(['month'=>$month, 'lottery_type'=>$lottery_type])){
                   $StaticHzProfits = new StaticHzProfits();
                   $setData['created_at'] = time();
               }
               $setData['lottery_type'] = $lottery_type;
               $setData['updated_at'] = time();
               $setData['month'] = $month;
               $setData['hz_0_4'] = $tmpProfits['hz_0_4'][$month]; # 0-4 和值
               $setData['hz_1_6'] = $tmpProfits['hz_1_6'][$month]; # 1-6 和值
               $setData['hz_5_10'] = $tmpProfits['hz_5_10'][$month]; # 5-10 和值
               $setData['hz_11_15'] = $tmpProfits['hz_11_15'][$month]; # 11 - 15
               $setData['hz_16_19'] = $tmpProfits['hz_16_19'][$month]; # 16 - 19
               $setData['hz_20_24'] = $tmpProfits['hz_20_24'][$month]; # 20 - 24
               $setData['hz_25_29'] = $tmpProfits['hz_25_29'][$month]; # 25 - 29
               $setData['hz_30_35'] = $tmpProfits['hz_30_35'][$month]; # 30 - 35

               $StaticHzProfits->setAttributes($setData);

               $rst = $StaticHzProfits->save();
           }

       }

        return ['status'=>200, 'data'=>$setData, 'rst'=>$rst];
    }


    /**
     * @desc 记录上次四定单双值， 主要针对当前四定组合排除最近一期的号码
     */
    public static function static4DdsLastTime(){
        $SscKjDataDs = SscKjDataDs::find()->orderBy(['id'=>SORT_DESC])->limit(90)->asArray()->all();
        $SscKjDataDs = array_reverse($SscKjDataDs);
        $m = \Yii::$app->cache;
        foreach ($SscKjDataDs as $key=>$dsData){
            if(in_array($key, [0, 10, 11])) continue;
            foreach (self::$typeArr as $k=>$Arr){
                if(in_array($dsData['code_1_2_3_4'], $Arr)){
                    $mkey = 'SD_LAST_TIME_RECORD_'.$k;
                    $m->set($mkey, $dsData['code_1_2_3_4'], 4*60*60);
                }
            }
        }
        //p($SscKjDataDs);
        $LastTime = [];
        foreach (self::$typeArr as $k=>$Arr){
            if(in_array($k, [0, 8, 9, 10, 11])) continue;
            $mkey = 'SD_LAST_TIME_RECORD_'.$k;
            $LastTime[$k] = $m->get($mkey);
        }

        return ['status'=>200, 'data'=>$LastTime];
    }


    /**
     * @desc 统计4定和值排查利润:按照类型排查和值利润
     */
    public static function static4DHzProfits($start_date = '2019-03-01', $end_date = '2019-03-29', $nums = 5){

        $profits = [];
        $dateArr = self::getStartAndEndDate($start_date, $end_date);

        $codesArr = NumService::get2bCodeArr();
        $countsNum = count($codesArr);

        //echo '<pre>';
        foreach ($dateArr as $date){
            $profits[$date] = 0;
            //$SscKjDatas = SscKjData::find()->select(['qihao','codes_4nums_hz', 'LEFT(code_str, 7) AS code'])->where(['=','date',$date])->asArray()->all();
            $SscKjDatas = self::getMcCode($date);
            //p($SscKjDatas);
            $counts = 0;
            foreach ($SscKjDatas as $key=>$data){
                //if($data['qihao'] == '190301023') p([$data,$codesArr]);
                //if(in_array($data['codes_4nums_hz'], $rst)){ # 判断和值不准确
                if(in_array($data['code'], $codesArr)){ # 判断号码准确
                    $profits[$date] += 995;
                }else{
                    $profits[$date] = $profits[$date] - $countsNum * 0.1;
                }
                $counts += $countsNum;
                //p(['nums'=>$nums, 'rst'=>$codesArr, 'qihao'=>$data['qihao'], 'counts'=>$countsNum, 'data'=>$data, 'profits'=>$profits[$date]],0);
            }
            //p($counts);
            //$profits[$date] = $profits[$date] - $counts * 0.1;
        }
        //p($profits);

        return $profits;
    }

    /**
     * @desc 每天每个和值利润统计 add at 2019-04-27
     * @return array
     */
    public static function allHzStaticProfitsPerdate($lottery_type = DEFAULT_LOTTERY_TYPE){
        if(in_array($lottery_type, [1])){
            return ['status'=>200, 'msg'=> '七星彩低频彩不需统计天利润'];
        }
        $m = \Yii::$app->cache;
        $mkey = 'allHzStaticProfitsPerdate_01_'.$lottery_type;

        $allStatic = [];
        $flag = StaticPerHzPerdateProfits::find()->count();
        static $i = 3;
        for($s=0; $s<$i; $s++){
            if(!$flag OR !$time = $m->get($mkey)) {
                $staticsStarTime = self::getStaticStartTime($lottery_type); # 获取统计开始时间
                $time = $staticsStarTime;
                /*
                for ($i=0; $i<20; $i++){
                    self::allHzStaticProfitsPerdate($lottery_type);
                }
                */
            }else{
                $i = 5;
                $time = $time + 24 * 3600;
            }

            $date = date('Y-m-d', $time);
            $date = min([date('Y-m-d'), $date]);
            if($date>date('Y-m-d')) break;
            if($statics = self::staticSdHzProfitsPerdate($date, $lottery_type)){
                //p(['statics'=>$statics]);
                $setData = ['date'=>$date];
                foreach ($statics as $k=>$staticData) {
                    //$tzMoney = $staticData['tzMoney'];
                    $setData['codes_' . $staticData['name']] = $staticData['profits'];
                }
                if(!$StaticPerHzPerdateProfits = StaticPerHzPerdateProfits::findOne(['date'=>$date, 'lottery_type'=>$lottery_type])){
                    $StaticPerHzPerdateProfits = new StaticPerHzPerdateProfits();
                    $setData['created_at'] = time();
                }
                $setData['lottery_type'] = $lottery_type;
                $setData['updated_at'] = time();
                $StaticPerHzPerdateProfits->setAttributes($setData);
                //p($StaticPerHzPerdateProfits->attributes);

                $rst = $StaticPerHzPerdateProfits->save();
                if($rst){
                    $allStatic[$date] = $StaticPerHzPerdateProfits->attributes;
                }
                //p($StaticPerHzPerdateProfits->getFirstErrors());
            }
            $m->set($mkey, $time, 7*24*3600);
        }

        return $allStatic;
    }


    /**
     * @desc 获取开奖号码缓存数据
     * @param $date
     * @return array|SscKjData[]|bool
     */
    public static function getMcCode($date){
        $m = \Yii::$app->cache;
        $mkey = 'MC_KJ_CODE_'.$date;
        if($SscKjDatas = $m->get($mkey) && $date >= date('Y-m-d')) return $SscKjDatas;

        $SscKjDatas = SscKjData::find()->select(['qihao','codes_4nums_hz', 'LEFT(code_str, 7) AS code'])->where(['=','date',$date])->asArray()->all();

        $m->set($mkey, $SscKjDatas, 7*24*60*60);


        return $SscKjDatas;
    }


    /**
     * @desc 给定开始和结束日期，返回区间日期
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public static function getStartAndEndDate($start_date = '2019-03-01', $end_date = '2019-03-30'){
         $dateArr = [$start_date];
        for ($i=1; $i<=1000; $i++){
            $d = date('Y-m-d', strtotime($start_date)+$i*86400);
            $dateArr[] = $d;
            if($d>=$end_date) break;
        }

        return $dateArr;
    }

    /**
     * @desc 每天三字现出现次数
     * @param string $date
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function staticKj3NumCounts($date = '2019-02-11', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'staticKj3NumCounts_'.$lottery_type.'_'.$date;

        if($staticDatas = $m->get($mkey)) return $staticDatas;
        $SscKjDatas = SscKjData::find()->where(['date'=>$date, 'lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->limit(1000)->all();
        if(!$SscKjDatas) return ['status'=>300, 'msg'=>'无统计数据'];
        $staticDatas = [];
        foreach ($SscKjDatas as $key=>$SscKjData){
            if(!$SscKjData->code_3n) continue;
            $codes3Nums = explode(',', $SscKjData->code_3n);
            foreach ($codes3Nums as $nums){
                if(!isset($staticDatas[$nums])) $staticDatas[$nums] = 0;
                $staticDatas[(string)$nums] += 1;
            }
        }

        arsort($staticDatas);

        if($date != date('Y-m-d')){
            $m->set($mkey, $staticDatas, 7*24*3600);
        }

        return $staticDatas;
    }

    /**
     * @desc 每月三字现出现次数
     * @param string $month
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function staticKj3NCounts($month = '2019-02', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'staticKj4NumCounts_'.$lottery_type.'_'.$month;

        if($staticDatas = $m->get($mkey)) return $staticDatas;
        $SscKjDatas = SscKjData::find()->select(['code_3n'])->where(['LEFT(date, 7)'=>$month, 'lottery_type'=>$lottery_type])->asArray()->all();

        if(!$SscKjDatas) return ['status'=>300, 'msg'=>'无统计数据'];
        $staticDatas = [];
        foreach ($SscKjDatas as $SscKjData){
            $code_3ns = explode(',', $SscKjData['code_3n']);
            foreach ($code_3ns as $code_3n){
                if(!isset($staticDatas[$code_3n])) $staticDatas[$code_3n] = 0;
                $staticDatas[(string)$code_3n] = (integer)$staticDatas[$code_3n] + 1;
            }
        }

        arsort($staticDatas);

        if($month != date('Y-m')){
            $m->set($mkey, $staticDatas, 7*24*3600);
        }

        return $staticDatas;
    }

    /**
     * @desc 每月四字现出现次数
     * @param string $date
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function staticKj4NCounts($month = '2019-02', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'staticKj4NumCounts_'.$lottery_type.'_'.$month;

        if($staticDatas = $m->get($mkey)) return $staticDatas;
        $SscKjDatas = SscKjData::find()->select(['code_4n', 'nums'=>'COUNT(id)'])->where(['LEFT(date, 7)'=>$month, 'lottery_type'=>$lottery_type])->groupBy('code_3n ,lottery_type')->orderBy(['id'=>SORT_DESC])->asArray()->all();

        if(!$SscKjDatas) return ['status'=>300, 'msg'=>'无统计数据'];
        $staticDatas = [];
        foreach ($SscKjDatas as $key=>$SscKjData){
            $code_4n = $SscKjData['code_4n'];
            if(!isset($staticDatas[$code_4n])) $staticDatas[$code_4n] = 0;
            $staticDatas[(string)$code_4n] = (integer)$SscKjData['nums'];
        }

        arsort($staticDatas);

        if($month != date('Y-m')){
            $m->set($mkey, $staticDatas, 7*24*3600);
        }

        return $staticDatas;
    }

    /**
     * @desc 每天和值范围数量统计:
     * @param string $date
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function staticHzCounts($date = '2019-02-11', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'staticHzCounts_'.$lottery_type.'_'.$date;

        //if($staticDatas = $m->get($mkey)) return $staticDatas;
        $codeTypes = StaticService::getAllCodeTypes($type = 1); # 统计基础号码类型筛选,类型：1和值2号码类型[例如:双双重、三重]

        $staticDatas = [];
        foreach ($codeTypes as $codeType) {
            $where = ['date'=>$date, 'lottery_type'=>$lottery_type];
            $codeTypeFields = explode(',', $codeType);
            $where['codes_4nums_hz'] = $codeTypeFields;

            $SscKjDatas = SscKjData::find()->where($where)->orderBy(['id'=>SORT_DESC])->limit(2000)->all();
            $staticDatas['hz_'.$codeTypeFields[0].'_'.end($codeTypeFields)]  = count($SscKjDatas);
        }
        $staticDatas['date'] = $date;

        if($date != date('Y-m-d')){
            $m->set($mkey, $staticDatas, 7*24*3600);
        }

        return $staticDatas;
    }

    /**
     * @desc 每天号码类型数量统计:双重、三重、双双重、两兄弟、三兄弟、四兄弟、双重&两兄弟、双重&三兄弟
     * @param string $date
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function staticCodeTypeCounts($date = '2019-02-11', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'staticCodeTypeCounts_'.$lottery_type.'_'.$date;

        //if($staticDatas = $m->get($mkey)) return $staticDatas;
        $codeTypes = StaticService::getAllCodeTypes($type = 2); # 统计基础号码类型筛选,类型：1和值2号码类型[例如:双双重、三重]
        $staticDatas = [];
        foreach ($codeTypes as $codeType) {
            $where = ['date'=>$date, 'lottery_type'=>$lottery_type];
            $codeTypeFields = explode(',', $codeType);
            foreach ($codeTypeFields as $codeTypeField){
                $where[$codeTypeField] = 1;
            }
            $count = SscKjData::find()->where($where)->orderBy(['id'=>SORT_DESC])->count();
            $staticDatas[str_replace(',', '_', $codeType)]  = $count;
        }

        if($date != date('Y-m-d')){
            $m->set($mkey, $staticDatas, 7*24*3600);
        }

        return $staticDatas;
    }

    /**
     * @desc 返回号码类型
     * @param $type - 类型：1和值2号码类型[例如:双双重、三重]
     * @return array
     */
    public static function getAllCodeTypes($type = 2){
        $SscStaticVals = SscStaticVal::find()->where(['status'=>1, 'type'=>$type])->asArray()->all();
        $m = \Yii::$app->cache;

        $mkey = 'getAllCodeTypes_02_'.$type;
        if($codeTypes = $m->get($mkey)) return $codeTypes;
        $codeTypes = ArrayHelper::getColumn($SscStaticVals, 'val');

        $m->set($mkey, $codeTypes, \Yii::$app->params['BASE_DATA_CACHE_TIME']);

        return $codeTypes;
    }

    /**
     * @desc 每天三字现热码
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function allDateStatic3NumsPerDate( $lottery_type = DEFAULT_LOTTERY_TYPE) {
        $m = \Yii::$app->cache;
        $mkey = 'allDateStatic3Nums_PERDATE_03_' . $lottery_type;

        $flag = Static3numArisePerdate::find()->where(['lottery_type' => $lottery_type])->count();
        static $i = 5;
        for ($s = 0; $s < $i; $s++) {
            if (!$flag OR !$time = $m->get($mkey)) {
                $staticsStarTime = self::getStaticStartTime($lottery_type); # 获取统计开始时间
                $time = $staticsStarTime;
                /*
                for ($i=0; $i<20; $i++){
                    self::allDateStatic3NumsPerDate($lottery_type);
                }
                */
            } else {
                $i = 5;
                $time = $time + 24 * 3600 - 10 * 60;
            }

            $date = date('Y-m-d', $time);
            $date = min([date('Y-m-d'), $date]);
            if ($date > date('Y-m-d')) break;
            if ($statics = self::staticKj3NumCounts($date, $lottery_type)) {
                //p([$statics, $date]);
                if ($statics['status'] == 300) {
                    $m->set($mkey, $time, 7 * 24 * 3600);
                    continue;
                }
                $setData = [];
                foreach ($statics as $key => $static) {
                    $setData['codes_' . $key] = $static;
                }
                if (!$Static3numArisePerdate = Static3numArisePerdate::findOne(['date' => $date, 'lottery_type' => $lottery_type])) {
                    $Static3numArisePerdate = new Static3numArisePerdate();
                    $setData['created_at'] = time();
                }
                $setData = array_merge($setData, [
                    'date' => $date,
                    'lottery_type' => $lottery_type,
                    'updated_at' => time(),
                ]);

                $Static3numArisePerdate->setAttributes($setData);
                //p($Static3numArisePerdate->attributes);
                $rst = $Static3numArisePerdate->save();
                //p(['date'=>$date, 'lottery_type'=>$lottery_type, $Static3numArisePerdate, $rst]);
            }
            $m->set($mkey, $time, 7 * 24 * 3600);
        }
    }

    /**
     * @desc 每月三字现出现次数统计
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc 7:北京快乐8 8幸运五星彩
     * @return array
     */
    public static function allDateStatic3nPerMonth( $lottery_type = DEFAULT_LOTTERY_TYPE){
        for ($i=1; $i>=0; $i--){
            $months[] = date('Y-m', strtotime('-'.$i.' months'));
        }

        $allStatic = [];
        foreach ($months as $month){
            //if($month>date('Y-m')) break;
            if($statics = self::staticKj3nCounts($month, $lottery_type)){
                $setData = [];
                foreach ($statics as $key=>$static){
                    $setData['code_'.$key] = (int)$static;
                }
                if(!$data = StaticCode3nAriseMonth::findOne(['month'=>$month, 'lottery_type'=>$lottery_type])){
                    $data = new StaticCode3nAriseMonth();
                    $setData['created_at'] = time();
                }
                $setData = array_merge($setData, [
                    'month' => $month,
                    'lottery_type' => $lottery_type,
                    'updated_at' => time(),
                ]);

                $data->setAttributes($setData);
                //p($data->attributes);
                //$allStatic[$month] = $data->attributes;
                $rst = $data->save();
                $allStatic[$month] = $rst;
                //p(['date'=>$date, 'lottery_type'=>$lottery_type, $Static3numArisePerdate, $rst]);
            }
        }

        return $allStatic;
    }

    /**
     * @desc 每月四字现出现次数统计
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc 7:北京快乐8 8幸运五星彩
     * @return array
     */
    public static function allDateStatic4nPerMonth( $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'allDateStatic4nPerMonth_01_'.$lottery_type;
        for ($i=1; $i>=0; $i--){
            $months[] = date('Y-m', strtotime('-'.$i.' months'));
        }

        $allStatic = [];
        foreach ($months as $month){
            //if($month>date('Y-m')) break;
            if($statics = self::staticKj4NCounts($month, $lottery_type)){
                $setData = [];
                foreach ($statics as $key=>$static){
                    $setData['code_'.$key] = (int)$static;
                }
                if(!$data = StaticCode4nAriseMonth::findOne(['month'=>$month, 'lottery_type'=>$lottery_type])){
                    $data = new StaticCode4nAriseMonth();
                    $setData['created_at'] = time();
                }
                $setData = array_merge($setData, [
                    'month' => $month,
                    'lottery_type' => $lottery_type,
                    'updated_at' => time(),
                ]);

                $data->setAttributes($setData);
                //p($data->attributes);
                //$allStatic[$month] = $data->attributes;
                $rst = $data->save();
                $allStatic[$month] = $rst;
                //p(['date'=>$date, 'lottery_type'=>$lottery_type, $Static3numArisePerdate, $rst]);
            }
        }

        return $allStatic;
    }

    /**
     * @desc 每天号码类型数量统计
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function allDateStaticCodeTypePerDate( $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'allDateStaticCodeType_PERDATE_05_'.$lottery_type;

        $allStatic = [];
        $flag = StaticCodeTypeArisePerdate::find()->where(['lottery_type'=>$lottery_type])->count();
        static $i = 2;
        for($s=0; $s<$i; $s++){
            if(!$flag OR !$time = $m->get($mkey)) {
                $staticsStarTime = self::getStaticStartTime($lottery_type); # 获取统计开始时间
                $time = $staticsStarTime;
                /*
                for ($i=0; $i<20; $i++){
                    self::allDateStaticCodeTypePerDate($lottery_type);
                }
                */
            }else{
                $i = 2;
                $time = $time + 24 * 3600;
            }

            $date = date('Y-m-d', $time);
            $date = min([date('Y-m-d'), $date]);
            if($date>date('Y-m-d')) break;
            if($statics = StaticService::staticCodeTypeCounts($date, $lottery_type)){
                $setData = [];
                foreach ($statics as $key=>$static){
                    $setData[$key] = $static;
                }
                if(!$StaticTables = StaticCodeTypeArisePerdate::findOne(['date'=>$date, 'lottery_type'=>$lottery_type])){
                    $StaticTables = new StaticCodeTypeArisePerdate();
                    $setData['created_at'] = time();
                }
                $setData = array_merge($setData, [
                    'date' => $date,
                    'lottery_type' => $lottery_type,
                    'updated_at' => time(),
                ]);
                $allStatic[] = $setData;

                $StaticTables->setAttributes($setData);
                $StaticTables->save();
                //p(['date'=>$date, 'lottery_type'=>$lottery_type, $Static3numArisePerdate, $rst]);
            }
            $m->set($mkey, $time, 7*24*3600);
        }

        return $allStatic;
    }

    /**
     * @desc 号码类型每天
     * @param $date
     * @param $lottery_type
     * @return mixed
     */
    public static function staticCodeTypeArisePerData($dates, $lottery_type){
        if(!is_array($dates)) $dates = [$dates];
        foreach ($dates as $date){
            if($statics = StaticService::staticCodeTypeCounts($date, $lottery_type)){
                $setData = [];
                foreach ($statics as $key=>$static){
                    $setData[$key] = $static;
                }
                if(!$StaticTables = StaticCodeTypeArisePerdate::findOne(['date'=>$date, 'lottery_type'=>$lottery_type])){
                    $StaticTables = new StaticCodeTypeArisePerdate();
                    $setData['created_at'] = time();
                }
                $setData = array_merge($setData, [
                    'date' => $date,
                    'lottery_type' => $lottery_type,
                    'updated_at' => time(),
                ]);
                $allStatic[] = $setData;

                $StaticTables->setAttributes($setData);
                $rst[$date] = $StaticTables->save();
                //p(['date'=>$date, 'lottery_type'=>$lottery_type, $Static3numArisePerdate, $rst]);
            }
        }

        return $rst;
    }


    /**
     * @desc 每天和值范围数量统计
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function allDateStaticHzPerDate( $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'allDateStaticHz_PERDATE_05_'.$lottery_type;

        $allStatic = [];
        $flag = StaticHzArisePerdate::find()->where(['lottery_type'=>$lottery_type])->count();
        static $i = 2;
        for($s=0; $s<$i; $s++){
            if(!$flag OR !$time = $m->get($mkey)) {
                $staticsStarTime = self::getStaticStartTime($lottery_type); # 获取统计开始时间
                $time = $staticsStarTime;
                /*
                for ($i=0; $i<20; $i++){ # 死循环
                    self::allDateStaticHzPerDate($lottery_type);
                }
                */
            }else{
                $i = 2;
                $time = $time + 24 * 3600;
            }

            $date = date('Y-m-d', $time);
            $date = min([date('Y-m-d'), $date]);
            if($date>date('Y-m-d')) break;
            if($statics = StaticService::staticHzCounts($date, $lottery_type)){
                $setData = [];
                foreach ($statics as $key=>$static){
                    $setData[$key] = $static;
                }
                if(!$StaticTables = StaticHzArisePerdate::findOne(['date'=>$date, 'lottery_type'=>$lottery_type])){
                    $StaticTables = new StaticHzArisePerdate();
                    $setData['created_at'] = time();
                }
                $setData = array_merge($setData, [
                    'date' => $date,
                    'lottery_type' => $lottery_type,
                    'updated_at' => time(),
                ]);
                $allStatic[] = $setData;
                //p($setData,0);

                $StaticTables->setAttributes($setData);
                $rst = $StaticTables->save();
                //p($rst,0); p($StaticTables->attributes);
                //p(['date'=>$date, 'lottery_type'=>$lottery_type, $StaticTables, $rst]);
            }
            $m->set($mkey, $time, 7*24*3600);
        }

        return $allStatic;
    }


   public static function sort_with_keyName($arr,$orderby='desc'){
   //在内存的另一处 $a 复制内容与 $arr 一样的数组
       foreach($arr as $key => $value)
           $a[$key]=$value;
       if($orderby== 'asc'){//对数组 $arr 进行排序
           asort($arr);
       }else{
           arsort($arr);
       }
       /*创建一个以原始数组的键名为元素值 (键值) 的
        *数组 $b, 其元素 (键值) 顺序，与排好序的数组 $arr 一致。
       */
       $index=0;
       foreach ($arr as $keys => $values) //按排序后数组的顺序
           foreach($a as $key => $value) //在备份数组中寻找键值
               if ($values==$value)//如果找到键值
                   $b[$index++]=$key; // 则将数组 $b 的元素值，设置成备份数组 $a 的键名
   //返回用数组 $b 的键值作为键名,数组 $arr 的键值作为键值,所组成的数组
       return array_combine($b, $arr);
   }

    /**
     * @desc 二字现遗漏，主要双重、对数、两兄弟
     * @param $lottery_type - 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
   public static function static2NumsYl($lottery_type = DEFAULT_LOTTERY_TYPE){
       $rst = ['status'=>200, 'msg'=>'操作成功~'];

       $Ssc2numsVals = Ssc2numsVal::find()->where('1=1')->asArray()->all();
       //p($Ssc2numsVals);
       foreach ($Ssc2numsVals as $key=>$Ssc2numsVal){
           $setData = [];
           if(!$Ssc2numsYl = Ssc2numsYl::findOne(['val'=>$Ssc2numsVal['val'], 'lottery_type'=>$lottery_type])){
               $Ssc2numsYl = new Ssc2numsYl();
               $setData['created_at'] = time();
           }
           $val = $Ssc2numsVal['val'];
           $yl_records = StaticService::get2NumsYlRecords($val, $lottery_type);
           $setData = array_merge($setData, [
               'val' => $val,
               'lottery_type' => $lottery_type,
               'current_miss' => $yl_records['current_miss'],
               'last_time_miss' => $yl_records['last_time_miss'],
               'last_time_miss_range' => $yl_records['last_time_miss_range'],
               'max_miss' => $yl_records['max_miss'],
               'yl_records' => $yl_records['current_miss'].$yl_records['yl_records'],
               'max_range' => $yl_records['max_range'],
               'history_max_miss' => max($Ssc2numsYl->history_max_miss, $yl_records['max_miss']),
               'updated_at' => time(),
           ]);

           $Ssc2numsYl->setAttributes($setData);
           //p($Ssc2numsYl->attributes);
           $Ssc2numsYl->save();
       }

        return $rst;
   }

    /**
     * @desc 二字现遗漏记录
     * @param $nums
     * @param $lottery_type - 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @param int $limit
     * @return array
     */
   public static function get2NumsYlRecords($nums, $lottery_type = DEFAULT_LOTTERY_TYPE, $limit = 300){
       if(strlen($nums) != 2) return [];
       $limit = ($nums[0] == $nums[1]) ? 480 : 450;

       $last_id = SscDataService::getKjDataLastId($lottery_type); # 表记录最后一条id
       $limit_id = $last_id - $limit;
      //p([$last_id, $limit_id]);

       $codes = [$nums[0], $nums[1]];
       $where = [
            'OR',
            //['like', 'LEFT(code_str,7)', '%'.$codes[0].','.$codes[1].'%', false],
            ['like', 'LEFT(code_str,7)', '%'.$codes[0].'%'.$codes[1].'%', false],

            //['like', 'LEFT(code_str,7)', '%'.$codes[1].','.$codes[0].'%', false],
            ['like', 'LEFT(code_str,7)', '%'.$codes[1].'%'.$codes[0].'%', false],
        ];
       $SscKjDatas = SscKjData::find()->select(['id','qihao','index_id','kj_code','qihao'])->where($where)->andWhere('index_id>='.$limit_id)->andWhere(['lottery_type'=>$lottery_type])->limit($limit)->orderBy(['id'=>SORT_DESC])->asArray()->all();
       //p($SscKjDatas);

       $yl_records = '';
       $max_miss = 0;
       foreach ($SscKjDatas as $k=>$SscKjData){
           if($k == 0) continue;
           $yl = $SscKjDatas[$k-1]['index_id']-$SscKjData['index_id'] - 1;
           $yl_records .= '-'.$yl;
           if($k == 1) {
               $last_time_miss = $yl;
               $last_time_miss_range = $SscKjDatas[$k-1]['qihao'].'-'.$SscKjData['qihao'];
           }
           $max_miss = max($max_miss, $yl);
           if($yl == $max_miss){
               $max_range = $SscKjDatas[$k-1]['qihao'].'-'.$SscKjData['qihao'];
           }
       }

       //p($yl_records);
       $rstData = [
           'current_miss' => $last_id - $SscKjDatas[0]['index_id'],
           'yl_records' =>$yl_records,
           'lottery_type' =>$lottery_type,
           'last_time_miss' => $last_time_miss,
           'max_miss' => $max_miss,
           'last_time_miss_range' => $last_time_miss_range,
           'max_range' => $max_range,
       ];
       //p($rstData);

       return $rstData;
   }

    /**
     * @desc 处理统计数据
     */
   public static function opAllStaticProfits($lottery_type=DEFAULT_LOTTERY_TYPE, $qihao=''){
       if(StaticService::isCanOpStatic($lottery_type, $qihao, $mkey = 'opAllStaticProfits')){
           $t1 = microtime(true);
           Tool_Common::log('/data/'.__FUNCTION__, "INFO", '处理统计数据-开始', ['lottery_type'=>$lottery_type, 't1'=>$t1]);
           #$rst['opStaticProfits'] = StaticService::opStaticProfits($lottery_type); # 暂停统计利润

           $rst['allDateStatic3nPerMonth'] = StaticService::allDateStatic3nPerMonth($lottery_type); # 三现每月统计
           $t2 = microtime(true);

           $rst['allDateStatic4nPerMonth'] = StaticService::allDateStatic4nPerMonth($lottery_type); # 部分四现每月统计
           $t3 = microtime(true);

           $rst['allDateStatic3NumsPerDate'] = StaticService::allDateStatic3NumsPerDate($lottery_type); # 上奖三字现
           $t4 = microtime(true);

           # 每月四定单双利润统计，四定类型详见：StaticService::$typeArr
           $rst['static4dMonthsProfits'] = StaticService::static4dMonthsProfits($lottery_type);
           $t5 = microtime(true);

           # 每天四定利润统计，四定类型详见：StaticService::$typeArr
           //$rst['static4dPerDateProfits'] = StaticService::static4dPerDateProfits($lottery_type);
           # 号码类型每天数量统计
           $rst['allDateStaticCodeTypePerDate'] = StaticService::allDateStaticCodeTypePerDate($lottery_type);
           $t6 = microtime(true);

           # 和值每天数量统计
           $rst['allDateStaticHzPerDate'] = StaticService::allDateStaticHzPerDate($lottery_type);
           $t7 = microtime(true);

           $rst['staticCodeTypeProfitsDate_parent'] = StaticService::staticCodeTypeProfitsDate_parent($lottery_type);
           $t8 = microtime(true);

           $rst['staticCodeTypeProfitsMonth_parent'] = StaticService::staticCodeTypeProfitsMonth_parent($lottery_type);
           $t9 = microtime(true);
           Tool_Common::log('/data/'.__FUNCTION__, "INFO", '处理统计数据-开始', [
               'lottery_type'=>$lottery_type,
               'time_consume1'=>($t2-$t1).'s',
               'time_consume2'=>($t3-$t2).'s',
               'time_consume3'=>($t4-$t3).'s',
               'time_consume4'=>($t5-$t4).'s',
               'time_consume5'=>($t6-$t5).'s',
               'time_consume6'=>($t7-$t6).'s',
               'time_consume7'=>($t8-$t7).'s',
               'time_consume8'=>($t9-$t8).'s',
           ]);

           StaticService::afterOpStatic($lottery_type, $qihao, 'opAllStaticProfits');
       }

       return '处理成功';
   }

    /**
     * 统计一个计划利润
     * @param string $plan_id
     */
   public static function staticOnePlanProfits(string $plan_id=''): bool
   {
       $UserSysPlans = UserSysPlans::findOne($plan_id);
       $profits = SscDataService::getPlanProfits($UserSysPlans);
       $StaticProfits = StaticProfits::find()->select(['qihao'])
           ->where(['plan_id'=>$plan_id])->orderBy(['id'=>SORT_DESC])->limit(1)->one();
       $BettingRecords = BettingRecords::find()->select(['plan_id', 'profits', 'current_profits'])
           ->where(['AND', ['=', 'plan_id',$plan_id], ['=', 'plan_id', $plan_id]])->asArray()->all();
       $setDatas = [

       ];
       return true;
   }

    /**
     * @desc 号码类型遗漏更新
     * @return mixed
     */
   public static function opAllCodeTypeYl($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao=''){
       $rst = ['status'=>200];
       $m = \Yii::$app->cache;

       try {
           $status = StaticService::isCanOpStatic($lottery_type, $qihao, $mkey = 'opAllCodeTypeYl');
           $msg = '数据处理成功~';
           if ($status) {
               $m->set($mkey, 1, 360);
               $time1 = microtime(true);
               # 号码类型：双重、双双重、四重、三兄弟、四兄弟
               $rst[$lottery_type]['updateCodeTypeYL'] = SscDataService::updateCodeTypeYL($type = 2, $lottery_type);
               $time2 = microtime(true);
               # 三字现
               $rst[$lottery_type]['updateCodeTypeYLs3'] = SscDataService::updateCodeTypeYLs($type = 3, $lottery_type); # 10s
               $time3 = microtime(true);
               # 四字现
               $rst[$lottery_type]['updateCodeTypeYLs4'] = SscDataService::updateCodeTypeYLs($type = 4, $lottery_type); # 70s
               $time4 = microtime(true);

               # 四字现带双组合，如:123，包含1123、1223、1233
               $rst[$lottery_type]['updateCodeTypeYLs5'] = SscDataService::updateCodeTypeYLs($type = 5, $lottery_type); # 70s
               $time5 = microtime(true);

               StaticService::afterOpStatic($lottery_type, $qihao, 'opAllCodeTypeYl');
               $rst['data'][$lottery_type]['consume_time1'] = $time2 - $time1;
               $rst['data'][$lottery_type]['consume_time2'] = $time3 - $time2;
               $rst['data'][$lottery_type]['consume_time3'] = $time4 - $time3;
               $rst['data'][$lottery_type]['consume_time4'] = $time5 - $time4;
               $rst['data'][$lottery_type]['msg'] = $msg;
               Tool_Common::log('opAllCodeTypeYl', 'INFO', '号码类型遗漏更新', $rst);
           }else{
               $msg = '数据已经处理过了~';
               $rst['data'][$lottery_type] = $msg;
           }
       }catch (\Exception $exception){
           Tool_Common::log('/static/'.__FUNCTION__.'_e', 'ERR', '统计错误', ['lottery_type'=>$lottery_type, 'err_msg'=>$exception->getMessage()]);
       }

       return $rst;
   }

    /**
     * @desc 用户分配的彩种
     * @return array
     */
    public static function getUserLotteryTypes($uid){
        $lottery_types = TzSystemsAuth::findOne(['uid'=>$uid])->lottery_types;
        $lottery_typesArr = explode(',', $lottery_types);

        return $lottery_typesArr;
    }

    /**
     * @desc 需要抓取开奖号码的彩种
     * @param int $useCache
     * @return array|LotteryType[]
     */
    public static function getGrabDataLotteryTypes(int $useCache=1): array
    {
        return LotteryTypeService::getLotteryTypeData($grabDataStatus=1);
    }


    /**
     * @desc 需要处理的猜中
     * @return array
     */
   public static function getLotteryTypes(){
       $m = \Yii::$app->cache;
       $mkey = __FUNCTION__.'_x0';
       $types = $m->get($mkey);
       if(empty($types)){
           $lotteryTypes = LotteryType::find()->select(['lottery_type'])->where(['enable'=>1])->asArray()->all();
           $types = array_column($lotteryTypes, 'lottery_type');
           $m->set($mkey, $types, 1800);
       }

       return $types;
   }

    /**
     * @desc 返回号码类型名称
     * @param int $type
     * @return array|mixed
     */
   public static function getCodeTypeName($type = 2){
       $codeTypes = [
           2 => '号码类型',
           3 => '三现带双重',
           4 => '四现带双重',
           5 => '四现不带双重',
           99 => '三四现热码',
       ];
       if(!$type OR !isset($codeTypes[$type])) return $codeTypes;

       return $codeTypes[$type];
   }

    /**
     * @desc 给定号码计算遗漏 未完待续 -- 2019.05.09
     * @param $codes
     * @param int $lottery_type
     * @param int $playway
     * @param $tz_type - 一字定倍数切换方案
     * @return int|array
     */
    public static function getYlByCodes($codes, $lottery_type = DEFAULT_LOTTERY_TYPE, $tz_type = 18, $staticAll=0){
        $tzTypes = TzTypes::findOne(['type'=>$tz_type]);
        $playway = $tzTypes->playway;

        $codeData = str_replace('@', ',', str_replace(',', '', implode('@', $codes)));

        $lastIndexId = SscDataService::getLastIndexId($lottery_type);
        $dateNum = ($playway == 3) ? 7 : 3;
        $lastIndexId7 = SscDataService::getLastIndexId($lottery_type, 7);
        $dateNum = ($playway == 3) ? 30 : 10;
        $lastIndexId30 = SscDataService::getLastIndexId($lottery_type, $dateNum);
        if($staticAll){
            $lastIndexId30 = 0;
            ini_set('memory_limit', '10240M');
        }
        switch ($playway){
            case 1: # 二字定
                break;
            case 2: # 三字定
                $oneCodes = explode(',', $codes[0]);
                $xpos = array_search('X', $oneCodes) + 1;
                switch ($xpos){
                    case 1:
                        $concatStr = "CONCAT('X,', code2, ',', code3, ',', code4)";
                        break;
                    case 2:
                        $concatStr = "CONCAT(code1, ',X,', code3, ',', code4)";
                        break;
                    case 3:
                        $concatStr = "CONCAT(code1, ',', code2, ',X,', code4)";
                        break;
                    case 4:
                        $concatStr = "CONCAT(code1, ',', code2, ',', code3, ',X')";
                        break;
                }
                $where = ['AND', ['=', 'lottery_type', $lottery_type], ['IN', $concatStr, $codes], ['>=', 'index_id', $lastIndexId30]];
                $query = SscKjData::find()->select(['id', 'index_id', 'qihao', 'code_4n_str'])->where($where);
                //p($query->createCommand()->getRawSql());
                if(!$staticAll){
                    $query->limit(20000);
                }
                $SscKjData = $query->asArray()->orderBy('id DESC')->all();
                break;
            case 3: # 四字定
                $where = ['AND', ['=', 'lottery_type', $lottery_type], ['IN', 'code_4n_str', $codes], ['>=', 'index_id', $lastIndexId30]];
                $query = SscKjData::find()->select(['id', 'index_id', 'qihao', 'code_4n_str'])->where($where);
                if(!$staticAll){
                    $query->limit(20000);
                }
                $SscKjData = $query->asArray()->orderBy('id DESC')->all();
                break;
            case 4: # 一字定
            case 10:
                $where = ['OR'];
                $codesArr = explode('@', $codes);
                foreach ($codesArr as $str){
                    $codesStrArr = explode(',', $str);
                    foreach ($codesStrArr as $key=>$arrStr){
                        if($arrStr == 'X') continue;
                        $codeKey = 'code'.($key+1);
                        $len = strlen($arrStr);
                        $tmpCodesArr = [];
                        for($i=0; $i<$len; $i++){
                            $tmpCodesArr[] = $arrStr[$i];
                        }
                        $where = array_merge($where,[[ 'IN', $codeKey, $tmpCodesArr]]);
                    }
                }
                $record = SscKjData::find()->select(['qihao','code_str'])->where($where)->andWhere(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();
                $last_QiHao = SscDataService::getKjDataLastQihao($lottery_type); # 表记录最后一条id
                $yl = self::qihaoSpace($record['qihao'], $last_QiHao);
                break;
        }

        $maxWeekHit = 0; # 近一周最大连中
        $maxWeekHitSet = [];
        $maxWeekYl = 0; # 近一周最大遗漏
        $maxWeekYlSet = [];
        $maxMonthHit = 0; # 近一月最大连中
        $maxMonthHitSet = [];
        $maxMonthYl = 0; # 近一月最大遗漏
        $maxMonthYlSet = [];
        $maxHit = 0; # 最大连中
        $maxHitSet = []; # 最大连中集合
        if(count($SscKjData) > 2){
            $allKjData = [];
            foreach($SscKjData as $key=>$r){
                //p([$r['index_id'], $lastIndexId, $lastIndexId7, $lastIndexId30], 0);
                if($key == 0) continue;
                $len = $SscKjData[$key-1]['index_id'] - $r['index_id'] - 1;
                $range[$SscKjData[$key-1]['index_id'].'_'. $r['index_id']] = $len;
                if($len==0){
                    # 中，统计连中:周、月
                    $maxHit += 1;
                    if($r['index_id']>=$lastIndexId7){
                        $maxWeekHit += 1;
                    }
                    if($r['index_id']>=$lastIndexId30){
                        $maxMonthHit += 1;
                    }
                }else{
                    # 不中、统计连中:周、月
                    if($r['index_id']>=$lastIndexId7){
                        $maxWeekYlSet[] = $len;
                        $maxWeekHitSet[] = $maxWeekHit;
                        $maxWeekHit = 0;
                    }
                    if($r['index_id']>=$lastIndexId30){
                        $maxMonthYlSet[] = $len;
                        $maxMonthHitSet[] = $maxMonthHit;
                        $maxMonthHit = 0;
                    }
                    $maxHitSet[] = $maxHit;
                }
                //$range[$SscKjData[$key-1]['index_id'].'_'. $r['index_id']] = $len;
                $allKjData[$r['index_id']] = $r;
            }
            //p(['range'=>$range]);
            $max_miss = max($range);
            $yl_str = implode('-', array_values($range));
            # 最大遗漏期间计算 end
            //p([$field=>$num,$min_id, $SscKjData[1]->id,$max_range]);
        }
        $last_times = 0;
        if(count($SscKjData)>1){
            $last_times = $SscKjData[0]['index_id'] - $SscKjData[1]['index_id'] - 1;  // 上次遗漏次数
        }
        $last_time_miss_range = $SscKjData[1]['qihao'] .'-'. $SscKjData[0]['qihao'];
        $current_times = $lastIndexId - $SscKjData[0]['index_id'];
        if(empty($yl_str)) $yl_str = $last_times;
        if(empty($maxWeekYlSet)){
            $maxWeekYlSet = $maxMonthYlSet ? : [1];
        }

        $ylCount = explode('-', $yl_str);
        if($staticAll) {
            p([$ylCount, count($ylCount)]);
        }

        //p([/*max($maxWeekYlSet), max($maxMonthYlSet), */'maxWeekYlSet'=>$maxWeekYlSet, 'maxMonthYlSet'=>$maxMonthYlSet, 'yl_str'=>$yl_str]);
        if(!empty($maxWeekYlSet)){
            $yl_str = str_replace('-'.max($maxWeekYlSet).'-', '-<strong><font color="red">'.max($maxWeekYlSet).'</font></strong>-', $yl_str).'-';
        }
        if(!empty($maxMonthYlSet)){
            $yl_str = str_replace('-'.max($maxMonthYlSet).'-', '-'.'<strong><font color="green">'.max($maxMonthYlSet).'</font></strong>-', $yl_str);
        }

        # 月最大连中
        $maxMonthHit = $maxMonthHitSet ? max($maxMonthHitSet) : 1; # 月最大连中
        $maxMonthHitArr = array_fill(0, $maxMonthHit, 0);
        $yl_str = str_replace('-'.implode('-', $maxMonthHitArr).'-', '-'.'<strong><font color="#adff2f">'.implode('-', $maxMonthHitArr).'</font></strong>-', $yl_str);

        # 周最大连中
        $maxWeekHit = $maxWeekHitSet ? max($maxWeekHitSet) : []; # 周最大连中
        $maxWeekHitArr = $maxWeekHit ? array_fill(0, $maxWeekHit, 0) : [];
        $yl_str = str_replace('-'.implode('-', $maxWeekHitArr).'-', '-<strong><font color="#8b008b">'.implode('-', $maxWeekHitArr).'</font></strong>-', $yl_str);
        $ylCountData = array_count_values($ylCount); // 遗漏统计 //p($ylCountData);

        //p(['maxMonthYlSet'=>$maxMonthYlSet, 'maxWeekYlSet'=>$maxWeekYlSet]);
        return [
            'current_times' => $current_times,    // 当前遗漏次数
            'last_times' => $last_times,    // 上次遗漏次数
            'last_time_miss_range' => $last_time_miss_range,    // 上次遗漏范围
            'week_max_miss' => max($maxWeekYlSet),   // 本周最大遗漏
            'week_max_hit' => $maxWeekHit,   // 本周最大连中
            'month_max_miss' => max($maxMonthYlSet?:[1]),   // 本月最大遗漏
            'month_max_hit' => $maxMonthHit,   // 近本周最大连中
            'max_miss' => $max_miss ?: $last_times,   // 历史最大遗漏
            'max_hit' => max($maxHitSet?:[1]) ?: 1,   // 历史最大连中
            //'max_range' => $max_range,   // 近200期内的最大遗漏范围
            'counts' => count($codes),   // 组数
            'yl_str' => BaseStringHelper::truncate($yl_str,1400),
            //'yl_str' => trim($yl_str, '-'),
            'codeData' => $codeData,
        ];
    }

    /**
     * @desc 给定号码计算遗漏 未完待续 -- 2019.05.09
     * @param $codes
     * @param int $lottery_type
     * @param int $tz_type - 一字定倍数切换方案
     * @return array
     */
   public static function getYlByCodes2($codes, $lottery_type = DEFAULT_LOTTERY_TYPE, $tz_type = 18){
       $yl = 0;
       $tzTypes = TzTypes::findOne(['type'=>$tz_type]);
       $playway = $tzTypes->playway;

       $codeData = str_replace('@', ',', str_replace(',', '', implode('@', $codes)));

       $lastIndexId = SscDataService::getLastIndexId($lottery_type);
       $lastIndexId7 = SscDataService::getLastIndexId($lottery_type, 7);
       $lastIndexId30 = SscDataService::getLastIndexId($lottery_type, 30);
       $static_nums = 300;
       $min_index_id = $lastIndexId-$static_nums;
       switch ($playway){
           case 1: # 二字定
               break;
           case 2: # 三字定
               break;
           case 3: # 四字定
               $where = ['AND', ['=', 'lottery_type', $lottery_type], ['>=', 'index_id', $lastIndexId30] ];
               $query = SscKjData::find()->select(['qihao','index_id', 'is_zj'=>'SUM(1-0)'])->where($where);
               $query1 = $query->andWhere(['IN', 'code_4n_str', $codes]);
               #$sql = $query1->createCommand()->getRawSql();p($sql);
               $zjSscKjDatas = $query1->orderBy('id DESC')->indexBy(['index_id'])->groupBy(['index_id'])->asArray()->all(); # 中奖记录
               #Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '遗漏统计', ['sql'=>$sql]);
               $query = SscKjData::find()->select(['qihao','index_id', 'is_zj'=>'SUM(0)'])->where($where);
               $allSscKjData = $query->orderBy('id DESC')->indexBy(['index_id'])->groupBy(['index_id'])->asArray()->all(); # 所有记录
               break;
           case 4: # 一字定
           case 10:
               $where = ['OR'];
               $codesArr = explode('@', $codes);
               foreach ($codesArr as $str){
                   $codesStrArr = explode(',', $str);
                   foreach ($codesStrArr as $key=>$arrStr){
                       if($arrStr == 'X') continue;
                       $codeKey = 'code'.($key+1);
                       $len = strlen($arrStr);
                       $tmpCodesArr = [];
                       for($i=0; $i<$len; $i++){
                           $tmpCodesArr[] = $arrStr[$i];
                       }
                       $where = array_merge($where,[[ 'IN', $codeKey, $tmpCodesArr]]);
                   }
               }
               $query = SscKjData::find()->select(['qihao','code_str','code_4n_str'])->where($where)->andWhere(['lottery_type'=>$lottery_type]);
               $record = $query->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();
               $last_Qihao = SscDataService::getKjDataLastQihao($lottery_type); # 表记录最后一条id
               $yl = self::qihaoSpace($record['qihao'], $last_Qihao);
               break;
       }

       $maxWeekHit = 1; # 近一周最大连中
       $maxWeekYl = 1; # 近一周最大遗漏
       $maxMonthHit = 1; # 近一月最大连中
       $maxMonthYl = 1; # 近一月最大遗漏
       $maxHit = 1; # 最大连中
       $maxYl = 1; # 最大遗漏
       if(count($allSscKjData) > 2){
           $yl_Arr = []; # 遗漏数据
           $tmpMaxWeekYl = 0; # 周最大遗漏
           $tmpMaxWeekHit = 0; # 周最大连中
           $tmpMaxMonthYl = 0; # 月最大遗漏
           $tmpMaxMonthHit = 0; # 月最大连中
           foreach($allSscKjData as $index_id=>$SscKjData){
               if(isset($zjSscKjDatas[$index_id])){
                   $yl_Arr[] = 1;
                   # 中，统计连中:周、月
                   $maxHit += 1;
                   if($index_id>=$lastIndexId7){
                       $tmpMaxWeekHit += 1;
                       $maxWeekHit = max([$maxWeekHit, $tmpMaxWeekHit]);
                       $tmpMaxWeekYl = 0;
                   }
                   if($index_id>=$lastIndexId30){
                       $tmpMaxMonthHit += 1;
                       $maxMonthHit = max([$maxMonthHit, $tmpMaxMonthHit]);
                       $tmpMaxMonthYl = 0;
                   }
               }else{
                   $yl_Arr[] = 0;
                   # 不中、统计连中:周、月
                   if($index_id>=$lastIndexId7){
                       $tmpMaxWeekYl += 1;
                       $maxWeekYl = max([$maxWeekYl, $tmpMaxWeekYl]);
                       $tmpMaxWeekHit = 0;
                   }
                   if($index_id>=$lastIndexId30){
                       $tmpMaxMonthYl += 1;
                       $maxMonthYl = max([$maxMonthYl, $tmpMaxMonthYl]);
                       $tmpMaxMonthHit = 0;
                   }
               }
               $len = $SscKjData[$key-1]['index_id'] - $index_id - 1;
               $range[$SscKjData[$key-1]['index_id'].'_'. $index_id] = $len;
           }
           $current_times = $yl_Arr[0];
           $yl_str = implode('-', $yl_Arr);
       }
       $last_times = $yl_Arr[1]??1;  // 上次遗漏次数
       # 月最大连中
       $maxMonthHitArr = array_fill(1, $maxMonthHit, 1);
       $yl_str = str_replace('-'.implode('-', $maxMonthHitArr).'-', '-'.'<strong><font color="#189365">'.implode('-', $maxMonthHitArr).'</font></strong>-', $yl_str);
       # 周最大连中
       $maxWeekHitArr = array_fill(1, $maxWeekHit, 1);
       $yl_str = str_replace('-'.implode('-', $maxWeekHitArr).'-', '-'.'<strong><font color="#065f3e">'.implode('-', $maxWeekHitArr).'</font></strong>-', $yl_str);

       # 月最大遗漏
       $maxMonthYlArr = array_fill(0, $maxWeekHit, 0);
       $yl_str = str_replace('-'.implode('-', $maxMonthYlArr).'-', '-<strong><font color="#962017">'.implode('-', $maxMonthYlArr).'</font></strong>-', $yl_str);
       # 周最大遗漏
       $maxWeekYlArr = array_fill(0, $maxWeekYl, 0);
       $yl_str = str_replace('-'.implode('-', $maxWeekYlArr).'-', '-<strong><font color="#581611">'.implode('-', $maxWeekYlArr).'</font></strong>-', $yl_str);
       //p([$maxWeekHit, $maxWeekYl, $maxMonthHit, $maxMonthYl]);

       return [
           'current_times' => $current_times??0,    // 当前遗漏次数
           'last_times' => $last_times,    // 上次遗漏次数
           'week_max_miss' => $maxWeekYl,   // 本周最大遗漏
           'week_max_hit' => $maxWeekHit,   // 本周最大连中
           'month_max_miss' => $maxMonthYl,   // 本月最大遗漏
           'month_max_hit' => $maxMonthHit,   // 近本周最大连中
           'max_miss' => $maxYl, // 最大遗漏
           'max_hit' => $maxHit, // 最大连中
           'counts' => count($codes),   // 组数
           'yl_str' => $yl_str,
           'codeData' => $codeData,
       ];
   }

    /**
     * @desc 期号之间的距离期数
     * @param string $start_qihao
     * @param $end_qihao
     * @return int|string
     */
   public static function qihaoSpace($start_qihao = '', $end_qihao){
       $space = 0;

       $startDate = strstr($start_qihao, 0, 6);
       $endDate = strstr($end_qihao, 0, 6);
       if($startDate == $endDate){
           $space = $end_qihao - $start_qihao;
       }

       return $space;
   }


    /**
     * @desc 计算两兄弟利润
     * @param int $lottery_type
     * @return float
     */
   public static function calculate2bProfits($lottery_type = DEFAULT_LOTTERY_TYPE, $start_date = '2019-03-20', $end_date = '2019-03-30', $filterNums = 1000){
       $profits = 0.00;

       $m = \Yii::$app->cache;
       $where = ['AND', ['=', 'lottery_type', $lottery_type], ['>=', 'date', $start_date], ['<=', 'date', $end_date]];
       $SscKjDatas = SscKjData::find()->where($where)->orderBy(['id'=>SORT_DESC])->all();
       $logArr = [];
       foreach ($SscKjDatas as $SscKjData){
           $qihao = $SscKjData->qihao;
           $mkey_profits = 'PROFITS_2B_'.$lottery_type.'_'.$qihao.'_'.$filterNums;
           if($profitsData = $m->get($mkey_profits)){
               $mkey_buyCodes = 'buyCodes_'.$lottery_type.'_'.$qihao;
               if($buyCodes = $m->get($mkey_buyCodes)){
                   $buyCodes = NumService::filterLaterCodesAnd2bcode($lottery_type, $qihao, $filterNums);
                   $m->set($mkey_buyCodes, $buyCodes, 30*24*3600);
               }
               $codes = implode('@', $buyCodes);

               $profitsData = OpKjService::calcProfits(3, $codes, $SscKjData->code_str, 0.1);
               unset($profitsData['codes']);
               $logArr[] = [/*'codes'=>$codes, */'qihao'=>$qihao, 'counts'=>count($buyCodes), 'profitsData'=>$profitsData];
               $m->set($mkey_profits, $profitsData, 30*24*3600);
           }
           $profits += $profitsData['profits'];
       }
       $logArr['profits'] = $profits;

       return $profits;
   }

   /**
     * @desc 判断当前期是否需要统计
     * @param int $lottery_type
     * @return mixed
     */
    public static function isCanOpStatic($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao='', $key = 'opAllStaticProfits'){
        $m = \Yii::$app->cache;
        $mkey = McKeyService::buildStaticMKey($key, $lottery_type);

        $status = $m->get($mkey); # 为true或1则不能再往下执行统计

        $qihao = $qihao?:HN0898Service::getCurrentQihao($lottery_type);
        $isExists = SscKjData::findOne(['lottery_type'=>$lottery_type,'qihao'=>$qihao]);
        //p([$lottery_type, $status, $qihao, $isExists]);
        if(!$status && $isExists) {
            $flag = true;
            ####################待观察0423######################
            //$cacheTime = BetService::getBetCacheTime($lottery_type, $qihao);
            //$m->set($mkey, true, $cacheTime); # 进来直接锁住再往下执行
            ####################待观察0423######################
        }else{
            $flag = false;
        }
        return $flag;
    }

    /**
     * @desc 处理完统计数据锁住（设置为true或1）、防止数据做没必要对的重复统计
     * @param int $lottery_type
     * @param string $qihao 最新已经开奖的期号
     * @param string $key
     * @return mixed
     */
    public static function afterOpStatic($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao='', $key = 'opAllStaticProfits'){
        $rst = true;

        $qihao = $qihao?:HN0898Service::getCurrentQihao($lottery_type);
        if(SscKjData::findOne(['lottery_type'=>$lottery_type,'qihao'=>$qihao])) {
            $m = \Yii::$app->cache;
            $mkey = McKeyService::buildStaticMKey($key, $lottery_type);

            $cacheTime = BetService::getBetCacheTime($lottery_type, $qihao);
            $rst = $m->set($mkey, true, $cacheTime);
        }

        return $rst;
    }


    /**
     * @desc 最优号码
     * @param int $type 1和值2号码类型[例如:双双重、三重]3三字现带双重4四字现带双重5四字现不带双重
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function getNiceCodes($type = 3, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $codesArr = [];
        $SscStaticYls = SscStaticYl::findAll(['type'=>$type, 'lottery_type'=>$lottery_type]);

        foreach ($SscStaticYls as $SscStaticYl){
            $yl_recodes = $SscStaticYl->yl_records;
            $ylArr = explode('-', $yl_recodes);

            $f3 = $ylArr[2]; # 上上次遗漏
            $f2 = $ylArr[1]; # 上次遗漏
            $f1 = $ylArr[0]; # 当前遗漏

            if($f3>570 && $f2>150 && $f2<260 && $f1>80 && $f1<260){
                $codesArr[] = $SscStaticYl->val;
            }
        }

        return $codesArr;
    }

    /**
     * @desc 根据开奖表数据 计算需要统计开始时间戳
     * @param int $lottery_type
     * @return false|int
     */
    public static function getStaticStartTime($lottery_type = DEFAULT_LOTTERY_TYPE){
        $time = strtotime('-120 days'); # 默认

        $date = date('Y-m-d', $time);

        $minKjDate = SscKjData::find()->select(['id', 'date'])->where(['lottery_type' => $lottery_type])->orderBy('id ASC')->asArray()->limit(1)->one()['date'];

        if($minKjDate > $date){
            $time = strtotime($minKjDate.' '.'00:00:00');
        }

        return $time;
    }

    /**
     * @desc 创建三字现汇总表
     * @param int $lottery_type
     * @return string
     */
    public static function getCreateCodeType3nSql($lottery_type = DEFAULT_LOTTERY_TYPE){
        $sql = '
CREATE TABLE `lt_static_code_3n_arise_month` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `month` varchar(10) DEFAULT NULL COMMENT \'月份\',
';

        $datas = self::getAll3n($lottery_type);
        // 取得列的列表
        foreach ($datas as $key => $row) {
            $counts[] = $row['count'];
            $vals[]  = $row['val'];
        }

        array_multisort($counts, SORT_DESC,  $vals, SORT_ASC, $datas);

        foreach ($datas as $key=>$code){
            $sql .= '    `code_'.$code['val'].'` tinyint(4) DEFAULT NULL COMMENT \''.$code['val'].'\','."\r\n";
        }

        $sql .= '
    `lottery_type` int(11) DEFAULT \'5\' COMMENT \'彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc 7:北京快乐8 8:幸运五星\',
    `created_at` int(11) DEFAULT NULL COMMENT \'创建时间\',
    `updated_at` int(11) NOT NULL COMMENT \'更新时间\',
    `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT \'更新时间\',
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT=\'三现热码月统计表\';';

        return $sql;
    }

    /**
     * @desc 创建配数遗漏表
     * @param int $lottery_type
     * @param int $type 1天2月
     * @return string
     */
    public static function getCreatePeiShuCodeTypeSql($type = 1){
        $date_type = [1=>['val'=>1, 'name'=>'date', 'cn_name'=>'天'], ['val'=>1, 'name'=>'month', 'cn_name'=>'月']];
        $sql = '
CREATE TABLE `lt_static_pei_shu_code_'.$date_type[$type]['name'].'_profits` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `'.$date_type[$type]['name'].'` varchar(10) DEFAULT NULL COMMENT \''.$date_type[$type]['cn_name'].'\',
';

        $datas = self::getAllPeiShu();

        foreach ($datas as $key=>$code_str){
            $sql .= '    `code_'.$code_str.'` decimal(10,2) DEFAULT NULL COMMENT \''.$code_str.'\','."\r\n";
        }

        $sql .= '
    `lottery_type` int(11) DEFAULT \'5\' COMMENT \'彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc 7:北京快乐 8:幸运五星\',
    `created_at` int(11) DEFAULT NULL COMMENT \'创建时间\',
    `updated_at` int(11) NOT NULL COMMENT \'更新时间\',
    `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT \'更新时间\',
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT=\'配数'.$date_type[$type]['cn_name'].'统计利润表\';';

        return $sql;
    }

    /**
     * @desc 创建每期对错表
     * @return string
     */
    public static function getCreatePeiShuTrueFalseSql(){
        $date_type = ['val'=>1, 'name'=>'date', 'cn_name'=>'天'];
$sql = '
CREATE TABLE `lt_static_pei_shu_code_true_false` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `'.$date_type['name'].'` varchar(10) DEFAULT NULL COMMENT \''.$date_type['cn_name'].'\',
    `qihao` varchar(24) DEFAULT NULL COMMENT \'期号\',
    `kj_code` varchar(24) DEFAULT NULL COMMENT \'号码\',
';

        $datas = self::getAllPeiShu();

        foreach ($datas as $key=>$code_str){
            $sql .= '    `code_'.$code_str.'` TINYINT(1) DEFAULT NULL COMMENT \''.$code_str.'\','."\r\n";
        }

$sql .= '
    `lottery_type` int(11) DEFAULT \'5\' COMMENT \'彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc 7:北京快乐 8:幸运五星\',
    `created_at` int(11) DEFAULT NULL COMMENT \'创建时间\',
    `updated_at` int(11) NOT NULL COMMENT \'更新时间\',
    `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT \'更新时间\',
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT=\'配数每期对错表\';';

        return $sql;
    }

    /**
     * @desc 获取所有三字现
     * @param int $lottery_type
     * @return array|mixed
     */
    public static function getAll3n($lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'getCreateCodeType3nSql_code';
        if(!$datas = $m->get($mkey)){
            $code3ns = ThreeNum::find()->select(['val'=>'code'])->asArray()->all();
            foreach ($code3ns as $key=>$code3n){
                $code3ns[$key]['type_2'] = 0;
                $code3ns[$key]['type_3'] = 0;
            }

            $nums3ns = SscStaticVal::find()->select(['val','type_2', 'type_3'])->where(['type'=>3])->orderBy(['type_3'=>SORT_ASC])->asArray()->all();
            $datas3n = array_merge($code3ns, $nums3ns);

            $datas = $datas3n;
            //p($datas);

            foreach ($datas as $k=>$data){
                $where = ['AND', ['=', 'lottery_type', $lottery_type], ['LIKE', 'code_3n', $data['val']], ['=', 'type_2', $data['type_2']], ['=', 'type_3', $data['type_3']]];
                $count = SscKjData::find()->where($where)->count();
                $datas[$k]['count'] = $count;
            }
            $m->set($mkey, $datas, 30*3600*24); # datas : Array ( [val] => 012 [type_2] => 0 [type_3] => 0 [nums] => 1067 )
        }

        return $datas;

    }

    /**
     * @desc 获取所有三字现
     * @param int $lottery_type
     * @return array|mixed
     */
    public static function getAllPeiShu(){
        $m = \Yii::$app->cache;
        $mkey = 'getCreatePeiShuCodeTypeSql_code';
        if(true OR !$datas = $m->get($mkey)){
            $datas = [
                '147_369',
                '258_369',
                '019_368',
                '123_678',
                '147_258',
                '017_348',
                '456_789',
                '012_789',
                '345_678',
                '357_019',
                '3b',
            ];
        }

        return $datas;

    }

    /**
     * @desc 获取所有四字现
     * @param int $lottery_type
     * @return array|mixed
     */
    public static function getCreateCodeType4nSql($lottery_type = DEFAULT_LOTTERY_TYPE){
$sql = '
CREATE TABLE `lt_static_code_4n_arise_month` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `month` varchar(10) DEFAULT NULL COMMENT \'月份\',
';

        $types = [0, 1];
        $codes = [];
        foreach ($types as $type){
            $SscKjDatas = SscKjData::find()->select(['code_4n'])->where(['lottery_type'=>$lottery_type, 'type_2'=>$type])->groupBy('code_4n ,lottery_type')->orderBy(['COUNT(id)'=>SORT_DESC])->limit(50)->asArray()->all();
            $codes = array_merge($codes, ArrayHelper::getColumn($SscKjDatas, 'code_4n'));
        }
        foreach ($codes as $key=>$code){
            $sql .= '    `code_'.$code.'` tinyint(4) DEFAULT NULL COMMENT \''.$code.'\','."\r\n";
        }

$sql .= '
    `lottery_type` int(11) DEFAULT \'5\' COMMENT \'彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc 7:北京快乐8 8:幸运五星\',
    `created_at` int(11) DEFAULT NULL COMMENT \'创建时间\',
    `updated_at` int(11) NOT NULL COMMENT \'更新时间\',
    `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT \'更新时间\',
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT=\'四现热码月统计表\';';

        return $sql;
    }

    /**
     * @desc 四定三现带双统计
     * @param int $lottery_type
     * @return array
     */
    public static function getStaticCodeType2($lottery_type = DEFAULT_LOTTERY_TYPE){
        $threeNums = ThreeNum::find()->asArray()->all();

        $codes = ArrayHelper::getColumn($threeNums, 'code');
        $staticDatas = [];
        foreach ($codes as $code){
            $where = ['AND', ['LIKE', 'code_3n', $code], ['=', 'type_2', 1], ['=', 'lottery_type', $lottery_type]];
            $count = SscKjData::find()->where($where)->count('id');
            $staticDatas[$code] = $count;
        }
        arsort($staticDatas);
        return $staticDatas;
    }

    /**
     * @desc 单个号码类型遗漏统计
     * @param string $val
     * @param int $type
     * @param int $lottery_type
     * @param int $static_nums
     * @return array|string
     */
    public static function getValStatic($val = '', $type = 4, $lottery_type = DEFAULT_LOTTERY_TYPE, $static_nums = 50000){
        if(empty($val)) return '';

        $data = SscDataService::getCodeTypeYlHistoryMiss($val, $lottery_type, $static_nums, $type);

        return $data;
    }

    /**
     * @desc 和值遗漏
     * @param string $hzs
     * @param int $lottery_type
     * @param int $static_nums
     * @return array
     */
    public static function getHzYl($hzs = '0,1,2,3,4,5,6', $lottery_type = DEFAULT_LOTTERY_TYPE, $static_nums = 10000){
        $zuHes = explode(',', $hzs);
        $miss = SscDataService::getSdHzYlHistoryMiss($zuHes, $lottery_type, $static_nums);

        return $miss;
    }

    /**
     * @desc 三字现遗漏
     * @param string $val
     * @param int $lottery_type
     * @param int $static_nums
     * @return array
     */
    public static function get3NumStatic($val = '', $lottery_type = DEFAULT_LOTTERY_TYPE, $static_nums = 10000){

        $miss = SscDataService::get3NumHistoryMiss($val, $lottery_type, $static_nums); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];

        return $miss;
    }

    /**
     * @desc 号码类型：双重、双双重、四重、三兄弟、四兄弟 遗漏统计
     * @param string $val
     * @param int $lottery_type
     * @param int $static_nums
     * @return array
     */
    public static function getCodeTypeStatic($val = '', $lottery_type = DEFAULT_LOTTERY_TYPE, $static_nums = 5000){

        $miss = StaticsQxMissService::getCodeTypeHistoryMiss($val, $lottery_type, $static_nums); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
        $miss['val_desc'] = \backend\service\SscDataService::getStaticNameByType($val);

        return $miss;
    }

    /**
     * @param $data
     * @param $type 1:遗漏查询 2利润统计
     * @return array
     */
    public static function queryCodeTypeStatic($data, $type = 1){
        $code_types = [
            1 => 2, # 二定
            2 => 3, # 三定
            3 => 4, # 四定
        ];
        $code_type = $code_types[$data['UserSysPlans']['playway']];
        $in_codes = $data['UserSysPlans']['in_codes']? trim($data['UserSysPlans']['in_codes'], ','):''; # 在此号码范围内
        $in_codes = (!empty($in_codes)) ? explode(',', str_replace(['，', ' '], ',', $in_codes)) : [];

        $base_codes = $data['UserSysPlans']['base_codes']? trim($data['UserSysPlans']['base_codes'], ','):''; # 在此号码范围内
        $base_codes = (!empty($base_codes)) ? explode(',', str_replace(['，', ' '], ',', $base_codes)) : [];

        $tz_type = $data['UserSysPlans']['tz_type'];
        $lottery_type = $data['UserSysPlans']['lottery_type'];
        $model = new UserSysPlans();
        UserSysPlansService::preOpData($data, $user_id=1);
        $model->load($data);
        $codes_hz = json_decode($model->hz_Arr, true);
        $baseOnCodes = array_merge($in_codes, $base_codes);
        $codes = NumService::getCodesKuaiXuan($codes_hz, $code_type, $baseOnCodes);

        /*
        ####################### 调整 开始 ########################
        # 动态过滤1 -- 遗漏查询调整
        if(isset($codes_hz['is_filter_dynamic']) && $codes_hz['is_filter_dynamic']==1 && count($codes_hz['filter_dynamic_types'])>0){
            $filter_dynamic_codes = NumService::getBeforeKjCodesDynamic($model);
            if(!empty($filter_dynamic_codes)){
                $codesArr = array_intersect($codes, $filter_dynamic_codes); # 返回$codesArr和$filter_dynamic_codes交集
            }
        }
        # 动态过滤2 -- 遗漏查询调整
        if(isset($codes_hz['filter_dynamic_types2'])){
            $filter_dynamic_codes2 = DynamicFilterService::getFilterDynamic2($model);
            if(!empty($filter_dynamic_codes2)){
                $codesArr = array_intersect($codesArr, $filter_dynamic_codes2); # 返回$codesArr和$filter_dynamic_codes交集
            }
        }
        ####################### 调整 开始 ########################
        */

        if($type == 1) {
            # 1 遗漏
            $rst = StaticService::getYlByCodes($codes, $lottery_type, $tz_type);
        }elseif($type == 3){
            # 2 遗漏2：1-0-1-1-1-0-0-0-0   0代表中1代表不中
            $rst = StaticService::getYlByCodes2($codes, $lottery_type, $tz_type);
        }else{
            # 利润
            $rst = StaticService::getYlByCodes($codes, $lottery_type, $tz_type);
        }
        $rst['code_desc'] = \backend\service\NumService::getDescByKuaixuan($codes_hz);
        if(!empty($base_codes)){
            $code_desc = '导入号码('.count($base_codes).'):'.substr($data['UserSysPlans']['base_codes'], 0, 60).'...';
            if(empty($rst['code_desc'])){
                $rst['code_desc'] = $code_desc;
            }else{
                $rst['code_desc'] .= ','.$code_desc;
            }
        }

        return $rst;
    }

    /**
     * @desc 号码类型监控
     * @param int $lottery_type
     * @return array
     */
    public static function static4dYlCode(){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        $lottery_types = StaticService::getLotteryTypes();
        $wheres = [
            1 => ['type_22'=>0, 'type_2'=>1, 'type_3'=>0, 'type'=>4], # 四现带双：除双双重、取双重、除三重
            2 => ['type_22'=>0, 'type_2'=>0, 'type_3'=>0, 'type'=>4], # 四现不带双：除双双重、除双重、除三重
        ];
        foreach ($lottery_types as $k=>$lottery_type){
            foreach ($wheres as $key=>$where){
                $where['lottery_type'] = $lottery_type;
                $datas = SscStaticYl::findAll($where);
                $numDatas = [];
                foreach ($datas as $data){
                    $tmpYls = explode('-', $data->yl_records);
                    //if(($tmpYls[0] + $tmpYls[2]>2500) OR ($tmpYls[0]>1000 && $tmpYls[2]>1000)){ # 四现带双
                    if(($tmpYls[0] + $tmpYls[2]>3500) OR ($tmpYls[0]>1700 && $tmpYls[2]>1700)){ # 四现不带双
                        $numDatas[] = ['val'=>$data->val, 'yl_records'=>$data->yl_records];
                    }
                }
                $rst['data'][$lottery_type][$key] = $numDatas;
            }
        }

        return $rst;
    }

    /**
     * @desc 号码类型月年统计
     * @param $data
     * @param $static_type - 1:月、2:年
     * @return array
     */
    public static function queryCodeTypeProfits($data, $static_type){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        $code_types = [
            1 => 2, # 二定
            2 => 3, # 三定
            3 => 4, # 四定
        ];

        $code_type = $code_types[$data['UserSysPlans']['playway']];
        $tz_type = $data['UserSysPlans']['tz_type'];
        $lottery_type = $data['UserSysPlans']['lottery_type'];
        $model = new UserSysPlans();
        UserSysPlansService::preOpData($data, $user_id=1);
        $model->load($data);
        $codes_hz = json_decode($model->hz_Arr, true);
        $codes = NumService::getCodesKuaiXuan($codes_hz, $code_type);

        $datas = StaticService::getProfitsDatasByCodes($codes, $static_type, $lottery_type);
        $rst['datas'] = $datas;
        $rst['code_desc'] = \backend\service\NumService::getDescByKuaixuan($codes_hz);

        return $rst;
    }

    /**
     * @desc 号码利润统计
     * @param array $codes
     * @param int $static_type - 1月2年
     * @param int $lottery_type
     * @return array
     */
    public static function getProfitsDatasByCodes($codes=[], $static_type=1, $lottery_type=DEFAULT_LOTTERY_TYPE){
        if($static_type == 1){
            # 统计维度：月
            $groupBy = 'LEFT(date,7)';
        }elseif($static_type==2){
            $groupBy = 'LEFT(date,4)';
            # 统计维度：年
        }
        $d = SscKjData::find()->select(['count'=>'count(id)', 'time'=>$groupBy])
            ->where(['AND',
                ['IN', 'code_4n_str', $codes],
                ['=', 'lottery_type', $lottery_type],
            ])
            ->groupBy([$groupBy])
            ->orderBy(['id'=>SORT_DESC])->asArray()->all();
        $keys1 = ArrayHelper::getColumn($d, 'time');
        $vals1 = ArrayHelper::getColumn($d, 'count');
        $data1 = array_combine($keys1, $vals1); # 每个周期中将期数
        //p([$static_type, $d, $data1]);

        $qs = SscKjData::find()->select(['count'=>'count(id)', 'time'=>$groupBy])
            ->where(['=', 'lottery_type', $lottery_type])
            ->groupBy([$groupBy])
            ->orderBy(['id'=>SORT_DESC])->asArray()->all();
        $keys2 = ArrayHelper::getColumn($qs, 'time');
        $vals2 = ArrayHelper::getColumn($qs, 'count');
        $data2 = array_combine($keys2, $vals2); # 每个周期开奖期数
        //p([$static_type, $qs, $data2]);

        $counts = count($codes);
        $profits = [];
        foreach ($data2 as $timer=>$qishu_per_section){
            # 利润 = 中奖金额(中奖次数*赔率) - 投注金额(号码注数*投注金额*每个周期期数)
            $tmpData = [
                'time' => $timer,
                'profits' => $data1[$timer] * 995 - $counts * 0.1 * $qishu_per_section,
                'zj_qishus' => $data1[$timer],
                'all_qishus' => $qishu_per_section,
                'counts' => $counts,
                'qishus' => $qishu_per_section,
            ];
            $profits[] = $tmpData;
        }

        return $profits;
    }
}
