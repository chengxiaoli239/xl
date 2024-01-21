<?php
namespace backend\modules\kj\controllers;

use common\kj\qxc\Qxc360;
use common\kj\qxc\QxcKcw;
use common\kj\qxc\QxcTcw;
use Yii;
use yii\web\Controller;


class QxcController extends Controller
{
    /**
     * @desc 开彩网
     * @return array
     */
    public function actionKcw($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = QxcKcw::getLotteryNo($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 360
     * @return json|xml
     */
    public function action360($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = Qxc360::getLotteryNo($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 官网
     * @param string $type
     * @return array|bool
     */
    public function actionTcw($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = QxcTcw::getLotteryNo($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 官网
     * @param string $type
     * @return array|bool
     */
    public function actionTcwBatch($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = QxcTcw::getBatchLotteryNo($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 官网 https://www.lottery.gov.cn/kj/kjlb.html?qxc
     * @param string $type
     * @return array|bool
     */
    public function actionPl5Batch($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = QxcTcw::QixingCaiBatch($is_new=0, $lottery_type=17);
        return $data;
    }

    /**
     * @desc 官网 https://www.lottery.gov.cn/kj/kjlb.html?qxc
     * @param string $type
     * @return array|bool
     */
    public function actionQxcBatch($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $data = QxcTcw::QixingCaiBatch();
        return $data;
    }

    /**
     * @desc 官网  https://www.lottery.gov.cn/kj/kjlb.html?qxc
     * @param string $type
     * @return array|bool
     */
    public function actionTcwOne($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = QxcTcw::getTcwOne($type, $post['is_auto']);
        return $data;
    }

    /**
     * @desc 官网  https://www.lottery.gov.cn/kj/kjlb.html?qxc
     * @param string $type
     * @return array|bool
     */
    public function actionPl5One($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = QxcTcw::getTcwOne($type, $post['is_auto'], $lottery_type=17);
        return $data;
    }

    /**
     * @desc 官网 七星彩  https://99065x.com/
     * @param string $type
     * @return array|bool
     */
    public function actionNineNineQxc($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = QxcTcw::getNineNineLottery($type, $post['is_auto'], $lottery_type=1);
        return $data;
    }

    /**
     * @desc 官网 排列五  https://99065x.com/
     * @param string $type
     * @return array|bool
     */
    public function actionNineNinePlw($type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = QxcTcw::getNineNineLottery($type, $post['is_auto'], $lottery_type=17);
        return $data;
    }

    /**
     * @desc 官网 福彩3D  https://99065jjj.com/
     * @param string $type
     * @return array|bool
     */
    public function actionFcSd(string $type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = QxcTcw::getNineNineLottery($type, $post['is_auto'], $lottery_type=26);
        return $data;
    }

    /**
     * @desc 官网 排列3  https://99065jjj.com/
     * @param string $type
     * @return array|bool
     */
    public function actionPl3(string $type = 'json'){
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();
        $data = QxcTcw::getNineNineLottery($type, $post['is_auto'], $lottery_type=27);
        return $data;
    }

    /**
     * 官方获取号码
     * @param string $type
     * @return array|bool
     */
    public function actionPl3Official(string $type = 'json')
    {
        ($type == 'json' OR !$type) && (\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON);
        $post = \Yii::$app->request->post();

        $data = QxcTcw::getOfficialCode($type, $post['is_auto']??1, $lottery_type=27);

        return $data;
    }
}
