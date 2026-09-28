<?php

namespace backend\service\statics\yl;

/** 五字全倒：前四位为所选五个数字中的四个不同数字，顺序不限。 */
class FiveAllReverseYlService extends FiveComboYlService
{
    public const TYPE = 8;
    public const ROW_VAL = 'five_all_reverse';

    public static function isHit(string $combination, array $drawCodes): bool
    {
        $drawCode = self::canonicalDrawCode($drawCodes);
        if ($drawCode === null || strlen($combination) !== 5) {
            return false;
        }
        foreach (str_split($drawCode) as $digit) {
            if (strpos($combination, $digit) === false) {
                return false;
            }
        }
        return true;
    }

    protected static function matchingCombinations(array $drawCodes, array $hits): array
    {
        $drawCode = self::canonicalDrawCode($drawCodes);
        if ($drawCode === null) {
            return [];
        }
        $matches = [];
        for ($digit = 0; $digit <= 9; $digit++) {
            if (strpos($drawCode, (string)$digit) !== false) {
                continue;
            }
            $code = str_split($drawCode.$digit);
            sort($code, SORT_NUMERIC);
            $code = implode('', $code);
            if (isset($hits[$code])) {
                $matches[] = $code;
            }
        }
        return $matches;
    }

    private static function canonicalDrawCode(array $drawCodes): ?string
    {
        if (count($drawCodes) !== 4) {
            return null;
        }
        $digits = array_map('strval', $drawCodes);
        foreach ($digits as $digit) {
            if (!preg_match('/^[0-9]$/', $digit)) {
                return null;
            }
        }
        if (count(array_unique($digits)) !== 4) {
            return null;
        }
        sort($digits, SORT_NUMERIC);
        return implode('', $digits);
    }
}
