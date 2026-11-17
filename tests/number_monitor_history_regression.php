<?php

// Uses an isolated in-memory database; no production data or betting requests.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__) . '/common/config/bootstrap.php';

use backend\service\NumberMonitorService;
use backend\modules\forum\controllers\NumberMonitorController;
use common\models\AdminModel;

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
        'user' => [
            'class' => yii\web\User::class,
            'identityClass' => AdminModel::class,
            'enableSession' => false,
        ],
    ],
]);
$db = $app->db;
$db->createCommand('CREATE TABLE lt_admin (id INTEGER PRIMARY KEY, username TEXT NOT NULL,
    user_type INTEGER NOT NULL DEFAULT 0, status INTEGER NOT NULL DEFAULT 10)')->execute();
$db->createCommand('CREATE TABLE lt_number_monitor_files (id INTEGER PRIMARY KEY, uid INTEGER NOT NULL,
    lottery_type INTEGER NOT NULL DEFAULT 8, status INTEGER NOT NULL DEFAULT 1,
    scheme_count INTEGER NOT NULL DEFAULT 2)')->execute();
$db->createCommand('CREATE TABLE lt_number_monitor_schemes (id INTEGER PRIMARY KEY, file_id INTEGER NOT NULL,
    title TEXT NOT NULL, lottery_type INTEGER NOT NULL, pattern_count INTEGER NOT NULL DEFAULT 1,
    started_draw_id INTEGER NOT NULL, last_draw_id INTEGER NOT NULL)')->execute();
$db->createCommand('CREATE TABLE lt_number_monitor_patterns (id INTEGER PRIMARY KEY, scheme_id INTEGER NOT NULL,
    pattern TEXT NOT NULL, occurrence_count INTEGER NOT NULL DEFAULT 1)')->execute();
$db->createCommand('CREATE TABLE lt_ssc_kj_data (id INTEGER PRIMARY KEY, lottery_type INTEGER NOT NULL,
    qihao TEXT NOT NULL, code_4n_str TEXT NOT NULL)')->execute();

$db->createCommand()->insert('{{%number_monitor_files}}', ['id' => 1, 'uid' => 123])->execute();
$db->createCommand()->batchInsert('{{%admin}}', ['id', 'username'], [
    [123, 'owner-a'], [456, 'owner-b'],
])->execute();
$db->createCommand()->insert('{{%number_monitor_files}}', [
    'id' => 2, 'uid' => 456, 'scheme_count' => 1, 'status' => 0,
])->execute();
$db->createCommand()->insert('{{%number_monitor_schemes}}', [
    'id' => 10, 'file_id' => 1, 'title' => '方案 1', 'lottery_type' => 8,
    'started_draw_id' => 1, 'last_draw_id' => 54,
])->execute();
$db->createCommand()->insert('{{%number_monitor_schemes}}', [
    'id' => 11, 'file_id' => 1, 'title' => '等待方案', 'lottery_type' => 8,
    'started_draw_id' => 54, 'last_draw_id' => 54,
])->execute();
$db->createCommand()->insert('{{%number_monitor_schemes}}', [
    'id' => 12, 'file_id' => 2, 'title' => '另一账号', 'lottery_type' => 8,
    'started_draw_id' => 1, 'last_draw_id' => 54,
])->execute();
$db->createCommand()->batchInsert('{{%number_monitor_patterns}}', ['id', 'scheme_id', 'pattern'], [
    [1, 10, '1234'], [2, 10, '12XX'], [3, 12, '5678'],
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
checkHistory(NumberMonitorService::schemeOwnerUid(12) === 456,
    'scheme owner lookup returned the wrong account');
checkHistory(NumberMonitorService::schemePatterns(456, 12, 1)['patterns'][0]['pattern'] === '5678',
    'scheme owner could not read their patterns');
try {
    NumberMonitorService::schemePatterns(123, 12, 1);
    throw new RuntimeException('another account patterns were visible without owner scoping');
} catch (InvalidArgumentException $expected) {
}
$owners = NumberMonitorService::monitorOwners(8);
checkHistory(count($owners) === 2 && (int)$owners[0]['uid'] === 123
    && $owners[0]['username'] === 'owner-a'
    && (int)$owners[0]['scheme_count'] === 2
    && (int)$owners[1]['uid'] === 456
    && (int)$owners[1]['active_file_count'] === 0,
    'admin owner summary omitted accounts or changed counts');
try {
    NumberMonitorService::schemeHistory(123, 12);
    throw new RuntimeException('another account history was visible without owner scoping');
} catch (InvalidArgumentException $expected) {
}
$controller = (new ReflectionClass(NumberMonitorController::class))->newInstanceWithoutConstructor();
$roleCheck = new ReflectionMethod(NumberMonitorController::class, 'isSuperAdmin');
$visibleOwner = new ReflectionMethod(NumberMonitorController::class, 'visibleOwnerUid');
$ownerCheck = new ReflectionMethod(NumberMonitorController::class, 'readOwnerUid');
$admin = new AdminModel();
$admin->id = 1;
$admin->user_type = AdminModel::USER_TYPE_SUPER_ADMIN;
$admin->status = AdminModel::STATUS_ACTIVE;
$app->user->setIdentity($admin);
$controller->_user_id = 1;
checkHistory($roleCheck->invoke($controller) === true
    && $visibleOwner->invoke($controller, 456) === 456
    && $visibleOwner->invoke($controller, 0) === 0
    && $ownerCheck->invoke($controller, 12) === 456,
    'super admin could not read another account scheme');
$controller->_user_id = 123;
checkHistory($roleCheck->invoke($controller) === false
    && $visibleOwner->invoke($controller, 456) === 123
    && $ownerCheck->invoke($controller, 12) === 123,
    'non-admin was allowed to read another account scheme');
$controller->_user_id = 1;
$admin->user_type = AdminModel::USER_TYPE_3D_ADMIN;
checkHistory($roleCheck->invoke($controller) === false,
    'non-super-admin role retained cross-account access');
$admin->user_type = AdminModel::USER_TYPE_SUPER_ADMIN;
$admin->status = AdminModel::STATUS_DELETED;
checkHistory($roleCheck->invoke($controller) === false,
    'disabled super admin retained cross-account access');

echo "number_monitor_history_regression: OK\n";
