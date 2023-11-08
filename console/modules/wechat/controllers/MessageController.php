<?php

namespace console\modules\wechat\controllers;

use backend\service\HN0898Service;
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
        $q = HN0898Service::getQihao($lt=27);
        var_dump($q);
    }

}
