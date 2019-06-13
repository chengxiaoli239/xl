<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\SscKjData;
use backend\models\SscKjData3num;
use backend\models\SscKjDataDs;
use backend\models\ThreeNum;
use common\tools\Tool_Common;
use  yii;

class BaseNumService extends BaseService {

    /**
     * @description 和值生成，默认两位数和值
     * @param int $hezhi
     * @param int $w 几字定
     * @return array
     */
    public static function heZhi($hezhi = 8, $w = 2){
        $numsArr = [];
        $mixNums = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ];
        $maxNums = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ];
        if($w == 2){
            foreach ($mixNums as $mixNum){
                foreach ($maxNums as $maxNum){
                    $tmpNums = [];
                    $he = $mixNum + $maxNum;
                    if($he == $hezhi){
                        if($mixNum < $maxNum){
                            $tmpNums = [$mixNum, $maxNum];
                        }else{
                            $tmpNums = [$maxNum, $mixNum];
                        }
                    }else continue;

                    $numsArr[] = array_values($tmpNums);
                }
            }
            if($hezhi%2 == 0){
                $num = $hezhi/2;
                $numsArr[] = [$num, $num];
            }
        }
        $tmpArrs = $numsArr;
        //array_unique($numsArr);
        foreach ($numsArr as $key=>$arr){
            $i = 0;
            foreach ($tmpArrs as $tmpKey=>$tmpArr){
                if($tmpKey<$key) continue;
                if($tmpArr == $arr){
                    $i++;
                    if($i>1){
                        unset($numsArr[$tmpKey]);
                        $i=0;
                    }
                }
            }
        }

        return array_values($numsArr);
    }

    /**
     * @desc 获取多个和值数组
     * @param $heZhiArr [8,9] 和值数组
     * @return array
     */
    public static function getHeZhiArr($heZhiArr){
        $numsArr = [];

        foreach ($heZhiArr as $zhi){
            $nums = BaseNumService::heZhi($zhi);
            $numsArr[$zhi] = $nums;
        }

        return $numsArr;
    }

    /**
     * @description 两字定位的组合数据
     * @param int $zu
     * @param $heZhiArr 和值数组
     * @param string $numSplit
     * @param string $zhuSplit
     * @return array
     */
    public static function dwZuHe($zu = [1,4], $heZhiArr = [8], $numSplit='X', $zhuSplit = '@', $count = 5){
        if($zu[0] > $zu[1]) return false;
        $numsArr = [];
        $start = $zu[0];
        $end = $zu[1];

        $codeStr = '';
        $numsArr = BaseNumService::getHeZhiArr($heZhiArr);
        foreach ($numsArr as $nums){
            $codeArr = self::getInitCodeArr($numSplit, $count); // 例如: $codeArr = [',', ',', ',', ',', ',', ','];
            //p([$numsArr,$codeArr]);
            foreach ($nums as $Arr){
                $codeArr[$start-1] = $Arr[0];
                $codeArr[$end-1] = $Arr[1];
                $codeStr .= implode(',', $codeArr).$zhuSplit;

                if($Arr[0] != $Arr[1]){
                    $codeArr = self::getInitCodeArr($numSplit, $count); // 例如: $codeArr = [',', ',', ',', ',', ',', ','];
                    $codeArr[$start-1] = $Arr[1];
                    $codeArr[$end-1] = $Arr[0];
                    $codeStr .= implode(',', $codeArr).$zhuSplit;
                }
            }
        }

        //$codeStr = str_replace(',,',','.$n.',',trim($codeStr, $zhuSplit));
        $codeStr = trim($codeStr, $zhuSplit);
        return $codeStr;
    }

    /**
     * @desc 四定位单双号码组装
     * @param int $p_1
     * @param int $p_2
     * @param int $p_3
     * @param int $p_4
     * @param string $zhuSplit
     * @return string
     */
    public static function dw4ZuHe($p_1 = 2, $p_2 = 2, $p_3 = 2, $p_4 = 2,$zhuSplit = ','){
        # 0:全 1:大 2:小 3:单 4:双   对应底下键值
        $codesArr = [ '', '56789', '01234', '13579', '02468'];
        $codes = [ $codesArr[$p_1], $codesArr[$p_2], $codesArr[$p_3], $codesArr[$p_4] ];
        foreach ($codes as $key=>$code){
            $codes[$key] = $code ? $code : 'X';
        }

        $strCodes = implode($zhuSplit, $codes);
        return $strCodes;
    }
    /**
     * @description 生成多个提供的字符串
     * @param string $numSplit
     * @param $count
     * @return array
     */
    public static function getInitCodeArr($numSplit = ',', $count){
        $codeArr = [];
        for ($i = 1; $i < $count; $i++){
            $codeArr[] = $numSplit;
        }

        return $codeArr;
    }

    public static function startAndEndNumHeZhi($qihao){
        $nums = SscKjData::findOne(['qihao'=>$qihao]);
        p($nums);
    }

    /**
     * @desc 计算某一期某两个为的和值
     * @param $qihao
     * @param int $start
     * @param int $end
     * @return mixed
     */
    public static function heZhiByPosition($qihao, $start = 0, $end = 4){
        $nums = SscKjData::findOne(['qihao'=>$qihao]);
        $kj_codes = $nums->kj_code;
        return $kj_codes[$start] + $kj_codes[$end];
    }

    /**
     * @desc 计算前多少期区间数据包含和值，应用场景：二字定投注参考
     * @param string $interval
     * @param int $start
     * @param int $end
     * @return array
     * { "id": "16018", "kj_code": "05588", "code1": "0", "code2": "5", "code3": "5", "code4": "8", "code5": "8", "qihao": "180513079", "date": "2018-05-13", "update_time": "2018-05-13 19:10:51", "heZhi": "13" },
     * { "id": "16017", "kj_code": "12744", "code1": "1", "code2": "2", "code3": "7", "code4": "4", "code5": "4", "qihao": "180513078", "date": "2018-05-13", "update_time": "2018-05-13 19:00:52", "heZhi": "6" }
     */
    public static function getHeZhiByPosition($interval = '300', $start = 1, $end = 5){
        $msg = ['status'=>200, 'msg'=>'操作成功！'];
        $SscKjData = SscKjData::find()->select(" *,(code".$start." + code".$end.") AS heZhi " )
            ->limit($interval)->orderBy('id DESC')->asArray()->all();


        $msg['data'] = $SscKjData;
        return $msg;
    }
    /**
     * @desc 计算前多少期区间两个位置的和值汇总，应用场景：二字定
     * @param string $interval
     * @param int $start
     * @param int $end
     * @return array
     */
    public static function getHeZhiByPositionTotal($interval = '300', $zuhe = [1, 5], $heZhi = []){
        $msg = ['status'=>200, 'msg'=>'操作成功！'];
        $start = $zuhe[0];
        $end = $zuhe[1];

        $last = SscKjData::find()->select(['max(id) as last_id'])->asArray()->one();
        $max_id = $last['last_id'] - $interval;
        $field = 'code_'.$start.'_'.$end;
        $counts = SscKjData::find()->select($field.',COUNT(id) as nums')->where('id>'.$max_id)->groupBy($field)->orderBy('nums DESC')->asArray()->all();
        if($heZhi){
            foreach ($counts as $key=>$count){
                if(!in_array($count[$field], $heZhi)){
                    unset($counts[$key]);
                }
            }
        }

        $msg['data'] = $counts;
        return $msg;
    }

    /**
     * @description 在某两个位置和值为给定的值当前期遗漏的期数
     * @param string $interval
     * @param int $start
     * @param int $end
     * @param array $heZhi
     * @return array
     */
    public static function getDwHeZhiCurrentYL($zu = [1,5], $heZhi = [], $interval = '30'){
        if(empty($heZhi))
            $heZhi = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18];
        $msg = ['status'=>200, 'msg'=>'操作成功！'];
        $start = $zu[0];
        $end = $zu[1];

        $SscKjData = SscKjData::find()->select(" *,(code".$start." + code".$end.") AS heZhi " )
            ->limit($interval)->orderBy('id DESC')->asArray()->all();

        $counts = [];
        foreach ($heZhi as $zhi){
            $counts[$zhi] = 0;
        }
        foreach ($counts as $key=>$zhi){
            foreach ($SscKjData as $k=>$data){
                if($key != $data['heZhi']){
                    $counts[$key] += 1;
                }else{
                    break;
                }
            }
        }
        $msg['data'] = $counts;
        return $msg;
    }

    /**
     * @description 在某两个位置单双为给定的值当前期遗漏的期数
     * @param string $interval
     * @param int $start
     * @param int $end
     * @param array $heZhi
     * @return array
     */
    public static function getDwDsCurrentYL($zu = [1,4], $zhis = [], $interval = '50'){

        $msg = ['status'=>200, 'msg'=>'操作成功！'];

        $codeField = 'code_'.implode('_',$zu);
        $field = $codeField;
        $SscKjDsDatas = SscKjDataDs::find()->select($field)->limit($interval)->orderBy('id DESC')->asArray()->all();

        $counts = [];
        foreach ($zhis as $zhi){
            $counts[$zhi] = 0;
        }
        foreach ($counts as $key=>$zhi){
            foreach ($SscKjDsDatas as $k=>$sscKjDsData){
                if($key != $sscKjDsData[$field]){
                    $counts[$key] += 1;
                }else{
                    break;
                }
            }
        }
        $msg['data'] = $counts;
        return $msg;
    }

    /**
     * @description 获取一组号码（二字定、三字定、四字定）单双遗漏
     * @param $codes
     * @return int
     */
    public static function getCodesYL($codes, $playway = 2, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $tmpArr = explode(',',$codes);
        //p([$codes, $playway]);
        $p_1 = SscDataService::justDataSingleOrDouble($tmpArr[0]);
        $p_2 = SscDataService::justDataSingleOrDouble($tmpArr[1]);
        $p_3 = SscDataService::justDataSingleOrDouble($tmpArr[2]);
        $p_4 = SscDataService::justDataSingleOrDouble($tmpArr[3]);
        $positionArr = [ 1=>$p_1, 2=>$p_2, 3=>$p_3, 4=>$p_4 ];
        $positionArr = array_filter($positionArr);
        switch ($playway){
            case 2: # 三字定
                foreach ($positionArr as $k=>$p){
                    if(!in_array($p, [3,4])) return '';
                    $tmpZhi[] =  ($p % 2 == 0) ? 2 : 1;
                }
                $keys = array_keys($positionArr);
                $position = implode(',',$keys);
                break;
            case 3: # 四字定
                $position = '1,2,3,4';
                if(in_array($p_1,[3,4]) && in_array($p_2,[3,4]) && in_array($p_3,[3,4]) && in_array($p_4,[3,4])){
                    $tmpZhi[] =  ($p_1 % 2 == 0) ? 2 : 1;
                    $tmpZhi[] =  ($p_2 % 2 == 0) ? 2 : 1;
                    $tmpZhi[] =  ($p_3 % 2 == 0) ? 2 : 1;
                    $tmpZhi[] =  ($p_4 % 2 == 0) ? 2 : 1;
                }
                break;

        }
        if($tmpZhi)
            $zhi = implode('',$tmpZhi);
        if($zhi){
            $current_miss = \backend\models\SscDsYl::findOne(['positions'=>$position,'zhi'=>$zhi, 'lottery_type'=>$lottery_type])['current_miss'];
        }

        return $current_miss ? $current_miss : '';
    }

    /**
     * @description 某三字现为给定的值当前期遗漏的期数
     * @param string $interval
     * @param int $start
     * @param int $end
     * @param array $heZhi
     * @return array
     */
    public static function get3NCurrentYL($zhis = [], $interval = '1000'){

        $msg = ['status'=>200, 'msg'=>'操作成功！'];

        $field = 'qihao,code_str,code_3n';
        $SscKjData3Nums = SscKjData3Num::find()->select($field)->limit($interval)->orderBy('id DESC')->asArray()->all();
        //p($SscKjData3Nums);

        $counts = [];
        foreach ($zhis as $zhi){
            $counts[$zhi] = 0;
        }
        foreach ($counts as $key=>$zhi){
            foreach ($SscKjData3Nums as $k=>$SscKjData3Num){
                $code_3n = $SscKjData3Num['code_3n'];
                if(!$code_3n){
                    $counts[$key] += 1;
                }else{
                    $flag = strpos($key, $code_3n);
                    if($flag !== false){
                        break;
                    }else{
                        $counts[$key] += 1;
                    }
                }
            }
        }
        $msg['data'] = $counts;
        return $msg;
    }

    /**
     * @desc 返回所有三字现组合
     * @return static[]
     */
    public static function getAll3Num(){
        $mcKey =  'CODE_ALL_3NUM';
        $m = \Yii::$app->cache;
        if(!$zuHes = $m->get($mcKey)){
            $ThreeNums = ThreeNum::find()->all();
            $zuHes = [];
            foreach ($ThreeNums as $key=>$threeNum){
                $zuHes[$key+1] = $threeNum->code;
            }
        }

        return $zuHes;
    }

    /**
     * @description 获取往后几期的期数
     * @param $qishu
     */
    public static function getAfterQihaos($qishu){
        $newQihao = HN0898Service::getQihao();
        $qh =  substr($newQihao, 6, 3);
        $date = substr($newQihao, 0, 6);
        $qihaos = [$newQihao,];
        for ($i = 1; $i<$qishu; $i++){
            $qh = $qh + 1;
            if($qh > 120){
                $date = $date + 1;
                $qh = '001';
            }
            $qihao = sprintf("%03d", $qh);
            $qihaos[] = $date.$qihao;
        }

        return implode(',',$qihaos);
    }

    public static function getThreeNumYL($codes, $interval = '120'){
        $codesArr = [$codes[0],$codes[1],$codes[2]];
        sort($codesArr);
        $SscKjData = SscKjData::find()->select('kj_code') ->limit($interval)->orderBy('id DESC')->asArray()->all();
        p($SscKjData);
        foreach ($SscKjData as $key=>$kjData){

        }
        p($codesArr);
    }

    /**
     * @desc 获取三字现，双重加一码、不含三重
     * @return array
     */
    public static function getRepeat3Codes($repeat = 0){
        $repeatCodes = ['00', '11', '22', '33', '44', '55', '66', '77', '88', '99']; # 双重数组
        $singleCodes = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $threeCodesArr = [];
        foreach ($repeatCodes as $repeatCode){
            foreach ($singleCodes as $singleCode){
                $flag = strstr($repeatCode, $singleCode) !== false;
                if(!$repeat && $flag){
                    continue;
                }
                $tmp3Code = $repeatCode.$singleCode;
                $tmp3CodeArr = [$tmp3Code[0],$tmp3Code[1],$tmp3Code[2]];
                sort($tmp3CodeArr);
                $threeCodesArr[] = implode('', $tmp3CodeArr);
            }
        }
        $codesArr = $threeCodesArr;
        sort($codesArr);

        return $codesArr;
    }

    /**
     * @desc 获取四字现，双重加两码、不含三重
     * @return array
     */
    public static function getRepeat4Codes($repeat = 0){
        $all3Codes = self::getAll3Num();
        $singleCodes = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $threeCodesArr = [];
        foreach ($all3Codes as $all3Code){
            foreach ($singleCodes as $singleCode){
                //p([$all3Code, $singleCode]);
                $flag = strstr($all3Code, $singleCode) !== false;
                if($repeat && $flag){
                    # 重复（带双重）
                    $tmp3Code = $all3Code.$singleCode;
                }elseif(!$repeat && !$flag){
                    # 不带双重
                    $tmp3Code = $all3Code.$singleCode;
                }else{
                    continue;
                }
                $tmp3CodeArr = [$tmp3Code[0], $tmp3Code[1], $tmp3Code[2], $tmp3Code[3]];
                sort($tmp3CodeArr);
                $threeCodesArr[] = implode('', $tmp3CodeArr);
            }
        }
        $codesArr = $threeCodesArr;
        $codesArr = array_unique($codesArr);
        sort($codesArr);

        return $codesArr;
    }

    /**
     * @desc 获取四字现
     * @return array
     */
    public static function get4Codes(){
        $codesArr = [];

        return $codesArr;
    }

}