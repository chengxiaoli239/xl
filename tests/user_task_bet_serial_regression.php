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

// 默认仅模拟 Redis；可指定独立测试 Redis 端口验证真实 SET/Lua，禁止发送真实下注。
$redis = new class {
    public $values = [];

    public function executeCommand(string $command, array $params)
    {
        if ($command === 'SET') {
            if (array_slice($params, 2) !== ['NX', 'EX', 600]) {
                throw new RuntimeException('客户锁应使用原子 SET NX EX 并保留足够租期');
            }
            if (array_key_exists($params[0], $this->values)) {
                return null;
            }
            $this->values[$params[0]] = $params[1];
            return true;
        }
        if ($command === 'GET') {
            return $this->values[$params[0]] ?? null;
        }
        if ($command !== 'EVAL') {
            throw new RuntimeException('释放客户锁必须使用 Redis Lua 原子脚本');
        }

        $key = $params[2];
        $owner = $params[3];
        if (($this->values[$key] ?? null) !== $owner) {
            return 0;
        }

        unset($this->values[$key]);
        return 1;
    }
};
if (getenv('REDIS_SERIAL_TEST_PORT')) {
    $redis = new \yii\redis\Connection([
        'hostname' => '127.0.0.1',
        'port' => (int)getenv('REDIS_SERIAL_TEST_PORT'),
        'database' => 0,
    ]);
}
$cache = new class extends \yii\caching\ArrayCache {
    public $redis;
};
$cache->redis = $redis;
$cache->keyPrefix = 'serial-regression:'.bin2hex(random_bytes(12)).':';
// 不提供 app.redis，确保锁始终使用缓存自身的连接和 keyPrefix。
\Yii::$app = (object)['cache' => $cache];
$acquire = new ReflectionMethod(UserTaskBetJob::class, 'acquireSerialLock');
$acquire->setAccessible(true);
$release = new ReflectionMethod(UserTaskBetJob::class, 'releaseSerialLock');
$release->setAccessible(true);
$userKey = UserTaskBetJob::buildSerialLockKey($sameAccount);
$otherUserKey = UserTaskBetJob::buildSerialLockKey($differentUser);
$redisKey = $cache->buildKey($userKey);

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
assertSameValue('first-plan', $redis->executeCommand('GET', [$redisKey]), '未获得锁的任务不能释放正在下注的客户锁');
// 模拟旧租期结束，新任务获得同名锁。
$release->invoke(null, $userKey, 'first-plan');
assertSameValue(true, $acquire->invoke(null, $userKey, 'next-plan'), '新任务应能取得已释放的锁');
$release->invoke(null, $userKey, 'first-plan');
assertSameValue('next-plan', $redis->executeCommand('GET', [$redisKey]), '旧任务不得删除租期到期后由新任务持有的锁');
$release->invoke(null, $userKey, 'next-plan');
assertSameValue(null, $redis->executeCommand('GET', [$redisKey]), '当前锁 owner 应能原子释放客户锁');
assertSameValue(true, $acquire->invoke(null, $userKey, 'next-plan'), '首个计划结束后同客户下个计划应能执行');
$release->invoke(null, $userKey, 'next-plan');
$release->invoke(null, $otherUserKey, 'other-user');

echo "user_task_bet_serial_regression: OK\n";
