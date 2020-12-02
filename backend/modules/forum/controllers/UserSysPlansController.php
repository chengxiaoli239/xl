<?php

namespace backend\modules\forum\controllers;

use backend\models\ImportPlanCodes;
use backend\models\TzSystemsAuth;
use backend\service\BetService;
use backend\service\HN0898Service;
use backend\service\StaticService;
use backend\service\TzService;
use backend\service\UserService;
use backend\service\UserSysPlansService;
use common\service\CommonService;
use common\tools\Tool_Common;
use Yii;
use backend\models\UserSysPlans;
use backend\models\searchs\UserSysPlans as UserSysPlansSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * UserSysPlansController implements the CRUD actions for UserSysPlans model.
 */
class UserSysPlansController extends BaseController
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
     * Lists all UserSysPlans models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UserSysPlansSearch();
        $queryParams = Yii::$app->request->queryParams;

        $lottery_types = UserSysPlansService::getMyLotteryTypes($this->_user_id);
        /*
        if(!$queryParams['UserSysPlans']['lottery_type']){
            $lottery_type = $lottery_types[0]['lottery_type'];
        }else{
            $lottery_type = $queryParams['UserSysPlans']['lottery_type'];
        }
        */
        $lottery_type = CommonService::getIndexLotteryType($this->_user_id, $queryParams);

        $queryParams['UserSysPlans']['lottery_type'] = $lottery_type;

        if($this->_user_id !== 1){ # 超级管理员
            $queryParams['UserSysPlans']['uid'] = $this->_user_id;
        }
        $dataProvider = $searchModel->search($queryParams);

        $myTzTypes = UserSysPlansService::getMyTzTypes($this->_user_id, $lottery_type);

        $view = $this->_user_id !== 1 ? 'index' : 'index_admin';
        $data = [
            'lottery_types' => $lottery_types,
            'lottery_type' => $lottery_type,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'myTzTypes' => $myTzTypes,
        ];
        return $this->render($view, $data);
    }

    /**
     * Displays a single UserSysPlans model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id, \Yii::$app->user->id),
        ]);
    }

    /**
     * @desc 三定的tz_type跟
     * Creates a new UserSysPlans model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($tz_type='')
    {
        $model = new UserSysPlans();
        $queryParams = \Yii::$app->request->queryParams;

        $playway = BetService::getPlaywayByTzType($tz_type);

        if($this->_post){
            $this->_post['UserSysPlans']['lottery_type'] = $queryParams['lottery_type'];
        }

        UserSysPlansService::preOpData($this->_post, $this->_user_id);
        //p([$this->_post, $queryParams]);
        if ($model->load($this->_post) && $model->save()) {
            if(in_array($tz_type, \Yii::$app->params['IMPORT_CODES_TYPES']) && $model->id){ # 导入号码保存
                UserSysPlansService::saveImportCodesTxt($model->id, $this->_post['UserSysPlans']['import_codes_txt'], $this->_user_id);
            }
            return $this->redirect(['index', 'UserSysPlans[lottery_type]'=>$queryParams['lottery_type']]);
        }
        $tz_sites_Arr = TzService::getTzSites($this->_user_id);
        $plan_types = TzService::getTzPlanTypes();

        $model->nums = UserSysPlansService::getDefaultTzNums($tz_type);
        $model->status = $model->status ? 1 : 0;
        $model->playway = $playway;
        $model->is_test = 0;
        $model->single = in_array($tz_type, [30]) ? 1 : 0.1;
        $model->tz_type = $tz_type;
        $model->buy_type = 0;
        $model->plan_type = 0;
        $defaultSiteId = UserService::getUserDefaultSite($this->_user_id);
        $model->tz_sites = [$defaultSiteId];

        if(in_array($tz_type, [28])){ # 系统快捷
        }

        $data =  [
            'model' => $model,
            'tz_type' => $tz_type,
            'lottery_type' => $queryParams['lottery_type'],
            'playway' => $playway,
            'plan_types' => $plan_types,
            'tz_sites_Arr' => $tz_sites_Arr
        ];
        $data = array_merge($data, UserSysPlansService::getSysPlansTypeDatas($playway, $tz_type));

        return $this->render('create',$data);
    }

    /**
     * Updates an existing UserSysPlans model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id, \Yii::$app->user->id);

        UserSysPlansService::preOpData($this->_post, $this->_user_id, $id);
        $this->_post['update_time'] = date('Y-m-d H:i:s');
        if ($model->load($this->_post) && $model->save()) {
            if(in_array($this->_post['UserSysPlans']['tz_type'], \Yii::$app->params['IMPORT_CODES_TYPES']) && $model->id){ # 导入号码保存
                UserSysPlansService::saveImportCodesTxt($model->id, $this->_post['UserSysPlans']['import_codes_txt'], $this->_user_id);
            }
            return $this->redirect(['index', 'UserSysPlans[lottery_type]'=>$model->lottery_type]);
        }
        $tz_sites_Arr = TzService::getTzSites($this->_user_id);
        $model->tz_sites = explode(',', $model->tz_sites);
        if(in_array($model->tz_type, [22])){ # 和值、四定单双
            $model->hz_Arr = explode(',', $model->hz_Arr);
        }elseif (in_array($model->tz_type, [28])){
            if($model->hz_Arr){
                $jsons = json_decode($model->hz_Arr, true);
                foreach ($jsons as $key=>$v){
                    $model->$key = $v;
                }
            }
        }elseif (false && in_array($model->tz_type, \Yii::$app->params['IMPORT_CODES_TYPES'])){
            $codes = ImportPlanCodes::findOne(['uid'=>$this->_user_id, 'plan_id'=>$model])->codes;
            $model->import_codes_txt = str_replace('@', ' ', str_replace(',', '', $codes));
        }elseif (in_array($model->tz_type, [18, 19, 20, 25, 27, 29, 30, 31, 32, 33])){
            $hz_Arr_Data = json_decode($model->hz_Arr, true);
            //p($hz_Arr_Data);
            $codes = ImportPlanCodes::findOne(['uid'=>$this->_user_id, 'plan_id'=>$model])->codes;
            $model->import_codes_txt = str_replace('@', ' ', str_replace(',', '', $codes));
            foreach ($hz_Arr_Data as $key=>$val){
                if(in_array($key, ['hz', 'p1', 'p2', 'p3', 'p4', 'p5', 'bet_while_miss', 'status_val', 'type_4ds', 'code1', 'code2', 'arise', 'type_4d', 'type_4s', 'hefen', 'no_fix_hefen', 'arise_in', 'xhenfen','singles_key'])){
                    $model->$key = $val;
                }elseif(in_array($key, ['hefen_pos', 'no_fix_henfen_pos', 'arise_in_sel'])){
                    $model->$key = explode(',', $val);
                }else{
                    $model->$key[] = $val;
                }
            }
        }
        $plan_types = TzService::getTzPlanTypes();

        $data =  [
            'model' => $model,
            'tz_type' => $model->tz_type,
            'playway' => $model->playway,
            'lottery_type' => $model->lottery_type,
            'plan_types' => $plan_types,
            'tz_sites_Arr' => $tz_sites_Arr,
        ];
        $data = array_merge($data, UserSysPlansService::getSysPlansTypeDatas($model->playway, $model->tz_type));
        //p($data);

        return $this->render('update',$data);
    }

    /**
     * @desc 更新投注状态
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchStatus($id,$status){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $rst = HN0898Service::updateSysPlansStatus($id, $status, $this->_user_id);

        return $this->redirect(['index', 'UserSysPlans[lottery_type]'=>$rst['lottery_type']]);
    }

    /**
     * @desc 计划列表 - 立即投注
     * @param $id
     * @return \yii\web\Response
     */
    public function actionTzNow($id){
        //\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $rst = BetService::userSysPlansTzNow($id, $this->_user_id);
        Tool_Common::log('actionTzNow', 'INFO', '计划列表 - 立即投注', ['rst'=>$rst]);

        return $this->redirect(['/forum/betting-records/index', 'BettingRecords[lottery_type]'=>$rst['lottery_type']]);
    }

    /**
     * @desc 更新投注 购买方向
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchBuyType($id,$status){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        BetService::updateSysPlansBuyType($id, $status, $this->_account);

        return $this->redirect(['index']);
    }

    /**
     * @desc 重新计算止盈止损计划盈利点
     * @param $id
     * @return \yii\web\Response
     */
    public function actionReCalculateProfits($id){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $rst = BetService::reCalculateProfits($id, $this->_user_id);

        return $this->redirect(['index', 'UserSysPlans[lottery_type]'=>$rst['lottery_type']]);
    }

    /**
     * Deletes an existing UserSysPlans model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id, \Yii::$app->user->id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the UserSysPlans model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return UserSysPlans the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id, $uid)
    {
        if($uid == 1){
            $where = ['id'=>$id];
        }else{
            $where = ['id'=>$id, 'uid'=>$this->_user_id];
        }
        if (($model = UserSysPlans::findOne($where)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
