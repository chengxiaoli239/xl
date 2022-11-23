<?php
namespace backend\service\jobs;

class TestJob extends \yii\base\BaseObject implements \yii\queue\JobInterface
{
    public $url;
    public $file;

    public function execute($data) {
        \common\tools\Tool_Common::log('/jobs/'.__FUNCTION__, 'INFO', '测试队列TestJob', $data);
    }
}