<?php
namespace common\service\jobs\robots\user;

use common\models\wechat\WechatUser;
use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;

class WechatFriendsInfoJobs extends CommonJob {

    public static function getName($params) {
        self::$name = '好友信息变更同步';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        try {
            $user_id = $params['user_id']; # 机器人系统user_id
            $userName = $params['userName']; # 微信id
            $wcId = $params['wcId']; # 微信原始id
            $WechatUser = WechatUser::findOne(['user_id'=>$user_id, 'userName'=>$userName]);
            if(empty($RobotUser)){
                throw_info('机器人robot_user找不到记录');
            }
            $WechatUser->nickName = $params['nickName'];
            $WechatUser->remark = $params['remarkName'];
            $WechatUser->bigHead = $params['bigHead'];
            $WechatUser->smallHead = $params['smallHead'];
            $WechatUser->save();
        }catch (\Exception $e){
            return $e->getMessage();
        }

        Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', '微信用户信息变更', ['params'=>$params]);

        return '微信登录状态同步成功:';
    }

}
