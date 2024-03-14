<?php
/**
 * Created by PhpStorm.
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\api\controllers;

use backend\controllers\BaseController;
use common\tools\Tool_Common;
use common\service\open\telegram\TelegramMessageService;
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

    public function __construct($id, $module, TelegramMessageService $TelegramMessageService, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->service = $TelegramMessageService;
    }

    /**
     * @desc 消息推送
     * @return array
     */
    public function actionCallback(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        //p([$this->_post, $this->_get]);
        $this->service->callbackMessage($this->_post, $this->_get);
        Tool_Common::log('/telegram/'.__FUNCTION__,'INFO', '聊天信息', ['get'=>$this->_get, 'post'=>$this->_post]);

        return ['post'=>$this->_post];
    }

}
