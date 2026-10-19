<?php
namespace common\service\jobs\plan;

use backend\models\BetErrorPlansTask;
use backend\service\BetService;
use common\service\jobs\CommonJob;
use yii\helpers\Json;

class UserTaskBetJob extends CommonJob {

    public $retryCount = 1;

    private const TASK_LOCK_TTL = 180;
    private const USER_SERIAL_LOCK_TTL = 180;
    private const USER_SERIAL_LOCK_WAIT = 5;
    private const USER_SERIAL_RETRY_DELAY = 3;

    public function getTtr(): int
    {
        return 120;
    }

    public static function getName($params) {
        self::$name = '30-执行下注';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params, $this->queueId);
    }

    public static function handle($params, $queueId = ''){
        $taskId = $params['task_id'];
        if(empty($taskId)){
            throw_info('计划任务id为空');
        }
        $qihao = $params['qihao'];

        $lockKey = 'user-task-bet-job:'.$taskId;
        if (!\Yii::$app->cache->add($lockKey, 1, self::TASK_LOCK_TTL)) {
            return '下注任务正在执行，本次跳过:'.$taskId;
        }

        $serialLockKey = null;
        $serialLockOwner = self::buildLockOwner($taskId, $queueId);
        try {
            $task = BetErrorPlansTask::find()
                ->select(['uid', 'tz_system_id', 'account'])
                ->where(['id' => (int)$taskId])
                ->asArray()
                ->one();
            if ($task) {
                $serialLockKey = self::buildSerialLockKey($task);
                if (!self::acquireSerialLock($serialLockKey, $serialLockOwner)) {
                    self::requeueAfterSerialWait($params, $taskId);
                    return '同一用户盘口下注正在处理，已延后重试:'.$taskId;
                }
            }

            $result = BetService::betUserOneTask($taskId, $qihao);
        } finally {
            if ($serialLockKey !== null) {
                self::releaseSerialLock($serialLockKey, $serialLockOwner);
            }
            \Yii::$app->cache->delete($lockKey);
        }

        return is_json($result)?Json::decode($result):$result;
    }

    /**
     * Build the lock scope used by service-side betting requests.
     * Plans sharing a user and盘口 system are serialized; unrelated users/sites remain concurrent.
     */
    public static function buildSerialLockKey(array $task): string
    {
        $uid = (int)($task['uid'] ?? 0);
        $tzSystemId = (int)($task['tz_system_id'] ?? 0);

        return 'user-task-bet-serial:'.$uid.':'.$tzSystemId;
    }

    private static function buildLockOwner($taskId, $queueId): string
    {
        return getmypid().':'.(string)$queueId.':'.(string)$taskId.':'.uniqid('', true);
    }

    private static function acquireSerialLock(string $key, string $owner): bool
    {
        $deadline = microtime(true) + self::USER_SERIAL_LOCK_WAIT;
        do {
            if (\Yii::$app->cache->add($key, $owner, self::USER_SERIAL_LOCK_TTL)) {
                return true;
            }
            usleep(250000);
        } while (microtime(true) < $deadline);

        return false;
    }

    private static function releaseSerialLock(string $key, string $owner): void
    {
        // Do not delete a lock that was acquired by a later task after our lease expired.
        if (\Yii::$app->cache->get($key) === $owner) {
            \Yii::$app->cache->delete($key);
        }
    }

    private static function requeueAfterSerialWait(array $params, $taskId): void
    {
        $params['task_id'] = (int)$taskId;
        $params['business_id'] = $params['business_id'] ?? (int)$taskId;
        $params['queue_delay_time'] = self::USER_SERIAL_RETRY_DELAY;
        $params['serial_retry'] = (int)($params['serial_retry'] ?? 0) + 1;
        $params['queue_fast'] = true;
        if (!push_queue(self::class, $params)) {
            throw new \RuntimeException('同一用户盘口下注延后入列失败:'.$taskId);
        }
    }

}
