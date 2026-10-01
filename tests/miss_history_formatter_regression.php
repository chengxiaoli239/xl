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

$longRecords = '12-'.implode('-', range(1, 60));

if (array_slice(MissHistoryFormatter::splitRecords($longRecords), 0, 30) !== array_map('strval', array_merge(['12'], range(1, 29)))) {
    throw new RuntimeException('遗漏记录应只保留最新的 30 条');
}

if (MissHistoryFormatter::splitRecords('-'.implode('-', range(1, 5)).'-') !== array_map('strval', range(1, 5))) {
    throw new RuntimeException('首尾多余的分隔符应被忽略');
}

if (MissHistoryFormatter::splitRecords(' 3 - 4 -5 ') !== ['3', '4', '5']) {
    throw new RuntimeException('带空格的遗漏记录拆分错误');
}

if (MissHistoryFormatter::splitRecords('') !== []) {
    throw new RuntimeException('空遗漏记录应返回空数组');
}

$listCell = MissHistoryFormatter::renderListCell(12, '12-'.implode('-', range(1, 40)));
if (substr_count($listCell, '>12<') !== 1 || substr_count($listCell, 'miss-history__latest') !== 1) {
    throw new RuntimeException('列表单元格应只高亮一次最新遗漏');
}
if (substr_count($listCell, '-29') !== 2) {
    throw new RuntimeException('列表单元格应展示最新 30 条');
}
if (substr_count($listCell, '-30') !== 1) {
    throw new RuntimeException('超出展示上限的记录只应保留在 title 中');
}
if (strpos($listCell, '完整遗漏记录：12-1-2-3') === false) {
    throw new RuntimeException('title 应保留完整遗漏记录');
}

$shortCell = MissHistoryFormatter::renderListCell(3, '1-2-3', false);
if (strpos($shortCell, '完整遗漏记录') !== false) {
    throw new RuntimeException('未超出上限时不应附上完整记录');
}
if (substr_count($shortCell, '>3<') !== 1) {
    throw new RuntimeException('当前遗漏应排在记录之前');
}

if (substr_count(MissHistoryFormatter::renderListCell(3, '3-1-2'), '-3') !== 0) {
    throw new RuntimeException('声明含当前遗漏时应去重');
}
if (substr_count(MissHistoryFormatter::renderListCell(3, '3-1-2', false), '-3') !== 1) {
    throw new RuntimeException('未声明含当前遗漏时不应去重');
}

echo "miss_history_formatter_regression: OK\n";
