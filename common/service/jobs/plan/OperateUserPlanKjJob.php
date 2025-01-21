<?php
namespace common\service\jobs\plan;

use backend\service\OpKjService;
use common\service\jobs\CommonJob;

class OperateUserPlanKjJob extends CommonJob {

    public static function getName($params) {
        self::$name = '游戏计划开奖任务';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $betId = $params['bet_id'];
        $kjData = $params['kj_data'];

        return OpKjService::opOneBettingRecord($betId, '', $kjData);
    }

}