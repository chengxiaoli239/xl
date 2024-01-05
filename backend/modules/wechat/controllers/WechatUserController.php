<?php

namespace backend\modules\wechat\controllers;

use backend\service\HN0898Service;
use common\service\wechat\WechatUserService;
use common\tools\Tool_Common;
use Yii;
use backend\models\wechat\WechatUser;
use backend\models\searchs\wechat\WechatUser as WechatUserSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * WechatUserController implements the CRUD actions for WechatUser model.
 */
class WechatUserController extends BaseController
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
     * Lists all WechatUser models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new WechatUserSearch();
        $queryParams = Yii::$app->request->queryParams;
        $queryParams['WechatUser']['user_id'] = $this->_user_id;
        $queryParams['WechatUser']['robot_wechat'] = WechatUserService::getCurrentRobotWechat($this->_user_id, $queryParams['WechatUser']['robot_wechat']);
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single WechatUser model.
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
     * @desc 更新是否接收用户消息状态
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchStatus($id, $field='', $val=0){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        #p([$id, $field, $val]);
        $row = $this->findModel(['id'=>$id, 'user_id'=>$this->_user_id]);
        if(!empty($row)){
            HN0898Service::updateStatus($id, $model = '\backend\models\wechat\WechatUser', $field, $val);
            WechatUserService::getWechatUsers($this->_user_id, false);
        }

        return $this->redirect(['index']);
    }

    /**
     * @desc 批量更新状态
     * @param $id
     * @param $status
     * @return array
     */
    public function actionBatchSwitchStatus(): array
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        try {
            HN0898Service::batchSwitchStatus($post['ids'], $model = '\backend\models\wechat\WechatUser', $post['field'], $post['val'], $this->_user_id);
            WechatUserService::getWechatUsers($this->_user_id, false);
        }catch (\Exception $e){
            return ['status'=>300, 'msg'=>$e->getMessage()];
        }

        return ['status'=>200, 'msg'=>'操作成功'];
    }

    /**
     * Creates a new WechatUser model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new WechatUser();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing WechatUser model.
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
     * 同步微信好友
     * @return array
     */
    public function actionSyncFriends(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $mkey = __FUNCTION__.'_'.$this->_user_id;
        $num = \Yii::$app->redis->incr($mkey);
        if($num>1){
            return ['status'=>300, 'msg'=>'请求太频繁请稍后重试'];
        }
        \Yii::$app->redis->expire($mkey, 120);

        $rst = WechatUserService::syncWechatFriends($this->_user_id);
        Tool_Common::log('/wechat/'.__FUNCTION__, 'INFO', '同步微信好友', ['post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * Deletes an existing WechatUser model.
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
     * Finds the WechatUser model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return WechatUser the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($params)
    {
        if (($model = WechatUser::findOne($params)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
