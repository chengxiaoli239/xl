<?php

namespace backend\modules\forum\controllers;

use backend\service\HN0898Service;
use backend\service\UserSysPlansService;
use common\service\CommonService;
use Yii;
use backend\models\BetErrorPlansTask;
use backend\models\searchs\BetErrorPlansTask as BetErrorPlansTaskSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * BetErrorPlansTaskController implements the CRUD actions for BetErrorPlansTask model.
 */
class BetErrorPlansTaskController extends BaseController
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
     * Lists all BetErrorPlansTask models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new BetErrorPlansTaskSearch();
        $queryParams = Yii::$app->request->queryParams;

        $lottery_type = CommonService::getIndexLotteryType($this->_user_id, $queryParams);
        $lottery_types = UserSysPlansService::getMyLotteryTypes($this->_user_id);
        $queryParams['BetErrorPlansTask']['lottery_type'] = $lottery_type;

        if($this->_user_id !== 1){
            $queryParams['BetErrorPlansTask']['lottery_type'] = $lottery_type;
            $queryParams['BetErrorPlansTask']['uid'] = $this->_user_id;
        }

        $dataProvider = $searchModel->search($queryParams);
        $data = [
            'lottery_types' => $lottery_types,
            'lottery_type' => $lottery_type,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'currentQihao' => HN0898Service::getQihao($lottery_type),
            'plan_ids' => $queryParams['BetErrorPlansTask']['plan_ids'] ?? '',
            'qihao' => $queryParams['BetErrorPlansTask']['qihao'] ?? '',
        ];

        if($this->_user_id !== 1){ # 超级管理员
            $view = 'index';
        }else{
            $view = 'index_admin';
        }
        return $this->render($view, $data);
    }

    /**
     * @desc 更新投注状态
     * @param $id
     * @param $status
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionSwitchStatus(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $id = $post['id'];
        $Model = $this->findModel($id);
        if($Model->uid != $this->_user_id && $this->_user_id != 1){
            return ['status'=>400, 'msg'=>'非法请求'];
        }
        //$rStatus = $Model->status; # 可根据是否可以重推
        $rst = HN0898Service::updateStatus($id, '\backend\models\BetErrorPlansTask');

        return $rst;
    }

    /**
     * Displays a single BetErrorPlansTask model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new BetErrorPlansTask model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new BetErrorPlansTask();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing BetErrorPlansTask model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * @desc 更新用户状态
     * @param $id
     * @return \yii\web\Response
     */
    public function actionSwitchTaskStatus(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(\Yii::$app->user->id == 1){
            $rst = HN0898Service::updateStatus($post['rid'], $model = '\backend\models\BetErrorPlansTask', 'status');
        }

        return $rst;
    }

    /**
     * Deletes an existing BetErrorPlansTask model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the BetErrorPlansTask model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return BetErrorPlansTask the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = BetErrorPlansTask::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
