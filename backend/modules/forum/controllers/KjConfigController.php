<?php

namespace backend\modules\forum\controllers;

use backend\service\HN0898Service;
use backend\service\UserSysPlansService;
use common\kj\cqssc\CqsscKcw;
use common\service\CommonService;
use common\service\lottery\LotteryTypeService;
use Yii;
use backend\models\KjConfig;
use backend\models\searchs\KjConfig as KjConfigSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * KjConfigController implements the CRUD actions for KjConfig model.
 */
class KjConfigController extends BaseController
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
     * Lists all KjConfig models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new KjConfigSearch();
        $queryParams = Yii::$app->request->queryParams;
        $lottery_types = UserSysPlansService::getMyLotteryTypes($this->_user_id);

        $lottery_type = CommonService::getIndexLotteryType($this->_user_id, $queryParams);
        $queryParams['KjConfig']['lottery_type'] = $lottery_type;
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', [
            'lottery_types' => $lottery_types,
            'lottery_type' => $lottery_type,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single KjConfig model.
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
     * Creates a new KjConfig model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new KjConfig();
        $post = Yii::$app->request->post();
        self::trimParams($post);

        if ($model->load($post) && $model->save()) {
            return $this->redirect(['index']);
        }

        $lotteryNameArr = CqsscKcw::getLotteryNameArr();
        return $this->render('create', [
            'model' => $model,
            'lottery_type_arr' => $lotteryNameArr,
        ]);
    }

    /**
     * @desc 去空格
     * @param $posts
     * @return mixed
     */
    public static function trimParams(&$posts){
        foreach ($posts as $key=>$post){
            foreach ($post as $k){
                $posts[$key][$k] = trim($post[$k]);
            }
        }
        return $post;
    }

    /**
     * Updates an existing KjConfig model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $post = Yii::$app->request->post();
        self::trimParams($post);

        if ($model->load($post) && $model->save()) {
            return $this->redirect(['index']);
        }

        $lotteryNameArr = CqsscKcw::getLotteryNameArr();
        return $this->render('update', [
            'model' => $model,
            'lottery_type_arr' => $lotteryNameArr,
        ]);
    }

    /**
     * @desc 更新投注状态
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchStatus($id,$status){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        HN0898Service::updateKjConfigStatus($id, $status, $this->_user_id);
        LotteryTypeService::getLotteryTypeData($grabDataStatus=1, $useCache=0);

        return $this->redirect(['index']);
    }

    /**
     * Deletes an existing KjConfig model.
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
     * Finds the KjConfig model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return KjConfig the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = KjConfig::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
