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
        $BetRows = \backend\models\wechat\Bets::find()->where($where)->limit(1)->all();

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
        $counts = array_count_values($betCodes); # 所有元素的次数

        $kjCode3 = str_replace(',','', $kjCode);
        $count = $counts[$kjCode3] ?? 0; # 中奖次数，防止相同的号码，下注时候出现多次
        if($count>0){
            # 中奖
            $status = self::STATUS_LT_SUCCESS;
            $bonus = round($Odds['odds'] * $betRow->single, 2); # 奖金赔率 * 下注金额
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
        $logArr = ['status'=>$status, 'bonus'=>$bonus, 'Odds'=>$Odds, 'count'=>$count, 'betCodes'=>$betCodes, 'kjCode'=>$kjCode, 'betRecord'=>$betRow->getAttributes()];
        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '直选开奖处理', $logArr);

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

        $count = $betCounts[$kj_code_3n] ?? 0; # 中奖次数，防止相同的号码，下注时候出现多次

        if($count>0){
            # 中奖
            $status = self::STATUS_LT_SUCCESS;
            $bonus = round($Odds['odds'] * $betRow->single, 2); # 奖金赔率 * 下注金额
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
        $logArr = ['status'=>$status, 'bouns'=>$bonus, 'Odds'=>$Odds, 'betCounts'=>$betCounts, 'count'=>$count, 'betCodes'=>$betCodes, 'kjCode'=>$kjCode, 'kj_code_3n'=>$kj_code_3n, 'betRecord'=>$betRow->getAttributes()];
        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '直选开奖处理', $logArr);

        return true;
    }
}
