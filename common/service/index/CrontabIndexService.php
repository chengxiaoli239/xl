<?php
namespace common\service\index;

use backend\models\TzSystemsUsers;
use backend\service\BaseService;

class  CrontabIndexService{

    public static function autoLogin(){
        $TzSystemsUsers = TzSystemsUsers::find()->alias('u')
            ->leftJoin('{{%tz_systems}} s', 'u.tz_system_id=s.id')
            ->where(['AND',['=', 'u.status', 1], ['=', 'u.is_auto_login', 1], ['<>', 'u.ssc_domain', ''], ['=', 's.status', 1]])->all();

        foreach ($TzSystemsUsers as $TzSystemsUser){
            $tz_system_id = $TzSystemsUser->tz_system_id;
            $now_time = date('H:i');
            $clock_times = [16=>'02:00'];
            if(isset($clock_times[$tz_system_id]) && $now_time>$clock_times[$tz_system_id]){
                $r = ['status'=>300, 'msg'=>'关盘时间'];
            }else{
                $r = BaseService::login($TzSystemsUser->id);
            }
            $rst[$TzSystemsUser->id] = $r;
        }

        return $rst;
    }

}