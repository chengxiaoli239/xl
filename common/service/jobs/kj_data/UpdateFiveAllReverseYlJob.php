<?php

namespace common\service\jobs\kj_data;

use backend\service\statics\yl\FiveAllReverseYlService;
use common\service\jobs\CommonJob;

class UpdateFiveAllReverseYlJob extends CommonJob
{
    public $retryCount = 1;

    public function getTtr(): int
    {
        return 120;
    }

    public static function getName($params)
    {
        self::$name = '29五字全倒遗漏';
        return self::$name;
    }

    public function exec($params)
    {
        return FiveAllReverseYlService::refreshSummary((int)$params['lottery_type']);
    }
}
