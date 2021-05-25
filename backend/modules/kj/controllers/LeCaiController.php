<?php
namespace backend\modules\kj\controllers;

use common\kj\cqssc\CqsscKcw;
use common\kj\lecai\LeCaiService;
use Yii;
use yii\web\Controller;


class LeCaiController extends Controller
{
    /**
     * @desc 乐彩 - 台湾快五
     * @return json|xml
     */
    public function actionK5($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = LeCaiService::getLotteryK5($type, $lottery_type=18, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 乐彩 - 台湾快五 网盘
     * @return json|xml
     */
    public function actionK5ByUser($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = LeCaiService::getLotteryByUser($type, $lottery_type=18, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 乐彩 - 台湾快五 网盘
     * @return json|xml
     */
    public function actionK5Batch($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        //$post = \Yii::$app->request->post();
        $data = LeCaiService::getLotteryBatch($lottery_type=18);
        return $data;
    }

    /**
     * @desc 乐彩 - 台湾快五 网盘
     * @return json|xml
     */
    public function actionK5BatchGw($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        //$post = \Yii::$app->request->post();
        $data = LeCaiService::getLotteryBatchGw($lottery_type=18);
        return $data;
    }

}