<?php

require dirname(__DIR__).'/backend/service/statics/yl/ThreeComboYlService.php';
require dirname(__DIR__).'/backend/service/statics/yl/ThreeSameYlService.php';

use backend\service\statics\yl\ThreeSameYlService;

function assertEqual($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message.': '.var_export($actual, true));
    }
}

assertEqual(['000', '111', '222', '333', '444', '555', '666', '777', '888', '999'], ThreeSameYlService::getCombinations(), '10 groups');
assertEqual(10000, ThreeSameYlService::normalizePeriods(0), 'default periods');
assertEqual(10000, ThreeSameYlService::normalizePeriods(20000), 'max periods');
assertEqual(2000, ThreeSameYlService::normalizePeriods(2000), 'custom periods');
assertEqual(2000, \backend\service\statics\yl\ThreeComboYlService::normalizePeriods(0), 'other statistics default');
assertEqual(['000', '999'], ThreeSameYlService::normalizeCodeFilter('000，999,012,0000,000'), 'filter');
assertEqual(false, ThreeSameYlService::isHit('000', [0, 0, 0]), 'require four positions');
assertEqual(false, ThreeSameYlService::isHit('012', [0, 0, 0, 1]), 'reject non-triple code');
assertEqual(false, ThreeSameYlService::isHit('000', [1, 2, 3, 4, 0, 0, 0]), 'ignore later positions');

$draws = [];
$referenceHits = array_fill_keys(ThreeSameYlService::getCombinations(), 0);
$referenceFirst = [];
$start = microtime(true);
for ($number = 0; $number < 10000; $number++) {
    $digits = str_split(str_pad((string)$number, 4, '0', STR_PAD_LEFT));
    foreach (ThreeSameYlService::getCombinations() as $code) {
        $count = 0;
        foreach ($digits as $digit) {
            $count += (int)($digit === $code[0]);
        }
        $hit = $count >= 3;
        assertEqual($hit, ThreeSameYlService::isHit($code, $digits), 'all draws '.$number.' '.$code);
        if ($hit) {
            $referenceHits[$code]++;
            $referenceFirst[$code] = $referenceFirst[$code] ?? $number;
        }
    }
    $draws[] = ['qihao' => (string)$number, 'code1' => $digits[0], 'code2' => $digits[1], 'code3' => $digits[2], 'code4' => $digits[3]];
}
foreach (ThreeSameYlService::calculate($draws) as $row) {
    assertEqual(37, $row['hit_count'], '36 triples plus one quadruple');
    assertEqual($referenceHits[$row['code']], $row['hit_count'], 'optimized hit count');
    assertEqual($referenceFirst[$row['code']], $row['current_miss'], 'optimized current miss');
}
$sample = [
    ['qihao' => '5', 'code1' => 0, 'code2' => 0, 'code3' => 1, 'code4' => 1],
    ['qihao' => '4', 'code1' => 0, 'code2' => 0, 'code3' => 0, 'code4' => 0],
    ['qihao' => '3', 'code1' => 0, 'code2' => 1, 'code3' => 0, 'code4' => 0],
    ['qihao' => '2', 'code1' => 1, 'code2' => 2, 'code3' => 3, 'code4' => 4],
    ['qihao' => '1', 'code1' => 1, 'code2' => 0, 'code3' => 0, 'code4' => 0],
];
$row = ThreeSameYlService::calculate($sample, ['000'])[0];
assertEqual(1, $row['current_miss'], 'current miss');
assertEqual(3, $row['hit_count'], 'quadruple included');
assertEqual('0-1', $row['yl_records'], 'history newest first');
assertEqual(0, $row['last_time_miss'], 'last completed miss');
assertEqual(1, $row['max_miss'], 'max completed miss');
$empty = ThreeSameYlService::calculate($sample, ['999'])[0];
assertEqual(5, $empty['current_miss'], 'window-limited miss');
assertEqual(true, $empty['is_window_limit'], 'window marker');
echo 'three_same_yl_regression: OK; 10000 draws '.round(microtime(true) - $start, 3).'s; peak '.round(memory_get_peak_usage(true) / 1048576, 1)."MiB\n";
