<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/backend/helpers/MissHistoryFormatter.php';

use backend\helpers\MissHistoryFormatter;

$html = MissHistoryFormatter::render(123, '-43-83-43');

if (strpos($html, 'miss-history__latest') === false || strpos($html, '>123<') === false) {
    throw new RuntimeException('最新遗漏未突出显示');
}
if (strpos($html, '123</strong>') > strpos($html, '43')) {
    throw new RuntimeException('最新遗漏没有排在历史遗漏之前');
}
if (strpos($html, '123</strong>-43-83-43</span>') === false) {
    throw new RuntimeException('遗漏记录数量不正确');
}

$limitedHtml = MissHistoryFormatter::render(500, '', '≥500');
if (strpos($limitedHtml, '≥500') === false) {
    throw new RuntimeException('统计窗口上限展示错误');
}

$storedWithCurrent = MissHistoryFormatter::render(123, '123-43-83-43', '', true);
if (substr_count($storedWithCurrent, '>123<') !== 1) {
    throw new RuntimeException('已含当前遗漏的历史记录被重复展示');
}

echo "miss_history_formatter_regression: OK\n";
