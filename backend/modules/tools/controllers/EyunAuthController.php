<?php

namespace backend\modules\tools\controllers;

use backend\models\EyunAuthBackend;
use common\service\wechat\eyun\EYunBaseService;
use Yii;
use backend\models\searchs\EyunAuth as EyunAuthSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * EyunAuthController implements the CRUD actions for EyunAuth model.
 */
class EyunAuthController extends BaseController
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
     * Lists all EyunAuth models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new EyunAuthSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single EyunAuth model.
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
        $model = new EyunAuthBackend();

        //p(Yii::$app->request->post(), 0);
        if ($model->load(Yii::$app->request->post())) {
            $now_time = time();
            $model->updated_at = $now_time;
            $model->created_at = $now_time;
            if(!$model->save()){
                p($model->getErrors());
            }
            return $this->redirect(['index']);
        }
        //p('xxx');

        return $this->renderAjax('_form', [
            'model' => $model,
        ]);

        /*
        $model = new EyunAuthBackend();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => true];
            } else {
                return $this->redirect(['index']);
            }
        }

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_form', [
                'model' => $model,
            ]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
        */
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

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }

        return $this->renderAjax('update', [
            'model' => $model,
        ]);
    }


    /**
     * Deletes an existing EyunAuth model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionLogin($id)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $model = $this->findModel($id);
        $loginRst = EYunBaseService::memberLogin($id);
        if($loginRst['code'] != 1000){
            return ['status'=>301, 'msg'=>'登陆失败：'.$loginRst['message']];
        }

        return ['status'=>200, 'msg'=>'登陆成功'];
    }

    /**
     * Deletes an existing EyunAuth model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        EyunAuthBackend::deleteRecord(['id'=>$id]);

        return $this->redirect(['index']);
    }

    /**
     * Finds the EyunAuth model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return EyunAuth the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = EyunAuthBackend::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
