<?php

namespace backend\service\sports;

use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class EventsLiveDatasService extends SportsBaseService  {

    public static function getRelatedDatas(){

        return [];
    }

    public static function getSportTypes(){

        return [
            1 => ['sport_type'=>1, 'name'=>'足球'],
            2 => ['sport_type'=>2, 'name' => '网球'],
            3 => ['sport_type'=>3, 'name' => '篮球'],
        ];
    }
}