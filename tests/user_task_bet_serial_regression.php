<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/common/service/jobs/CommonJob.php';
require dirname(__DIR__).'/common/service/jobs/plan/UserTaskBetJob.php';

use common\service\jobs\plan\UserTaskBetJob;

function assertSameValue($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message.' expected='.var_export($expected, true).' actual='.var_export($actual, true).PHP_EOL);
        exit(1);
    }
}

$sameAccount = [
    'uid' => 35,
    'tz_system_id' => 6,
    'account' => 'AA03',
];
$differentAccount = $sameAccount;
$differentAccount['account'] = 'aa04';
$differentSite = $sameAccount;
$differentSite['tz_system_id'] = 7;

assertSameValue(
    UserTaskBetJob::buildSerialLockKey($sameAccount),
    UserTaskBetJob::buildSerialLockKey($differentAccount),
    '同一用户同一盘口系统不应因账号字段不同而绕过串行锁'
);
if (UserTaskBetJob::buildSerialLockKey($sameAccount) === UserTaskBetJob::buildSerialLockKey($differentSite)) {
    throw new RuntimeException('不同盘口系统不应共用串行锁');
}

echo "user_task_bet_serial_regression: OK\n";
