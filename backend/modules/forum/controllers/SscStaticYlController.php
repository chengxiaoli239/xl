<?php

namespace backend\modules\forum\controllers;

use backend\service\StaticService;
use backend\service\UserSysPlansService;
use common\service\CommonService;
use Yii;
use backend\models\SscStaticYl;
use backend\models\searchs\SscStaticYl as SscStaticYlSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * SscStaticYlController implements the CRUD actions for SscStaticYl model.
 */
class SscStaticYlController extends BaseController
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
     * Lists all SscStaticYl models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SscStaticYlSearch();
        $queryParams = Yii::$app->request->queryParams;
        $lottery_type = CommonService::getIndexLotteryType($this->_user_id, $queryParams);
        $queryParams['SscStaticYl']['status'] = 1;
        $type = $queryParams['SscStaticYl']['type'] ?? 2;
        $queryParams['SscStaticYl']['type'] = $type;

        $codeType = $queryParams['SscStaticYl']['code_type'] ?? 1;
        $codeTypeName = CommonService::getCodeTypeName($codeType);
        //p($codeTypeName);
        $lottery_types = UserSysPlansService::getMyLotteryTypes($this->_user_id);
        /*
        if(!$queryParams['SscStaticYl']['lottery_type']){
            $lottery_type = $lottery_types[0]['lottery_type'];
        }else{
            $lottery_type = $queryParams['SscStaticYl']['lottery_type'];
        }
        */
        $queryParams['SscStaticYl']['lottery_type'] = $lottery_type;

        $dataProvider = $searchModel->search($queryParams);

        $view = $type == 2 ? 'index' : 'indexs';
        //p($view,0);
        return $this->render($view, [
            'lottery_types' => $lottery_types,
            'lottery_type' => $lottery_type,
            'code_type' => $codeType,
            'searchModel' => $searchModel,
            'codeTypeName' => $codeTypeName,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @desc 单个号码统计
     * @return array|string
     */
    public function actionGetValStatic(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $type = $post['type'] ? $post['type'] : 4;
        $lottery_type = $post['lottery_type'] ? $post['lottery_type'] : 5;

        $rst = StaticService::getValStatic($post['val'], $type, $lottery_type);

        return $rst;
    }

    /**
     * @desc 号码类型统计
     * @return array|string
     */
    public function actionGetCodeTypeStatic(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $lottery_type = $post['lottery_type'] ? $post['lottery_type'] : 5;

        $rst = StaticService::getCodeTypeStatic($post['val'], $lottery_type);

        return $rst;
    }

    /**
     * @desc 表单页，遗漏、利润功能查询
     * @return array
     */
    public function actionQuery(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $rst = StaticService::queryCodeTypeStatic($post, $post['type']);

        return $rst;
    }

    /**
     * @desc 表单页，遗漏、利润功能查询
     * @return array
     */
    public function actionQueryProfits(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $rst = StaticService::queryCodeTypeProfits($post, $post['static_type']);

        return $rst;
    }

    /**
     * Displays a single SscStaticYl model.
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
     * Creates a new SscStaticYl model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new SscStaticYl();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing SscStaticYl model.
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
     * Deletes an existing SscStaticYl model.
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
     * Finds the SscStaticYl model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return SscStaticYl the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SscStaticYl::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
