<?php

namespace common\service\lottery\aozhou5;

use backend\models\wechat\Bets;
use backend\service\agent\AgentUsersBalanceService;
use common\helpers\lottery\DrawLottery;
use common\helpers\LotteryType;
use common\helpers\SscMethod;
use common\service\CommonService;
use common\service\lottery\CommonLotteryService;
use common\service\thirdD\MethodMatchService;
use common\service\thirdD\Odds3dService;
use common\service\wechat\WechatUserService;
use common\tools\Tool_Common;

class AoZhou5Service extends CommonLotteryService
{
    const KJ_CODE_NUM = 5; # 目前为5个号码，后续可根绝用户需求调整为4或5个
    public static function bet(): bool
    {
        $data = [
            'codes' => '1念3',
            'qihao' => '51086548',
            'lottery_type' => CommonLotteryService::LOTTERY_TYPE_AOZHOU5,
            'lotteryclass' => 'aozhou5',
            'post_desc' => '1念3/50',
            'status' => CommonLotteryService::STATUS_LT_WAIT,
            'createtime' => time(),
            'uid' => '40',
            'tz_system_id' => '18',
            'account' => 'aa33',
        ];
        $Bet = new Bets();
        $Bet->setAttributes($data);
        $result = $Bet->save();

        return $result;
    }

    /**
     * 开奖数据处理
     * @return void
     */
    public static function afterKj()
    {
        try {
            $lottery_type = self::LOTTERY_TYPE_AOZHOU5;
            $bets = Bets::find()->where(['status'=>0, 'lottery_type'=>$lottery_type])
                ->orderBy('id DESC')->limit(100)->all();
            Tool_Common::log('/kj_aozhou5/'.__FUNCTION__, 'INFO', LotteryType::getName($lottery_type).'开奖之后业务处理', ['lottery_type'=>$lottery_type, 'count'=>count($bets)]);
            foreach ($bets as $bet){
                self::opOneBettingRecord($bet->id, $bet);
            }
        }catch (\Exception $e){
            Tool_Common::log('/kj_aozhou5/'.__FUNCTION__, 'ERR', '开奖处理异常', ['lottery_type'=>$lottery_type, 'name'=>LotteryType::TYPE_OPTIONS[$lottery_type], 'err_msg'=>$e->getMessage()]);
        }

    }

