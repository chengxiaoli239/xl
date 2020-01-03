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
use backend\models\SystemConfig;
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
     * @param $code_type 1一定2二定3三定4四定
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
        if($code_type == 4){
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
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination2($codes, $type = 1, $code_type = 4){
        if(strlen($codes) != 2) return [];

        if($code_type == 2){
            $tmpCodesArr = NumService::getCodesTwo([$codes[0], $codes[1]]); # 格式：[['1','2', 'X', 'X'], ['1', 'X', '2', 'X']] ..
            $codesArr = [];
            foreach ($tmpCodesArr as $tmpCodes){
                $codesArr[] = implode(',', $tmpCodes);
            }
        }else{
            if($type == 1){ # 取
                 $where = [
                     'AND',
                     ['=', 'code_type', 4],
                     [
                         'OR',
                         ['LIKE', 'code', '%'.$codes[0].','.$codes[1].'%', false],
                         ['LIKE', 'code', '%'.$codes[0].'%'.$codes[1].'%', false],

                         ['LIKE', 'code', '%'.$codes[1].','.$codes[0].'%', false],
                         ['LIKE', 'code', '%'.$codes[1].'%'.$codes[0].'%', false],
                     ]
                ];
            }else{ # 除
                if($codes[0] == $codes[1]){ # 双重
                    $where = [
                        'AND',
                        ['NOT LIKE', 'code', $codes[0].','.$codes[1].',%,%', false],
                        ['NOT LIKE', 'code', $codes[0].',%,'.$codes[1].',%', false],
                        ['NOT LIKE', 'code', $codes[0].',%,%,'.$codes[1], false],
                        ['NOT LIKE', 'code', '%,'.$codes[0].','.$codes[1].',%', false],
                        ['NOT LIKE', 'code', '%,'.$codes[0].',%,'.$codes[1], false],
                        ['NOT LIKE', 'code', '%,%,'.$codes[0].','.$codes[1], false],
                        ['=', 'code_type', 4],
                    ];
                }else{
                    $where = [
                        'AND',
                        ['NOT LIKE', 'code', '%'.$codes[0].'%', false],
                        ['NOT LIKE', 'code', '%'.$codes[1].'%', false],
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
     * @param $codes 格式：111或者112或者123
     * @param $type 0除1取
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination3($codes, $type = 1, $code_type = 4){
        if(strlen($codes) != 3) return [];

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

        return array_unique($codesArr);
    }

    /**
     * @desc 大于4个号码返回四定号码组合号码
     * @param $codes 格式：11234或者11123或者11223或者12345或者123456
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination4p($codes, $codeSplit = '', $code_type = 4){
        if(strlen($codes)<5) return [];
        $tmpArr = [];
        $len = strlen($codes);
        for($i=0; $i<$len; $i++){
            $tmpArr[] = $codes[$i];
        }
        $tmpArr1 = $tmpArr2 = $tmpArr3 = $tmpArr4 = $tmpArr;

        # 第1步：循环获取二字组合
        $codes2Arr = [];
        $codes3Arr = [];
        $codes4Arr = [];
        foreach ($tmpArr1 as $k1=>$v1){
            $fen = floor(count($tmpArr1) / 2);
            if($k1+1>$fen) break;
            foreach ($tmpArr2 as $k2=>$v2){
                if($k2<=$k1) continue;
                $codes2Str = $v1.$codeSplit.$v2;
                $codes2Arr[] = $codes2Str;
                foreach ($tmpArr3 as $k3=>$v3){
                    if($k3==$k1 OR $k3==$k2) continue;
                    $codes3Str = $codes2Str.$codeSplit.$v3;
                    $codes3Arr[] = $codes3Str;
                    foreach ($tmpArr4 as $k4=>$v4){
                        if($k4==$k1 OR $k4==$k2 OR $k4==$k3) continue;
                        $codes4Str = $codes3Str.$codeSplit.$v4;
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
        foreach ($codes4Arr as $k=>$v){
            $tmpCodesArr[] = implode('', $v);
        }
        $tmpCodesArr = array_unique($tmpCodesArr);
        $codesArr = [];
        foreach ($tmpCodesArr as $k=>$v){
            $codesArr = array_merge($codesArr, NumService::getAllCombination4($v));
        }

        return $codesArr;
    }


    /**
     * @desc 快选功能过滤
     * @param $codes_hz
     * @param $type 1一定2二定3三定4四定
     * @return array
     */
    public static function getCodesKuaiXuan($codes_hz, $code_type = 4) {
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
        # 5、两兄弟
        if(isset($codes_hz['type_2b'])){
            $where = array_merge($where, [['=', 'type_2b', $codes_hz['type_2b']]]);
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

        # 定位合分 - 三定
        if($code_type == 3 && isset($codes_hz['hefen_pos']) && isset($codes_hz['hefen']) && !empty($codes_hz['hefen_pos']) && !empty($codes_hz['hefen'])){
            $poss = explode(',', $codes_hz['hefen_pos']);

            $lenHefen = strlen($codes_hz['hefen']);
            $codes_hefen = [];
            for ($i=0; $i<$lenHefen; $i++){
                if($codes_hz['hefen'][$i]<=7){
                    $hefenArr = [$codes_hz['hefen'][$i], $codes_hz['hefen'][$i] + 10, $codes_hz['hefen'][$i] + 20];
                }else{
                    $hefenArr = [$codes_hz['hefen'][$i], $codes_hz['hefen'][$i] + 10];
                }
                $codes_hefen = array_merge($codes_hefen, $hefenArr);
            }
            $codes_str = '';
            foreach ($poss as $pos){
                $codes_str .= '`code_'.$pos.'`' . ' +';
                $where = array_merge($where, [['<>', 'code_'.$pos, 'X']]);
            }
            $codes_str = rtrim(trim($codes_str), '+');
            $where = array_merge($where, [ ['IN', '('.$codes_str.')', $codes_hefen ] ]);
            //$query->andWhere($andWhere);
        }

        # 不定位合分(1两数、2三数) - 三定
        //if($code_type == 3 && !empty($codes_hz['no_fix_hefen']) && !empty($codes_hz['no_fix_hefen_pos'])){
        if(!empty($codes_hz['no_fix_hefen']) && !empty($codes_hz['no_fix_hefen_pos'])){

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

            if($codes_hz['no_fix_hefen_pos'] == 1){ # 两数合分
                $tmp_no_fix_hefen = ['OR'];
                $poss = [[1,2], [1,3], [1,4],[2,3],[2,4],[3,4]];
                if($code_type == 3){ # 三定
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
                }elseif($code_type == 4){ # 四定
                    foreach ($poss as $pos){ # ['IN', SUM(code_1 + code2),[1,11,21]]
                        $son_where = [ ['IN', '(`code_'.$pos[0].'` + `code_'.$pos[1].'`)', $codes_no_fix_hefen] ];
                        $tmp_no_fix_hefen = array_merge($tmp_no_fix_hefen, $son_where);
                    }
                }
            }elseif($codes_hz['no_fix_hefen_pos'] == 2){ # 三数合分
                if($code_type == 3) { # 三定
                    $tmp_no_fix_hefen = ['IN', 'codes_hz', $codes_no_fix_hefen];
                }elseif ($code_type == 4){
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

        # 合分 - 四定
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

        # 三定、四定除、取
        if($code_type == 3 && !empty($codes_hz['arise_in']) && in_array($codes_hz['arise_in_sel'], [1, 2])){
            $lenAriseIn = strlen($codes_hz['arise_in']);
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
        if(isset($codes_hz['p1']) && !empty($codes_hz['p1'])){
            //$p1_codes = explode(',', $codes_hz['p1']);
            $p1_codes = self::getCodesArrByStr($codes_hz['p1']);
            $where = array_merge($where, [ ['IN', 'code_1', $p1_codes] ]);
        }
        # 第2位
        if(isset($codes_hz['p2']) && !empty($codes_hz['p2'])){
            $p2_codes = self::getCodesArrByStr($codes_hz['p2']);
            $where = array_merge($where, [ ['IN', 'code_2', $p2_codes] ]);
        }
        # 第3位
        if(isset($codes_hz['p3']) && !empty($codes_hz['p3'])){
            $p3_codes = self::getCodesArrByStr($codes_hz['p3']);
            $where = array_merge($where, [ ['IN', 'code_3', $p3_codes] ]);
        }
        # 第4位
        if(isset($codes_hz['p4']) && !empty($codes_hz['p4'])){
            $p4_codes = self::getCodesArrByStr($codes_hz['p4']);
            $where = array_merge($where, [ ['IN', 'code_4', $p4_codes] ]);
        }

        # 同时选择取、除四单四双
        if( isset($codes_hz['type_4d']) OR isset($codes_hz['type_4s'])){

            if(($codes_hz['type_4s'] == 0 && $codes_hz['type_4d'] == 0) OR ($codes_hz['type_4s'] == 1 && $codes_hz['type_4d'] == 1) ){
                # 同时除、取四单四双
                if($codes_hz['type_4s'] == 0 && $codes_hz['type_4d'] == 0){
                    # 除
                    $where = array_merge($where, [['=', 'type_4ds', 0]]);
                }else{
                    # 取
                    $where = array_merge($where, [['IN', 'type_4ds', [1,2]]]);
                }

            } else {
                # 四单
                if (isset($codes_hz['type_4d']) && !empty($codes_hz['type_4d'])) {
                    if ($codes_hz['type_4d'] == 1) {
                        # 取
                        $where = array_merge($where, [['=', 'type_4ds', 1]]);
                    } else {
                        # 除
                        $where = array_merge($where, [['IN', 'type_4ds', [0, 2]]]);
                    }
                }
                # 四双
                if (isset($codes_hz['type_4s']) && !empty($codes_hz['type_4s'])) {
                    if ($codes_hz['type_4s'] == 1) {
                        # 取
                        $where = array_merge($where, [['=', 'type_4ds', 2]]);
                    } else {
                        # 除
                        $where = array_merge($where, [['IN', 'type_4ds', [0, 1]]]);
                    }
                }
            }
        }

        # tz_type:28 三现:双重+两兄弟
        if(($codes_hz['get_types'] && in_array(1, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(1, $codes_hz['remove_types']) )){
            if(in_array(1, $codes_hz['get_types'])){
                $type_3n_2b = 1;
            }else{
                $type_3n_2b = 0;
            }
            $where = array_merge($where, [['=', 'type_3n_2b', $type_3n_2b]]);
        }
        # tz_type:28 双重
        if(($codes_hz['get_types'] && in_array(2, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(2, $codes_hz['remove_types']) )){
            if(in_array(2, $codes_hz['get_types'])){
                $type_2 = 1;
            }else{
                $type_2 = 0;
            }
            $where = array_merge($where, [['=', 'type_2', $type_2]]);
        }
        # tz_type:28 三重
        if(($codes_hz['get_types'] && in_array(3, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(3, $codes_hz['remove_types']) )){
            if(in_array(3, $codes_hz['get_types'])){
                $type_3 = 1;
            }else{
                $type_3 = 0;
            }
            $where = array_merge($where, [['=', 'type_3', $type_3]]);
        }
        # tz_type:28 四重
        if(($codes_hz['get_types'] && in_array(4, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(4, $codes_hz['remove_types']) )){
            if(in_array(4, $codes_hz['get_types'])){
                $type_4 = 1;
            }else{
                $type_4 = 0;
            }
            $where = array_merge($where, [['=', 'type_4', $type_4]]);
        }
        # tz_type:28 两兄弟
        if(($codes_hz['get_types'] && in_array(5, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(5, $codes_hz['remove_types']) )){
            if(in_array(5, $codes_hz['get_types'])){
                $type_2b = 1;
            }else{
                $type_2b = 0;
            }
            $where = array_merge($where, [['=', 'type_2b', $type_2b]]);
        }
        # tz_type:28 三兄弟
        if(($codes_hz['get_types'] && in_array(6, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(6, $codes_hz['remove_types']) )){
            if(in_array(6, $codes_hz['get_types'])){
                $type_3b = 1;
            }else{
                $type_3b = 0;
            }
            $where = array_merge($where, [['=', 'type_3b', $type_3b]]);
        }

        # tz_type:28 双双重
        if(($codes_hz['get_types'] && in_array(7, $codes_hz['get_types'])) OR ($codes_hz['remove_types'] && in_array(7, $codes_hz['remove_types']) )){
            if(in_array(7, $codes_hz['get_types'])){
                $type_22 = 1;
            }else{
                $type_22 = 0;
            }
            $where = array_merge($where, [['=', 'type_22', $type_22]]);
        }

        # 对数
        if(isset($codes_hz['type_log'])){
            $where = array_merge($where, [['=', 'type_log', $codes_hz['type_log']]]);
        }

        $get = \Yii::$app->request->get();
        if($get['t'] == 1)p($where);
        $codesArr = [];
        if($code_type == 4 OR $code_type == 3){
            $Num4Types = Num4Type::find()->where($where)->asArray()->all();
            $codesArr = ArrayHelper::getColumn($Num4Types, 'code');
        }

         # 上奖
        //if(isset($codes_hz['arise']) && !empty($codes_hz['arise'])){
        if(isset($codes_hz['arise'])){
            $asises = explode(',', $codes_hz['arise']);
            $codesArr_arise = self::getCodesArise($asises, $type = 1, $code_type);
            if(in_array($code_type, [3, 4])){
                $codesArr = array_intersect($codesArr, $codesArr_arise);
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

        return $datas;
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
            $codes_Arr[] = $codes_str[$i];
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
            if($hz_Arr['type_2'] == 1) $filter1['type_2'] = 1; else $filter0['type_22'] = 0;
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
        # 7、四兄弟
        if(isset($hz_Arr['type_4b'])){
            if($hz_Arr['type_4b'] == 1) $filter1['type_4b'] = 1; else $filter0['type_4b'] = 0;
        }
        //p([$filter0, $filter1]);
        # 7、对数
        if(isset($hz_Arr['type_log'])){
            if($hz_Arr['type_log'] == 1) $filter1['type_log'] = 1; else $filter0['type_log'] = 0;
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
        # 11.1、类型取 - 新
        if(isset($hz_Arr['get_types']) OR isset($hz_Arr['get_types'])){
            if(isset($hz_Arr['get_types'])) $filter6['get_types'] = $hz_Arr['get_types'];// else $filter0['arise'] = 0;
        }
        # 11.2、类型除 - 新
        if(isset($hz_Arr['remove_types']) OR isset($hz_Arr['remove_types'])){
            if(isset($hz_Arr['remove_types'])) $filter7['remove_types'] = $hz_Arr['remove_types'];
        }

        # 合分 - 三定
        if(isset($hz_Arr['hefen_pos']) && isset($hz_Arr['hefen'])){
            if(isset($hz_Arr['hefen_pos']) && isset($hz_Arr['hefen'])) $filter8['hefen'] = 1; else $filter9['hefen'] = 0;
        }
        # 合分 - 四定
        if(isset($hz_Arr['hefen'])){
            if(isset($hz_Arr['hefen'])) $filter8['hefen'] = 1; else $filter9['hefen'] = 0;
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

        if(!empty($hz_Arr['p1'])){
            $desc .= '千'.$hz_Arr['p1'];
        }
        if(!empty($hz_Arr['p2'])){
            $desc .= ' 百'.$hz_Arr['p2'];
        }
        if(!empty($hz_Arr['p3'])){
            $desc .= ' 十'.$hz_Arr['p3'];
        }
        if(!empty($hz_Arr['p4'])){
            $desc .= ' 个'.$hz_Arr['p4'];
        }

        if(!empty($filter8)){
            if(isset($hz_Arr['hefen_pos'])){
                $desc .= ' 合分取[位:'.$hz_Arr['hefen_pos'] . ' 合分:'.$hz_Arr['hefen'].']';
            }else{
                $desc .= ' 合分取[位:1,2,3,4 '. '合分:'.$hz_Arr['hefen'].']';
            }
        }

        # 上奖取
        if(!empty($filter3)){
            $desc .= ' 上奖:';
            foreach ($filter3 as $key3=>$v3){
                $desc .= $v3.'、';
            }
            $desc = trim($desc, '、').' ';
        }
        # 上奖除
        if(!empty($filter4)){
            $desc .= '上奖除:';
            foreach ($filter4 as $key4=>$v4){
                $desc .= $v4.'、';
            }
            $desc = trim($desc, '、').' ';
        }

        if(!empty($filter0)){
            $desc .= ' 除:';
            foreach ($filter0 as $key0=>$v0){
                $desc .= $typesArr[$key0].'、';
            }
            $desc = trim($desc, '、').' ';
        }
        # 类型取
        if(!empty($filter6) OR !empty($filter7)){
            $codeTypes = UserSysPlansService::getCodeTypes();
            if(!empty($filter6)){
                $desc .= '类型取:';
                foreach ($filter6['get_types'] as $key6=>$v6){
                    $desc .= $codeTypes[$v6].'、';
                }
                $desc = trim($desc, '、').' ';
            }
            # 上奖除
            if(!empty($filter7)){
                $desc .= '类型除:';
                foreach ($filter7['remove_types'] as $key7=>$v7){
                    $desc .= $codeTypes[$v7].'、';
                }
                $desc = trim($desc, '、').' ';
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
            'type_22'=>'双双重',
            'type_2b'=>'两兄弟',
            'type_3b'=>'三兄弟',
            'type_4b'=>'四兄弟',
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







}