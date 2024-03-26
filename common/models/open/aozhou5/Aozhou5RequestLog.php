<?php

namespace common\models\open\aozhou5;

use common\models\open\OutSiteRequestLog;
use Yii;
class Aozhou5RequestLog extends OutSiteRequestLog
{
    public static function tableName(): string
    {
        return '{{%aozhou5_request_log}}';
    }

}
