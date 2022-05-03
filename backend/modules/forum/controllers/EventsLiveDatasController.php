<?php

namespace backend\modules\forum\controllers;

use backend\service\sports\EventsLiveDatasService;
use Yii;
use backend\models\sports\EventsLiveDatas;
use backend\models\searchs\EventsLiveDatas as EventsLiveDatasSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * EventsLiveDatasController implements the CRUD actions for EventsLiveDatas model.
 */
class EventsLiveDatasController extends BaseController
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
     * Lists all EventsLiveDatas models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new EventsLiveDatasSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single EventsLiveDatas model.
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
     * Creates a new EventsLiveDatas model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new EventsLiveDatas();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing EventsLiveDatas model.
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
     * @desc 比赛关联
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionGameRelated(){

        $hasRelatedDatas = EventsLiveDatasService::getRelatedDatas();
        $queryParams = \yii::$app->request->queryParams;

        $sport_type = $queryParams['SportType']['sport_type'] ? : 1;
        $sport_types = EventsLiveDatasService::getSportTypes($this->_user_id);
        #if ($model->load(Yii::$app->request->post()) && $model->save()) {
        #    return $this->redirect(['view', 'id' => $model->id]);
        #}

        return $this->render('create', [
            'sport_types' => $sport_types,
            'sport_type' => $sport_type,
            'model' => [],
        ]);
    }

    /**
     * Deletes an existing EventsLiveDatas model.
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
     * Finds the EventsLiveDatas model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return EventsLiveDatas the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = EventsLiveDatas::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
