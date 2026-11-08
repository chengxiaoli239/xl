<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/common/config/bootstrap.php';

use backend\service\numbers\DynamicFilterService;
use backend\service\numbers\DynamicType2Service;

$option = array_column(DynamicFilterService::DYNAMIC_FILTER_TYPES, null, 'type')[47];
if ($option['playway'] !== [1, 2, 3] || $option['params']['z'] !== '1') {
    throw new RuntimeException('类型47入口或默认模式错误');
}

$db = new yii\db\Connection(['dsn' => 'sqlite::memory:']);
$db->open();
$db->createCommand('CREATE TABLE lt_num4_type (code TEXT, code_type INTEGER, code_1 TEXT, code_2 TEXT, code_3 TEXT, code_4 TEXT)')->execute();
$pool = ['0', '1', '2'];
$outside = ['3', '4', '5', '6', '7', '8', '9'];

foreach ([[3, [], 2401], [2, ['4'], 343], [1, ['3', '4'], 49]] as [$playway, $fixed, $expected]) {
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

    $pools = [1 => $pool, 2 => $pool, 3 => $pool, 4 => $pool];
    foreach ($fixed as $position) {
        unset($pools[(int)$position]);
    }
    $query = (new yii\db\Query())->select(['code'])->from('lt_num4_type')
        ->where(['code_type' => $playway + 1]);
    foreach (DynamicType2Service::filter44PositionWhere($playway, $pools, $fixed, 2) as $condition) {
        $query->andWhere($condition);
    }
    $codes = $query->column($db);
    if (count($codes) !== $expected || !in_array(str_repeat('3', $playway + 1).str_repeat('X', 3 - $playway), $codes, true)) {
        throw new RuntimeException('类型47排除组数或号码错误，playway='.$playway.' count='.count($codes));
    }
    foreach ($codes as $code) {
        foreach (str_split($code) as $digit) {
            if ($digit !== 'X' && !in_array($digit, $outside, true)) {
                throw new RuntimeException('类型47排除模式仍包含近期数字');
            }
        }
    }
    $db->createCommand()->delete('lt_num4_type')->execute();
}

// 二定单来源位共用号码池，排除后的两个投注位各剩7个数字。
$conditions = DynamicType2Service::filter44TwoFixedWhere(['3', '4'], $pool, 2);
if ($conditions !== [
    ['NOT IN', 'code_1', $pool], ['NOT IN', 'code_2', $pool],
    ['=', 'code_3', 'X'], ['=', 'code_4', 'X'],
]) {
    throw new RuntimeException('二定共用来源位的排除条件错误');
}
$fixedXTake = DynamicType2Service::filter44PositionWhere(1, [2 => $pool], ['1', '3'], 1);
if ($fixedXTake !== [
    ['=', 'code_1', 'X'], ['=', 'code_3', 'X'], ['IN', 'code_2', $pool],
]) {
    throw new RuntimeException('二定定X位置过滤条件生成错误');
}
$take = DynamicType2Service::filter44PositionWhere(3, [1 => $pool]);
if ($take !== [['IN', 'code_1', $pool]]) {
    throw new RuntimeException('旧类型44取号码的条件改变');
}
foreach ([['x'=>'1234', 'y'=>'0'], ['x'=>'1234', 'y'=>'10'], ['x'=>'15', 'y'=>'3'],
    ['x'=>'11', 'y'=>'3'], ['x'=>'1234', 'y'=>'3', 'z'=>'3']] as $params) {
    try {
        DynamicType2Service::filter47((object)['playway'=>3], ['params'=>$params]);
        throw new RuntimeException('类型47错误接受无效参数');
    } catch (InvalidArgumentException $expectedError) {
    }
}

echo "filter47_position_mode_regression: OK (take, exclude, 2/3/4-fixed)\n";
