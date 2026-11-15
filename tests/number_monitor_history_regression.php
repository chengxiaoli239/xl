<?php

// Uses an isolated in-memory database; no production data or betting requests.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__) . '/common/config/bootstrap.php';

use backend\service\NumberMonitorService;

function checkHistory(bool $valid, string $message): void
{
    if (!$valid) {
        throw new RuntimeException($message);
    }
}

$app = new yii\console\Application([
    'id' => 'number-monitor-history-test',
    'basePath' => dirname(__DIR__) . '/console',
    'runtimePath' => sys_get_temp_dir(),
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite::memory:',
            'tablePrefix' => 'lt_',
        ],
    ],
]);
$db = $app->db;
$db->createCommand('CREATE TABLE lt_number_monitor_files (id INTEGER PRIMARY KEY, uid INTEGER NOT NULL)')->execute();
$db->createCommand('CREATE TABLE lt_number_monitor_schemes (id INTEGER PRIMARY KEY, file_id INTEGER NOT NULL,
    title TEXT NOT NULL, lottery_type INTEGER NOT NULL, started_draw_id INTEGER NOT NULL, last_draw_id INTEGER NOT NULL)')->execute();
$db->createCommand('CREATE TABLE lt_number_monitor_patterns (id INTEGER PRIMARY KEY, scheme_id INTEGER NOT NULL,
    pattern TEXT NOT NULL)')->execute();
$db->createCommand('CREATE TABLE lt_ssc_kj_data (id INTEGER PRIMARY KEY, lottery_type INTEGER NOT NULL,
    qihao TEXT NOT NULL, code_4n_str TEXT NOT NULL)')->execute();

$db->createCommand()->insert('{{%number_monitor_files}}', ['id' => 1, 'uid' => 123])->execute();
$db->createCommand()->insert('{{%number_monitor_schemes}}', [
    'id' => 10, 'file_id' => 1, 'title' => '方案 1', 'lottery_type' => 8,
    'started_draw_id' => 1, 'last_draw_id' => 54,
])->execute();
$db->createCommand()->insert('{{%number_monitor_schemes}}', [
    'id' => 11, 'file_id' => 1, 'title' => '等待方案', 'lottery_type' => 8,
    'started_draw_id' => 54, 'last_draw_id' => 54,
])->execute();
$db->createCommand()->batchInsert('{{%number_monitor_patterns}}', ['id', 'scheme_id', 'pattern'], [
    [1, 10, '1234'], [2, 10, '12XX'],
])->execute();

$drawRows = [[1, 8, '1001', '9,9,9,9']];
for ($id = 2; $id <= 55; $id++) {
    $code = $id === 53 ? '1,2,9,9' : ($id === 54 ? '9,9,1,1' : '1,2,3,4');
    $drawRows[] = [$id, 8, (string)(1000 + $id), $code];
}
$db->createCommand()->batchInsert('{{%ssc_kj_data}}',
    ['id', 'lottery_type', 'qihao', 'code_4n_str'], $drawRows)->execute();

$page = NumberMonitorService::schemeHistory(123, 10);
checkHistory(count($page['records']) === 50 && $page['has_more'] === true,
    'history page size or next-page marker is incorrect');
checkHistory($page['records'][0]['id'] === 54 && $page['records'][0]['status'] === 2
    && $page['records'][1]['id'] === 53 && $page['records'][1]['status'] === 1,
    'recent hit and miss results do not match monitoring rules');
checkHistory($page['next_before_id'] === 5, 'history cursor is incorrect');
$older = NumberMonitorService::schemeHistory(123, 10, $page['next_before_id']);
checkHistory(count($older['records']) === 3 && $older['records'][2]['id'] === 2
    && $older['has_more'] === false, 'older history included unmonitored draws');
checkHistory(NumberMonitorService::schemeHistory(123, 11)['records'] === [],
    'unprocessed scheme returned history');
try {
    NumberMonitorService::schemeHistory(456, 10);
    throw new RuntimeException('history was visible to another account');
} catch (InvalidArgumentException $expected) {
}

echo "number_monitor_history_regression: OK\n";
