<?php
namespace common\service\jobs\kj_data;

use backend\service\SscDataService;
use common\service\jobs\CommonJob;

class StaticPeiShuTrueFalseJob extends CommonJob {

    public static function getName($params) {
        self::$name = '24设置配数对错';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lottery_type = $params['lottery_type'];

        $rst = SscDataService::staticPeiShuTrueFalse([$lottery_type]);
        return $rst;
    }

}