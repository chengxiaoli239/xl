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
     * @desc 上奖 - 返回匹配含号码的组合 -- 已完成 2019-04-22
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


    /**
     * @desc 快选功能过滤
     * @param $codes_hz
     * @return array
     */
    public static function getCodesKuaiXuan($codes_hz) {
        //p($codes_hz,0);
        if(empty($codes_hz)) return [];

        $where = ['AND'];
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

        # 四单
        if(isset($codes_hz['type_4d']) && !empty($codes_hz['type_4d'])){
            if($codes_hz['type_4d'] == 1){
                # 取
                $where = array_merge($where, [['=', 'type_4ds', 1]]);
            }else{
                # 除
                $where = array_merge($where, [['IN', 'type_4ds', [0,2]]]);
            }
        }

        # 四双
        if(isset($codes_hz['type_4s']) && !empty($codes_hz['type_4s'])){
            if($codes_hz['type_4s'] == 1){
                # 取
                $where = array_merge($where, [['=', 'type_4ds', 2]]);
            }else{
                # 除
                $where = array_merge($where, [['IN', 'type_4ds', [0,1]]]);
            }
        }

        # 对数
        if(isset($codes_hz['type_log'])){
            $where = array_merge($where, [['=', 'type_log', $codes_hz['type_log']]]);
        }

        $query = Num4Type::find()->where($where);

        $Num4Types = $query->asArray()->all();
        $codesArr = ArrayHelper::getColumn($Num4Types, 'code');

         # 上奖
        if(isset($codes_hz['arise']) && !empty($codes_hz['arise'])){
            //$tmpArise = self::getCodesArrByStr($codes_hz['arise']);

            $codesArr_arise = self::getCodesArise([$codes_hz['arise']]);
            $codesArr = array_intersect($codesArr, $codesArr_arise);
        }
        //p($codesArr);

        return array_unique($codesArr);
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
        # 0、上奖
        if(isset($hz_Arr['arise'])){
            if($hz_Arr['arise']) $filter3['arise'] = $hz_Arr['arise'];// else $filter0['arise'] = 0;
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

        # 8、和值
        if(isset($hz_Arr['hz']) && !empty($hz_Arr['hz'])){
            $filter2['hz'] = implode(',',$hz_Arr['hz']);
        }
        # 9、四单四双
        if(isset($hz_Arr['type_4ds'])){
            if($hz_Arr['type_4ds']) $filter1['type_4ds'] = $hz_Arr['type_4ds']; //else $filter0['type_4ds'] = 0;
        }

        $typesArr = self::getNameByCodesType();
        if(!empty($filter2['hz'])){
            //$desc .= '和值:'.yii\helpers\BaseStringHelper::truncate($filter2['hz'],10).' ';
            $desc .= '和值:'.$filter2['hz'].' ';
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
        if(!empty($filter0)){
            $desc .= '除:';
            foreach ($filter0 as $key0=>$v0){
                $desc .= $typesArr[$key0].'、';
            }
            $desc = trim($desc, '、').' ';
        }
        if(!empty($filter3)){
            $desc .= '上奖:';
            foreach ($filter3 as $key3=>$v3){
                $desc .= $v3.'、';
            }
            $desc = trim($desc, '、').' ';
        }
        if(!empty($hz_Arr['p1'])){
            $desc .= ' p1:'.$hz_Arr['p1'];
        }
        if(!empty($hz_Arr['p2'])){
            $desc .= ' p2:'.$hz_Arr['p2'];
        }
        if(!empty($hz_Arr['p3'])){
            $desc .= ' p3:'.$hz_Arr['p3'];
        }
        if(!empty($hz_Arr['p4'])){
            $desc .= ' p4:'.$hz_Arr['p4'];
        }


        return $desc;
    }

    /**
     * @desc 返回筛选名称
     * @param string $type
     * @return array|mixed
     */
    public static function getNameByCodesType($type = ''){
        $typeArr = [
            'type_2'=>'双重',
            'type_3'=>'三重',
            'type_4'=>'四重',
            'type_22'=>'双双重',
            'type_2b'=>'两兄弟',
            'type_3b'=>'三兄弟',
            'type_4b'=>'四兄弟',
            'type_log'=>'对数',
            'type_4ds_1'=>'四单',
            'type_4ds_2'=>'四双',
            'arise'=>'上奖',
        ];

        if($typeArr[$type]) return $typeArr[$type];

        return $typeArr;
    }

    public static function gendouble3Nums(){

    }









}