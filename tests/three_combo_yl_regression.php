<?php

require dirname(__DIR__).'/backend/service/statics/yl/ThreeComboYlService.php';

use backend\service\statics\yl\ThreeComboYlService;

function assertSameValue($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message.' expected='.var_export($expected, true).' actual='.var_export($actual, true).PHP_EOL);
        exit(1);
    }
}

$codes = ThreeComboYlService::getCombinations();
assertSameValue(120, count($codes), '组合总数错误');
assertSameValue('012', $codes[0], '首个组合错误');
assertSameValue('789', $codes[119], '最后组合错误');
assertSameValue(true, ThreeComboYlService::isHit('012', [0, 0, 1, 2]), '0012应命中012');
assertSameValue(true, ThreeComboYlService::isHit('012', [1, 1, 1, 1]), '1111应命中012');
assertSameValue(false, ThreeComboYlService::isHit('012', [0, 1, 2, 3]), '出现3不应命中012');
assertSameValue(['012', '789'], ThreeComboYlService::normalizeCodeFilter('210, 987'), '号码标准化错误');
assertSameValue(10000, ThreeComboYlService::normalizePeriods(20000), '期数上限错误');

$draws = [
    ['qihao' => '003', 'code1' => 0, 'code2' => 1, 'code3' => 2, 'code4' => 3],
    ['qihao' => '002', 'code1' => 2, 'code2' => 2, 'code3' => 0, 'code4' => 1],
    ['qihao' => '001', 'code1' => 1, 'code2' => 1, 'code3' => 1, 'code4' => 1],
];
$stats = ThreeComboYlService::calculate($draws, ['012']);
assertSameValue(1, $stats[0]['current_miss'], '当前遗漏错误');
assertSameValue(0, $stats[0]['last_time_miss'], '上次遗漏错误');
assertSameValue(2, $stats[0]['hit_count'], '命中次数错误');

assertSameValue(true, ThreeComboYlService::isHit('238', [3, 3, 8, 8]), '3388应命中238');
assertSameValue(true, ThreeComboYlService::isHit('368', [3, 3, 8, 8]), '3388应命中368');
assertSameValue(true, ThreeComboYlService::isHit('378', [3, 3, 8, 8]), '3388应命中378');
assertSameValue(true, ThreeComboYlService::isHit('389', [3, 3, 8, 8]), '3388应命中389');

$sharedMissDraws = [];
for ($position = 0; $position < 46; $position++) {
    $sharedMissDraws[] = ['qihao' => (string)(100 - $position), 'code1' => 0, 'code2' => 0, 'code3' => 0, 'code4' => 0];
}
$sharedMissDraws[] = ['qihao' => '054', 'code1' => 3, 'code2' => 3, 'code3' => 8, 'code4' => 8];
$sharedMissStats = ThreeComboYlService::calculate($sharedMissDraws, ['238', '368', '378', '389']);
foreach ($sharedMissStats as $row) {
    assertSameValue(46, $row['current_miss'], $row['code'].'应可与其他复式号码同时遗漏46期');
}

echo "three_combo_yl_regression: OK\n";
