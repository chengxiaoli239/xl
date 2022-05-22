<?php

namespace backend\service\sports;

use backend\models\Matchs;
use backend\models\sports\SportsPlatesGames;
use backend\service\pingbo\tennis\TennisService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class FootBallSportsService extends SportsBaseService  {
    public static $game_type = 33;
    public static $system_id = 14;

    public static function pushFootBallDatas($datas){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        $plate_datas = $datas['plate_datas'];
        $p_data = $plate_datas['data'];
        $setDatas = [
            'plate_id' => (string)$plate_datas['plate_id'],
            'event_id' => $p_data['event_id'], # 项目id
            'game_schedule' => $plate_datas['game_schedule'], # 比赛进度
            'is_has_jq' => $plate_datas['is_has_jq'], # 是否角球
            'score' => $plate_datas['score'], # 比分
            'league_matches_id' => $p_data['league_matches_id'], # 联赛id
            'league_matches_name' => $p_data['league_matches_name'], # 联赛名称
            'name1' => $p_data['name1'], # 队员1
            'name1_path' => $p_data['name1_p'], # 队员1元素定位
            'name2' => $p_data['name2'], # 队员2
            'name2_path' => $p_data['name2_p'], # 队员2元素定位
            'bet_url_path' => $p_data['bet_url_p'], # 下注元素定位
            'bet_url' => $p_data['bet_url'], # 下注链接
        ];
        $now_time = time();
        Tool_Common::log('/sports/'.__FUNCTION__, 'INFO', '数据', ['setDatas'=>$setDatas]);
        $where = ['event_id'=>$p_data['event_id'], 'league_matches_id'=>$p_data['league_matches_id']];
        $where = ['event_id'=>$p_data['event_id']];
        $SportsPlatesGames = SportsPlatesGames::findOne($where);
        if(empty($SportsPlatesGames)){
            $SportsPlatesGames = new SportsPlatesGames();
            $setDatas = array_merge($setDatas, [
                'created_at' => $now_time,
            ]);
        }
        $setDatas = array_merge($setDatas, [
            'updated_at' => $now_time,
        ]);
        $SportsPlatesGames->setAttributes($setDatas);
        if(!$SportsPlatesGames->save()){
            return ['status'=>300, 'msg'=>$SportsPlatesGames->getErrors(), 'attr'=>$SportsPlatesGames->getAttributes()];
        }

        return $rst;
    }
}