<?php

namespace backend\modules\agent\controllers;

use backend\service\AgentUsersService;
use backend\service\ChatCommonBetService;
use backend\service\HN0898Service;
use Yii;
use backend\models\AgentUsers;
use backend\models\searchs\AgentUsers as AgentUsersSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * AgentUsersController implements the CRUD actions for AgentUsers model.
 */
class AgentUsersController extends BaseController
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
     * Lists all AgentUsers models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AgentUsersSearch();
        $queryParams = Yii::$app->request->queryParams;
        if($this->_user_id !== 1) $queryParams['AgentUsers']['agent_id'] = $this->_user_id;
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single AgentUsers model.
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
     * Creates a new AgentUsers model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $agent_id = \Yii::$app->user->id;
        $model = new AgentUsers();

        AgentUsersService::opPreData($this->_post, $agent_id);
        //p($this->_post);
        $this->_post['images'] = AgentUsersService::getImages($agent_id);
        if ($model->load($this->_post) && $model->save()) {
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * @desc 更新投注状态
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchStatus($id,$status, $field){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $rst = AgentUsersService::updateAgentUsersStatus($id, $status, $this->_user_id, $field);

        return $this->redirect(['index', 'UserSysPlans[lottery_type]'=>$rst['lottery_type']]);
    }

    /**
     * @desc 变更用户数据入口
     * @return array
     */
    public function actionUpUserData(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $rst = ['status'=>200, 'msg'=>'修改成功'];

        $agent_id = \Yii::$app->user->id;
        $rst['data'] = AgentUsersService::actUpUserData($this->_post, $agent_id);

        return $rst;
    }

    /**
     * @desc 变更用户数据入口
     * @return array
     */
    public function actionGetToken(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        $agent_id = \Yii::$app->user->id;
        $rand = Yii::$app->getSecurity()->generateRandomString();
        $rst['token'] = AgentUsersService::getUserToken('rand_name', $agent_id, $rand);

        return $rst;
    }

    public function actionUserBalanceFlows(){
    }

    /**
     * @desc 获取用户信息接口
     * @return array
     */
    public function actionGetUserInfo(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $rst = ChatCommonBetService::getUserInfo($this->_post['token']);

        return $rst;
    }
    /**
     * Updates an existing AgentUsers model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $agent_id = \Yii::$app->user->id;
        $model = $this->findModel($id);

        AgentUsersService::opPreData($this->_post, $agent_id);
        if(!$this->_post['images']) $this->_post['images'] = AgentUsersService::getImages($agent_id);
        if ($model->load($this->_post) && $model->save()) {
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing AgentUsers model.
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
     * Finds the AgentUsers model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return AgentUsers the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AgentUsers::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
