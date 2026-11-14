<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/common/config/bootstrap.php';

use backend\service\Lucky5\Lucky5Service;

// Synthetic numbers only: no plan, database, or platform request is used.
$valid = [
    [1, ['12XX', '1,X,3,X'], ['12XX', '1X3X']],
    [2, ['123X', 'X,2,3,4'], ['123X', 'X234']],
    [3, ['1234', '7,4,9,3'], ['1234', '7493']],
];
foreach ($valid as [$playway, $input, $expected]) {
    $actual = Lucky5Service::normalizePlatformTxtCodes($input, $playway);
    if ($actual !== $expected) {
        throw new RuntimeException('TXT 号码标准化结果不符，玩法='.$playway);
    }
}

foreach ([[1, ['123X']], [2, ['1234']], [3, ['12XX']], [3, ['12345']], [4, ['1234']]] as $invalid) {
    if (Lucky5Service::normalizePlatformTxtCodes($invalid[1], $invalid[0]) !== []) {
        throw new RuntimeException('TXT 号码错误地接受了不匹配玩法');
    }
}

echo "lucky_txt_import_codes_regression: OK\n";
