<?php
namespace backend\modules\kj\controllers;

use common\kj\indexes\DaoQiongSi;
use common\kj\indexes\NaSiDaKe;
use common\kj\ssc\Lucky5;
use Yii;
use yii\web\Controller;
use common\service\CommonService;


/**
 * @desc 指数
 * Class IndexesController
 * @package backend\modules\kj\controllers
 */
class IndexesController extends Controller
{
    /**
     * @desc 幸运五星 - 新疆时时彩
     * @return json|xml
     */
    public function actionBatch($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = Lucky5::batch($type);
        return $data;
    }

    /**
     * @desc 纳斯达克 - 抓网盘 - 在用
     * @return - json|xml
     */
    public function actionNsdk($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = NaSiDaKe::getLotteryNo($type);
        return $data;
    }

    /**
     * @desc 道琼斯 - 抓网盘 - 在用
     * @return - json|xml
     */
    public function actionDqs($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = DaoQiongSi::getLotteryNo($type);
        return $data;
    }

    /**
     * @desc 上证指数 - 抓网盘 - 在用
     * @return - json|xml
     */
    public function actionSzzs($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = NaSiDaKe::getLotteryNo($type, $is_auto=1, $lottery_type=21);
        return $data;
    }

    /**
     * @desc 深圳成指 - 抓网盘 - 在用
     * @return - json|xml
     */
    public function actionSzcz($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = DaoQiongSi::getLotteryNo($type, $is_auto=1, $lottery_type=21);
        return $data;
    }

}