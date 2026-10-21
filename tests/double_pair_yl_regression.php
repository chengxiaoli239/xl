<?php

require dirname(__DIR__).'/backend/service/statics/yl/ThreeComboYlService.php';
require dirname(__DIR__).'/backend/service/statics/yl/DoublePairYlService.php';

use backend\service\statics\yl\DoublePairYlService;

function checkEqual($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message.': '.var_export($actual, true));
    }
}

$codes = DoublePairYlService::getCombinations();
checkEqual(10000, DoublePairYlService::normalizePeriods(0), 'default periods');
checkEqual(10000, DoublePairYlService::normalizePeriods(20000), 'max periods');
checkEqual(2000, DoublePairYlService::normalizePeriods(2000), 'custom periods');
checkEqual(45, count($codes), '45 pairs');
checkEqual('0011', $codes[0], 'leading zeros');
checkEqual('8899', $codes[44], 'last pair');
checkEqual(['0011', '8899'], DoublePairYlService::normalizeCodeFilter('1010，0011,9898,0000,0001,0123,01'), 'normalize permutations and reject invalid pairs');
checkEqual(false, DoublePairYlService::isHit('0000', [0, 0, 0, 0]), 'quadruple excluded');
checkEqual(false, DoublePairYlService::isHit('0011', [0, 0, 0, 1]), 'triple excluded');
checkEqual(false, DoublePairYlService::isHit('0011', [0, 0, 1]), 'require four positions');
checkEqual(false, DoublePairYlService::isHit('0011', [2, 3, 4, 5, 0, 0, 1, 1]), 'ignore fifth and later positions');

$draws = [];
$firstHit = [];
$hitCounts = array_fill_keys($codes, 0);
$start = microtime(true);
for ($number = 0; $number < 10000; $number++) {
    $digits = str_split(str_pad((string)$number, 4, '0', STR_PAD_LEFT));
    foreach ($codes as $code) {
        $a = 0;
        $b = 0;
        foreach ($digits as $digit) {
            $a += (int)($digit === $code[0]);
            $b += (int)($digit === $code[2]);
        }
        $hit = $a === 2 && $b === 2;
        checkEqual($hit, DoublePairYlService::isHit($code, $digits), 'draw '.$number.' '.$code);
        if ($hit) {
            $hitCounts[$code]++;
            $firstHit[$code] = $firstHit[$code] ?? $number;
        }
    }
    $draws[] = ['qihao' => (string)$number, 'code1' => $digits[0], 'code2' => $digits[1], 'code3' => $digits[2], 'code4' => $digits[3]];
}
foreach (DoublePairYlService::calculate($draws) as $row) {
    checkEqual(6, $row['hit_count'], 'six permutations');
    checkEqual($hitCounts[$row['code']], $row['hit_count'], 'optimized count');
    checkEqual($firstHit[$row['code']], $row['current_miss'], 'optimized omission');
}
$sample = [];
foreach (['0000', '1010', '0001', '1100', '0011'] as $index => $code) {
    $sample[] = ['qihao' => (string)(5 - $index), 'code1' => $code[0], 'code2' => $code[1], 'code3' => $code[2], 'code4' => $code[3]];
}
$row = DoublePairYlService::calculate($sample, ['0011'])[0];
checkEqual(1, $row['current_miss'], 'current miss');
checkEqual(3, $row['hit_count'], 'hits excluding triple and quadruple');
checkEqual('1-0', $row['yl_records'], 'newest history first');
checkEqual(1, $row['last_time_miss'], 'last miss');
checkEqual(1, $row['max_miss'], 'max miss');
$empty = DoublePairYlService::calculate($sample, ['8899'])[0];
checkEqual(5, $empty['current_miss'], 'window miss');
checkEqual(true, $empty['is_window_limit'], 'window marker');
echo 'double_pair_yl_regression: OK; 10000 draws '.round(microtime(true) - $start, 3).'s; peak '.round(memory_get_peak_usage(true) / 1048576, 1)."MiB\n";
