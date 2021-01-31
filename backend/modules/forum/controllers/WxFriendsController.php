<?php

namespace backend\modules\forum\controllers;

use backend\service\Wx;
use backend\service\WxService;
use Yii;
use backend\models\WxFriends;
use backend\models\searchs\WxFriends as WxFriendsSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * WxFriendsController implements the CRUD actions for WxFriends model.
 */
class WxFriendsController extends BaseController
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
     * Lists all WxFriends models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new WxFriendsSearch();
        $queryParams = Yii::$app->request->queryParams;
        $queryParams['WxFriendsSearch']['uid'] = $this->_user_id;
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single WxFriends model.
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
     * Creates a new WxFriends model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new WxFriends();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing WxFriends model.
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
     * Deletes an existing WxFriends model.
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
     * Finds the WxFriends model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return WxFriends the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = WxFriends::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    /**
     * @desc 更新状态
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchStatus($id,$status){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        WxService::SwitchStatus($id, $status, $this->_user_id, 'WxFriends');

        return $this->redirect(['index']);
    }


    /**
     * @desc 登录微信
     */
    public function actionLogin(){

        //重新扫描登陆时，清空缓存
        $uid = $this->_user_id;
        /*
        session_start();
        unset($_SESSION);
        session_destroy();
        */
        $model = $this->findModel(['uid'=>$uid]);
        $uuid    = WxService::get_uuid();
        $erweima = WxService::qrcode($uuid);
        $model->login_img = $erweima;
        //echo ($erweima); //显示二维码
        //echo "<a href='/forum/wx-friends/sync-friends?uuid=" . $uuid . "'>扫描后，点击登陆确认</a>(备注：扫描后点击登陆按钮" . $uuid . ")";

        $data = [
            'model' => $model,
            'uuid' => $uuid,
            'erweima' => $erweima,
        ];

        return $this->render('login', $data);
    }

    /**
     * @desc 是否确认登陆状态
     * @return array
     */
    public function actionGetLoginStatus(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(!empty($post['uuid'])){
            $rst = WxService::isLogin($post['uuid']);
            if($rst['code']==200){
                $rst['data'] = WxService::webWxNewLoginPage($rst['redirect_uri']);
            }
        }else{
            $rst = ['status'=>300, 'msg'=>'uuid'];
        }

        return $rst;
    }

    /**
     * @desc 同步好友状态
     */
    public function actionSyncFriends(){
        $get = \Yii::$app->request->get();

        $list = WxService::syncFriendsData($this->_user_id, $get['uuid']);

        header('Location: /forum/wx-friends/index');
        //return $this->render('index', [ 'list'=>$list ]);
    }
}
