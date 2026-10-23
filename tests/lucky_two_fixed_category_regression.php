<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/common/config/bootstrap.php';

use backend\service\Lucky5\Lucky5Service;
use backend\service\Lucky5\LuckyBaseService;

function sameCategory($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message.': expected '.$expected.', actual '.$actual);
    }
}

// Use synthetic numbers only; never construct an account or send a request.
foreach ([[1, 2], [1, 3], [1, 4], [2, 3], [2, 4], [3, 4]] as $fixed) {
    $betPositions = array_values(array_diff([1, 2, 3, 4], $fixed));
    $codes = [];
    for ($a = 0; $a < 8; $a++) {
        for ($b = 0; $b < 8; $b++) {
            $digits = ['X', 'X', 'X', 'X'];
            $digits[$betPositions[0] - 1] = (string)$a;
            $digits[$betPositions[1] - 1] = (string)$b;
            $codes[] = implode('', $digits);
        }
    }
    foreach (Lucky5Service::splitCodes($codes, 50) as $batch) {
        sameCategory(20, LuckyBaseService::getNumType('30', '1', $batch), 'two-fixed quick-select request category');
        sameCategory(102, Lucky5Service::getWay(30), 'quick-select request method stays unchanged');
        sameCategory(0, preg_match('/,,|^,|,$/', implode(',', $batch)), 'request has no empty numbers');
        foreach ($batch as $code) {
            sameCategory(2, substr_count($code, 'X'), 'two-fixed number has two X positions');
            sameCategory(4, strlen($code), 'two-fixed number stays four characters');
        }
    }
    sameCategory(64, count($codes), 'eight by eight pool');
}

// Existing categories outside two-fixed quick select must remain unchanged.
foreach ([[30, 2, ['12X3'], 30], [29, 2, ['12X3'], 30], [20, 3, ['1234'], 40],
    [27, 1, ['1XXX2'], 30], [27, 1, ['12XX'], 40],
    [31, 1, ['1XXX2'], 40], [33, 1, ['12XX'], 40],
    [36, 6, ['12'], 21], [17, 6, ['123'], 31], [37, 6, ['1234'], 41]] as $case) {
    sameCategory($case[3], LuckyBaseService::getNumType($case[0], $case[1], $case[2]), 'existing category for type '.$case[0]);
}

echo "lucky_two_fixed_category_regression: OK (six position pairs, split requests, existing categories)\n";
