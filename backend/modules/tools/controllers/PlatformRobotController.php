<?php

namespace backend\modules\tools\controllers;

use common\service\open\actions\PlatformRobotService;
use Yii;
use backend\models\open\PlatformRobot;
use backend\models\searchs\PlatformRobot as PlatformRobotSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PlatformRobotController implements the CRUD actions for PlatformRobot model.
 */
class PlatformRobotController extends BaseController
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
     * Lists all PlatformRobot models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PlatformRobotSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single PlatformRobot model.
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
     * Creates a new EyunAuth model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new PlatformRobot();

        if ($model->load(Yii::$app->request->post())) {
            $now_time = time();
            $platform_robot_id = explode(':', trim($model->token))[0];
            $model->platform_robot_id = $platform_robot_id;
            $model->user_id = $this->_user_id;
            $model->updated_at = $now_time;
            $model->created_at = $now_time;
            if(!$model->save()){
                p($model->getErrors());
            }
            return $this->redirect(['index']);
        }

        return $this->renderAjax('_form', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing EyunAuth model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $now_time = time();
            $platform_robot_id = explode(':', trim($model->token))[0];
            $model->platform_robot_id = $platform_robot_id;
            $model->user_id = $this->_user_id;
            $model->updated_at = $now_time;
            $model->save();
            return $this->redirect(['index']);
        }

        return $this->renderAjax('update', [
            'model' => $model,
        ]);
    }

    /**
     * login platform eg:telegram.
     * If login is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionLogin($id)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $model = $this->findModel($id);
        $loginRst = PlatformRobotService::login($model);
        if($loginRst['status'] != 200){
            return ['status'=>301, 'msg'=>'登陆失败：'.$loginRst['message']];
        }

        return ['status'=>200, 'msg'=>'登陆成功'];
    }

    /**
     * Deletes an existing PlatformRobot model.
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
     * Finds the PlatformRobot model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return PlatformRobot the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = PlatformRobot::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
