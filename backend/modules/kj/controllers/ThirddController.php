<?php
namespace backend\modules\kj\controllers;

use common\kj\ssc\Thirdd;
use Yii;
use yii\web\Controller;


class ThirddController extends Controller
{
    /**
     * @desc 官方
     * @return array|json|xml|bool
     */
    public function actionFuCai($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $Thirdd = new Thirdd();
        $post = \Yii::$app->request->post();
        $is_auto = $post['is_auto']??1;
        $data = $Thirdd->getFuCai3d($type, $is_auto);

        return $data;
    }


}
