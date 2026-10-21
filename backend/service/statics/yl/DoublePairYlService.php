<?php

namespace backend\service\statics\yl;

/** 双双重：前四位为两个不同数字各出现两次，不含三重、四重。 */
class DoublePairYlService extends ThreeComboYlService
{
    public const TYPE = 14;
    public const DEFAULT_PERIODS = 10000;
    public const ROW_VAL = 'double_pair';
    public const COMBINATION_LENGTH = 4;

    public static function getCombinations(): array
    {
        $codes = [];
        for ($first = 0; $first < 9; $first++) {
            for ($second = $first + 1; $second <= 9; $second++) {
                $codes[] = str_repeat((string)$first, 2).str_repeat((string)$second, 2);
            }
        }
        return $codes;
    }

    public static function normalizeCodeFilter(string $value): array
    {
        $valid = array_fill_keys(static::getCombinations(), true);
        $codes = [];
        foreach (preg_split('/[\s,，]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) as $code) {
            $digits = str_split($code);
            sort($digits, SORT_STRING);
            $code = implode('', $digits);
            if (isset($valid[$code])) {
                $codes[] = $code;
            }
        }
        return array_values(array_unique($codes, SORT_STRING));
    }

    public static function isHit(string $combination, array $drawCodes): bool
    {
        if (count($drawCodes) < 4 || preg_match('/^([0-9])\1([0-9])\2$/', $combination) !== 1
            || $combination[0] >= $combination[2]) {
            return false;
        }
        $digits = array_slice($drawCodes, 0, 4);
        sort($digits, SORT_STRING);
        return implode('', $digits) === $combination;
    }

    protected static function matchingCombinations(array $drawCodes, array $hits): array
    {
        if (count($drawCodes) < 4) {
            return [];
        }
        $digits = array_slice($drawCodes, 0, 4);
        sort($digits, SORT_STRING);
        $code = implode('', $digits);
        return isset($hits[$code]) ? [$code] : [];
    }
}
