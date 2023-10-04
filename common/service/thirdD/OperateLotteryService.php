<?php

namespace common\service\thirdD;

use backend\models\SscKjData;
use backend\service\OpKjService;
use common\models\thirdD\BetOrderId;
use common\service\CommonService;
use common\service\helpers\ThirdD;
use common\tools\Tool_Common;

class OperateLotteryService extends CommonBaseService
{

    private static function runWhere($lottery_type=DEFAULT_LOTTERY_TYPE, $qihao=''){
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
     * @return bool
     */
    public static function operate($lottery_type=DEFAULT_LOTTERY_TYPE, $qihao=''){

        $where = OperateLotteryService::runWhere($lottery_type, $qihao);
        #$where = ['id'=>369]; # 测试
        $BetRows = \backend\models\wechat\Bets::find()->where($where)->limit(1)->all();
        if(empty($BetRows)){
            throw_info('记录为空');
        }

        if(empty($qihao)){
            $qihao = $BetRows[0]->qihao;
        }
        $kjCode = CommonService::getAwardNumberByQihao($qihao, $lottery_type); // 3,4,5,6,7
        if(empty($kjCode)){
            return false;
        }

        $kjCode = trim(substr($kjCode, 0, 5));
        $kjCode = $kjCode[0].','.$kjCode[2].','.$kjCode[4];
        foreach ($BetRows as $betRow){
            $method_id = $betRow->play_method;
            try {
                switch ($method_id){
                    case MethodMatchService::METHOD_ID_ZHIXUAN:
                        OperateLotteryService::runZhiXuan($betRow, $kjCode);
                        break;
                    case MethodMatchService::METHOD_ID_ZULIU: # 组六
                    case MethodMatchService::METHOD_ID_ZUSAN: # 组三
                        OperateLotteryService::runZuXuan($betRow, $kjCode, $betRow->lottery_type);
                        break;
                    case MethodMatchService::METHOD_ID_DUDAN: # 独胆
                        OperateLotteryService::runDuDan($betRow, $kjCode, $betRow->lottery_type);
                        break;
                    case MethodMatchService::METHOD_ID_SHUANGFEN: # 双飞
                    case MethodMatchService::METHOD_ID_QUANTUO: # 对子全拖
                        OperateLotteryService::runShuangFen($betRow, $kjCode, $betRow->lottery_type);
                        break;
                    default:
                        Tool_Common::log('/eyun/'.__FUNCTION__, 'ERR', '开奖处理异常0', ['lottery_type'=>$lottery_type, 'betRowId'=>$betRow->id, 'err_msg'=>'位置玩法ID:'.$method_id]);
                        break;
                }

            }catch (\Exception $e){
                Tool_Common::log('/eyun/'.__FUNCTION__, 'ERR', '开奖处理异常1', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
            }
        }

        return true;
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
        if($zjCount>0){
            # 中奖
            $status = self::STATUS_LT_SUCCESS;
            $bonus = round($Odds['odds'] * $betRow->single, 2) * $zjCount; # 奖金赔率 * 下注金额
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
        $betRow->setAttributes($updateDatas);
        $flag = $betRow->save();
        if(empty($flag)){
            throw_info($betRow->getErrors());
        }
        $logArr = ['status'=>$status, 'bonus'=>$bonus, 'Odds'=>$Odds, 'zjCount'=>$zjCount, 'betCodes'=>$betCodes, 'kjCode'=>$kjCode, 'betRecord'=>$betRow->getAttributes()];
        $playMethod = \common\service\CommonService::getPlayMethods()[$betRow->play_method];
        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', $playMethod.'-开奖处理', $logArr);

        return true;
    }

    /**
     * 组选：组三、组六
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runZuXuan(object $betRow, $kjCode='', $lottery_type=26){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        $codes = $betRow->codes;
        $betCodes = explode(MethodMatchService::ZU_SPLIT_FLAG, trim($codes)); # 下注号码

        $kjCodeArr = explode(',', $kjCode);
        $kj_code_3n = CommonService::get3n($kjCodeArr, $lottery_type)[0]; # 开奖所有排序三字现，可用于组三、组六判断
        $betCounts = array_count_values($betCodes); # 所有元素的次数
        #p([$kjCodeArr, $code_3n, $betCodes, $counts]);

        $zjCount = $betCounts[$kj_code_3n] ?? 0; # 中奖次数，防止相同的号码，下注时候出现多次

        if($zjCount>0){
            # 中奖
            $status = self::STATUS_LT_SUCCESS;
            $bonus = round($Odds['odds'] * $betRow->single, 2) * $zjCount; # 奖金赔率 * 下注金额
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
            throw_info($betRow->getErrors());
        }
        $logArr = ['status'=>$status, 'bouns'=>$bonus, 'Odds'=>$Odds, 'betCounts'=>$betCounts, 'zjCount'=>$zjCount, 'betCodes'=>$betCodes, 'kjCode'=>$kjCode, 'kj_code_3n'=>$kj_code_3n, 'betRecord'=>$betRow->getAttributes()];
        $playMethod = \common\service\CommonService::getPlayMethods()[$betRow->play_method];
        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', $playMethod.'-开奖处理', $logArr);

        return true;
    }

    /**
     * 独胆
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runDuDan(object $betRow, $kjCode='', $lottery_type=26){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
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

        if($zjCount>0){
            # 中奖
            $status = self::STATUS_LT_SUCCESS;
            $bonus = round($Odds['odds'] * $betRow->single, 2) * $zjCount; # 奖金赔率 * 下注金额
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
            throw_info($betRow->getErrors());
        }
        $logArr = ['status'=>$status, 'bouns'=>$bonus, 'Odds'=>$Odds, 'zjCount'=>$zjCount, 'betCodes'=>$betCodes, 'kjCode'=>$kjCode, 'kj_code_3n'=>$kj_code_3n, 'betRecord'=>$betRow->getAttributes()];
        $playMethod = \common\service\CommonService::getPlayMethods()[$betRow->play_method];
        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', $playMethod.'-开奖处理', $logArr);

        return true;
    }

    /**
     * 双飞
     * @param object $row
     * @param string $kjCode 2,3,4
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function runShuangFen(object $betRow, $kjCode='', $lottery_type=26){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
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
            $zjCount += $betCount[$code];
        }
        #p(['kj_code_2n'=>$kj_code_2n, 'betCodes'=>$betCodes, 'zjCount'=>$zjCount, 'betCounts'=>$betCount]);

        if($zjCount>0){
            # 中奖
            $status = self::STATUS_LT_SUCCESS;
            $bonus = round($Odds['odds'] * $betRow->single, 2) * $zjCount; # 奖金赔率 * 下注金额
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
            throw_info($betRow->getErrors());
        }
        $logArr = ['status'=>$status, 'bouns'=>$bonus, 'Odds'=>$Odds, 'zjCount'=>$zjCount, 'betCodes'=>$betCodes, 'kjCode'=>$kjCode, 'kj_code_2n'=>$kj_code_2n, 'betRecord'=>$betRow->getAttributes()];
        $playMethod = \common\service\CommonService::getPlayMethods()[$betRow->play_method];
        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', $playMethod.'-开奖处理', $logArr);

        return true;
    }
}
