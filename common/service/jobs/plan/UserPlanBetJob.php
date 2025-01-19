<?php
namespace common\service\jobs\plan;

use backend\service\BetService;
use common\service\jobs\CommonJob;

class UserPlanBetJob extends CommonJob {

    public static function getName($params) {
        self::$name = '计划下注任务';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $isAuto = $params['is_auto']??1;

        return BetService::insertRecord($params['plan_id'], $params['qiHao'], $isAuto);
    }

}