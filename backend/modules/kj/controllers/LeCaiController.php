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
     * @desc 开800 - 台湾宾果
     * @return json|xml
     */
    public function actionK5ByUser($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = LeCaiService::getLotteryByUser($type, $lottery_type=18, $post['is_auto']);
        return $data;
    }

}