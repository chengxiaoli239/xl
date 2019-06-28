<?php

namespace backend\modules\forum\controllers;

use Yii;
use backend\models\SscStaticYl;
use backend\models\searchs\SscStaticYl as SscStaticYlSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * SscStaticYlController implements the CRUD actions for SscStaticYl model.
 */
class SscStaticYlController extends BaseController
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
     * Lists all SscStaticYl models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SscStaticYlSearch();
        $queryParams = Yii::$app->request->queryParams;
        $queryParams['SscStaticYl']['status'] = 1;
        $type = $queryParams['SscStaticYl']['type'] ? $queryParams['SscStaticYl']['type'] : 2;
        $queryParams['SscStaticYl']['type'] = $type;
        if($type == 91){ # 热码
            $queryParams['SscStaticYl']['val'] = [
                # type:91 三现带双重热码
                '116','007','077','112','001','227','377','344','244','277',
                '288','447','448','446','688','779','188','788','066','122'
                /*
                '007','116','227','344','377','688','077','112','001','228',
                '277','288','447','008','244','355','399','122','448','499',
                '334','779','788','677','005','667','556','011','557','488'
                */
            ];
        }elseif ($type == 92){
             $queryParams['SscStaticYl']['val'] = [
                # type:92 四现不带双重热码
                '3557','0399','1127','1229','2399','1129','0288','1227','0559','1136',
                '1146','1158','1168','4599','0017','0067','2488','3499','4558','0015',
            ];
        }elseif($type == 93){
            $queryParams['SscStaticYl']['val'] = [
                # type:93 四现不带双重热码
                '0137','0345','0247','2348','0239','0568','0689','1234','0234','4578',
                '0356','0478','2378','0349','0125','0145','1259','1678','2359','3678'
            ];
        }
        $dataProvider = $searchModel->search($queryParams);

        $view = $type == 2 ? 'index' : 'indexs';
        return $this->render($view, [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single SscStaticYl model.
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
     * Creates a new SscStaticYl model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new SscStaticYl();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing SscStaticYl model.
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
     * Deletes an existing SscStaticYl model.
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
     * Finds the SscStaticYl model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return SscStaticYl the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SscStaticYl::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
