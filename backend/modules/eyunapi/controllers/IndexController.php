<?php
namespace backend\modules\eyunapi\controllers;

use common\service\wechat\eyun\api\EventServiceTrait;
use yii\web\Controller;

class IndexController extends Controller
{
    public function actionCallback()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = \Yii::$app->request->getRawBody();
        $result = EventServiceTrait::eventHandler($data);

        return $result;
    }

}
