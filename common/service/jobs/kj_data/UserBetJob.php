<?php
namespace common\service\jobs\kj_data;

use common\service\jobs\CommonJob;

class UserBetJob extends CommonJob {

    public static function getName($params) {
        self::$name = '4用户游戏任务执行';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        return $params;
    }

}