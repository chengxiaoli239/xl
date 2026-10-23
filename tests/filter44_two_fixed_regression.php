<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/common/config/bootstrap.php';

use backend\service\numbers\DynamicType2Service;

function same44($expected, $actual, string $description): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($description.': '.var_export($actual, true));
    }
}

$pool = ['0', '1', '2', '3', '4', '5', '6', '7'];
$db = new yii\db\Connection(['dsn' => 'sqlite::memory:']);
$db->open();
$db->createCommand('CREATE TABLE lt_num4_type (code TEXT, code_type INTEGER, code_1 TEXT, code_2 TEXT, code_3 TEXT, code_4 TEXT)')->execute();
$fixedCases = [
    ['3', '4'], ['1', '2'], ['1', '3'], ['1', '4'], ['2', '3'], ['2', '4'],
];
foreach ($fixedCases as $fixed) {
    $conditions = DynamicType2Service::filter44TwoFixedWhere($fixed, $pool);
    same44(4, count($conditions), 'four position conditions');
    $rows = [];
    $betPositions = array_values(array_diff([1, 2, 3, 4], array_map('intval', $fixed)));
    for ($first = 0; $first <= 9; $first++) {
        for ($second = 0; $second <= 9; $second++) {
            $digits = ['X', 'X', 'X', 'X'];
            $digits[$betPositions[0] - 1] = (string)$first;
            $digits[$betPositions[1] - 1] = (string)$second;
            $rows[] = array_merge([implode('', $digits), 2], $digits);
        }
    }
    $db->createCommand()->batchInsert('lt_num4_type', ['code', 'code_type', 'code_1', 'code_2', 'code_3', 'code_4'], $rows)->execute();
    $query = (new yii\db\Query())->from('lt_num4_type')->where(['code_type' => 2]);
    foreach ($conditions as $condition) {
        $query->andWhere($condition);
    }
    same44(64, (int)$query->count('*', $db), 'SQL query returns 64 codes');
    $db->createCommand()->delete('lt_num4_type')->execute();
    $allowed = [];
    foreach ($conditions as $condition) {
        $position = (int)substr($condition[1], -1);
        $allowed[$position] = $condition[0] === 'IN' ? $condition[2] : [$condition[2]];
    }
    $count = 0;
    foreach (['X', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'] as $a) {
        foreach (['X', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'] as $b) {
            foreach (['X', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'] as $c) {
                foreach (['X', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'] as $d) {
                    $digits = [$a, $b, $c, $d];
                    if (count(array_filter($digits, static function ($digit): bool { return $digit === 'X'; })) !== 2) {
                        continue;
                    }
                    if (in_array($a, $allowed[1], true) && in_array($b, $allowed[2], true)
                        && in_array($c, $allowed[3], true) && in_array($d, $allowed[4], true)) {
                        $count++;
                    }
                }
            }
        }
    }
    same44(64, $count, '8x8 combinations for X positions '.implode(',', $fixed));
}
$conditions = DynamicType2Service::filter44TwoFixedWhere(['3', '4'], $pool);
same44(['IN', 'code_1', $pool], $conditions[0], 'source pool applies to thousand');
same44(['IN', 'code_2', $pool], $conditions[1], 'same source pool applies to hundred');
same44(['=', 'code_3', 'X'], $conditions[2], 'tens fixed X');
same44(['=', 'code_4', 'X'], $conditions[3], 'ones fixed X');
foreach ([['3'], ['3', '3'], ['3', '5']] as $invalidFixed) {
    try {
        DynamicType2Service::filter44TwoFixedWhere($invalidFixed, $pool);
        throw new RuntimeException('invalid X positions unexpectedly accepted');
    } catch (InvalidArgumentException $expected) {
    }
}
try {
    DynamicType2Service::filter44TwoFixedWhere(['3', '4'], []);
    throw new RuntimeException('empty source pool unexpectedly accepted');
} catch (RuntimeException $expected) {
    if ($expected->getMessage() === 'empty source pool unexpectedly accepted') {
        throw $expected;
    }
}
echo "filter44_two_fixed_regression: OK (six X-position pairs, 64 codes each)\n";
