<?php
namespace backend\modules\api\controllers;

use backend\service\FootBallService;

class ValidateController extends Controller
{
    /**
     * @desc 更新用户状态
     * @param $id
     * @param $status
     * @return array|\yii\web\Response
     */
    public function actionValidateSecret(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        return FootBallService::validateSecret($post);
    }

}
