<?php

namespace backend\modules\forum\controllers;

use backend\service\HN0898Service;
use backend\service\WxService;
use Yii;
use backend\models\WxMsgTypes;
use backend\models\searchs\WxMsgTypes as WxMsgTypesSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * WxMsgTypesController implements the CRUD actions for WxMsgTypes model.
 */
class CommonSetController extends BaseController
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
     * @desc 更新用户状态
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchStatus(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(\Yii::$app->user->id == 1){
            $rst = HN0898Service::updateStatus($post['id'], $model = '\backend\models\\'.$post['model'], $post['field']);
        }

        return $rst;
    }


}
