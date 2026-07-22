<?php

namespace backend\modules\api\controllers;

use backend\service\clients\ClientAuthService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class ClientAuthController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'login' => ['POST'],
                    'validate' => ['POST'],
                ],
            ],
        ];
    }

    public function actionLogin(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $post = Yii::$app->request->post();

        return ClientAuthService::login(
            (string)($post['username'] ?? ''),
            (string)($post['password'] ?? ''),
            (string)Yii::$app->request->userIP
        );
    }

    public function actionValidate(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $post = Yii::$app->request->post();

        return ClientAuthService::validateToken((string)($post['access_token'] ?? ''));
    }
}
