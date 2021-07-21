<?php
/**
 * Created by PhpStorm.
 * User:wangyegao
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\api\controllers;

use backend\service\BaseService;
use backend\service\clients\TzSystemUsersService;
use common\tools\Tool_Common;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class TzSystemUsersController extends Controller
{
    public $enableCsrfValidation = false;

    public function init(){
        $post = \Yii::$app->request->post();
        $AUTH_ACCESS_TOKENS = TzSystemUsersService::getAuthAccessTokens();
        if(!in_array($post['access_token'], $AUTH_ACCESS_TOKENS)){
            header('content-type:application/json');
            die(json_encode(['status'=>300, 'msg'=>'您无权限访问', 'data'=>$post, $AUTH_ACCESS_TOKENS], 320));
        }
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
    public static function actionSynBalance(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = \Yii::$app->request->post();
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }

        $rst = BaseService::synBalanceByAccessToken($post['access_token'], $is_auto=2);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '同步积分接口', ['post'=>$post, 'rst'=>$rst]);

        return $rst;
    }


}