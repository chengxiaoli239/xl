<?php

namespace backend\service\statics\yl;

/** 四重：0000至9999的10组四位同号，分别统计前四位遗漏。 */
class FourSameYlService extends ThreeComboYlService
{
    public const TYPE = 11;
    public const ROW_VAL = 'four_same';
    public const COMBINATION_LENGTH = 4;

    public static function getCombinations(): array
    {
        $codes = [];
        for ($digit = 0; $digit <= 9; $digit++) {
            $codes[] = str_repeat((string)$digit, 4);
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
        return count($drawCodes) >= 4
            && preg_match('/^([0-9])\1{3}$/', $combination) === 1
            && implode('', array_slice($drawCodes, 0, 4)) === $combination;
    }

    protected static function matchingCombinations(array $drawCodes, array $hits): array
    {
        $code = implode('', array_slice($drawCodes, 0, 4));
        return isset($hits[$code]) && static::isHit($code, $drawCodes) ? [$code] : [];
    }
}
