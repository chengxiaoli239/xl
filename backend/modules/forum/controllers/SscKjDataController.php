<?php

namespace backend\modules\forum\controllers;

use backend\service\UserSysPlansService;
use common\service\CommonService;
use Yii;
use backend\models\SscKjData;
use backend\models\searchs\SscKjData as SscKjDataSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * SscKjDataController implements the CRUD actions for SscKjData model.
 */
class SscKjDataController extends BaseController
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
     * Lists all SscKjData models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SscKjDataSearch();
        $queryParams = Yii::$app->request->queryParams;
        $lottery_types = UserSysPlansService::getMyLotteryTypes($this->_user_id);
        /*
        if(!$queryParams['SscKjData']['lottery_type']){
            $lottery_type = $lottery_types[0]['lottery_type'];
        }else{
            $lottery_type = $queryParams['SscKjData']['lottery_type'];
        }
        */
        $lottery_type = CommonService::getIndexLotteryType($this->_user_id, $queryParams);
        $queryParams['SscKjData']['lottery_type'] = $lottery_type;
        $dataProvider = $searchModel->search($queryParams);
        return $this->render('index', [
            'lottery_types' => $lottery_types,
            'lottery_type' => $lottery_type,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Lists all SscKjData models.
     * @return mixed
     */
    public function actionIndexOrg()
    {
        $searchModel = new SscKjDataSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index_org', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionExport()
    {
        // Set unlimited execution time and memory
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $searchModel = new SscKjDataSearch();
        $queryParams = Yii::$app->request->queryParams;
        $lottery_type = CommonService::getIndexLotteryType($this->_user_id, $queryParams);
        $queryParams['SscKjData']['lottery_type'] = $lottery_type;

        try {
            $query = $searchModel->search($queryParams)->query;
            $filename = '开奖数据_' . date('Ymd'). '_'. rand(1000, 9999) . '.csv';

            // Create a temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
            $fp = fopen($tempFile, 'w');

            // 写入CSV头
            fputcsv($fp, ['序号', '期号', '号码', '号码', '和值', '千', '百', '十', '个', '五', '时间']);

            // Process in batches
            foreach ($query->batch(100) as $models) {
                foreach ($models as $model) {
                    $typeArr = [1=>'四单', 2=>'四双', 3=>'两单两双', 4=>'一单三双', 5=>'一双三单'];
                    fputcsv($fp, [
                        $model->index_id,
                        $model->qihao,
                        $model->code_str,
                        $model->code_4n_str,
                        $model->codes_4nums_hz,
                        $model->code1,
                        $model->code2,
                        $model->code3,
                        $model->code4,
                        $model->code5,
                        date('Y-m-d H:i', $model->created_at)
                    ]);
                }
            }

            fclose($fp);

            // Send file using Yii2's response
            return Yii::$app->response->sendFile($tempFile, $filename, [
                'mimeType' => 'text/csv',
                'inline' => false
            ])->on(yii\web\Response::EVENT_AFTER_SEND, function($event) use ($tempFile) {
                unlink($tempFile); // Delete temporary file
            });

        } catch (\Exception $e) {
            Yii::error("Export failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Displays a single SscKjData model.
     * @param string $id
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
     * Creates a new SscKjData model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new SscKjData();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing SscKjData model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
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
     * Deletes an existing SscKjData model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the SscKjData model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return SscKjData the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SscKjData::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
