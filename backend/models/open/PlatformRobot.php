<?php

namespace backend\models\open;

use common\models\open\PlatformRobot as CommonPlatformRobot;

class PlatformRobot extends CommonPlatformRobot
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_OPTIONS = [
        self::STATUS_INACTIVE => '禁用',
        self::STATUS_ACTIVE => '激活',
    ];

}
