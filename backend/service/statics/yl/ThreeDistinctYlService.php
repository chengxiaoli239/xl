<?php

namespace backend\service\statics\yl;

/**
 * 三字不重复遗漏：从 0-9 选择 3 个不同数字，共 C(10, 3)=120 组。
 * 开奖前四位中包含这三个数字即命中，顺序不限，其他数字可以重复或存在。
 */
class ThreeDistinctYlService extends ThreeComboYlService
{
    public const TYPE = 9;
    public const ROW_VAL = 'three_distinct';

    public static function isHit(string $combination, array $drawCodes): bool
    {
        if (count($drawCodes) < 4 || strlen($combination) !== 3) {
            return false;
        }

        $drawDigits = array_values(array_unique(array_map('strval', array_slice($drawCodes, 0, 4))));
        if (count($drawDigits) < 3) {
            return false;
        }

        foreach (str_split($combination) as $digit) {
            if (!in_array($digit, $drawDigits, true)) {
                return false;
            }
        }

        return true;
    }

    protected static function matchingCombinations(array $drawCodes, array $hits): array
    {
        $drawDigits = array_values(array_unique(array_map('strval', array_slice($drawCodes, 0, 4))));
        if (count($drawDigits) < 3) {
            return [];
        }

        sort($drawDigits, SORT_NUMERIC);
        $matches = [];
        $count = count($drawDigits);
        for ($first = 0; $first < $count - 2; $first++) {
            for ($second = $first + 1; $second < $count - 1; $second++) {
                for ($third = $second + 1; $third < $count; $third++) {
                    $combination = $drawDigits[$first].$drawDigits[$second].$drawDigits[$third];
                    if (isset($hits[$combination])) {
                        $matches[] = $combination;
                    }
                }
            }
        }

        return $matches;
    }
}
