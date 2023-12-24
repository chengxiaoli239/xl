<?php
/**
 * Created by PhpStorm.
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\api\controllers;

use backend\service\BetService;
use backend\service\clients\AgentClientsService;
use backend\service\clients\TzSystemUsersService;
use common\tools\Tool_Common;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class AgentClientsController extends Controller
{
    public $enableCsrfValidation = false;
    public $TzSystemsUsers = [];

    public function init(){
        $isAdminRoute = in_array(Yii::$app->controller->route, ['admin/route/index', 'admin/route/index.html']);
        $post = \Yii::$app->request->post();
        $AUTH_ACCESS_TOKENS = TzSystemUsersService::getAuthAccessTokens();
        if(!in_array($post['access_token'], $AUTH_ACCESS_TOKENS) && !$isAdminRoute){
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
     * 获取反向号码接口
     * @return array
     */
    public function actionGetInverseCodes(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        try {
            $code_type = $post['code_type'] ?? 4;
            $codesArr = BetService::getInverseCodesN($post['codesArr'], $code_type);

            $data = [
                'counts' => count($codesArr),
                'codeArr' => $codesArr
            ];
            $rst = ['status'=>200, 'data'=>$data, 'msg'=>'操作成功'];
        }catch (\Exception $e){
            $rst = ['status'=>300, 'data'=>[], 'msg'=>$e->getMessage()];
        }
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '获取反向号码接口', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @desc 错误日志记录
     * @return array|bool
     */
    public function actionPushAgentErrLog(){
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

    /**
     * @desc 客户端同步member bet日志
     * @return array|bool
     */
    public function actionSyncMemberBetLogs(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }
        if(empty($post['member_bet_logs']['Data']['Rows'])){
            return ['status'=>302, 'msg'=>'数据不能为空'];
        }

        # from_type:kuaixuan、kuaiyi  from:page、api
        $rst = AgentClientsService::syncMemberBetLogs($post['member_bet_logs'], $post['access_token'], $post['from_type'], $post['from'], $post['lottery_type']);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '客户端bet数据日志同步', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }
}
