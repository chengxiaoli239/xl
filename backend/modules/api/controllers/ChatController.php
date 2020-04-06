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
    public function actionBet(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $token = $post['params']['token'];
        $txt = $post['params']['txt'];

        $rst = ChatCommonBetService::betByDesc($token, $txt);
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/chatBet','INFO', '聊天室下注', $post);

        return ['status'=>200, 'msg'=>'接收到请求', 'token'=>$token, 'tz_params'=>$post['params']];
    }
}