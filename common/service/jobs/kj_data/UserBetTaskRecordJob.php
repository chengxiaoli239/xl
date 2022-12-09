<?php
namespace common\service\jobs\kj_data;

use backend\service\BetService;
use common\service\jobs\CommonJob;

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

        $rst = BetService::insertPlansTask([$lottery_type]);
        return $rst;
    }

}