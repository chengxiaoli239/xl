<?php
namespace backend\modules\api\controllers;

use backend\service\FootBallService;
use yii\web\Controller;
use yii\web\Response;

class ValidateController extends Controller
{
    /**
     * @desc 更新用户状态
     * @return array|Response
     */
    public function actionValidateSecret(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        return FootBallService::validateSecret($post);
    }

}
