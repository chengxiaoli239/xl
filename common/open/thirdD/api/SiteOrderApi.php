<?php
namespace common\open\thirdD\api;

use Yii;
use common\open\chaoti\SxThirdDBase;

class SiteOrderApi extends SxThirdDBase
{

    const PAY_STATUS_UN_PAY = 1;
    const PAY_STATUS_PAID = 2;
    const PAY_STATUS_OPTIONS = [
        self::PAY_STATUS_UN_PAY => '待支付',
        self::PAY_STATUS_PAID => '已支付',
    ];

    // 下单订单
    const API_CREATE_ORDER = '/ajaxapp/soonsend';

    /**
     * 推单
     * @param array $params    参数
     * @return array
     */
    public static function push(array $params): array
    {
        $res = self::createObject()->post(self::API_CREATE_ORDER, $params);
        
        return $res;
    }

}
