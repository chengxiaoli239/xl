<?php
/**
 * Created by PhpStorm.
 * User:wangyegao
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\api\controllers;

use backend\service\clients\TzSystemUsersService;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class TzSystemUsersController extends Controller
{
    public $enableCsrfValidation = false;

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

        $rst = TzSystemUsersService::getTzSystemUserLists($post);

        return $rst;
    }



}