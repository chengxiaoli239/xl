<?php
/**
 * Created by PhpStorm.
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\api\controllers;

use backend\controllers\BaseController;
use common\tools\Tool_Common;
use common\service\open\telegram\MessageService;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class TelegramController extends BaseController
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

    public function __construct($id, $module, MessageService $telegramMessageService, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->service = $telegramMessageService;
    }

    /**
     * @desc 消息推送
     * @return array
     */
    public function actionCallback(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $token = $this->_get['token'];
            if(empty($token)){
                throw_info('token is null');
            }
            //p([$this->_post, $this->_get]);
            $this->service->callbackMessage($this->_post, $token);
        }catch (\Exception $e){
            Tool_Common::log('/telegram/'.__FUNCTION__,'ERR', '聊天信息接收-异常', ['token'=>$token, 'post'=>$this->_post]);
            return ['code'=>300, 'data'=>[], 'message'=>'fail'];
        }
        Tool_Common::log('/telegram/'.__FUNCTION__,'INFO', '聊天信息', ['token'=>$token, 'post'=>$this->_post]);

        return ['code'=>200, 'data'=>['post'=>$this->_post], 'message'=>'success'];
    }

}
