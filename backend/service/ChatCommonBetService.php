<?php

namespace backend\service;
use backend\models\AgentUsers;
use backend\models\CodeTypes;
use backend\models\Num4Type;
use backend\models\SscKjData;
use backend\models\SystemConfig;
use common\tools\Tool_Common;

class ChatCommonBetService extends BaseService {


    public static function betByDesc($token, $desc = ''){

        $rst = ChatCommonBetService::getUserInfoByToken($token);
        if(!$rst['status'] != 200){
            //return ['status'=>404, 'msg'=>'非法用户', 'data'=>['avatar'=>'static/images/avatar/f1/f_xxx.jpg']];
            return $rst;
        }

        //$code_hz = BetService::xx();

    }

    /**
     * @desc
     * @param $token
     * @return AgentUsers|array
     */
    public static function getUserInfoByToken($token){
        $AgentUsers = AgentUsers::findOne(['token'=>$token]);
        p($AgentUsers);
        if($AgentUsers->status == 0){
            return ['status'=>301, 'msg'=>'账号未激活'];
        }
        if($AgentUsers->status == 2){
            return ['status'=>302, 'msg'=>'账号被冻结'];
        }

        if($AgentUsers->balance<=0){
            return ['status'=>303, 'msg'=>'账号余分不足！'];
        }

        return $AgentUsers;
    }
}