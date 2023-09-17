<?php

namespace common\service\wechat;

use common\models\eyun\EyunAuth;
use common\models\eyun\RobotUser;
use common\service\BaseService;

class RobotUserService extends BaseService
{
    # 禁用
    const STATUS_DISABLE = 0;
    # 激活
    const STATUS_ACTIVE = 1;

    const WECHAT_STATUS_OFFLINE = 0;
    const WECHAT_STATUS_ONLINE = 1;

    public static $s = [
        'status' => [
            self::STATUS_DISABLE => '已禁用',
            self::STATUS_ACTIVE => '已激活',
        ],
        'wechat_status' => [
            self::WECHAT_STATUS_OFFLINE => '已掉线',
            self::WECHAT_STATUS_ONLINE => '在线',
        ],
    ];

}
