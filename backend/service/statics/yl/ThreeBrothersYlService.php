<?php

namespace backend\service\statics\yl;

/**
 * 三兄弟遗漏：统计相邻的三个数字组合，共 10 组，0/9 视为首尾相邻。
 * 开奖前四位去重后包含该三位数字即命中。
 */
class ThreeBrothersYlService extends ThreeDistinctYlService
{
    public const TYPE = 10;
    public const ROW_VAL = 'three_brothers';

    public static function getCombinations(): array
    {
        return [
            '012', '123', '234', '345', '456',
            '567', '678', '789', '019', '089',
        ];
    }

    public static function normalizeCodeFilter(string $value): array
    {
        return array_values(array_intersect(parent::normalizeCodeFilter($value), static::getCombinations()));
    }
}
