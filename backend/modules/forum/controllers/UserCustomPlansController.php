<?php

namespace backend\modules\forum\controllers;

use backend\models\TzSystems;
use backend\service\TzService;
use backend\service\UserCustomPlansService;
use common\models\AdminModel;
use Yii;
use backend\models\UserCustomPlans;
use backend\models\searchs\UserCustomPlans as UserCustomPlansSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\service\BaseNumService;
use backend\models\User;
use backend\service\HN0898Service;
use backend\service\SscDataService;

/**
 * UserCustomPlansController implements the CRUD actions for UserCustomPlans model.
 */
class UserCustomPlansController extends BaseController
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
     * Lists all UserCustomPlans models.
     * @return mixed
     */
    public function actionIndex()
    {
        $account =AdminModel::findOne(Yii::$app->user->id)['account'];
        $searchModel = new UserCustomPlansSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        if($account != ''){
            $queryParams['UserFollowData']['account'] = $account;
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single UserCustomPlans model.
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
     * Creates a new UserCustomPlans model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $playway = \Yii::$app->request->queryParams['playway'];
        $model = new UserCustomPlans();
        UserCustomPlansService::preOpData($this->_post, $playway, $this->_account);
        $model->tz_sites = TzService::getTzSites();
        $this->_post['UserCustomPlans']['account'] = $this->_account;
        $flag = $this->_post['UserCustomPlans']['flag'];
        if ($model->load($this->_post) && $flag && $model->save()) {
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'playway' => $playway,
        ]);
    }

    /**
     * Updates an existing UserCustomPlans model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $playway = $model->playway;
        UserCustomPlansService::preOpData($this->_post, $playway, $this->_account);
        $this->_post['UserCustomPlans']['account'] = $this->_account;
        $flag = $this->_post['UserCustomPlans']['flag'];

        if ($model->load($this->_post) && $flag && $model->save()) {
            return $this->redirect(['index']);
        }
        //p($position,0);
        //p($model->getErrors());
        $model->hezhis = explode(',',$model->hezhis);
        $model->positions = explode('|',$model->positions);
        if(in_array($model->playway, [2,3])){
            $tmpArr = explode(',',$model->codes);
            $model->position_1 = SscDataService::justDataSingleOrDouble($tmpArr[0]);
            $model->position_2 = SscDataService::justDataSingleOrDouble($tmpArr[1]);
            $model->position_3 = SscDataService::justDataSingleOrDouble($tmpArr[2]);
            $model->position_4 = SscDataService::justDataSingleOrDouble($tmpArr[3]);
        }

        return $this->render('update', [
            'model' => $model,
            'playway'=>$model->playway,
        ]);
    }

    public function actionUpdateStatus($id){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $rst = HN0898Service::updateCustomPlansStatus($id, $this->_account);

        return $this->redirect(['index']);

        return $rst;
    }

    /**
     * Deletes an existing UserCustomPlans model.
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
     * Finds the UserCustomPlans model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return UserCustomPlans the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = UserCustomPlans::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
