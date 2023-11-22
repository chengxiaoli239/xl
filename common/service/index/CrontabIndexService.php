<?php
namespace common\service\index;

use backend\models\TzSystemsUsers;
use backend\service\BaseService;
use common\service\chat\Tool_Common;

class  CrontabIndexService{

    public static function autoLogin(): array
    {
        $query = TzSystemsUsers::find()->alias('u')
            ->leftJoin('{{%tz_systems}} s', 'u.tz_system_id=s.id')
            ->where(['AND',['=', 'u.status', 1], ['=', 'u.is_auto_login', 1], ['<>', 'u.ssc_domain', ''], ['=', 's.status', 1]]);
        $sql = $query->createCommand()->getRawSql();
        $TzSystemsUsers = $query->asArray()->all();

        foreach ($TzSystemsUsers as $TzSystemsUser){
            try {
                $tz_system_id = $TzSystemsUser['tz_system_id'];
                $now_time = date('H:i');
                $close_times = [
                    16=> ['02:00', '06:50'], # 16:tz_system_id
                    #17=> ['00:00', '07:00'], # 17:tz_system_id
                    #18=> ['00:00', '07:00'], # 17:tz_system_id
                ];
                if(isset($close_times[$tz_system_id]) && $now_time>$close_times[$tz_system_id][0] && $now_time<$close_times[$tz_system_id][1]){
                    $r = ['status'=>300, 'msg'=>'关盘时间:'.$now_time, 'data'=>$close_times[$tz_system_id]];
                }else{
                    $r = BaseService::login($TzSystemsUser['id']);
                }
                var_dump($r);
                $rst[$TzSystemsUser['id']] = ['tz_system_id'=>$tz_system_id, 'r'=>$r];
            }catch (\Exception $e){
                $rst[$TzSystemsUser['id']] = ['tz_system_id'=>$tz_system_id, 'err_msg'=>$e->getMessage()];
                Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '登陆信息', ['rst'=>$rst]);
            }
        }

        return $rst;
    }

}
