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

    /**
     * php yii thirdd/run-lottery
     */
    public function actionRunLottery()
    {
        OperateLotteryService::operate($lottery_type=26);
    }


}
