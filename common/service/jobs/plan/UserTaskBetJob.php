<?php
namespace common\service\jobs\plan;

use backend\service\BetService;
use common\service\jobs\CommonJob;

class UserTaskBetJob extends CommonJob {

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

        return BetService::betUserOneTask($taskId, $qihao);
    }

}