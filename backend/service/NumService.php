<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\CodeTypes;
use backend\models\Num4Type;
use backend\models\SscKjData;
use backend\models\StaticProfits;
use backend\models\SystemConfig;
use backend\models\UserSysPlans;
use common\tools\Tool_Common;
use backend\models\ThreeNum;
use yii\helpers\ArrayHelper;
use  yii;

class NumService extends BaseService {

    /**
     * @description 根据开奖号码返回三字现
     * @param $codes 格式：3,5,6,2
     * @param string $split
     * @return array
     */
    public static function getThreeNumByCodes($codes,$split = ','){
        $threeNumArr = [];
        if($split == ''){
            $codesArr = [$codes[0],$codes[1],$codes[2],$codes[3]];
        }else{
            $codesArr = array_unique(explode($split, $codes));
        }
        sort($codesArr);

        $len = count($codesArr);
        if($len >= 3){
            if($len == 3){
                $threeNumArr[] = implode('',$codesArr);
            }else{
                foreach ($codesArr as $key=>$codeArr){
                    $tmpArr = $codesArr;
                    unset($tmpArr[$key]);
                    $threeNumArr[] = implode('',$tmpArr);
                }

            }
            sort($threeNumArr);
        }
        return $threeNumArr;
    }

    /**
     * @description 获取所有三字现集合，120组
     * @return array|mixed
     */
    public static function getAllThreeNums(){
        $m = \Yii::$app->cache;
        $mkey = 'THREE_NUMS_ARR';
        if(!$threeNumsArr = $m->get($mkey)){
            $threeNums = ThreeNum::find()->select('code')->asArray()->all();
            foreach ($threeNums as $key=>$v){
                $threeNumsArr[] = $v['code'];
            }
            $m->set($mkey,$threeNumsArr, 6*3600);
        }

        return $threeNumsArr;
    }

    /**
     * @desc 获取系统模拟投注四定和值
     * @param $counts 获取几组和值
     * @param string $qihao
     * @param int $type  1倒序剔除2随机剔除
     * @return array
     */
    public static function getSystemTzHz($counts = 5, $qihao = '190329036', $type = 1){
        $HeZhis = SystemConfig::findOne(['key'=>'system_tz_hz'])->value;
        $HeZhiArr = explode(',', $HeZhis);

        $orderByArr = [
            1 => ['id'=>SORT_DESC],
            2 => ['RAND()'=>SORT_DESC],
        ];
        $SscKjDatas = SscKjData::find()->select(['codes_4Nums_hz'])->where('qihao<'.$qihao)->orderBy($orderByArr[1])->limit(20)->asArray()->all();
        $hzsArr = ArrayHelper::getColumn($SscKjDatas, 'codes_4Nums_hz');
        if($type == 2){
            shuffle($hzsArr);
        }
        //p([$hzsArr]);
        foreach ($hzsArr as $k=>$hz){
            foreach ($HeZhiArr as $key=>$heZhi){
                if($hz == $heZhi){
                    unset($HeZhiArr[$key]);
                    if(count($HeZhiArr) == $counts){
                        return $HeZhiArr;
                    }
                }
            }
        }
        return $HeZhiArr;
    }

