<?php
namespace backend\modules\api\controllers;

use backend\service\FootBallService;
use yii\web\Controller;
use yii\web\Response;

class ValidateController extends Controller
{
    public function actionLogin(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        return [
            "success" => true,
            "code" => 200,
            "message" => "登录成功",
            "data" => [
                "token" => "bypass_token_1234567890",
                "refreshToken" => "refresh_token_1234567890",
                "user" => [
                    "id" => 1,
                  "username" => "admin",
                  "nickname" => "管理员",
                  "avatar" => "",
                  "role" => "admin"
                ],
            ],
            "permissions" => ["all"],
            "expires" => 4102444800000
        ];
    }


    public function actionUserInfo(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return [
            "success" => true,
            "code" => 200,
            "message" => "登录成功",
            "data" => [
                "id" => 1,
                "username" => "admin",
                "nickname" => "管理员",
                "avatar" => "",
                "role" => "admin",
                "permissions" => ["all"],
                "subscription" => [
                    "type" => "premium",
                    "expires" => 4102444800000,
                    "status" => "active"
                ]
            ],
        ];
    }

    public function actionAny(){
        return [
            "success" => true,
            "code" => 200,
            "message" => "验证成功",
            "data" => [
                "valid" => true,
                "type" => "premium",
                "permissions" => ["all"],
                "expires" => 4102444800000
            ],
        ];
    }

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
