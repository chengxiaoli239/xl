<?php
namespace common\service\ssc\filterCode;

class FenLiShu extends \common\service\BaseService
{
    const TYPE_ABCD = 1;
    const TYPE_ABCX = 2;
    const TYPE_ABXD = 3;
    const TYPE_AXCD = 4;
    const TYPE_XBCD = 5;
    const TYPE_AXXD = 6;
    const TYPE_XBCX = 7;
    const TYPE_ABXX = 8;
    const TYPE_AXCX = 9;
    const TYPE_XBXD = 10;
    const TYPE_XXCD = 11;

    const TYPE_FLS_OPTIONS = [
        self::TYPE_ABCD => 'ABCD',
        self::TYPE_ABCX => 'ABCX',
        self::TYPE_ABXD => 'ABXD',
        self::TYPE_AXCD => 'AXCD',
        self::TYPE_XBCD => 'XBCD',
        self::TYPE_AXXD => 'AXXD',
        self::TYPE_XBCX => 'XBCX',
        self::TYPE_ABXX => 'ABXX',
        self::TYPE_AXCX => 'AXCX',
        self::TYPE_XBXD => 'XBXD',
        self::TYPE_XXCD => 'XXCD',
    ];
}