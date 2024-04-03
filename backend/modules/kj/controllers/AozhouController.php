<?php
namespace backend\modules\kj\controllers;

use common\kj\ssc\Aozhou;
use Yii;
use yii\web\Controller;


class AozhouController extends Controller
{
    /**
     * @desc 澳洲幸运五星 - 批量
     * @return json|xml
     */
    public function actionBatchLucky5($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = Aozhou::batch($type);
        return $data;
    }

    /**
     * @desc 幸运五星 - 抓网盘 - 在用
     * @return json|xml
     */
    public function actionIndex($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = Aozhou::getSiteLucky5($type);
        return $data;
    }

    /**
     * @desc 澳洲幸运五星彩 - 实时资讯网 https://1680632.com
     * @param string $type
     * @return array
     */
    public function actionLucky5($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = Aozhou::getLucky5($type, $post['is_auto']??1);
        return $data;
    }

    /**
     * @desc 澳洲幸运五星彩 - 实时资讯网 https://1680632.com
     * @param string $type
     * @return array
     */
    public function actionLucky5Out(string $type = 'json')
    {
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = Aozhou::getLucky5Out($type, $post['is_auto']??1, $isOut=1);
        return $data;
    }


}