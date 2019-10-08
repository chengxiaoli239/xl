<?php
namespace backend\modules\kj\controllers;

use common\kj\ssc\Lucky5;
use Yii;
use yii\web\Controller;
use common\service\CommonService;


class XjSscController extends Controller
{
    /**
     * @desc 幸运五星 - 新疆时时彩
     * @return json|xml
     */
    public function actionIndex($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = Lucky5::getLotteryNo($type);
        return $data;
    }
}