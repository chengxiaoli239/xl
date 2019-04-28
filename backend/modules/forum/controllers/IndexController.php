<?php
/**
 * Created by PhpStorm.
 * User:wangyegao
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\forum\controllers;

use Yii;
use backend\models\User;
use backend\models\UserFollowData;
use backend\service\OpKjService;
use common\tools\Tool_Common;
use backend\service\HN0898Service;
use backend\service\BaseNumService;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

use backend\controllers\BaseController;


class IndexController extends BaseController
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
     * 登录获取页面信息
     */
    public function actionGetRemoteData(){
        //$cookie = \Yii::$app->request->get('cookie');
        $cookie = User::findOne(['account'=>'gaozi2017'])['cookie'];
        $html_data = HN0898Service::getRemoteHtmlContent($cookie); // 1、登录： cookie 传值  2、未登录 为空
        p($html_data);
    }

    public function actionTz(){
        p('xxxxxxxxxx');

    }

    public function actionQihao()
    {
        $qihao = HN0898Service::getQihao();
        p($qihao);
    }

    /**
     * @decription 同步数据库
     */
    public function actionSynSql(){
        $sql_file = Yii::$app->params['sql_file'];
        $sql_content = file_get_contents($sql_file);
        $sql_arr = explode(';', $sql_content);

        print_r($sql_arr);
    }

}