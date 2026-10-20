<?php

namespace common\service\jobs\kj_data;

use backend\service\statics\yl\TwoComboYlService;
use common\service\jobs\CommonJob;

class UpdateTwoComboYlJob extends CommonJob
{
    public $retryCount = 1;

    public function getTtr(): int
    {
        return 120;
    }

    public static function getName($params)
    {
        self::$name = '33二字复式遗漏';
        return self::$name;
    }

    public function exec($params)
    {
        return TwoComboYlService::refreshSummary((int)$params['lottery_type']);
    }
}
