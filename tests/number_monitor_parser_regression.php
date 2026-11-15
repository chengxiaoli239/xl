<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__) . '/common/config/bootstrap.php';

use common\service\numberMonitor\NumberSchemeParser;

$content = "\xEF\xBB\xBF1234 4567 8923\r\n\r\n1,3,5,6 0,3,6,1 9,1,4,5\r\n\r\n12XX 23XX 89XX\r\n\r\n234X 890X 234X 892X 235X";
$schemes = NumberSchemeParser::parse($content);
if (count($schemes) !== 4) {
    throw new RuntimeException('blank-line scheme grouping failed');
}
if ($schemes[0]['fixed_count'] !== 4 || $schemes[0]['patterns'] !== ['1234', '4567', '8923']) {
    throw new RuntimeException('four-fixed pattern parsing failed');
}
if ($schemes[1]['patterns'] !== ['1356', '0361', '9145']) {
    throw new RuntimeException('comma-separated digit parsing failed');
}
$spacedCommaSchemes = NumberSchemeParser::parse("1, 3, 5, 6 0, 3, 6, 1\n\n12XX 23XX");
if ($spacedCommaSchemes[0]['patterns'] !== ['1356', '0361']) {
    throw new RuntimeException('comma-separated digits with spaces failed');
}
if ($schemes[2]['fixed_count'] !== 2 || $schemes[2]['patterns'] !== ['12XX', '23XX', '89XX']) {
    throw new RuntimeException('two-fixed pattern parsing failed');
}
if ($schemes[3]['fixed_count'] !== 3 || $schemes[3]['pattern_count'] !== 5
    || $schemes[3]['patterns'] !== ['234X', '890X', '234X', '892X', '235X']) {
    throw new RuntimeException('three-fixed pattern parsing or duplicate preservation failed');
}

$allFourDigitPatterns = [];
for ($number = 0; $number < 10000; $number++) {
    $allFourDigitPatterns[] = str_pad((string)$number, 4, '0', STR_PAD_LEFT);
}
$commaSeparatedPatterns = array_map(function ($pattern) {
    return implode(',', str_split($pattern));
}, $allFourDigitPatterns);
$largeScheme = implode(' ', $commaSeparatedPatterns);
$largeContent = implode("\n\n", array_fill(0, 10, $largeScheme));
$largeSchemes = NumberSchemeParser::parse($largeContent);
if (count($largeSchemes) !== 10 || $largeSchemes[0]['pattern_count'] !== 10000
    || array_sum(array_column($largeSchemes, 'pattern_count')) !== 100000) {
    throw new RuntimeException('large schemes and files must accept the 100000 pattern limit');
}
try {
    NumberSchemeParser::parse($largeScheme . ' ' . $allFourDigitPatterns[0]);
    throw new RuntimeException('per-scheme pattern limit must be enforced');
} catch (InvalidArgumentException $expected) {
}
try {
    NumberSchemeParser::parse(implode("\n\n", array_fill(0, 11, '1234')));
    throw new RuntimeException('scheme count limit must be enforced');
} catch (InvalidArgumentException $expected) {
}

foreach ([
    ['12345', 'each pattern must be exactly four positions'],
    ['XXXX', 'all wildcard patterns must be rejected'],
    ['1XXX', 'single-fixed plays are not supported'],
    ["1234\n12XX", 'mixed fixed counts in one scheme must be rejected'],
    ['12XZ', 'unsupported pattern characters must be rejected'],
    ['1234 12345', 'malformed extra number must be rejected'],
    ['1234 123', 'short extra number must be rejected'],
] as [$invalid, $message]) {
    try {
        NumberSchemeParser::parse($invalid);
        throw new RuntimeException($message);
    } catch (InvalidArgumentException $expected) {
    }
}

if (!NumberSchemeParser::matchesAny(['12XX'], '1,2,9,8')) {
    throw new RuntimeException('two-fixed position match failed');
}
if (!NumberSchemeParser::matchesAny(['X234'], '9,2,3,4')) {
    throw new RuntimeException('wildcard position match failed');
}
if (NumberSchemeParser::matchesAny(['12XX'], '2,1,9,8')) {
    throw new RuntimeException('fixed-position mismatch was counted as a hit');
}
if (NumberSchemeParser::advance(1, 3, false) !== [2, 1]
    || NumberSchemeParser::advance(2, 2, false) !== [2, 3]
    || NumberSchemeParser::advance(1, 1, true) !== [1, 2]) {
    throw new RuntimeException('streak transitions failed');
}

echo "number_monitor_parser_regression: OK\n";
