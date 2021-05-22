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
            $rst[$TzSystemsUser->id] = BaseService::login($TzSystemsUser->id);
        }

        return $rst;
    }

}