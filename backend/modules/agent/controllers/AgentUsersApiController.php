<?php

namespace backend\modules\agent\controllers;

use backend\service\AgentUsersService;
use backend\service\ChatCommonBetService;
use backend\service\HN0898Service;
use Yii;
use backend\models\AgentUsers;
use backend\models\searchs\AgentUsers as AgentUsersSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * AgentUsersController implements the CRUD actions for AgentUsers model.
 */
class AgentUsersApiController extends BaseController
{
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
     * @desc 获取用户信息接口
     * @return array
     */
    public function actionGetUserInfo(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $rst = ChatCommonBetService::getUserInfo($this->_post['token']);

        return $rst;
    }

}
