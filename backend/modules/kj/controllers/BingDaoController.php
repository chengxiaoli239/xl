<?php
namespace backend\modules\kj\controllers;

use common\kj\ssc\BingDao;
use common\kj\ssc\Lucky5;
use Yii;
use yii\web\Controller;
use common\service\CommonService;


class BingDaoController extends Controller
{

    /**
     * @desc 冰岛90 - 抓官网 - 在用
     * @return json|xml
     */
    public function actionIndex($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = BingDao::getLotteryOne($type);
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