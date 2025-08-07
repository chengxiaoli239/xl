<?php
namespace common\service\jobs\kj_data;

use backend\service\StaticService;
use common\service\jobs\CommonJob;

class UpdateCodeTypeYlJob extends CommonJob {

    public static function getName($params) {
        self::$name = '26号码类型遗漏';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lottery_type = $params['lottery_type'];
        $qihao = $params['qihao'];

        $rst = StaticService::opAllCodeTypeYl($lottery_type, $qihao);
        return $rst;
    }

}