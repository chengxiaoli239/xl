<?php

namespace backend\helpers;

use yii\helpers\Html;

class MissHistoryFormatter
{
    public static function render($currentMiss, $records = '', string $currentDisplay = '', bool $recordsIncludeCurrent = false): string
    {
        $currentDisplay = $currentDisplay !== '' ? $currentDisplay : (string)$currentMiss;
        $history = self::splitRecords($records);
        if ($recordsIncludeCurrent && isset($history[0]) && (string)$history[0] === (string)$currentMiss) {
            array_shift($history);
        }

        $items = [
            Html::tag('span',
                Html::tag('small', '最新', ['class' => 'miss-history__latest-label'])
                .Html::tag('strong', Html::encode($currentDisplay), ['class' => 'miss-history__value']),
                [
                    'class' => 'miss-history__item miss-history__item--latest',
                    'title' => '最新遗漏：'.$currentDisplay,
                ]
            ),
        ];

        foreach ($history as $value) {
            $items[] = Html::tag('span', Html::encode($value), [
                'class' => 'miss-history__item',
                'title' => '历史遗漏：'.$value,
            ]);
        }

        return Html::tag('div', implode('', $items), [
            'class' => 'miss-history',
            'aria-label' => '遗漏记录，从左到右为最新到更早',
        ]);
    }

    private static function splitRecords($records): array
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
