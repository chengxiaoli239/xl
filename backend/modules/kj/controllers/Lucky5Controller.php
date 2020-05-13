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
        $data = Lucky5::getLotteryLucky($type);
        return $data;
    }

    /**
     * @desc 幸运五星彩 - 实时资讯网 https://cc138001.com
     * @param string $type
     * @return array
     */
    public function actionShiXun($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = Lucky5::getLotteryShiXun($type);
        return $data;
    }

    /**
     * @desc 幸运五星彩 - 实时资讯网 https://cc138001.com
     * @param string $type
     * @return array
     */
    public function actionShiXunOne($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = Lucky5::getLotteryShiXunOne($type);
        return $data;
    }
}