<?php
/**
 * Created by PhpStorm.
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\api\controllers;

use backend\service\BaseService;
use backend\service\clients\BettingRecordsService;
use backend\service\clients\TzSystemUsersService;
use backend\service\clients\UserSysPlansService;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class UserSysPlansController extends BaseUsersController
{
    public $enableCsrfValidation = false;

    public function init(){
        $isAdminRoute = in_array(Yii::$app->controller->route, ['admin/route/index', 'admin/route/index.html']);
        parent::init();
        $post = \Yii::$app->request->post();
        $AUTH_ACCESS_TOKENS = TzSystemUsersService::getAuthAccessTokens();
        if(!in_array($post['access_token'], $AUTH_ACCESS_TOKENS) && !$isAdminRoute){
            header('content-type:application/json');
            die(json_encode(['status'=>300, 'msg'=>'您无权限访问', 'data'=>$post], 320));
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


        $rst = UserSysPlansService::getLists($this->uid);

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

        $rst = BaseService::synBalance($post['tz_system_users_id'], $is_auto=2);

        return $rst;
    }


}
