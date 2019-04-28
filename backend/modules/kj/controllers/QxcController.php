<?php
namespace backend\modules\kj\controllers;

use common\kj\qxc\Qxc360;
use common\kj\qxc\QxcKcw;
use common\kj\qxc\QxcTcw;
use Yii;
use yii\web\Controller;


class QxcController extends Controller
{
    /**
     * @desc 开彩网
     * @return array
     */
    public function actionKcw($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = QxcKcw::getLotteryNo($type);
        return $data;
    }

    /**
     * @desc 360
     * @return json|xml
     */
    public function action360($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = Qxc360::getLotteryNo($type);
        return $data;
    }

    /**
     * @desc 官网
     * @param string $type
     * @return array|bool
     */
    public function actionTcw($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = QxcTcw::getLotteryNo($type);
        return $data;
    }
    /**
     * @desc 官网
     * @param string $type
     * @return array|bool
     */
    public function actionTcwBatch($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = QxcTcw::getBatchLotteryNo($type);
        return $data;
    }
}