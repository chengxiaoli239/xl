<?php

namespace backend\service\sports;

use backend\models\sports\SportsPlatesGames;
use backend\models\sports\SportsRelated;
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

    /**
     * @param array $data
     * @return array
     */
    public static function actGameRelated($data=[], $uid=''){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        $rst['data'] = $data;
        $setDatas = [];
        $where = ['uid'=>$uid, 'relate_A_game_id'=>$data['event_id'], 'relate_B_game_id'=>$data['plates_games_event_id']]; # A比分网B盘口
        $SportsRelated = SportsRelated::find()->where($where)->one();
        $now_time = time();
        if(empty($SportsRelated)){
            $SportsRelated = new SportsRelated();
            $setDatas = [
                'uid' => $uid,
                'relate_A_game_id' => $data['event_id'],
                'relate_B_game_id' => $data['plates_games_event_id'],
                'created_at' => $now_time,
            ];
        }

        $plate = SportsPlatesGames::findOne(['event_id'=>$data['plates_games_event_id']]);

        $setDatas = array_merge($setDatas, [
            'update_time' => $now_time,
            'base_url_A' => $plate->bet_url,
            'updated_at' => time(),
            'base_url_B' => $plate->bet_url,
        ]);
        $SportsRelated->setAttributes($setDatas);
        if(!$SportsRelated->save()){
            $rst = ['status'=>300, 'msg'=>$SportsRelated->getErrors()];
        }

        return $rst;
    }
}