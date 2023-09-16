<?php
/**
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\eyunapi\controllers;

use common\service\wechat\eyun\api\EventServiceTrait;
use common\services\kuaishou\api\KuaishouService;
use common\tools\Tool_Common;
use Yii;
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