    /**
     * 单个计划开奖处理
     * @param int $recordId
     * @param $bet
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function opOneBettingRecord(int $recordId=0, $bet=null): bool
    {
        if(empty($bet)){
            $bet = Bets::findOne($recordId);
        }
        if(empty($bet)){
            throw_info('找不到记录BettingRecords:'.$recordId);
        }
        if($bet->status == 1){
            throw_info('已经处理的记录:'.$recordId);
        }

        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $is_simulate = $bet->is_simulate;
            $qihao = $bet->qihao;
            $single = $bet->single;
            $codes = $bet->codes;
            $lottery_type = $bet->lottery_type;

            # 开奖数据
            if(!$kjData = CommonService::getAwardNumberByQihao($qihao, $lottery_type)){ // 3,4,5,6,7
                throw_info(LotteryType::TYPE_OPTIONS[$lottery_type].$qihao.'期未开奖!');
            }
            $drawData = DrawLottery::getGuiDrawData($kjData, $codeNum=self::KJ_CODE_NUM); # 4个或者5个

            $profitsData = self::calcProfits($codes, $drawData, $single, $bet->user_id);
            //p(['qihao'=>$qihao, 'kjData'=>$kjData, 'drawData'=>$drawData, 'codes'=>$codes, 'id'=>$bet->id, 'playmethod'=>$bet->play_method]);

            $bonus = $profitsData['bonus'];
            $profits = $bonus - $bet['bet_money'];
            $zjResult = $profitsData['zjResult'];

            $updateData = [
                'bonus' => $bonus,
                'profits' => $profits,
                'kj_codes' => $kjData,
                'updated_at' => time(),
                'status' => 1
            ];
            //p($updateData);
            $bet->setAttributes($updateData);
            $status = $bet->save();
            $logArr = [
                'qihao'=>$qihao,
                'opRst'=>$status,'playway'=>LotteryType::TYPE_OPTIONS,'codes'=>$codes,'is_simulate'=>$is_simulate,
                'kjData'=>$kjData, 'single'=>$single,'zjResult'=>$zjResult,'bonus'=>$bonus, 'profits'=>$profits,
            ];

            if($bonus>0){
                AgentUsersBalanceService::updateBalance((string)$bet->order_id, $bonus, $bet->wechat_user_id, WechatUserService::TYPE_ORDER_AWARD); # 派奖
            }

            #Tool_Common::log('opSscKjData','INFO','投注记录', $logArr);
            $transaction->commit();
        }catch (\Exception $e){
            $transaction->rollBack();
            Tool_Common::log('/kj_aozhou5/'.__FUNCTION__,'ERR','投注记录-处理失败', ['record_id'=>$recordId, 'err_msg'=>$e->getMessage(), 'file'=>$e->getFile().'_'.$e->getLine()]);
            return false;
        }

        # 开奖结果私发用户

        return true;
    }

    /**
     * @param $codesData 12角;23角、1念2;3念4
     * @param $drawData - 开奖号码：[$kjCode, $heZhi, $gui, $ds]
     * @param $single
     * @param $userId
     * @return array
     */
    public static function calcProfits($codesData, $drawData, $single, $userId): array
    {
        list($kjCode, $heZhi, $gui, $ds) = $drawData;
        //p(['codes'=>$codes, 'drawData'=>$drawData, 'single'=>$single, 'userId'=>$userId, 'TYPE_FT_OPTIONS'=>SscMethod::TYPE_FT_OPTIONS]);
        $codesArr = explode(MethodMatchService::ZU_SPLIT_FLAG, $codesData);
        $bonus = 0.00;
        $zjTimes = 0;
        $bettingMoney = count($codesArr) * $single;
        foreach ($codesArr as $codes){
            list($methodId, $methodName) = SscMethod::getMethod($codes);
            $Odds = Odds3dService::getOdds($userId, $methodId); # 玩法赔率
            //p([$methodId, $Odds, $methodName]);
            try {
                switch ($methodId){
                    case SscMethod::FT_ZHENG_ID:
                    case strpos($codes, '番') !== false:
                    case strpos($codes, '角') !== false:
                    case strpos($codes, '单') !== false OR strpos($codes, '双') !== false:
                    case strpos($codes, '大') !== false OR strpos($codes, '小') !== false:
                        if(strpos($codes, (string)$gui) !== false){
                            $zjTimes += 1;
                            $bonus += $Odds['odds'] * $single;
                        }
                        break;
                    case strpos($codes, '念') !== false:
                        list($firstCode, $nianCode) = explode('念', trim($codes));
                        if($gui == $firstCode){
                            $zjTimes += 1;
                            $bonus += $Odds['odds'] * $single;
                        }elseif($gui == $nianCode){
                            $zjTimes += 1;
                            $bonus += $single;
                        }
                        break;
                }
            }catch (\Exception $e){
                p($e->getMessage());
            }
        }
        //$bouns = $Odds['odds'] * $single * $zjTimes;
        $rstData = array_merge($rstData??[], [
            # 投注号码
            'codes' => $codesData,
            # 开奖号码
            'kjCodes' => '总和'.$heZhi.'('.$ds.','.$gui.')',
            # 投注金额
            'betting_money' => $bettingMoney,
            # 中奖金额 = 赔率 * 倍数 * 注数
            'bonus' => $bonus,
            # 利润 = 中奖金额 - 投注金额
            'profits' => $bouns - $bettingMoney,
            'zjTimes' => $zjTimes,
        ]);

        return $rstData;
    }

}
