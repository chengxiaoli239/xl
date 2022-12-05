<?php
/**
 * Description
 *
 *
 * Datetime: 2021-01-07 14:50
 */

namespace common\exceptions;


use common\tools\Dingtalk;
use yii\web\ErrorHandler;

class BackendExceptionHandler extends ErrorHandler
{
    public function renderException($exception)
    {
        if (YII_ENV == 'prod') {
            $message = '';
            $message .= "请求路由:" . "{$_SERVER['REQUEST_METHOD']} " . \Yii::$app->request->getHostInfo() . \Yii::$app->request->url . "\n";

            $message .= $exception->getMessage().$exception->getTraceAsString();
            Dingtalk::sendMessageToRobot(\Yii::$app->params['DINGTALK_MSG_KEY'], $message);
        }

        parent::renderException($exception);
    }
}
