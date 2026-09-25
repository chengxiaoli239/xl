<?php

require dirname(__DIR__).'/backend/service/plans/PlanBetWindowService.php';

use backend\service\plans\PlanBetWindowService;

function assertWindow(bool $expected, bool $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message.PHP_EOL);
        exit(1);
    }
}

function assertInvalidWindow(string $start, string $end): void
{
    try {
        PlanBetWindowService::isWithin($start, $end);
    } catch (InvalidArgumentException $exception) {
        return;
    }

    fwrite(STDERR, '非法时间不应被静默当成全天有效'.PHP_EOL);
    exit(1);
}

$outside = strtotime('2026-09-25 20:00:00');
assertWindow(true, PlanBetWindowService::isWithin(null, null, $outside), '空窗口应全天允许');
assertWindow(true, PlanBetWindowService::isWithin('09:00', '18:00', strtotime('2026-09-25 09:00:00')), '开始边界应允许');
assertWindow(true, PlanBetWindowService::isWithin('09:00', '18:00', strtotime('2026-09-25 18:00:00')), '结束边界应允许');
assertWindow(false, PlanBetWindowService::isWithin('09:00', '18:00', $outside), '窗口外应禁止');
assertWindow(true, PlanBetWindowService::isWithin('22:00', '02:00', strtotime('2026-09-25 23:30:00')), '跨午夜前半段应允许');
assertWindow(true, PlanBetWindowService::isWithin('22:00', '02:00', strtotime('2026-09-25 01:30:00')), '跨午夜后半段应允许');
assertWindow(false, PlanBetWindowService::isWithin('22:00', '02:00', strtotime('2026-09-25 12:00:00')), '跨午夜窗口外应禁止');
assertInvalidWindow('25:00', '02:00');
assertInvalidWindow('09:00', '');

echo "plan_bet_window_regression: OK\n";
