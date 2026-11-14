<?php

namespace common\service\jobs\number_monitor;

use backend\service\NumberMonitorService;
use common\service\jobs\CommonJob;

class ProcessNumberMonitorDrawJob extends CommonJob
{
    public $retryCount = 3;
    public $lotteryType;

    public function getTtr(): int
    {
        return 90;
    }

    public static function getName($params)
    {
        self::$name = '号码监控开奖匹配';
        return self::$name;
    }

    public function exec($params)
    {
        $lotteryType = (int)($params['lotteryType'] ?? $this->lotteryType);
        try {
            $processed = NumberMonitorService::processPendingDraws($lotteryType);
            if ($processed > 0 && NumberMonitorService::hasPendingDraws($lotteryType)
                && !push_queue_monitor(self::class, [
                    'lotteryType' => $lotteryType,
                    'business_id' => 'backfill-' . $lotteryType,
                ])) {
                throw new \RuntimeException('无法续入号码监控开奖任务');
            }
        } catch (\Throwable $exception) {
            $retry = (int)($params['monitor_retry'] ?? 0);
            if ($retry < 2) {
                push_queue_monitor(self::class, [
                    'lotteryType' => $lotteryType,
                    'monitor_retry' => $retry + 1,
                    'queue_delay_time' => 10,
                    'business_id' => 'retry-' . $lotteryType . '-' . ($retry + 1),
                ]);
            }
            throw $exception;
        }
        return true;
    }
}
