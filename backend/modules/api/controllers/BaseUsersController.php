<?php
/**
 * Created by PhpStorm.
 * User:wangyegao
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\api\controllers;

use backend\models\TzSystemsUsers;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\clients\TzSystemUsersService;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class BaseUsersController extends Controller
{
    public $enableCsrfValidation = false;
    public $access_token = '';
    public $uid = 0;

    public function init(){
        $post = \Yii::$app->request->post();
        $this->uid = self::getUidByAccessToken($post['access_token']);
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
     * @desc 获取uid by access_token
     * @param string $access_token
     * @return int
     */
    public static function getUidByAccessToken($access_token=''){
        $m = \Yii::$app->cache;
        $mkey = 'getUidByAccessToken_'.$access_token;
        $uid = $m->get($mkey);
        if(empty($uid)){
            $ADMIN_ACCESS_TOKEN = BetService::getConfig('ADMIN_ACCESS_TOKEN'); # 管理员token
            if($access_token == $ADMIN_ACCESS_TOKEN){
                $uid = 1;
            }else{
                if($TzSystemsUsers = TzSystemsUsers::findOne(['access_token'=>$access_token])){
                    $uid = $TzSystemsUsers->uid;
                }
            }
            $m->set($mkey, $uid, 60);
        }

        return $uid;
    }



}