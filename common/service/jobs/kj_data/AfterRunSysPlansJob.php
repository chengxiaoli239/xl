<?php
namespace common\service\jobs\kj_data;

use backend\service\TzService;
use common\service\jobs\CommonJob;

class AfterRunSysPlansJob extends CommonJob {

    public static function getName($params) {
        self::$name = '下注计划处理完成之后的业务';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lottery_type = $params['lottery_type'];
        $qihao = $params['lottery_type'];

        $rst['afterRunSysPlans'] = TzService::afterRunSysPlans($qihao, $lottery_type); # 开关的开启或关闭
        return $rst;
    }

}