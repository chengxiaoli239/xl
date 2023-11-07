<?php

namespace backend\modules\statics\controllers;

use Yii;
use backend\models\statics\Static3dUserProfitsDayAll;
use backend\models\searchs\statics\Static3dUserProfitsDayAll as Static3dUserProfitsDayAllSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * Static3dUserProfitsDayController implements the CRUD actions for Static3dUserProfitsDayAll model.
 */
class Static3dUserProfitsDayAllController extends BaseController
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
     * Lists all Static3dUserProfitsDayAll models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new Static3dUserProfitsDayAllSearch();
        $queryParams = Yii::$app->request->queryParams;
        if(!empty($queryParams) && empty($queryParams['Static3dUserProfitsDayAll']['date'])){
            $queryParams['Static3dUserProfitsDayAll']['date'] = date('Y-m-d');
        }

        if($this->_user_id != 1){
            $queryParams['Static3dUserProfitsDayAll']['user_id'] = $this->_user_id;
        }

        $dataProvider = $searchModel->search($queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Static3dUserProfitsDayAll model.
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
     * Creates a new Static3dUserProfitsDayAll model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Static3dUserProfitsDayAll();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Static3dUserProfitsDayAll model.
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
     * Deletes an existing Static3dUserProfitsDayAll model.
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
     * Finds the Static3dUserProfitsDayAll model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Static3dUserProfitsDayAll the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Static3dUserProfitsDayAll::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
