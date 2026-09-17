<?php
namespace common\service\jobs\datas;

use backend\service\datas\DatasClearService;
use common\service\jobs\CommonJob;

class ClearExpiredBetDataJob extends CommonJob
{
    public function getTtr(): int
    {
        return 900;
    }

    public static function getName($params): string
    {
        self::$name = '清理过期投注数据';
        return self::$name;
    }

    public function exec($params): array
    {
        return DatasClearService::clearExpiredBetData();
    }
}
