<?php
namespace backend\modules\kj\controllers;

use common\kj\cqssc\Cqssc360;
use common\kj\cqssc\CqsscKcw;
use common\kj\cqssc\CqsscSevenDay;
use common\kj\xjssc\XjSsc;
use Yii;
use yii\web\Controller;
use common\service\CommonService;


class TaiwanBinguoController extends Controller
{
    /**
     * @desc 开800 - 台湾宾果
     * @return json|xml
     */
    public function actionKai800($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = CqsscKcw::getLotteryTaiwanBinguo($type);
        return $data;
    }


}