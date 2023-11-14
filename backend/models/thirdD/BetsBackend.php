<?php

namespace backend\models\thirdD;

use common\models\thirdD\Bets;

class BetsBackend extends Bets
{
    const LOTTERY_FUCAI = 26;
    const LOTTERY_PL3 = 27;
    const LOTTERYS = [
        self::LOTTERY_FUCAI => '╦ё',
        self::LOTTERY_PL3 => 'ее',
    ];

}
