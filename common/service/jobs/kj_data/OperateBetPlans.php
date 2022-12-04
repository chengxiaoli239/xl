<?php
namespace common\service\jobs\kj_data;

use backend\service\TzService;
use common\service\jobs\CommonJob;

class OperateBetPlans extends CommonJob {

    public static function getName($params) {
        self::$name = '处理下注计划';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lottery_type = $params['lottery_type'];

        $rst = TzService::opSystemBetPlans($lottery_type); # 处理系统投注计划，更新统计数据、
        return $rst;
    }

}