<?php
namespace common\service\jobs\kj_data;

use backend\service\BetService;
use common\service\jobs\CommonJob;
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
        $HI = date('H:i');
        if($lottery_type == DEFAULT_LOTTERY_TYPE && '04:00'<$HI && $HI<'09:00'){
            return '幸运五非开盘时间';
        }

        $rst = BetService::insertPlansTask([$lottery_type]);
        return $rst;
    }

}