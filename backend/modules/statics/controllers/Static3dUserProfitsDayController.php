<?php

namespace backend\modules\statics\controllers;

use backend\service\UserService;
use common\models\AdminModel;
use Yii;
use backend\models\statics\Static3dUserProfitsDay;
use backend\models\searchs\statics\Static3dUserProfitsDay as Static3dUserProfitsDaySearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * Static3dUserProfitsDayController implements the CRUD actions for Static3dUserProfitsDay model.
 */
class Static3dUserProfitsDayController extends BaseController
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
     * Lists all Static3dUserProfitsDay models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new Static3dUserProfitsDaySearch();
        $queryParams = Yii::$app->request->queryParams;
        if(!empty($queryParams) && empty($queryParams['Static3dUserProfitsDay']['date'])){
            //$queryParams['Static3dUserProfitsDay']['date'] = date('Y-m-d');
        }

        $is3dAdmin = UserService::is3dAdmin(\Yii::$app->user->identity);
        if($this->_user_id != 1 && !$is3dAdmin){
            $user = \Yii::$app->user->identity;
            $user_id = $this->_user_id;;
            if($user->user_type == AdminModel::USER_TYPE_3D_CHILD) {
                $user_id = $user->parent_id;
            }
            $queryParams['Static3dUserProfitsDay']['user_id'] = $user_id;
        }

        $dataProvider = $searchModel->search($queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'is3dAdmin' => $is3dAdmin,
        ]);
    }

    /**
     * Displays a single Static3dUserProfitsDay model.
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
     * Creates a new Static3dUserProfitsDay model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Static3dUserProfitsDay();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Static3dUserProfitsDay model.
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
     * Deletes an existing Static3dUserProfitsDay model.
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
     * Finds the Static3dUserProfitsDay model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Static3dUserProfitsDay the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Static3dUserProfitsDay::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
