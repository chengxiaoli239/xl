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
        $post = \Yii::$app->request->post();
        $data = QxcKcw::getLotteryNo($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 360
     * @return json|xml
     */
    public function action360($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = Qxc360::getLotteryNo($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 官网
     * @param string $type
     * @return array|bool
     */
    public function actionTcw($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = QxcTcw::getLotteryNo($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 官网
     * @param string $type
     * @return array|bool
     */
    public function actionTcwBatch($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = QxcTcw::getBatchLotteryNo($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 官网
     * @param string $type
     * @return array|bool
     */
    public function actionQxcBatch($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = QxcTcw::QixingCaiBatch($type, $post['is_auto']);
        return $data;
    }

}