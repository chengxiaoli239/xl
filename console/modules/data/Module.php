<?php
namespace console\modules\data;

class Module extends \yii\base\Module
{
    //public $controllerNamespace = 'console\modules\wechat\controllers';
    public function init()
    {
        parent::init();

        $this->params['foo'] = 'bar';
        // ...  其他初始化代码 ...
        if (\Yii::$app instanceof \yii\console\Application) {
            //$this->controllerNamespace = 'console\modules\wechat\commands';
        }
    }
}
