<?php
namespace common\service\jobs\user;

use backend\service\UserService;
use common\service\jobs\CommonJob;

class UserExpireTimeOperateJob extends CommonJob {

    public static function getName($params) {
        self::$name = '账号到期自动关闭';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){

        try {
            $userId = $params['user_id'];

            UserService::updateUserStatus($userId, $status=1);
        }catch (\Exception $e){
            return $e->getMessage();
        }

        return true;
    }

}