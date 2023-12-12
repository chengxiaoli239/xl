<?php
namespace backend\modules\eyunapi\controllers;

use common\service\wechat\eyun\api\EventServiceTrait;
use common\tools\Tool_Common;
use yii\helpers\Json;
use yii\web\Controller;

class IndexController extends Controller
{
    public function actionCallback()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = \Yii::$app->request->getRawBody();
        $data = Json::decode($data, 320);
        if(empty($data)){
            return ['code'=>10000, 'msg'=>'消息不能为空'];
        }
        $result = EventServiceTrait::eventHandler($data);
        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', 'e云消息通知', ['data'=>$data, 'result'=>$result]);

        return $result;
    }
}
