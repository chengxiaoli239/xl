<?php
namespace common\service\jobs\plan;

use backend\models\BettingRecords;
use backend\models\TzSystemsUsers;
use backend\service\Lucky5\Lucky5Service;
use common\service\jobs\CommonJob;

class UserBetSnIdJob extends CommonJob {

    public static function getName($params) {
        self::$name = '获取注单编号';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $tzSystemId = $params['tz_system_id'];
        $lottery_type = $params['lottery_type'];
        $qiHao = $params['qihao'];
        $TzSystemUser = TzSystemsUsers::findOne($tzSystemId);
        $snId = Lucky5Service::getSnId($TzSystemUser);
        try {
            $where = [
                'qihao'=>$qiHao,
                'lottery_type'=>$lottery_type,
                'is_simulate'=>1,
                'sn' => \backend\service\BetService::$test_true_sn,
                'snid' => \backend\service\BetService::$test_true_snid,
            ];
            $Bettings = BettingRecords::find()->where($where)->all();
            foreach ($Bettings as $betting){
            }
            //BettingRecords::updateAll(['snid'=>$snId], );
        }catch (\Exception $e){

        }

        return ['tz_system_id'=>$tzSystemId, 'user_id'=>$TzSystemUser->uid, 'snId'=>$snId];
    }

}