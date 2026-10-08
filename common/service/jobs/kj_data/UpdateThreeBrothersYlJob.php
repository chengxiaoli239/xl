<?php

namespace common\service\jobs\kj_data;

use backend\service\statics\yl\ThreeBrothersYlService;
use common\service\jobs\CommonJob;

class UpdateThreeBrothersYlJob extends CommonJob
{
    public $retryCount = 1;

    public function getTtr(): int
    {
        return 120;
    }

    public static function getName($params)
    {
        self::$name = '31三兄弟遗漏';
        return self::$name;
    }

    public function exec($params)
    {
        return ThreeBrothersYlService::refreshSummary((int)$params['lottery_type']);
    }
}
