<?php
namespace backend\modules\api\controllers;

use common\tools\Tool_Common;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class WechatMessageController extends Controller
{
    public $enableCsrfValidation = false;
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * @desc 聊天室下注
     * @return array
     */
    public function actionMessageReceive(): array
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        Tool_Common::log('chatBet','INFO', '聊天消息接收', ['post'=>$post]);

        return ['status'=>200, 'msg'];
    }

}
