<?php
namespace backend\modules\kj\controllers;

use common\kj\cqssc\CqsscKcw;
use Yii;
use yii\web\Controller;


class KuaiLe8Controller extends Controller
{
   /**
     * @desc 希腊5分彩
     * @return array
     */
    public function actionIndex($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryKuaiLe8();
        return $data;
    }

}