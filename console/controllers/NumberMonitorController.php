<?php

namespace console\controllers;

use backend\service\NumberMonitorService;
use common\service\jobs\number_monitor\ProcessNumberMonitorDrawJob;
use Yii;
use yii\console\Controller;

class NumberMonitorController extends Controller
{
    public function actionSchedule(int $interval = 3): void
    {
        $interval = max(1, min(10, $interval));
        $scheduledThrough = [];

        while (true) {
            foreach (array_keys(NumberMonitorService::lotteryTypeOptions()) as $lotteryType) {
                $lotteryType = (int)$lotteryType;
                try {
                    if (!NumberMonitorService::hasActiveMonitoringFiles($lotteryType)) {
                        $scheduledThrough[$lotteryType] = 0;
                        continue;
                    }

                    $latestDrawId = NumberMonitorService::latestDrawIdForLottery($lotteryType);
                    $lastScheduledId = max(
                        NumberMonitorService::processedDrawIdForLottery($lotteryType),
                        $scheduledThrough[$lotteryType] ?? 0
                    );
                    if ($latestDrawId <= $lastScheduledId) {
                        continue;
                    }

                    $queued = push_queue_monitor(ProcessNumberMonitorDrawJob::class, [
                        'lotteryType' => $lotteryType,
                        'business_id' => 'draw-poll-' . $lotteryType . '-' . $latestDrawId,
                    ]);
                    if ($queued) {
                        $scheduledThrough[$lotteryType] = $latestDrawId;
                    } else {
                        Yii::warning('号码监控开奖任务入列失败', 'number-monitor');
                    }
                } catch (\Throwable $exception) {
                    Yii::error($exception, 'number-monitor-scheduler');
                }
            }

            sleep($interval);
        }
    }
}
