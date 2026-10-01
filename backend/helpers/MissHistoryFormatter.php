<?php

namespace backend\helpers;

use yii\helpers\Html;

class MissHistoryFormatter
{
    /**
     * 列表页单元格：只展示最新若干条，完整记录放进 title，避免超长单元格把表格撑坏。
     */
    public static function renderListCell($currentMiss, $records, bool $recordsIncludeCurrent = true, int $limit = 30): string
    {
        $values = self::splitRecords($records);
        $shown = array_slice($values, 0, max($limit, 0));
        $cell = self::render($currentMiss, $shown, '', $recordsIncludeCurrent);
        if (count($values) <= count($shown)) {
            return $cell;
        }

        return Html::tag('span', $cell, ['title' => '完整遗漏记录：'.implode('-', $values)]);
    }

    public static function render($currentMiss, $records = '', string $currentDisplay = '', bool $recordsIncludeCurrent = false): string
    {
        $currentDisplay = $currentDisplay !== '' ? $currentDisplay : (string)$currentMiss;
        $history = self::splitRecords($records);
        if ($recordsIncludeCurrent && isset($history[0]) && (string)$history[0] === (string)$currentMiss) {
            array_shift($history);
        }

        $items = [Html::tag('strong', Html::encode($currentDisplay), [
            'class' => 'miss-history__latest',
            'title' => '最新遗漏',
        ])];

        foreach ($history as $value) {
            $items[] = '-'.Html::encode($value);
        }

        return Html::tag('span', implode('', $items), [
            'class' => 'miss-history',
            'aria-label' => '遗漏记录，从左到右为最新到更早',
        ]);
    }

    /**
     * 把 `-` 分隔的遗漏记录拆成明细数组；已拆好的数组原样返回。
     * 列表页可按需截取最新若干条，避免单个单元格过长。
     */
    public static function splitRecords($records): array
    {
        if (is_array($records)) {
            $values = $records;
        } else {
            $values = preg_split('/\s*-\s*/', trim((string)$records, " \t\n\r\0\x0B-"), -1, PREG_SPLIT_NO_EMPTY);
        }

        return array_values(array_filter(array_map('strval', $values), static function ($value) {
            return $value !== '';
        }));
    }
}
