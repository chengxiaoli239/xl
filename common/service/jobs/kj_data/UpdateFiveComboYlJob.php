<?php

namespace common\service\jobs\kj_data;

use backend\service\statics\yl\FiveComboYlService;
use common\service\jobs\CommonJob;

class UpdateFiveComboYlJob extends CommonJob
{
    public $retryCount = 1;

    public function getTtr(): int
    {
        return 120;
    }

    public static function getName($params)
    {
        self::$name = '28五字复式遗漏';
        return self::$name;
    }

    public function exec($params)
    {
        return FiveComboYlService::refreshSummary((int)$params['lottery_type']);
    }
}
