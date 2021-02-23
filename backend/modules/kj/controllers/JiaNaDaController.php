<?php
namespace backend\modules\kj\controllers;

use common\kj\ssc\JiaNaDa;
use Yii;
use yii\web\Controller;
use common\service\CommonService;


class JiaNaDaController extends Controller
{
    /**
     * @desc 彩种:加拿大 - 抓官网
     * @return json|xml
     */
    public function actionIndex($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = JiaNaDa::getLottery($type);
        return $data;
    }

    /**
     * @desc 彩种:加拿大 - 抓官网
     * @return json|xml
     */
    public function actionCanada($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = JiaNaDa::getLotteryCanada($type);
        return $data;
    }


}