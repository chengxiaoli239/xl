<?php

namespace backend\modules\forum\controllers;

use backend\service\UserSysPlansService;
use common\service\CommonService;
use Yii;
use backend\models\StaticPerHzPerdateProfits;
use backend\models\searchs\StaticPerHzPerdateProfits as StaticPerHzPerdateProfitsSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * StaticPerHzPerdateProfitsController implements the CRUD actions for StaticPerHzPerdateProfits model.
 */
class StaticPerHzPerdateProfitsController extends BaseController
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
     * Lists all StaticPerHzPerdateProfits models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new StaticPerHzPerdateProfitsSearch();
        $queryParams = Yii::$app->request->queryParams;
        $lottery_types = UserSysPlansService::getMyLotteryTypes($this->_user_id);
        /*
        if(!$queryParams['StaticPerHzPerdateProfits']['lottery_type']){
            $lottery_type = $lottery_types[0]['lottery_type'];
        }else{
            $lottery_type = $queryParams['StaticPerHzPerdateProfits']['lottery_type'];
        }
        */
        $lottery_type = CommonService::getIndexLotteryType($this->_user_id, $queryParams);
        $queryParams['StaticPerHzPerdateProfits']['lottery_type'] = $lottery_type;

        $dataProvider = $searchModel->search($queryParams);
        return $this->render('index', [
            'lottery_types' => $lottery_types,
            'lottery_type' => $lottery_type,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single StaticPerHzPerdateProfits model.
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
     * Creates a new StaticPerHzPerdateProfits model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new StaticPerHzPerdateProfits();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing StaticPerHzPerdateProfits model.
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
     * Deletes an existing StaticPerHzPerdateProfits model.
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
     * Finds the StaticPerHzPerdateProfits model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return StaticPerHzPerdateProfits the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = StaticPerHzPerdateProfits::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
