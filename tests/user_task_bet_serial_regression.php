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
$differentUser = $sameAccount;
$differentUser['uid'] = 36;

assertSameValue(
    UserTaskBetJob::buildSerialLockKey($sameAccount),
    UserTaskBetJob::buildSerialLockKey($differentAccount),
    '同一用户的计划不应因盘口账号不同而绕过串行锁'
);
assertSameValue(
    UserTaskBetJob::buildSerialLockKey($sameAccount),
    UserTaskBetJob::buildSerialLockKey($differentSite),
    '同一用户的不同盘口系统也应按客户统一串行'
);
if (UserTaskBetJob::buildSerialLockKey($sameAccount) === UserTaskBetJob::buildSerialLockKey($differentUser)) {
    throw new RuntimeException('不同用户不应共用串行锁');
}

// 使用进程内缓存模拟客户争锁，禁止连接数据库或发送真实下注。
$cache = new \yii\caching\ArrayCache();
\Yii::$app = (object)['cache' => $cache];
$acquire = new ReflectionMethod(UserTaskBetJob::class, 'acquireSerialLock');
$acquire->setAccessible(true);
$release = new ReflectionMethod(UserTaskBetJob::class, 'releaseSerialLock');
$release->setAccessible(true);
$userKey = UserTaskBetJob::buildSerialLockKey($sameAccount);
$otherUserKey = UserTaskBetJob::buildSerialLockKey($differentUser);

assertSameValue(true, $acquire->invoke(null, $userKey, 'first-plan'), '首个计划应获得客户锁');
$startedAt = microtime(true);
for ($i = 0; $i < 20; $i++) {
    assertSameValue(false, $acquire->invoke(null, $userKey, 'waiting-plan'), '同客户其他计划应延后');
}
if (microtime(true) - $startedAt >= 1) {
    throw new RuntimeException('锁冲突不应占住消费者等待');
}
assertSameValue(true, $acquire->invoke(null, $otherUserKey, 'other-user'), '客户争锁时其他客户仍应能执行');
$release->invoke(null, $userKey, 'waiting-plan');
assertSameValue('first-plan', $cache->get($userKey), '未获得锁的任务不能释放正在下注的客户锁');
$release->invoke(null, $userKey, 'first-plan');
assertSameValue(true, $acquire->invoke(null, $userKey, 'next-plan'), '首个计划结束后同客户下个计划应能执行');
$release->invoke(null, $userKey, 'first-plan');
assertSameValue('next-plan', $cache->get($userKey), '旧任务不能删除后续任务的锁');

echo "user_task_bet_serial_regression: OK\n";
