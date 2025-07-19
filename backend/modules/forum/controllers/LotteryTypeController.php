<?php

namespace backend\modules\forum\controllers;

use backend\service\HN0898Service;
use backend\service\SscDataService;
use backend\service\statics\statics_base\DealDataService;
use backend\service\StaticService;
use backend\service\SystemService;
use backend\service\UserSysPlansService;
use Yii;
use backend\models\LotteryType;
use backend\models\searchs\LotteryType as LotteryTypeSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * LotteryTypeController implements the CRUD actions for LotteryType model.
 */
class LotteryTypeController extends BaseController
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
     * Lists all LotteryType models.
     * @return mixed
     */
    public function actionIndex()
    {
        $lottery_types = UserSysPlansService::getMyLotteryTypes($this->_user_id, 0);
        $searchModel = new LotteryTypeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single LotteryType model.
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
     * @desc 更新投注状态
     * @param $id
     * @return \yii\web\Response
     */
    public function actionSwitchStatus($id){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $get = \Yii::$app->request->get();
        $field = $get['field'];
        $val = $get['val'];
        $rst = HN0898Service::updateStatus($id, '\backend\models\LotteryType', $field, $val);
        StaticService::getGrabDataLotteryTypes($useCache=0);

        return $this->redirect(['index']);
    }

    /**
     * @desc 开启本期投注状态
     * @param $id
     * @return \yii\web\Response
     */
    public function actionOpenBetStatus(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $rst = HN0898Service::openBetStatus($post['lottery_type']);

        return $rst;
    }

    /**
     * @desc 初始化彩种单双数据
     * @return array
     */
    public function actionInitDsDatas(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        $rst = HN0898Service::initDsDatas($post['lottery_type']);

        return $rst;
    }

    /**
     * Creates a new LotteryType model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new LotteryType();

        $post = Yii::$app->request->post();
        if ($model->load($post) && $model->save()) {
            $lottery_type = $post['LotteryType']['lottery_type'];
            UserSysPlansService::getMyLotteryTypes(\Yii::$app->user->id, $useCache=0);
            DealDataService::insertLotteryDealDataStatus($lottery_type);
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * @desc 更新记录status状态 0/1
     * @param $id
     * @return \yii\web\Response
     */
    public function actionInitLottery(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $rstData = SystemService::initLottery($post['lottery_type']);
        $rst['rstData'] = $rstData;
        $rst['post']['post_url'] = 'http://'.\Yii::$app->request->hostName.'/forum/lottery-type/init-lottery';
        $rst['post']['post_data'] = $post;

        return $rst;
    }

    /**
     * @desc 删除下注记录status状态 0/1
     * @param $id
     * @return \yii\web\Response
     */
    public function actionDelBetRecord(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $rstData = SystemService::delBetRecord($post['lottery_type']);
        $rst['rstData'] = $rstData;
        $rst['post']['post_url'] = 'http://'.\Yii::$app->request->hostName.'/forum/lottery-type/del-bet-record';
        $rst['post']['post_data'] = $post;

        return $rst;
    }

    /**
     * Updates an existing LotteryType model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $post = Yii::$app->request->post();
        if ($model->load($post) && $model->save()) {
            UserSysPlansService::getMyLotteryTypes(\Yii::$app->user->id, $useCache=0);
            $lottery_type = $post['LotteryType']['lottery_type'];
            DealDataService::insertLotteryDealDataStatus($lottery_type);
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing LotteryType model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        LotteryType::deleteRecord(['id'=>$id]);

        return $this->redirect(['index']);
    }

    /**
     * Finds the LotteryType model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return LotteryType the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = LotteryType::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
