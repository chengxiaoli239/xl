<?php

namespace backend\service;
use backend\models\AgentUsers;
use backend\models\CodeTypes;
use backend\models\Num4Type;
use backend\models\SscKjData;
use backend\models\SystemConfig;
use common\tools\Tool_Common;

class ChatCommonBetService extends BaseService {

    /**
     * @desc 接受用户发送desc
     * @param $token
     * @param $desc
     * @param int $lottery_type
     * @return array
     */
    public static function postDesc($token, $desc, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $types = [1=>'上分', 2=>'下分', 3=>'查询开奖', 4=>'投注'];
        # 判断用户执行业务类型

        $rst = ['status'=>200, 'msg'=>'操作成功'];
        # 1、上分
        if(strpos($desc, '上') === 0){
            $type = 1;
        }

        # 2、下分
        if(strpos($desc, '下') === 0){
            $type = 2;
        }

        # 4、下分
        if(strpos($desc, '奖') === 0){
            $type = 3;
        }

        return $rst;
    }

    /**
     * @param $token
     * @param string $desc
     * @return array|AgentUsers ['status'=>200, 'msg'=>'投注结果描述']
     */
    public static function postBetDesc($token, $desc = '', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = [];
        $flag = 1;


        $info = ChatCommonBetService::getUserInfoByToken($token);
        $rst['userInfo'] = $info['data'];
        $rst['data'] =  [ # 期号、当前期状态
            'qihao' => substr(HN0898Service::getQihao($lottery_type), -3),
        ];


        # 1、记录下发送desc

        # 2、判断用户状态
        if($info['status'] != 200){ # 验证账号状态
            $rst['status'] = 300;
            $rst['msg'] = $info['msg'];
            return $rst;
        }
        $userInfo = $info['data'];

        $rst['status'] = 200;
        $rst['msg'] = '状态正常，等待对接攻击接口';
        $rst['userInfo'] = $userInfo;

        # 3、判断用户余额
        $balance = $userInfo['balance']; # 用户投注前余额
        $needMoney = NumService::getNeedMoneyByDesc($desc);
        if($balance<$needMoney){
            $flag = 0;
            $rst['status'] = 300;
            $rst['msg'] = '需分:'.$needMoney. '，剩鱼:'.$balance;
        }

        # 4、余额足够执行下注
        if($flag){

        }

        # 5、下注成功扣除余额
        //$code_hz = BetService::xx();

        return $rst;
    }

    /**
     * @desc 获取用户信息
     * @param $token
     * @return AgentUsers|array
     */
    public static function getUserInfoByToken($token){
        $AgentUsers = AgentUsers::findOne(['token'=>$token]);
        //$rst = ['status'=>200, 'msg'=>'操作成功', 'params'=>['name'=>$AgentUsers->name, 'avatar'=>$AgentUsers->images]];

        $rst['data'] = $AgentUsers;
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
            $rst['msg'] = '账号余分不足！剩鱼:'.$AgentUsers->balance;
            return $rst;
        }

        $rst['status'] = 200;
        return $rst;
    }

    /**
     * @desc 获取用户信息
     * @param $token
     * @return array
     */
    public static function getUserInfo($token){
        if(empty($token)) return ['status'=>404, 'msg'=>'缺少参数token'];

        if(!$AgentUsers = AgentUsers::findOne(['token'=>$token])){
            return ['status'=>401, 'msg'=>'未找到用户信息'];
        }

        //$rst = ['status'=>200, 'msg'=>'操作成功', 'data'=>$AgentUsers->attributes];
        $rst = ['status'=>200, 'msg'=>'操作成功', 'data'=>['name'=>$AgentUsers->name, 'avatar'=>$AgentUsers->images]];

        return $rst;
    }

    public static function getLotteryTypeByToken($token){
        $lottery_type = 8;

        $UserInfo = AgentUsers::findOne(['token'=>$token]);
        p($UserInfo);
    }


}