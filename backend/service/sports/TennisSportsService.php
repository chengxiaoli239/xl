<?php

namespace backend\service\sports;

use backend\models\Matchs;
use backend\service\pingbo\tennis\TennisService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class TennisSportsService extends SportsBaseService  {
    public static $game_type = 33;

    public static function grabTennisSportsGame(){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        $rstDatas = TennisService::getGames();
        $datas = [];
        if($rstDatas['status'] == 200){
            $datas = $rstDatas['data'];
        }
        if(empty($datas)){
            return $rst;
        }
        $time = time();
        $allDatas = [];
        foreach ($datas as $data){
            $setDatas = [];
            $where = ['game_type'=>$data['game_type'], 'game_id'=>$data['game_id']];
            if(!$Matchs = Matchs::findOne($where)){
                $Matchs = new Matchs();
                $setDatas = [
                    'created_at' => $time,
                ];
            }
            $setDatas = array_merge($data, $setDatas);
            $allDatas[] = $setDatas;
            $Matchs->setAttributes($setDatas);
            if(!$Matchs->save()){
                p($Matchs->getErrors());
            }
        }
        $rst['datas'] = $allDatas;

        return $rst;
    }
}