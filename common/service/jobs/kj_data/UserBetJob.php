<?php
namespace common\service\jobs\kj_data;

use backend\service\BetService;
use backend\service\TzService;
use common\service\jobs\CommonJob;

class UserBetJob extends CommonJob {

    public static function getName($params) {
        self::$name = '用户游戏业务开启';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $user_id = $params['user_id'];

        $rst['UserBetJob'] = BetService::lotteryBet($user_id);; # 开关的开启或关闭
        return $rst;
    }

}