    /**
     * @desc 根据号码str和个数，返回数组
     * @param $codes_str - 号码，例如：1234
     * @param int $nums - 个数，比如：2
     * @return array - [12,13,14,23,24,34]
     */
    public static function getCodesArrByNum($codes_str, $nums=2){
        $len = strlen($codes_str);

        $codesArr = [];
        for($i=0; $i<$len; $i++){
            if($nums<=1){ # 1个号码
                $codesArr[] = $codes_str[$i];
            }elseif($nums>=2){
                for($j=1; $j<$len; $j++){
                    if($j<=$i) continue;
                    if($nums == 2){ # 两个号码
                        $codesArr[] = $codes_str[$i].$codes_str[$j];
                    }elseif($nums >= 3){
                        if($nums == 3){ # 3个号码
                            for ($k=2; $k<$len; $k++){
                                if($k<=$j) continue;
                                $codesArr[] = $codes_str[$i].$codes_str[$j].$codes_str[$k];
                            }
                        }else{
                            for ($k=2; $k<$len; $k++){
                                if($k<=$j) continue;
                                for ($l=3; $l<$len; $l++){ # 4个号码
                                    if($l<=$k) continue;
                                    $codesArr[] = $codes_str[$i].$codes_str[$j].$codes_str[$k].$codes_str[$l];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $codesArr;
    }

    /**
     * @desc 排除多少期内的码，利润统计
     * @param string $qihao
     * @param int $nums
     * @param string $type
     * @return array
     */
    public static function getRemoveCodes($qihao = '', $nums = 2000, $type = 'ssc'){
        $codes = [];
        $m = \Yii::$app->cache;
        $zjKey ='ZJ_REMOVEE_0_'.$qihao;
        if($r = $m->get($zjKey)) return $r;
        if(!$qihao) return ['status'=>300, 'msg'=>'期号不能为空'];
        $nums = $nums + 230;
        switch ($type){
            case 'ssc':
                //$mkey = 'CACHE_REMOVE_CODES_'.$type;
                $mkey = self::getRemoveMkey($qihao, $nums, $type);
                $SscKjDatas = SscKjData::find()->select(['LEFT(code_str, 7) AS code_str'])->where('qihao<'.$qihao)->limit($nums)->orderBy(['id'=>SORT_DESC])->asArray()->all();
                $codes = ArrayHelper::getColumn($SscKjDatas, 'code_str');
                foreach ($codes as $key=>$code){
                    $mckey = $mkey.'_'.$code;
                    $m->set($mckey, 1, 10*60);
                }

                $all4NumCodes = Num4Type::find()->select(['code AS code_str'])->where(['code_type'=>4])->orderBy(['id'=>SORT_DESC])->asArray()->all();
                $nums4Codes = ArrayHelper::getColumn($all4NumCodes, 'code_str');
                $allCodes = [];
                foreach ($nums4Codes as $num){
                    $mckey = $mkey.'_'.$num;
                    if($m->get($mckey)) continue;
                    $allCodes[] = $num;
                }
                $kjCodes = SscKjData::find()->select(['qihao', 'LEFT(code_str, 7) AS code_str'])->where(['qihao'=>$qihao])->asArray()->one();
                if(in_array($kjCodes['code_str'], $allCodes)){
                    $r = 1;
                    $m->set($zjKey, $r, 10*60);
                }else{
                    $r = 2;
                }

                $m->set($zjKey, $r, 10*60);
                return $r;

                break;
            case 'pl5':
                break;
            default:;
        }


        return $codes;
    }

    /**
     * @desc 获取期号key
     * @param $qihao
     * @param $nums
     * @param $type
     * @return string
     */
    public static function getRemoveMkey($qihao, $nums, $type){
        $mkey = 'CACHE_REMOVE_CODES_5_'.$type.'_'.$qihao;

        return $mkey;
    }

    /**
     * @desc 获取两兄弟和值号码
     * @return array
     */
    public static function get2bCodeArr(){
        $m = \Yii::$app->cache;
        $mkey = 'CODES_2B_CODES_MCKEY_0';
        //if($codesArr = $m->get($mkey)) return $codesArr;
        # 和值范围 start
        //$rst = NumService::getSystemTzHz($nums, $data['qihao'], 1); # 剔除(有随机和倒序) 和值
        //$HeZhis = [8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26];
        $HeZhis = SystemConfig::findOne(['key'=>'system_tz_hz'])->value;
        $heZhiArr = explode(',', $HeZhis);


        # 是否随机过滤和值号码
        $filter_hz_code_status = SystemConfig::findOne(['key'=>'filter_hz_code_status'])->value;
        $filter_hz_nums = SystemConfig::findOne(['key'=>'filter_hz_nums'])->value;
        if($filter_hz_code_status){
            /* 随机剔除 */
            for ($i=1; $i<=$filter_hz_nums; $i++){
                $v = rand($heZhiArr[0], end($heZhiArr));
                $rndKey = array_search($v, $heZhiArr);
                unset($heZhiArr[$rndKey]);
            }
        }

        //return $heZhiArr;
        //if($data['qihao'] == '190401023') p([$nums, $data, $rst]);
        $where = ['codes_hz'=>$heZhiArr, 'type_2b'=>1, 'type_3'=>0, 'type_4b'=>0, 'code_type'=>4];
        $Num4Types = Num4Type::find()->where($where);
        $codes = $Num4Types->asArray()->all();
        $codesArr = ArrayHelper::getColumn($codes, 'code');
        # 和值范围 end
        $m->set($mkey, $codesArr, 7*24*3600);

        return $codesArr;
    }

    /**
     * @desc 取两兄弟后剔除最近出现的多少期号码
     * @param int $lottery_type
     */
    public static function filterLaterCodesAnd2bcode($lottery_type = 5, $qihao = '190516056', $nums = 1000){

        //$filter_hz_code_status = SystemConfig::findOne(['key'=>'filter_hz_code_status'])->value;
        $filterCodes = self::getRecentlyCodes($lottery_type, $qihao, $nums); # 最近 $nums 期号码

        $codesArr = self::get2bCodeArr();
        //p(count($codesArr));
        foreach ($filterCodes as $filterCode){
            $filterKey = array_search($filterCode, $codesArr);
            if($filterKey !== false){
                $tmpCode[$filterKey] = $codesArr[$filterKey];
                unset($codesArr[$filterKey]);
            }
        }
        //p($tmpCode);

        return $codesArr;
    }

    /**
     * @desc 最近出现的号码
     * @param int $lottery_type
     * @param int $nums
     * @return array
     */
    public static function getRecentlyCodes($lottery_type = 5, $qihao = '190516056', $nums = 500){
        $limit = $nums + ceil($nums * 0.3);
        $SscKjDatas = SscKjData::find()->where(['AND', ['=', 'lottery_type', $lottery_type], ['<', 'qihao', $qihao]])->orderBy(['id'=>SORT_DESC])->limit($limit)->all();
        $codesArr = [];
        foreach ($SscKjDatas as $SscKjData){
            $codesArr[] = substr($SscKjData->code_str, 0,7);
            //$codesArr = array_unique($codesArr);

            //if(count($codesArr) == $nums) break;
        }

        return $codesArr;
    }


    /**
     * @desc 根据四定单双1122返回号码组合
     * @param $codesArr
     */
    public static function getCodesByDs($codesArr){

        $codesData = [];
        foreach ($codesArr as $codes){
            $code = $codes[0] % 2 == 0 ? '02468' : '13579';
            $code .= $codes[1] % 2 == 0 ? ',02468' : ',13579';
            $code .= $codes[2] % 2 == 0 ? ',02468' : ',13579';
            $code .= $codes[3] % 2 == 0 ? ',02468' : ',13579';
            $codesData[] = $code;
        }

        return $codesData;
    }

    /**
     * @desc 返回匹配含号码的组合
     * @param array $codesArr
     */
    public static function getCodesArise_bak($codesArr = []){

        $codes4 = [];
        # 去除双重数字
        foreach ($codesArr as $key=>$codes){
            $len = strlen($codes);
            $a = [$codes[0], $codes[1], $codes[2], $codes[3]];
            $uniqueLen = count(array_unique($a));
            if($len == 4 && $uniqueLen <4){ # 四个号码全倒有双重的
                $codes4[] = $codes;
                unset($codesArr[$key]);
                continue;
            }
        }

        $where = ['OR'];
        foreach ($codesArr as $key=>$codes){
            $tmpWhere = ['AND'];
            for ($i=0; $i<$len; $i++){
                $tmpWhere[] = ['like', 'code', $codes[$i]];
            }
            $where[] = $tmpWhere;

        }
        if(!empty($codesArr)){
            $codes = Num4Type::find()->select(['code'])->where(['AND', ['=', 'code_type', 4], $where])->all();
            $codesData = ArrayHelper::getColumn($codes, 'code');
        }

        if(!empty($codes4)){
            # 有双重全倒
            $codes4Arr = [];
            foreach ($codes4 as $codes){
                # 四个号码全倒
                //$a = [$codes[0], $codes[1], $codes[2], $codes[3]];
                $tmpArr = [
                    $codes[0].','.$codes[1].','.$codes[2].','.$codes[3],
                    $codes[0].','.$codes[1].','.$codes[3].','.$codes[2],
                    $codes[0].','.$codes[2].','.$codes[1].','.$codes[3],
                    $codes[0].','.$codes[2].','.$codes[3].','.$codes[1],
                    $codes[0].','.$codes[3].','.$codes[1].','.$codes[2],
                    $codes[0].','.$codes[3].','.$codes[2].','.$codes[1],

                    $codes[1].','.$codes[0].','.$codes[2].','.$codes[3],
                    $codes[1].','.$codes[0].','.$codes[3].','.$codes[2],
                    $codes[1].','.$codes[2].','.$codes[0].','.$codes[3],
                    $codes[1].','.$codes[2].','.$codes[3].','.$codes[0],
                    $codes[1].','.$codes[3].','.$codes[0].','.$codes[2],
                    $codes[1].','.$codes[3].','.$codes[2].','.$codes[0],

                    $codes[2].','.$codes[0].','.$codes[1].','.$codes[3],
                    $codes[2].','.$codes[0].','.$codes[3].','.$codes[1],
                    $codes[2].','.$codes[1].','.$codes[0].','.$codes[3],
                    $codes[2].','.$codes[1].','.$codes[3].','.$codes[0],
                    $codes[2].','.$codes[3].','.$codes[0].','.$codes[1],
                    $codes[2].','.$codes[3].','.$codes[1].','.$codes[0],

                    $codes[3].','.$codes[0].','.$codes[1].','.$codes[2],
                    $codes[3].','.$codes[0].','.$codes[2].','.$codes[1],
                    $codes[3].','.$codes[1].','.$codes[0].','.$codes[2],
                    $codes[3].','.$codes[1].','.$codes[2].','.$codes[0],
                    $codes[3].','.$codes[2].','.$codes[0].','.$codes[1],
                    $codes[3].','.$codes[2].','.$codes[1].','.$codes[0],
                ];
                $tmpArr = array_unique($tmpArr);
                $codes4Arr = array_merge($codes4Arr, $tmpArr);
            }
        }

        $rstData = $codesData;
        if($codes4Arr && $codesData){
            $rstData = array_merge($codesData, $codes4Arr);
        }elseif(!empty($codes4Arr)){
            $rstData = $codes4Arr;
        }
        //array_unique($rstData);

        return $rstData;
    }

    /**
     * @desc 上奖 - 返回匹配含号码的组合 -- 已完成 2019-04-22
     * @param array $codesArr ['123', '456']
     * @param $type 0除1取
     * @param $code_type 1一定2二定3三定4四定5五位二定
     * @return array
     */
    public static function getCodesArise($codesArr = [], $type = 1, $code_type = 4){

        $codes4Arr = [];
        # 去除双重数字
        foreach ($codesArr as $key=>$codes){
            $len = strlen($codes);
            if($len == 1){
                $codesArrTmp = NumService::getAllCombination1($codes, $type, $code_type);
                # 一个码
            }elseif ($len == 2){
                # 两个码
                $codesArrTmp = NumService::getAllCombination2($codes, $type, $code_type);
            }elseif ($len == 3){
                # 三个码
                $codesArrTmp = NumService::getAllCombination3($codes, $type, $code_type);
            }elseif ($len == 4){
                # 四个码 - 全倒
                $codesArrTmp = NumService::getAllCombination4($codes, $type, $code_type);
            }elseif($len > 4){
                # 大于四个码
                $codesArrTmp = NumService::getAllCombination4p($codes, $type, $code_type);
            }
            $codes4Arr = array_merge($codes4Arr, $codesArrTmp);
        }
        if(in_array($code_type, [4, 5])){
            $codes4Arr = array_unique($codes4Arr);
        }

        return $codes4Arr;
    }

    /**
     * @desc 1个号码返回组号码组合 - 全倒
     * @param $codes 格式：1或者2
     * @param $type 0除1取
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination1($codes, $type = 1, $code_type = 4){
        if(strlen($codes) != 1) return [];

        $op = ($type == 1) ? 'LIKE' : 'NOT LIKE';
        $where = ['AND', [$op, 'code', $codes], ['=', 'code_type', $code_type]];
        //p($where);
        $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
        $codesArr = ArrayHelper::getColumn($Num4Types, 'code');

        return array_unique($codesArr);
    }

    /**
     * @desc 2个号码返回组号码组合 - 全倒
     * @param $codes 格式：11或者12
     * @param $type 0除1取
     * @param $code_type 2二字定3三定4四定5五位二定
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination2($codes, $type = 1, $code_type = 4){
        if(strlen($codes) != 2) return [];

        if($code_type == 2){ # 二定
            $tmpCodesArr = NumService::getCodesTwo([$codes[0], $codes[1]]); # 格式：[['1','2', 'X', 'X'], ['1', 'X', '2', 'X']] ..
            $codesArr = [];
            foreach ($tmpCodesArr as $tmpCodes){
                $codesArr[] = implode(',', $tmpCodes);
            }
        }elseif($code_type==3) { # 三定
            if ($type == 1) {
                $where = [
                    'AND',
                    ['=', 'code_type', 3],
                    [
                        'OR',
                        ['LIKE', 'code', '%' . $codes[0] . ',' . $codes[1] . '%', false],
                        ['LIKE', 'code', '%' . $codes[0] . '%' . $codes[1] . '%', false],

                        ['LIKE', 'code', '%' . $codes[1] . ',' . $codes[0] . '%', false],
                        ['LIKE', 'code', '%' . $codes[1] . '%' . $codes[0] . '%', false],
                    ]
                ];
            }
            $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
            $codesArr = ArrayHelper::getColumn($Num4Types, 'code');

        }elseif($code_type == 5){ # 五位二定
            $codesArr = NumService::getTwo5ByTwoNums([$codes[0], $codes[1]]); # 格式：[['1','X','X','X','2'],['X','1','X','X','2'], ['X','X','1','X','2'],['X','X','X','1','2']] ..
        }elseif($code_type == 4){
            if ($type == 1) { # 取
                $where = [
                    'AND',
                    ['=', 'code_type', 4],
                    [
                        'OR',
                        ['LIKE', 'code', '%' . $codes[0] . ',' . $codes[1] . '%', false],
                        ['LIKE', 'code', '%' . $codes[0] . '%' . $codes[1] . '%', false],

                        ['LIKE', 'code', '%' . $codes[1] . ',' . $codes[0] . '%', false],
                        ['LIKE', 'code', '%' . $codes[1] . '%' . $codes[0] . '%', false],
                    ]
                ];
            } else { # 除
                if ($codes[0] == $codes[1]) { # 双重
                    $where = [
                        'AND',
                        ['NOT LIKE', 'code', $codes[0] . ',' . $codes[1] . ',%,%', false],
                        ['NOT LIKE', 'code', $codes[0] . ',%,' . $codes[1] . ',%', false],
                        ['NOT LIKE', 'code', $codes[0] . ',%,%,' . $codes[1], false],
                        ['NOT LIKE', 'code', '%,' . $codes[0] . ',' . $codes[1] . ',%', false],
                        ['NOT LIKE', 'code', '%,' . $codes[0] . ',%,' . $codes[1], false],
                        ['NOT LIKE', 'code', '%,%,' . $codes[0] . ',' . $codes[1], false],
                        ['=', 'code_type', 4],
                    ];
                } else {
                    $where = [
                        'AND',
                        ['NOT LIKE', 'code', '%' . $codes[0] . '%', false],
                        ['NOT LIKE', 'code', '%' . $codes[1] . '%', false],
                        ['=', 'code_type', 4],
                    ];
                }
            }
            $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
            $codesArr = ArrayHelper::getColumn($Num4Types, 'code');
        }

        if($code_type == 4){
            $datas = array_unique($codesArr);
        }else{
            $datas = $codesArr;
        }
        return $datas;
    }

    /**
     * @desc 3个号码返回组号码组合 - 全倒
     * @param $codes - 格式：111或者112或者123
     * @param $type 0除1取
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination3($codes, $type = 1, $code_type = 4){
        if(strlen($codes) != 3) return [];

        $codesArr = [];
        if($code_type == 5){ # 五位二定
            $twoNums = NumService::getTwoNums($codes);
            foreach ($twoNums as $twoNum){
                $codesArr = NumService::getTwo5ByTwoNums([$twoNum[0], $twoNum[1]]);
            }
        }elseif($code_type == 2){ # 二定 - 在处理 12,13,23
            $codesArr = NumService::get2DingWeiByCodes($codes);
        }elseif($code_type == 3){ # 三定
            if($type == 1) {
                $where = [
                    'AND',
                    ['=', 'code_type', 3],
                    ['LIKE', 'code', '%'.$codes[0].'%', false],
                    ['LIKE', 'code', '%'.$codes[1].'%', false],
                    ['LIKE', 'code', '%'.$codes[2].'%', false],
                ];
            }
            $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
            $codesArr = ArrayHelper::getColumn($Num4Types, 'code');
        }elseif($code_type == 4){
            $op = $type == 1 ? 'OR' : 'AND';
            $like_op = ($type == 1) ? 'LIKE' : 'NOT LIKE';
            if($type == 1){
                $where = [
                    'AND',
                    ['=', 'code_type', 4],
                    [
                        $op,
                        [$like_op, 'code', '%'.$codes[0].','.$codes[1].','.$codes[2].'%', false],
                        [$like_op, 'code', $codes[0].'%'.$codes[1].','.$codes[2], false],
                        [$like_op, 'code', $codes[0].','.$codes[1].'%'.$codes[2], false],

                        [$like_op, 'code', '%'.$codes[0].','.$codes[2].','.$codes[1].'%', false],
                        [$like_op, 'code', $codes[0].'%'.$codes[2].','.$codes[1], false],
                        [$like_op, 'code', $codes[0].','.$codes[2].'%'.$codes[1], false],

                        [$like_op, 'code', '%'.$codes[1].','.$codes[0].','.$codes[2].'%', false],
                        [$like_op, 'code', $codes[1].'%'.$codes[0].','.$codes[2], false],
                        [$like_op, 'code', $codes[1].','.$codes[0].'%'.$codes[2], false],

                        [$like_op, 'code', '%'.$codes[1].','.$codes[2].','.$codes[0].'%', false],
                        [$like_op, 'code', $codes[1].'%'.$codes[2].','.$codes[0], false],
                        [$like_op, 'code', $codes[1].','.$codes[2].'%'.$codes[0], false],

                        [$like_op, 'code', '%'.$codes[2].','.$codes[0].','.$codes[1].'%', false],
                        [$like_op, 'code', $codes[2].'%'.$codes[0].','.$codes[1], false],
                        [$like_op, 'code', $codes[2].','.$codes[0].'%'.$codes[1], false],

                        [$like_op, 'code', '%'.$codes[2].','.$codes[1].','.$codes[0].'%', false],
                        [$like_op, 'code', $codes[2].'%'.$codes[1].','.$codes[0], false],
                        [$like_op, 'code', $codes[2].','.$codes[1].'%'.$codes[0], false],
                    ]
                ];
            }else{
                $where = [
                    'AND',
                    ['NOT LIKE', 'code', '%'.$codes[0].'%', false],
                    ['NOT LIKE', 'code', '%'.$codes[1].'%', false],
                    ['NOT LIKE', 'code', '%'.$codes[2].'%', false],
                    ['=', 'code_type', 4],
                ];
            }
            //p($where);
            $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
            $codesArr = ArrayHelper::getColumn($Num4Types, 'code');
        }

        return array_unique($codesArr);
    }

    /**
     * @desc 4个号码返回24组号码组合
     * @param $codes 格式：1123或者1112或者1122或者1234
     * @param $type 0除1取
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination4($codes, $type = 1, $code_type = 4){
        if(strlen($codes) != 4) return [];
        if($code_type == 5) { # 五位二定
            $twoNums = NumService::getTwoNums($codes);
            foreach ($twoNums as $twoNum) {
                $codesArr = NumService::getTwo5ByTwoNums([$twoNum[0], $twoNum[1]]);
            }
        }elseif($code_type==2) { # 四个位置组合：12，13，14，23，24，34
            $codesArr = NumService::get2DingWeiByCodes($codes);
        }elseif ($code_type==3){
            $codesArr = NumService::get3DingWeiByCodes($codes);
        }else{
            if($type == 1){
                $codesArr = [
                    $codes[0].','.$codes[1].','.$codes[2].','.$codes[3],
                    $codes[0].','.$codes[1].','.$codes[3].','.$codes[2],
                    $codes[0].','.$codes[2].','.$codes[1].','.$codes[3],
                    $codes[0].','.$codes[2].','.$codes[3].','.$codes[1],
                    $codes[0].','.$codes[3].','.$codes[1].','.$codes[2],
                    $codes[0].','.$codes[3].','.$codes[2].','.$codes[1],

                    $codes[1].','.$codes[0].','.$codes[2].','.$codes[3],
                    $codes[1].','.$codes[0].','.$codes[3].','.$codes[2],
                    $codes[1].','.$codes[2].','.$codes[0].','.$codes[3],
                    $codes[1].','.$codes[2].','.$codes[3].','.$codes[0],
                    $codes[1].','.$codes[3].','.$codes[0].','.$codes[2],
                    $codes[1].','.$codes[3].','.$codes[2].','.$codes[0],

                    $codes[2].','.$codes[0].','.$codes[1].','.$codes[3],
                    $codes[2].','.$codes[0].','.$codes[3].','.$codes[1],
                    $codes[2].','.$codes[1].','.$codes[0].','.$codes[3],
                    $codes[2].','.$codes[1].','.$codes[3].','.$codes[0],
                    $codes[2].','.$codes[3].','.$codes[0].','.$codes[1],
                    $codes[2].','.$codes[3].','.$codes[1].','.$codes[0],

                    $codes[3].','.$codes[0].','.$codes[1].','.$codes[2],
                    $codes[3].','.$codes[0].','.$codes[2].','.$codes[1],
                    $codes[3].','.$codes[1].','.$codes[0].','.$codes[2],
                    $codes[3].','.$codes[1].','.$codes[2].','.$codes[0],
                    $codes[3].','.$codes[2].','.$codes[0].','.$codes[1],
                    $codes[3].','.$codes[2].','.$codes[1].','.$codes[0],
                ];
            }else{
                $where = [
                    'AND',
                    ['NOT LIKE', 'code', '%'.$codes[0].'%', false],
                    ['NOT LIKE', 'code', '%'.$codes[1].'%', false],
                    ['NOT LIKE', 'code', '%'.$codes[2].'%', false],
                    ['NOT LIKE', 'code', '%'.$codes[3].'%', false],
                    ['=', 'code_type', 4],
                ];

                $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
                $codesArr = ArrayHelper::getColumn($Num4Types, 'code');
            }
        }

        return array_unique($codesArr);
    }

    /**
     * @desc 获取二定位 by codes
     * @param string $codes  123或1234或12345
     * @return array array ['1,2,X,X', '1,X,2,X', '1,X,X,2']
     */
    public static function get2DingWeiByCodes($codes=''){
        $where = [
            'AND',
            ['=', 'code_type', 2],
        ];
        $tmpWhere = ['OR'];
        $len = strlen($codes);
        for($i=0; $i<$len; $i++){
            for($j=$i+1; $j<$len; $j++){
                $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$i].'%'.$codes[$j].'%', false]]);
                $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$j].'%'.$codes[$i].'%', false]]);
            }
        }
        $where = array_merge($where, [$tmpWhere]);
        $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
        $codesArr = ArrayHelper::getColumn($Num4Types, 'code');

