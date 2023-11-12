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
    public static function getUserInfo(string $domain, array $headers=[], array $params=[]): array
    {
        $object = self::createObject();
        $object->apiUrl = $domain;
        $params = array_merge([
            'action' => 'printrefresh',
            'iCurrPage' => 0,
            'doaction' => '',
            'time' => rand(100, 999),
        ], $params);
        $res = $object->get(self::API_USER_INFO, $params, $headers);

        return $res;
    }
}
