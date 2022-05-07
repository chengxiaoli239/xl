<?php

namespace backend\service\sports;

use backend\models\Matchs;
use backend\service\pingbo\tennis\TennisService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class FootBallSportsService extends SportsBaseService  {
    public static $game_type = 33;
    public static $system_id = 14;

    public static function pushFootBallDatas($datas){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        return $rst;
    }
}