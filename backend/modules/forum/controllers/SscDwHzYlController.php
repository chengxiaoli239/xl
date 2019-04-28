<?php

namespace backend\modules\forum\controllers;

use Yii;
use backend\models\SscDwHzYl;
use backend\models\searchs\SscDwHzYl as SscDwHzYlSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * SscDwHzYlController implements the CRUD actions for SscDwHzYl model.
 */
class SscDwHzYlController extends BaseController
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
     * Lists all SscDwHzYl models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SscDwHzYlSearch();
        $queryParams = Yii::$app->request->queryParams;
        $zhi = $queryParams['SscDwHzYl']['zhi'];
        $positions = $queryParams['SscDwHzYl']['positions'];
        if(!$zhi){
            $zhi = $queryParams['SscDwHzYl']['zhi'] = [8,9];
        }
        if(!$positions){
            $positions = $queryParams['SscDwHzYl']['positions'] = ['2,3', '3,4'];
        }
        $dataProvider = $searchModel->search($queryParams);
        $searchModel->zhi = $zhi;
        $searchModel->positions = $positions;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'zhi' => is_array($zhi) ? '合'.implode(',',$zhi) : '',
        ]);
    }

    /**
     * Displays a single SscDwHzYl model.
     * @param string $id
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
     * Creates a new SscDwHzYl model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new SscDwHzYl();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing SscDwHzYl model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
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
     * Deletes an existing SscDwHzYl model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the SscDwHzYl model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return SscDwHzYl the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SscDwHzYl::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
