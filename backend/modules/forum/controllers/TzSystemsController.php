<?php

namespace backend\modules\forum\controllers;

use backend\service\HN0898Service;
use common\service\CommonService;
use Yii;
use backend\models\TzSystems;
use backend\models\searchs\TzSystems as TzSystemsSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * TzSystemsController implements the CRUD actions for TzSystems model.
 */
class TzSystemsController extends BaseController
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
     * Lists all TzSystems models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new TzSystemsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single TzSystems model.
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
     * @desc 更新记录status状态 0/1
     * @param $id
     * @return \yii\web\Response
     */
    public function actionSwitchStatus($id){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $rst = HN0898Service::updateStatus($id, $model = '\backend\models\TzSystems');

        return $this->redirect(['index', 'UserSysPlans[lottery_type]'=>$rst['lottery_type']]);
    }

    /**
     * Creates a new TzSystems model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new TzSystems();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing TzSystems model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $post = Yii::$app->request->post();
        if(!empty($post)){
            $post['TzSystems']['tz_types'] && $post['TzSystems']['tz_types'] = implode(',',$post['TzSystems']['tz_types']);
            $post['TzSystems']['status'] = $post['TzSystems']['status'][0];
        }

        if ($model->load($post) && $model->save()) {
            return $this->redirect(['index']);
        }

        $allTzTypes = CommonService::getAllTzTypes();
        return $this->render('update', [
            'allTzTypes' => $allTzTypes,
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing TzSystems model.
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
     * Finds the TzSystems model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return TzSystems the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = TzSystems::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
