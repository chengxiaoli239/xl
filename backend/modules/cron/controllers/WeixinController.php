<?php

namespace backend\modules\cron\controllers;

use backend\service\WxService;
use yii\web\Controller;

/**
 * Default controller for the `cron` module
 */
class WeixinController extends Controller
{
    private static function _init()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    }

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionSendMsg(){
        self::_init();
        $rst = WxService::sendMsg();# 群发微信消息

        return $rst;
    }
}
