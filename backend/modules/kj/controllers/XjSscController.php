<?php
namespace backend\modules\kj\controllers;

use common\kj\cqssc\Cqssc360;
use common\kj\cqssc\CqsscKcw;
use common\kj\cqssc\CqsscSevenDay;
use common\kj\xjssc\XjSsc;
use Yii;
use yii\web\Controller;
use common\service\CommonService;


class XjSscController extends Controller
{
    /**
     * @desc 7天
     * @return json|xml
     */
    public function actionSevenDay($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = XjSsc::getLotteryNo($type);
        return $data;
    }

    /**
     * @desc 99
     * @return json|xml
     */
    public function actionNineNine($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = XjSsc::getLottery99($type);
        return $data;
    }

}