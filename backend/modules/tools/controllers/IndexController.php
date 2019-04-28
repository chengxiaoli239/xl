<?php
/**
 * Created by PhpStorm.
 * User:wangyegao
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\tools\controllers;

use Yii;
use yii\web\Controller;
use common\service\CommonService;


class IndexController extends Controller
{
    public function actionOpThreeNum(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $rst = CommonService::opKjThreeNum();
        return ['status'=>200,'data'=>$rst,'msg'=>'操作成功'];
    }

}