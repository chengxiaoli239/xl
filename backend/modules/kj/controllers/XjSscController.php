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
     * @desc 7天 - 新疆时时彩
     * @return json|xml
     */
    public function actionSevenDay($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = XjSsc::getLotteryNoSevenDay($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 99 - 新疆时时彩
     * @return json|xml
     */
    public function actionNineNine($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = XjSsc::getLotteryNo99($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 开奖9号娱乐网 - 新疆时时彩
     * @return json|xml
     */
    public function actionNineNum($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = XjSsc::getLotteryNoNineNum($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 直播网 - 新疆时时彩
     * @return json|xml
     */
    public function actionZhiBoWang($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = XjSsc::getLotteryNoZhiBo($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 福利彩网 - 新疆时时彩
     * @return json|xml
     */
    public function actionFuLiCai($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = XjSsc::fuLiCai($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc CG网 - 新疆时时彩
     * @return json|xml
     */
    public function actionCg($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = XjSsc::cG($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 福利彩网 - 新疆时时彩
     * @return json|xml
     */
    public function actionHuangGuan($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = XjSsc::huangGuan($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 九九网 - 新疆时时彩
     * @return json|xml
     */
    public function actionNineNineNew($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = XjSsc::NineNineNew($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 99 - 新疆时时彩
     * @return json|xml
     */
    public function actionBatchSevenDay($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = XjSsc::batchSevenDay($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 99 - 新疆时时彩
     * @return json|xml
     */
    public function actionBatchZhiBoWang($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = XjSsc::getLotteryNoBatch($type, $post['is_auto']);
        return $data;
    }
}