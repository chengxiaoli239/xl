<?php

namespace backend\modules\agent\controllers;

use backend\service\AgentUsersService;
use Yii;
use backend\models\AgentUsersBalanceFlows;
use backend\models\searchs\AgentUsersBalanceFlows as AgentUsersBalanceFlowsSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * AgentUsersBalanceFlowsController implements the CRUD actions for AgentUsersBalanceFlows model.
 */
class AgentUsersBalanceFlowsController extends BaseController
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
     * Lists all AgentUsersBalanceFlows models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AgentUsersBalanceFlowsSearch();
        $queryParams = Yii::$app->request->queryParams;
        if(\Yii::$app->user->id != 1){
            $queryParams['AgentUsersBalanceFlows']['agent_id'] = \Yii::$app->user->id;
        }
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single AgentUsersBalanceFlows model.
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
     * @return array
     */
    public function actionUserFlowsCheck(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $desc = $this->_post['type'] == 1 ? '用户申请->代理审核' : '代理操作';

        $rst = AgentUsersService::userFlowsCheck($this->_post, \Yii::$app->user->id, $desc);

        return $rst;
    }

    /**
     * Creates a new AgentUsersBalanceFlows model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new AgentUsersBalanceFlows();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing AgentUsersBalanceFlows model.
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
     * Deletes an existing AgentUsersBalanceFlows model.
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
     * Finds the AgentUsersBalanceFlows model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return AgentUsersBalanceFlows the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AgentUsersBalanceFlows::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
