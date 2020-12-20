<?php

namespace backend\modules\forum\controllers;

use backend\service\BaseService;
use backend\service\UserService;
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

        return $rst;
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
            'model' => $this->findModel($id),
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
     * @desc 更新用户到期时间
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
        $model = $this->findModel($id);
        $post = Yii::$app->request->post();
        if($post){
            $post['TzSystemsUsers']['user_agent'] = 'User-Agent: '.$_SERVER['HTTP_USER_AGENT'];
            $cookie = '';
            if($model->account == $post['TzSystemsUsers']['account']){
                 $cookie = trim($post['TzSystemsUsers']['cookie']);
            }
            $post['TzSystemsUsers']['ssc_domain'] = trim($post['TzSystemsUsers']['ssc_domain'], '/');
            $post['TzSystemsUsers']['cookie'] = $cookie;
        }

        if ($model->load($post) && $model->save()) {
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
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the TzSystemsUsers model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return TzSystemsUsers the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = TzSystemsUsers::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
