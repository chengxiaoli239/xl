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

    /**
     * 管理员管理的系统类型
     * @param int $user_type
     * @return string
     */
    public static function manageSites(int $user_type=1): string
    {
        return 'admin:manageSites:' . $user_type;
    }

    /**
     * 用信息
     * @param int $userId
     * @return string
     */
    public static function userSiteInfo(int $userId=0): string
    {
        return 'admin:site_info:' . $userId;
    }

    /**
     * 获取线路
     * @param $username
     * @return string
     */
    public static function getLineNumber($username): string
    {
        return 'admin:line_number_'.$username;
    }
}
