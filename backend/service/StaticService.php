<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\BettingRecords;
use backend\models\Num4Type;
use backend\models\Ssc2numsVal;
use backend\models\Ssc2numsYl;
use backend\models\SscKjData;
use backend\models\SscKjData3num;
use backend\models\SscKjDataDs;
use backend\models\SscStaticVal;
use backend\models\Static3numArisePerdate;
use backend\models\Static4dProfits;
use backend\models\Static4dProfitsPerdate;
use backend\models\StaticCodeTypeArisePerdate;
use backend\models\StaticHzProfits;
use backend\models\StaticHzProfitsPerdate;
use backend\models\StaticPerHzPerdateProfits;
use backend\models\StaticProfits;
use backend\models\SystemConfig;
use backend\models\TzTypes;
use yii\helpers\ArrayHelper;
use  yii;

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
    public static  $kArr = [0=>'所有', 1=>'一双三单、一单三双', 2=>'两双两单', 3=>'四双四单', 4=>'一单三双', 5=>'一双三单', 6=>'一单三双|四双', 7=>'一双三单|四单', 8=>'四双', 9=>'四单', 10=>'单数量', 11=>'双数量', 12=>'一单三双|四单', 13=>'一双三单|四双', 14=>'一单三双|四单|四双', 15=>'一双三单|四单|四双', 20=>'四定和值', 21=>'和值范围四定', 22=>'单双'];

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
                $LastStaticProfits = StaticProfits::find()->where($where)->orderBy(['id'=>SORT_DESC])->one();
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
        $SscKjDatas = SscKjData::find()->where($where)->limit($num)->all();
        $allCost = 0.00; # 成本
        $allZjBouns = 0.00; # 中奖金额
        $allProfits = 0.00; # 利润
        $zjCount = 0;
        foreach ($SscKjDatas as $codeKey => $SscKjData) {
            if ($codeKey == ($num - 1)) break;
            //$kjData = $SscKjData->code_str;
            $kjData = $SscKjData->kj_code;
            if ($fx == 0) {
                $resultCodes = self::getDiffCodes($kjData);
            } else {
                $resultCodes = self::getSameCodes($kjData, 1);
            }
            $kjCodes = substr($SscKjDatas[$codeKey + 1]['code_str'], 0, 7);
            $rst = OpKjService::opKjData4($resultCodes, $kjCodes);
            //p([$kjData, $resultCodes, $kjCodes, $rst],0); //p($rst);

            $cost = 9 * 62.5;
            $zjBouns = 999.5 * $rst['data']['zjTimes'];
            if ($zjBouns > 0) $zjCount = $zjCount + 1;
            $profits = $zjBouns - $cost;

            $allCost += $cost;
            $allZjBouns += $zjBouns;
            $allProfits += $profits;
        }
        p(['staticQihao'=>$SscKjData->qihao, 'zjCount'=>$zjCount, 'allCost'=>$allCost, 'allZjBouns'=>$allZjBouns, 'allProfits'=>$allProfits]);
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
        $mkey = 'MONTH_STATIC_DATA_'.$lottery_type.'_'.$month;
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
            $m->set($mkey, $allStatic, 6*30*24*60*60);
        }

        //echo $date.'月份：';
        return $allStatic;
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
        /*
        $num = count($SscKjDatas);
        //p($SscKjDataDs);
        foreach ($SscKjDatas as $SscKjData){
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
        */
        //p([$static, count($SscKjDataDs)]);

        $allStatic = [];
        foreach ($typeArr as $k=>$hzArr){

            $where = ['LEFT(date, 7)'=>$month, 'codes_4nums_hz'=> $hzArr, 'lottery_type'=>$lottery_type];
            $zJcounts = SscKjData::find()->where($where)->orderBy(['id'=>SORT_ASC])->count(); # 中奖次数
            $where = ['codes_hz'=>$hzArr];
            $NumCounts = Num4Type::find()->where($where)->orderBy(['id'=>SORT_ASC])->count(); # 期数

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
            $m->set($mkey, $allStatic, 6*30*24*60*60);
        }

        //echo $date.'月份：';
        return $allStatic;
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
        $mkey = 'DATE_STATIC_DATA_'.$date;
        $typeArr = self::$typeArr;
        $where = ['lottery_type'=>$lottery_type, 'date' => $date];
        $static = [ 0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0, 10=>0, 10=>0]; # 统计每种组合出现次数
        //$SscKjDatas = SscKjData::find()->where($where)->limit($num)->all();
        $SscKjDataDs = SscKjDataDs::find()->where($where)->orderBy(['id'=>SORT_ASC])->all();
        $num = count($SscKjDataDs);
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
        //p([$static, count($SscKjDataDs), $lottery_type, $static[$lottery_type]]);

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
            $m->set($mkey, $allStatic, 6*30*24*60*60);
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
        $mkey = 'DATE_STATIC_HZ_DATA_'.$lottery_type.'_'.$date;
        $typeArr = self::$typeHzArr;

        if($allStatic = $m->get($mkey)) return $allStatic;

        $allCounts = SscKjData::find()->where(['date'=>$date, 'lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_ASC])->count();
        $allStatic = [];
        foreach ($typeArr as $k=>$hzArr){
            $where = ['date' => $date, 'lottery_type'=>$lottery_type, 'codes_4nums_hz'=> $hzArr];
            $zJcounts = SscKjData::find()->where($where)->orderBy(['id'=>SORT_ASC])->count(); # 中奖次数
            $where = ['codes_hz'=>$hzArr];
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
        if($date != date('Y-m-d')){
            $m->set($mkey, $allStatic, 6*30*24*60*60);
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
            $where = ['codes_hz'=>$hz];
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
            $m->set($mkey, $allStatic, 6*30*24*60*60);
        }

        return $allStatic;
    }

    /**
     * @desc 所有月份4定利润统计
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function allMonthStaticProfits($lottery_type = DEFAULT_LOTTERY_TYPE){
        $months = [];
        for ($i=0; $i>=0; $i--){
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
    public static function allMonthSdHzStaticProfits($lottery = DEFAULT_LOTTERY_TYPE){
        $months = [];
        for ($i=3; $i>=0; $i--){
            $months[] = date('Y-m', strtotime('-'.$i.' months'));
        }
        $allStatic = [];
        foreach ($months as $month){
            $statics = self::staticSdHzProfits($month, $lottery);
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
     * @desc 记录每天的四定统计 - 写表
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array
     */
    public static function static4dPerDateProfits($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = ['status'=>200, 'msg'=>'处理成功'];
        $allStaticProfits = self::allDateStaticProfits($lottery_type);
        $tmpProfits = [];
        foreach ($allStaticProfits as $key=>$allStaticProfit){
            $tmpProfits[] = $allStaticProfit;
        }

        foreach ($tmpProfits as $tmpProfit){
            foreach ($tmpProfit as $date=>$tmp){
                if($date != date('Y-m-d')) continue;
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

                $rst = $Static4dProfits->save();
            }
        }

        return ['status'=>200, 'data'=>$setData, 'rst'=>$rst];
    }

    /**
     * @desc 利润统计
     * @return array
     */
    public static function opStatic(){
        $lottery_types = StaticService::getLotteryTypes();
        foreach ($lottery_types as $lottery_type) {
            if(!$status = StaticService::isCanOpStatic($lottery_type, $mkey = 'opStatic')) continue;
            $rst[] = StaticService::staticSDHzPerDateProfits($lottery_type); # 每天四定和值利润统计
            $rst[] = StaticService::staticHzMonthsProfits($lottery_type); # 每月四定和值利润统计
            $rst[] = StaticService::allHzStaticProfitsPerdate($lottery_type);//p($rst);# 循环计算每天每个和值利润统计
            StaticService::afterOpStatic($lottery_type, 'opStatic');
        }

        return $rst;
    }

    /**
     * @desc 记录每天四定和值利润统计 - 写表
     * @return array
     */
    public static function staticSDHzPerDateProfits($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = ['status'=>200, 'msg'=>'处理成功'];
        $allStaticProfits = self::allDateHzStaticProfits($lottery_type);

        //p($allStaticProfits);

        $tmpProfits = $allStaticProfits;

        foreach ($tmpProfits as $key=>$tmpProfit){
            foreach ($tmpProfit as $k=>$tmp){
                $date = $k;
                if($date != date('Y-m-d')) continue;
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
    public static function allDateStaticProfits($lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'allDateStaticProfits_PERDATE_'.$lottery_type.'_19';

        $allStatic = [];
        for($s=0; $s<5; $s++){
            if(!$time = $m->get($mkey)) {
                $time = strtotime('-1 day');
            }else{
                $time = $time + 24 * 3600;
            }

            $date = date('Y-m-d', $time);
            $date = min([date('Y-m-d'), $date]);
            if($date>date('Y-m-d')) break;
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
        $mkey = 'allDateHzStaticProfits_PERDATE_08_'.$lottery_type;

        $allStatic = [];
        for($s=0; $s<5; $s++){
            if(!$time = $m->get($mkey)) {
                $time = strtotime('-1 day');
            }else{
                $time = $time + 24 * 3600;
            }

            $date = date('Y-m-d', $time);
            $date = min([date('Y-m-d'), $date]);
            if($date>date('Y-m-d')) break;
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
     * @desc 记录每个月的四定统计 - 写表
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

        foreach ($tmpProfits as $tmpProfit){
            foreach ($tmpProfit as $month=>$tmp){
                if($month != date('Y-m')) continue;
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
               if($month != date('Y-m')) continue;
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
                p(['nums'=>$nums, 'rst'=>$codesArr, 'qihao'=>$data['qihao'], 'counts'=>$countsNum, 'data'=>$data, 'profits'=>$profits[$date]],0);
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
        $m = \Yii::$app->cache;
        $mkey = 'allHzStaticProfitsPerdate_01_'.$lottery_type;

        $allStatic = [];
        for($s=0; $s<5; $s++){
            if(!$time = $m->get($mkey)) {
                $time = strtotime('-1 day');
            }else{
                $time = $time + 24 * 3600;
            }

            $date = date('Y-m-d', $time);
            $date = min([date('Y-m-d'), $date]);
            if($date>date('Y-m-d')) break;
            if($statics = self::staticSdHzProfitsPerdate($date, $lottery_type)){
                //p(['statics'=>$statics]);
                $setData = ['date'=>$date, 'lottery_type'=>$lottery_type];
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
        for ($i=1; $i<=100; $i++){
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
        $SscKjData3nums = SscKjData3num::find()->where(['date'=>$date, 'lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->limit(2000)->all();
        if(!$SscKjData3nums) return ['status'=>300, 'msg'=>'无统计数据'];
        $staticDatas = [];
        foreach ($SscKjData3nums as $key=>$SscKjData3num){
            if(!$SscKjData3num->code_3n) continue;
            $codes3Nums = explode(',', $SscKjData3num->code_3n);
            foreach ($codes3Nums as $nums){
                if(!isset($staticDatas[$nums])) $staticDatas[$nums] = 0;
                $staticDatas[(string)$nums] += 1;
            }
        }

        $tmpData = [];
        foreach ($staticDatas as $key=>$data){
            if($data>220 OR in_array($key,['014', '147', '124'])){
                $tmpData[$key] = $data;
            }
        }
        arsort($staticDatas);

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
        $codeTypes = StaticService::getAllCodeTypes();

        $staticDatas = [];
        foreach ($codeTypes as $codeType) {
            $where = ['date'=>$date, 'lottery_type'=>$lottery_type];
            $codeTypeFields = explode(',', $codeType);
            foreach ($codeTypeFields as $codeTypeField){
                $where[$codeTypeField] = 1;
            }
            $SscKjDatas = SscKjData::find()->where($where)->orderBy(['id'=>SORT_DESC])->limit(2000)->all();
            $staticDatas[str_replace(',', '_', $codeType)]  = count($SscKjDatas);
        }

        if($date != date('Y-m-d')){
            $m->set($mkey, $staticDatas, 7*24*3600);
        }

        return $staticDatas;
    }

    /**
     * @desc 返回号码类型
     * @return array
     */
    public static function getAllCodeTypes(){
        $SscStaticVals = SscStaticVal::find()->where(['status'=>1])->asArray()->all();
        $m = \Yii::$app->cache;

        $mkey = 'getAllCodeTypes_01';
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
    public static function allDateStatic3NumsPerDate( $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'allDateStatic3Nums_PERDATE_02_'.$lottery_type;

        $allStatic = [];
        for($s=0; $s<5; $s++){
            if(!$time = $m->get($mkey)) {
                $time = strtotime('-1 day');
            }else{
                $time = $time + 24 * 3600;
            }

            $date = date('Y-m-d', $time);
            $date = min([date('Y-m-d'), $date]);
            if($date>date('Y-m-d')) break;
            if($statics = self::staticKj3NumCounts($date, $lottery_type)){
                $setData = [];
                foreach ($statics as $key=>$static){
                    $setData['codes_'.$key] = $static;
                }
                if(!$Static3numArisePerdate = Static3numArisePerdate::findOne(['date'=>$date, 'lottery_type'=>$lottery_type])){
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
            $m->set($mkey, $time, 7*24*3600);
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
        $mkey = 'allDateStaticCodeType_PERDATE_01_'.$lottery_type;

        $allStatic = [];
        for($s=0; $s<5; $s++){
            $StaticTables = StaticCodeTypeArisePerdate::find()->all();
            if(count($StaticTables) == 0) $beforeDays = 120; # 数据表为空时默认统计前120前的数据
            if(!$time = $m->get($mkey)) {
                $time = strtotime('-'.$beforeDays.' day');
            }else{
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
     * @desc 统计所有二字现遗漏
     * @return array
     */
   public static function staticAll2NumsYl(){

       $rst = ['status'=>200, 'msg'=>'处理完成'];
       $lottery_types = self::getLotteryTypes();
       foreach ($lottery_types as $lottery_type) {
           $status = StaticService::isCanOpStatic($lottery_type, $mkey = 'staticAll2NumsYl_0');
           if(!$status) continue;
           $rst['static2NumsYl'] = StaticService::static2NumsYl($lottery_type);
           StaticService::afterOpStatic($lottery_type, 'staticAll2NumsYl_0');
       }

       return $rst;
   }

    /**
     * @desc 二字现遗漏，主要双重、对数、两兄弟
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
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
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @param int $limit
     * @return array
     */
   public static function get2NumsYlRecords($nums, $lottery_type = DEFAULT_LOTTERY_TYPE, $limit = 600){
       if(strlen($nums) != 2) return [];

       $last_id = SscDataService::getKjDataLastId($lottery_type); # 表记录最后一条id
       $limit_id = $last_id - $limit;

       $codes = [$nums[0], $nums[1]];
       $where = [
            'OR',
            ['like', 'LEFT(code_str,7)', '%'.$codes[0].','.$codes[1].'%', false],
            ['like', 'LEFT(code_str,7)', '%'.$codes[0].'%'.$codes[1].'%', false],

            ['like', 'LEFT(code_str,7)', '%'.$codes[1].','.$codes[0].'%', false],
            ['like', 'LEFT(code_str,7)', '%'.$codes[1].'%'.$codes[0].'%', false],
        ];
       $SscKjDatas = SscKjData::find()->select(['id','kj_code','qihao'])->where($where)->andWhere('id>='.$limit_id)->andWhere(['lottery_type'=>$lottery_type])->limit($limit)->orderBy(['id'=>SORT_DESC])->asArray()->all();
       //p($SscKjDatas);

       $yl_records = '';
       $max_miss = 0;
       foreach ($SscKjDatas as $k=>$SscKjData){
           if($k == 0) continue;
           $yl = $SscKjDatas[$k-1]['id']-$SscKjData['id'];
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
           'current_miss' => $last_id - $SscKjDatas[0]['id'],
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
   public static function opAllStaticProfits(){
       $lottery_types = self::getLotteryTypes();
       foreach ($lottery_types as $lottery_type){
           if(!$status = StaticService::isCanOpStatic($lottery_type, $mkey = 'opAllStaticProfits')) continue;
           $rst['opStaticProfits'] = StaticService::opStaticProfits($lottery_type);
           $rst['allDateStatic3NumsPerDate'] = StaticService::allDateStatic3NumsPerDate($lottery_type); # 上奖三字现

           $rst['static4dMonthsProfits'] = StaticService::static4dMonthsProfits($lottery_type); # 每月四定单双利润统计，四定类型详见：StaticService::$typeArr
           $rst['static4dPerDateProfits'] = StaticService::static4dPerDateProfits($lottery_type); # 每天四定利润统计，四定类型详见：StaticService::$typeArr

           $rst['allDateStaticCodeTypePerDate'] = StaticService::allDateStaticCodeTypePerDate($lottery_type); # 号码类型每天数量统计

           StaticService::afterOpStatic($lottery_type, 'opAllStaticProfits');
       }

       return $rst;
   }

    /**
     * @desc 需要处理的猜中
     * @return array
     */
   public static function getLotteryTypes(){
       $lottery_types = SystemConfig::findOne(['key'=>'lottery_types'])->value;
       $lottery_typesArr = explode(',', $lottery_types);

       return $lottery_typesArr;
   }

    /**
     * @desc 给定号码计算遗漏 未完待续 -- 2019.05.09
     * @param $codes
     * @param int $lottery_type
     * @param int $playway
     * @param $tz_type 一字定倍数切换方案
     * @return int
     */
   public static function getYlByCodes($codes, $lottery_type = DEFAULT_LOTTERY_TYPE, $tz_type = 18){
       $yl = 0;
       $tzTypes = TzTypes::findOne(['type'=>$tz_type]);
       $playway = $tzTypes->playway;

       switch ($playway){
           case 1: # 二字定
               break;
           case 2: # 三字定
               break;
           case 3: # 四字定
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
                $record = SscKjData::find()->select(['qihao','code_str'])->where($where)->andWhere(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->asArray()->one();
                $last_Qihao = SscDataService::getKjDataLastQihao($lottery_type); # 表记录最后一条id
                $yl = self::qihaoSpace($record['qihao'], $last_Qihao);
                break;
       }
       //p(['codes'=>$codes, $record, $last_Qihao, 'yl'=>$yl]);

       return $yl;
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

               $profitsData = OpKjService::calcuProfits(3, $codes, $SscKjData->code_str, 0.1);
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
    public static function isCanOpStatic($lottery_type = DEFAULT_LOTTERY_TYPE, $key = 'opAllStaticProfits'){
        $m = \Yii::$app->cache;
        $mkey = McKeyService::buildStaticMKey($key, $lottery_type);

        $status = $m->get($mkey); # 为true或1则不能再往下执行统计
        return !$status;
    }

    /**
     * @desc 处理完统计数据锁住（设置为true或1）、防止数据做没必要对的重复统计
     * @param int $lottery_type
     * @return mixed
     */
    public static function afterOpStatic($lottery_type = DEFAULT_LOTTERY_TYPE, $key = 'opAllStaticProfits'){
        $rst = true;

        $qihao = HN0898Service::getQihao($lottery_type);
        if($SscKjData = SscKjData::findOne(['lottery_type'=>$lottery_type,'qihao'=>$qihao])) {
            $m = \Yii::$app->cache;
            $mkey = McKeyService::buildStaticMKey($key, $lottery_type);

            $qihao = HN0898Service::getQihao($lottery_type);
            $cacheTime = BetService::getBetCacheTime($lottery_type, $qihao);
            $rst = $m->set($mkey, true, $cacheTime);
        }

        return $rst;
    }







}