        return $codesArr;
    }

    /**
     * @desc 获取二定位 by codes
     * @param string $codes  123或1234或12345
     * @return array array ['1,2,X,X', '1,X,2,X', '1,X,X,2']
     */
    public static function get3DingWeiByCodes($codes=''){
        $where = [
            'AND',
            ['=', 'code_type', 3],
        ];
        $tmpWhere = ['OR'];
        $len = strlen($codes);
        for($i=0; $i<$len; $i++){
            for($j=$i+1; $j<$len; $j++){
                if($j<=$i) continue;
                for($k=$i+2; $k<$len; $k++){
                    if($k<=$j) continue;
                    $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$i].'%'.$codes[$j].'%'.$codes[$k].'%', false]]);
                    $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$i].'%'.$codes[$k].'%'.$codes[$j].'%', false]]);

                    $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$j].'%'.$codes[$i].'%'.$codes[$k].'%', false]]);
                    $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$j].'%'.$codes[$k].'%'.$codes[$i].'%', false]]);

                    $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$k].'%'.$codes[$i].'%'.$codes[$j].'%', false]]);
                    $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$k].'%'.$codes[$j].'%'.$codes[$i].'%', false]]);
                }
            }
        }
        $where = array_merge($where, [$tmpWhere]);
        $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
        $codesArr = ArrayHelper::getColumn($Num4Types, 'code');

        return $codesArr;
    }

    /**
     * @desc 大于4个号码返回四定号码组合号码
     * @param $codes - 格式：11234或者11123或者11223或者12345或者123456
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination4p($codes, $codeSplit = '', $code_type = 4){
        if(strlen($codes)<5) return [];
        if($code_type == 5) { # 五位二定
            $codesArr = [];
            $twoNums = NumService::getTwoNums($codes);
            foreach ($twoNums as $twoNum) {
                $codesArr = array_merge($codesArr, NumService::getTwo5ByTwoNums($twoNum[0], $twoNum[1]));
            }
        }elseif ($code_type == 2){
            $codesArr = NumService::get2DingWeiByCodes($codes);
        }elseif ($code_type == 3){
            $codesArr = NumService::get3DingWeiByCodes($codes);
        }elseif ($code_type == 4){
            $tmpArr = [];
            $len = strlen($codes);
            for ($i = 0; $i < $len; $i++) {
                $tmpArr[] = $codes[$i]; // [1,2,3,4,5,6]
            }
            $tmpCodesArr = []; // ['1234', '2345', '4567'....]
            for($i=0; $i<$len-3; $i++){
                for($j=$i+1; $j<$len; $j++){
                    for($k=$j+1; $k<$len; $k++){
                        for($l=$k+1; $l<$len; $l++){
                            $tmpStr = $tmpArr[$i].$tmpArr[$j].$tmpArr[$k].$tmpArr[$l];
                            $tmpCodesArr[] = $tmpStr;
                        }
                    }

                }
            }
            $codesArr = [];
            foreach ($tmpCodesArr as $k => $v) {
                $codesArr = array_merge($codesArr, NumService::getAllCombination4($v, $type=1, $code_type));
            }
            $codesArr = array_unique($codesArr);
        }else {
            $tmpArr = [];
            $len = strlen($codes);
            for ($i = 0; $i < $len; $i++) {
                $tmpArr[] = $codes[$i];
            }
            $tmpArr1 = $tmpArr2 = $tmpArr3 = $tmpArr4 = $tmpArr;

            # 第1步：循环获取二字组合
            $codes2Arr = [];
            $codes3Arr = [];
            $codes4Arr = [];
            foreach ($tmpArr1 as $k1 => $v1) {
                $fen = floor(count($tmpArr1) / 2);
                if ($k1 + 1 > $fen) break;
                foreach ($tmpArr2 as $k2 => $v2) {
                    if ($k2 <= $k1) continue;
                    $codes2Str = $v1 . $codeSplit . $v2;
                    $codes2Arr[] = $codes2Str;
                    foreach ($tmpArr3 as $k3 => $v3) {
                        if ($k3 == $k1 OR $k3 == $k2) continue;
                        $codes3Str = $codes2Str . $codeSplit . $v3;
                        $codes3Arr[] = $codes3Str;
                        foreach ($tmpArr4 as $k4 => $v4) {
                            if ($k4 == $k1 OR $k4 == $k2 OR $k4 == $k3) continue;
                            $codes4Str = $codes3Str . $codeSplit . $v4;
                            $tmp = [$codes4Str[0], $codes4Str[1], $codes4Str[2], $codes4Str[3],];
                            asort($tmp);
                            $codes4Arr[] = $tmp;
                        }
                    }
                }
            }
            //p($codes4Arr);
            //$codes4Arr = array_unique($codes4Arr);
            $tmpCodesArr = [];
            //p($codes4Arr);
            foreach ($codes4Arr as $k => $v) {
                $tmpCodesArr[] = implode('', $v);
            }
            $tmpCodesArr = array_unique($tmpCodesArr);
            $codesArr = [];
            foreach ($tmpCodesArr as $k => $v) {
                $codesArr = array_merge($codesArr, NumService::getAllCombination4($v, $type = 1, $code_type));
            }
        }

        return $codesArr;
    }

    /**
     * @desc
     * @param string $code1
     * @param string $code2
     * @return array ['1,X,X,X,2', 'X,1,X,X,2', 'X,X,1,X,2', 'X,X,X,1,2', '2,X,X,X,1', 'X,2,X,X,1', 'X,X,2,X,1', 'X,X,X,2,1']
     */
    public static function getTwo5ByTwoNums($code1 = '', $code2 = ''){
        $codesArr = [];

        $tmpCodesArr = NumService::getCodesTwo5([$code1, $code2]);
        foreach ($tmpCodesArr as $tmpCodes){
            $codesArr[] = implode(',', $tmpCodes);
        }

        return $codesArr;
    }

    /**
     * @param $codes
     */
    public static function getTwoNums($codes){
        $codesArr = [];
        if(strlen($codes) == 1){
            $codesArr[] = [$codes, $codes];
        }elseif (strlen($codes) == 2){
            $codesArr = [[$codes[0], $codes[1]]];
        }elseif(strlen($codes) == 3){
            $codesArr = [[$codes[0], $codes[1]], [$codes[0], $codes[2]], [$codes[1], $codes[2]]];
        }else{
            $codesArr = [];
            $len = strlen($codes);
            for($i=0; $i<$len; $i++){ # 2345678
                for ($j=$i+1; $j<$len; $j++){
                    $codesArr = array_merge($codesArr, [ [$codes[$i], $codes[$j]] ]);
                }
            }
        }

        return $codesArr;
    }

    /**
     * @desc 快选功能过滤
     * @param $codes_hz array
     * @param int $code_type 1一定2二定3三定4四定
     * @return array
     */
    public static function getCodesKuaiXuan($codes_hz, $code_type = 4, $codes=[], $lottery_type='') {
        //p([$codes_hz, $code_type]);
        if(empty($codes_hz)) return [];

        $where = ['AND', ['=', 'code_type', $code_type]];
        # 双重:type_2、三重:type_3、四重:type_4、双双重:type_22、两兄弟:type_2b、三兄弟:type_3b、四兄弟:type_4b
        # 1、双重
        if(isset($codes_hz['type_2'])){
            $where = array_merge($where, [['=', 'type_2', $codes_hz['type_2']]]);
        }
        # 2、三重
        if(isset($codes_hz['type_3'])){
            $where = array_merge($where, [['=', 'type_3', $codes_hz['type_3']]]);
        }
        # 3、四重
        if(isset($codes_hz['type_4'])){
            $where = array_merge($where, [['=', 'type_4', $codes_hz['type_4']]]);
        }
        # 4、双双重
        if(isset($codes_hz['type_22'])){
            $where = array_merge($where, [['=', 'type_22', $codes_hz['type_22']]]);
        }
        # 5.0、两兄弟
        if(isset($codes_hz['type_2b'])){
            $where = array_merge($where, [['=', 'type_2b', $codes_hz['type_2b']]]);
        }
        # 5.1、双两兄弟
        if(isset($codes_hz['type_22b'])){
            $where = array_merge($where, [['=', 'type_22b', $codes_hz['type_22b']]]);
        }
        # 6、三兄弟
        if(isset($codes_hz['type_3b'])){
            $where = array_merge($where, [['=', 'type_3b', $codes_hz['type_3b']]]);
        }
        # 7、四兄弟
        if(isset($codes_hz['type_4b'])){
            $where = array_merge($where, [['=', 'type_4b', $codes_hz['type_4b']]]);
        }

        # 和值
        if(isset($codes_hz['hz'])){
            $where = array_merge($where, [ ['IN', 'codes_hz', $codes_hz['hz']] ]);
            //$query->andWhere($andWhere);
        }
        # tz_type:28 和值-取
        if(isset($codes_hz['get_hzs']) && empty($codes_hz['get_hzs'])){
            $where = array_merge($where, [ ['IN', 'codes_hz', $codes_hz['get_hzs']] ]);
        }
        # tz_type:28 和值-除
        if(isset($codes_hz['remove_hzs']) && !empty($codes_hz['remove_hzs'])){
            $where = array_merge($where, [ ['NOT IN', 'codes_hz', $codes_hz['remove_hzs']] ]);
        }

        # 定位合分
        if($code_type == 3 && isset($codes_hz['hefen_pos']) && isset($codes_hz['hefen']) && !empty($codes_hz['hefen_pos']) && !empty($codes_hz['hefen'])){
            # 三定
            $poss = explode(',', $codes_hz['hefen_pos']);
            $lenHefen = strlen($codes_hz['hefen']);
            $hf_codes_hzs = [];
            for ($i=0; $i<$lenHefen; $i++){
                if($codes_hz['hefen'][$i]<=7){
                    $hefenArr = [$codes_hz['hefen'][$i], $codes_hz['hefen'][$i] + 10, $codes_hz['hefen'][$i] + 20];
                }else{
                    $hefenArr = [$codes_hz['hefen'][$i], $codes_hz['hefen'][$i] + 10];
                }
                $hf_codes_hzs = array_merge($hf_codes_hzs, $hefenArr);
            }
            $codes_str = '';
            foreach ($poss as $pos){
                $codes_str .= '`code_'.$pos.'`' . ' +';
                $where = array_merge($where, [['<>', 'code_'.$pos, 'X']]);
            }
            $codes_str = rtrim(trim($codes_str), '+');
            $where = array_merge($where, [ ['IN', '('.$codes_str.')', $hf_codes_hzs ] ]);
            //$query->andWhere($andWhere);
        }if($code_type == 4 && isset($codes_hz['hefen_pos']) && isset($codes_hz['hefen']) && !empty($codes_hz['hefen_pos']) && !empty($codes_hz['hefen'])){
            # 四定
            $poss = explode(',', $codes_hz['hefen_pos']);
            $lenPos = count($poss);
            $hf_codes_hzs = self::getHezhisByHefen($codes_hz['hefen'], $lenPos);

            $codes_str = '';
            foreach ($poss as $pos){
                $codes_str .= '`code_'.$pos.'`' . ' + ';
                $where = array_merge($where, [['<>', 'code_'.$pos, 'X']]);
            }
            $codes_str = rtrim(trim($codes_str), '+');
            $where = array_merge($where, [ ['IN', '('.$codes_str.')', $hf_codes_hzs ] ]);
            //p([$poss, $codes_hz['hefen'], $hf_codes_hzs]);
        }

        # 配数 - 支持二定、三定、四定
        if(isset($codes_hz['ps_1']) && !empty($codes_hz['ps_1']) && isset($codes_hz['ps_2']) && !empty($codes_hz['ps_2'])){
            $ps1 = (string)$codes_hz['ps_1']; $ps1_len = strlen($ps1); # 配数1
            $ps2 = (string)$codes_hz['ps_2']; $ps2_len = strlen($ps2); # 配数2
            //p([$ps1_len, $ps2_len]);
            $tmpPsWhere2 = ['OR'];
            for ($x=0;$x<$ps1_len;$x++){
                for ($y=0;$y<$ps2_len;$y++){
                    $p1Types = [1,2,3,4];
                    $p2Types = [1,2,3,4];
                    foreach ($p1Types as $p1Type){
                        foreach ($p2Types as $p2Type){
                            if($p1Type == $p2Type) continue;
                            $tmpPsWhere2 = array_merge($tmpPsWhere2, [
                                ['AND',
                                    ['=', 'code_'.$p1Type, $ps1[$x] ],
                                    ['=', 'code_'.$p2Type, $ps2[$y] ]
                                ]
                            ]);
                            //$tmpPsWhere2 = array_merge($tmpPsWhere1, [$tmpPsWhere1]);
                        }
                        //p($tmpPsWhere2);
                    }
                }
            }
            $where = array_merge($where, [$tmpPsWhere2]);
        }

        ####################################  走移 start  ##################################
        # 123千走456各1元 - 未完成待续
        if(isset($codes_hz['zou_yi']) && !empty($codes_hz['zou_yi'])){
            $poses = ['1', '2', '3', '4'];
            $fix_poses = [];
            $no_fix_poses = [];
            foreach ($poses as $ps){
                $len_zouyi = strlen($codes_hz['zou_yi']);
                if(isset($codes_hz['p'.$ps]) && !empty($codes_hz['p'.$ps])){ # 定位位置
                    $fix_poses[] = $ps;
                }else{ # 走移位置
                    $no_fix_poses[] = $ps;
                }
            }
            $zouyi_codes = $codes_hz['zou_yi']; # 走移号码
            $fix_pos_counts = count($fix_poses);
            //p(['code_desc'=>$codes_hz, 'no_fix_poses'=>$no_fix_poses, 'fix_poses'=>$fix_poses, 'code_type'=>$code_type, 'fix_pos_counts'=>$fix_pos_counts, 'zouyi_codes'=>$zouyi_codes]);
            if($code_type == 2){ # 二定
                if($fix_pos_counts == 1){ # 已经定一个位 - 剩余一个未定位
                    $where_tmp_zouyi = ['OR'];
                    for ($z=0; $z<$len_zouyi; $z++){
                        foreach ($no_fix_poses as $no_fix_zouyi_pose){
                            $where_tmp_zouyi = array_merge($where_tmp_zouyi, [['=', 'code_'.$no_fix_zouyi_pose, $zouyi_codes[$z]]]);
                        }
                    }
                    $where = array_merge($where, [$where_tmp_zouyi]);
                }
            }elseif ($code_type == 3){ # 二定
                if($fix_pos_counts == 1){ # 已经定一个位
                    $where_tmp_zouyi = ['OR'];
                    for ($z=0; $z<$len_zouyi; $z++){
                        for ($zz=$z+1; $zz<$len_zouyi; $zz++) {
                            for($y=0; $y<count($no_fix_poses); $y++){
                                for($yy=$y+1; $yy<count($no_fix_poses); $yy++){
                                    $where_tmp_zouyi = array_merge($where_tmp_zouyi, [
                                        [ 'AND',
                                            ['OR',
                                                ['AND',
                                                    ['=', 'code_' . $no_fix_poses[$y], $zouyi_codes[$z]],
                                                    ['=', 'code_' . $no_fix_poses[$yy], $zouyi_codes[$zz]],
                                                ],
                                                ['AND',
                                                    ['=', 'code_' . $no_fix_poses[$yy], $zouyi_codes[$z]],
                                                    ['=', 'code_' . $no_fix_poses[$y], $zouyi_codes[$zz]],
                                                ],
                                            ]
                                        ]
                                    ]);
                                }
                            }
                        }
                    }
                }elseif ($fix_pos_counts == 2){ # 已经定两个位 - 剩余一个未定位 - 已校验
                    $where_tmp_zouyi = ['OR'];
                    for ($z=0; $z<$len_zouyi; $z++){
                        foreach ($no_fix_poses as $no_fix_zouyi_pose){
                            $where_tmp_zouyi = array_merge($where_tmp_zouyi, [['=', 'code_'.$no_fix_zouyi_pose, $zouyi_codes[$z]]]);
                        }
                    }
                }
                $where = array_merge($where, [$where_tmp_zouyi]);
            }
        }
        //p(['code_type'=>$code_type, 'fix_pos_counts'=>$fix_pos_counts, 'fix_poses'=>$fix_poses, 'no_fix_poses'=>$no_fix_poses, 'where'=>$where]);
        ####################################  走移 end  ##################################

        # 不定位合分(1两数、2三数) - 三定
        //if($code_type == 3 && !empty($codes_hz['no_fix_hefen']) && !empty($codes_hz['no_fix_hefen_pos'])){
        if(!empty($codes_hz['no_fix_hefen']) && !empty($codes_hz['no_fix_hefen_pos'])){ # no_fix_hefen:不定位合分值、no_fix_hefen_pos:1两数2三数

            /**
             * 1、处理合分值，例如：149转换成：
             * 二定：1、11、4、14、9
             * 三定：1、11、21、4、14、24、9、19、29
             * 四定：1、11、21、31、4、14、24、9、19、29
             */
            $no_fix_lenHefen = strlen($codes_hz['no_fix_hefen']);
            $codes_no_fix_hefen = [];
            for ($i=0; $i<$no_fix_lenHefen;$i++){
                if($codes_hz['no_fix_hefen_pos'] == 1){
                    # 1、两数合分
                    if($codes_hz['no_fix_hefen'][$i]<=8){
                        $no_fix_hefenArr = [$codes_hz['no_fix_hefen'][$i], $codes_hz['no_fix_hefen'][$i] + 10];
                    }else{
                        $no_fix_hefenArr = [$codes_hz['no_fix_hefen'][$i]];
                    }
                }elseif($codes_hz['no_fix_hefen_pos'] == 2){
                    # 1、三数合分
                    if($codes_hz['no_fix_hefen'][$i]<=7){
                        $no_fix_hefenArr = [$codes_hz['no_fix_hefen'][$i], $codes_hz['no_fix_hefen'][$i] + 10, $codes_hz['no_fix_hefen'][$i] + 20];
                    }else{
                        $no_fix_hefenArr = [$codes_hz['no_fix_hefen'][$i], $codes_hz['no_fix_hefen'][$i] + 10];
                    }
                }
                $codes_no_fix_hefen = array_merge($codes_no_fix_hefen, $no_fix_hefenArr);
            }
            //p($codes_no_fix_hefen);

            /**
             * 2、组合where条件
             */
            if($codes_hz['no_fix_hefen_pos'] == 1){ # 两数合分 ----------- 不定位合分
                $tmp_no_fix_hefen = ['OR'];
                $poss = [[1,2], [1,3], [1,4],[2,3],[2,4],[3,4]];
                if(in_array($code_type, [2, 3])){ # 三定
                    foreach ($poss as $pos){ # ['IN', SUM(code_1 + code2),[1,11,21]]
                        $son_where = [
                            ['AND',
                                ['IN', '(`code_'.$pos[0].'` + `code_'.$pos[1].'`)', $codes_no_fix_hefen],
                                ['<>', '`code_'.$pos[0].'`', 'X'],
                                ['<>', '`code_'.$pos[1].'`', 'X']
                            ]
                        ];
                        $tmp_no_fix_hefen = array_merge($tmp_no_fix_hefen, $son_where);
                    }
                }elseif($code_type == 2){ # 二定

                }elseif($code_type == 4){ # 四定
                    foreach ($poss as $pos){ # ['IN', SUM(code_1 + code2),[1,11,21]]
                        $son_where = [ ['IN', '(`code_'.$pos[0].'` + `code_'.$pos[1].'`)', $codes_no_fix_hefen] ];
                        $tmp_no_fix_hefen = array_merge($tmp_no_fix_hefen, $son_where);
                    }
                }
            }elseif($codes_hz['no_fix_hefen_pos'] == 2){ # 三数合分 ----------- 不定位合分
                if($code_type == 3) { # 三定
                    $tmp_no_fix_hefen = ['IN', 'codes_hz', $codes_no_fix_hefen];
                }elseif ($code_type == 4){ # 四定
                    $tmp_no_fix_hefen = ['OR'];
                    $poss = [[1,2,3], [1,2,4], [1,3,4],[2,3,4]];
                    foreach ($poss as $pos){ # ['IN', SUM(code_1 + code2),[1,11,21]]
                        $son_where = [ ['IN', '(`code_'.$pos[0].'` + `code_'.$pos[1].'` + `code_'.$pos[2].'`)', $codes_no_fix_hefen] ];
                        $tmp_no_fix_hefen = array_merge($tmp_no_fix_hefen, $son_where);
                    }
                }
            }
            $where = array_merge($where, [$tmp_no_fix_hefen]);
        }

        # 合分 - 四定，例如：合分：147，转化成和值：1、11、21、31、4、14、14、34、7、17、27
        if($code_type == 4 && isset($codes_hz['xhefen']) && !empty($codes_hz['xhefen'])){
            $lenHefen = strlen($codes_hz['xhefen']);
            $codes_hefen = [];
            for ($i=0; $i<$lenHefen; $i++){
                if($codes_hz['xhefen'][$i]<=6){
                    $hefenArr = [$codes_hz['xhefen'][$i], $codes_hz['xhefen'][$i] + 10, $codes_hz['xhefen'][$i] + 20, $codes_hz['xhefen'][$i] + 30];
                }else{
                    $hefenArr = [$codes_hz['xhefen'][$i], $codes_hz['xhefen'][$i] + 10, $codes_hz['xhefen'][$i] + 20];
                }
                $codes_hefen = array_merge($codes_hefen, $hefenArr);
            }
            $where = array_merge($where, [ ['IN', 'codes_hz', $codes_hefen ] ]);
        }

        # 单双类型：1122，1212，2222 等，总共16种
        if(!empty($codes_hz['type_ds_details'])){
            $where = array_merge($where, [['IN', 'type_ds', $codes_hz['type_ds_details']]]);
        }

        # 三定、四定 "含" 除、取
        if(in_array($code_type, [3,4]) && !empty($codes_hz['arise_in']) && in_array($codes_hz['arise_in_sel'], [1, 2])){
            $lenAriseIn = strlen($codes_hz['arise_in']); # 含的个数
            $tmpAriseInType = $codes_hz['arise_in_sel'];
            if($tmpAriseInType == 1){ # 除
                $op = 'AND';
                $sel_type = '<>';
            }elseif($tmpAriseInType == 2){ # 取
                $op = 'OR';
                $sel_type = '=';
            }
            if($lenAriseIn>1){
                $tmpAriseIn = [$op];
                for($i=0; $i<$lenAriseIn; $i++){
                    $tmpAriseIn = array_merge($tmpAriseIn, [
                        [
                            $op,
                            [$sel_type, 'code_1', $codes_hz['arise_in'][$i] ], [$sel_type, 'code_2', $codes_hz['arise_in'][$i] ],
                            [$sel_type, 'code_3', $codes_hz['arise_in'][$i] ], [$sel_type, 'code_4', $codes_hz['arise_in'][$i] ]
                        ]
                    ]);
                }
                $where = array_merge($where, [$tmpAriseIn]);
            }else{
                $where = array_merge($where, [
                    [ $op,
                        [$sel_type, 'code_1', $codes_hz['arise_in'] ], [$sel_type, 'code_2', $codes_hz['arise_in'] ],
                        [$sel_type, 'code_3', $codes_hz['arise_in'] ], [$sel_type, 'code_4', $codes_hz['arise_in'] ]
                    ]
                ]);
            }
        }

        # 第1位
        if(isset($codes_hz['p1']) && $codes_hz['p1'] !== ''){
            //$p1_codes = explode(',', $codes_hz['p1']);
            $p1_codes = self::getCodesArrByStr($codes_hz['p1']);
            $where = array_merge($where, [ ['IN', 'code_1', $p1_codes] ]);
        }
        if(isset($codes_hz['p1_0']) && $codes_hz['p1_0'] !== ''){
            $p1_codes = self::getCodesArrByStr($codes_hz['p1_0']);
            $where = array_merge($where, [ ['NOT IN', 'code_1', $p1_codes] ]);
        }

        # 第2位
        if(isset($codes_hz['p2']) && $codes_hz['p2'] !== ''){
            $p2_codes = self::getCodesArrByStr($codes_hz['p2']);
            $where = array_merge($where, [ ['IN', 'code_2', $p2_codes] ]);
        }
        if(isset($codes_hz['p2_0']) && $codes_hz['p2_0'] !== ''){
            $p2_codes = self::getCodesArrByStr($codes_hz['p2_0']);
            $where = array_merge($where, [ ['NOT IN', 'code_2', $p2_codes] ]);
        }

        # 第3位
        if(isset($codes_hz['p3']) && $codes_hz['p3'] !== ''){
            $p3_codes = self::getCodesArrByStr($codes_hz['p3']);
            $where = array_merge($where, [ ['IN', 'code_3', $p3_codes] ]);
        }
        if(isset($codes_hz['p3_0']) && $codes_hz['p3_0'] !== ''){
            $p3_codes = self::getCodesArrByStr($codes_hz['p3_0']);
            $where = array_merge($where, [ ['NOT IN', 'code_3', $p3_codes] ]);
        }

        # 第4位
        if(isset($codes_hz['p4']) && $codes_hz['p4'] !== ''){
            $p4_codes = self::getCodesArrByStr($codes_hz['p4']);
            $where = array_merge($where, [ ['IN', 'code_4', $p4_codes] ]);
        }
        if(isset($codes_hz['p4_0']) && $codes_hz['p4_0'] !== ''){
            $p4_codes = self::getCodesArrByStr($codes_hz['p4_0']);
            $where = array_merge($where, [ ['NOT IN', 'code_4', $p4_codes] ]);
        }

        # 第5位
        if(isset($codes_hz['p5']) && $codes_hz['p5'] !== ''){
            $p5_codes = self::getCodesArrByStr($codes_hz['p5']);
            $where = array_merge($where, [ ['IN', 'code_5', $p5_codes] ]);
        }
        if(isset($codes_hz['p5_0']) && $codes_hz['p5_0'] !== ''){
            $p5_codes = self::getCodesArrByStr($codes_hz['p5_0']);
            $where = array_merge($where, [ ['NOT IN', 'code_5', $p5_codes] ]);
        }

        # 同时选择取、除四单四双
        if(isset($codes_hz['type_4ds']) OR isset($codes_hz['type_4ds'])){
            if(is_array($codes_hz['type_4ds']) AND !empty($codes_hz['type_4ds'])){
                $where = array_merge($where, [['IN', 'type_4ds', $codes_hz['type_4ds']]]);
            }
        }

        # tz_type:38 三现:双重+两兄弟 1
        if(isset($codes_hz['type_3n_2b']) && $codes_hz['type_3n_2b'] !== ''){
            $where = array_merge($where, [['=', 'type_3n_2b', (int)$codes_hz['type_3n_2b']]]);
        }

        # tz_type:28 三现:双重+两兄弟 1
        if(($codes_hz['get_types'] && in_array(1, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(1, $codes_hz['remove_types']) )){
            if($codes_hz['get_types'] && in_array(1, $codes_hz['get_types'])){
                $type_3n_2b = 1;
            }else{
                $type_3n_2b = 0;
            }
            $where = array_merge($where, [['=', 'type_3n_2b', $type_3n_2b]]);
        }
        # tz_type:28 双重 2
        if(($codes_hz['get_types'] && in_array(2, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(2, $codes_hz['remove_types']) )){
            if($codes_hz['get_types'] && in_array(2, $codes_hz['get_types'])){
                $type_2 = 1;
            }else{
                $type_2 = 0;
            }
            $where = array_merge($where, [['=', 'type_2', $type_2]]);
        }
        # tz_type:28 三重 3
        if(($codes_hz['get_types'] && in_array(3, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(3, $codes_hz['remove_types']) )){
            if($codes_hz['get_types'] && in_array(3, $codes_hz['get_types'])){
                $type_3 = 1;
            }else{
                $type_3 = 0;
            }
            $where = array_merge($where, [['=', 'type_3', $type_3]]);
        }
        # tz_type:28 四重 4
        if(($codes_hz['get_types'] && in_array(4, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(4, $codes_hz['remove_types']) )){
            if($codes_hz['get_types'] && in_array(4, $codes_hz['get_types'])){
                $type_4 = 1;
            }else{
                $type_4 = 0;
            }
            $where = array_merge($where, [['=', 'type_4', $type_4]]);
        }
        # tz_type:28 两兄弟 5
        if(($codes_hz['get_types'] && in_array(5, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(5, $codes_hz['remove_types']) )){
            if($codes_hz['get_types'] && in_array(5, $codes_hz['get_types'])){
                $type_2b = 1;
            }else{
                $type_2b = 0;
            }
            $where = array_merge($where, [['=', 'type_2b', $type_2b]]);
        }
        # tz_type:28 三兄弟 6
        if(($codes_hz['get_types'] && in_array(6, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(6, $codes_hz['remove_types']) )){
            if($codes_hz['get_types'] && in_array(6, $codes_hz['get_types'])){
                $type_3b = 1;
            }else{
                $type_3b = 0;
            }
            $where = array_merge($where, [['=', 'type_3b', $type_3b]]);
        }

        # tz_type:28 双双重 7
        if(($codes_hz['get_types'] && in_array(7, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(7, $codes_hz['remove_types']) )){
            if($codes_hz['get_types'] && in_array(7, $codes_hz['get_types'])){
                $type_22 = 1;
            }else{
                $type_22 = 0;
            }
            $where = array_merge($where, [['=', 'type_22', $type_22]]);
        }

        # tz_type:28 双两兄弟 8
        if(($codes_hz['get_types'] && in_array(8, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(8, $codes_hz['remove_types']) )){
            if($codes_hz['get_types'] && in_array(8, $codes_hz['get_types'])){
                $type_22b = 1;
            }else{
                $type_22b = 0;
            }
            $where = array_merge($where, [['=', 'type_22b', $type_22b]]);
        }
        # tz_type:28 四兄弟 9
        if(($codes_hz['get_types'] && in_array(9, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(9, $codes_hz['remove_types']) )){
            if($codes_hz['get_types'] && in_array(9, $codes_hz['get_types'])){
                $type_4b = 1;
            }else{
                $type_4b = 0;
            }
            $where = array_merge($where, [['=', 'type_4b', $type_4b]]);
        }

        # 对数
        if(isset($codes_hz['type_log'])){
            $where = array_merge($where, [['=', 'type_log', $codes_hz['type_log']]]);
        }

        # 上奖字段
        $tmpAriseLen = strlen($codes_hz['arise']);
        $tmpAriseArr = [];
        for ($i=0; $i<$tmpAriseLen; $i++){
            $tmpAriseArr[] = $codes_hz['arise'][$i];
        }

        //p([$where, $codes_hz]);
        //$get = \Yii::$app->request->get(); if($get['t'] == 1)p($where);
        $codesArr = [];
        if($code_type == 4){
        }elseif ($code_type == 3){
        }elseif ($code_type == 5){ # 五位二定
            $tmpArisewhere = ['OR'];
            if($codes_hz['arise']){
                $tmpArisewhere = array_merge($tmpArisewhere, [ ['IN', 'code_1', $tmpAriseArr] ]);
                $tmpArisewhere = array_merge($tmpArisewhere, [ ['IN', 'code_2', $tmpAriseArr] ]);
                $tmpArisewhere = array_merge($tmpArisewhere, [ ['IN', 'code_3', $tmpAriseArr] ]);
                $tmpArisewhere = array_merge($tmpArisewhere, [ ['IN', 'code_4', $tmpAriseArr] ]);
                $where = array_merge($where, [$tmpArisewhere]);
            }
        }
        $query = Num4Type::find()->where($where);

        //p([$codes_hz['arb_pos_isbaohan'], $codes_hz['arb_pos_nums'], $codes_hz['arb_pos_codes']]);
        ############################ 任意位置包含、排除 start ###################################
        if(isset($codes_hz['arb_pos_isbaohan'])){
            $arb_pos_nums = $codes_hz['arb_pos_nums']; # 个数
            $arb_pos_codes = $codes_hz['arb_pos_codes']; # 号码
            if($codes_hz['arb_pos_isbaohan'] == 1){ # 任意位置包含
                $arb_pos_asises = NumService::getCodesArrByNum($arb_pos_codes, $arb_pos_nums);
                $arb_codesArr = self::getCodesArise($arb_pos_asises, $type = 1, $code_type); # 比如：$arb_pos_nums=2, arb_pos_asises= ['12','34','45'] # 每个元素为两个号码
                $query->andWhere(['IN', 'code', $arb_codesArr]);
            }elseif($codes_hz['arb_pos_isbaohan'] === 0){ # 排除

            }
        }
        ########################### 任意位置包含、排除 start ###################################

        ###################################################### filters过滤参数开始05.24 ######################################################
        # 1、排除前x期 05.24
        if(in_array($code_type, [2,3,4]) && isset($codes_hz['filters']) && isset($codes_hz['filters']['is_filter']) && $codes_hz['filters']['is_filter']==1){
            $filters = $codes_hz['filters'];
            if(!empty($codes)){
                $filter_poses = NumService::getFilterPosByCode($codes[0]); # 根据导入的号码判断要过滤的位置
                if(!empty($filter_poses)){
                    foreach ($filter_poses as $pos){
                        $query->andWhere(['<>', 'code_'.$pos, 'X']);
                    }
                }
                if($lottery_type && isset($filters['filter_xQ_before']) && !empty($filters['filter_xQ_before'])){
                    $qihao = HN0898Service::getCurrentQihao($lottery_type);
                    $index_id = SscKjData::find()->where(['AND', ['=', 'qihao', $qihao], ['=','lottery_type', $lottery_type]])->asArray()->one()['index_id'];
                    $filter_index_ids = [];
                    if(isset($filters['filter_xQ_before']) && !empty($filters['filter_xQ_before'])){ # 1,2;4~6 前x期
                        $tmp_filter_index_Arrs = explode(';', $filters['filter_xQ_before']);
                        foreach ($tmp_filter_index_Arrs as $tmp_filter_index_Arr){
                            if(strpos($tmp_filter_index_Arr, ',') !== false){ # 1,2
                                $tmp_filter_index_Arr2 = explode(',', $tmp_filter_index_Arr);
                                foreach ($tmp_filter_index_Arr2 as $tmp_index){
                                    $filter_index_ids[] = $index_id - $tmp_index + 1;
                                }
                            }elseif(strpos($tmp_filter_index_Arr, '~') !== false){ # 4~6
                                $tmp_filter_index_Arr2 = explode('~', $tmp_filter_index_Arr);
                                if(empty($tmp_filter_index_Arr2) OR count($tmp_filter_index_Arr2)<2) continue;
                                sort($tmp_filter_index_Arr2); # 正序
                                for ($i=$tmp_filter_index_Arr2[0]; $i<=end($tmp_filter_index_Arr2); $i++){
                                    $filter_index_ids[] = $index_id - $i + 1;
                                }
                            }else{
                                if(is_string($tmp_filter_index_Arr)){
                                    $tmp_filter_index_Arr = (int)$tmp_filter_index_Arr;
                                }
                                $filter_index_ids[] = $index_id - $tmp_filter_index_Arr + 1;
                            }
                        }
                        if(!empty($filter_index_ids)){ # 过滤期的index_id
                            $SscKjDatas = SscKjData::find()->where(['AND', ['IN', 'index_id', $filter_index_ids], ['=', 'lottery_type', $lottery_type]])->asArray()->all();
                            //p(['SscKjDatas'=>$SscKjDatas, 'filters'=>$filters, 'filter_index_ids'=>$filter_index_ids]);
                            foreach ($SscKjDatas as $sscKjData){
                                if(!empty($filters['filter_pos1'])) { # 特殊过滤
                                    $tmp_filter1_where = ['OR',];
                                    $filter_pos1 = $filters['filter_pos1'];
                                    # pos1
                                    foreach ($filter_poses as $k=>$pos) {
                                        $tmp_filter1_where[] = ['<>', 'code_' . $pos, $sscKjData['code' . $filter_pos1[$k]]];
                                    }
                                    $query->andWhere($tmp_filter1_where);
                                }

                                # pos2
                                if(!empty($filters['filter_pos2'])){ # 特殊过滤
                                    $tmp_filter2_where = ['OR'];
                                    $filter_pos2 = $filters['filter_pos2'];
                                    sort($filter_pos2);

                                    # 现在
                                    foreach ($filter_poses as $k=>$pos){
                                        $tmp_filter2_where[] = ['<>', 'code_'.$pos, $sscKjData['code'.$filter_pos2[$k]]];
                                    }
                                    $query->andWhere($tmp_filter2_where);
                                }
                            }
                        }
                    }
                }
                //$query->andWhere(['IN', 'code', $codes]);
            }
        }

        # 2、排除前x天同期 05.25
        if(in_array($code_type, [2,3,4]) && isset($codes_hz['filter_dates']) && isset($codes_hz['filter_dates']['is_filter_date']) && $codes_hz['filter_dates']['is_filter_date']==1){
            $filter_dates = $codes_hz['filter_dates'];
            if(!empty($codes)){
                $filter_poses = NumService::getFilterPosByCode($codes[0]); # 根据导入的号码判断要过滤的位置
                if(!empty($filter_poses)){
                    foreach ($filter_poses as $pos){
                        $query->andWhere(['<>', 'code_'.$pos, 'X']);
                    }
                }
                if($lottery_type && isset($filter_dates['filter_xD_before']) && !empty($filter_dates['filter_xD_before'])){
                    $qihao = HN0898Service::getQihao($lottery_type);
                    $sub_qihao = substr($qihao, -3, 3); # 短期号
                    //p([$qihao, $sub_qihao]);
                    # 以下待修改 05-24 20点40分
                    //$index_date = SscKjData::find()->where(['AND', ['=', 'qihao', $qihao], ['=','lottery_type', $lottery_type]])->asArray()->one()['date'];
                    $index_date = date('Y-m-d');
                    $filter_index_dates = [];
                    if(isset($filter_dates['filter_xD_before']) && !empty($filter_dates['filter_xD_before'])){ # 1,2;4~6 # 前x天同期
                        $tmp_filter_index_Arrs = explode(';', $filter_dates['filter_xD_before']);
                        foreach ($tmp_filter_index_Arrs as $tmp_filter_index_Arr){
                            //p($tmp_filter_index_Arr);
                            if(strpos($tmp_filter_index_Arr, ',') !== false){ # 1,2
                                $tmp_filter_index_Arr2 = explode(',', $tmp_filter_index_Arr);
                                foreach ($tmp_filter_index_Arr2 as $tmp_index){
                                    $filter_index_dates[] = date('Y-m-d', (strtotime($index_date) - $tmp_index*86400));
                                }
                            }elseif(strpos($tmp_filter_index_Arr, '~') !== false){ # 4~6
                                $tmp_filter_index_Arr2 = explode('~', $tmp_filter_index_Arr);
                                if(empty($tmp_filter_index_Arr2) OR count($tmp_filter_index_Arr2)<2) continue;
                                sort($tmp_filter_index_Arr2); # 正序
                                for ($i=$tmp_filter_index_Arr2[0]; $i<=end($tmp_filter_index_Arr2); $i++){
                                    $filter_index_dates[] = date('Y-m-d', (strtotime($index_date) - $i*86400));
                                }
                            }else{
                                if(is_string($tmp_filter_index_Arr)){
                                    $tmp_filter_index_Arr = (int)$tmp_filter_index_Arr;
                                }
                                $filter_index_dates[] = date('Y-m-d', (strtotime($index_date) - $tmp_filter_index_Arr*86400)); # $tmp_filter_index_Arr 为整数
                            }
                        }
                        //p($filter_index_dates);
                        if(!empty($filter_index_dates)){ # 过滤期的index_id
                            $where_index_date = ['AND', ['IN', 'date', $filter_index_dates], ['=', 'lottery_type', $lottery_type], ['LIKE', 'qihao', '%'.$sub_qihao, false]];
                            $SscKjDatas = SscKjData::find()->select(['qihao','date','kj_code','code1','code2','code3','code4'])->where($where_index_date)->asArray()->all();
                            //p(['SscKjDatas'=>$SscKjDatas, 'filter_dates'=>$filter_dates]);
                            foreach ($SscKjDatas as $sscKjData){
                                $tmp_filter1_where = ['OR', ];
                                $filter_pos1 = $filter_dates['filter_date_pos1'];
                                # pos1
                                foreach ($filter_poses as $k=>$pos){
                                    $tmp_filter1_where[] = ['<>', 'code_' . $pos, $sscKjData['code' . $filter_pos1[$k]]];
                                }
                                $query->andWhere($tmp_filter1_where);

                                # pos2
                                if(!empty($filter_dates['filter_date_pos2'])){ # 特殊过滤
                                    $tmp_filter2_where = ['OR'];
                                    $filter_pos2 = $filter_dates['filter_date_pos2'];
                                    sort($filter_pos2);

                                    # 现在
                                    foreach ($filter_poses as $k=>$pos){
                                        $tmp_filter2_where[] = ['<>', 'code_'.$pos, $sscKjData['code'.$filter_pos2[$k]]];
                                    }
                                    $query->andWhere($tmp_filter2_where);
                                }
                            }
                        }
                    }
                }
            }
        }

        # 3、排除期期号定位 05.25
        if(in_array($code_type, [2,3,4]) && isset($codes_hz['filter_qihaos']) && isset($codes_hz['filter_qihaos']['is_filter_qihao']) && $codes_hz['filter_qihaos']['is_filter_qihao']==1){
            $filter_qihaos = $codes_hz['filter_qihaos'];
            $is_filter_qihao = $filter_qihaos['is_filter_qihao'];
            if(!empty($codes)){
                $filter_poses = NumService::getFilterPosByCode($codes[0]); # 根据导入的号码判断要过滤的位置
                if(!empty($filter_poses)){
                    foreach ($filter_poses as $pos){
                        $query->andWhere(['<>', 'code_'.$pos, 'X']);
                    }
                }
                $cnt = count($filter_poses);
                $fcnt = 0 - $cnt;
                if($is_filter_qihao && $cnt>0){
                    $qihao = HN0898Service::getQihao($lottery_type);
                    $sub_qihao = (string)substr($qihao, $fcnt, $cnt); # 短期号 156期，如果二定：56  三定：156
                    //p([$qihao, $sub_qihao, $fcnt, $cnt],0);
                    $tmp_filter2_where = ['OR', ];

                    foreach ($filter_poses as $n=>$pos){ # [1,2]:1千2百、[3,4]:3十4个
                        $tmp_filter2_where[] = ['<>', 'code_'.$pos, $sub_qihao[$n]];
                    }
                    //p(['filter_poses'=>$filter_poses, 'tmp_filter2_where'=>$tmp_filter2_where]);
                    $query->andWhere($tmp_filter2_where);
                }
            }
        }

        (!empty($codes)) && $query->andWhere(['IN', 'code', $codes]);
        ###################################################### filters过滤参数结束05.24 ######################################################

        $Num4Types = $query->asArray()->orderBy(['code'=>SORT_ASC])->all();
        $codesArr = ArrayHelper::getColumn($Num4Types, 'code');
        //p(['where'=>$where, 'index_id'=>$index_id, 'filter_index_ids'=>$filter_index_ids, 'filters'=>$filters, 'code'=>$codes, 'end'=>$codesArr]);
        //p(['where'=>$where, 'codes_hz'=>$codes_hz, 'codesArr'=>$codesArr]);

         # 上奖
        //if(isset($codes_hz['arise']) && !empty($codes_hz['arise'])){
        if(in_array($code_type, [2,3,4]) && isset($codes_hz['arise'])){
            $asises = explode(',', $codes_hz['arise']);
            $codesArr_arise = self::getCodesArise($asises, $type = 1, $code_type);
            //p(['code_type'=>$code_type, 'where'=>$where, 'codes_hz'=>$codes_hz, 'codesArr_arise'=>$codesArr_arise, 'codesArr'=>$codesArr]);
            if(in_array($code_type, [2,3,4])) {
                $codesArr = array_intersect($codesArr, $codesArr_arise); # 函数用于比较两个(或更多个)数组的键值,并返回交集
            }else{
                $codesArr = $codesArr_arise;
            }
        }

        # tz_type:28 上奖取
        if(isset($codes_hz['get_arises'])){
            $codes_hz['arise'] = explode(',', $codes_hz['get_arises']);
            $codesArr_arise = self::getCodesArise([$codes_hz['arise'], $type = 1]);
            $codesArr = array_intersect($codesArr, $codesArr_arise);
        }

        # tz_type:28 上奖除
        if(isset($codes_hz['remove_arises'])){ # remove_arises
            $codes_hz['remove_arises'] = explode(',', $codes_hz['remove_arises']);
            $codesArr_arise = self::getCodesArise($codes_hz['remove_arises'], $type = 0);
            //p([count($codesArr_arise), $codes_hz['remove_arises'], $codesArr_arise]);
            $codesArr = array_intersect($codesArr, $codesArr_arise);
        }

        if($code_type == 4){
            $datas = array_unique($codesArr);
        }else{
            $datas = $codesArr;
        }
        //p(count($datas));

        return $datas;
    }

    /**
     * @desc 获取过滤位置 by code 目前注意针对导入之后再过滤的情况
     * @param string $code
     * @param string $split
     * @return array
     */
    public static function getFilterPosByCode($code='', $split=','){
        $codeArr = explode($split, $code);
        $poses = [];
        foreach ($codeArr as $k=>$n){
            if($n != 'X') $poses[] = $k+1;
        }
        return $poses;
    }

    /**
     * @param $hefens 1234  合分值
     * @param int $lenHefen 位置个数
     * @param $code_type 1一定2二定3三定4四定
     * @return array
     */
    public static function getHezhisByHefen($hefens, $lenPos = 4, $code_type = 4){
        $hezhis = [];
        //p([$hefens, $lenPos],0);

        $lenHefen = strlen($hefens);
        for ($i=0; $i<$lenHefen; $i++){
            $hefensZhi = $hefens[$i];
            if($lenPos == 4){
                if($hefensZhi<=6){
                    $hefenArr = [$hefensZhi, $hefensZhi + 10, $hefensZhi + 20, $hefensZhi + 30];
                }else{
                    $hefenArr = [$hefensZhi, $hefensZhi + 10, $hefensZhi + 20];
                }
            }elseif ($lenPos == 3){
                if($hefensZhi<=7){
                    $hefenArr = [$hefensZhi, $hefensZhi + 10, $hefensZhi + 20];
                }else{
                    $hefenArr = [$hefensZhi, $hefensZhi + 10];
                }
            }elseif ($lenPos == 2){
                if($hefensZhi<=8){
                    $hefenArr = [$hefensZhi, $hefens[$i] + 10];
                }else{
                    $hefenArr = [$hefensZhi];
                }
            }
            $hezhis = array_merge($hezhis, $hefenArr);
        }

        return $hezhis;
    }

    /**
     * @desc 根据codestr转换为array
     * @param $codes_str 34567
     * @return array [3,4,5,6,7]
     */
    public static function getCodesArrByStr($codes_str){
        $strlen = strlen($codes_str);
        $codes_Arr = [];
        for ($i=0; $i<$strlen; $i++){
            $codes_Arr[] = (string)$codes_str[$i];
        }

        return $codes_Arr;
    }

    /**
     * @desc 快选计划描述转换
     * @param $hz_Arr
     * @return string
     */
    public static function getDescByKuaixuan($hz_Arr){
        //p($hz_Arr,0);
        # 双重:type_2、三重:type_3、四重:type_4、双双重:type_22、两兄弟:type_2b、三兄弟:type_3b、四兄弟:type_4b
        //$desc = '[快选] ';
        $desc = '';
        $desc_detail = '';
        $filter0 = []; # 除
        $filter1 = []; # 取
        $filter2 = []; # 和值
        $filter3 = []; # 上奖
        $filter4 = []; # 上奖除
        $filter5 = []; # 和值除
        $filter6 = []; # 类型取
        $filter7 = []; # 类型除
        $filter8 = []; # 合分取
        $filter9 = []; # 合分除
        $filter10 = []; # 号码组
        $filter11 = []; # 定位 含
        # {"get_types":["1","2"],"remove_types":["4","5"],"get_hzs":["7","8","10"],"remove_hzs":["12","13","14"],"get_arises":"123","remove_arises":"456"}
        # 0.1、上奖取
        if(isset($hz_Arr['arise']) OR isset($hz_Arr['get_arises'])){
            if(isset($hz_Arr['get_arises'])) $hz_Arr['arise'] = $hz_Arr['get_arises'];
            if(isset($hz_Arr['arise'])) $filter3['arise'] = $hz_Arr['arise'];// else $filter0['arise'] = 0;
        }
        # 0.2、上奖除 - 新
        if(isset($hz_Arr['remove_arises']) OR isset($hz_Arr['remove_arises'])){
            if(isset($hz_Arr['remove_arises'])) $filter4['remove_arises'] = $hz_Arr['remove_arises'];// else $filter0['arise'] = 0;
        }

        # 1、双重
        if(isset($hz_Arr['type_2'])){
            if($hz_Arr['type_2'] == 1) $filter1['type_2'] = 1; else $filter0['type_2'] = 0;
        }
        # 2、三重
        if(isset($hz_Arr['type_3'])){
            if($hz_Arr['type_3'] == 1) $filter1['type_3'] = 1; else $filter0['type_3'] = 0;
        }
        # 3、四重
        if(isset($hz_Arr['type_4'])){
            if($hz_Arr['type_4'] == 1) $filter1['type_4'] = 1; else $filter0['type_4'] = 0;
        }
        # 4、双双重
        if(isset($hz_Arr['type_22'])){
            if($hz_Arr['type_22'] == 1) $filter1['type_22'] = 1; else $filter0['type_22'] = 0;
        }
        # 5、两兄弟
        if(isset($hz_Arr['type_2b'])){
            if($hz_Arr['type_2b'] == 1) $filter1['type_2b'] = 1; else $filter0['type_2b'] = 0;
        }
        # 6、三兄弟
        if(isset($hz_Arr['type_3b'])){
            if($hz_Arr['type_3b'] == 1) $filter1['type_3b'] = 1; else $filter0['type_3b'] = 0;
        }
        # 7.1、四兄弟
        if(isset($hz_Arr['type_4b'])){
            if($hz_Arr['type_4b'] == 1) $filter1['type_4b'] = 1; else $filter0['type_4b'] = 0;
        }
        # 7.2、对数
        if(isset($hz_Arr['type_log'])){
            if($hz_Arr['type_log'] == 1) $filter1['type_log'] = 1; else $filter0['type_log'] = 0;
        }
        # 7.3、三现:双重+两兄
        if(isset($hz_Arr['type_3n_2b'])){
            if($hz_Arr['type_3n_2b'] == 1) $filter1['type_3n_2b'] = 1; else $filter0['type_3n_2b'] = 0;
        }

        # 8.1、和值
        if((isset($hz_Arr['hz']) && !empty($hz_Arr['hz'])) OR (isset($hz_Arr['get_hzs']) && !empty($hz_Arr['get_hzs']))){
            if(isset($hz_Arr['get_hzs'])) $hz_Arr['hz'] = $hz_Arr['get_hzs'];
            $filter2['hz'] = implode(',',$hz_Arr['hz']);
        }
        # 8.2、和值除 - 新
        if(isset($hz_Arr['remove_hzs']) && !empty($hz_Arr['remove_hzs'])){
            $filter5['hz'] = implode(',',$hz_Arr['remove_hzs']);
        }

        # 9、四单
        if(isset($hz_Arr['type_4d'])){
            if($hz_Arr['type_4d'] == 1) $filter1['type_4d'] = 1; else $filter0['type_4d'] = 0;
        }
        # 10、四双
        if(isset($hz_Arr['type_4s'])){
            if($hz_Arr['type_4s'] == 1) $filter1['type_4s'] = 1; else $filter0['type_4s'] = 0;
        }
        # 11.1、类型取 - 新  双重、三重、四重、双双重
        if(isset($hz_Arr['get_types']) OR isset($hz_Arr['get_types'])){
            if(isset($hz_Arr['get_types'])) $filter6['get_types'] = $hz_Arr['get_types'];// else $filter0['arise'] = 0;
        }
        # 14.1、单双类型取
        if(isset($hz_Arr['type_4ds']) && !empty($hz_Arr['type_4ds'])){
            if(isset($hz_Arr['type_4ds'])) $filter6['type_4ds'] = $hz_Arr['type_4ds'];
        }

        # 11.2、类型除 - 新
        if(isset($hz_Arr['remove_types']) OR isset($hz_Arr['remove_types'])){
            if(isset($hz_Arr['remove_types'])) $filter7['remove_types'] = $hz_Arr['remove_types'];
        }
        # 12.1、号码组1
        if(isset($hz_Arr['code1'])){
            $filter10['code1'] = $hz_Arr['code1'];
        }
        # 12.1、号码组2
        if(isset($hz_Arr['code2'])){
            $filter10['code2'] = $hz_Arr['code2'];
        }
        # 12.1、当前号码组
        if(isset($hz_Arr['status_val'])){
            $filter10['status_val'] = $hz_Arr['status_val'];
        }
        # 13 二、三、四定 含
        if(isset($hz_Arr['arise_in_sel']) && isset($hz_Arr['arise_in'])){
            $filter11 = ['sel'=>$hz_Arr['arise_in_sel'], 'val'=>$hz_Arr['arise_in']];
        }

        # 当前遗漏
        //if(isset($hz_Arr['current_miss'])){
        if(isset($hz_Arr['bet_while_miss'])){
            $desc .= '[遗漏:'.$hz_Arr['bet_while_miss'].'投,当前:'.(int)$hz_Arr['current_miss'].'] ';
        }

        # 合分 - 三定
        if(isset($hz_Arr['hefen_pos']) && isset($hz_Arr['hefen'])){
            if(isset($hz_Arr['hefen_pos']) && isset($hz_Arr['hefen'])) $filter8['hefen'] = 1; else $filter9['hefen'] = 0;
        }
        # 合分 - 四定
        if(isset($hz_Arr['hefen'])){
            if(isset($hz_Arr['hefen'])) $filter8['hefen'] = 1; else $filter9['hefen'] = 0;
        }
        if(!empty($filter10['code1'])){
            $desc .= '组1:'.$filter10['code1'].' ';
        }
        if(!empty($filter10['code2'])){
            $desc .= '组2:'.$filter10['code2'].' ';
        }
        if(!empty($filter10['status_val'])){
            $desc .= '当前:组'.$filter10['status_val'].' ';
        }

        # 导入方式，号码轮换号码组
        if($hz_Arr['change_per']==1){
            $desc .= '组:'.(int)$hz_Arr['turn_key'].'. ';
        }

        # 号码
        if(isset($hz_Arr['codes'])){
            $desc .= '号码:'.$hz_Arr['codes'];
        }

        $typesArr = self::getNameByCodesType();
        #和值取
        if(!empty($filter2['hz'])){
            //$desc .= '和值:'.yii\helpers\BaseStringHelper::truncate($filter2['hz'],10).' ';
            $desc .= '和值:'.$filter2['hz'].' ';
        }
        # 和值除
        if(!empty($filter5['hz'])){
            //$desc .= '和值:'.yii\helpers\BaseStringHelper::truncate($filter2['hz'],10).' ';
            $desc .= '和值除:'.$filter5['hz'].' ';
        }
        # 14.2、单双类型 - 1122,2121,2222 等
        if(isset($hz_Arr['type_ds_details']) && !empty($hz_Arr['type_ds_details'])){
            $desc .= '类型:'.implode(',',$hz_Arr['type_ds_details']);
        }
        if(!empty($filter1)){
            $desc .= '取:';
            foreach ($filter1 as $key1=>$v1){
                if($key1 == 'type_4ds'){
                    $desc .= $typesArr[$key1.'_'.$v1].'、';
                }else{
                    $desc .= $typesArr[$key1].'、';
                }
            }
            $desc = trim($desc, '、').' ';
        }

        if(isset($hz_Arr['p1']) && $hz_Arr['p1'] !== ''){
            $desc .= '千'.$hz_Arr['p1'];
        }
        if(isset($hz_Arr['p2']) && $hz_Arr['p2'] !== ''){
            $desc .= ' 百'.$hz_Arr['p2'];
        }
        if(isset($hz_Arr['p3']) && $hz_Arr['p3'] !== ''){
            $desc .= ' 十'.$hz_Arr['p3'];
        }
        if(isset($hz_Arr['p4']) && $hz_Arr['p4'] !== ''){
            $desc .= ' 个'.$hz_Arr['p4'];
        }
        if(isset($hz_Arr['p5']) && $hz_Arr['p5'] !== ''){
            $desc .= ' 五'.$hz_Arr['p5'];
        }
        if(isset($hz_Arr['ps_1']) && $hz_Arr['ps_1'] !== ''){
            $desc .= ' 配数1:'.$hz_Arr['ps_1'];
        }
        if(isset($hz_Arr['ps_2']) && $hz_Arr['ps_2'] !== ''){
            $desc .= ' 配数2:'.$hz_Arr['ps_2'];
        }

        # 不定位合分:两数、三数
        if(isset($hz_Arr['no_fix_hefen_pos']) && isset($hz_Arr['no_fix_hefen'])){ # no_fix_hefen_pos=1:两数、no_fix_hefen_pos=2:三数
            if($hz_Arr['no_fix_hefen_pos'] == 2){
                $desc .= ' 三不定合:'.$hz_Arr['no_fix_hefen'];
            }else{
                $desc .= ' 两不定合:'.$hz_Arr['no_fix_hefen'];
            }
        }

        if(!empty($filter8)){
            if(isset($hz_Arr['hefen_pos'])){
                $desc .= ' 合分取[位:'.$hz_Arr['hefen_pos'] . ' 合分:'.$hz_Arr['hefen'].']';
            }else{
                $desc .= ' 合分取[位:1,2,3,4 '. '合分:'.$hz_Arr['hefen'].']';
            }
        }

        if(!empty($filter11)){
            $desc .= '定位含:';
            $desc .= $filter11['sel'] == 1 ? '除' : '取';
            $desc .= $filter11['val'];
        }

        # 上奖取
        if(!empty($filter3)){
            $desc .= ' 上奖:';
            foreach ($filter3 as $key3=>$v3){
                $desc .= $v3.',';
            }
            $desc = trim($desc, ',').' ';
        }
        # 上奖除
        if(!empty($filter4)){
            $desc .= '上奖除:';
            foreach ($filter4 as $key4=>$v4){
                $desc .= $v4.'、';
            }
            $desc = trim($desc, ',').' ';
        }

        if(!empty($filter0)){
            $desc .= ' 除:';
            foreach ($filter0 as $key0=>$v0){
                $desc .= $typesArr[$key0].',';
            }
            $desc = trim($desc, ',').' ';
        }
        # 类型取
        if(!empty($filter6) OR !empty($filter7)){
            $codeTypes1 = UserSysPlansService::getCodeTypes($flag = 1);
            $codeTypes2 = UserSysPlansService::getCodeTypes($flag = 2);
            //p([$hz_Arr['get_types'], $filter6['get_types']]);
            if(!empty($filter6)){
                $desc .= '类型取:';
                if(!empty($filter6['get_types'])){
                    foreach ($filter6['get_types'] as $key6=>$v6){
                        $desc .= $codeTypes1[$v6].',';
                    }
                }
                if(!empty($filter6['type_4ds'])){
                    foreach ($filter6['type_4ds'] as $key6=>$v6){
                        $desc .= $codeTypes2[$v6].',';
                    }
                }
                $desc = trim($desc, ',').' ';
            }
            # 上奖除
            if(!empty($filter7)){
                $desc .= '类型除:';
                foreach ($filter7['remove_types'] as $key7=>$v7){
                    $desc .= $codeTypes1[$v7].',';
                }
                $desc = trim($desc, ',').' ';
            }
        }

        return $desc;
    }

    /**
     * @desc 返回筛选名称
     * @param string $type
     * @return array|mixed
     */
    public static function getNameByCodesType($type = ''){
        # {"get_types":["1","2"],"remove_types":["4","5"],"get_hzs":["7","8","10"],"remove_hzs":["12","13","14"],"get_arises":"123","remove_arises":"456"}
        $typeArr = [
            'type_2'=>'双重',
            'type_3'=>'三重',
            'type_4'=>'四重',
            'type_22'=>'双双',
            'type_2b'=>'两兄',
            'type_3b'=>'三兄',
            'type_3n_2b'=>'三现[双重+两兄]',
            'type_4b'=>'四兄',
            'type_log'=>'对数',
            'type_4d'=>'四单',
            'type_4s'=>'四双',
            'arise'=>'上奖',
            'remove_arises'=>'上奖除',
            'get_types'=>'类型取',
            'remove_types'=>'类型除',
            //'hefen_pos'=>'合分位',
            'hefen'=>'合分',
        ];

        if($typeArr[$type]) return $typeArr[$type];

        return $typeArr;
    }

    public static function gendouble3Nums(){

    }

    /**
     * @desc 去除最近多少期号码 - 四定
     * @param array $code_hz
     * @return array
     */
    public static function getNotLatelyCodes($code_hz = ['lately_start'=>0, 'lately_end'=>400], $lottery_type = DEFAULT_LOTTERY_TYPE){

        $last = SscKjData::find()->where(['lottery_type'=>$lottery_type])->select(['last_id'=>'index_id'])->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();

        $startIndexId = $last['last_id'] - $code_hz['lately_end'];
        $endIndexId = $last['last_id'] - $code_hz['lately_start'];
        $where = ['AND', ['>=', 'index_id', $startIndexId], ['<=', 'index_id', $endIndexId], ['=', 'lottery_type', $lottery_type]];
        $areaKjCodes = SscKjData::find()->select(['qihao', 'code_4n', 'type_4', 'type_3', 'type_2'])->where($where)->asArray()->all();
        $code_4ns = ArrayHelper::getColumn($areaKjCodes, 'code_4n');

        $latelyCodes = SscDataService::getAriseCodes($code_4ns); # 缓存开奖号码四定组合 ,最近开奖号码全倒
        $latelyCodes = array_unique($latelyCodes);
        //p(count($latelyCodes));

        $codes = Num4Type::find()->where(['AND', ['=', 'code_type', 4], ['>', 'id', 0], ['not in', 'code', $latelyCodes]])->asArray()->all();

        $codesArr = ArrayHelper::getColumn($codes, 'code');

        return $codesArr;
    }

    /**
     * @desc 获取二字定的号码
     * @param array $codeArr
     * @return array
     */
    public static function getCodesTwo($codes = [1, 2]){
        if(count($codes) != 2 && count($codes) != 3) return ['status'=>300, 'msg'=>'号码错误'];

        if(count($codes) == 2){
            $datas = [
                [$codes[0], $codes[1], 'X', 'X'],
                [$codes[1], $codes[0], 'X', 'X'],
                [$codes[0], 'X', $codes[1], 'X'],
                [$codes[1], 'X', $codes[0], 'X'],
                [$codes[0], 'X', 'X', $codes[1]],
                [$codes[1], 'X', 'X', $codes[0]],
                ['X', $codes[0], $codes[1], 'X'],
                ['X', $codes[1], $codes[0], 'X'],
                ['X', $codes[0], 'X', $codes[1]],
                ['X', $codes[1], 'X', $codes[0]],
                ['X', 'X', $codes[0], $codes[1]],
                ['X', 'X', $codes[1], $codes[0]],
            ];
        }else{
            $datas = [
                [$codes[0], $codes[1], $codes[2], 'X'],
                [$codes[0], $codes[2], $codes[1], 'X'],
                [$codes[1], $codes[0], $codes[2], 'X'],
                [$codes[1], $codes[2], $codes[0], 'X'],
                [$codes[2], $codes[0], $codes[1], 'X'],
                [$codes[2], $codes[1], $codes[0], 'X'],

                [$codes[0], $codes[1], 'X', $codes[2]],
                [$codes[0], $codes[2], 'X', $codes[1]],
                [$codes[1], $codes[0], 'X', $codes[2]],
                [$codes[1], $codes[2], 'X', $codes[0]],
                [$codes[2], $codes[0], 'X', $codes[1]],
                [$codes[2], $codes[1], 'X', $codes[0]],

                [$codes[0], 'X', $codes[1], $codes[2]],
                [$codes[0], 'X', $codes[2], $codes[1]],
                [$codes[1], 'X', $codes[0], $codes[2]],
                [$codes[1], 'X', $codes[2], $codes[0]],
                [$codes[2], 'X', $codes[0], $codes[1]],
                [$codes[2], 'X', $codes[1], $codes[0]],

                ['X', $codes[0], $codes[1], $codes[2]],
                ['X', $codes[0], $codes[2], $codes[1]],
                ['X', $codes[1], $codes[0], $codes[2]],
                ['X', $codes[1], $codes[2], $codes[0]],
                ['X', $codes[2], $codes[0], $codes[1]],
                ['X', $codes[2], $codes[1], $codes[0]],
            ];
        }

        return $datas;
    }

    /**
     * @desc 返回五位二定
     * @param array $codes
     * @return array
     */
    public static function getCodesTwo5($codes = [1, 2]){
        if(count($codes) != 2) return ['status'=>300, 'msg'=>'号码错误'];

        if($codes[0] == $codes[1]){
            $datas = [
                [$codes[0],'X','X','X', $codes[1]],

                ['X',$codes[0],'X','X', $codes[1]],

                ['X','X',$codes[0],'X', $codes[1]],

                ['X','X','X',$codes[0], $codes[1]],
            ];
        }else{
            $datas = [
                [$codes[0],'X','X','X', $codes[1]],
                [$codes[1],'X','X','X', $codes[0]],

                ['X',$codes[0],'X','X', $codes[1]],
                ['X',$codes[1],'X','X', $codes[0]],

                ['X','X',$codes[0],'X', $codes[1]],
                ['X','X',$codes[1],'X', $codes[0]],

                ['X','X','X',$codes[0], $codes[1]],
                ['X','X','X',$codes[1], $codes[0]],
            ];
        }

        return $datas;
    }

    /**
     * @desc 删除数组指定值的元素使用array_keys搜索指定的值再循环unset
     * @param $arr
     * @param $value
     * @return mixed
     */
    public static function delByValue($arr, $value){
        if(empty($arr) OR empty($value)) return [];
        $keys = array_keys($arr, $value);
        if(!empty($keys)){
            foreach ($keys as $key) {
                unset($arr[$key]);
            }
        }
        return array_values($arr);
    }

    /**
     * @desc 转换处理  头：千、尾：个
     * @param $codes_desc
     * @return mixed
     */
    public static function opChangeDesc($codes_desc){

        $codes_desc = str_replace('头', '千', $codes_desc);
        $codes_desc = str_replace('尾', '个', $codes_desc);

        $codes_desc = str_replace('二字定', '二定', $codes_desc);
        $codes_desc = str_replace('两定', '二定', $codes_desc);
        $codes_desc = str_replace('两字定', '二定', $codes_desc);

        $codes_desc = str_replace('三字定', '三定', $codes_desc);
        $codes_desc = str_replace('四字定', '四定', $codes_desc);

        return $codes_desc;
    }

    /**
     * @desc 快译描述转换成位置号码，支持一二三四定
     * @param $codes_desc - 千12345百12345十67890
     * @return array ['p1'=>12345, 'p2'=>12345, 'p3'=>67890]
     */
    public static function getCodesHzByDesc($codes_desc='', &$msg=''){
        //echo $codes_desc.'<br>';
        $data = [];
        if(!$codes_desc) return $data;

        $codes_desc = NumService::opChangeDesc($codes_desc); # 替换通用位置名词 头->千、尾->个

        $code_type = NumService::getCodeTypeByDesc($codes_desc, $positions); # 获取定位类型

        $data = NumService::getHzsByDesc($codes_desc, $data);

        # 获取位置号码除、取
        $data = NumService::getPosCodes($codes_desc, $data); # p1、p1_0

        //p(['code_type'=>$code_type, 'pos'=>$positions, 'data'=>$data]);
        # 筛选逻辑包括两数合、三数合、跑=移、值范围、取双重、除双重、取三重、除三重、取双双重、除双双重、取二兄弟、除二兄弟、
        # 取千单、 除千单、取千大、除千大、取百单、除百单、取百大、除百大、取十单、除十单、取十大、除十大、取个单、除个单、取个大、除个大

        $data = NumService::getCodeType($codes_desc, $data);# 号码类型：type_2、type_3、type_22、type_4 等

        $data = NumService::getCodeTypeDw($codes_desc, $data); # 定位：23568头尾 、千1234百4567十7890
        //p([$codes_desc, $data]);

        $data = NumService::getCodeTypeDao($codes_desc, $data); # 倒类型

        $data = NumService::getCodeTypeFixPosHeFen($codes_desc, $data); # 不定位

        $data = NumService::getSingleByDesc($codes_desc, $data);# 获取倍数

        # 走移、 现  暂未完成
        $data = NumService::getCodeTypeZouYi($codes_desc, $data); # 走移

        $data['code_type'] = $code_type;
        if($code_type == 2 && $data['single']<1){
            $msg = '二定最少1元';
        }

        return $data;
    }

    /**
     * @desc 根据code描述获取号码
     * @param $codes_desc
     * @param  $code_type 号码类型：1字定2二字定3三字定4四字定
     * @return array
     */
    public static function getCodesByDesc($codes_descs, $code_type = ''){
        $codesArr = [];
        $codes_descArr = explode(',', $codes_descs);

        foreach ($codes_descArr as $codes_desc){
            $codes_hz = NumService::getCodesHzByDesc($codes_desc);
            //p($codes_hz);
            if(empty($code_type)) $code_type = $codes_hz['code_type'];
            $tmpCodesArr = NumService::getCodesKuaiXuan($codes_hz, $code_type);
            $logArr = ['codes_desc'=>$codes_desc, 'codes_hz'=>$codes_hz, 'tmpCodesArr'=>$tmpCodesArr, 'counts'=>count($tmpCodesArr)];
            Tool_Common::log('/getCodes/'.__FUNCTION__, 'INFO', '根据code描述获取号码', $logArr);
            if($tmpCodesArr) $codesArr = array_merge($codesArr, $tmpCodesArr);
        }

        $codesArr = array_unique($codesArr);

        return $codesArr;
    }

    /**
     * @desc 根据描述判断号码类型：
     * @param $codes_desc - 四字定千1234百4567十7890个2468、三字定千1234百6789十1357、二定千02468百13579、千2345十5678个0289
     * @return int code_type 1一字定2二字定3三字定4四字定
     */
    public static function getCodeTypeByDesc($codes_desc, &$positions = []){
        $code_type = 0;

        # 1、出现：XX定、X定，则首先判断
        if(strpos($codes_desc, '一定') !== false OR strpos($codes_desc, '一字定') !== false){
            $code_type = 1;
        }elseif (strpos($codes_desc, '二定') !== false OR strpos($codes_desc, '二字定') !== false){
            $code_type = 2;
        }elseif (strpos($codes_desc, '三定') !== false OR strpos($codes_desc, '三字定') !== false){
            $code_type = 3;
        }elseif (strpos($codes_desc, '四定') !== false OR strpos($codes_desc, '四字定') !== false){
            $code_type = 4;
        }

        # 2、不出现：XX定、X定，则判断：千百十个出现次数来判断定位类型
        $num = 0;
        $types = ['千', '百', '十', '个', '五'];
        foreach ($types as $type){
            if(strpos($codes_desc, $type) !== false){
                $num = $num + 1;
                $positions[] = $type; # 记录：千、百、十、个、五
            }
        }

        if($code_type == 0){
            $code_type = $num;
        }

        return $code_type;
    }

    /**
     * @param $codes_desc 值范围15-35、值15,17,18
     * @param $data
     * @return mixed
     */
    public static function getHzsByDesc($codes_desc, &$data){

        if(!$data['hz']) $data['hz'] = [];
        if(preg_match("/值范围\d+\-\d+/", $codes_desc, $returns) OR preg_match("/值\d+\-\d+/", $codes_desc, $returns)){
            $str = str_replace('值范围', '', $returns[0]);
            $str = str_replace('值', '', $str);
            if(strpos($str, '-') !== false){
                # 和值区间
                $zhi_scopes = explode('-', $str);
                if(count($zhi_scopes) == 1){
                    $min_zhi = $max_zhi = $zhi_scopes[0];
                }else{
                    $min_zhi = array_shift($zhi_scopes);
                    $max_zhi = end($zhi_scopes);
                }
                for ($i=$min_zhi; $i<=$max_zhi; $i++){
                    $data['hz'][] = $i;
                }

            }
        }elseif (preg_match("/值范围\d+\,\d+/", $codes_desc, $returns) OR preg_match("/值\d+\,\d+/", $codes_desc, $returns)){
            $str = str_replace('值范围', '', $returns[0]);
            $str = str_replace('值', '', $str);
            $data['hz'] = explode(',', $str);
        }
        if(empty($data['hz'])) unset($data['hz']);

        return $data;
    }

    /**
     * @desc 获取位置的号码
     * @param $codes_desc
     * @param $data
     * @return mixed
     */
    public static function getPosCodes($codes_desc, &$data){
        if(empty($data)) $data = [];

        $get_types = [
            '取千大'=>['p1'=>'56789'],'取千小'=>['p1'=>'01234'],'取千单'=>['p1'=>'13579'],'取千双'=>['p1'=>'02468'],'除千大'=>['p1_0'=>'56789'],'除千小'=>['p1_0'=>'01234'],'除千单'=>['p1_0'=>'13579'],'除千双'=>['p1_0'=>'02468'],
            '取百大'=>['p2'=>'56789'],'取百小'=>['p2'=>'01234'],'取百单'=>['p2'=>'13579'],'取百双'=>['p2'=>'02468'],'除百大'=>['p2_0'=>'56789'],'除百小'=>['p2_0'=>'01234'],'除百单'=>['p2_0'=>'13579'],'除百双'=>['p2_0'=>'02468'],
            '取十大'=>['p3'=>'56789'],'取十小'=>['p3'=>'01234'],'取十单'=>['p3'=>'13579'],'取十双'=>['p3'=>'02468'],'除十大'=>['p3_0'=>'56789'],'除十小'=>['p3_0'=>'01234'],'除十单'=>['p3_0'=>'13579'],'除十双'=>['p3_0'=>'02468'],
            '取个大'=>['p4'=>'56789'],'取个小'=>['p4'=>'01234'],'取个单'=>['p4'=>'13579'],'取个双'=>['p4'=>'02468'],'除个大'=>['p4_0'=>'56789'],'除个小'=>['p4_0'=>'01234'],'除个单'=>['p4_0'=>'13579'],'除个双'=>['p4_0'=>'02468'],
            '取五大'=>['p5'=>'56789'],'取五小'=>['p5'=>'01234'],'取五单'=>['p5'=>'13579'],'取五双'=>['p5'=>'02468'],'除五大'=>['p5_0'=>'56789'],'除五小'=>['p5_0'=>'01234'],'除五单'=>['p5_0'=>'13579'],'除五双'=>['p5_0'=>'02468'],
        ];
        foreach ($get_types as $key=>$get_type){
            if(strpos($codes_desc, $key) !== false){
                $data = array_merge($data, $get_types[$key]);
            }
        }

        $get_num_types = [
            '取千'=>'p1','除千'=>'p1_0',
            '取百'=>'p2','除百'=>'p2_0',
            '取十'=>'p3','除十'=>'p3_0',
            '取个'=>'p4','除个'=>'p4_0',
            '取五'=>'p5','除五'=>'p5_0',
        ];
        foreach ($get_num_types as $get_num_type=>$val){
            if(strpos($codes_desc, $get_num_type) !== false){
                preg_match("/".$get_num_type."\d+/", $codes_desc, $matches);
                $match_codes = str_replace($get_num_type, '', $matches[0]);
                $data = array_merge($data, [$val=>$match_codes]);
            }
        }

        return $data;
    }

    /**
     * @desc 号码类型筛选
     * @param $codes_desc
     * @param $data
     * @return mixed
     */
    public static function getCodeType($codes_desc, &$data){

        # 双重:type_2
        if(strpos($codes_desc, '除双重') !== false){
            $data['type_2'] = 0;
        }
        if(strpos($codes_desc, '取双重') !== false){
            $data['type_2'] = 1;
        }

        # 三重:type_3
        if(strpos($codes_desc, '除三重') !== false){
            $data['type_3'] = 0;
        }
        if(strpos($codes_desc, '取三重') !== false){
            $data['type_3'] = 1;
        }
        # 四重:type_4
        if(strpos($codes_desc, '除四重') !== false){
            $data['type_4'] = 0;
        }
        if(strpos($codes_desc, '取四重') !== false){
            $data['type_4'] = 1;
        }

        # 双双重、两双重：type_22
        if(strpos($codes_desc, '除双双重') !== false OR strpos($codes_desc, '除两双重') !== false){
            $data['type_22'] = 0;
        }
        if(strpos($codes_desc, '取双双重') !== false OR strpos($codes_desc, '取两双重') !== false){
            $data['type_22'] = 1;
        }

        # 二兄弟、两兄弟、兄弟
        if(strpos($codes_desc, '取二兄弟') !== false OR strpos($codes_desc, '取兄弟') !== false OR strpos($codes_desc, '取两兄弟') !== false){
            $data['type_2b'] = 1;
        }
        if(strpos($codes_desc, '除二兄弟') !== false OR strpos($codes_desc, '除兄弟') !== false OR strpos($codes_desc, '除两兄弟') !== false){
            $data['type_2b'] = 0;
        }

        return $data;
    }

    /**
     * @desc 获取一定号码
     * @param $codes_hz ['p1'=>'123', 'p2'=>'234', 'p3'=>'3267', 'p4'=>'5678', 'p5'=>'8095']
     * @return array
     */
    public static function getOneFixedCode($codes_hz){
        $codeArr = [];
        //$str = implode(',', $codes_hz);
        $poss = ['p1', 'p2', 'p3', 'p4', 'p5'];
        foreach ($poss as $pos){
            if(!isset($codes_hz[$pos]) OR empty($codes_hz[$pos])){
                $codeArr[$pos] = '';
            }else{
                $codeArr[$pos] = $codes_hz[$pos];
            }
        }

        return [implode(',', $codeArr)];
    }

    /**
     * @desc 例如:23456头尾各1、千百十456789各0.1、头尾23456各1、千百十456789各0.1
     * @param $codes_desc
     * @param array $data
     * @return array
     */
    public static function getCodeTypeDw($codes_desc, &$data = []){

        $posData = [ # 支持不同汉族类型的位置翻译成数据表字段
            '千百十'=>['p1', 'p2', 'p3'], '千百个'=>['p1', 'p2', 'p4'], '千十个'=>['p1', 'p3', 'p4'], '百十个'=>['p2', 'p3', 'p4'],
            '千百'=>['p1', 'p2'], '千十'=>['p1', 'p3'], '千个'=>['p1', 'p4'], '百十'=>['p2', 'p3'], '百个'=>['p2','p4'], '十个'=>['p3','p4'],
            '千'=>['p1'], '百'=>['p2'],'十'=>['p3'], '个'=>['p4'], '五'=>['p5'],
        ];

        $pds = [ # 文字跟上面的数组一一对应
            '千百十'=>['千', '百', '十'], '千百个'=>['千', '百', '个'], '千十个'=>['千', '十', '个'], '百十个'=>['百', '十', '个'],
            '千百'=>['千', '百'], '千十'=>['千', '十'],'千个'=>['千', '个'], '百十'=>['百', '十'], '百个'=>['百', '个'], '十个'=>['十', '个'],
            '千'=>['千'], '百'=>['百'],'十'=>['十'], '个'=>['个'], '五'=>['五'],
        ];

        $is_num_head = 0;
        preg_match("/^[0-9]+/", $codes_desc, $is_num_head_matches);  # 匹配是否数字开头
        if(!empty($is_num_head_matches[0])){
            $is_num_head = 1;
        }

        //p([$is_num_head, $codes_desc, $is_num_head_matches]);
        //p($codes_desc);
        $hasOp = [];
        foreach ($posData as $key=>$poss){
            //if(in_array($key, $hasOp)) break;
            if(strpos($codes_desc, $key) !== false) {
                $hasOp = array_merge($hasOp, $pds[$key]);

                if($is_num_head){
                    preg_match("/[0-9]+".$key."/", $codes_desc, $matches2);  # 023468头尾、 123百567千 数字开头
                }else{
                    preg_match("/".$key."[0-9]+/", $codes_desc, $matches1);  # 头百尾23456、头尾，中文开头
                    /* 新改造，暂时注释掉
                    if(empty($matches1[0])){
                        preg_match("/".$key."[0-9]+/", $codes_desc, $matches1);  # 头百尾23456、头尾
                    }
                    preg_match("/^[0-9]+".$key."/", $codes_desc, $matches2);  # 023468头尾
                    if(empty($matches2[0])){
                        preg_match("/".$key."[0-9]+/", $codes_desc, $matches2);  # 头百尾23456、头尾
                    }
                    */
                }

                $matches = max($matches1[0], $matches2[0]);
                //p(['matches1'=>$matches1, 'matches2'=>$matches2, 'matches'=>$matches, $poss]);
                $nums = str_replace($key, '', $matches);
                foreach ($poss as $pos){
                    //p([$key, $pos, $nums, $matches], 0);
                    $data[$pos] = $nums;
                }
                if(count($poss)>1) return $data;
            }
        }

        return $data;
    }

    /**
     * @desc 例如:234倒两各1、456倒三定各1、2345倒四定各1
     * @param $codes_desc
     * @param array $data
     * @return array
     */
    public static function getCodeTypeDao($codes_desc, &$data = []){

        if(strpos($codes_desc, '倒二定') !== false){
            $data['code_type'] = 2;
        }
        if(strpos($codes_desc, '倒三定') !== false){
            $data['code_type'] = 3;
        }
        if(strpos($codes_desc, '倒四定') !== false){
            $data['code_type'] = 4;
        }

        preg_match("/^[0-9]+倒/", $codes_desc, $matches2);  # 023468倒x定
        if(empty($matches2[0])){
            preg_match("/倒[0-9]+/", $codes_desc, $matches2);  # 头百尾23456、头尾
        }
        if(!empty($matches2[0])){
            $codes = str_replace('倒', '', $matches2[0]);
            $data['arise'] = $codes;
        }

        return $data;
    }

    /**
     * @desc 例如:234千走456两定各1、234千走456三定各1、234千走456三定各1
     * @param $codes_desc
     * @param array $data
     * @return array
     */
    public static function getCodeTypeZouYi($codes_desc, &$data = []){

        $codes_desc = str_replace('走移', '走', $codes_desc);

        if(strpos($codes_desc, '走') !== false){
            $data['code_type'] = 2;
        }

        preg_match("/走[0-9]+/", $codes_desc, $matches2);  # 走345
        if(!empty($matches2[0])){
            $codes = str_replace('走', '', $matches2[0]);
            $data['zou_yi'] = $codes;
        }

        return $data;
    }

    /**
     * @desc 例如:千12345百12345十67890合分2345各0.1 - 暂支持不定位合分
     * @param $codes_desc
     * @param array $data
     * @return array
     */
    public static function getCodeTypeFixPosHeFen($codes_desc, &$data = []){
        # no_fix_hefen:不定位合分值、no_fix_hefen_pos:1两数2三数

        if(strpos($codes_desc, '三数合分') !== false OR strpos($codes_desc, '三数合') !== false){
            $data['no_fix_hefen_pos'] = 2;
            preg_match("/三数合分[0-9]+/", $codes_desc, $matches2);  # 023468倒x定
            $codes = str_replace('三数合分', '', $matches2[0]);
            $codes = str_replace('三数合', '', $codes);
        }else{
            if(strpos($codes_desc, '合分') !== false){# 默认两数合分
                $data['no_fix_hefen_pos'] = 1;
                preg_match("/合分[0-9]+/", $codes_desc, $matches2);  # 023468倒x定
                $codes = str_replace('两数合分', '', $matches2[0]);
                $codes = str_replace('两数合', '', $codes);
                $codes = str_replace('合分', '', $codes);
            }

        }
        if(!empty($codes)){
            $data['no_fix_hefen'] = $codes;
        }

        return $data;
    }

    /**
     * @desc 获取倍数
     * @param $codes_desc
     * @param array $data
     * @return array
     */
    public static function getSingleByDesc($codes_desc, &$data = []){

        if(strpos($codes_desc, '各') !== false){
            preg_match("/各([0-9].)?(\d)+/", $codes_desc, $matches);
            $data['single'] = str_replace('各', '', $matches[0]);
        }

        return $data;
    }

    /**
     * @param $desc
     * @return float
     */
    public static function getNeedMoneyByDesc($desc){
        $money = 10.00;

        return $money;
    }

    /**
     * @desc 按计划id做利润数据统计
     * @return array
     */
    public static function staticPlansProfits($limit = 1000){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        $m = \Yii::$app->cache;
        $where = ['OR', ['AND', ['=', 'account', 'admin'], ['=', 'status', 1]]];
        $planids = explode(',', SystemConfig::findOne(['key'=>'system_static_plan_ids'])->value);
        foreach ($planids as $planid){
            $where = array_merge($where, [['=', 'id', $planid]]);
        }

        $plans = UserSysPlans::find()->where($where)->all();

        $time = time();
        foreach ($plans as $plan){
            $mkey = 'staticPlansProfits_plan_'.$plan->id;
            if(!$last_id = $m->get($mkey)){
                $last_id = 0;
            }
            $lottery_type = $plan->lottery_type;
            $where = ['AND', ['=', 'lottery_type', $lottery_type], ['>=', 'date', '2018-01-01']];
            $last_qihao = NumService::getLastStaticProfitsQihao($lottery_type, $plan->id);
            if($last_qihao){
                $where = array_merge($where, [['>', 'qihao', $last_qihao]]);
            }else{
                $where = array_merge($where, [['>', 'id', $last_id]]);
            }
            $plan_mkey = 'plan_id_mkey_'.$plan->id;
            if(!$codesStrs = $m->get($plan_mkey)){
                $codesStrs = BetService::getPlansAllCodesType1($plan->tz_type, $plan->buy_type, $plan->sel_same, $plan->hz_Arr, $plan->id);
                $m->set($plan_mkey, $codesStrs, 5 * 60);
            }
            $count = count(explode('@', $codesStrs));
            $bet_money = $count * $plan->single;

            $SscKjDatas = SscKjData::find()->where($where)->limit($limit)->all();
            foreach ($SscKjDatas as $SscKjData){
                $qihao = $SscKjData->qihao;
                # 存在记录则 continue
                $where = ['AND', ['=', 'qihao', $qihao], ['=', 'plan_id', $plan->id]];
                if($StaticProfits = StaticProfits::find()->where($where)->one()){
                    continue;
                }

                # static_profits 表
                $where = ['AND', ['=', 'plan_id', $plan->id], ['=', 'qihao', $SscKjData->qihao], ['=', 'lottery_type', $lottery_type]];
                if($StaticProfits = StaticProfits::find()->where($where)->one()){
                    continue;
                }

                $kjCode = substr($SscKjData->code_str, 0, 7);
                if(strpos($codesStrs, $kjCode) !== false){
                    $flag = true;
                    # 中奖
                    $zjBouns = 9950 * $plan->single;
                    $profits = $zjBouns - $bet_money;# 中奖金额 - 投注金额
                }else{
                    $flag = false;
                    $zjBouns = 0;
                    $profits = $zjBouns - $bet_money;# 中奖金额 - 投注金额
                }

                $StaticProfits = new StaticProfits();
                $cut_profits = $profits + NumService::getCutProfits($qihao, $plan->id, $lottery_type); # 截至当前期利润
                $setDatas = [
                    'plan_id' => $plan->id,
                    'uid' => $plan->uid,
                    'static_time' => substr($SscKjData->date,0,7),
                    'playway' => $plan->playway,
                    'qihao' => $qihao,
                    'kj_code' => $SscKjData->code_str,
                    'tz_money' => $bet_money,
                    'profits' => $profits,
                    'zj_bouns' => $zjBouns,
                    'lottery_type' => $lottery_type,
                    'cut_profits' => substr($SscKjData->date,8) == '01' ? $profits : $cut_profits,
                    'tz_time' => (string)$time,
                    'created_at' => $time,
                    'updated_at' => $time,
                ];
                $StaticProfits->setAttributes($setDatas);
                $r = $StaticProfits->save();
                if(!$r){
                    p($StaticProfits->getErrors());
                }
                $rst['data'][$qihao]['rst'] = $r;
                $rst['data'][$qihao]['profits'] = $profits;
                $rst['data'][$qihao]['bet_money'] = $bet_money;
                $rst['data'][$qihao]['kj_codes'] = $kjCode;
                $rst['data'][$qihao]['cut_profits'] = $cut_profits;
                //$rst['data'][$qihao]['codesStrs'] = $codesStrs;
                $rst['data'][$qihao]['flag'] = (int)$flag;

                //p(['flag'=>(int)$flag, 'kjCode'=>$kjCode, 'profits'=>$profits, 'codesArr'=>$codesStrs,  /*$SscKjData->attributes*/]);
            }
        }
        Tool_Common::log('staticPlansProfits', 'INFO', '数据统计', $rst);

        return $rst;
    }

    /**
     * @desc 获取最后统计的期号
     * @param $lottery_type
     * @return mixed
     */
    public static function getLastStaticProfitsQihao($lottery_type, $plan_id = ''){

        $where = ['AND', ['=', 'plan_id', $plan_id], ['=', 'lottery_type', $lottery_type]];
        $StaticProfits = StaticProfits::find()->where($where)->orderBy(['qihao'=>SORT_DESC])->one();
        $last_qihao = $StaticProfits->qihao;
        if(!$last_qihao){
            $last_qihao = '';
        }
        //$m = \Yii::$app->cache;
        //$pkey = NumService::buildLastStaticProfitsKey($plan_id, $lottery_type);
        //$m->set($pkey, $last_qihao, 3600);

        return $last_qihao;
    }

    /**
     * @desc 返回统计期号key
     * @return string
     */
    public static function buildLastStaticProfitsKey($plan_id, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $pkey = 'mkey_staticPlansProfits_0_'.$plan_id.'_'.$lottery_type;

        return $pkey;
    }

    /**
     * @desc 获取截止上一期的利润
     * @param $qihao
     * @param $plan_id
     * @param int $lottery_type
     * @return float
     */
    public static function getCutProfits($qihao, $plan_id, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $profits = 0.00;

        $where = ['AND', ['<', 'qihao', $qihao], ['=', 'plan_id', $plan_id], ['=', 'lottery_type', $lottery_type]];
        $StaticProfits = StaticProfits::find()->where($where)->orderBy(['id'=>SORT_DESC])->one();
        if($StaticProfits){
            $profits = $StaticProfits->cut_profits;
        }

        return $profits;
    }
}