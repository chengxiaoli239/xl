<?php

require dirname(__DIR__).'/backend/service/statics/yl/ThreeComboYlService.php';
require dirname(__DIR__).'/backend/service/statics/yl/TwoComboYlService.php';
require dirname(__DIR__).'/backend/service/statics/yl/FourSameYlService.php';

use backend\service\statics\yl\TwoComboYlService;
use backend\service\statics\yl\FourSameYlService;

function checkSame($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message.' expected='.var_export($expected, true).' actual='.var_export($actual, true));
    }
}

checkSame(45, count(TwoComboYlService::getCombinations()), '二字复式共45组');
checkSame('01', TwoComboYlService::getCombinations()[0], '二字复式首组保留0');
checkSame('89', TwoComboYlService::getCombinations()[44], '二字复式末组');
checkSame(['01', '89'], TwoComboYlService::normalizeCodeFilter('10，98,11,012,01'), '二字复式筛选标准化');
checkSame(['0000', '1111', '2222', '3333', '4444', '5555', '6666', '7777', '8888', '9999'], FourSameYlService::getCombinations(), '四重10组');
checkSame(['0000', '7777'], FourSameYlService::normalizeCodeFilter('0000，7777,0123,7,0000'), '四重筛选保留重复数字及0');

// 穷举前四位，01复式必须且仅能命中16种直码，0000四重只命中一种。
$pairHits = 0;
$sameHits = 0;
for ($number = 0; $number < 10000; $number++) {
    $code = str_pad((string)$number, 4, '0', STR_PAD_LEFT);
    $digits = str_split($code);
    $expectedPair = strspn($code, '01') === 4;
    checkSame($expectedPair, TwoComboYlService::isHit('01', $digits), '复式命中'.$code);
    checkSame($code === '0000', FourSameYlService::isHit('0000', $digits), '四重命中'.$code);
    $pairHits += (int)$expectedPair;
    $sameHits += (int)FourSameYlService::isHit('0000', $digits);
}
checkSame(16, $pairHits, '每组二字复式16种直码');
checkSame(1, $sameHits, '每组四重1种直码');
checkSame(false, FourSameYlService::isHit('0123', [0, 1, 2, 3]), '非四重不命中');
checkSame(false, FourSameYlService::isHit('0000', [0, 0, 0]), '不足四位不命中');
checkSame(false, TwoComboYlService::isHit('01', [0, 1, 0]), '复式不足四位不命中');

$draws = [];
foreach (['0123', '0000', '1100', '9999', '0000'] as $index => $code) {
    $digits = str_split($code);
    $draws[] = ['qihao' => (string)(100 - $index), 'code1' => $digits[0], 'code2' => $digits[1], 'code3' => $digits[2], 'code4' => $digits[3]];
}
$same = array_column(FourSameYlService::calculate($draws), null, 'code');
checkSame(1, $same['0000']['current_miss'], '0000当前遗漏');
checkSame(2, $same['0000']['last_time_miss'], '0000上次遗漏');
checkSame('2', $same['0000']['yl_records'], '遗漏记录');
checkSame(2, $same['0000']['hit_count'], '0000命中次数');
checkSame(3, $same['9999']['current_miss'], '9999当前遗漏');
checkSame(true, $same['1111']['is_window_limit'], '未命中只显示窗口下限');
$pair = array_column(TwoComboYlService::calculate($draws), null, 'code');
checkSame(1, $pair['01']['current_miss'], '01当前遗漏');
checkSame(3, $pair['01']['hit_count'], '01复式包括0000及1100');
checkSame('0-1', $pair['01']['yl_records'], '01遗漏最新在前');
checkSame(10, count(FourSameYlService::calculate([])), '四重空数据');
checkSame(45, count(TwoComboYlService::calculate([])), '复式空数据');

// 模拟最大10000期，验证有界计算成本。
$large = array_merge(...array_fill(0, 2000, $draws));
$start = microtime(true);
foreach ([FourSameYlService::class, TwoComboYlService::class] as $service) {
    checkSame(2000, $service::normalizePeriods(0), '默认2000期');
    checkSame(10000, $service::normalizePeriods(20001), '最大10000期');
    $service::calculate($large);
}
echo 'two_combo_four_same_yl_regression: OK; 10000 periods '.round(microtime(true) - $start, 3).'s; peak '.round(memory_get_peak_usage(true) / 1048576, 1)."MiB\n";
