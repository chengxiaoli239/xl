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
$sameAccountDifferentCase = $sameAccount;
$sameAccountDifferentCase['account'] = ' aa03 ';
$differentAccount = $sameAccount;
$differentAccount['account'] = 'aa04';
$differentSite = $sameAccount;
$differentSite['tz_system_id'] = 7;

assertSameValue(
    UserTaskBetJob::buildSerialLockKey($sameAccount),
    UserTaskBetJob::buildSerialLockKey($sameAccountDifferentCase),
    '同一盘口账号的大小写和空格不应产生不同锁'
);
if (UserTaskBetJob::buildSerialLockKey($sameAccount) === UserTaskBetJob::buildSerialLockKey($differentAccount)) {
    throw new RuntimeException('不同盘口账号不应共用串行锁');
}
if (UserTaskBetJob::buildSerialLockKey($sameAccount) === UserTaskBetJob::buildSerialLockKey($differentSite)) {
    throw new RuntimeException('不同盘口系统不应共用串行锁');
}

echo "user_task_bet_serial_regression: OK\n";
