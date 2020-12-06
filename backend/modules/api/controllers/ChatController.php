<?php
/**
 * Created by PhpStorm.
 * User:wangyegao
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\api\controllers;

use backend\service\ChatCommonBetService;
use backend\service\HN0898Service;
use common\tools\Tool_Common;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class ChatController extends Controller
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
    public function actionPostDesc(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $token = $post['params']['token'];
        $txt = $post['params']['tz_txt'];

        $rst = ChatCommonBetService::postDesc($token, trim($txt), $lottery_type = 5);
        ChatCommonBetService::recordPostDesc($post, $rst);
        Tool_Common::log('chatBet','INFO', '聊天室下注', ['post'=>$post, 'rst'=>$rst]);

        return $rst;
        //return ['status'=>200, 'msg'=>'接收到请求', 'token'=>$token, 'tz_params'=>$post['params']];
    }

    /**
     * @desc 获取用户信息接口
     * @return array
     */
    public function actionGetUserInfo(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $token = $post['token'];

        $rst = ChatCommonBetService::getUserInfo($token);

        return $rst;
    }
}