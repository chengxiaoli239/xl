<?php
namespace common\service\cache\keys\admin;

trait AdminCacheKeyTrait
{
    /**
     * 管理员选中的用户类型 key
     * @param int $user_type
     * @return string
     */
    public static function userType(int $user_type=1): string
    {
        return 'admin:user_type:' . $user_type;
    }
}
