<?php

namespace common\service\lottery\aozhou5;

use backend\models\SscKjData;
use backend\models\wechat\Bets;
use backend\models\wechat\WechatUser;
use backend\service\agent\AgentUsersBalanceService;
use common\helpers\lottery\DrawLottery;
use common\helpers\LotteryType;
use common\helpers\SscMethod;
use common\service\CommonService;
use common\service\jobs\telegram\SendMessageJobs;
use common\service\lottery\CommonLotteryService;
use common\service\open\telegram\AoZhouKjService;
use common\service\ssc\QihaoService;
use common\service\thirdD\MethodMatchService;
use common\service\thirdD\Odds3dService;
use common\service\wechat\WechatUserService;
use common\tools\Tool_Common;
use yii\helpers\Json;

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
     * @return bool
     */
    public static function afterKj()
    {
        try {
            $lotteryType = self::LOTTERY_TYPE_AOZHOU5;
            list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);
            if(!SscKjData::find()->where(['lottery_type'=>$lotteryType, 'qihao'=>$currentKjQiHao])->asArray()->one()){
                return false;
            }
            $bets = Bets::find()->where(['status'=>0, 'lottery_type'=>$lotteryType]) # , 'qihao'=>$currentKjQiHao
                ->orderBy('id DESC')->limit(100)->all();
            $replyData = [];
            foreach ($bets as $bet){
                $result = self::opOneBettingRecord($bet->id, $bet);
                if(!$result){
                    continue;
                }
                $replyData[$bet->wechat_user_id][$bet->qihao]['betIds'][] = $bet->id;
                $replyData[$bet->wechat_user_id][$bet->qihao]['userId'][] = $bet->user_id;
                $replyData[$bet->wechat_user_id][$bet->qihao]['reply_content'] = Json::decode($bet->reply_content);
                $replyData[$bet->wechat_user_id]['result'][] = $result;
            }
            Tool_Common::log('/kj_aozhou5/'.__FUNCTION__, 'INFO', LotteryType::getName($lotteryType).'开奖之后业务处理', ['lottery_type'=>$lotteryType, 'currentKjQiHao'=>$currentKjQiHao, 'count'=>count($bets), 'replyData'=>$replyData]);

            foreach ($replyData as $wechatUserId=>$replyDatum){
                foreach ($replyDatum as $qiHao=>$value){
                    list($codeHz, $kjCode, $ds, $ft) = AoZhouKjService::getAoZhouKjData($qiHao);
                    $text = "第".$qiHao."期\n\n".$kjCode.'总和'.$codeHz."(".$ds.",".$ft.")\n\n";
                    $userId = $value['user_id'];
                    $replyContent = $value['reply_content'];

                    $betRows = Bets::find()->where(['id'=>$value['betIds'],'qihao'=>$qiHao, 'status'=>1])->asArray()->all();
                    foreach ($betRows as $betRow){
                        $profits = $betRow['profits'];
                        $text .= $betRow['codes'].'/'.floatval($betRow['bet_money']).'，'.(
                            $betRow['profits']==0 ? ('平') : ($profits>0?('中，得'.$profits):('不中，亏'.abs($profits)))
                            )."\n";
                    }
                    $platformUser = WechatUser::find()->where(['id'=>$wechatUserId])->asArray()->one();
                    $text .= "\n余额：".$platformUser['balance'];
                    $sendData = [
                        'user_id' => $userId,
                        'chat_id' => $replyContent['fromUser'], # 谁发就给谁回复，要先判断是否是群聊，判断条件：fromGroup 存在且有值
                        'content' => $text, # 测试阶段调试信息 - 用户下注完回复
                        'business_id' => $userId,
                        'token' => $replyContent['token'], # 机器人的token
                    ];
                    //todo 开奖结果私发用户
                    push_queue(SendMessageJobs::class, $sendData); # 中奖结果消息发送
                }
            }
        }catch (\Exception $e){
            Tool_Common::log('/kj_aozhou5/'.__FUNCTION__, 'ERR', '开奖处理异常', ['lottery_type'=>$lotteryType, 'name'=>LotteryType::TYPE_OPTIONS[$lotteryType], 'err_msg'=>$e->getMessage()]);
        }

        return true;
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
            $qiHao = $bet->qihao;
            $single = $bet->single;
            $codes = $bet->codes;
            $lottery_type = $bet->lottery_type;

            # 开奖数据
            if(!$kjData = CommonService::getAwardNumberByQihao($qiHao, $lottery_type)){ // 3,4,5,6,7
                throw_info(LotteryType::TYPE_OPTIONS[$lottery_type].$qiHao.'期未开奖!');
            }
            $drawData = DrawLottery::getGuiDrawData($kjData, $codeNum=self::KJ_CODE_NUM); # 4个或者5个

            $profitsData = self::calcProfits($codes, $drawData, $single, $bet->user_id);
            //p(['qihao'=>$qihao, 'kjData'=>$kjData, 'drawData'=>$drawData, 'codes'=>$codes, 'id'=>$bet->id, 'playmethod'=>$bet->play_method]);

            $bonus = $profitsData['bonus'];
            $profits = $bonus - $bet['bet_money'];
            $zjTimes = $profitsData['zjTimes'];

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
                'qiHao'=>$qiHao,
                'opRst'=>$status,'codes'=>$codes,'is_simulate'=>$is_simulate, 'profits'=>$profits,
                'kjData'=>$kjData, 'single'=>$single,'zjTimes'=>$zjTimes,'bonus'=>$bonus,
            ];

            if($bonus>0){
                # 派奖
                AgentUsersBalanceService::updateBalance((string)$bet->order_id, $bonus, $bet->wechat_user_id, WechatUserService::TYPE_ORDER_AWARD);
            }

            Tool_Common::log('/kj_aozhou5/'.__FUNCTION__,'INFO','投注记录处理', $logArr);
            $transaction->commit();
        }catch (\Exception $e){
            $transaction->rollBack();
            Tool_Common::log('/kj_aozhou5/'.__FUNCTION__,'ERR','投注记录-处理失败', ['record_id'=>$recordId, 'err_msg'=>$e->getMessage(), 'file'=>$e->getFile().'_'.$e->getLine()]);
            return false;
        }

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
