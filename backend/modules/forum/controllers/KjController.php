<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/8
 * Time: 22:00
 */

namespace backend\modules\forum\controllers;
use common\service\CommonService;
use Yii;
use backend\controllers\BaseController;

class KjController extends BaseController
{
    /**
     * @decription Yii 控制器初始化方法
     */
    public static function _init()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

    }

    public function actionDo(){
        self::_init();
        $rst =CommonService::Kj();

        return $rst;
    }

}
