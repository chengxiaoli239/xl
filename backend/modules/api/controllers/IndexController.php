<?php
/**
 * Created by PhpStorm.
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\api\controllers;

use backend\models\UserFollowData;
use backend\service\clients\TzSystemUsersService;
use backend\service\FootBallService;
use backend\service\HN0898Service;
use backend\service\UserService;
use common\tools\Tool_Common;
use Yii;
use yii\web\Controller;
use backend\service\BaseNumService;
use yii\filters\VerbFilter;

class IndexController extends Controller
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
     * @desc 接口参数：
     * 1、投注方式 playway ： 1二字定，10定位胆 目前暂支持二字定
     * 2、投注号码 code
     * 3、是否模拟：is_simulate
     * 4、期号：qihao，默认最新期号
     * @return array
     */
    public function actionTz(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = \Yii::$app->request->post();
        $playway = $data['playway'] ? $data['playway'] : 1;
        $users = [ ['account'=>'gaozi2017'] ]; // 临时测试
        $qihao = HN0898Service::getQihao();
        foreach ($users as $user){
            switch ($playway){
                case 1: // 1、二字定
                    $hezhi = $data['hezhi'];
                    $position = $data['position'];
                    $single = $data['single'] = 0.1;
                    $is_simulate = $data['is_simulate'] ? $data['is_simulate'] : 0;  // 默认模拟投注
                    $order_type = 2;

                    $account = $user['account'];
                    $HN0898Service = new HN0898Service($account, 0);
                    $code = BaseNumService::dwZuHe(explode(',',$position),[$hezhi]);
                    # 投注数据
                    $data = [ 'code'=>$code, 'qihao'=>$qihao, 'playway'=>$playway, 'single'=>$single, 'is_simulate'=>$is_simulate,'order_type'=>$order_type,'position'=>$position ];
                    $rst = $HN0898Service->tz($data);
                    break;
                case 10: # 2、定位胆
                    break;
                default:;
            }
        }
        return $rst;
    }

    /**
     * @desc 添加投注计划接口
     * @return array
     */
    public function actionOpPlan(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = \Yii::$app->request->post();
        $position = $data['position'];
        $hezhi = $data['hezhi'];
        $single = $data['single'];
        $playway = 1; # 二字定
        $is_simulate = $data['is_simulate'] = 1;
        $status = $data['status'] = 1;
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        $code = BaseNumService::dwZuHe(explode(',',$position),[$hezhi]);

        $users = [ ['account'=>'gaozi2017'] ]; // 临时测试
        foreach ($users as $k=>$user){
            $account = $user['account'];
            $planData = [
                'account'=> $account,
                'code'=> $code,
                'codes_hezhi' => $hezhi,
                'position'=>$position,
                'single' => $single,
                'is_simulate' => $is_simulate,
                'status' => $status,
                'plan_type' => 2,
            ];
            $UserFollowData  = UserFollowData::findOne(['account'=>$account,'playway'=>$playway,'codes_hezhi'=>$hezhi,'position'=>$position,'plan_type'=>2]);
            if(!$UserFollowData){
                $UserFollowData = new UserFollowData();
                $planData['create'] = time();
            }
            $UserFollowData->setAttributes($planData);

            if(!$UserFollowData->save()){
                $rst = ['status'=>300, 'msg'=>'计划添加失败'];
            }else{
                $HN0898Service = new HN0898Service($account, 0);

                # 投注数据
                $qihao = HN0898Service::getQihao();
                $data = [ 'code'=>$code, 'qihao'=>$qihao, 'playway'=>$playway, 'single'=>$single, 'is_simulate'=>$is_simulate,'order_type'=>2,'position'=>$position ];
                if($UserFollowData == 1) $rst = $HN0898Service->tz($data);
                else
                    $rst = ['status'=>302, 'msg'=>'投注失败~'];
            }
        }

        return $rst;
    }

    /**
     * @desc 接受微信消息
     * @return array
     */
    public function actionUpMembersInfo(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = \Yii::$app->request->post();

        Tool_Common::log('UpMembersInfo','INFO','0898投注记录-投注失败', ['msg'=>$data]);

        $rst = ['status'=>302, 'msg'=>'操作成功', 'data'=>$data];

        return $rst;
    }

    /**
     * @desc 登陆信息测试
     * @return array|mixed
     */
    public function actionUpdateUserCookies(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = \Yii::$app->request->post();

        $rst = UserService::updateUserCookies($data);
        Tool_Common::log('/user/upUserInfo','INFO','记录用户的登陆cookies', ['data'=>$data, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @return array|mixed
     */
    public function actionUserLogin(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = \Yii::$app->request->post();

        $data['msg'] = '登陆成功';

        return $data;
    }

    public function actionGetUserInfoByToken(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        $rst = UserService::getUserInfoByToken($post);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '获取用信息接口', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    public function actionUpdateClientLoginFlag(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        $rst = UserService::updateClientLoginFlag($post['access_token'], $post['flag']);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '获取用信息接口', ['account'=>$this->TzSystemsUsers['username'], 'post'=>$post, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @desc 更新用户状态
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionSwitchStatus(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        $rst = HN0898Service::updateStatus($post['id'], $model = '\backend\models\TzSystemsUsers', $post['field']);

        return $rst;
    }

    /**
     * @desc 更新用户状态
     * @param $id
     * @param $status
     * @return \yii\web\Response
     */
    public function actionValidate(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();

        return FootBallService::validateSecret($post);
    }

}
