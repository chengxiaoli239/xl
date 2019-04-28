<?php

namespace backend\modules\forum\controllers;

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
class WxMsgTypesController extends BaseController
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
     * Lists all WxMsgTypes models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new WxMsgTypesSearch();
        $queryParams = Yii::$app->request->queryParams;
        $queryParams['WxMsgTypes']['uid'] = $this->_user_id;
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single WxMsgTypes model.
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
     * Creates a new WxMsgTypes model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new WxMsgTypes();
        //Yii::$app->request->post()
        if($this->_post){
            $this->_post['WxMsgTypes']['updated_at'] = time();
            $this->_post['WxMsgTypes']['uid'] = $this->_user_id;
            if(!$this->_post['WxMsgTypes']['id']){
                $this->_post['WxMsgTypes']['created_at'] = time();
            }
        }

        if ($model->load($this->_post) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing WxMsgTypes model.
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
     * Deletes an existing WxMsgTypes model.
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
     * Finds the WxMsgTypes model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return WxMsgTypes the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = WxMsgTypes::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    /**
     * @desc 更新状态
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchStatus($id,$status){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        WxService::SwitchStatus($id, $status, $this->_account, 'WxMsgTypes');

        return $this->redirect(['index']);
    }


}
