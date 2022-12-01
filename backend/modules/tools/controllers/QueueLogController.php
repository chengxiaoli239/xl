<?php
/**
 * Description
 *
 *
 * Datetime: 2022-04-08 17:04
 */

namespace backend\modules\tools\controllers;


use backend\controllers\BaseController;
use backend\models\searchs\QueueLog as QueueLogSearch;
use backend\service\tools\QueueService;
use common\tools\Common;
use yii\base\Module;
use yii\filters\VerbFilter;

class QueueController extends BaseController
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
}