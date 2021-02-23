<?php

namespace backend\modules\forum\controllers;
use backend\service\SscDataService;
use yii\filters\VerbFilter;

class ToolsController extends \backend\controllers\BaseController
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

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionClearTablesData(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if($this->_user_id != 1) return ['stauts'=>'300', 'msg'=>'不是管理员无权限'];
        $rst = SscDataService::clearDataTables();
        if($rst['status'] != 200) p($rst);

        return $this->redirect(['index']);
    }

}
