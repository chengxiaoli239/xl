<?php

namespace backend\modules\forum\controllers;

use backend\models\ImportPlanCodes;
use backend\models\TzSystemsAuth;
use backend\models\TzSystemsUsers;
use backend\service\BetService;
use backend\service\HN0898Service;
use backend\service\numbers\DynamicFilterService;
use backend\service\NumService;
use backend\service\StaticService;
use backend\service\TzService;
use backend\service\UserService;
use backend\service\UserSysPlansService;
use common\service\CommonService;
use common\service\jobs\user\UserExpireTimeOperateJob;
use common\service\ssc\filterCode\FenLiShu;
use common\tools\Tool_Common;
use Yii;
use backend\models\UserSysPlans;
use backend\models\searchs\UserSysPlans as UserSysPlansSearch;
use backend\controllers\BaseController;
use yii\helpers\ArrayHelper;
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
        $tipTxt = '';

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$this->_user_id]);
        $mkey = 'user_expire_time_'.$this->_user_id;
        if($TzSystemsUsers->expire_time<(time()+1800)){
            $num = commonRedis()->incr($mkey);
            if($num<1){
                push_queue(UserExpireTimeOperateJob::class, ['user_id'=>$this->_user_id, 'business_id'=>$this->_user_id, 'queue_delay_time'=>1800]);
                commonRedis()->expire($mkey, 3600*2);
            }
            $tipTxt = "<div >你账号即将过期，为了不影响使用，请提前续费</font></div>";
        }

        //$view = $this->_user_id !== 1 ? 'index' : 'index_admin';
        $data = [
            'lottery_types' => $lottery_types,
            'lottery_type' => $lottery_type,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'myTzTypes' => $myTzTypes,
            'tipTxt' => $tipTxt,
        ];
        return $this->render('index', $data);
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

        $lottery_types = UserSysPlansService::getMyLotteryTypes($this->_user_id);
        if($this->_post){
            $this->_post['UserSysPlans']['lottery_type'] = $queryParams['lottery_type'];
        }

        UserSysPlansService::preOpData($this->_post, $this->_user_id);
        if ($model->load($this->_post) && $model->save()) {
            if(in_array($tz_type, \Yii::$app->params['IMPORT_CODES_TYPES']) && $model->id){ # 导入号码保存
                UserSysPlansService::saveImportCodesTxt($model->id, $this->_post['UserSysPlans']['import_codes_txts'], (int)$this->_post['change_per'][0], $this->_user_id);
            }
            return $this->redirect(['index', 'UserSysPlans[lottery_type]'=>$queryParams['lottery_type']]);
        }
        $tz_sites_Arr = TzService::getTzSites($this->_user_id);
        $plan_types = TzService::getTzPlanTypes();

        ############################ 排除参数开始 #############################
        # 1、排除前x期
        $model->is_filter = 0;
        $model->filter_xQ_before = ''; # x期
        $model->filter_pos1 = []; # 位置选项
        $model->filter_pos2 = []; # 位置选项

        # 2、排除前x天同期
        $model->is_filter_date = 0;
        $model->filter_xD_before = ''; # x天
        $model->filter_date_pos1 = []; # 位置选项
        $model->filter_date_pos2 = []; # 位置选项

        # 3、排除前期号
        $model->is_filter_qihao = 0;

        # 5、动态过滤
        $model->is_filter_dynamic = 0;
        # 分离数
        $model->fenli_shu_code = [0=>'']; #
        $model->fenli_shu_sel = [current(FenLiShu::getTypeOptions((int)$playway))];
        ############################ 排除参数结束 #############################

        $model->nums = UserSysPlansService::getDefaultTzNums($tz_type);
        $model->status = $model->status ? 1 : 0;
        $model->playway = $playway;
        $model->is_test = 0;
        $model->take_profits = 100000;
        $model->stop_loss = 100000;
        $model->single = in_array($tz_type, [27, 30, 17, 36, 37]) ? 1 : 0.1;
        $model->tz_type = $tz_type;
        $model->buy_type = 0;
        $model->plan_type = 0;
        $defaultSiteId = UserService::getUserDefaultSite($this->_user_id);
        $model->tz_sites = [$defaultSiteId];
        $model->start_qihao = HN0898Service::getQihao($queryParams['lottery_type']);

        $is_filters = [1=>'是'];
        $filter_pos1 = NumService::$pos_to_desc;
        $filter_pos2 = NumService::$pos_to_desc;
        $code_filter_types = NumService::get_code_filter_types();
        $filter_dynamic_typesArr = NumService::getDynamicType($playway);  # 动态过滤1
        $data =  [
            'model' => $model,
            'tz_type' => $tz_type,
            'lottery_type' => $queryParams['lottery_type'],
            'playway' => $playway,
            'plan_types' => $plan_types,
            'tz_sites_Arr' => $tz_sites_Arr,

            ############################ 排除参数开始 #############################
            # 1、排除前x期
            'is_filters' => $is_filters,
            'filter_pos1' => $filter_pos1,
            'filter_pos2' => $filter_pos2,

            # 2、排除前x天内同期
            'is_filter_dates' => $is_filters,
            'filter_date_pos1' => $filter_pos1,
            'filter_date_pos2' => $filter_pos2,
            'lottery_types' => $lottery_types,

            # 4、动态过滤
            'is_filter_dynamic' => $is_filters,
            'filter_dynamic_typesArr' => $filter_dynamic_typesArr,
            'filter_dynamic_types2' => DynamicFilterService::DYNAMIC_FILTER_TYPES,

            'code_filter_types' => $code_filter_types, # 排除类型

            # 2、排除前x天内同期
            'is_filter_qihaos' => $is_filters,
            ############################ 排除参数结束 #############################
        ];
        $data = array_merge($data, UserSysPlansService::getSysPlansTypeDatas($playway, $tz_type));
        //p($data);

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
        //p([$model->load($this->_post), $model->attributes, $model->save()]);
        if ($model->load($this->_post) && $model->save()) {
            //p([$model->load($this->_post), $model->attributes]);
            if(in_array($this->_post['UserSysPlans']['tz_type'], \Yii::$app->params['IMPORT_CODES_TYPES']) && $model->id){ # 导入号码保存
                UserSysPlansService::saveImportCodesTxt($model->id, $this->_post['UserSysPlans']['import_codes_txts'], (int)$this->_post['UserSysPlans']['change_per'][0], $this->_user_id);
            }
            if($this->_post['UserSysPlans']['is_batch_simulate'] == 1){
                $db = \Yii::$app->db;
                # 批量模拟数据
                $delete_sql = 'DELETE FROM {{%betting_records}} WHERE plan_id="'.$model->id.'"';
                $rst_delete = $db->createCommand($delete_sql)->execute();
                Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '模拟下注', ['plan_id'=>$model->id, 'delete_sql'=>$delete_sql, 'rst_delete'=>$rst_delete]);
            }
            return $this->redirect(['index', 'UserSysPlans[lottery_type]'=>$model->lottery_type]);
        }
        $tz_sites_Arr = TzService::getTzSites($this->_user_id);
        $model->tz_sites = explode(',', $model->tz_sites);
        if(in_array($model->tz_type, [22])){ # 和值、四定单双
            $model->hz_Arr = explode(',', $model->hz_Arr);
            /*
        }elseif (in_array($model->tz_type, [28])){
            if($model->hz_Arr){
                $jsons = json_decode($model->hz_Arr, true);
                foreach ($jsons as $key=>$v){
                    $model->$key = $v;
                }
            }
            */
        }elseif (in_array($model->tz_type, \Yii::$app->params['IS_XIAN'])){
            $hz_Arr_Data = json_decode($model->hz_Arr, true);
            $model->hz_Arr = $hz_Arr_Data['codes'];
        }elseif (in_array($model->tz_type, [18, 19, 20, 25, 27, 28, 29, 30, 31, 32, 33, 34])){
            $hz_Arr_Data = json_decode($model->hz_Arr, true);
            $where = ['plan_id'=>$model->id];
            if($this->_user_id != 1){
                $where['uid'] = $this->_user_id;
            }

            ############################ 排除参数开始 #############################
            # 1、排除前x期
            if(isset($hz_Arr_Data['filters'])){
                $filters=$hz_Arr_Data['filters'];
                $model->is_filter = $filters['is_filter'];
                $model->filter_xQ_before = $filters['filter_xQ_before'];
                $model->filter_pos1 = $filters['filter_pos1']; # 位置选项
                $model->start_qihao = $hz_Arr_Data['filters']['start_qihao'] ? : ''; # 模拟起始起始期号
                $model->filter_pos2 = $filters['filter_pos2']; # 位置选项
            }
            # 动态排除同位置号码
            if(isset($hz_Arr_Data['filters']['filter_type'])){
                $model->filter_type = $hz_Arr_Data['filters']['filter_type'];
                $model->filter_nums = $hz_Arr_Data['filters']['filter_nums'];
                $model->playway = $hz_Arr_Data['filters']['playway'];
                $model->filter_poses = $hz_Arr_Data['filters']['filter_poses'];
                $model->test_period_days = $hz_Arr_Data['filters']['test_period_days'] ? : '';
                $model->start_qihao = $hz_Arr_Data['filters']['start_qihao'] ? : '';
            }
            unset($hz_Arr_Data['filters']);

            # 动态过滤1
            if(isset($hz_Arr_Data['is_filter_dynamic'])){
                $model->is_filter_dynamic = (int)$hz_Arr_Data['is_filter_dynamic'];
                $model->filter_dynamic_types = $hz_Arr_Data['filter_dynamic_types'];
            }
            # 动态过滤2
            if(!empty($hz_Arr_Data['filter_dynamic_types2'])){
                $filter_dynamic_type2 = [];
                $dynamicTypes2 = array_column($hz_Arr_Data['filter_dynamic_types2'], null, 'type');
                foreach (DynamicFilterService::DYNAMIC_FILTER_TYPES as $k=>$dynamicType2){
                    $filter_dynamic_type2[$k] = $dynamicTypes2[$dynamicType2['type']]??$dynamicType2;
                }
                $model->filter_dynamic_types2 = $filter_dynamic_type2;
            }
            //p([$dynamicTypes2, $filter_dynamic_type2, DynamicFilterService::DYNAMIC_FILTER_TYPES]);

            # 动态排除前x位号码

            # 2、排除前x天同期
            if(isset($hz_Arr_Data['filter_dates'])){
                $filter_dates=$hz_Arr_Data['filter_dates'];
                $model->is_filter_date = $filter_dates['is_filter_date'];
                $model->filter_xD_before = $filter_dates['filter_xD_before'];
                $model->filter_date_pos1 = $filter_dates['filter_date_pos1']; # 位置选项
                $model->filter_date_pos2 = $filter_dates['filter_date_pos2']; # 位置选项
            }
            unset($hz_Arr_Data['filter_dates']);

            # 3、排除期号为定位的，比如：058期，二定则排除：58XX
            if(isset($hz_Arr_Data['filter_qihaos'])){
                $filter_qihaos = $hz_Arr_Data['filter_qihaos'];
                $model->is_filter_qihao = $filter_qihaos['is_filter_qihao'];
            }
            unset($hz_Arr_Data['filter_qihaos']);
            ############################ 排除参数结束 #############################

            $model->change_per = [];
            //$codes = ImportPlanCodes::findAll($where)->codes;
            $ImportPlanCodes = ImportPlanCodes::find()->where($where)->asArray()->all();
            //p($ImportPlanCodes,0);
            if(!empty($ImportPlanCodes)){
                //$codes = ArrayHelper::getColumn($ImportPlanCodes, 'codes');
                foreach ($ImportPlanCodes as $k=>$ImportPlanCodesData){
                    $codes[$ImportPlanCodesData['plan_id_sort_key']] = str_replace('@', ' ', str_replace(',', '', $ImportPlanCodesData['codes']));
                }
                $model->change_per = $hz_Arr_Data['change_per'] ? [$hz_Arr_Data['change_per']] : [];
                $model->import_codes_txts = $codes;
            }
            foreach ($hz_Arr_Data as $key=>$val){
                switch (true){
                    case in_array($key, ['hefen_pos1', 'hefen_pos2', 'hefen_pos3', 'hefen_pos4', 'no_fix_henfen_pos', 'fixed_sel_pos', 'arise_in_sel', 'odd_pos', 'even_pos', 'big_pos', 'small_pos']):
                        $model->$key = explode(',', $val);
                        break;
                    case $key == 'fenli_shu': # 分离数
                        $flsCodes = [];
                        $flsSels = [];
                        foreach ($hz_Arr_Data['fenli_shu']?:[] as $index=>$fls){
                            $flsCodes[] = $fls['code'];
                            $flsSels[] = $fls['type'];
                            $model->{"fenli_shu_sel_".$index} = $fls['type'];
                        }
                        $model->fenli_shu_sel = $flsCodes;
                        $model->fenli_shu_code = $flsCodes;
                        break;
                    default:
                        $model->$key = $val;
                        //if(in_array($key, ['hz', 'p1', 'p2', 'p3', 'p4', 'p5', 'bet_while_miss', 'status_val', 'type_4ds', 'code1', 'code2', 'arise', 'type_4d', 'type_4s', 'hefen', 'no_fix_hefen', 'arise_in', 'xhenfen','singles_key', 'ps_1', 'ps_2'])){
                        break;

                }
            }
        }
        $plan_types = TzService::getTzPlanTypes();

        $is_filters = [1=>'是'];
        $filter_pos1 = NumService::$pos_to_desc;
        $filter_pos2 = NumService::$pos_to_desc;
        $code_filter_types = NumService::get_code_filter_types();
        $data =  [
            'model' => $model,
            'tz_type' => $model->tz_type,
            'playway' => $model->playway,
            'lottery_type' => $model->lottery_type,
            'plan_types' => $plan_types,
            'tz_sites_Arr' => $tz_sites_Arr,

            # 1、排除前x期
            'is_filters' => $is_filters,
            'filter_pos1' => $filter_pos1,
            'filter_pos2' => $filter_pos2,

            # 2、排除前x天内同期
            'is_filter_dates' => $is_filters,
            'filter_date_pos1' => $filter_pos1,
            'filter_date_pos2' => $filter_pos2,

            # 4、动态过滤
            'is_filter_dynamic' => $is_filters,
            'filter_dynamic_typesArr' => NumService::getDynamicType($model->playway), # 动态过滤1
            'filter_dynamic_types2' => $filter_dynamic_type2??DynamicFilterService::DYNAMIC_FILTER_TYPES, # 动态过滤2

            'code_filter_types' => $code_filter_types, # 排除类型
            # 2、排除前x天内同期
            'is_filter_qihaos' => $is_filters,
        ];
        $data = array_merge($data, UserSysPlansService::getSysPlansTypeDatas($model->playway, $model->tz_type));

        return $this->render('update',$data);
    }

    /**
     * @desc
     * @return string
     */
    public function actionNewQuickBet(){
        $model = new usersysplans();
        $queryParams = \yii::$app->request->queryParams;

        $lottery_types = UserSysPlansService::getMyLotteryTypes($this->_user_id);
        $playway = betservice::getplaywaybytztype($tz_type=38);

        if($this->_post){
            $this->_post['UserSysPlans']['lottery_type'] = $queryParams['lottery_type'];
        }
        $lottery_type = CommonService::getIndexLotteryType($this->_user_id, $queryParams);

        UserSysPlansService::preopdata($this->_post, $this->_user_id);
        if ($model->load($this->_post) && $model->save()) {
            if(in_array($tz_type, \yii::$app->params['import_codes_types']) && $model->id){ # 导入号码保存
                UserSysPlansService::saveimportcodestxt($model->id, $this->_post['usersysplans']['import_codes_txts'], (int)$this->_post['change_per'][0], $this->_user_id);
            }
            return $this->redirect(['index', 'UserSysPlans[lottery_type]'=>$queryParams['lottery_type']]);
        }
        $tz_sites_arr = TzService::getTzSites($this->_user_id);
        $plan_types = TzService::getTzPlanTypes();

        ############################ 排除参数开始 #############################
        # 1、排除前x期
        $model->is_filter = 0;
        $model->filter_xQ_before = ''; # x期
        $model->filter_pos1 = []; # 位置选项
        $model->filter_pos2 = []; # 位置选项

        # 2、排除前x天同期
        $model->is_filter_date = 0;
        $model->filter_xD_before = ''; # x天
        $model->filter_date_pos1 = []; # 位置选项
        $model->filter_date_pos2 = []; # 位置选项

        ########### 新过滤快打 start #############
        UserSysPlansService::newKuaiDa($tz_type, $model, $lottery_type, $this->_user_id);
        ########### 新过滤快打 end #############

        # 2、排除前期号
        $model->is_filter_qihao = 0;

        # 5、动态过滤
        $model->is_filter_dynamic = 0;
        ############################ 排除参数结束 #############################

        $model->nums = usersysplansservice::getdefaulttznums($tz_type);
        $model->status = $model->status ? 1 : 0;
        $model->playway = $playway;
        $model->is_test = 0;
        $model->single = in_array($tz_type, [27, 30, 17, 36, 37]) ? 1 : 0.1;
        $model->tz_type = $tz_type;
        $model->buy_type = 0;
        $model->plan_type = 0;
        $model->tz_sites = userservice::getuserdefaultsite($this->_user_id);

        $is_filters = [1=>'是'];
        $filter_pos1 = NumService::$pos_to_desc;
        $filter_pos2 = NumService::$pos_to_desc;
        $data =  [
            'model' => $model,
            'tz_type' => $tz_type,
            'playway' => $playway,
            'plan_types' => $plan_types,
            'tz_sites_arr' => $tz_sites_arr,

            ############################ 排除参数开始 #############################
            # 1、排除前x期
            'is_filters' => $is_filters,
            'filter_pos1' => $filter_pos1,
            'filter_pos2' => $filter_pos2,

            # 2、排除前x天内同期
            'is_filter_dates' => $is_filters,
            'filter_date_pos1' => $filter_pos1,
            'filter_date_pos2' => $filter_pos2,
            'filter_dynamic_typesArr' => NumService::getDynamicType($playway),
            'lottery_types' => $lottery_types,
            'lottery_type' => $lottery_type,

            # 2、排除前x天内同期
            //'is_filter_qihaos' => $is_filters,
            ############################ 排除参数结束 #############################
        ];
        $data = array_merge($data, UserSysPlansService::getSysPlansTypeDatas($playway, $tz_type));
        return $this->render('create',$data);

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
     * @desc 批量更新状态
     * @param $id
     * @param $status
     * @return array
     */
    public function actionBatchSwitchStatus(): array
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        try {
            HN0898Service::batchSwitchStatus($post['ids'], '\backend\models\UserSysPlans', $post['field'], $post['val'], $this->_user_id);
            if($post['field'] == 'status'){
                foreach ($post['ids'] as $id){
                    HN0898Service::updateSysPlansStatus($id, $post['val'], $this->_user_id);
                }
            }
        }catch (\Exception $e){
            return ['status'=>300, 'msg'=>$e->getMessage()];
        }

        return ['status'=>200, 'msg'=>'操作成功'];
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
     * @desc 计划列表 - 立即投注
     * @param $id
     * @return \yii\web\Response
     */
    public function actionChangeIp(){
        if($this->_user_id==1){
            $rst = BetService::changPoxyIp();
            Tool_Common::log('ChangeIp', 'INFO', '更换代理ip', ['rst'=>$rst]);
        }

        return $this->redirect(['/forum/user-sys-plans/index']);
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

    public function actionSwitchTest($id, $status){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        HN0898Service::updateStatus($id, $model = '\backend\models\UserSysPlans', 'is_test');

        return $this->redirect(['index']);
    }

    /**
     * @desc 更新投注 购买方向
     * @param $status
     * @return \yii\web\Response
     */
    public function actionOpenPlanBetStatus(){ # open-plan-bet-status
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $id = $post['id'];
        $rst = UserSysPlansService::openPlanBetStatus($id, $this->_user_id);

        return $rst;
    }
    /**
     * @desc 重新计算止盈止损计划盈利点
     * @param $id
     * @param $type 1某个计划盈利归零2所有下注就盈利归零
     * @return \yii\web\Response|array
     */
    public function actionReCalculateProfits($id='', $type=1){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if($type==2){
            $rst = BetService::reCalculateAllBettingRecords($this->_user_id);
            $rstIndex = ['index'];
        }else{
            $rst = BetService::reCalculateProfits($id, $this->_user_id);
            $rstIndex = ['index', 'UserSysPlans[lottery_type]'=>$rst['lottery_type']];
        }
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$this->_user_id]);
        list($code, $current_profits) = UserService::updateUserProfits($TzSystemsUsers);
        if($type==2){
            return ['status'=>200, 'msg'=>'操作成功'];
        }

        return $this->redirect($rstIndex);
    }

    /**
     * @desc 表单页，遗漏、利润功能查询
     * @return array
     */
    public function actionQuickBet(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $rst = UserSysPlansService::newQuickBet($post, $this->_user_id, $post['code_type']);

        return $rst;
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
        UserSysPlansService::deleteOnePlanDatas($id, $this->_user_id);

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
