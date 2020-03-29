<?php
namespace backend\modules\agent;

class Module extends \yii\base\Module
{
    //public $controllerNamespace = 'item\modules\agent\controllers';
    public function init()
    {
        parent::init();

        $this->params['foo'] = 'bar';
        // ...  其他初始化代码 ...
        if (\Yii::$app instanceof \yii\console\Application) {
            //$this->controllerNamespace = 'item\modules\agent\commands';
        }
    }
}