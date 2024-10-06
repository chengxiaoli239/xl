<?php

namespace backend\models\statics;

use common\models\statics\StaticPositionTypeArisePerdate as CommonStaticPositionTypeArisePerdate;

class StaticPositionTypeArisePerdate extends CommonStaticPositionTypeArisePerdate
{
    const TYPE_DX = 1;
    const TYPE_DS = 2;
    const TYPE_OPTIONS = [
        self::TYPE_DX => '大小',
        self::TYPE_DS => '单双',
    ];
}
