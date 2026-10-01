<?php

namespace common\service\jobs\kj_data;

use backend\service\statics\yl\ThreeDistinctYlService;
use common\service\jobs\CommonJob;

class UpdateThreeDistinctYlJob extends CommonJob
{
    public $retryCount = 1;

    public function getTtr(): int
    {
        return 120;
    }

    public static function getName($params)
    {
        self::$name = '30三字不重复遗漏';
        return self::$name;
    }

    public function exec($params)
    {
        return ThreeDistinctYlService::refreshSummary((int)$params['lottery_type']);
    }
}
