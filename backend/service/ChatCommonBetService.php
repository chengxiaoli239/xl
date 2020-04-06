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
     * @desc 获取用户信息
     * @param $token
     * @return AgentUsers|array
     */
    public static function getUserInfoByToken($token){
        $AgentUsers = AgentUsers::findOne(['token'=>$token]);
        $rst = ['status'=>200, 'msg'=>'操作成功', 'params'=>['name'=>$AgentUsers->name, 'avatar'=>$AgentUsers->images]];

        if($AgentUsers->status == 0){
            $rst['status'] = 301;
            $rst['msg'] = '账号未激活';
            return $rst;
        }
        if($AgentUsers->status == 2){
            $rst['status'] = 302;
            $rst['msg'] = '账号被冻结';
            return $rst;
        }

        if($AgentUsers->balance <= 0){
            $rst['status'] = 303;
            $rst['msg'] = '账号余分不足！';
            return $rst;
        }

        return $AgentUsers;
    }
}