<?php

namespace console\controllers;

use backend\service\statics\statics_3d\Statics3dUserDataService;
use common\service\thirdD\CommonBaseService;
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
        $date = $params['date']??date('Y-m-d');
        $date = '2023-11-07';
        list($code, $data, $msg) = Statics3dUserDataService::calculateUserDayData($wechat_user_id=19, $date, [26,27]);
        var_dump('我擦 我擦');
        p($data);
    }

}
