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
        $result = ThreeBrothersYlService::refreshSummary((int)$params['lottery_type']);
        // 沿用每期开奖已有的统计入口，兼容尚未重启的开奖采集进程。
        push_queue(UpdateFourSameYlJob::class, $params);
        push_queue(UpdateTwoComboYlJob::class, $params);
        push_queue(UpdateThreeSameYlJob::class, $params);
        push_queue(UpdateDoublePairYlJob::class, $params);
        return $result;
    }
}
