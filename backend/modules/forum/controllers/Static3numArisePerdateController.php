<?php

namespace backend\modules\forum\controllers;

use backend\service\UserSysPlansService;
use common\service\CommonService;
use Yii;
use backend\models\Static3numArisePerdate;
use backend\models\searchs\Static3numArisePerdate as Static3numArisePerdateSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * Static3numArisePerdateController implements the CRUD actions for Static3numArisePerdate model.
 */
class Static3numArisePerdateController extends BaseController
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
     * Lists all Static3numArisePerdate models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new Static3numArisePerdateSearch();
        $queryParams = Yii::$app->request->queryParams;
        $lottery_types = UserSysPlansService::getMyLotteryTypes($this->_user_id);
        /*
        if(!$queryParams['Static3numArisePerdate']['lottery_type']){
            $lottery_type = $lottery_types[0]['lottery_type'];
        }else{
            $lottery_type = $queryParams['Static3numArisePerdate']['lottery_type'];
        }
        */
        $lottery_type = CommonService::getIndexLotteryType($this->_user_id, $queryParams);
        $queryParams['Static3numArisePerdate']['lottery_type'] = $lottery_type;

        $dataProvider = $searchModel->search($queryParams);
        return $this->render('index', [
            'lottery_types' => $lottery_types,
            'lottery_type' => $lottery_type,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Static3numArisePerdate model.
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
     * Creates a new Static3numArisePerdate model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Static3numArisePerdate();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Static3numArisePerdate model.
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
     * Deletes an existing Static3numArisePerdate model.
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
     * Finds the Static3numArisePerdate model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Static3numArisePerdate the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Static3numArisePerdate::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
