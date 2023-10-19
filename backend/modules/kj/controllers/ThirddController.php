<?php
namespace backend\modules\kj\controllers;

use common\kj\ssc\Lucky5;
use common\kj\ssc\Thirdd;
use Yii;
use yii\web\Controller;
use common\service\CommonService;


class ThirddController extends Controller
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
     * @desc 官方
     * @return json|xml
     */
    public function actionFuCai3d($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $Thirdd = new Thirdd();
        $data = $Thirdd->getFuCai3d($type);

        return $data;
    }

    /**
     * @desc 幸运五星彩 - 实时资讯网 https://cc138001.com
     * @param string $type
     * @return array
     */
    public function actionShiXun($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = Lucky5::getLotteryShiXun($type, $post['is_auto']);
        return $data;
    }


}
