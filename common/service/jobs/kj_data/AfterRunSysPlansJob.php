<?php
namespace common\service\jobs\kj_data;

use backend\service\TzService;
use common\service\jobs\CommonJob;

class AfterRunSysPlansJob extends CommonJob {

    public static function getName($params) {
        self::$name = '2游戏开关开启';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lottery_type = $params['lottery_type'];
        $qihao = $params['qihao'];

        $rst['afterRunSysPlans'] = TzService::afterRunSysPlans($qihao, $lottery_type); # 开关的开启或关闭
        return $rst;
    }

}