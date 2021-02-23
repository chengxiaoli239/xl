<?php

namespace backend\modules\forum\controllers;

use backend\service\SscDataService;
use Yii;
use backend\models\SscDwHzStatic;
use backend\models\searchs\SscDwHzStatic as SscDwHzStaticSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use backend\models\SscKjData;

/**
 * SscDwHzStaticController implements the CRUD actions for SscDwHzStatic model.
 */
class SscDwHzStaticController extends BaseController
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
     * Lists all SscDwHzStatic models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SscDwHzStaticSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @desc 号码200期出现次数柱状图
     * @return string
     */
    public function actionEcharts(){
        $searchModel = new SscDwHzStaticSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $periods = Yii::$app->request->queryParams['periods'];
        $positions = Yii::$app->request->queryParams['positions'];
        $periods = $periods ? $periods : 200;
        $positions = $positions ? $positions : '2,3';
        $chartsData = SscDataService::getDwHzChartsData($periods, $positions);
        //p($chartsData);

        return $this->render('echarts', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'chartsData' => $chartsData,
            //'positions' => $positions,
        ]);
    }

    /**
     * @desc 用户利润趋势，待完成
     * @return string
     */
    public function actionProfitsEcharts(){
        $searchModel = new SscDwHzStaticSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $periods = Yii::$app->request->queryParams['periods'];  // 今天，昨天，本周，本月
        $positions = Yii::$app->request->queryParams['positions'];
        $periods = $periods ? $periods : 200;
        $positions = $positions ? $positions : '2,3';
        $chartsData = SscDataService::getProfitsChartsData($this->_account, $periods, $positions);
        //p($chartsData);

        return $this->render('echarts', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'chartsData' => $chartsData,
            //'positions' => $positions,
        ]);
    }


    /**
     * Displays a single SscDwHzStatic model.
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
     * Creates a new SscDwHzStatic model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new SscDwHzStatic();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing SscDwHzStatic model.
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
     * Deletes an existing SscDwHzStatic model.
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
     * Finds the SscDwHzStatic model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return SscDwHzStatic the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SscDwHzStatic::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    /**
     * @desc 测试专用
     */
    public function actionTest(){
        $intervals = [
            20,50,100,120,200,300,500,1000,2000
        ];
        foreach ($intervals as $key => $interval) {
            $data = SscDataService::heZhiStatic($interval);
        }
        $data = SscDataService::updateHeZhiYL(); // 更新定位和值遗漏表

        p($data);
    }





























}
