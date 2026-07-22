<?php
namespace common\service\index;

use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use backend\service\BaseService;
use common\service\CommonService;
use common\service\chat\Tool_Common;

class  CrontabIndexService{

    const LOGIN_MODE_NORMAL = 'normal';
    const LOGIN_MODE_SKIP_CLOSED = 'skip_closed';
    const LOGIN_MODE_FORCE_MORNING = 'force_morning';
    const MORNING_RELOGIN_SYSTEM_IDS = [7, 9, 10];

    public static function resolveLoginMode(int $tzSystemId, string $nowTime=''): string
    {
        if(!in_array($tzSystemId, self::MORNING_RELOGIN_SYSTEM_IDS, true)){
            return self::LOGIN_MODE_NORMAL;
        }

        $nowTime = $nowTime ?: date('H:i');
        if($nowTime >= '05:00' && $nowTime < '07:45'){
            return self::LOGIN_MODE_SKIP_CLOSED;
        }
        if($nowTime >= '07:45' && $nowTime < '08:00'){
            return self::LOGIN_MODE_FORCE_MORNING;
        }

        return self::LOGIN_MODE_NORMAL;
    }

    public static function autoLogin(): array
    {
        $query = TzSystemsUsers::find()->alias('u')
            ->leftJoin('{{%tz_systems}} s', 'u.tz_system_id=s.id')
            ->innerJoin('{{%user_sys_plans}} p', 'p.uid=u.uid AND FIND_IN_SET(u.tz_system_id, p.tz_sites)')
            ->where([
                'AND',
                ['=', 'u.status', 1],
                ['=', 'u.is_auto_login', 1],
                ['<>', 'u.ssc_domain', ''],
                ['=', 's.status', 1],
                ['IN', 'u.is_local_bet', [BetsBackend::BET_TYPE_SERVER_API, BetsBackend::BET_TYPE_LOCAL_API]],
                ['=', 'p.status', 1],
                ['=', 'p.is_test', 0],
                ['=', 'p.is_batch_simulate', 0],
            ]);
        $query->groupBy('u.id');
        //$sql = $query->createCommand()->getRawSql();p($sql);
        $TzSystemsUsers = $query->asArray()->all();
        if(empty($TzSystemsUsers)) return ['status'=>200, 'msg'=>'为空'];

        foreach ($TzSystemsUsers as $TzSystemsUser){
            try {
                $tz_system_id = $TzSystemsUser['tz_system_id'];
                $now_time = date('H:i');
                $close_times = [
                    16=> ['02:00', '06:50'], # 16:tz_system_id
                    #17=> ['00:00', '07:00'], # 17:tz_system_id
                    #18=> ['00:00', '07:00'], # 17:tz_system_id
                ];
                $loginMode = self::resolveLoginMode((int)$tz_system_id, $now_time);
                if($loginMode === self::LOGIN_MODE_SKIP_CLOSED){
                    $r = ['status'=>200, 'msg'=>'晨间关盘，等待07:45重新登录'];
                }elseif($loginMode === self::LOGIN_MODE_FORCE_MORNING){
                    $hasActivePlan = CommonService::hasRealPlansActiveSys($tz_system_id, $TzSystemsUser['uid']);
                    $successKey = 'morning_auto_login_success_'.date('Ymd').'_'.$TzSystemsUser['id'];
                    $attemptKey = 'morning_auto_login_attempt_'.$TzSystemsUser['id'];
                    if(!$hasActivePlan){
                        $r = ['status'=>200, 'msg'=>'没有启用的真实计划，跳过晨间登录'];
                    }elseif(\Yii::$app->cache->get($successKey)){
                        $r = ['status'=>200, 'msg'=>'今日晨间登录已完成'];
                    }elseif(!\Yii::$app->cache->add($attemptKey, 1, 50)){
                        $r = ['status'=>200, 'msg'=>'晨间登录处理中'];
                    }else{
                        $r = BaseService::login($TzSystemsUser['id'], 2);
                        if((int)($r['status'] ?? 0) === 200){
                            \Yii::$app->cache->set($successKey, 1, 3 * 3600);
                        }
                    }
                }elseif(isset($close_times[$tz_system_id]) && $now_time>$close_times[$tz_system_id][0] && $now_time<$close_times[$tz_system_id][1]){
                    $r = ['status'=>300, 'msg'=>'关盘时间:'.$now_time, 'data'=>$close_times[$tz_system_id]];
                }else{
                    $r = BaseService::login($TzSystemsUser['id']);
                }
                var_dump($TzSystemsUser['username'].' '.$TzSystemsUser['account'].' '.($r['msg'] ?? '') . ' ' . date('Y-m-d H:i:s'));
                $rst[$TzSystemsUser['id']] = ['tz_system_id'=>$tz_system_id, 'login_mode'=>$loginMode, 'status'=>$r['status'] ?? 0];
            }catch (\Exception $e){
                $rst[$TzSystemsUser['id']] = ['tz_system_id'=>$tz_system_id, 'err_msg'=>$e->getMessage()];
                Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '登陆信息', ['rst'=>$rst]);
            }
        }

        return $rst??[];
    }

}
