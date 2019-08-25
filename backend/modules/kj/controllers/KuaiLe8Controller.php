<?php
namespace backend\modules\kj\controllers;

use common\kj\cqssc\CqsscKcw;
use Yii;
use yii\web\Controller;


class KuaiLe8Controller extends Controller
{
   /**
     * @desc 北京快乐8 - 会员网
     * @return array
     */
    public function actionIndex($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryKuaiLe8();
        return $data;
    }


    /**
     * @desc 北京快乐8 - 99彩票网
     * @return array
     */
    public function actionSevenDay($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryKuaiLe8SevenDay();
        return $data;
    }


}