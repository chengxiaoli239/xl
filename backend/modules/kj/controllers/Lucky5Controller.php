<?php
namespace backend\modules\kj\controllers;

use common\kj\ssc\Lucky5;
use Yii;
use yii\web\Controller;
use common\service\CommonService;


class Lucky5Controller extends Controller
{
    /**
     * @desc 幸运五星 - 新疆时时彩
     * @return json|xml
     */
    public function actionBatch($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = Lucky5::batch($type);
        return $data;
    }

    /**
     * @desc 幸运五星 - 抓网盘 - 在用
     * @return json|xml
     */
    public function actionIndex($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);

        return Lucky5::getLotteryLucky($type);
    }

    /**
     * @desc 幸运五星彩 - 实时资讯网 https://cc138001.com
     * @param string $type
     * @return array
     */
    public function actionShiXun($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();

        return Lucky5::getLotteryShiXun($type, $post['is_auto']);
    }

    /**
     * @desc 幸运五星彩 - 实时资讯网 https://web01.cc138001.com
     * @param string $type
     * @return array
     */
    public function actionShiXunOne($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = Lucky5::getLotteryShiXunOne($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 幸运五星彩 - 实时资讯网 https://web01.cc138001.com - 新接口
     * @param string $type
     * @return array
     */
    public function actionShiXunOneNew($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = Lucky5::getLotteryShiXunOneNew($type, $post['is_auto']);
        return $data;
    }
}