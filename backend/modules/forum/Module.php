<?php
namespace backend\modules\forum;

class Module extends \yii\base\Module
{
    //public $controllerNamespace = 'item\modules\forum\controllers';
    public function init()
    {
        parent::init();

        $this->params['foo'] = 'bar';
        // ...  其他初始化代码 ...
        if (\Yii::$app instanceof \yii\console\Application) {
            //$this->controllerNamespace = 'item\modules\forum\commands';
        }
    }
}