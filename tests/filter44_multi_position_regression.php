<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/common/config/bootstrap.php';

use backend\service\numbers\DynamicFilterService;
use backend\service\numbers\DynamicType2Service;

$option = array_column(DynamicFilterService::DYNAMIC_FILTER_TYPES, null, 'type')[44];
if ($option['playway'] !== [1, 2, 3]) {
    throw new RuntimeException('类型44必须对二、三、四定可用');
}

$db = new yii\db\Connection(['dsn' => 'sqlite::memory:']);
$db->open();
$db->createCommand('CREATE TABLE lt_num4_type (code TEXT, code_type INTEGER, code_1 TEXT, code_2 TEXT, code_3 TEXT, code_4 TEXT)')->execute();
$pools = [2 => ['0', '1', '2', '3', '4', '5', '6', '7', '8'],
    4 => ['1', '2', '3', '4', '5', '6', '7', '8', '9']];

foreach ([
    [1, ['1', '3'], 81, 'X0X9', 'X9X0'],
    [2, ['1'], 810, 'X009', 'X090'],
    [3, [], 8100, '0009', '0900'],
] as [$playway, $fixed, $expected, $included, $excluded]) {
    $rows = [];
    for ($a = 0; $a < 10; $a++) {
        for ($b = 0; $b < 10; $b++) {
            for ($c = 0; $c < 10; $c++) {
                for ($d = 0; $d < 10; $d++) {
                    $digits = [(string)$a, (string)$b, (string)$c, (string)$d];
                    foreach ($fixed as $position) {
                        $digits[(int)$position - 1] = 'X';
                    }
                    $code = implode('', $digits);
                    $rows[$code] = array_merge([$code, $playway + 1], $digits);
                }
            }
        }
    }
    $db->createCommand()->batchInsert('lt_num4_type',
        ['code', 'code_type', 'code_1', 'code_2', 'code_3', 'code_4'], array_values($rows))->execute();
    $query = (new yii\db\Query())->select(['code'])->from('lt_num4_type')
        ->where(['code_type' => $playway + 1]);
    foreach (DynamicType2Service::filter44PositionWhere($playway, $pools, $fixed) as $condition) {
        $query->andWhere($condition);
    }
    $actual = $query->column($db);
    if (count($actual) !== $expected || !in_array($included, $actual, true)
        || in_array($excluded, $actual, true)) {
        throw new RuntimeException('类型44多位置筛选不正确，playway='.$playway.' count='.count($actual));
    }
    $db->createCommand()->delete('lt_num4_type')->execute();
}

// 二、三定未勾选定X时，旧配置仍容许X通过；四定只容许真实数字。
$twoLegacy = DynamicType2Service::filter44PositionWhere(1, [2 => $pools[2]]);
if ($twoLegacy !== [['OR', ['=', 'code_2', 'X'], ['IN', 'code_2', $pools[2]]]]) {
    throw new RuntimeException('二定旧配置过滤方式改变');
}
try {
    DynamicType2Service::filter44PositionWhere(3, [2 => []]);
    throw new RuntimeException('空号码池应停止过滤');
} catch (InvalidArgumentException $expected) {
}

echo "filter44_multi_position_regression: OK (2/3/4-fixed, independent pools)\n";
