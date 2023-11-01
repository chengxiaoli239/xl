<?php

namespace console\controllers;

use common\service\thirdD\OperateLotteryService;
use yii\base\Module;
use yii\console\Controller;

class ThirddController extends Controller
{
    public function __construct($id, Module $module, array $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    public function actionDw(){
        var_dump('我擦 我擦');
    }

    /**
     * php yii thirdd/run-lottery
     */
    public function actionRunLottery()
    {
        OperateLotteryService::operate($lottery_type=26);
    }


}
