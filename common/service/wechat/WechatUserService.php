<?php

namespace common\service\wechat;

use backend\models\AgentUsersBalanceFlows;
use common\models\eyun\RobotUser;
use common\models\wechat\WechatUser;
use common\service\BaseService;
use common\service\thirdD\ThirdDTypeService;
use common\service\wechat\eyun\EYunBaseService;
use common\tools\Tool_Common;

class WechatUserService extends BaseService
{
    const STATUS_DISABLE = 0; # 禁用
    const STATUS_ACTIVE = 1; # 激活

    const WECHAT_STATUS_OFFLINE = 0;
    const WECHAT_STATUS_ONLINE = 1;

    # 用户消息业务类型
    const TYPE_ORDER_BET = 1;
    const TYPE_ORDER_CANCEL = 2;
    const TYPE_BALANCE_UP = 3;
    const TYPE_BALANCE_DOWN = 4;
    const TYPE_ORDER_AWARD = 5;

    const OP_BALANCE_MEMBER_OP = 1;
    const OP_BALANCE_AGENT_OP = 2;

    public static $s = [
        'status' => [
            self::STATUS_DISABLE => '已禁用',
            self::STATUS_ACTIVE => '已激活',
        ],
        'wechat_status' => [
            self::WECHAT_STATUS_OFFLINE => '已掉线',
            self::WECHAT_STATUS_ONLINE => '在线',
        ],
        'balance_type' => [
            self::TYPE_ORDER_BET => '下注',
            self::TYPE_ORDER_CANCEL => '撤单',
            self::TYPE_BALANCE_UP => '上分',
            self::TYPE_BALANCE_DOWN => '下分',
            self::TYPE_ORDER_AWARD => '派奖',
        ],
        'balance_OPERATE_TYPE' => [
            self::OP_BALANCE_MEMBER_OP => '用户申请->代理审核',
            self::OP_BALANCE_AGENT_OP => '代理操作',
        ],
    ];

    public static function getWechatUsersKey($user_id){
        return 'getWechatUsersKey_x0_'.$user_id;
    }
    public static function getWechatUserKey($id){
        return 'getWechatUserKey_x0_'.$id;
    }

    /**
     * 获取代理微信好友
     * @param string $user_id 代理id
     * @param bool $useCache
     * @return array|mixed|\yii\db\ActiveRecord[]
     */
    public static function getWechatUsers($user_id='', $useCache=true){
        $m = \Yii::$app->cache;
        $mkey = self::getWechatUsersKey($user_id);
        if(!$useCache OR !$data = $m->get($mkey)){
            $dataQuery = WechatUser::find()
                ->select(['id', 'user_id', 'userName', 'agent_id'=>'user_id', 'member_id'=>'id', 'nickName', 'status', 'smallHead'])
                ->where(['user_id'=>$user_id]);
            #$sql = $dataQuery->createCommand()->getRawSql();p($sql);
            $data = $dataQuery->indexBy(['userName'])->asArray()->all();
            $m->set($mkey, $data, 600);
        }

        return $data;
    }

    /**
     * 获取代理微信好友
     * @param string $user_id
     * @param bool $useCache
     * @return array|mixed|\yii\db\ActiveRecord[]
     */
    public static function getWechatUser($id='', $useCache=true){
        $m = \Yii::$app->cache;
        $mkey = self::getWechatUserKey($id);
        if(!$useCache OR !$data = $m->get($mkey)){
            $dataQuery = WechatUser::find()
                ->select([
                    'id',
                    'user_id',
                    'agent_id'=>'user_id',
                    'member_id'=>'id',
                    'name'=>'nickName',
                    'balance',
                    'nickName',
                    'status',
                    'smallHead'
                ])->where(['id'=>$id]);
            #$sql = $dataQuery->createCommand()->getRawSql();p($sql);
            $data = $dataQuery->indexBy(['userName'])->asArray()->one();
            $m->set($mkey, $data, 1);
        }

        return $data;
    }

    public static function syncWechatFriends($user_id=0){

        try {

            $RobotUser = RobotUser::findOne(['user_id'=>$user_id]);
            if(empty($RobotUser)){
                throw_info('找不到机器人记录');
            }
            if(empty($RobotUser->wechat_status)){
                throw_info('机器人不在线不能同步好友');
            }
            # 登录成功之后 - 初始化通讯录
            $e = new EYunBaseService($user_id);
            # 初始化通讯录列表（第四步）
            $initAddressListRst = $e->initAddressList();
            # 获取通讯录列表（第五步）
            $response = $e->getAddressList($RobotUser->wcId);
        }catch (\Exception $e){
            return ['code'=>300, 'msg'=>$e->getMessage()];
        }
    }

}
