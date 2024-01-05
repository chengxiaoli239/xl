<?php
namespace common\service\cache\keys\wechat;

trait WechatCacheKeyTrait
{
    public static function userCurrentWechat($user_id=0): string
    {
        return 'wechat:users:user_id_' . $user_id;
    }

}
