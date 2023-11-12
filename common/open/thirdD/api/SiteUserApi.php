<?php
namespace common\open\thirdD\api;

use Yii;
use common\open\thirdD\SxThirdDBase;

class SiteUserApi extends SxThirdDBase
{
    # 用户信息接口
    const API_USER_INFO = '/ajaxapp/printrefresh';

    /**
     * 用户信息
     * @param array $params
     * @return array
     */
    public static function getUserInfoPost(array $params=[]): array
    {
        $res = self::createObject()->post(self::API_USER_INFO, $params);

        return $res;
    }

    /**
     * 用户信息
     * @param array $params
     * @return array
     */
    public static function getUserInfo(array $headers, array $params=[]): array
    {
        $params = array_merge([
            'action' => 'printrefresh',
            'iCurrPage' => 0,
            'doaction' => '',
            'time' => 193
        ], $params);
        $res = self::createObject()->get(self::API_USER_INFO, $params, $headers);

        return $res;
    }
}
