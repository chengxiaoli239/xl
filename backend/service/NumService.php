<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
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

                $all4NumCodes = Num4Type::find()->select(['code AS code_str'])->orderBy(['id'=>SORT_DESC])->asArray()->all();
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
        $where = ['codes_hz'=>$heZhiArr, 'type_2b'=>1, 'type_3'=>0, 'type_4b'=>0];
        $Num4Types = Num4Type::find()->where($where);
        $codes = $Num4Types->asArray()->all();
        $codesArr = ArrayHelper::getColumn($codes, 'code');
        # 和值范围 end
        $m->set($mkey, $codesArr, 7*24*3600);

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
            $codes = Num4Type::find()->select(['code'])->where($where)->all();
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
     * @desc 上奖 - 返回匹配含号码的组合 -- 已完完 2019-04-22
     * @param array $codesArr
     */
    public static function getCodesArise($codesArr = [], $isUnique = 1){

        $codes4Arr = [];
        # 去除双重数字
        foreach ($codesArr as $key=>$codes){
            $len = strlen($codes);
            if($len == 1){
                $codesArrTmp = NumService::getAllCombination1($codes);
                # 一个码
            }elseif ($len == 2){
                # 两个码
                $codesArrTmp = NumService::getAllCombination2($codes);
            }elseif ($len == 3){
                # 三个码
                $codesArrTmp = NumService::getAllCombination3($codes);
            }elseif ($len == 4){
                # 四个码 - 全倒
                $codesArrTmp = NumService::getAllCombination4($codes);
            }elseif($len > 4){
                # 大于四个码
                $codesArrTmp = NumService::getAllCombination4p($codes);
            }
            $codes4Arr = array_merge($codes4Arr, $codesArrTmp);
        }
        if($isUnique){
            $codes4Arr = array_unique($codes4Arr);
        }

        return $codes4Arr;
    }

    /**
     * @desc 1个号码返回组号码组合 - 全倒
     * @param $codes 格式：1或者2
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination1($codes){
        if(strlen($codes) != 1) return [];

        $where = ['like', 'code', $codes];
        //p($where);
        $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
        $codesArr = ArrayHelper::getColumn($Num4Types, 'code');

        return array_unique($codesArr);
    }

    /**
     * @desc 2个号码返回组号码组合 - 全倒
     * @param $codes 格式：11或者12
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination2($codes){
        if(strlen($codes) != 2) return [];

        $where = [
            'OR',
            ['like', 'code', '%'.$codes[0].','.$codes[1].'%', false],
            ['like', 'code', '%'.$codes[0].'%'.$codes[1].'%', false],

            ['like', 'code', '%'.$codes[1].','.$codes[0].'%', false],
            ['like', 'code', '%'.$codes[1].'%'.$codes[0].'%', false],
        ];
        //p($where);
        $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
        $codesArr = ArrayHelper::getColumn($Num4Types, 'code');

        return array_unique($codesArr);
    }

    /**
     * @desc 3个号码返回组号码组合 - 全倒
     * @param $codes 格式：111或者112或者123
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination3($codes){
        if(strlen($codes) != 3) return [];

        $where = [
            'OR',
            ['like', 'code', '%'.$codes[0].','.$codes[1].','.$codes[2].'%', false],
            ['like', 'code', $codes[0].'%'.$codes[1].','.$codes[2], false],
            ['like', 'code', $codes[0].','.$codes[1].'%'.$codes[2], false],

            ['like', 'code', '%'.$codes[0].','.$codes[2].','.$codes[1].'%', false],
            ['like', 'code', $codes[0].'%'.$codes[2].','.$codes[1], false],
            ['like', 'code', $codes[0].','.$codes[2].'%'.$codes[1], false],

            ['like', 'code', '%'.$codes[1].','.$codes[0].','.$codes[2].'%', false],
            ['like', 'code', $codes[1].'%'.$codes[0].','.$codes[2], false],
            ['like', 'code', $codes[1].','.$codes[0].'%'.$codes[2], false],

            ['like', 'code', '%'.$codes[1].','.$codes[2].','.$codes[0].'%', false],
            ['like', 'code', $codes[1].'%'.$codes[2].','.$codes[0], false],
            ['like', 'code', $codes[1].','.$codes[2].'%'.$codes[0], false],

            ['like', 'code', '%'.$codes[2].','.$codes[0].','.$codes[1].'%', false],
            ['like', 'code', $codes[2].'%'.$codes[0].','.$codes[1], false],
            ['like', 'code', $codes[2].','.$codes[0].'%'.$codes[1], false],

            ['like', 'code', '%'.$codes[2].','.$codes[1].','.$codes[0].'%', false],
            ['like', 'code', $codes[2].'%'.$codes[1].','.$codes[0], false],
            ['like', 'code', $codes[2].','.$codes[1].'%'.$codes[0], false],
        ];
        //p($where);
        $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
        $codesArr = ArrayHelper::getColumn($Num4Types, 'code');

        return array_unique($codesArr);
    }

    /**
     * @desc 4个号码返回24组号码组合
     * @param $codes 格式：1123或者1112或者1122或者1234
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination4($codes){
        if(strlen($codes) != 4) return [];
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

        return array_unique($codesArr);
    }

    /**
     * @desc 大于4个号码返回四定号码组合号码
     * @param $codes 格式：11234或者11123或者11223或者12345或者123456
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination4p($codes, $codeSplit = ''){
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
}