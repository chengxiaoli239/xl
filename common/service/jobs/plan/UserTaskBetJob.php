<?php
namespace common\service\jobs\plan;

use backend\service\BetService;
use common\service\jobs\CommonJob;
use yii\helpers\Json;

class UserTaskBetJob extends CommonJob {

    public $retryCount = 1;

    public function getTtr(): int
    {
        return 120;
    }

    public static function getName($params) {
        self::$name = '30-执行下注';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $taskId = $params['task_id'];
        if(empty($taskId)){
            throw_info('计划任务id为空');
        }
        $qihao = $params['qihao'];

        $lockKey = 'user-task-bet-job:'.$taskId;
        if (!\Yii::$app->cache->add($lockKey, 1, 180)) {
            return '下注任务正在执行，本次跳过:'.$taskId;
        }

        try {
            $result = BetService::betUserOneTask($taskId, $qihao);
        } finally {
            \Yii::$app->cache->delete($lockKey);
        }

        return is_json($result)?Json::decode($result):$result;
    }

}
