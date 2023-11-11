<?php

namespace common\service\thirdD;

use backend\models\SscKjData;
use backend\service\agent\AgentUsersBalanceService;
use backend\service\OpKjService;
use common\models\thirdD\BetOrderId;
use common\service\CommonService;
use common\service\helpers\ThirdD;
use common\service\wechat\WechatUserService;
use common\tools\Tool_Common;
use yii\helpers\Json;

class OperateLotteryService extends CommonBaseService
{

    private static function runWhere($lottery_type=DEFAULT_LOTTERY_TYPE, $qihao=''): array
    {
        $where = [
            'AND',
            ['=', 'lottery_type', $lottery_type],
            ['=', 'status', self::STATUS_LT_WAIT],
        ];
        if(!empty($qihao)){
            $where[] = ['=', 'qihao', $qihao];
        }

        return $where;
    }

    /**
     * @param int $lottery_type
     * @param string $qihao
     * @return array
     */
    public static function operate(int $lottery_type=DEFAULT_LOTTERY_TYPE, string $qihao=''): array
    {
        try {
            $where = OperateLotteryService::runWhere($lottery_type, $qihao);
            #$where = ['id'=>[384,384]]; # 测试
            $BetRowsQuery = \backend\models\wechat\Bets::find()->where($where)->limit(100);
            $sql = $BetRowsQuery->createCommand()->getRawSql();
            $BetRows = $BetRowsQuery->all();
            if(empty($BetRows)){
                throw_info('记录为空');
            }
            #p(['BetRows'=>$BetRows]);
            Tool_Common::log('/data_kj/'.__FUNCTION__, 'INFO', '开奖计算00', ['lottery_type'=>$lottery_type, 'counts'=>count($BetRows), 'sql'=>$sql]);

            $idData = [];
            foreach ($BetRows as $betRow){
                list($code, $data, $msg) = OperateLotteryService::operateOne($betRow);
                Tool_Common::log('/data_kj/'.__FUNCTION__, 'INFO', '开奖计算01', ['code'=>$code, 'data'=>$data, 'msg'=>$msg]);
                if($code==0){
                    $idData[] = $data['idData'];
                }
            }
        }catch (\Exception $e){
            return [10001, ['lottery_type'=>$lottery_type], $e->getMessage()];
        }
        $idData = array_unique($idData);
        Tool_Common::log('/data_kj/'.__FUNCTION__, 'INFO', '开奖处理结束', ['lottery_type'=>$lottery_type, 'idData'=>$idData]);

        return [0, ['lottery_type'=>$lottery_type, 'idData'=>$idData], '处理成功'];
    }

