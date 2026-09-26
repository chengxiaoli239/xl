<?php

error_reporting(E_ALL & ~E_DEPRECATED);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';

Yii::setAlias('@backend', dirname(__DIR__) . '/backend');
Yii::setAlias('@common', dirname(__DIR__) . '/common');

use backend\models\ImportPlanCodes;
use backend\models\UserSysPlans;
use backend\service\PlanCopyService;
use yii\console\Application;

function assertIntegration($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nexpected: " . var_export($expected, true) . "\nactual: " . var_export($actual, true));
    }
}

new Application([
    'id' => 'plan-copy-test',
    'basePath' => dirname(__DIR__),
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite::memory:',
        ],
    ],
]);

$db = Yii::$app->db;
$schema = [
    'CREATE TABLE admin (id INTEGER PRIMARY KEY, username VARCHAR(255) NOT NULL, status INTEGER NOT NULL)',
    'CREATE TABLE tz_systems_users (id INTEGER PRIMARY KEY AUTOINCREMENT, uid INTEGER, status INTEGER, tz_system_id INTEGER)',
    'CREATE TABLE tz_systems_auth (id INTEGER PRIMARY KEY AUTOINCREMENT, uid INTEGER, tz_systems_ids VARCHAR(255), tz_types VARCHAR(255), lottery_types VARCHAR(255), created_at INTEGER, updated_at INTEGER, update_time TEXT)',
    'CREATE TABLE user_sys_plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        is_parent INTEGER, children_plan_id VARCHAR(255), uid INTEGER NOT NULL,
        account VARCHAR(24) NOT NULL, playway INTEGER, status INTEGER, single NUMERIC NOT NULL,
        singles TEXT, tz_type INTEGER, bet_direct INTEGER, buy_type INTEGER, tz_sites VARCHAR(24),
        hz_Arr TEXT, nums INTEGER, sel_same INTEGER, is_custom INTEGER, is_test INTEGER,
        is_batch_simulate INTEGER, is_profits_record INTEGER, is_area_profits INTEGER,
        is_init_perdate INTEGER, lottery_type INTEGER, take_profits NUMERIC, stop_loss NUMERIC,
        current_profits NUMERIC, plan_type INTEGER, tz_sort INTEGER, base_codes TEXT,
        "desc" TEXT, remark TEXT, real_bet_start_time TEXT, real_bet_end_time TEXT,
        created_at INTEGER NOT NULL, updated_at INTEGER NOT NULL, update_time TEXT
    )',
    'CREATE TABLE import_plan_codes (
        id INTEGER PRIMARY KEY AUTOINCREMENT, uid INTEGER, plan_id INTEGER,
        plan_id_sort_key TEXT, codes TEXT CHECK (uid <> 66 OR codes <> \'FAIL\'), status INTEGER,
        created_at INTEGER, updated_at INTEGER NOT NULL, update_time TEXT
    )',
];
foreach ($schema as $sql) {
    $db->createCommand($sql)->execute();
}

$db->createCommand()->batchInsert('admin', ['id', 'username', 'status'], [
    [6, 'aa06', 10],
    [66, 'aa66', 10],
])->execute();
$db->createCommand()->insert('tz_systems_users', [
    'uid' => 66,
    'status' => 1,
    'tz_system_id' => 7,
])->execute();
$db->createCommand()->insert('tz_systems_auth', [
    'uid' => 66,
    'tz_systems_ids' => '7,8',
    'updated_at' => 1,
])->execute();

$planDefaults = [
    'uid' => 6,
    'account' => 'aa06',
    'playway' => 3,
    'status' => 1,
    'single' => 1.5,
    'tz_type' => 30,
    'tz_sites' => '2,7',
    'hz_Arr' => json_encode(['codes' => '1234', 'betStatus' => 1, 'singles_key' => 2]),
    'is_test' => 0,
    'is_batch_simulate' => 0,
    'current_profits' => 88,
    'created_at' => 1,
    'updated_at' => 1,
];
$db->createCommand()->insert('user_sys_plans', array_merge($planDefaults, ['id' => 22079]))->execute();
$db->createCommand()->batchInsert(
    'import_plan_codes',
    ['uid', 'plan_id', 'plan_id_sort_key', 'codes', 'status', 'created_at', 'updated_at'],
    [
        [6, 22079, '0', '1,2,3,4', 1, 1, 1],
        [6, 22079, '1', '5,6,7,8', 0, 1, 1],
    ]
)->execute();

$copy = PlanCopyService::copyPlan(22079, 66);
assertIntegration(66, (int)$copy->uid, '新计划应属于目标账号');
assertIntegration('aa66', (string)$copy->account, '新计划账号名称应替换');
assertIntegration(0, (int)$copy->status, '新计划应默认关闭');
assertIntegration('7', (string)$copy->tz_sites, '真实计划应选择目标账号已启用盘口');
assertIntegration(0.0, (float)$copy->current_profits, '新计划不应继承历史盈利');
$copyHz = json_decode((string)$copy->hz_Arr, true);
assertIntegration(0, $copyHz['betStatus'], '新计划下注状态应重置');
assertIntegration(0, $copyHz['singles_key'], '新计划倍投索引应重置');

$copiedCodes = ImportPlanCodes::find()
    ->where(['uid' => 66, 'plan_id' => (int)$copy->id])
    ->orderBy(['plan_id_sort_key' => SORT_ASC])
    ->asArray()
    ->all();
assertIntegration(2, count($copiedCodes), '源计划全部导入号码都应复制');
assertIntegration('1,2,3,4', $copiedCodes[0]['codes'], '第一组导入号码应保留');
assertIntegration('5,6,7,8', $copiedCodes[1]['codes'], '第二组导入号码应保留');
assertIntegration(0, (int)$copiedCodes[1]['status'], '导入号码组状态应保留');

$db->createCommand()->insert('user_sys_plans', array_merge($planDefaults, ['id' => 22090]))->execute();
$db->createCommand()->batchInsert(
    'import_plan_codes',
    ['uid', 'plan_id', 'plan_id_sort_key', 'codes', 'status', 'created_at', 'updated_at'],
    [
        [6, 22090, '0', 'OK', 1, 1, 1],
        [6, 22090, '1', 'FAIL', 1, 1, 1],
    ]
)->execute();
$targetPlanCount = (int)UserSysPlans::find()->where(['uid' => 66])->count();
$targetCodeCount = (int)ImportPlanCodes::find()->where(['uid' => 66])->count();
$failed = false;
try {
    PlanCopyService::copyPlan(22090, 66);
} catch (Throwable $e) {
    $failed = true;
}
assertIntegration(true, $failed, '导入号码保存失败时复制操作应报错');
assertIntegration($targetPlanCount, (int)UserSysPlans::find()->where(['uid' => 66])->count(), '失败后不能留下半成品计划');
assertIntegration($targetCodeCount, (int)ImportPlanCodes::find()->where(['uid' => 66])->count(), '失败后不能留下部分导入号码');

echo "plan_copy_integration: OK\n";
