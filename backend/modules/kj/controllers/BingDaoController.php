<?php
namespace backend\modules\kj\controllers;

use common\kj\cqssc\CqsscKcw;
use common\kj\ssc\BingDao;
use common\kj\ssc\Lucky5;
use Yii;
use yii\web\Controller;
use common\service\CommonService;


class BingDaoController extends Controller
{
    /**
     * @desc 彩种:冰岛90s - 抓官网
     * @return json|xml
     */
    public function actionIndex($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = BingDao::getLotteryOne($type, $l_type=6);
        return $data;
    }

    /**
     * @desc 彩种:冰岛3m - 抓官网
     * @return json|xml
     */
    public function actionIndex3m($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = BingDao::getLotteryOne($type, $l_type=7);
        return $data;
    }

    /**
     * @desc 彩种:冰岛台湾冰果 5m - 抓官网
     * @return json|xml
     */
    public function actionIndex5mTw($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryBg($type, $l_type=8);
        return $data;
    }

    /**
     * @desc 彩种:冰岛5m - 抓官网
     * @return json|xml
     */
    public function actionIndex5m($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = BingDao::getLotteryOne($type, $l_type=8);
        return $data;
    }

    /**
     * @desc 彩种:冰岛5m - 抓官网
     * @return json|xml
     */
    public function actionIndex10m($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = BingDao::getLotteryOne($type, $l_type=9);
        return $data;
    }

    /**
     * @desc 冰岛 - 实时资讯网 https://icelot.20191030pro.com/
     * @param string $type
     * @return array
     */
    public function actionShiXun($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = Lucky5::getLotteryShiXun($type);
        return $data;
    }


}