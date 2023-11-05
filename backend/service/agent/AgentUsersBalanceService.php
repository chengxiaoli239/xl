<?php
namespace backend\service\agent;
use backend\models\AgentUsersBalanceFlows;
use backend\service\BaseService;
use common\models\wechat\WechatUser;
use common\service\chat\Tool_Common;
use common\service\thirdD\ThirdDTypeService;
use common\service\wechat\WechatUserService;
use yii\helpers\ArrayHelper;
use yii;

class AgentUsersBalanceService extends BaseService {

    # 审核状态 0未审核1审核通过2拒绝
    const FLOW_CHECK_STATUS_WAIT = 0;
    const FLOW_CHECK_STATUS_PASS = 1;
    const FLOW_CHECK_STATUS_REFUSE = 2;

    public static $s = [
        'status' => [
            self::FLOW_CHECK_STATUS_WAIT => '待审核',
            self::FLOW_CHECK_STATUS_PASS => '审核通过',
            self::FLOW_CHECK_STATUS_REFUSE => '拒绝',
        ],
    ];

    /**
     * 处理上下分申请匹配     * @param $text
     * @param $wechatUser
     * @return array
     */
    public static function operateBalanceChange($text='', $wechatUser=[]){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $WechatUser = WechatUser::findOne($wechatUser['id']);
            $agent_id = $WechatUser->user_id;
            $member_id = (string)$WechatUser->id;

            if(AgentUsersBalanceFlows::findOne(['agent_id'=>$agent_id, 'member_id'=>$member_id, 'status'=>0])){
                throw_info('有未审核记录，请联系矿主处理', ThirdDTypeService::CODE_FOR_USER);
            }

            if(preg_match('/上\s*(\d+)$/', $text,$matches)){
                $balance = (int)$matches[1];
                $type = WechatUserService::TYPE_BALANCE_UP;
                $desc = '申请上 '.$balance.'，'.'等待审核成功上分';
            }elseif (preg_match('/下\s*(\d+)$/', $text,$matches)){
                $balance = (int)$matches[1];
                $type = WechatUserService::TYPE_BALANCE_DOWN;
                # 校验积分下分上分充足
                $now_balance = $WechatUser->balance;
                $after_balance = $now_balance - $balance;
                $desc = '申请下'.$balance.'，暂扣'.$balance.'，剰余'.$after_balance.'，等待转咪';;
                if($after_balance<0){
                    throw_info('分数不足：'.$now_balance);
                }
                $WechatUser->balance = $after_balance; # ，申请下分之后的积分
                $WechatUser->updated_at = time();
                if(!$WechatUser->save()){
                    throw_info(current($WechatUser->getErrors()));
                }
            }
            $setData = [
                'agent_id' => $agent_id,
                'member_id' => $member_id,
                'member_account' => $WechatUser->nickName,
                'type' => $type, # 1上分2下分
                'balance' => $balance, # 上/下 积分，变动
                'balance_now' => $WechatUser->balance, # 当前积分
                'desc' => '用户'.$desc,
                'status' => 0,
                'created_at' =>time(),
                'updated_at' =>time(),
            ];

            $AgentUsersBalanceFlows = new AgentUsersBalanceFlows();
            $AgentUsersBalanceFlows->setAttributes($setData);
            if(!$AgentUsersBalanceFlows->save()){
                $msg = current($AgentUsersBalanceFlows->getErrors());
                $logArr = ['matches'=>$matches, 'msg'=>$msg, 'attributes'=>$AgentUsersBalanceFlows->attributes];
                \common\tools\Tool_Common::log('upOrDownBalance', 'ERR', '用户上下分', $logArr);
                throw_info($desc.'失败'.$msg);
            }
            $logArr =  ['desc'=>$desc, 'WechatUser'=>$WechatUser->attributes, 'attributes'=>$AgentUsersBalanceFlows->attributes];
            Tool_Common::log('upOrDownBalance', 'INFO', '用户上下分',$logArr);
            $msg = $desc;
            $data = [
                'userInfo' => $WechatUser->attributes,
                'msg' => $msg,
            ];
            $transaction->commit();
            Tool_Common::log('/wechat/'.__FUNCTION__, 'ERR', '消息接收处理', ['text'=>$text, 'data'=>$data]);
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = [ ];
            $msg = ($e->getCode()==ThirdDTypeService::CODE_FOR_USER) ? $e->getMessage() : '申请上下分异常';
            Tool_Common::log('/wechat/'.__FUNCTION__, 'ERR', '消息接收处理异常', ['text'=>$text, 'err_msg'=>$e->getMessage().'_'.$e->getFile().'_'.$e->getLine()]);
        }

        return [ThirdDTypeService::CODE_FOR_USER, $data, $msg];
    }

}
