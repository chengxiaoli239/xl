<?php

namespace backend\service;
use backend\models\AgentRecordUsersDesc;
use backend\models\AgentUsers;
use backend\models\AgentUsersBalanceFlows;
use backend\models\CodeTypes;
use backend\models\Num4Type;
use backend\models\SscKjData;
use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use common\tools\Tool_Common;

class ChatCommonBetService extends BaseService {
    public static $types = [1=>'上分', 2=>'下分', 3=>'开奖查询', 4=>'投注'];

    /**
     * @desc 接受用户发送desc
     * @param $token
     * @param $desc
     * @param int $lottery_type
     * @return array
     */
    public static function postDesc($token, $desc, $lottery_type = DEFAULT_LOTTERY_TYPE){

        $info = ChatCommonBetService::getUserInfoByToken($token);
        $userInfo = $info['data'];
        //$rst['userInfo'] = $userInfo;
        $types = [1=>'上分', 2=>'下分', 3=>'查询开奖', 4=>'投注'];
        $type = ChatCommonBetService::getTypeByDesc($desc);
        $qihao = HN0898Service::getQihao($lottery_type);
        $rst = [
            'type' => $type,
            'qihao' => $qihao,
            'userInfo' => $userInfo,
        ];

        $rst['data'] =  [ # 期号、当前期状态
            'qihao' => substr($qihao, -3),
        ];

        $setData = [
            'agent_id' => $userInfo->agent_id,
            'member_id'=>$userInfo->id,
        ];

        if(in_array($type, [1, 2])){ # 1、上、下分
            $rst = array_merge(ChatCommonBetService::upOrDownBalance($desc, $userInfo), $rst);
            return $rst;
        }elseif ($type == 3){   # 查询开奖
            $SscKjData = SscKjData::find()->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->one();
            $rst = array_merge(['msg'=>'【号码】'.$SscKjData->code_str], $rst);
            return $rst;

        }else{  # 4、投注
            $TzSystemsUsers = TzSystemsUsers::findOne(['is_agent'=>1, 'uid'=>$userInfo->agent_id, 'status'=>1]);
            $setData = [
                'agent_id' => $userInfo->agent_id,
                'type' => $type,
                'desc' => $desc,
                'token' =>$token,
                'status' => 0,
                'tz_system_id' => $TzSystemsUsers->tz_system_id,
                'created_at' => time(),
                'updated_at' => time(),
            ];
        }

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

        $rst['data'] = $AgentUsers->attributes;
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

    /**
     * @desc 获取业务类型 $types = [1=>'上分', 2=>'下分', 3=>'查询开奖', 4=>'投注'];
     * @param $desc
     * @return int
     */
    public static function getTypeByDesc($desc){
        # 判断用户执行业务类型
        $type = 4; # 默认

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

        return $type;
    }

    /**
     * @desc 上下分
     * @param $desc
     * @param array $userInfo
     * @return array
     */
    public static function upOrDownBalance($desc, $userInfo = []){
        $rst = ['status'=>200, 'msg'=>'申请成功，等待审核'];

        if(preg_match('/^上\d+$/',$desc,$Arr)){
            $type = 1;
            $type_desc = '上';
        }elseif (preg_match('/^下\d+$/',$desc,$Arr)){
            $type = 2;
            $type_desc = '下';
        }
        $agent_id = $userInfo['agent_id'];
        $member_id = (string)$userInfo['id'];

        if($AgentUsersBalanceFlows = AgentUsersBalanceFlows::findOne(['agent_id'=>$agent_id, 'member_id'=>$member_id, 'status'=>0])){
            return ['status'=>300, 'msg'=>'有未审核记录，请联系矿主处理'];
        }

        $balance = str_replace($type_desc, '', $Arr[0]);

        $setData = [
            'agent_id' => $agent_id,
            'member_id' => $member_id,
            'member_account' => $userInfo['name'],
            'type' => $type, # 1上分2下分
            'balance' => $balance, # 上/下 积分，变动
            'balance_now' => $userInfo['balance'], # 当前积分
            'desc' => '用户申请'.$type_desc.' '.$balance,
            'status' => 0,
            'created_at' =>time(),
            'updated_at' =>time(),
        ];

        $AgentUsersBalanceFlows = new AgentUsersBalanceFlows();
        $AgentUsersBalanceFlows->setAttributes($setData);
        if(!$flag = $AgentUsersBalanceFlows->save()){
            $msg = current($AgentUsersBalanceFlows->getErrors());
            Tool_Common::log('upOrDownBalance', 'ERR', '用户上下分', ['Arr'=>$Arr, 'msg'=>$msg, 'attributes'=>$AgentUsersBalanceFlows->attributes]);
            return ['status'=>300, 'msg'=>$type_desc.'失败'.$msg];
        }
        Tool_Common::log('upOrDownBalance', 'INFO', '用户上下分', ['desc'=>$desc, 'userInfo'=>$userInfo, 'attributes'=>$AgentUsersBalanceFlows->attributes]);
        $rst['msg'] = $rst['msg'].','.$type_desc.$balance;
        $rst['userInfo'] = $userInfo;

        return $rst;
    }

    /**
     * @desc 记录用发送消息
     * @param $post
     * @param $rst
     * @return array
     */
    public static function recordPostDesc($post, $postRst){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        $token = $post['params']['token'];
        $desc = $post['params']['tz_txt'];
        $type = $postRst['type'];

        $setData = [
            'agent_id' => $postRst['userInfo']['agent_id'],
            'member_id' => $postRst['userInfo']['id'],
            'member_account' => $postRst['userInfo']['name'],
            'qihao' => $postRst['qihao'],
            'desc' => $desc,
            'type' => $type,
            'token' => $token,
            'user_info' => json_encode($postRst['userInfo'], 320),
            'status' => $postRst['status'],
            'return' => $postRst['msg'],
            'created_at' => time(),
            'updated_at' => time(),
        ];

        $AgentRecordUsersDesc = new AgentRecordUsersDesc();
        $AgentRecordUsersDesc->setAttributes($setData);
        $rst['saveFlag'] = $AgentRecordUsersDesc->save();

        return $rst;
    }

}