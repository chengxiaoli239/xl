<?php
namespace common\service\jobs\kj_data;

use backend\service\statics\statics_qx\PositionDxDsService;
use common\service\jobs\CommonJob;

class CommonDataStaticsJob extends CommonJob {

    public static function getName($params) {
        self::$name = '31普通数据统计';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lotteryType = $params['lottery_type'];
        $date = $params['date'] ?? date('Y-m-d');

        $rst = PositionDxDsService::staticPositionDxDs($lotteryType, $date);
        return $rst;
    }

}
