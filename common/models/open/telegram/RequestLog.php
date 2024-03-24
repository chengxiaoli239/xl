<?php

namespace common\models\open\telegram;

use common\models\open\OutSiteRequestLog;
use Yii;
class RequestLog extends OutSiteRequestLog
{
    const REQUEST_STATUS_SUCCESS = 2;
    const REQUEST_STATUS_FAIL = 3;

    const STATUS_OPTIONS = [
        self::REQUEST_STATUS_SUCCESS => '成功',
        self::REQUEST_STATUS_FAIL => '成功',
    ];

    public static function tableName(): string
    {
        return '{{%out_site_request_log}}';
    }

}
