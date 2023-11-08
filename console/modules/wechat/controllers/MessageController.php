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
        var_dump('dw');
    }

}
