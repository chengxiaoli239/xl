<?php
namespace backend\modules\wechat\controllers;

use backend\models\searchs\TzSystemsUsers as TzSystemsUsersSearch;
use backend\models\TzSystemsUsers;
use common\models\eyun\HistoryRobots;
use common\service\wechat\RobotUserService;
use common\service\wechat\WechatUserService;
use common\tools\Tool_Common;
use Yii;
use backend\models\wechat\RobotUser;
use backend\models\searchs\wechat\RobotUser as RobotUserSearch;
use backend\controllers\BaseController;
use yii\data\ArrayDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * RobotUserController implements the CRUD actions for RobotUser model.
 */
class RobotUserController extends BaseController
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
     * Lists all RobotUser models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new RobotUserSearch();
        $queryParams = Yii::$app->request->queryParams;
        if($this->_user_id != 1){
            $queryParams['RobotUser']['user_id'] = $this->_user_id;
        }
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single RobotUser model.
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView()
    {
        $user_id = $this->_user_id;
        $allModels = RobotUser::find()->alias('r')
            ->select(['h.id', 'h.user_id', 'h.nickName', 'h.wechat_status', 'h.wcId', 'h.smallHeadImgUrl', 'h.update_at'])
            ->leftJoin(HistoryRobots::tableName().' h', 'r.user_id=h.user_id')
            ->where(['=', 'r.user_id', $user_id])
            ->asArray()->all();
        $dataProvider = new ArrayDataProvider([
            'allModels' => $allModels
        ]);

        #$SystemModels = TzSystemsUsers::find()->where(['uid'=>$user_id])->orderBy(['balance'=>SORT_DESC])->all();
        $tzSystemsUserSearchModel = new TzSystemsUsersSearch();
        $queryParams = Yii::$app->request->queryParams;
        if($this->_user_id != 1){
            $queryParams['TzSystemsUsers']['uid'] = $this->_user_id;
        }
        $systemDataProvider = $tzSystemsUserSearchModel->search($queryParams);
        return $this->render('view', [
            'model' => $this->findModel(['user_id'=>$user_id]),
            'dataProvider' => $dataProvider,
            'systemDataProvider' => $systemDataProvider,
            #'SystemModels' => $SystemModels,
        ]);
    }

    /**
     * 如果是获取二维码登录，则前端需要用下面的方法一直等待执行微信登录
     * @return array
     */
    public function actionSwitchWechat(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        $rst = RobotUserService::switchWechat($this->_user_id, $post);

        return $rst;
    }

    /**
     * 执行微信登录，获取二维码之后，前端调用该方法一直等待，仅跟上面的方法执行
     * @return array
     */
    public function actionActWechatLogin(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        #return ['status'=>200, 'msg'=>'ddd'];

        $rst = RobotUserService::actWechatLogin($this->_user_id, $post);
        Tool_Common::log('/wechat/'.__FUNCTION__, 'INFO', '执行微信登录', ['post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * 同步微信好友
     * @return array
     */
    public function actionSyncWechatFriends(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        $rst = WechatUserService::syncWechatFriends($this->_user_id);
        Tool_Common::log('/wechat/'.__FUNCTION__, 'INFO', '执行微信登录', ['post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * Creates a new RobotUser model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new RobotUser();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing RobotUser model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $params = ['id'=>$id, 'user_id'=>$this->_user_id];
        $model = $this->findModel($params);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionUpdateTzSystemUser($id)
    {
        $model = (new TzSystemsUsers())->findOne($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view']);
        }

        return $this->renderAjax('update_tz_system_user', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing RobotUser model.
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
     * Finds the RobotUser model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return RobotUser the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($params)
    {
        if (($model = RobotUser::findOne($params)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
