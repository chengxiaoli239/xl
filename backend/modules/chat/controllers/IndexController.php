<?php
namespace backend\modules\chat\controllers;

use common\service\ChatService;
use Yii;
use yii\web\Controller;
use common\service\CommonService;


class IndexController extends Controller
{
	public function actionIndex($id=''){
	    $data = [];
        return $this->render('chat', $data);
    }
    public function actionBind($id=''){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = ['status'=>200, 'uid'=>$id, 'msg'=>'登录成功'];

        return $data;
    }

    public static function send(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $data = ['status'=>200, 'uid'=>'xxx', 'msg'=>'登录成功'];
        $data['rst'] = ChatService::send();
        return $data;
    }
}
