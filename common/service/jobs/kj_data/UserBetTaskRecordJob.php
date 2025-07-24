<?php
namespace common\service\jobs\kj_data;

use backend\service\BetService;
use common\service\jobs\CommonJob;
use common\service\lottery\LotteryTypeService;
use common\tools\KjDataGet;

class UserBetTaskRecordJob extends CommonJob {

    public static function getName($params) {
        self::$name = '3用户游戏任务写入';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lottery_type = $params['lottery_type'];

        KjDataGet::isCanGrab($lottery_type, $isCanBet);
        if(!$isCanBet){
            return '非开盘时间段';
        }
        $lotteryTypeData = LotteryTypeService::getLotteryTypeData();
        $openingTime = $lotteryTypeData[$lottery_type]['opening_time'];
        $closingTime = $lotteryTypeData[$lottery_type]['closing_time'];
        $HI = date('H:i:s');
        if($lottery_type == 8 && $closingTime<$HI && $HI<$openingTime){
            return '幸运五非开盘时间';
        }

        return BetService::insertPlansTask([$lottery_type], $isJob=1);
    }

}