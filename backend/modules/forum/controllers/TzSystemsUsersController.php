<?php

namespace backend\modules\forum\controllers;

use backend\service\BaseService;
use backend\service\UserService;
use common\tools\Tool_Common;
use Yii;
use backend\models\TzSystemsUsers;
use backend\models\searchs\TzSystemsUsers as TzSystemsUsersSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * TzSystemsUsersController implements the CRUD actions for TzSystemsUsers model.
 */
class TzSystemsUsersController extends BaseController
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
                    'set-ssl-mode' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all TzSystemsUsers models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new TzSystemsUsersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @return array|bool
     */
    public function actionLogin(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $id = $post['id'];
        $is_auto = $post['is_auto'];
        $rst = BaseService::login($id, $is_auto);
        Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '手工登录', ['id'=>$id, 'is_auto'=>$is_auto, 'rst'=>$rst]);
        if(empty($rst['username'])){
            $TzSystemsUsers = TzSystemsUsers::findOne($id);
            $rst['username'] = $TzSystemsUsers->username;
            $rst['account'] = $TzSystemsUsers->account;
            $rst['balance'] = $TzSystemsUsers->balance;
        }

        return $rst;
    }

    public function actionSetSslMode()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $admin = \Yii::$app->user->identity;
        if(!$admin || (int)$admin->status !== 10){
            return ['status'=>403, 'msg'=>'无权限'];
        }

        $id = (int)\Yii::$app->request->post('id');
        $sslMode = (int)\Yii::$app->request->post('ssl_mode');
        if(!isset(TzSystemsUsers::SSL_MODE_OPTIONS[$sslMode])){
            return ['status'=>400, 'msg'=>'TLS模式无效'];
        }

        $model = TzSystemsUsers::findOne($id);
        if(!$model){
            return ['status'=>404, 'msg'=>'盘口账号不存在'];
        }

        $model->ssl_mode = $sslMode;
        $model->updated_at = time();
        if(!$model->save(false, ['ssl_mode', 'updated_at'])){
            return ['status'=>500, 'msg'=>'TLS模式保存失败'];
        }

        return [
            'status'=>200,
            'msg'=>'TLS模式已更新',
            'ssl_mode'=>$sslMode,
            'ssl_mode_label'=>TzSystemsUsers::SSL_MODE_OPTIONS[$sslMode],
        ];
    }

    /**
     * Displays a single TzSystemsUsers model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id, $this->_user_id),
        ]);
    }

    /**
     * Creates a new TzSystemsUsers model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new TzSystemsUsers();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * @desc 更新到期时间
     * @return mixed
     */
    public function actionUpExpireTime(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $expire_time = $post['time_val'];
        $id = $post['id'];

        $rst = UserService::upExpireTime($id, $expire_time);

        return $rst;
    }

    /**
     * Updates an existing TzSystemsUsers model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id, $this->_user_id);
        $post = Yii::$app->request->post();
        //p($post);
        if($post){
            if(empty($model->user_agent)){
                $post['TzSystemsUsers']['user_agent'] = 'User-Agent: '.$_SERVER['HTTP_USER_AGENT'];
            }
            $cookie = '';
            if($model->account == $post['TzSystemsUsers']['account']){
                 $cookie = trim($post['TzSystemsUsers']['cookie']);
            }

            $session_id = Yii::$app->getSession()->id;
            $ip = Yii::$app->getRequest()->getRemoteIP();
            $ss = Yii::$app->getSession();
            $log = [
                'session_id'=>$session_id,
                'ss'=>$ss,
                'ip'=>$ip,
            ];
            //p($log);

            # 修改网页登陆密码 - 开始
            if(!empty($post['TzSystemsUsers']['sys_password']) OR !empty($post['TzSystemsUsers']['sys_repassword'])){
                if($model->load($post)){
                    $rst = TzSystemsUsers::changePassword($post['TzSystemsUsers']['sys_password'], $post['TzSystemsUsers']['sys_repassword']);
                    if($rst){
                        UserService::clearUserLoginInfo(YII::$app->user->id);
                        Yii::$app->user->logout();
                        return $this->goHome();
                    }
                }else{
                    return $this->render('update', [
                        'model' => $model,
                    ]);
                }
            }
            # 修改网页登陆密码 - 结束

            $post['TzSystemsUsers']['ssc_domain'] = trim($post['TzSystemsUsers']['ssc_domain'], '/');
            $post['TzSystemsUsers']['cookie'] = $cookie;
            $post['TzSystemsUsers']['desc'] = '';
        }

        if ($model->load($post) && $model->save()) {
            $is3dUser = UserService::is3dUser($this->_user_id);
            if($is3dUser){
                return $this->redirect(['/wechat/robot-user/view']);
            }
            return $this->redirect(['/forum/user/view.html']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing TzSystemsUsers model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id, $this->_user_id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the TzSystemsUsers model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return TzSystemsUsers the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id, $uid='')
    {
        $where = ['id'=>$id];
        if(!empty($uid)) $where['uid'] = $uid;
        if (($model = TzSystemsUsers::findOne($where)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
