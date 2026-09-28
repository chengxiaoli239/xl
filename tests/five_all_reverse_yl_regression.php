<?php

require dirname(__DIR__).'/backend/service/statics/yl/ThreeComboYlService.php';
require dirname(__DIR__).'/backend/service/statics/yl/FiveComboYlService.php';
require dirname(__DIR__).'/backend/service/statics/yl/FiveAllReverseYlService.php';

use backend\service\statics\yl\FiveAllReverseYlService;
use backend\service\statics\yl\FiveComboYlService;

function assertEqual($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message.' expected='.var_export($expected, true).' actual='.var_export($actual, true).PHP_EOL);
        exit(1);
    }
}

$combinations = FiveAllReverseYlService::getCombinations();
assertEqual(252, count($combinations), '组合数量');
assertEqual('01234', $combinations[0], '首组号码');
assertEqual('56789', $combinations[251], '末组号码');
assertEqual(['01234', '56789'], FiveAllReverseYlService::normalizeCodeFilter('43210,98765'), '号码筛选');
assertEqual(10000, FiveAllReverseYlService::normalizePeriods(20000), '期数上限');
assertEqual(true, FiveAllReverseYlService::isHit('01234', [4, 3, 0, 2]), '任选四个数字且顺序不限');
assertEqual(false, FiveAllReverseYlService::isHit('01234', [0, 0, 1, 2]), '重复数字不算五字全倒');
assertEqual(false, FiveAllReverseYlService::isHit('01234', [0, 1, 2, 5]), '出现组合外数字不能命中');
assertEqual(false, FiveAllReverseYlService::isHit('01234', [0, 1, 2]), '不足四位不能命中');
assertEqual(true, FiveComboYlService::isHit('01234', [0, 0, 1, 2]), '五字复式仍按前四位命中');

$draws = [
    ['qihao' => '004', 'code1' => 0, 'code2' => 0, 'code3' => 1, 'code4' => 2],
    ['qihao' => '003', 'code1' => 4, 'code2' => 3, 'code3' => 0, 'code4' => 2],
    ['qihao' => '002', 'code1' => 1, 'code2' => 2, 'code3' => 3, 'code4' => 5],
    ['qihao' => '001', 'code1' => 0, 'code2' => 1, 'code3' => 2, 'code4' => 3],
];
$stats = FiveAllReverseYlService::calculate($draws, ['01234', '12345']);
assertEqual('01234', $stats[0]['code'], '首组顺序');
assertEqual(1, $stats[0]['current_miss'], '当前遗漏');
assertEqual(1, $stats[0]['last_time_miss'], '上次遗漏');
assertEqual(2, $stats[0]['hit_count'], '命中次数');
assertEqual(2, $stats[1]['current_miss'], '第二组当前遗漏');
assertEqual(1, $stats[1]['hit_count'], '第二组命中次数');

$allStats = FiveAllReverseYlService::calculate([
    ['qihao' => '001', 'code1' => 3, 'code2' => 2, 'code3' => 1, 'code4' => 0],
]);
$hitCodes = array_column(array_filter($allStats, static function ($row) {
    return $row['hit_count'] === 1;
}), 'code');
assertEqual(['01234', '01235', '01236', '01237', '01238', '01239'], array_values($hitCodes), '四个不同数字应命中六组五字全倒');

echo "five_all_reverse_yl_regression: OK\n";
