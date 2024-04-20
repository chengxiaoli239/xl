<?php
namespace common\service\jobs\kj_data;

use backend\service\statics\yl\OneNumYl;
use backend\service\StaticService;
use common\service\jobs\CommonJob;

class Update1NumYlJob extends CommonJob {

    public static function getName($params) {
        self::$name = '27-一码遗漏';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lotteryType = $params['lottery_type'];
        $rst = OneNumYl::yl($lotteryType);

        return $rst;
    }

}