<?php
namespace common\service\index;

use backend\models\TzSystemsUsers;
use backend\service\BaseService;

class  CrontabIndexService{

    public static function autoLogin(){
        $query = TzSystemsUsers::find()->alias('u')
            ->leftJoin('{{%tz_systems}} s', 'u.tz_system_id=s.id')
            ->where(['AND',['=', 'u.status', 1], ['=', 'u.is_auto_login', 1], ['<>', 'u.ssc_domain', ''], ['=', 's.status', 1]]);
        $sql = $query->createCommand()->getRawSql();
        $TzSystemsUsers = $query->asArray()->all();

        foreach ($TzSystemsUsers as $TzSystemsUser){
            $tz_system_id = $TzSystemsUser['tz_system_id'];
            $now_time = date('H:i');
            $close_times = [16=>['02:00', '06:50']];
            if(isset($close_times[$tz_system_id]) && $now_time>$close_times[$tz_system_id][0] && $now_time<$close_times[$tz_system_id][1]){
                $r = ['status'=>300, 'msg'=>'关盘时间'];
            }else{
                $r = BaseService::login($TzSystemsUser['id']);
            }
            $rst[$TzSystemsUser['id']] = $r;
        }

        return $rst;
    }

}