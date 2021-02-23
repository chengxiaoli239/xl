<?php
namespace backend\modules\kj\controllers;

use common\kj\cqssc\CqsscKcw;
use Yii;
use yii\web\Controller;


class KuaiLe8Controller extends Controller
{
   /**
     * @desc 北京快乐8 - 会员网
    * @return array|bool 返回格式(数组)：{"expect":"2020100623","opencode":"0,8,6,3,6","opentime":"2020-10-06 17:41:38"}
    */
    public function actionIndex($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryKuaiLe8();
        return $data;
    }


    /**
     * @desc 北京快乐8 - 99彩票网
     * @return array|bool 返回格式(数组)：{"expect":"2020100623","opencode":"0,8,6,3,6","opentime":"2020-10-06 17:41:38"}
     */
    public function actionNineNine($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryKuaiLe8NineNine();
        return $data;
    }

    /**
     * @desc 北京快乐8 - 800网
     * @return array|bool 返回格式(数组)：{"expect":"2020100623","opencode":"0,8,6,3,6","opentime":"2020-10-06 17:41:38"}
     */
    public function actionEight($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryKuaiLe8Eight();
        return $data;
    }

}