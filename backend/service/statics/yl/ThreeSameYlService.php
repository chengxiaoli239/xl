<?php

namespace backend\service\statics\yl;

/** 三重：前四位中所选数字至少出现三次，包含四重，与原三重口径一致。 */
class ThreeSameYlService extends ThreeComboYlService
{
    public const TYPE = 13;
    public const DEFAULT_PERIODS = 10000;
    public const ROW_VAL = 'three_same';
    public const COMBINATION_LENGTH = 3;

    public static function getCombinations(): array
    {
        $codes = [];
        for ($digit = 0; $digit <= 9; $digit++) {
            $codes[] = str_repeat((string)$digit, 3);
        }
        return $codes;
    }

    public static function normalizeCodeFilter(string $value): array
    {
        $codes = preg_split('/[\s,，]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_intersect($codes, static::getCombinations()), SORT_STRING));
    }

    public static function isHit(string $combination, array $drawCodes): bool
    {
        if (count($drawCodes) < 4 || preg_match('/^([0-9])\1{2}$/', $combination) !== 1) {
            return false;
        }
        $counts = array_count_values(array_slice($drawCodes, 0, 4));
        return ($counts[$combination[0]] ?? 0) >= 3;
    }

    protected static function matchingCombinations(array $drawCodes, array $hits): array
    {
        if (count($drawCodes) < 4) {
            return [];
        }
        foreach (array_count_values(array_slice($drawCodes, 0, 4)) as $digit => $count) {
            $code = str_repeat((string)$digit, 3);
            if ($count >= 3 && isset($hits[$code])) {
                return [$code];
            }
        }
        return [];
    }
}
