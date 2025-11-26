<?php
namespace common\service\jobs;

use common\models\QueueLog;
use common\tools\Tool_Common;
use yii\base\BaseObject;
use yii\db\Expression;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;

abstract class CommonJob extends BaseObject implements JobInterface, RetryableJobInterface
{
    public $retryCount = 3; // 最大重试次数（总共执行3次：第1次 + 2次重试）

    // 重试间隔时间（秒）
    public function getTtr(): int
    {
        return 30;
    }
    protected $queueId;
    protected $errorMessage;

    public static $name = '';
    public static $isCheckRunTime = true; #是否检查队列运行多久没有运行完成 true-检查 false-不检查
    public static $isCatchError = false;

    public function __construct($queueId)
    {
        parent::__construct($queueId);
        $this->queueId = $queueId;
//        $this->errorMessage = json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
//        //注册自定义错误处理方法
    }

    /**
     * 判断是否需要重试
     * @param $attempt 当前尝试次数（从1开始）
     * @param $error 异常对象
     * @return bool
     */
    public function canRetry($attempt, $error): bool
    {
        // 检查是否是超时异常，如果是则允许重试
        if ($error instanceof \Symfony\Component\Process\Exception\ProcessTimedOutException) {
            // 超时异常允许重试，但不超过配置的最大重试次数
            // 注意：队列框架会在调用 canRetry 之前检查 attempts 配置
            // 所以这里只需要检查是否小于 retryCount 即可
            Tool_Common::log('/queue/retry', 'INFO', '检测到超时异常，允许重试', [
                'queue_id' => $this->queueId,
                'attempt' => $attempt,
                'retry_count' => $this->retryCount,
                'error' => $error->getMessage()
            ]);
            return $attempt < $this->retryCount;
        }
        
        // 检查错误消息中是否包含超时相关关键词
        $errorMessage = $error instanceof \Exception ? $error->getMessage() : (string)$error;
        if (stripos($errorMessage, 'timeout') !== false || 
            stripos($errorMessage, 'exceeded the timeout') !== false ||
            stripos($errorMessage, '超时') !== false ||
            stripos($errorMessage, 'ProcessTimedOutException') !== false) {
            Tool_Common::log('/queue/retry', 'INFO', '检测到超时关键词，允许重试', [
                'queue_id' => $this->queueId,
                'attempt' => $attempt,
                'retry_count' => $this->retryCount,
                'error_message' => substr($errorMessage, 0, 200)
            ]);
            return $attempt < $this->retryCount;
        }
        
        // 其他异常默认不重试
        return false;
    }

    public function execute($queue)
    {
        //注册致命错误处理方法
        $startTime = time();
        var_dump("进入队列:{$this->queueId}");
        $log = QueueLog::find()->andWhere(['id'=>$this->queueId])->limit(1)->one();
        if (empty($log)) {
            sleep(10);
            $log = QueueLog::find()->andWhere(['id'=>$this->queueId])->limit(1)->one();
            if (empty($log)) {
                Tool_Common::log('/queue/exception', 'info', '队列异常', "找不到队列日志:".$this->queueId);
                return true;
            }
        }
        //处理成功不处理
        if ($log['status'] == QueueLog::STATUS_SUCCESS) {
            Tool_Common::log('/queue/exception', 'info1', '队列已完成', "队列已完成:".$this->queueId);
            return true;
        }
        $cacheKey = "queue:" . $this->queueId;
        $lock = \Yii::$app->cache->get($cacheKey);
        if ($lock) {
            Tool_Common::log('/queue/exception', 'info1', '已经有队列在处理了', $this->queueId);
            var_dump('已经有队列在处理了');
            return true;
        }

        \Yii::$app->cache->set($cacheKey, 1, 20);
        QueueLog::updateAll(['status'=>QueueLog::STATUS_CONSUMING, 'count'=>(new Expression("`count`+1")),], ['id'=>$this->queueId]);
        try {
            $status = QueueLog::STATUS_SUCCESS;
            $params = json_decode($log['params'] ?? '', true);
            $name = static::getName($params);
            $result = $this->exec($params);
            QueueLog::updateAll( [ 'status'=>$status, 'time'=>time()-$startTime,'remark'=>json_encode($result, 320),'complete_time'=>time()], ['id'=>$this->queueId]);
        } catch (\Throwable $e) {
            self::$isCatchError = true;
            var_dump('a', $this->queueId . ',' . $e->getMessage());
            $err_msg = $e->getMessage();
            $err_msg = strlen($err_msg)>1000? substr($err_msg, 0, 1000) : $err_msg;
            Tool_Common::log('/queue/exception', 'info', '队列异常--', $err_msg.'-File-'.$e->getFile().'--line-'.$e->getLine());
            QueueLog::updateAll(['status'=>QueueLog::STATUS_FAILED, 'remark'=>$e->getMessage(),'time'=>time()-$startTime, 'complete_time'=>time(),], ['id'=>$this->queueId] );
        } finally {
            \Yii::$app->cache->delete($cacheKey);
        }
    }

    abstract public function exec($params);

    public static function getName($params)
    {
        return self::$name;
    }

    public function fatalError()
    {
        $error = error_get_last();
        if (!empty($error) && !self::$isCatchError) {
            $message = '致命错误:' .json_encode($error);
            QueueLog::updateAll(
                [
                    'status'=>QueueLog::STATUS_FAILED,
                    'remark'=> $message
                ],
                ['id'=>$this->queueId]
            );
            #Dingtalk::sendMessageToRobot('system_exceptions', "队列致命错误{$this->queueId}:" .$message);
            die;
        }
    }

    public function appError($errno, $errstr, $errfile, $errline)
    {
        $error['message'] = "[$errno] $errstr";
        $error['file'] = $errfile;
        $error['line'] = $errline;
        $error['class'] = $error['function'] = '';
        if (!in_array($errno, array(E_STRICT, E_DEPRECATED))) {
            if (in_array($errno, array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
                $message = json_encode($error);
                #Dingtalk::sendMessageToRobot('system_exceptions', "队列错误{$this->queueId}:" . $message);
                QueueLog::updateAll(
                    [
                        'status'=>QueueLog::STATUS_FAILED,
                        'remark'=> $message
                    ],
                    ['id'=>$this->queueId]
                );
            }
        }
    }

    static function class_basename($class) {
        $path = explode('\\', $class);
        return end($path);
    }
}
