<?php
/**
 * Created by PhpStorm.
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

class TelegramController extends Controller
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
     * @desc 消息推送
     * @return array
     */
    public function actionCallback(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        Tool_Common::log('/telegram/'.__FUNCTION__,'INFO', '聊天信息', ['post'=>$post]);

        return ['post'=>$post];
    }

}
