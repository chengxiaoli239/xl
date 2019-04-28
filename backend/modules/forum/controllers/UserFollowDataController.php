<?php

namespace backend\modules\forum\controllers;

use backend\models\User;
use backend\service\HN0898Service;
use backend\service\SscDataService;
use backend\service\UserFollowDataService;
use common\models\AdminModel;
use Yii;
use backend\models\UserFollowData;
use backend\models\searchs\UserFollowData as UserFollowDataSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\service\BaseNumService;

/**
 * UserFollowDataController implements the CRUD actions for UserFollowData model.
 */
class UserFollowDataController extends BaseController
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
     * Lists all UserFollowData models.
     * @return mixed
     */
    public function actionIndex()
    {
        $account = User::findOne(['admin_id'=>Yii::$app->user->id])['account'];
        //p([$account,Yii::$app->user->id]);
        $queryParams = Yii::$app->request->queryParams;
        if($account != ''){
            $queryParams['UserFollowData']['account'] = $account;
        }

        $searchModel = new UserFollowDataSearch();
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single UserFollowData model.
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
     * Creates a new UserFollowData model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $playway = \Yii::$app->request->queryParams['playway'];
        $model = new UserFollowData();
        UserFollowDataService::preOpData($this->_post, $playway);
        $this->_post['UserFollowData']['account'] = $this->_account;
        $flag = SscDataService::just3DwRight($this->_post);
        if(!$flag){
            $model->addError('position','投注格式错误');
        }

        if ($model->load($this->_post) && $flag && $model->save()) {
            //return $this->redirect(['view', 'id' => $model->id]);
            return $this->redirect(['index']);
        }


        return $this->render('create', [
            'model' => $model,
            'playway' => $playway,
        ]);
    }

    /**
     * Updates an existing UserFollowData model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $this->_post = Yii::$app->request->post();
        $playway = $model->playway;
        UserFollowDataService::preOpData($this->_post, $playway);

        if ($model->load($this->_post) && $model->save()) {
            return $this->redirect(['index']);
        }
        $tmpArr = explode(',',$model->code);
        $model->position_1 = SscDataService::justDataSingleOrDouble($tmpArr[0]);
        $model->position_2 = SscDataService::justDataSingleOrDouble($tmpArr[1]);
        $model->position_3 = SscDataService::justDataSingleOrDouble($tmpArr[2]);
        $model->position_4 = SscDataService::justDataSingleOrDouble($tmpArr[3]);

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing UserFollowData model.
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

    public function actionUpdateStatus($id,$status){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $rst = HN0898Service::updateFollowDataStatus($id, $status, $this->_account);

        return $this->redirect(['index']);

        return $rst;
    }

    /**
     *@desc 立即投注
     */
    public function actionTzNow($id){
        $rst = HN0898Service::tzNow($this->_account, $id);

        return $this->redirect(['index']);
    }

    /**
     * Finds the UserFollowData model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return UserFollowData the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = UserFollowData::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
