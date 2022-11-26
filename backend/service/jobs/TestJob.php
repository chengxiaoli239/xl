<?php
namespace backend\service\jobs;

use common\service\jobs\CommonJob;

class TestJob extends CommonJob {

    public static function getName($params) {
        self::$name = '测试队列TestJob';
        return self::$name;
    }

    public function execute($params) {
        return self::handle($params);
    }

    public static function handle($params){
        \common\tools\Tool_Common::log('/jobs/'.__FUNCTION__, 'INFO', '测试队列TestJob 进入', $params);
        // TODO: Implement exec() method.

        return true;
    }

    public function exec($params)
    {
        var_dump($params);
        // TODO: Implement exec() method.
    }
}