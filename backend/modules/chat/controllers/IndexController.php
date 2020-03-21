<?php
namespace backend\modules\chat\controllers;

use Yii;
use yii\web\Controller;
use common\service\CommonService;


class IndexController extends Controller
{
	public function actionIndex(){
	    $data = [];
        return $this->render('chat', $data);
    }
}
