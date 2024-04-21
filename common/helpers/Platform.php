<?php
namespace common\helpers;

class Platform
{
    const WECHAT = 1;
    const TELEGRAM = 2;
    const TYPE_OPTIONS = [
        self::WECHAT => '微信',
        self::TELEGRAM => 'telegram',
    ];

    const SYSTEM_STATUS_INVALID = 0;
    const SYSTEM_STATUS_VALID = 1;
    const SYSTEM_STATUS_OPTIONS = [
        self::SYSTEM_STATUS_INVALID => '停止工作',
        self::SYSTEM_STATUS_VALID => '工作中',
    ];

    public static function getName($platformId=0): string
    {
        return self::TYPE_OPTIONS[$platformId]??'未知平台';

    }
}
