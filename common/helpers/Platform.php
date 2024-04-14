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

    public static function getName($platformId=0): string
    {
        return self::TYPE_OPTIONS[$platformId]??'未知平台';

    }
}
