<?php

namespace backend\modules\forum\controllers;

use backend\models\TzSystemsUsers;
use backend\service\clients\TzSystemUsersService;
use backend\service\HN0898Service;
use backend\service\UserService;
use common\models\AdminModel;
use common\service\CommonService;
use Yii;
use backend\models\TzSystems;
use backend\models\searchs\TzSystems as TzSystemsSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * TzSystemsController implements the CRUD actions for TzSystems model.
 */
class TzSystemsController extends BaseController
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
                    'delete' => ['POST', 'GET'],
                ],
            ],
        ];
    }

    /**
     * Lists all TzSystems models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new TzSystemsSearch();
        $queryParams = Yii::$app->request->queryParams;
        $TzSystemTypeId = TzSystemUsersService::TZ_SYSTEM_TYPES_OPTIONS[$this->user_type]??0;
        if(!empty($TzSystemTypeId)){
            $queryParams['TzSystems']['system_type_id'] = $TzSystemTypeId;
        }
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single TzSystems model.
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
     * @desc 更新记录is_auto_login 0/1
     * @param $id
     * @return \yii\web\Response
     */
    public function actionSwitchIsAutoLogin($id, $field='is_auto_login'){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $rst = HN0898Service::updateStatus($id, $model = '\backend\models\TzSystems', $field);

        return $this->redirect(['index', 'UserSysPlans[lottery_type]'=>$rst['lottery_type']]);
    }

    /**
     * @desc 更新记录status状态 0/1
     * @param $id
     * @return \yii\web\Response
     */
    public function actionSwitchStatus($id){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $rst = HN0898Service::updateStatus($id, $model = '\backend\models\TzSystems');

        return $this->redirect(['index', 'UserSysPlans[lottery_type]'=>$rst['lottery_type']]);
    }

    /**
     * Creates a new TzSystems model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreateBak()
    {
        $model = new TzSystems();
        if(\Yii::$app->request->isPost){
            $this->_post['TzSystems']['status'] = current($this->_post['TzSystems']['status']);
            $this->_post['TzSystems']['type'] = current($this->_post['TzSystems']['type']);
            $this->_post['TzSystems']['tz_types'] = implode(',', $this->_post['TzSystems']['tz_types']);
        }
        if ($model->load($this->_post) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $allTzTypes = CommonService::getAllTzTypes();
        return $this->render('create', [
            'model' => $model,
            'allTzTypes' => $allTzTypes,
        ]);
    }

    /**
     * Updates an existing TzSystems model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $post = Yii::$app->request->post();
        if(!empty($post)){
            $post['TzSystems']['type'] && $post['TzSystems']['type'] = implode(',',$post['TzSystems']['type']);
            $post['TzSystems']['tz_types'] && $post['TzSystems']['tz_types'] = implode(',',$post['TzSystems']['tz_types']);
            $post['TzSystems']['status'] = $post['TzSystems']['status'][0];
        }

        if ($model->load($post) && $model->save()) {
            return $this->redirect(['index']);
        }

        $model->tz_types = explode(',', $model->tz_types);
        $allTzTypes = CommonService::getAllTzTypes();
        return $this->render('update', [
            'allTzTypes' => $allTzTypes,
            'model' => $model,
        ]);
    }

    public function actionUpdateSiteInfo($id)
    {
        $model = (new TzSystems())->findOne($id);

        $post = $this->_post;
        if (!empty($post)) {
            $domain = trim($post['TzSystems']['ssc_domain'], '/');
            if(strpos($domain, 'https://') === false){
                $domain = 'https://'.$domain;
            }
            $post['TzSystems']['ssc_domain'] = $domain;
            $model->load($post) && $model->save();

            return $this->redirect(['site-index']);
        }

        return $this->renderAjax('update_site_info', [
            'model' => $model,
        ]);
    }

    public function actionCreate($id='')
    {
        if(!empty($id) OR $id=$this->_post['TzSystems']['id']){
            $model = $this->findModel($id);
        }else{
            $model = new TzSystems();
        }
        $YiiUser = \Yii::$app->user->identity;
        $userTypes = UserService::getAdminUserTypes(\Yii::$app->user->identity, $act=1);
        $nowTime = time();
        list($nextUserType, $nextRole) = UserService::getCreateDefaultRole($YiiUser, current($userTypes)['user_type']);
        if ($model->load($this->_post)) {
            try {
                $transaction = \Yii::$app->db->beginTransaction();
                if(empty($this->_post['TzSystems']['system_type_id'])){
                    $TzSystemTypeId = TzSystemUsersService::TZ_SYSTEM_TYPES_OPTIONS[$this->user_type]??0;
                    $this->_post['TzSystems']['system_type_id'] = $TzSystemTypeId;
                    $this->_post['TzSystems']['lottery_type'] = TzSystemUsersService::LOTTERY_TYPES_OPTIONS[$this->user_type];
                    $this->_post['TzSystems']['status'] = 1;
                }
                $this->_post['TzSystems']['type'] = $this->_post['TzSystems']['type']?:1;
                $this->_post['TzSystems']['tz_types'] = implode(',', $this->_post['TzSystems']['tz_types']);
                $this->_post['TzSystems']['created_at'] = $nowTime;
                $this->_post['TzSystems']['updated_at'] = $nowTime;
                $model->load($this->_post);

                if (!$model->save()) {
                    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    $errMsg = $model->getFirstErrors()?current($model->getFirstErrors()):'';
                    //throw_info($errMsg?:'处理异常'); // $model->getErrors()
                    return ['status'=>301, 'msg'=>$errMsg];
                }
                TzSystemUsersService::getSites($this->user_type, 0);
                $transaction->commit();
            }catch (\Exception $e){
                $transaction->rollBack();
                die('<script>alert("'.$e->getMessage().'"); history.back();</script>');
            }
            //p('kkdk');
            return $this->redirect(['/forum/tz-systems/index']);
        }else{
            $get = \Yii::$app->request->get();
            if(!empty($get['id'])){
                $model = $this->findModel($get['id']);
            }
        }

        $allTzTypes = CommonService::getAllTzTypes();
        return $this->renderAjax('create_tz_system', [
            'model' => $model,
            'allTzTypes' => $allTzTypes,
        ]);

    }

    /**
     * Deletes an existing TzSystems model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $TzSystemUsers = TzSystemsUsers::findOne(['tz_system_id'=>$id]);
        if(!empty($TzSystemUsers)){
            throw new NotFoundHttpException('有存在使用该系统的用户，请先删除');
        }
        TzSystems::deleteRecord(['id'=>$id]);

        return $this->redirect(['index']);
    }

    /**
     * Finds the TzSystems model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return TzSystems the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = TzSystems::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