    public static function operateOne(object $betRow): array
    {
        $qh = $betRow->qihao;
        $method_id = $betRow->play_method;
        $lottery_type = $betRow->lottery_type;
        $code_str = trim(CommonService::getAwardNumberByQihao($qh, $lottery_type)); // 3,4,5,6,7
        if(empty($code_str)){
            $msg = '未开奖：lottery_type:'.$lottery_type.'_qihao:'.$qh;
            $logArr = ['betRowId'=>$betRow->id, 'lottery_type'=>$lottery_type, 'qihao'=>$qh, 'method_id'=>$method_id, 'msg'=>$msg];
            Tool_Common::log('/data_kj/'.__FUNCTION__, 'INFO', '开奖计算21', $logArr);
            return [10002, $logArr, $msg];
        }
        $kjCode = $code_str[0].','.$code_str[2].','.$code_str[4]; // 3,4,5
        #$kjCode = '4,1,2'; # 测试

        $idData = ['wechat_user_id'=>$betRow->wechat_user_id, 'user_id'=>$betRow->user_id];
        Tool_Common::log('/data_kj/'.__FUNCTION__, 'INFO', '开奖计算22', ['betRowId'=>$betRow->id, 'lottery_type'=>$lottery_type, 'qihao'=>$qh, 'kjCode'=>$kjCode, 'method_id'=>$method_id]);
        //p($method_id);
        try {
            switch ($method_id){
                case MethodMatchService::METHOD_ID_ZHIXUAN:
                    OperateLotteryService::runZhiXuan($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_ZULIU: # 组六
                case MethodMatchService::METHOD_ID_ZUSAN: # 组三
                    OperateLotteryService::runZuXuan($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_DUDAN: # 独胆
                    OperateLotteryService::runDuDan($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_SHUANGFEN: # 双飞
                case MethodMatchService::METHOD_ID_QUANTUO: # 对子全拖
                    OperateLotteryService::runShuangFen($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_YIMADING: # 一码定
                    OperateLotteryService::runYiMaDing($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_ERMADING: # 二码定
                    OperateLotteryService::runErMaDing($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_BAOZI_QB: # 豹子全包
                    OperateLotteryService::runBaoZiQB($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_ZL_4_MA: # 组六4码
                case MethodMatchService::METHOD_ID_ZL_5_MA: # 组六5码
                case MethodMatchService::METHOD_ID_ZL_6_MA: # 组六6码
                case MethodMatchService::METHOD_ID_ZL_7_MA: # 组六7码
                case MethodMatchService::METHOD_ID_ZL_8_MA: # 组六8码
                case MethodMatchService::METHOD_ID_ZL_9_MA: # 组六9码
                    OperateLotteryService::runZuLiuXMa($betRow, $kjCode); # 组选x码
                    break;
                case MethodMatchService::METHOD_ID_ZS_2_MA: # 组三2码
                case MethodMatchService::METHOD_ID_ZS_3_MA: # 组三2码
                case MethodMatchService::METHOD_ID_ZS_4_MA: # 组三4码
                case MethodMatchService::METHOD_ID_ZS_5_MA: # 组三5码
                case MethodMatchService::METHOD_ID_ZS_6_MA: # 组三6码
                case MethodMatchService::METHOD_ID_ZS_7_MA: # 组三7码
                case MethodMatchService::METHOD_ID_ZS_8_MA: # 组三8码
                case MethodMatchService::METHOD_ID_ZS_9_MA: # 组三9码
                    OperateLotteryService::runZuSanXMa($betRow, $kjCode); # 组选x码
                    break;
                case MethodMatchService::METHOD_ID_ZL_QB: # 组六全包
                case MethodMatchService::METHOD_ID_ZS_QB: # 组三全包
                    OperateLotteryService::runZuXuanQuanBao($betRow, $kjCode); # 组选x码
                    break;
                case MethodMatchService::METHOD_ID_KD_0: # 跨度0
                case MethodMatchService::METHOD_ID_KD_1: # 跨度1
                case MethodMatchService::METHOD_ID_KD_2: # 跨度2
                case MethodMatchService::METHOD_ID_KD_3: # 跨度3
                case MethodMatchService::METHOD_ID_KD_4: # 跨度4
                case MethodMatchService::METHOD_ID_KD_5: # 跨度5
                case MethodMatchService::METHOD_ID_KD_6: # 跨度6
                case MethodMatchService::METHOD_ID_KD_7: # 跨度7
                case MethodMatchService::METHOD_ID_KD_8: # 跨度8
                case MethodMatchService::METHOD_ID_KD_9: # 跨度9
                    OperateLotteryService::runKuaDu($betRow, $kjCode); # 跨度
                    break;
                case MethodMatchService::METHOD_ID_YMT_ZL_2: # 一码拖2_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_3: # 一码拖3_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_4: # 一码拖4_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_5: # 一码拖5_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_6: # 一码拖6_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_7: # 一码拖7_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_8: # 一码拖8_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_9: # 一码拖9_组六
                    OperateLotteryService::runYiTuoZuLiu($betRow, $kjCode); # 跨度
                    break;
                case MethodMatchService::METHOD_ID_YMT_ZS_2: # 一码拖2_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_3: # 一码拖3_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_4: # 一码拖4_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_5: # 一码拖5_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_6: # 一码拖6_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_7: # 一码拖7_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_8: # 一码拖8_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_9: # 一码拖9_组三
                    OperateLotteryService::runYiTuoZuSan($betRow, $kjCode); # 跨度
                    break;
                case MethodMatchService::METHOD_ID_FS_3: # 复式三
                case MethodMatchService::METHOD_ID_FS_4: # 复式四
                case MethodMatchService::METHOD_ID_FS_5: # 复式五
                case MethodMatchService::METHOD_ID_FS_6: # 复式六
                case MethodMatchService::METHOD_ID_FS_7: # 复式七
                case MethodMatchService::METHOD_ID_FS_8: # 复式八
                case MethodMatchService::METHOD_ID_FS_9: # 复式九
                    OperateLotteryService::runFuShiX($betRow, $kjCode); # 跨度
                    break;
                case MethodMatchService::METHOD_ID_HZ_0: # 和值0
                case MethodMatchService::METHOD_ID_HZ_1: # 和值1
                case MethodMatchService::METHOD_ID_HZ_2: # 和值2
                case MethodMatchService::METHOD_ID_HZ_3: # 和值3
                case MethodMatchService::METHOD_ID_HZ_4: # 和值4
                case MethodMatchService::METHOD_ID_HZ_5: # 和值5
                case MethodMatchService::METHOD_ID_HZ_6: # 和值6
                case MethodMatchService::METHOD_ID_HZ_7: # 和值7
                case MethodMatchService::METHOD_ID_HZ_8: # 和值8
                case MethodMatchService::METHOD_ID_HZ_9: # 和值9
                case MethodMatchService::METHOD_ID_HZ_10: # 和值10
                case MethodMatchService::METHOD_ID_HZ_11: # 和值11
                case MethodMatchService::METHOD_ID_HZ_12: # 和值12
                case MethodMatchService::METHOD_ID_HZ_13: # 和值13
                case MethodMatchService::METHOD_ID_HZ_14: # 和值14
                case MethodMatchService::METHOD_ID_HZ_15: # 和值15
                case MethodMatchService::METHOD_ID_HZ_16: # 和值16
                case MethodMatchService::METHOD_ID_HZ_17: # 和值17
                case MethodMatchService::METHOD_ID_HZ_18: # 和值18
                case MethodMatchService::METHOD_ID_HZ_19: # 和值19
                case MethodMatchService::METHOD_ID_HZ_20: # 和值10
                case MethodMatchService::METHOD_ID_HZ_21: # 和值21
                case MethodMatchService::METHOD_ID_HZ_22: # 和值22
                case MethodMatchService::METHOD_ID_HZ_23: # 和值23
                case MethodMatchService::METHOD_ID_HZ_24: # 和值24
                case MethodMatchService::METHOD_ID_HZ_25: # 和值25
                case MethodMatchService::METHOD_ID_HZ_26: # 和值26
                case MethodMatchService::METHOD_ID_HZ_27: # 和值27
                    OperateLotteryService::runHeZhi($betRow, $kjCode); # 和值
                    break;
                case MethodMatchService::METHOD_ID_HZ_DA: # 和值大
                case MethodMatchService::METHOD_ID_HZ_XIAO: # 和值小
                case MethodMatchService::METHOD_ID_HZ_DAN: # 和值单
                case MethodMatchService::METHOD_ID_HZ_SHUANG: # 和值双
                    OperateLotteryService::runHeZhiDxDs($betRow, $kjCode); # 和值
                    break;
                case MethodMatchService::METHOD_ID_DW_ZX_FS: # 定位直选复式
                    OperateLotteryService::runHeZhiXuanFuShiDw($betRow, $kjCode); # 直选复式定位
                    break;
                case MethodMatchService::METHOD_ID_QD: # 全倒
                    OperateLotteryService::runQuanDao($betRow, $kjCode); # 全倒
                    break;
                case MethodMatchService::METHOD_ID_ZX_FS: # 直选复式
                    OperateLotteryService::runZhiXuanFuShi($betRow, $kjCode); # 直选复式
                    break;
                default:
                    $err_msg = '未知玩法ID:'.$method_id;
                    $logArr = ['lottery_type'=>$lottery_type, 'betRowId'=>$betRow->id, 'err_msg'=>$err_msg];
                    Tool_Common::log('/data_kj/'.__FUNCTION__, 'ERR', '开奖处理异常10', $logArr);
                    return [10003, $logArr, $err_msg];
                    break;
            }
            $resultData = ['betRowId'=>$betRow->id, 'idData'=>$idData, 'method_id'=>$method_id, 'lottery_type'=>$lottery_type, 'err_msg'=>'处理结束'];
            Tool_Common::log('/data_kj/'.__FUNCTION__, 'ERR', '开奖处理结束99', $resultData);
            var_dump(date('Y-m-d H:i:s ').'处理成功：betRowId:'.$betRow->id.'_method_id:'.$method_id);
        }catch (\Exception $e){
            $logArr = ['betRowId'=>$betRow->id, 'method_id'=>$method_id, 'lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()];
            Tool_Common::log('/data_kj/'.__FUNCTION__, 'ERR', '开奖处理异常11', $logArr);
            var_dump($e->getMessage());
            return [10004, $logArr, $e->getMessage()];
        }
        return [0, $resultData, '处理成功'];
    }

    /**
     * 直选
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runZhiXuan(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $kjCode = trim($kjCode);
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码
        $betCounts = array_count_values($betCodes); # 所有元素的次数

        $kjCode3 = str_replace(',','', $kjCode);
        $zjCount = $betCounts[$kjCode3] ?? 0; # 中奖次数，防止相同的号码，下注时候出现多次

        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);
        return true;
    }

    /**
     * 组选：组三、组六
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runZuXuan(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $lottery_type = $betRow->lottery_type;
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);
        $kj_code_3n = CommonService::get3n($kjCodeArr, $lottery_type)[0]; # 开奖所有排序三字现，可用于组三、组六判断
        $betCounts = array_count_values($betCodes); # 所有元素的次数
        #p([$kjCodeArr, $code_3n, $betCodes, $counts]);

        $zjCount = $betCounts[$kj_code_3n] ?? 0; # 中奖次数，防止相同的号码，下注时候出现多次

        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 独胆
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runDuDan(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $lottery_type = $betRow->lottery_type;
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);
        $kj_code_3n = CommonService::get3n($kjCodeArr, $lottery_type)[0]; # 开奖所有排序三字现，可用于组三、组六判断
        $zjCount = 0;
        foreach ($betCodes as $code){
            if(strpos($kj_code_3n, $code)===false) continue;
            $zjCount++;
        }
        #p(['kjCodeArr'=>$kjCodeArr, 'kj_code_3n'=>$kj_code_3n, 'betCodes'=>$betCodes, 'betCounts'=>$betCounts]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 双飞
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runShuangFen(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $lottery_type = $betRow->lottery_type;
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);
        $kj_code_2n = CommonService::get2n($kjCodeArr, $lottery_type); # 开奖所有排序三字现，可用于组三、组六判断
        $betCount = array_count_values($betCodes); # 所有元素的次数

        $zjCount = 0;
        $betCodes = array_unique($betCodes); # 统计次数之后，去重，防止多次计算中奖
        foreach ($betCodes as $code){
            if(!in_array($code, $kj_code_2n)) continue;
            $zjCount += (int)($betCount[$code]);
        }
        #p(['kj_code_2n'=>$kj_code_2n, 'betCodes'=>$betCodes, 'zjCount'=>$zjCount, 'betCounts'=>$betCount]);

        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 一码定位
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runYiMaDing(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);

        $zjCount = 0;
        $betCodes = array_unique($betCodes); # 统计次数之后，去重，防止多次计算中奖
        foreach ($betCodes as $code){
            if(strpos($code, '百') !== false){
                $posKjCode = $kjCodeArr[0];
                $betCodeStr = str_replace('百:', '', $code);
            }elseif (strpos($code, '十') !== false){
                $posKjCode = $kjCodeArr[1];
                $betCodeStr = str_replace('十:', '', $code);
            }elseif (strpos($code, '个') !== false){
                $posKjCode = $kjCodeArr[2];
                $betCodeStr = str_replace('个:', '', $code);
            }else{
                throw_info('匹配位置异常');
            }
            $betCodesArr = ThirdD::getArrayCodesByString($betCodeStr);
            $betCount = array_count_values($betCodesArr); # 所有元素的次数
            #p(['$betCodesArr'=>$betCodesArr, 'betCount'=>$betCount, 'posKjCode'=>$posKjCode], 0);

            if(!isset($betCount[$posKjCode])) continue;
            $zjCount += (int)($betCount[$posKjCode]);
        }
        #p(['betCodes'=>$betCodes, 'kjCodeArr'=>$kjCodeArr, 'zjCount'=>$zjCount]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 二码定位
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runErMaDing(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);

        $zjCount = 0;
        $betCodes = array_unique($betCodes); # 统计次数之后，去重，防止多次计算中奖
        $allErDingCodes = [];
        foreach ($betCodes as $oneCode){
            $oneCodes = explode(MethodMatchService::CODE_SPLIT_FLAG, $oneCode);

            $betCodesArr = [];
            $posKjCodes = [];
            foreach ($oneCodes as $code){
                if(strpos($code, '百') !== false){
                    $posKjCodes[] = $kjCodeArr[0]; # 235
                    $betCodesArr[] = str_replace('百:', '', $code);
                }elseif (strpos($code, '十') !== false){
                    $posKjCodes[] = $kjCodeArr[1];
                    $betCodesArr[] = str_replace('十:', '', $code);
                }elseif (strpos($code, '个') !== false){
                    $posKjCodes[] = $kjCodeArr[2];
                    $betCodesArr[] = str_replace('个:', '', $code);
                }else{
                    throw_info('匹配位置异常');
                }
            }
            $oneErDingCodeDatas = ThirdD::getArrayCodesByArray($betCodesArr);
            $posKjCode = $posKjCodes[0].$posKjCodes[1]; #二定开奖拼接的开奖号码
            $counts = array_count_values($oneErDingCodeDatas);
            $count = $counts[$posKjCode] ?? 0;

            $zjCount += (int)$count;
            #p(['RowId'=>$betRow->id, 'posKjCode'=>$posKjCode, 'oneErDingCodeDatas'=>$oneErDingCodeDatas, 'counts'=>$counts, 'count'=>$count]);
        }
        #p(['betCodes'=>$betCodes, 'kjCodeArr'=>$kjCodeArr, 'zjCount'=>$zjCount]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 豹子全包
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runBaoZiQB(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率

        $kjCodeArr = explode(',', $kjCode);
        #p(['Odds'=>$Odds, 'codes'=>$codes, 'betCodes'=>$betCodes, 'kjCodeArr'=>$kjCodeArr]);

        $zjCount = ($kjCodeArr[0]==$kjCodeArr[1] && $kjCodeArr[1]==$kjCodeArr[2]) ? 1 : 0;
        #p(['RowId'=>$betRow->id, 'posKjCode'=>$posKjCode, 'oneErDingCodeDatas'=>$oneErDingCodeDatas, 'counts'=>$counts, 'count'=>$count]);
        #p(['betCodes'=>$betCodes, 'kjCodeArr'=>$kjCodeArr, 'zjCount'=>$zjCount]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 组六x码
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runZuLiuXMa(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);
        $flag = \common\service\CommonService::isCodeTypeZxBz($kjCode);

        //p(['Odds'=>$Odds, 'betRow'=>$betRow->getAttributes(), 'codes'=>$codes, 'betCodes'=>$betCodes, 'kjCodeArr'=>$kjCodeArr, 'flag'=>$flag]);

        $zjCount = 0;
        if($flag == MethodMatchService::CODE_TYPE_ZU_LIU){
            $betCodes = array_unique($betCodes); # 统计次数之后，去重，防止多次计算中奖
            foreach ($betCodes as $oneCode){
                #p(['oneCode'=>$oneCode, 'kjCodeArr'=>$kjCodeArr]);
                if(strpos($oneCode, $kjCodeArr[0]) !== false && strpos($oneCode, $kjCodeArr[1])!==false && strpos($oneCode, $kjCodeArr[2])!==false){
                    $zjCount += 1;
                }
                #p(['RowId'=>$betRow->id, 'kjCodeArr'=>$kjCodeArr, 'posKjCode'=>$posKjCode, 'oneErDingCodeDatas'=>$oneErDingCodeDatas, 'counts'=>$counts, 'count'=>$count]);
            }
        }
        #p(['betCodes'=>$betCodes, 'Odds'=>$Odds, 'zjCount'=>$zjCount, 'kjCode'=>$kjCode]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 组三x码
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runZuSanXMa(object $betRow, string $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);

        //p(['Odds'=>$Odds, 'betRow'=>$betRow->getAttributes(), 'codes'=>$codes, 'betCodes'=>$betCodes, 'kjCodeArr'=>$kjCodeArr, 'flag'=>$flag]);
        $flag = \common\service\CommonService::isCodeTypeZxBz($kjCode);

        $zjCount = 0;
        $betCodes = array_unique($betCodes); # 统计次数之后，去重，防止多次计算中奖
        if($flag == MethodMatchService::CODE_TYPE_ZU_SAN) {
            foreach ($betCodes as $oneCode) {
                if (
                    (strpos($oneCode, $kjCodeArr[0]) !== false && strpos($oneCode, $kjCodeArr[1]) !== false && $kjCodeArr[0]!=$kjCodeArr[1]) OR
                    (strpos($oneCode, $kjCodeArr[0]) !== false && strpos($oneCode, $kjCodeArr[2]) !== false && $kjCodeArr[0]!=$kjCodeArr[2]) OR
                    (strpos($oneCode, $kjCodeArr[1]) !== false && strpos($oneCode, $kjCodeArr[2]) !== false && $kjCodeArr[1]!=$kjCodeArr[2])
                ) {
                    $zjCount += 1;
                }
                #p(['RowId' => $betRow->id, 'oneCode' => $oneCode, 'kjCodeArr' => $kjCodeArr, 'zjCount' => $zjCount]);
            }
        }
        #p(['betCodes'=>$betCodes, 'zjCount'=>$zjCount, 'Odds'=>$Odds]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 组三、组六全包
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runZuXuanQuanBao(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率

        #p(['Odds'=>$Odds, 'betRow'=>$betRow->getAttributes(), 'codes'=>$codes, 'betCodes'=>$betCodes, 'kjCodeArr'=>$kjCodeArr, 'flag'=>$flag]);
        $flag = \common\service\CommonService::isCodeTypeZxBz($kjCode);

        $zjCount = 0;
        if(($flag == MethodMatchService::CODE_TYPE_ZU_SAN && $betRow->play_method==MethodMatchService::METHOD_ID_ZS_QB) OR
            ($flag == MethodMatchService::CODE_TYPE_ZU_LIU && $betRow->play_method==MethodMatchService::METHOD_ID_ZL_QB)
        ) {
            $zjCount = 1;
        }
        #p(['betCodes'=>$betCodes, 'zjCount'=>$zjCount]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 跨度
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runKuaDu(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率

        $kd = \common\service\CommonService::getKuaDu($kjCode);
        #p(['Odds'=>$Odds, 'betRow'=>$betRow->getAttributes(), 'kd'=>$kd, 'kjCode'=>$kjCode, 'codes'=>$betRow->codes]);

        $zjCount = 0;
        if($kd == $betRow->codes) {
            $zjCount = 1;
        }
        #p(['betCodes'=>$betCodes, 'zjCount'=>$zjCount]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 一拖x组六
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runYiTuoZuLiu(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);

        $flag = \common\service\CommonService::isCodeTypeZxBz($kjCode);
        #p(['Odds'=>$Odds, 'betRow'=>$betRow->getAttributes(), 'codes'=>$codes, 'betCodes'=>$betCodes, 'kjCodeArr'=>$kjCodeArr, 'flag'=>$flag], 0);

        $zjCount = 0;
        $betCodes = array_unique($betCodes); # 统计次数之后，去重，防止多次计算中奖
        if($flag == MethodMatchService::CODE_TYPE_ZU_LIU) {
            foreach ($betCodes as $oneCode) {
                $f = preg_match_all('/(\d{1})拖(\d{2,})/', $oneCode, $matches);
                if(empty($f)){
                    throw_info('一码拖匹配异常');
                }
                $one = $matches[1][0];
                if(in_array($one, $kjCodeArr)){
                    $tuoMas = $matches[2][0]; # 拖的码
                    $leaveCodes = array_values(array_diff($kjCodeArr, [$one]));  # array_values 重建索引
                    #p(['tuoMas'=>$tuoMas, 'leaveCodes'=>$leaveCodes]);
                    if (strpos($tuoMas, $leaveCodes[0]) !== false && strpos($tuoMas, $leaveCodes[1]) !== false  ) {
                        $zjCount += 1;
                    }
                }
                #p(['RowId' => $betRow->id, 'oneCode' => $oneCode, 'kjCodeArr' => $kjCodeArr, 'zjCount' => $zjCount]);
            }
        }
        #p(['betCodes'=>$betCodes, 'zjCount'=>$zjCount]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 一拖x组三
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runYiTuoZuSan(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);

        $flag = \common\service\CommonService::isCodeTypeZxBz($kjCode);
        #p(['Odds'=>$Odds, 'betRow'=>$betRow->getAttributes(), 'codes'=>$codes, 'betCodes'=>$betCodes, 'kjCodeArr'=>$kjCodeArr, 'flag'=>$flag], 0);

        $zjCount = 0;
        $betCodes = array_unique($betCodes); # 统计次数之后，去重，防止多次计算中奖
        if($flag == MethodMatchService::CODE_TYPE_ZU_SAN) {
            foreach ($betCodes as $oneCode) {
                $f = preg_match_all('/(\d{1})拖(\d{2,})/', $oneCode, $matches);
                if(empty($f)){
                    throw_info('一码拖匹配异常');
                }
                $one = $matches[1][0];
                if(in_array($one, $kjCodeArr)){
                    $tuoMas = $matches[2][0]; # 拖的码
                    $leaveCodes = array_values(array_diff($kjCodeArr, [$one]));  # array_values 重建索引
                    #p(['tuoMas'=>$tuoMas, 'leaveCodes'=>$leaveCodes]);
                    if (strpos($tuoMas, $leaveCodes[0]) !== false) {
                        $zjCount += 1;
                    }
                }
                #p(['RowId' => $betRow->id, 'oneCode' => $oneCode, 'kjCodeArr' => $kjCodeArr, 'zjCount' => $zjCount]);
            }
        }
        #p(['betCodes'=>$betCodes, 'zjCount'=>$zjCount]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 复式三...九
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runFuShiX(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);
        #p(['Odds'=>$Odds, 'betRow'=>$betRow->getAttributes(), 'codes'=>$codes, 'betCodes'=>$betCodes, 'kjCodeArr'=>$kjCodeArr], 0);

        $zjCount = 0;
        $betCodes = array_unique($betCodes); # 统计次数之后，去重，防止多次计算中奖
        foreach ($betCodes as $oneCode) {
            if (strpos($oneCode, $kjCodeArr[0]) !== false && strpos($oneCode, $kjCodeArr[1]) !== false && strpos($oneCode, $kjCodeArr[2]) !== false) {
                $zjCount += 1;
            }
            #p(['RowId' => $betRow->id, 'oneCode' => $oneCode, 'kjCodeArr' => $kjCodeArr, 'zjCount' => $zjCount]);
        }
        #p(['betCodes'=>$betCodes, 'zjCount'=>$zjCount]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 和值0...27
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runHeZhi(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $heZhi = \common\service\CommonService::getHeZhi($kjCode);
        #p(['Odds'=>$Odds, 'betRow'=>$betRow->getAttributes(), 'codes'=>$codes, 'betCodes'=>$betCodes, 'heZhi'=>$heZhi], 0);

        $zjCount = 0;
        $betCodes = array_unique($betCodes); # 统计次数之后，去重，防止多次计算中奖
        foreach ($betCodes as $oneCode) {
            if ($oneCode == $heZhi) {
                $zjCount += 1;
            }
            #p(['RowId' => $betRow->id, 'oneCode' => $oneCode, 'kjCodeArr' => $kjCodeArr, 'zjCount' => $zjCount]);
        }
        #p(['betCodes'=>$betCodes, 'zjCount'=>$zjCount]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 和值大小单双
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runHeZhiDxDs(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $heZhi = \common\service\CommonService::getHeZhi($kjCode);
        #p(['Odds'=>$Odds, 'betRow'=>$betRow->getAttributes(), 'codes'=>$codes, 'betCodes'=>$betCodes, 'heZhi'=>$heZhi], 0);

        $zjCount = 0;
        $betCodes = array_unique($betCodes); # 统计次数之后，去重，防止多次计算中奖
        foreach ($betCodes as $oneCode) {
            if (
                ($oneCode == '大' && 14<=$heZhi && $heZhi<=27) OR
                ($oneCode == '小' && 0<=$heZhi && $heZhi<=13) OR
                ($oneCode == '单' && $heZhi%2==1) OR
                ($oneCode == '双' && $heZhi%2==0)
            ) {
                $zjCount += 1;
            }
            #p(['RowId' => $betRow->id, 'oneCode' => $oneCode, 'kjCodeArr' => $kjCodeArr, 'zjCount' => $zjCount]);
        }
        #p(['betCodes'=>$betCodes, 'zjCount'=>$zjCount]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 直选复式定位
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runHeZhiXuanFuShiDw(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);
        #p(['Odds'=>$Odds, 'betRow'=>$betRow->getAttributes(), 'codes'=>$codes, 'betCodes'=>$betCodes, 'kjCodeArr'=>$kjCodeArr], 0);

        $zjCount = 0;
        $betCodes = array_unique($betCodes); # 统计次数之后，去重，防止多次计算中奖
        foreach ($betCodes as $oneCode) {
            if(preg_match_all('/(?:百|十|个)(\d+)/u', $oneCode, $matches)){
                $flag = 1;
                #p(['matches'=>$matches, 'kjCodeArr'=>$kjCodeArr]);
                foreach ($matches[0] as $k=>$match0){
                    $f1 = (strpos($match0, '百') !== false && strpos($match0, $kjCodeArr[0]) === false);
                    $f2 = (strpos($match0, '十') !== false && strpos($match0, $kjCodeArr[1]) === false);
                    $f3 = (strpos($match0, '个') !== false && strpos($match0, $kjCodeArr[2]) === false);
                    # 只要其中一位匹配不到，则为不中奖 flag=0
                    if( $f1 OR $f2 OR $f3 ){
                        $flag = 0;
                    }
                }
                if ($flag) {
                    $zjCount += 1;
                }
            }
            //p(['RowId' => $betRow->id, 'oneCode' => $oneCode, 'kjCodeArr' => $kjCodeArr, 'zjCount' => $zjCount, 'flag'=>$flag]);
        }
        #p(['betCodes'=>$betCodes, 'zjCount'=>$zjCount]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 全倒
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runQuanDao(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);
        #p(['Odds'=>$Odds, 'betRow'=>$betRow->getAttributes(), 'codes'=>$codes, 'betCodes'=>$betCodes, 'kjCodeArr'=>$kjCodeArr]);

        $zjCount = 0;
        $betCodes = array_unique($betCodes); # 统计次数之后，去重，防止多次计算中奖
        foreach ($betCodes as $oneCode) {
            $flag = 1;
            #p(['matches'=>$matches, 'kjCodeArr'=>$kjCodeArr]);
            $f1 = strpos($oneCode, $kjCodeArr[0]) === false;
            $f2 = strpos($oneCode, $kjCodeArr[1]) === false;
            $f3 = strpos($oneCode, $kjCodeArr[2]) === false;
            # 只要其中一位匹配不到，则为不中奖 flag=0
            if( $f1 OR $f2 OR $f3 ){
                $flag = 0;
            }
            if ($flag) {
                $zjCount += 1;
            }
            //p(['RowId' => $betRow->id, 'oneCode' => $oneCode, 'kjCodeArr' => $kjCodeArr, 'zjCount' => $zjCount, 'flag'=>$flag]);
        }
        #p(['betCodes'=>$betCodes, 'zjCount'=>$zjCount]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 直选复式  跟全倒的匹配逻辑一样
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runZhiXuanFuShi(object $betRow, $kjCode=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);
        #p(['Odds'=>$Odds, 'betRow'=>$betRow->getAttributes(), 'codes'=>$codes, 'betCodes'=>$betCodes, 'kjCodeArr'=>$kjCodeArr]);

        $zjCount = 0;
        $betCodes = array_unique($betCodes); # 统计次数之后，去重，防止多次计算中奖
        foreach ($betCodes as $oneCode) {
            $flag = 1;
            #p(['matches'=>$matches, 'kjCodeArr'=>$kjCodeArr]);
            $f1 = strpos($oneCode, $kjCodeArr[0]) === false;
            $f2 = strpos($oneCode, $kjCodeArr[1]) === false;
            $f3 = strpos($oneCode, $kjCodeArr[2]) === false;
            # 只要其中一位匹配不到，则为不中奖 flag=0
            if( $f1 OR $f2 OR $f3 ){
                $flag = 0;
            }
            if ($flag) {
                $zjCount += 1;
            }
            //p(['RowId' => $betRow->id, 'oneCode' => $oneCode, 'kjCodeArr' => $kjCodeArr, 'zjCount' => $zjCount, 'flag'=>$flag]);
        }
        #p(['betCodes'=>$betCodes, 'zjCount'=>$zjCount]);
        self::endCaculate($betRow, $zjCount, $Odds, $kjCode);

        return true;
    }

    /**
     * 中奖计算结果之后的存表处理
     * @param object $betRow
     * @param int $zjCount
     * @param array $Odds
     * @param string $kjCode
     * @throws \common\exceptions\InfoException
     */
    private static function endCaculate(object $betRow, int $zjCount, array $Odds=[], string $kjCode=''): bool
    {
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            if($zjCount>0){
                # 中奖
                $status = self::STATUS_LT_SUCCESS;
                $bonus = round(($Odds['bouns'] * $betRow->single)/$Odds['money'], 2) * $zjCount; # 奖金赔率 * 下注金额
                //p(['bonus'=>$bonus, 'single'=>$betRow->single, 'money'=>$Odds['money'], 'Odds'=>$Odds]);
            }else{
                # 未中奖
                $status = self::STATUS_LT_FAIL;
                $bonus = 0.00;
            }
            $profits = (float)round($bonus - $betRow->bet_money, 2);
            $updateDatas = [
                'status' => $status,
                'bonus' => $bonus,
                'profits' => $profits,
                'kj_codes' => $kjCode,
                'updated_at' => time(),
            ];
            #p(['id'=>$betRow->id, 'betCodes'=>$betCodes, 'kj_code_3n'=>$kj_code_3n, 'betCounts'=>$betCounts, $updateDatas]);
            $betRow->setAttributes($updateDatas);
            $flag = $betRow->save();
            if(empty($flag)){
                throw_info(Json::encode($betRow->getErrors()));
            }
            if($zjCount>0){
                $vData = AgentUsersBalanceService::updateBalance((string)$betRow->order_id, $bonus, $betRow->wechat_user_id, WechatUserService::TYPE_ORDER_AWARD); # 撤单返还
            }
            $logArr = ['status'=>$status, 'bonus'=>$bonus, 'Odds'=>$Odds, 'zjCount'=>$zjCount, 'kjCode'=>$kjCode, 'vData'=>$vData, 'betRecord'=>$betRow->getAttributes()];
            $playMethod = \common\service\CommonService::getPlayMethods()[$betRow->play_method];
            Tool_Common::log('/data_kj/'.__FUNCTION__, 'INFO', $playMethod.'-开奖处理01', $logArr);
            $transaction->commit();
        }catch (\Exception $e){
            $transaction->rollBack();
            $logArr = ['betRowId'=>$betRow->id, 'zjCount'=>$zjCount, 'kjCode='>$kjCode, 'updateDatas'=>$updateDatas, 'err_msg'=>$e->getMessage()];
            Tool_Common::log('/data_kj/'.__FUNCTION__, 'ERR', $playMethod.'-开奖处理-异常', $logArr);
            return false;
        }

        return true;
    }
}
