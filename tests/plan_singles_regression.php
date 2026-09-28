<?php

require dirname(__DIR__).'/backend/service/plans/PlanSinglesService.php';

use backend\service\plans\PlanSinglesService;

function assertSingles(string $expected, string $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message.PHP_EOL);
        exit(1);
    }
}

$unicodeGradient = "0.40\u{2011}0.60\u{2011}0.80";
assertSingles('0.40-0.60-0.80', PlanSinglesService::normalize($unicodeGradient), '特殊连字符必须能分隔倍投档位');
assertSingles('0.40-0.60-0.80', PlanSinglesService::normalize('0.40-0.60-0.80'), '普通倍投梯度不能变化');
assertSingles('0.40-0.60-0.80', PlanSinglesService::normalize(" 0.40 \u{2011} 0.60 \u{2011} 0.80 "), '粘贴时的空白不能污染档位');

echo "plan_singles_regression: OK\n";
