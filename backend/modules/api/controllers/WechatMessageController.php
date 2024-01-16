<?php
namespace backend\modules\api\controllers;

use common\service\wechat\eyun\api\EventServiceTrait;
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

        $result = EventServiceTrait::eventHandler($post);
        Tool_Common::log('chatBet','INFO', '聊天消息接收', ['post'=>$post, 'result'=>$result]);

        return ['status'=>200, 'msg'=>'消息接收成功'];
    }

}
