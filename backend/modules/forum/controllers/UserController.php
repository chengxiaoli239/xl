<?php

namespace backend\modules\forum\controllers;

use backend\models\Admin;
use backend\models\TzSystems;
use backend\models\TzSystemsAuth;
use backend\models\searchs\TzSystemsUsers as TzSystemsUsersSearch;
use backend\models\TzSystemsUsers;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\clients\TzSystemUsersService;
use backend\service\PoxyIPService;
use backend\service\UserService;
use backend\service\UserSysPlansService;
use common\models\AdminModel;
use common\service\CommonService;
use common\tools\Util;
use izyue\admin\models\searchs\Assignment as AssignmentSearch;
use Yii;
use backend\models\User;
use backend\models\searchs\Admin as UserSearch;
use backend\controllers\BaseController;
use yii\helpers\Json;
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
        $queryParams = Yii::$app->request->queryParams;

        $user = \Yii::$app->user->identity;
        $user_type = UserService::getUserType($user, $queryParams, 'Admin');
        $userTypes = UserService::getAdminUserTypes($user, $act=1);
        //p([$userTypes, $user_type]);

        $queryParams['Admin']['user_type'] = $user_type;
        $dataProvider = $searchModel->search($queryParams);
        //$dataProvider['query'] = $queryParams['query']->leftjoin(TzSystemsUsers::tableName().' as t', 't.uid=u.id')->select(['u.*', 't.status as t_status']);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'userTypes' => $userTypes,
            'user_type' => $user_type,
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
                if($TzSystems->system_type_id>=15){
                    \common\service\thirdD\Odds3dService::addUserOdds($uid, $TzSystems->system_type_id); # 3d 用户添加赔率
                }
            }
            UserSysPlansService::getMyLotteryTypes($uid, $useCache=0);
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

    public function actionCreateUser($id='')
    {
        $id = $id?:($this->_post['AdminModel']['id']?:'');
        if(empty($id) OR !$model = $this->findModel($id)){
            $model = new $this->userClassName;
            $model->setScenario('create');
        }else{
            $model->setScenario('update');
        }
        $YiiUser = \Yii::$app->user->identity;
        $className = Util::class_basename($this->userClassName);
        //p(['identity'=>\Yii::$app->user->identity, $this->userClassName, UserService::is3dAdmin(\Yii::$app->user->identity), \Yii::$app->request->post(), $className, 'user'=>$YiiUser]);
        if(\Yii::$app->request->isPost){
            $this->_post['AdminModel']['status'] = $this->_post['AdminModel']['status']??AdminModel::STATUS_ACTIVE;
        }
        $userTypes = UserService::getAdminUserTypes(\Yii::$app->user->identity, $act=1);
        list($nextUserType, $nextRole) = UserService::getCreateDefaultRole($YiiUser, current($userTypes)['user_type']);
        $currentUserType = $YiiUser['user_type'];
        $nowTime = time();
        if ($model->load($this->_post)) {
            try {
                $transaction = \Yii::$app->db->beginTransaction();
                if($this->_user_id != 1){
                    $model->parent_id = \Yii::$app->user->id;
                    $model->user_type = $nextUserType;
                }

                if ($model->signup()) {
                    if($this->_post['AdminModel']['password']){
                        TzSystemsUsers::changePassword($this->_post['AdminModel']['password'], $this->_post['AdminModel']['password'], $model->id);
                    }
                    //p(['role'=>UserService::getCreateDefaultRole($YiiUser), 'YiiUser'=>$YiiUser, 'post'=>$this->_post, 'model'=>$model]);
                    # 创建账号之后触发
                    CommonService::opUser($model->id, 'add', $nextRole);
                    $TzSystemUsers = TzSystemsUsers::findOne(['uid'=>$model->id]);
                    $setData = [];
                    if(empty($TzSystemUsers)){
                        $TzSystemUsers = new TzSystemsUsers();
                        $setData = [
                            'uid' => $model->id,
                            'created_at' => $nowTime,
                            'expire_time' => $nowTime + 3600,
                        ];
                    }
                    $postData = current($this->_post);
                    $TzSystems = TzSystems::findOne($postData['tz_system_id']);
                    $setData = array_merge($setData, [
                        'username' => $postData['username'],
                        'user_type' => $nextUserType,
                        'tz_system_id' => $postData['tz_system_id'],
                        'kj_num' => $postData['kj_num'],
                        'is_auto_login' => $nextUserType==AdminModel::USER_TYPE_GUI?1:0,
                        'sys_name' => $TzSystems->name,
                        'balance' => 0.00,
                        'current_profits' => 0.00,
                        'ssc_domain' => $TzSystems->ssc_domain??'',
                        'account' => $postData['site_account']?:'',
                        'password' => $postData['site_password']?:'',
                        'secure_code' => $postData['secure_code']?:'',
                        'desc' => $postData['description']?:'',
                        'user_agent' => 'User-Agent: '.$_SERVER['HTTP_USER_AGENT'],
                        'updated_at' => $nowTime,
                    ]);
                    $TzSystemUsers->setAttributes($setData);
                    if(!$TzSystemUsers->save()){
                        throw_info(Json::encode($TzSystemUsers->getErrors()));
                    }
                    $TzSystemsAuth = TzSystemsAuth::findOne(['uid'=>$model->id]);
                    if($TzSystemsAuth){
                        $authSystemIds = array_values(array_filter(array_map('trim', explode(',', (string)$TzSystemsAuth->tz_systems_ids)), 'strlen'));
                        $postSystemId = (string)$postData['tz_system_id'];
                        if($postSystemId !== '' && !in_array($postSystemId, $authSystemIds, true)){
                            $authSystemIds[] = $postSystemId;
                            $TzSystemsAuth->tz_systems_ids = implode(',', $authSystemIds);
                            $TzSystemsAuth->updated_at = $nowTime;
                            $TzSystemsAuth->save(false, ['tz_systems_ids', 'updated_at']);
                        }
                    }
                    $new_access_token = UserService::resetToken($TzSystemUsers->uid);
                    \common\service\thirdD\Odds3dService::addUserOdds($model->id, $TzSystems->system_type_id); # 3d 用户添加赔率
                }else{
                    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    $errMsg = $model->getErrors()?current(current($model->getErrors())):$model->getErrors();
                    //throw_info($errMsg?:'处理异常'); // $model->getErrors()
                    return ['status'=>301, 'msg'=>$errMsg];
                }
                $transaction->commit();
            }catch (\Exception $e){
                $transaction->rollBack();
                die('<script>alert("'.$e->getMessage().'"); history.back();</script>');
            }
            //p('kkdk');
            return $this->redirect(['/admin/assignment/index.html']);
        }else{
            $get = \Yii::$app->request->get();
            if(!empty($get['id'])){
                #$model = $this->findModel($get['id']);
                $model = AdminModel::find()->alias('u')
                    ->leftJoin(TzSystemsUsers::tableName().' t', 'u.id=t.uid')
                    ->select(['u.*', 't.secure_code', 't.tz_system_id', 't.kj_num', 't.ssc_domain', 't.account as site_account', 't.password as site_password', 't.desc as description'])
                    ->where(['=', 'u.id', $get['id']])
                    ->limit(1)->one();
            }
        }

        $sites = TzSystemUsersService::getSites($currentUserType);
        return $this->renderAjax('create_user', [
            'model' => $model,
            'sites' => $sites
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
    public function actionSwitchStatus($id, $status, $field='status'){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if(\Yii::$app->user->id == 1){
            UserService::updateUserStatus($id, $status, $field);
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
     * @desc 更新用户代理场景开关
     * @param $id
     * @param string $field
     * @return \yii\web\Response
     */
    public function actionSwitchProxyScene($id, $field = 'is_proxy_login'){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $allowFields = ['is_proxy_login', 'is_proxy_bet'];
        if(\Yii::$app->user->id == 1 && in_array($field, $allowFields, true)){
            HN0898Service::updateStatus($id, $model = '\backend\models\TzSystemsUsers', $field);
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
    public function actionSwitchIsLocalBet($id, $status = 1){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if(\Yii::$app->user->id == 1){
            $rst = HN0898Service::updateStatus($id, $model = '\backend\models\TzSystemsUsers', 'is_local_bet', $status);
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
        TzSystemUsersService::getAuthAccessTokens($isAuto=2);

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
                    list($nextUserType, $nextRole) = UserService::getCreateDefaultRole($YiiUser);
                    CommonService::opUser($user->id, 'add', $nextRole);
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
                list($nextUserType, $nextRole) = UserService::getCreateDefaultRole($user);
                $rst = UserService::opUser($id, 'add', $nextRole);
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
        $where = ['id'=>$id];
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
