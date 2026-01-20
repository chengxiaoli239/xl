<?php
/**
 * Created by PhpStorm.
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\api\controllers;

use backend\models\UserSysPlans;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\clients\TzSystemUsersService;
use common\service\ssc\SscKjDataService;
use common\tools\Tool_Common;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class TzSystemUsersController extends Controller
{
    public $enableCsrfValidation = false;
    public $TzSystemsUsers = [];

    public function init(){
        $isAdminRoute = in_array(Yii::$app->controller->route, ['admin/route/index', 'admin/route/index.html']);
        $post = \Yii::$app->request->post();
        $AUTH_ACCESS_TOKENS = TzSystemUsersService::getAuthAccessTokens();
        if(!in_array($post['access_token'], $AUTH_ACCESS_TOKENS) && !$isAdminRoute && $post['from'] != 'hk_server'){
            header('content-type:application/json');
            die(json_encode(['status'=>302, 'msg'=>'您无权限访问', 'data'=>$post], 320));
        }
        $this->TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($post['access_token']);
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
     * @return array|mixed
     */
    public function actionGetLists(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        $rst = TzSystemUsersService::getLists($post);

        return $rst;
    }

    /**
     * @desc 同步余额接口
     * @return array|bool
     */
    public function actionSynBalance(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }

        $rst = BaseService::synBalanceByAccessToken($post['access_token'], $is_auto=2);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '同步积分接口', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @desc 同步余额接口
     * @return array|bool
     */
    public function actionGetCookies(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }

        $rst = TzSystemUsersService::getCookiesByAccessToken($post['access_token']);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '获取cookies接口', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @desc 更新新的robot7
     * @return array|bool
     */
    public function actionUpdateNewRobot7(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }

        $rst = TzSystemUsersService::updateRobot7ByAccessToken($post['access_token'], $post['new_robot7'], $post['old_robot7']);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '获取cookies接口', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @desc 客户端同步开奖号码
     * @return array|bool
     */
    public function actionPushSyncKjDatas(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }
        if(empty($post['kj_datas']['opencode'])){
            return ['status'=>302, 'msg'=>'开奖数据不能为空'];
        }

        $rst = TzSystemUsersService::syncClientKjDatas($post['kj_datas'], $post['access_token'], $post['lottery_type']);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '客户端开奖数据同步', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @desc 接收开奖号码
     * @return array
     */
    public function actionAcceptKjData(): array
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }

        $rst = SscKjDataService::acceptKjData($post['kj_data'], $post['lottery_type']);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '接收开奖数据同步', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @desc 客户端同步余额
     * @return array|bool
     */
    public function actionPushSyncBalance(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }

        $rst = TzSystemUsersService::syncClientBalance($post['access_token'], $post['balance']);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '客户端余额同步', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @desc 获取激活任务
     * @return array|bool
     */
    public function actionGetActivePlanTasks(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }

        $rst = TzSystemUsersService::getActivePlanTasks($post['access_token'], $post['current_qihao'], $post['direct']??0, $post['lottery_type']);
        $data = $rst['data']??[];
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '获取激活任务', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'count'=>count($data), /*'rst'=>$rst*/]);

        return $rst;
    }

    /**
     * @desc 获取激活期号
     * @return array|bool
     */
    public function actionGetActiveQihao(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }

        $rst = TzSystemUsersService::getActiveQihao($post['lottery_type']);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '获取激活期号', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, /* 'rst'=>$rst*/]);

        return $rst;
    }

    /**
     * @desc 获取计划号码的号码
     * @return array|bool
     */
    public function actionGetCodesByPlanIds(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }

        $rst = BetService::getCodesByPlanIds($post['plan_ids'], $post['access_token']);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '获取计划号码的号码接口', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @desc 激活期号
     * @return array|bool
     */
    public function actionPushActiveQihao(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }

        $activeQihaoData = $post['activeQihaoData']['Data'];
        $qihao = $activeQihaoData['real_period_no'];
        if($activeQihaoData['status'] == 1){
            Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', $qihao.'期-封盘0', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post]);
            return ['status'=>302, 'msg'=>$qihao.'期封盘状态'];
        }

        $rst = BetService::openBetQihao($post['access_token'], $qihao, $post['lottery_type']);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', $qihao.'期-开盘1', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @desc 通知游戏结果
     * @return array|bool
     */
    public function actionPushTasksBetRst(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }

        $rst = BetService::pushTasksBetRst($post['plan_id'], $post['qihao'], $post['betRst'], $post['access_token'], $post['lottery_type']);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '游戏结果通知', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }
    /**
     * @desc 游戏结果消息通知
     * @return array|bool
     */
    public function actionPushTasksBetRstNotice(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }
        return ['status'=>200, 'msg'=>'操作成功'];
    }

    /**
     * @desc 错误日志记录
     * @return array|bool
     */
    public function actionPushErrLog(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }
        $rst = ['status'=>200, 'msg'=>'接收成功'];

        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '错误日志记录成功', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @desc 更新客户端用户robot_id
     * @return array|bool
     */
    public function actionUpdateRobotId(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }
        $rst = TzSystemUsersService::updateClientRobotId($post['access_token'], $post['err_msg']);

        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '更新客户端用户robot_id', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

}
