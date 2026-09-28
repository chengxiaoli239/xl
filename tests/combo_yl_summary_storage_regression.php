<?php

require dirname(__DIR__).'/vendor/autoload.php';

require dirname(__DIR__).'/backend/service/statics/yl/ThreeComboYlService.php';
require dirname(__DIR__).'/backend/service/statics/yl/FiveComboYlService.php';
require dirname(__DIR__).'/backend/service/statics/yl/FiveAllReverseYlService.php';

use backend\service\statics\yl\ThreeComboYlService;
use backend\service\statics\yl\FiveComboYlService;
use backend\service\statics\yl\FiveAllReverseYlService;

$statistics = [];
foreach (FiveComboYlService::getCombinations() as $code) {
    $statistics[] = [
        'code' => $code,
        'current_miss' => 123,
        'last_time_miss' => 45,
        'last_time_miss_range' => '20260928214-20260927051',
        'max_miss' => 378,
        'max_range' => '20260928214-20260927051',
        'history_max_miss' => 378,
        'hit_count' => 23,
        'yl_records' => implode('-', range(100, 129)),
        'is_window_limit' => false,
    ];
}

if (strlen(json_encode($statistics)) <= 65535) {
    throw new RuntimeException('Fixture does not reproduce the TEXT overflow');
}

foreach ([FiveComboYlService::class, FiveAllReverseYlService::class] as $service) {
    $stored = $service::encodeSummary($statistics);
    if (strlen($stored) > 65535) {
        throw new RuntimeException($service.' summary exceeds TEXT storage');
    }
    if ($service::decodeSummary($stored) !== $statistics) {
        throw new RuntimeException($service.' summary failed round trip');
    }
}

$legacy = json_encode([['code' => '012', 'current_miss' => 4]]);
if (ThreeComboYlService::decodeSummary($legacy) !== [['code' => '012', 'current_miss' => 4]]) {
    throw new RuntimeException('Legacy JSON summary cannot be read');
}

echo "combo_yl_summary_storage_regression: OK\n";
