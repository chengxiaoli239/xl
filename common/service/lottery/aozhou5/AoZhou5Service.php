<?php

namespace common\service\lottery\aozhou5;

use backend\models\BettingRecords;
use common\service\lottery\CommonLotteryService;

class AoZhou5Service extends CommonLotteryService
{
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
        $Bet = new BettingRecords();
        $Bet->setAttributes($data);
        $result = $Bet->save();

        return $result;
    }

    public static function afterKj($lottery_type)
    {
        $bettingRecords = BettingRecords::find()->where(['status'=>0, 'lottery_type'=>$lottery_type])
            ->orderBy('id DESC')->limit(100)->all();

    }

}
