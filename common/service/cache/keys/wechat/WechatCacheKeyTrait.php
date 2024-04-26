<?php
namespace common\service\cache\keys\wechat;

trait WechatCacheKeyTrait
{
    public static function userCurrentWechat($user_id=0): string
    {
        return 'wechat:users:user_id_' . $user_id;
    }

    public static function robotInfo($robotId=0): string
    {
        return 'wechat:robot_info:robot_id_' . $robotId;
    }
}
