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
        \common\tools\Tool_Common::log('/jobs/'.__FUNCTION__, 'INFO', '≤‚ ‘∂”¡–', $logArr);
    }
}