<?php

namespace backend\modules\forum\controllers;

use backend\models\ImportPlanCodes;
use backend\models\TzSystemsAuth;
use backend\service\BetService;
use backend\service\HN0898Service;
use backend\service\StaticService;
use backend\service\TzService;
use backend\service\UserSysPlansService;
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
        if($this->_user_id !== 1){ # 超级管理员
            $queryParams['UserSysPlans']['uid'] = $this->_user_id;
        }
        $dataProvider = $searchModel->search($queryParams);
        $myTzTypes = UserSysPlansService::getMyTzTypes($this->_user_id);

        $view = $this->_user_id !== 1 ? 'index' : 'index_admin';
        return $this->render($view, [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'myTzTypes' => $myTzTypes,
        ]);
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
            'model' => $this->findModel($id),
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

        !$tz_type && $tz_type = \Yii::$app->request->queryParams['tz_type'];
        $playway = \Yii::$app->request->queryParams['playway'];
        !$playway && $playway = BetService::getPlaywayByTzType($tz_type);

        UserSysPlansService::preOpData($this->_post, $this->_user_id);
        if ($model->load($this->_post) && $model->save()) {
            if(in_array($tz_type, \Yii::$app->params['IMPORT_CODES_TYPES']) && $model->id){ # 导入号码保存
                UserSysPlansService::saveImportCodesTxt($model->id, $this->_post['UserSysPlans']['import_codes_txt'], $this->_user_id);
            }
            return $this->redirect(['index']);
        }
        $tz_sites_Arr = TzService::getTzSites($this->_user_id);

        $model->nums = UserSysPlansService::getDefaultTzNums($tz_type);
        $model->status = $model->status ? 1 : 0;
        $model->playway = $playway;
        $model->single = 0.1;
        $model->tz_type = $tz_type;
        $model->buy_type = 0;
        $defaultSiteIds = explode(',',TzSystemsAuth::findOne(['uid'=>$this->_user_id])->tz_systems_ids);
        $model->tz_sites = [$defaultSiteIds[0]];

        $data =  [
            'model' => $model,
            'tz_type' => $tz_type,
            'playway' => $playway,
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
        $model = $this->findModel($id);

        UserSysPlansService::preOpData($this->_post, $this->_user_id);
        //p($this->_post);

        if ($model->load($this->_post) && $model->save()) {
            //return $this->redirect(['view', 'id' => $model->id]);
            if(in_array($this->_post['UserSysPlans']['tz_type'], \Yii::$app->params['IMPORT_CODES_TYPES']) && $model->id){ # 导入号码保存
                UserSysPlansService::saveImportCodesTxt($model->id, $this->_post['UserSysPlans']['import_codes_txt'], $this->_user_id);
            }
            return $this->redirect(['index']);
        }
        //p($this->_post);
        $tz_sites_Arr = TzService::getTzSites($this->_user_id);
        $model->tz_sites = explode(',', $model->tz_sites);
        if(in_array($model->tz_type, [20, 22])){ # 和值、四定单双
            $model->hz_Arr = explode(',', $model->hz_Arr);
        }elseif (in_array($model->tz_type, \Yii::$app->params['IMPORT_CODES_TYPES'])){
            $codes = ImportPlanCodes::findOne(['uid'=>$this->_user_id, 'plan_id'=>$model])->codes;
            $model->import_codes_txt = str_replace('@', ' ', str_replace(',', '', $codes));
        }elseif ($model->tz_type == 25){
            $hz_Arr_Data = json_decode($model->hz_Arr, true);
            foreach ($hz_Arr_Data as $key=>$val){
                if(in_array($key, ['hz', 'p1', 'p2', 'p3', 'p4', 'arise', 'type_4d', 'type_4s'])){
                    $model->$key = $val;
                }else{
                    $model->$key[] = $val;
                }
            }
        }

        $data =  [
            'model' => $model,
            'tz_type' => $model->tz_type,
            'playway' => $model->playway,
            'tz_sites_Arr' => $tz_sites_Arr,
        ];
        $data = array_merge($data, UserSysPlansService::getSysPlansTypeDatas($model->playway, $model->tz_type));
        //p($model->getErrors());
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
        HN0898Service::updateSysPlansStatus($id, $status, $this->_user_id);

        return $this->redirect(['index']);
    }

    /**
     * @desc 计划列表 - 立即投注
     * @param $id
     * @return \yii\web\Response
     */
    public function actionTzNow($id){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $rst = BetService::userSysPlansTzNow($id, $this->_user_id);

        return $this->redirect(['/forum/betting-records/index']);
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
     * Deletes an existing UserSysPlans model.
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
     * Finds the UserSysPlans model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return UserSysPlans the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = UserSysPlans::findOne(['id'=>$id, 'uid'=>$this->_user_id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
