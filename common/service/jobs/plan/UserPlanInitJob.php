<?php
namespace common\service\jobs\plan;

use backend\service\statics\plan\OperatePlanService;
use common\service\jobs\CommonJob;

class UserPlanInitJob extends CommonJob {

    public static function getName($params) {
        self::$name = '翻倍计划初始化';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lotteryType = $params['lottery_type'];

        return OperatePlanService::initPlanPerDate($lotteryType); // 每天收盘初始化需要初始化的计划
    }

}
