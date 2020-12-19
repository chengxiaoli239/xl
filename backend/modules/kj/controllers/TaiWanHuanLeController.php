<?php
namespace backend\modules\kj\controllers;

use common\kj\cqssc\Cqssc360;
use common\kj\cqssc\CqsscKcw;
use common\kj\cqssc\CqsscSevenDay;
use common\kj\ssc\TaiWanHuanLe;
use common\kj\xjssc\XjSsc;
use Yii;
use yii\web\Controller;
use common\service\CommonService;


class TaiWanHuanLeController extends Controller
{
    /**
     * @desc 台湾欢乐
     * @return json|xml
     */
    public function actionIndex($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = TaiWanHuanLe::getLotteryHl($type, $post['is_auto']);
        return $data;
    }


}