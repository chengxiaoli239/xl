<?php

namespace backend\modules\forum\controllers;

use backend\models\Admin;
use backend\models\TzSystems;
use backend\models\TzSystemsAuth;
use backend\models\searchs\TzSystemsUsers as TzSystemsUsersSearch;
use backend\models\TzSystemsUsers;
use backend\service\baota\BaoTaService;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\PoxyIPService;
use backend\service\UserService;
use common\models\AdminModel;
use common\service\CommonService;
use izyue\admin\models\searchs\Assignment as AssignmentSearch;
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
    public $userClassName;
    public $idField = 'id';
    public $usernameField = 'username';
    public $fullnameField;
    public $searchClass;
    public $extraColumns = [];
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

    public function init()
    {
        parent::init();
        if ($this->userClassName === null) {
            $this->userClassName = Yii::$app->getUser()->identityClass;
            $this->userClassName = $this->userClassName ? : 'common\models\AdminModel';
        }
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
        #p($this->_post['TzSystemsAuth']['tz_systems_ids']);

        if ($model->load($this->_post) && $model->save()) {
            UserService::saveTzSystemUsers(explode(',', $this->_post['TzSystemsAuth']['tz_systems_ids']), $uid);
            CommonService::delUserBetRecords($uid);
            $oneTzSystemId = explode(',', $this->_post['TzSystemsAuth']['tz_systems_ids'])[0];
            if(!empty($oneTzSystemId)){
                $TzSystems = TzSystems::findOne($oneTzSystemId);
                if(in_array($TzSystems->system_type_id, [15])){ # 3d类型站点
                    \common\service\thirdD\Odds3dService::addUserOdds($uid); # 3d 用户添加赔率
                }
            }
            return $this->redirect(['index']);
        }

        $model->tz_systems_ids = explode(',', $model->tz_systems_ids);
        $model->tz_types = explode(',', $model->tz_types);
        $model->lottery_types = explode(',', $model->lottery_types);
        CommonService::delUserTzTypesCache($uid);

        $allSystems = CommonService::getAllSystems();
        $allTzTypes = CommonService::getAllTzTypes();
        $systemTzTypes = CommonService::getSystemTzTypes();
        $allLotteryTypes = CommonService::getAllLotteryTypes();

        return $this->render('open-systems', [
            'model' => $model,
            'uid' => $uid,
            'allSystems' => $allSystems,
            'allTzTypes' => $allTzTypes,
            'systemTzTypes' => $systemTzTypes,
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
        $user = \Yii::$app->user->identity;
        $uid = $user->id;

        $is3dAdmin = UserService::is3dAdmin($user);
        if($uid == 1 OR $is3dAdmin) {
            $searchModel = new TzSystemsUsersSearch();
            $queryParams = Yii::$app->request->queryParams;

            $user_type = UserService::getUserType($user, $queryParams, 'TzSystemsUsers');
            $userTypes = UserService::getAdminUserTypes($user, $act=1);

            $queryParams['TzSystemsUsers']['user_type'] = $user_type;
            $dataProvider = $searchModel->search($queryParams);

            return $this->render('view_admin', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'userTypes' => $userTypes,
                'user_type' => $user_type,
            ]);

        }else {
            #BetService::synUserAllBalance($uid);

            $model = $this->findAllModel($uid);
            $view = "view";
            return $this->render($view, [
                'models' => $model
            ]);
        }
    }

    /**
     * @desc 自动下注
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchAutoBetStatus($id, $status) {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $uid = \Yii::$app->user->id;
        if($uid == 1){
            HN0898Service::updateStatus($id, '\backend\models\TzSystemsUsers', $field = 'is_auto_bet');
        }else{
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid]);
            if($TzSystemsUsers->uid != $uid){
                throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
            }
            HN0898Service::updateStatus($id, '\backend\models\TzSystemsUsers', $field = 'is_auto_bet');
        }

        return $this->redirect(['view']);
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

    public function actionActUpdatePasswordPage()
    {
        $model = new AdminModel();

        return $this->renderAjax('update_password', [
            'model' => $model,
        ]);

    }

    public function actionUpdatePassword(): array
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $model = AdminModel::findOne(\Yii::$app->user->id);
        $model->setScenario('resetPassword');

        $post = $this->_post;
        if ($model->load($post) && $model->save()) {
            //UserService::setUserLoginInfo(\Yii::$app->user->id);
            $rst = TzSystemsUsers::changePassword($post['AdminModel']['password'], $post['AdminModel']['re_password']);
            if($rst){
                UserService::clearUserLoginInfo(YII::$app->user->id);
            }
            $result = ['status'=>200, 'msg'=>'更新成功'];
        }else{
            $result = ['status'=>300, 'msg'=>current($model->getFirstErrors())];
        }

        return $result;
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
     * @desc 个人信息页单击 - 同步单个系统余额
     * @return array
     */
    public function actionSyncOneBalance(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $tz_system_user_id = $post['tz_system_user_id'];
        $is_auto = $post['is_auto'];

        $system_user_id = TzSystemsUsers::findOne($tz_system_user_id)->tz_system_id;

        //$rst = HN0898Service::synBalance($tz_system_user_id);
        $rst = BetService::synBalance($this->_user_id,$system_user_id, $is_auto);
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
     * @desc 更新用户状态
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchAutoLogin($id){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if(\Yii::$app->user->id != 1){
            $is3dUser = UserService::is3dUser($this->_user_id);
            $model = TzSystemsUsers::find()->where(['uid'=>$this->_user_id, 'id'=>$id])->one();
            if(empty($model)){
                return $this->redirect(['/wechat/robot-user/view']);
            }
        }
        $rst = HN0898Service::updateStatus($id, $model = '\backend\models\TzSystemsUsers', 'is_auto_login');
        if($is3dUser){
            return $this->redirect(['/wechat/robot-user/view']);
        }

        return $this->redirect(['view']);
    }

    /**
     * @desc 更新用户状态
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchProxy($id){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if(\Yii::$app->user->id == 1){
            $rst = HN0898Service::updateStatus($id, $model = '\backend\models\TzSystemsUsers', 'is_use_proxy');
            PoxyIPService::delProxyUidsKey();
        }

        return $this->redirect(['view']);
    }

    /**
     * @desc 更新用户是否本地下注状态
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchIsLocalBet($id){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if(\Yii::$app->user->id == 1){
            $rst = HN0898Service::updateStatus($id, $model = '\backend\models\TzSystemsUsers', 'is_local_bet');
            PoxyIPService::delIsLocalBetKey();
        }

        return $this->redirect(['view']);
    }

    /**
     * @desc 修改投注系统状态，主要是禁止账号自动登录和获取余额
     * @return \yii\web\Response
     */
    public function actionSetProfits(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $uid = \Yii::$app->user->id;
        UserService::setProfits($uid, $post);

        return $this->redirect(['view']);
    }

    /**
     * @desc 更新记录follow_status 0/1
     * @param $id
     * @return \yii\web\Response
     */
    public function actionSwitchFieldStatus($id, $field='follow_status'){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        HN0898Service::updateStatus($id, $model = '\backend\models\TzSystemsUsers', $field);

        return $this->redirect(['view']);
    }

    /**
     * @desc 修改投注系统状态，主要是禁止账号自动登录和获取余额
     * @return \yii\web\Response
     */
    public function actionSetFollowBuy(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $uid = \Yii::$app->user->id;
        UserService::setFollowBuy($uid, $post);

        return $this->redirect(['view']);
    }

    public function actionResetToken(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $user_id = $post['user_id'];
        $uid = \Yii::$app->user->id;
        if($uid != 1){
            return ['status'=>300, 'data'=>[], 'message'=>'操作失败'];
        }
        try {
            $new_access_token = UserService::resetToken($user_id);
        }catch (\Exception $e){
            return ['status'=>301, 'data'=>[], 'message'=>$e->getMessage()];
        }

        return ['status'=>200, 'data'=>['access_token'=>$new_access_token]];
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

    public function actionMyChild(): string
    {
        $user = \Yii::$app->user->identity;
        $queryParams = Yii::$app->getRequest()->getQueryParams();

        //p(['queryParams'=>$queryParams, 'user'=>$user, 'roles'=>$roles, 'permissions'=>$permissions]);
        $user_type = UserService::getUserType($user, $queryParams);

        $searchModel = new AssignmentSearch;
        $dataProvider = $searchModel->search($queryParams, $this->userClassName, $this->usernameField);
        $dataProvider->query->where(['user_type'=>$user_type]);
        $dataProvider->query->andWhere(['parent_id'=>\Yii::$app->user->id]); # 取自己下级

        $userTypes = UserService::getAdminUserTypes($user);
        return $this->render('index_my_child', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'idField' => $this->idField,
            'userTypes' => $userTypes,
            'user_type' => $user_type,
            'usernameField' => $this->usernameField,
            'extraColumns' => $this->extraColumns,
        ]);
    }

    /**
     * Creates a new Menu model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreateMyChild()
    {
        $model = new $this->userClassName;
        $model->setScenario('create');
        $YiiUser = \Yii::$app->user->identity;
        //p([$this->userClassName, UserService::is3dAdmin(\Yii::$app->user->identity), \Yii::$app->request->post(), $className]);
        if ($model->load(Yii::$app->request->post())) {
            try {
                $flag = false;
                $transaction = \Yii::$app->db->beginTransaction();
                $model->user_type = AdminModel::USER_TYPE_3D_CHILD;
                $model->parent_id = \Yii::$app->user->id;

                if ($user = $model->signup()) {
                    # 创建账号之后触发
                    CommonService::opUser($user->id, 'add', UserService::getCreateDefaultRole($YiiUser));
                    $flag = true;
                }
                if(!$flag){
                    throw_info('处理异常');
                }
                $transaction->commit();
            }catch (\Exception $e){
                $transaction->rollBack();
            }
            return $this->redirect(['my-child']);
        }

        return $this->render('create_my_child', [
            'model' => $model,
        ]);

    }

    /**
     * Updates an existing Menu model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param  integer $id
     * @return mixed
     */
    public function actionUpdateMyChild($id)
    {
        $post = Yii::$app->request->post();
        $model = $this->findModel($id);
        $model->setScenario('update');
        if($model->load($post)){
            if($model->password){
                $pwd = $model->password;
                $model->desc = '账号：'.$model->username.' 密码：'.$pwd;
                $model->setPassword($pwd);
                $model->generateAuthKey();

                $now_time = time();
                $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$id]);
                if(empty($TzSystemsUsers)){
                    $TzSystemsUsers = new TzSystemsUsers();
                    $TzSystemsUsers->uid = $id;
                    $TzSystemsUsers->created_at = $now_time;
                    $access_token = md5($id.'_'.$pwd);
                    $TzSystemsUsers->access_token = $access_token;
                }
                $TzSystemsUsers->username = $model->username;
                $TzSystemsUsers->updated_at = $now_time;
                if(!$TzSystemsUsers->save()){
                    p($TzSystemsUsers->getErrors());
                }
            }

            if ($model->save()) {
                //MenuHelper::invalidate();
                $user = \Yii::$app->user->identity;
                $rst = UserService::opUser($id, 'add', UserService::getCreateDefaultRole($user));
                return $this->redirect(['index']);
            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }else{
            return $this->render('update', [
                'model' => $model,
            ]);
        }


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
    protected function findModel($id)
    {
        $admin_id = \Yii::$app->user->id;
        $where = ['id'=>$admin_id];
        if($admin_id != 1){
            $where = ['parent_id'=>$admin_id, 'id'=>$id];
        }
        if (($model = AdminModel::find()->where($where)->one()) !== null) {
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
