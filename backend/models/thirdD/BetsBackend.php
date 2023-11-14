<?php

namespace backend\models\thirdD;

use common\models\thirdD\Bets;

class BetsBackend extends Bets
{
    const LOTTERY_FUCAI = 26;
    const LOTTERY_PL3 = 27;
    const LOTTERYS = [
        self::LOTTERY_FUCAI => '福',
        self::LOTTERY_PL3 => '排',
    ];

    const PUSH_STATUS_WAIT = 0;
    const PUSH_STATUS_SUCCESS = 2;
    const PUSH_STATUS_FAIL = 3;
    const PUSH_STATUS_CANNOT = 4;
    const PUSH_STATUS_OPTIONS = [
        self::PUSH_STATUS_WAIT => '待推',
        self::PUSH_STATUS_SUCCESS => '成功',
        self::PUSH_STATUS_FAIL => '失败',
        self::PUSH_STATUS_CANNOT => '不可推',
    ];
    const PUSH_STATUS_CLASSES = [
        self::PUSH_STATUS_WAIT => 'grey',
        self::PUSH_STATUS_SUCCESS => 'green',
        self::PUSH_STATUS_FAIL => 'red',
        self::PUSH_STATUS_CANNOT => '#71125a',
    ];
}
