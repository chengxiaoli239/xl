<?php

namespace backend\modules\forum\controllers;

use Yii;
use backend\models\SscDwsHzNums;
use backend\models\searchs\SscDwsHzNums as SscDwsHzNumsSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\service\SscDataService;

/**
 * SscDwsHzNumsController implements the CRUD actions for SscDwsHzNums model.
 */
class SscDwsHzNumsController extends BaseController
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
     * Lists all SscDwsHzNums models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SscDwsHzNumsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single SscDwsHzNums model.
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
     * @desc 定位和值区间趋势
     * @return string
     */
    public function actionEcharts(){
        $searchModel = new SscDwsHzNumsSearch();
        $queryParams = Yii::$app->request->queryParams;
        $dataProvider = $searchModel->search($queryParams);
        $periods = $queryParams['SscDwsHzNums']['periods'];  // 20期，50期，100期，120期...
        $positions = $queryParams['SscDwsHzNums']['positions'];
        $hezhi = $queryParams['SscDwsHzNums']['hezhi'];
        $hezhi = $hezhi ? $hezhi : 9;
        //$periodsArr = [ '20', '50', '100', '120', '200', '300', '500', '1000', '2000', '5000' ];
        $periodsArr = [
            //20,
            //50,
            //100,
            //'120',
            200,
            //'500', '1000',
            '2000',
             '5000'
        ];
        $positions = $positions ? $positions : '2,3';
        $chartsData = SscDataService::getHzNumsChartsData($hezhi, $periodsArr, $positions);
        //p($chartsData);

        $searchModel->positions = $positions;
        $searchModel->hezhi = $hezhi;

        return $this->render('echarts', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'chartsData' => $chartsData,
            'periods' => $periods,
            'positions' => $positions,
        ]);
    }


    /**
     * Creates a new SscDwsHzNums model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new SscDwsHzNums();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing SscDwsHzNums model.
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
     * Deletes an existing SscDwsHzNums model.
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
     * Finds the SscDwsHzNums model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return SscDwsHzNums the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SscDwsHzNums::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
