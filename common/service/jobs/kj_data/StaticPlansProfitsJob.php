<?php
namespace common\service\jobs\kj_data;

use backend\models\SystemConfig;
use backend\service\StaticService;
use common\service\jobs\CommonJob;

class StaticSdProfitsJob extends CommonJob {

    public static function getName($params) {
        self::$name = '27计划利润统计';
        return self::$name;
    }

    private static $staticStatus = 0;
    private static function _init() {
        self::$staticStatus = SystemConfig::findOne(['key'=>'static_status'])->value;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        self::_init();
        $plan_id = $params['plan_id'];
        if(!self::$staticStatus) return ['status'=> 300, 'msg'=>'数据统计开关已关闭'];
        $rst = StaticService::staticOnePlanProifts($plan_id);
        return $rst;
    }

}