<?php
namespace backend\modules\kj\controllers;

use common\kj\cqssc\Cqssc360;
use common\kj\cqssc\CqsscKcw;
use common\kj\cqssc\CqsscSevenDay;
use Yii;
use yii\web\Controller;
use common\service\CommonService;


class CqsscController extends Controller
{
    /**
     * @desc 开彩网
     * @return array
     */
    public function actionKcw($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryNo($type);
        return $data;
    }

    /**
     * @desc 360
     * @return json|xml
     */
    public function action360($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = Cqssc360::getLotteryNo($type);
        return $data;
    }

    /**
     * @desc 开奖直播网
     * @return json|xml
     */
    public function actionZhiBoWang($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryNoZhiBo($type);
        return $data;
    }

    /**
     * @desc 1998彩票集团网：https://www.20041998.com/
     * @return json|xml
     */
    public function actionOneNineNineEight($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryNoOneNineNineEight($type);
        return $data;
    }

    /**
     * @desc 7天
     * @return json|xml
     */
    public function actionSevenDay($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscSevenDay::getLotteryNo($type);
        return $data;
    }

    /**
     * @desc 99
     * @return json|xml
     */
    public function actionNineNine($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscSevenDay::getLottery99($type);
        return $data;
    }

    /**
     * @desc 希腊1.5分彩
     * @return array
     */
    public function actionXlOneFive($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryNoXl(1);
        return $data;
    }

    /**
     * @desc 希腊3分彩
     * @return array
     */
    public function actionXlThree($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryNoXl(2);
        return $data;
    }

    /**
     * @desc 希腊5分彩
     * @return array
     */
    public function actionXlFive($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryNoXl(3);
        return $data;
    }

    /**
     * @desc 希腊10分彩
     * @return array
     */
    public function actionXlTen($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryNoXl(4);
        return $data;
    }
}