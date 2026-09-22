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
    public function beforeAction($action)
    {
        if($action->id === 'set-proxy-type'){
            Yii::$app->request->enableCsrfValidation = true;
            foreach(Yii::$app->log->targets as $target){ $target->logVars = []; }
        }
        return parent::beforeAction($action);
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
                    'delete' => ['POST'],
                    'set-ssl-mode' => ['POST'],
                    'set-proxy-type' => ['POST'],
                    'update-account' => ['POST'],
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

    public function actionSetProxyType()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if(Yii::$app->user->isGuest || (int)Yii::$app->user->id !== 1
            || !Yii::$app->user->identity || (int)Yii::$app->user->identity->status !== 10){
            return ['status'=>403, 'msg'=>'仅管理员可切换代理商'];
        }
        $id = filter_var(Yii::$app->request->post('id'), FILTER_VALIDATE_INT);
        $type = filter_var(Yii::$app->request->post('proxy_type'), FILTER_VALIDATE_INT);
        if(!$id || $type === false || !isset(TzSystemsUsers::PROXY_TYPE_OPTIONS[$type])){
            return ['status'=>400, 'msg'=>'账号或代理商无效'];
        }
        $model = TzSystemsUsers::findOne($id);
        if(!$model){
            return ['status'=>404, 'msg'=>'盘口账号不存在'];
        }
        $fields = ['proxy_type', 'updated_at'];
        $enabled = Yii::$app->request->post('is_use_proxy');
        if($enabled !== null){
            if(!in_array($enabled, [0, 1, '0', '1'], true)){
                return ['status'=>400, 'msg'=>'代理开关无效'];
            }
            $model->is_use_proxy = (int)$enabled;
            $fields[] = 'is_use_proxy';
        }
        if($type === \common\service\proxy\ProxyMihomoService::TYPE && (int)$model->is_use_proxy === 1){
            $site = \backend\models\TzSystems::findOne($model->tz_system_id);
            if((int)$model->is_local_bet !== \backend\models\thirdD\BetsBackend::BET_TYPE_SERVER_API
                || !in_array((int)$model->tz_system_id, [9, 10], true)
                || !$site || !in_array((int)$site->lottery_type, [0, 8], true)){
                return ['status'=>400, 'msg'=>'Mihomo 当前支持幸运五星彩的云服务器账号'];
            }
            if(!$model->hasAttribute('proxy_node_port')){
                return ['status'=>400, 'msg'=>'请先完成节点字段迁移'];
            }
            $port = filter_var(Yii::$app->request->post('proxy_node_port'), FILTER_VALIDATE_INT);
            if($port === false || \common\service\proxy\ProxyMihomoService::address($port) === \common\service\proxy\ProxyMihomoService::ADDRESS){
                return ['status'=>400, 'msg'=>'请选择此账号使用的节点'];
            }
            try{
                $result = \common\service\proxy\ProxyMihomoService::manager('list');
                $ready = false;
                foreach($result['nodes'] ?? [] as $node){
                    if((int)$node['port'] === $port && !empty($node['running'])){
                        $ready = true;
                    }
                }
                if(!$ready || !\common\service\proxy\ProxyMihomoService::isListening($port)){
                    return ['status'=>400, 'msg'=>'请先在节点管理页启动并测试该节点'];
                }
            }catch(\RuntimeException $e){
                return ['status'=>400, 'msg'=>$e->getMessage()];
            }
            $model->proxy_node_port = $port;
            $fields[] = 'proxy_node_port';
        }
        foreach(['is_proxy_login', 'is_proxy_bet'] as $field){
            $value = Yii::$app->request->post($field);
            if($value !== null){
                if(!in_array($value, [0, 1, '0', '1'], true)){
                    return ['status'=>400, 'msg'=>'场景开关无效'];
                }
                $model->$field = (int)$value;
                $fields[] = $field;
            }
        }
        // Update only routing choice; preserve all account switches, sessions and balances.
        $model->proxy_type = $type;
        $model->updated_at = time();
        if(!$model->save(false, $fields)){
            return ['status'=>500, 'msg'=>'代理商保存失败'];
        }
        Yii::$app->cache->delete('getProxyTypeByUid_'.$model->uid);
        \backend\service\clients\TzSystemUsersService::delTzSystemUserData();
        \backend\service\PoxyIPService::delProxyUidsKey();
        return ['status'=>200, 'msg'=>'代理商已更新，原代理开关保持不变', 'proxy_type'=>$type];
    }

    public function actionSetSslMode()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $admin = \Yii::$app->user->identity;
        if(!$admin || (int)$admin->status !== 10){
            Tool_Common::log('/user/'.__FUNCTION__, 'ERR', '无权限', ['user_id'=>\Yii::$app->user->id, 'identity'=>$admin ? $admin->attributes : null]);
            return ['status'=>403, 'msg'=>'无权限'];
        }

        $id = (int)\Yii::$app->request->post('id');
        $sslMode = (int)\Yii::$app->request->post('ssl_mode');
        if(!isset(TzSystemsUsers::SSL_MODE_OPTIONS[$sslMode])){
            return ['status'=>400, 'msg'=>'TLS模式无效'];
        }

        $model = TzSystemsUsers::findOne($id);
        if(!$model){
            Tool_Common::log('/user/'.__FUNCTION__, 'ERR', '盘口账号不存在', ['id'=>$id]);
            return ['status'=>404, 'msg'=>'盘口账号不存在'];
        }

        $model->ssl_mode = $sslMode;
        $model->updated_at = time();
        if(!$model->save(false, ['ssl_mode', 'updated_at'])){
            Tool_Common::log('/user/'.__FUNCTION__, 'ERR', '保存失败', ['id'=>$id, 'errors'=>$model->getErrors()]);
            return ['status'=>500, 'msg'=>'TLS模式保存失败'];
        }

        Tool_Common::log('/user/'.__FUNCTION__, 'INFO', 'TLS模式保存成功', ['id'=>$id, 'ssl_mode'=>$sslMode]);

        return [
            'status'=>200,
            'msg'=>'TLS模式已更新',
            'ssl_mode'=>$sslMode,
            'ssl_mode_label'=>TzSystemsUsers::SSL_MODE_OPTIONS[$sslMode],
        ];
    }

    /**
     * @desc 编辑盘口账号信息
     */
    public function actionUpdateAccount()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $id = (int)\Yii::$app->request->post('id');
        $model = TzSystemsUsers::findOne($id);
        if(!$model){
            return ['status'=>404, 'msg'=>'盘口账号不存在'];
        }
        $model->account = \Yii::$app->request->post('account', $model->account);
        $model->password = \Yii::$app->request->post('password', $model->password);
        $model->ssc_domain = \Yii::$app->request->post('ssc_domain', $model->ssc_domain);
        $model->desc = '';
        $model->updated_at = time();
        if(!$model->save(false)){
            return ['status'=>500, 'msg'=>'保存失败'];
        }
        // 清空该账号的RSA公钥缓存
        \Yii::$app->cache->delete('rsa_public_key_'.$model->tz_system_id);
        return ['status'=>200, 'msg'=>'保存成功'];
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
