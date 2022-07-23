<?php

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
        \common\tools\Tool_Common::log('/job/'.__CLASS__.'_'.__FUNCTION__, 'INFO', '≤‚ ‘∂”¡–', $logArr);
    }
}