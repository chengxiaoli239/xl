<?php

namespace izyue\admin\controllers;

use backend\models\SignupForm;
use backend\models\TzSystemsUsers;
use backend\service\UserService;
use backend\service\UserSysPlansService;
use common\models\AdminModel;
use common\tools\Util;
use izyue\admin\components\MenuHelper;
use Yii;
use izyue\admin\models\searchs\Assignment as AssignmentSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use izyue\admin\components\Helper;
use common\service\CommonService;

/**
 * AssignmentController implements the CRUD actions for Assignment model.
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class AssignmentController extends Controller
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
    public function init()
    {
        parent::init();
        if ($this->userClassName === null) {
            $this->userClassName = Yii::$app->getUser()->identityClass;
            $this->userClassName = $this->userClassName ? : 'common\models\User';
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'assign' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Assignment models.
     * @return mixed
     */
    public function actionIndex()
    {
        $user = \Yii::$app->user->identity;
        $roles = Yii::$app->authManager->getRolesByUser($user->id);
        $permissions = Yii::$app->authManager->getPermissionsByUser($user->id);
        $queryParams = Yii::$app->getRequest()->getQueryParams();

        //p(['queryParams'=>$queryParams, 'user'=>$user, 'roles'=>$roles, 'permissions'=>$permissions]);
        $user_type = UserService::getUserType($user, $queryParams);
        if ($this->searchClass === null) {
            $searchModel = new AssignmentSearch;
            $dataProvider = $searchModel->search($queryParams, $this->userClassName, $this->usernameField);
            $dataProvider->query->where(['user_type'=>$user_type]);
            if(\Yii::$app->user->id != 1){
                $dataProvider->query->andWhere(['parent_id'=>\Yii::$app->user->id]); # 取自己下级
            }
        } else {
            $class = $this->searchClass;
            $searchModel = new $class;
            $dataProvider = $searchModel->search($queryParams);
        }
        
        $userTypes = UserService::getAdminUserTypes($user);
        return $this->render('index', [
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
     * Displays a single Assignment model.
     * @param  integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
                'model' => $model,
                'idField' => $this->idField,
                'usernameField' => $this->usernameField,
                'fullnameField' => $this->fullnameField,
                'items' => $this->getItems($id)
        ]);
    }

    /**
     * Assign or revoke assignment to user
     * @param  integer $id
     * @param  string  $action
     * @return mixed
     */
    public function actionAssign($id)
    {
        $post = Yii::$app->getRequest()->post();
        $action = $post['action'];
        $roles = $post['roles'];
        $manager = Yii::$app->getAuthManager();
        $error = [];
        if ($action == 'assign') {
            foreach ($roles as $name) {
                try {
                    $item = $manager->getRole($name);
                    $item = $item ? : $manager->getPermission($name);
                    $manager->assign($item, $id);
                } catch (\Exception $exc) {
                    $error[] = $exc->getMessage();
                }
            }
        } else {
            foreach ($roles as $name) {
                try {
                    $item = $manager->getRole($name);
                    $item = $item ? : $manager->getPermission($name);
                    $manager->revoke($item, $id);
                } catch (\Exception $exc) {
                    $error[] = $exc->getMessage();
                }
            }
        }

        # 添加/删除代理赔率记录
        if(in_array('收费会员',$roles) OR in_array('member',$roles) OR in_array('3D代理', $roles)){
            $user = \Yii::$app->user->identity;
            CommonService::opUser($id, $action, UserService::getCreateDefaultRole($user));
        }
        Helper::invalidate();
        Yii::$app->response->format = 'json';
        return array_merge($this->getItems($id), ['errors' => $error]);
    }

    /**
     *
     * @param string $id
     * @return array
     */
    protected function getItems($id)
    {
        $manager = Yii::$app->getAuthManager();
        $avaliable = [];
        foreach (array_keys($manager->getRoles()) as $name) {
            $avaliable[$name] = 'role';
        }

        foreach (array_keys($manager->getPermissions()) as $name) {
            if ($name[0] != '/') {
                $avaliable[$name] = 'permission';
            }
        }

        $assigned = [];
        foreach ($manager->getAssignments($id) as $item) {
            $assigned[$item->roleName] = $avaliable[$item->roleName];
            unset($avaliable[$item->roleName]);
        }
        
        return[
            'avaliable' => $avaliable,
            'assigned' => $assigned
        ];
    }

    /**
     * Search roles of user
     * @param  integer $id
     * @param  string  $target
     * @param  string  $term
     * @return string
     */
    public function actionSearch($id, $target, $term = '')
    {
        Yii::$app->response->format = 'json';
        $authManager = Yii::$app->authManager;
        $roles = $authManager->getRoles();
        $permissions = $authManager->getPermissions();

        $avaliable = [];
        $assigned = [];
        foreach ($authManager->getAssignments($id) as $assigment) {
            if (isset($roles[$assigment->roleName])) {
                if (empty($term) || strpos($assigment->roleName, $term) !== false) {
                    $assigned['Roles'][$assigment->roleName] = $assigment->roleName;
                }
                unset($roles[$assigment->roleName]);
            } elseif (isset($permissions[$assigment->roleName]) && $assigment->roleName[0] != '/') {
                if (empty($term) || strpos($assigment->roleName, $term) !== false) {
                    $assigned['Permissions'][$assigment->roleName] = $assigment->roleName;
                }
                unset($permissions[$assigment->roleName]);
            }
        }

        if ($target == 'avaliable') {
            if (count($roles)) {
                foreach ($roles as $role) {
                    if (empty($term) || strpos($role->name, $term) !== false) {
                        $avaliable['Roles'][$role->name] = $role->name;
                    }
                }
            }
            if (count($permissions)) {
                foreach ($permissions as $role) {
                    if ($role->name[0] != '/' && (empty($term) || strpos($role->name, $term) !== false)) {
                        $avaliable['Permissions'][$role->name] = $role->name;
                    }
                }
            }
            return $avaliable;
        } else {
            return $assigned;
        }
    }

    /**
     * Creates a new Menu model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new $this->userClassName;
        $model->setScenario('create');
        $YiiUser = \Yii::$app->user->identity;
        $className = Util::class_basename($this->userClassName);
        //p([$this->userClassName, UserService::is3dAdmin(\Yii::$app->user->identity), \Yii::$app->request->post(), $className]);
        if ($model->load(Yii::$app->request->post())) {
            try {
                $flag = false;
                $transaction = \Yii::$app->db->beginTransaction();
                $model->user_type = UserService::is3dAdmin(\Yii::$app->user->identity) ? AdminModel::USER_TYPE_3D :  AdminModel::USER_TYPE_QX;
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
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);

    }

    /**
     * Updates an existing Menu model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param  integer $id
     * @return mixed
     */
    public function actionUpdate($id)
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
     * Deletes an existing Menu model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param  integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        MenuHelper::invalidate();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Assignment model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param  integer $id
     * @return \yii\db\ActiveRecord|\yii\web\IdentityInterface the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $class = $this->userClassName;
        if (($model = $class::findIdentity($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
