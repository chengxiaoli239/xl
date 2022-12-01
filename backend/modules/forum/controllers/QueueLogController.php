<?php

namespace backend\modules\forum\controllers;

use backend\service\tools\QueueService;
use common\tools\Common;
use Yii;
use common\models\QueueLog;
use backend\models\searchs\QueueLog as QueueLogSearch;
use backend\controllers\BaseController;
use yii\base\Module;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * QueueLogController implements the CRUD actions for QueueLog model.
 */
class QueueLogController extends BaseController
{
    protected $service;

    public function __construct($id, Module $module, QueueService $queueService, array $config = [])
    {
        parent::__construct($id, $module, $config);

        $this->service = $queueService;
    }

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
     * Lists all QueueLog models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new QueueLogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionListPage()
    {
        $data = [];

        $data['options'] = $this->service->getOptions();

        return $this->render('list.html', $data);
    }

    public function actionGetList()
    {
        try {
            $params = \Yii::$app->request->get();
            $result = $this->service->getList($params);
            return Common::jsonSuccess($result);
        } catch (\Exception $e) {
            return Common::jsonError([], $e->getMessage());
        }
    }

    public function actionRePush()
    {
        try {
            $params = \Yii::$app->request->post();
            $this->service->rePush($params);
            return Common::jsonSuccess(['重新入列成功']);
        } catch (\Exception $e) {
            return Common::jsonError([], $e->getMessage());
        }
    }

    public function actionMarkComplete()
    {
        try {
            $params = \Yii::$app->request->post();
            $this->service->markComplete($params);
            return Common::jsonSuccess(['标记成功']);
        } catch (\Exception $e) {
            return Common::jsonError([], $e->getMessage());
        }
    }

    public function actionStatus()
    {
        try {
            $params = \Yii::$app->request->post();
            $result = $this->service->status($params);
            return Common::jsonSuccess($result);
        } catch (\Exception $e) {
            return Common::jsonError([], $e->getMessage());
        }
    }

    /**
     * Displays a single QueueLog model.
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
     * Creates a new QueueLog model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new QueueLog();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing QueueLog model.
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
     * Deletes an existing QueueLog model.
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
     * Finds the QueueLog model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return QueueLog the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = QueueLog::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
