<?php
namespace common\service\jobs\kj_data;

use backend\service\StaticService;
use common\service\jobs\CommonJob;

class StaticAll2NumsYlJob extends CommonJob {

    public static function getName($params) {
        self::$name = '22二字现遗漏';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lottery_type = $params['lottery_type'];

        $rst = StaticService::static2NumsYl($lottery_type);
        return $rst;
    }

}