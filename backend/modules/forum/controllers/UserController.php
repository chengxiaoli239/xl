<?php

namespace backend\modules\forum\controllers;

use backend\models\Admin;
use backend\models\TzSystemsAuth;
use backend\models\searchs\TzSystemsUsers as TzSystemsUsersSearch;
use backend\models\TzSystemsUsers;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\UserService;
use common\models\AdminModel;
use common\service\CommonService;
use Yii;
use backend\models\User;
//use backend\models\searchs\User as UserSearch;
use backend\models\searchs\Admin as UserSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\service\HN0898Service;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends BaseController
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
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @desc ssc
     * @param $uid
     * @return string|\yii\web\Response
     */
    public function actionOpenSystems($uid){

        if(!$model = TzSystemsAuth::findOne(['uid'=>$uid])){
            $model = new TzSystemsAuth();
        }
        UserService::preOpenData($this->_post, $uid);

        if ($model->load($this->_post) && $model->save()) {
            UserService::saveTzSystemUsers(explode(',', $this->_post['TzSystemsAuth']['tz_systems_ids']), $uid);
            return $this->redirect(['index']);
        }

        $model->tz_systems_ids = explode(',', $model->tz_systems_ids);
        $model->tz_types = explode(',', $model->tz_types);
        $model->lottery_types = explode(',', $model->lottery_types);
        CommonService::delUserTzTypesCache($uid);

        $allSystems = CommonService::getAllSystems();
        $allTzTypes = CommonService::getAllTzTypes();
        $allLotteryTypes = CommonService::getAllLotteryTypes();

        return $this->render('open-systems', [
            'model' => $model,
            'uid' => $uid,
            'allSystems' => $allSystems,
            'allTzTypes' => $allTzTypes,
            'allLotteryTypes' => $allLotteryTypes,
        ]);
    }

    /**
     * @desc ssc
     * @param $uid
     * @return string|\yii\web\Response
     */
    public function actionOpenTennis($uid){

        if(!$model = TzSystemsAuth::findOne(['uid'=>$uid])){
            $model = new TzSystemsAuth();
        }
        UserService::preOpenData($this->_post, $uid);

        if ($model->load($this->_post) && $model->save()) {
            UserService::saveTzSystemUsers(explode(',', $this->_post['TzSystemsAuth']['tz_systems_ids']), $uid);
            return $this->redirect(['index']);
        }

        $model->tz_systems_ids = explode(',', $model->tz_systems_ids);
        $model->tz_types = explode(',', $model->tz_types);
        $model->lottery_types = explode(',', $model->lottery_types);
        CommonService::delUserTzTypesCache($uid);

        $allSystems = CommonService::getAllSystems();
        $allTzTypes = CommonService::getAllTzTypes();
        $allLotteryTypes = CommonService::getAllLotteryTypes();

        return $this->render('open-systems', [
            'model' => $model,
            'uid' => $uid,
            'allSystems' => $allSystems,
            'allTzTypes' => $allTzTypes,
            'allLotteryTypes' => $allLotteryTypes,
        ]);
    }

    /**
     * Displays a single User model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView()
    {
        $uid = \Yii::$app->user->id;
        if($uid == 1) {
            $searchModel = new TzSystemsUsersSearch();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

            return $this->render('view_admin', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);

        }else {
            BetService::synUserAllBalance($uid);

            $model = $this->findAllModel($uid);
            /*
            $user = AdminModel::findOne($uid);
            if($model->is_agent){
                $view = "agent_view";
            }else{
                $view = "view";
            }
            */
            $view = "view";
            return $this->render($view, [
                //'model' => $this->findModel($uid),
                'models' => $model
            ]);
        }
    }

    /**
     * @desc 手动登录
     * @return array|bool
     */
    public function actionLogin(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $id = $post['tz_sys_users_id'];
        $rst = BaseService::login($id);

        return $rst;
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new User();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findUserModel($id);
        $post = Yii::$app->request->post();

        if ($model->load($post) && $model->save()) {
            //UserService::saveTzSystemUsers(explode(',', $this->_post['TzSystemsAuth']['tz_systems_ids']), $uid);
            UserService::updateTzSystemUsers($post);
            return $this->redirect(['index', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing User model.
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
     * Updates an existing user model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionSetCookie(){
        $model = $this->findModel();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view']);
        }

        return $this->render('set-cookie', [
            'model' => $model,
        ]);
    }

    /**
     * @description 同步余额
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionSynBalance(){
        $model = $this->findModel();

        $rst = HN0898Service::synBalance($model->id);
        if($rst['status'] == 200){
            return $this->redirect(['view']);

        }
        return $this->redirect(['view']);
    }

    /**
     * @desc 同步单个系统余额
     * @return array
     */
    public function actionSyncOneBalance(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $tz_system_user_id = $post['tz_system_user_id'];

        $system_user_id = TzSystemsUsers::findOne($tz_system_user_id)->tz_system_id;

        //$rst = HN0898Service::synBalance($tz_system_user_id);
        $rst = BetService::synBalance($this->_user_id,$system_user_id);
        return $rst;
    }

    /**
     * @desc 更新用户状态
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchStatus($id,$status){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if(\Yii::$app->user->id == 1){
            UserService::updateUserStatus($id, $status);
        }

        return $this->redirect(['index']);
    }

    /**
     * @desc 修改投注系统状态，主要是禁止账号自动登录和获取余额
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchTzSystemStatus($id,$status){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if(\Yii::$app->user->id == 1){
            UserService::updateUserTzSystemStatus($id, $status);
        }

        return $this->redirect(['view']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id 新用户表
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findUserModel($id)
    {
        //$admin_id = \Yii::$app->user->id;
        if (($model = Admin::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel()
    {
        $admin_id = \Yii::$app->user->id;
        if (($model = AdminModel::findOne($admin_id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findAllModel()
    {
        $uid = \Yii::$app->user->id;
        $model = TzSystemsUsers::find()->where(['uid'=>$uid])->orderBy(['balance'=>SORT_DESC])->all();
        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
