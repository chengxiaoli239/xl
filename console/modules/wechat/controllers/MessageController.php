<?php

namespace console\modules\wechat\controllers;

use backend\service\HN0898Service;
use common\kj\qxc\QxcTcw;
use console\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * MessageController implements the CRUD actions for WechatUser model.
 */
class MessageController extends BaseController
{

    public function actionIndex()
    {
        var_dump('aaaa');
    }

    public function actionDw(){
        $data = QxcTcw::getNineNineLottery($type='json', $is_auto=1, $lottery_type=26);
        p($data);
    }

}
