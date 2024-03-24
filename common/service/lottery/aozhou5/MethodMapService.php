<?php

namespace common\service\lottery\aozhou5;

use backend\models\BettingRecords;
use common\helpers\LotteryType;
use common\service\lottery\CommonLotteryService;
use common\tools\Tool_Common;

class MethodMapService extends CommonLotteryService
{
    const METHOD_ZHENG_1 = 134;
    const METHOD_ZHENG_2 = 135;
    const METHOD_ZHENG_3 = 136;
    const METHOD_ZHENG_4 = 137;
    const METHOD_FAN_1 = 138;
    const METHOD_FAN_2 = 139;
    const METHOD_FAN_3 = 140;
    const METHOD_FAN_4 = 141;

    const METHOD_JIAO_12 = 142;
    const METHOD_JIAO_14 = 143;
    const METHOD_JIAO_23 = 144;
    const METHOD_JIAO_34 = 145;

    const METHOD_NIAN_12 = 146;
    const METHOD_NIAN_13 = 147;
    const METHOD_NIAN_14 = 148;
    const METHOD_NIAN_21 = 149;
    const METHOD_NIAN_23 = 150;
    const METHOD_NIAN_24 = 151;
    const METHOD_NIAN_31 = 152;
    const METHOD_NIAN_32 = 153;
    const METHOD_NIAN_34 = 154;
    const METHOD_NIAN_41 = 155;
    const METHOD_NIAN_42 = 156;
    const METHOD_NIAN_43 = 157;
    const METHOD_DAN = 158;
    const METHOD_SHUANG = 159;

    const METHOD_TYPE_OPTIONS = [
        # 正
        self::METHOD_ZHENG_1 => '1正',
        self::METHOD_ZHENG_2 => '2正',
        self::METHOD_ZHENG_3 => '3正',
        self::METHOD_ZHENG_4 => '4正',

        # 番
        self::METHOD_FAN_1 => '1番',
        self::METHOD_FAN_2 => '2番',
        self::METHOD_FAN_3 => '3番',
        self::METHOD_FAN_4 => '4番',

        # 角
        self::METHOD_JIAO_12 => '12角',
        self::METHOD_JIAO_14 => '14角',
        self::METHOD_JIAO_23 => '23角',
        self::METHOD_JIAO_34 => '34角',

        # 1念
        self::METHOD_NIAN_12 => '1念2',
        self::METHOD_NIAN_13 => '1念3',
        self::METHOD_NIAN_14 => '1念4',
        # 2念
        self::METHOD_NIAN_21 => '2念1',
        self::METHOD_NIAN_23 => '2念3',
        self::METHOD_NIAN_24 => '2念4',
        # 3念
        self::METHOD_NIAN_31 => '3念1',
        self::METHOD_NIAN_32 => '3念2',
        self::METHOD_NIAN_34 => '3念4',
        # 4念
        self::METHOD_NIAN_41 => '4念1',
        self::METHOD_NIAN_42 => '4念2',
        self::METHOD_NIAN_43 => '4念3',

        # 单双
        self::METHOD_DAN => '单',
        self::METHOD_SHUANG => '双',
    ];

}
