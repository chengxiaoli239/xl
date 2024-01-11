<?php

namespace backend\modules\statics\controllers;

use backend\service\HN0898Service;
use backend\service\UserService;
use common\models\AdminModel;
use common\service\jobs\statics_3d\UserDayStaticsJobs;
use common\service\wechat\WechatUserService;
use Yii;
use backend\models\statics\Static3dUserProfitsDayAll;
use backend\models\searchs\statics\Static3dUserProfitsDayAll as Static3dUserProfitsDayAllSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * Static3dUserProfitsDayController implements the CRUD actions for Static3dUserProfitsDayAll model.
 */
class Static3dUserProfitsDayAllController extends BaseController
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
     * Lists all Static3dUserProfitsDayAll models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new Static3dUserProfitsDayAllSearch();
        $queryParams = Yii::$app->request->queryParams;
        if(!empty($queryParams) && empty($queryParams['Static3dUserProfitsDayAll']['date'])){
            //$queryParams['Static3dUserProfitsDayAll']['date'] = date('Y-m-d');
        }

        $is3dAdmin = UserService::is3dAdmin(\Yii::$app->user->identity);
        if($this->_user_id != 1 && !$is3dAdmin){
            $user = \Yii::$app->user->identity;
            $user_id = $this->_user_id;;
            if($user->user_type == AdminModel::USER_TYPE_3D_CHILD) {
                $user_id = $user->parent_id;
            }
            $queryParams['Static3dUserProfitsDayAll']['user_id'] = $user_id;
        }

        $dataProvider = $searchModel->search($queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'is3dAdmin' => $is3dAdmin,
        ]);
    }

    /**
     * Displays a single Static3dUserProfitsDayAll model.
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
     * Creates a new Static3dUserProfitsDayAll model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Static3dUserProfitsDayAll();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Static3dUserProfitsDayAll model.
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
     * @desc 更新是否接收用户消息状态
     * @param $id
     * @param $status
     * @return array
     */
    public function actionReCalculate(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        push_queue_fast(UserDayStaticsJobs::class, ['user_id'=>$this->_user_id, 'msg'=>'报表重新计算', 'date'=>$post['date'], 'wechat_user_id'=>$post['wechatUserId']]);

        return ['status'=>200, 'msg'=>'操作成功，请稍候刷新...'];
    }

    /**
     * Deletes an existing Static3dUserProfitsDayAll model.
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
     * Finds the Static3dUserProfitsDayAll model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Static3dUserProfitsDayAll the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Static3dUserProfitsDayAll::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
