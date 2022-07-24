<?php
namespace backend\service\jobs;

class TestJob extends \yii\base\BaseObject implements \yii\queue\JobInterface
{
    public $url;
    public $file;

    public function execute($queue)
    {

        $logArr = [
            'url' => $this->url,
            'test'=>'xxxxxx'
        ];
        var_dump(date('Y-m-d H:i:s').' 测试队列');
        \common\tools\Tool_Common::log('/jobs/'.__FUNCTION__, 'INFO', '测试队列', $logArr);
    }
}