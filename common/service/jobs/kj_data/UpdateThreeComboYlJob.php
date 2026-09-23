<?php

namespace common\service\jobs\kj_data;

use backend\service\statics\yl\ThreeComboYlService;
use common\service\jobs\CommonJob;

class UpdateThreeComboYlJob extends CommonJob
{
    public $retryCount = 1;

    public function getTtr(): int
    {
        return 120;
    }

    public static function getName($params)
    {
        self::$name = '27三字复式遗漏';
        return self::$name;
    }

    public function exec($params)
    {
        return ThreeComboYlService::refreshSummary((int)$params['lottery_type']);
    }
}
