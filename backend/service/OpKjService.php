<?php

/**
 * Created by PhpStorm.
 *   
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\BettingRecords;
use backend\models\SscKjData;
use backend\models\User;
use backend\models\UserFollowData;
use common\service\CommonService;
use common\service\jobs\statics_3d\UserDayStaticsJobs;
use common\service\lottery\aozhou5\AoZhou5Service;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\OperateLotteryService;
use common\service\wechat\WechatUserService;
use common\tools\Tool_Common;
use  yii;

class OpKjService extends BaseService {

    /**
     * @description 处理开奖数据
     * 投注方式，具体见 CommonService::getOdds( ) 方法
     * @param integer $lottery_type - 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     * @return array
     */
    public static function opSscKjData(int $lottery_type = DEFAULT_LOTTERY_TYPE): array
    {
        $rst = ['status'=>200, 'msg'=>'开奖数据处理完成!'];
        switch (true){
            case in_array($lottery_type, CommonBaseService::THIRDD_LOTTERY_TYPES):
                list($code, $data, $msg) = OperateLotteryService::operate($lottery_type);  # 3D 处理3D下注记录
                $tmpData = ['code'=>$code, 'data'=>$data, 'msg'=>$msg];
                $rst['data'][] = $tmpData;
                Tool_Common::log('/data_kj/'.__FUNCTION__, 'INFO', '开奖后计算用户数据入列00', ['lottery_type'=>$lottery_type, 'tmpData'=>$tmpData]);
                $rst = ['code'=>$code, 'data'=>$data, 'msg'=>$msg];
                break;
            case $lottery_type == CommonBaseService::LOTTERY_TYPE_AOZHOU5:
                AoZhou5Service::afterKj();
                break;
            default:
                $bettingRecords = BettingRecords::find()
                    ->where(['status'=>0, 'lottery_type'=>$lottery_type, 'is_batch_simulate'=>0])
                    ->orderBy('id DESC'); //->limit(100)->all();
                //if(!$bettingRecords) return $rst;
                foreach ($bettingRecords->each(20) as $BettingRecord){
                    $rst['data'][$BettingRecord->id] = OpKjService::opOneBettingRecord($BettingRecord->id, $BettingRecord);
                }
                break;
        }
        Tool_Common::log('/data_kj/'.__FUNCTION__, 'INFO', '开奖后计算用户数据-5x', ['lottery_type'=>$lottery_type, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @desc 操作单个下注记录开奖处理
     * @param string $record_id
     * @return array
     */
    public static function opOneBettingRecord($record_id='', $BettingRecord=''){
        $rst = ['status'=>200, 'msg'=>'处理成功'];

        if(empty($BettingRecord)){
            $BettingRecord = BettingRecords::findOne($record_id);
        }
        if(empty($BettingRecord)){
            $rst = ['status'=>404, 'msg'=>'找不到记录BettingRecords'];
        }else{
            if($BettingRecord->status == 1){
                return ['status'=>304, 'msg'=>'已经处理的记录'];
            }

            try {
                Tool_Common::log('/kj_data/'.__FUNCTION__,'INFO','开奖处理-开始', ['record_id'=>$record_id, 'msg'=>'开始处理开奖']);
                $is_simulate = $BettingRecord->is_simulate;
                $qihao = $BettingRecord->qihao;
                $single = $BettingRecord->single;
                $playway = $BettingRecord->playway;
                $codes = $BettingRecord->codes;

                # 开奖数据
                $kjData = CommonService::getAwardNumberByQihao($qihao, $BettingRecord->lottery_type); // 3,4,5,6,7
                if(!$kjData){
                    return $rst = ['status'=>300, 'msg'=>$qihao.'期未开奖!'];
                }

                $profitsData = self::calcProfits($playway, $codes, $kjData, $single, $BettingRecord->uid);

                $bonus = $profitsData['bonus'];
                $profits = $bonus - $BettingRecord['betting_money'];

                $updateData = [
                    'bonus' => $bonus,
                    'profits' => $profits,
                    'kj_codes' => $kjData,
                    'updated_at' => time(),
                    'status' => 1
                ];
                $BettingRecord->setAttributes($updateData);
                if(!$BettingRecord->save()){
                    throw_info(yii\helpers\Json::encode($BettingRecord->getErrors()));
                }
                Tool_Common::log('/kj_data/'.__FUNCTION__,'INFO','开奖处理结束', ['record_id'=>$record_id, 'msg'=>'开始处理开奖']);
            }catch (\Exception $exception){
                Tool_Common::log('/kj_data/'.__FUNCTION__,'ERR','投注记录-处理失败', ['record_id'=>$record_id, 'err_msg'=>$exception->getMessage(), 'file'=>$exception->getFile().'_'.$exception->getLine()]);
                $rst = ['status'=>302, 'msg'=>$exception->getMessage(), 'file'=>$exception->getFile().'_'.$exception->getLine()];
            }
        }

        return $rst;
    }

    /**
     * @desc 投注号码变更
     * @param string $account
     * @param int $playway
     * @param int $is_simulate 是否是模拟投注
     * @return array
     */
    public static function changeTzCodes($recently_qihao = '',$account = 'gaozi2017', $playway = 1, $is_simulate = 1){
        $rst = ['status'=>200, 'msg'=>'号码变更处理完成!'];
        $UserFollowData = UserFollowData::findOne(['account'=>$account,'playway'=>$playway, 'is_simulate'=>$is_simulate]);
        //p(['account'=>$account,'playway'=>$playway, 'is_simulate'=>$is_simulate,$UserFollowData]);
        $reference_codes = $UserFollowData->reference_codes;
        $newTzCodes = self::getBestTzCodes($recently_qihao,$UserFollowData->position,$reference_codes);
        $UserFollowData->code = $newTzCodes['newTzCodes'];
        $UserFollowData->codes_hezhi = $newTzCodes['hezhi'];
        $UserFollowData->single = 0.2;
        $UserFollowDataUpStatus = $UserFollowData->save(false);
        $logArr = [
            'recently_qihao'=>$recently_qihao,
            'playway'=>$playway,
            'account'=>$account,
            'is_simulate'=>$is_simulate,
            'position'=>$UserFollowData->position,
            'reference_codes'=>$reference_codes,
            'newTzCodes'=>$newTzCodes,
            'UserFollowDataUpStatus'=>$UserFollowDataUpStatus
        ];
        Tool_Common::log('opChangTzCodes','INFO','0898投注记录', $logArr);

        if(!$UserFollowDataUpStatus)
            $rst = ['status'=>300, 'msg'=>'号码变更异常'.current($UserFollowData->getErrors())];

        return $rst;
    }

    /**
     * @desc 返回最佳投注二字定号码
     * @param string $qihao
     * @param string $position
     * @param string $reference_codes
     * @return array|bool
     */
    public static function getBestTzCodes($qihao = '',$position = '1,3', $reference_codes = '8,9,10,11,13'){
        if(!$qihao) return false;
        $rst = [];
        //$zuHes = [ '1,2', '1,3', '1,4', '2,3', '2,4', '3,4' ]; // 目前暂时支持这几种定位组合投注
        $zuHe = explode(',',$position); // array, 1、3位：[1,3]
        $fields = 'code_'.str_replace(',','_',$position);
        if(!$reference_codes)
            $heZhis = [8,9,10,11,12,13];
        else
            $heZhis = explode(',',$reference_codes);

        $heZhi_huizong = BaseNumService::getHeZhiByPositionTotal(120,$zuHe,$heZhis)['data']; // 在近xxx期期间和值汇总
        $mixNums = end($heZhi_huizong)[$fields]; // 120期出现概率最小的

        $kjData = SscKjData::findOne(['qihao'=>$qihao]);
        $recentlyHeZhi = $kjData->$fields;

        $heZhi_yilou = BaseNumService::getHeZhiYL($zuHe,$heZhis)['data']; // 和值为8、9在200期里边遗漏期数
        arsort($heZhi_yilou);
        $unsetArr = [$mixNums, $recentlyHeZhi];
        self::unsetArrEle($heZhis,$unsetArr);

        # 去除遗漏最大的数 start #
        if(count($heZhis) > 2){
            $max =  max($heZhi_yilou);
            $maxKey = array_search($max, $heZhi_yilou);
            $min =  min($heZhi_yilou);
            $minKey = array_search($min, $heZhi_yilou);
            $unsetArr = [$maxKey,$minKey];
            self::unsetArrEle($heZhis,$unsetArr);
        }
        # 去除遗漏最大的数 end #

        $maxZhi =  current($heZhis);

        $is_rand = 0;
        if(!$maxZhi){
            $is_rand = 1;
            $maxZhi = rand(8,12);
        }
        $newTzCodes = BaseNumService::dwZuHe($zuHe,[$maxZhi]); // 某两个位置组合 ，dwZuHe() 这个方法做成分析得出结果
        $logArr = [
            'unsetArr'=>$unsetArr,
            'kjData'=>$kjData->code_str,
            'qihao'=>$qihao,
            'zuHe'=>$zuHe,
            'fields'=>$fields,
            'maxZhi'=>$maxZhi,
            'is_rand'=>$is_rand,
            'newTzCodes'=>$newTzCodes
        ];
        Tool_Common::log('getBestTzCodes','INFO','获取最佳投注号码', $logArr);

        return ['postion'=>$position,'hezhi'=>$maxZhi,'newTzCodes'=>$newTzCodes];
    }

    /**
     * @desc 计算开奖利润数据 2019-05-04
     * @param integer $playway
     * @param string $codes 格式： 01234,01234,56789,56789@01234,01234,56789,X
     * @param string $kjData 格式：3,6,3,3,5
     * @return array
     */
    public static function calcProfits($playway, $codes, $kjData = '', $single = 0.1, $uid=''){
        if(!$kjData) return [];
        $rstData = [];
        $zjTimes = 0;
        # 开奖数据 start

        //$fun = 'opKjData'.$bettingRecord['playway']; // opKjData1、opKjData4、opKjData10
        switch ($playway){
            case 1: // 二字定
            case 2: // 三字定
                /*
                $kjData_n = substr($kjData, 0,7); // 开奖截取前4位号码
                $zjResult = OpKjService::opKjData1($codes, $kjData_n);
                break;
                */
            case 3: // 四字定
                //$kjData_n = substr($kjData, 0,7); // 开奖截取前4位号码
                $kjData_n = $kjData; // 开奖截取前4位号码
                $zjTimes = OpKjService::opKjData4($codes, $kjData_n);
                break;
            case 10:
            case 4:
                $zjTimes = OpKjService::opKjData10($codes, $kjData, $groupSplit = '@', $codeSplit = ',',$nullCode = '');
                break;
            default:;
        }
        $betting_money = SscDataService::calTzTotalMoney($codes, $single, $playway);

        $odds = CommonService::getOdds($playway, $uid);
        $bonus = $odds * $single * $zjTimes;
        $rstData = array_merge($rstData, [
            # 投注号码
            'codes' => $codes,
            # 开奖号码
            'kjCodes' => $kjData,
            # 投注金额
            'betting_money' => $betting_money,
            # 中奖金额 = 赔率 * 倍数 * 注数
            'bonus' => $bonus,
            # 利润 = 中奖金额 - 投注金额
            'profits' => $bonus - $betting_money,
            'zjTimes' => $zjTimes,
        ]);

        //Tool_Common::log('/kj/'.__FUNCTION__, 'INFO', '开奖处理', ['uid'=>$uid, 'playway'=>$playway, 'odds'=>$odds, 'betting_money'=>$betting_money, 'profits'=>$profits, 'bouns'=>$bouns]);

        return $rstData;
    }

    /**
     * @description 判断是否中奖，如果中奖则返回中奖金额，二、三、四字定，playway:1
     * @param int $playway  投注方式，具体见 CommonService::getOdds( ) 方法
     * @param string $codes 投注号码 0,X,8,X@0,X,8,X@1,X,7,X@1,X,7,X@2,X,6,X@2,X,6,X@3,X,5,X@3,X,5,X@4,X,4,X
     * @param string $kjData   开奖号码 3,4,5,6,7
     * @param float $single 投注倍数
     * @param int $dw 定位数，默认二字定
     * @return array
     */
    public static function opKjData1($codes = '', $kjData, $groupSplit = '@', $codeSplit=',', $nullCode='X'){
        $rst = ['status'=>200, 'msg'=>'开奖数据处理完成!'];
        $zjTimes = 0;   // 中奖倍数、次数
        $tzCodes = CommonService::genDw($codes, $groupSplit, $nullCode);
        foreach ($tzCodes as $tzCode){
            $tmpKeys = [];
            $tmpKjData = explode($codeSplit,$kjData);
            # 1、去除空号码的字符
            foreach ($tzCode as $key=>$code){
                if($code == $nullCode){
                    $tmpKeys[] = $key;
                }
            }

            # 2、开奖号码临时处理上面去除的key
            foreach ($tmpKeys as $key){
                $tmpKjData[$key] = $nullCode;
            }

            # 3、匹配上面两步的号码，相等则中奖
            if($tzCode == $tmpKjData){
                $zjTimes += 1;
            }
        }
        $rst['data'] = ['zjTimes'=>$zjTimes];

        return $rst;
    }

    /**
     * @desc 开奖处理，主要判断四字定位
     * @param string $codes 格式： 01234,01234,56789,56789@01234,01234,56789,X
     * @param $kjData
     * @return int
     */
    public static function opKjData4($codes = '', $kjData = '', $zhuSplit = '@'): int
    {
        $tzCodesArr = self::explodeCodes($codes);
        $kjCodesArr = explode(',', $kjData);
        $zjTimes = 0;
        foreach ($tzCodesArr as $codesArr){
            $flag = 1;
            foreach ($codesArr as $key=>$code){
                if($code == 'X') continue;
                if(strpos($code, $kjCodesArr[$key]) === false){
                    $flag = 0;
                }
            }
            if($flag) $zjTimes += 1;
        }
        return $zjTimes;
    }

    /**
     * @desc 拆分投注号码，str -> array (01234,01234,56789,56789、01234,01234,56789,56789@01234,01234,56789,X)
     * @param $codes
     * @param $split
     * @return array
     */
    public static function explodeCodes($codes, $split = ',', $zhuSplit = '@'){
        $codesArr = [];

        $codesArr0 = explode($zhuSplit, $codes);
        foreach ($codesArr0 as $key=>$codes){
            $codesArr[$key] = explode($split, $codes);
        }

        return $codesArr;
    }

    /**@desc 删除数组中某个元素
     * @param $array
     * @param array $unsetEle
     * @return bool
     */
    public static function unsetArrEle(&$array, $unsetEle = []){
        if(!$array OR !$unsetEle) return false;

        foreach ($unsetEle as $key=>$ele){
            $key = array_search($ele, $array);
            unset($array[$key]);
        }
    }

    /**
     * @description 判断是否中奖，如果中奖则返回中奖金额，定位胆，playway:10
     * @param $codes - 投注号码 0,X,8,X@0,X,8,X@1,X,7,X@1,X,7,X@2,X,6,X@2,X,6,X@3,X,5,X@3,X,5,X@4,X,4,X
     * @param $kjData - 开奖号码 3,4,5,6,7
     * @param $groupSplit
     * @param $codeSplit
     * @param $nullCode
     * @return int
     */
    public static function opKjData10($codes = '', $kjData, $groupSplit = '@', $codeSplit=',', $nullCode='X'): int
    {
        $zjTimes = 0;   // 中奖倍数、次数
        $tzCodes = CommonService::genDw10($codes, $groupSplit, $codeSplit, $nullCode);
        foreach ($tzCodes as $tzCode){
            $tmpKeys = [];
            $tmpKjData = explode($codeSplit,$kjData);

            //p([$tzCodes,$tmpKjData,$codeSplit,$kjData]);
            # 1、去除空号码的字符
            foreach ($tzCode as $key=>$code){
                if($code == $nullCode){
                    $tmpKeys[] = $key;
                }
            }

            # 2、开奖号码临时处理上面去除的key
            foreach ($tmpKeys as $key){
                $tmpKjData[$key] = $nullCode;
            }

            # 3、匹配上面两步的号码，相等则中奖
            if($tzCode == $tmpKjData){
                $zjTimes += 1;
            }
        }
        return $zjTimes;
    }

}
