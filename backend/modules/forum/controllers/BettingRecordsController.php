<?php

namespace backend\modules\forum\controllers;

use backend\models\searchs\VPerdateProfits as VPerdateProfitsSearch;
use backend\service\agent\AgentUsersService;
use backend\service\BetService;
use backend\service\HN0898Service;
use backend\service\StaticService;
use backend\service\UserSysPlansService;
use common\service\CommonService;
use Yii;
use backend\models\BettingRecords;
use backend\models\searchs\BettingRecords as BettingRecordsSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * BettingRecordsController implements the CRUD actions for BettingRecords model.
 */
class BettingRecordsController extends BaseController
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
     * Lists all BettingRecords models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new BettingRecordsSearch();
        $queryParams = Yii::$app->request->queryParams;
        $lottery_types = UserSysPlansService::getMyLotteryTypes($this->_user_id);

        $lottery_type = CommonService::getIndexLotteryType($this->_user_id, $queryParams);
        $queryParams['BettingRecords']['lottery_type'] = $lottery_type;

        if($this->_user_id !== 1){
            $queryParams['BettingRecords']['uid'] = $this->_user_id;
        }

        $dataProvider = $searchModel->search($queryParams);
        $qihao = HN0898Service::getQihao();

        $data = [
            'lottery_types' => $lottery_types,
            'lottery_type' => $lottery_type,
            'qihao' => $qihao,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ];
        $is_agent = AgentUsersService::isAgent($this->_user_id);
        if($this->_user_id !== 1){ # 超级管理员
            if($is_agent){
                $view = 'index_agent';
            }else{
                $view = 'index';
            }
        }else{
            $view = 'index_admin';
        }
        return $this->render($view, $data);
    }

    /**
     * Lists all BettingRecords models.
     * @return mixed
     */
    public function actionSysTzList()
    {
        $searchModel = new BettingRecordsSearch();
        $queryParams = Yii::$app->request->queryParams;
        $queryParams['BettingRecords']['account'] = 'admin';
        $dataProvider = $searchModel->search($queryParams);

        $qihao = HN0898Service::getQihao();
        return $this->render('sys-tz-list', [
            'qihao' => $qihao,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Lists all BettingRecords models.
     * @return mixed
     */
    public function actionPreDateProfits()
    {
        $searchModel = new VPerdateProfitsSearch();
        $queryParams = Yii::$app->request->queryParams;
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('pre-date-profits', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * Displays a single BettingRecords model.
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
     * Creates a new BettingRecords model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new BettingRecords();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     *@desc 投注列表- 立即投注(正买)
     */
    public function actionTzNow($id){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $rst = BetService::tzNowBetRecord($this->_user_id, $id);

        return $this->redirect(['index']);
    }

    /**
     * Updates an existing BettingRecords model.
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
     * Deletes an existing BettingRecords model.
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

    public function actionCancelOrder($bet_id = 0){
        $bet_id = (int)$bet_id;
        $record = null;
        if(!$bet_id){
            $snid = Yii::$app->request->get('snid');
            $tz_system_id = Yii::$app->request->get('tz_system_id');
            if($snid){
                $query = BettingRecords::find()->where(['snid'=>$snid]);
                if($tz_system_id !== null && $tz_system_id !== ''){
                    $query->andWhere(['tz_system_id'=>$tz_system_id]);
                }
                if($this->_user_id != 1){
                    $query->andWhere(['uid'=>$this->_user_id]);
                }
                $record = $query->orderBy(['id'=>SORT_DESC])->one();
                $bet_id = $record->id ?? 0;
            }
        }else{
            $record = BettingRecords::findOne($bet_id);
        }

        $uid = ($this->_user_id == 1 && $record) ? $record->uid : $this->_user_id;
        $rst = $bet_id ? BetService::cancelOrder($uid, $bet_id) : ['status'=>300, 'msg'=>'找不到投注记录', 'lottery_type'=>0];
        Yii::$app->session->setFlash(($rst['status'] ?? 0) == 200 ? 'success' : 'error', $rst['msg'] ?? '撤单失败');

        return $this->redirect(Yii::$app->request->referrer ?: ['index', 'BettingRecords[lottery_type]'=>($rst['lottery_type'] ?? 0)]);
    }

    /**
     * Finds the BettingRecords model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return BettingRecords the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = BettingRecords::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
