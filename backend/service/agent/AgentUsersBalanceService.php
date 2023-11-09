<?php
namespace backend\service\agent;
use backend\models\AgentUsersBalanceFlows;
use backend\service\BaseService;
use common\models\wechat\WechatUser;
use common\service\chat\Tool_Common;
use common\service\jobs\statics_3d\UserDayStaticsJobs;
use common\service\thirdD\CommonBaseService;
use common\service\wechat\WechatUserService;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii;
use yii\helpers\Json;

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
     * 处理上下分申请匹配
     * @param string $text
     * @param array $wechatUser
     * @return array
     * @throws Exception
     */
    public static function operateBalanceChange(string $text='', array $wechatUser=[]): array
    {
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $WechatUser = WechatUser::findOne($wechatUser['id']);
            $agent_id = $WechatUser->user_id;
            $member_id = (string)$WechatUser->id;
            $now_balance = $WechatUser->balance; # 操作时积分

            if(AgentUsersBalanceFlows::findOne(['agent_id'=>$agent_id, 'member_id'=>$member_id, 'status'=>0])){
                throw_info('有未审核记录，请联系矿主处理', CommonBaseService::CODE_FOR_USER);
            }

            if(preg_match('/上\s*(\d+)/', $text,$matches)){
                $balance = (int)$matches[1];
                $type = WechatUserService::TYPE_BALANCE_UP;
                $desc = '申请上 '.$balance.'咪，'.'等待审核';
                $after_balance = ''; # 等审核再记录
            }elseif (preg_match('/下\s*(\d+)/', $text,$matches)){
                $balance = (int)$matches[1];
                $type = WechatUserService::TYPE_BALANCE_DOWN;
                # 校验积分下分上分充足
                $after_balance = $now_balance - $balance;
                $desc = '申请下'.$balance.'，暂扣'.$balance.'咪，剰余'.$after_balance.'，等待转咪';;
                if($after_balance<0){
                    throw_info('分数不足：'.$now_balance);
                }
                $WechatUser->balance = $after_balance; # ，申请下分之后的积分
                $WechatUser->updated_at = time();
                if(!$WechatUser->save()){
                    throw_info(current($WechatUser->getErrors()));
                }
            }
            if(empty($type) OR empty($now_balance)){
                throw_info('上下分匹配异常');
            }
            $setData = [
                'agent_id' => $agent_id,
                'member_id' => $member_id,
                'member_account' => $WechatUser->nickName,
                'type' => $type, # 1上分2下分
                'balance' => $balance, # 上/下 积分，变动
                'balance_now' => $now_balance, # 申请前积分
                'balance_after' => $after_balance, # 上分：申请不加审核时处理，下分申请时候扣掉，审核不扣
                'desc' => '用户'.$desc,
                'status' => 0,
                'created_at' =>time(),
                'updated_at' =>time(),
            ];

            $Flows = new AgentUsersBalanceFlows();
            $Flows->setAttributes($setData);
            if(!$Flows->save()){
                $msg = current($Flows->getErrors());
                $logArr = ['matches'=>$matches, 'msg'=>$msg, 'attributes'=>$Flows->attributes];
                \common\tools\Tool_Common::log('upOrDownBalance', 'ERR', '用户上下分', $logArr);
                throw_info($desc.'失败'.$msg);
            }
            $logArr =  ['desc'=>$desc, 'WechatUser'=>$WechatUser->attributes, 'attributes'=>$Flows->attributes];
            Tool_Common::log('upOrDownBalance', 'INFO', '用户上下分',$logArr);
            $msg = $desc;
            $data = [
                'userInfo' => $WechatUser->attributes,
                'type' => $type,
                'msg' => $msg,
            ];
            $transaction->commit();
            //push_queue_fast(UserDayStaticsJobs::class, ['user_id'=>$agent_id, 'type'=>$type, 'wechat_user_id'=>$wechatUser['id']]);
            Tool_Common::log('/wechat/'.__FUNCTION__, 'ERR', '消息接收处理', ['text'=>$text, 'data'=>$data]);
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = [ ];
            $msg = ($e->getCode()== CommonBaseService::CODE_FOR_USER) ? $e->getMessage() : '申请上下分异常';
            Tool_Common::log('/wechat/'.__FUNCTION__, 'ERR', '消息接收处理异常', ['text'=>$text, 'err_msg'=>$e->getMessage().'_'.$e->getFile().'_'.$e->getLine()]);
        }

        return [CommonBaseService::CODE_FOR_USER, $data, $msg];
    }

    /**
     * @param string $orderId
     * @param float $money
     * @param string $member_id
     * @param int $type
     * @return array
     */
    public static function updateBalance(string $orderId='', float $money=0.00, string $member_id='', int $type=1): array
    {
        $WechatUser = WechatUser::findOne($member_id);
        if(empty($WechatUser)){
            throw_info('会员信息找不到');
        }
        $before_balance = $WechatUser->balance;
        switch ($type){
            case WechatUserService::TYPE_ORDER_BET:
                $changeMoney = 0 - $money;
                $after_balance = $WechatUser->balance +$changeMoney;
                if($after_balance<0){
                    throw_info('鱼分不足，目前盛鱼：'.$before_balance, CommonBaseService::CODE_FOR_USER);
                }
                break;

            case WechatUserService::TYPE_ORDER_CANCEL:
                $changeMoney = $money;
                $after_balance = $WechatUser->balance + $changeMoney;
                break;
        }
        $setData = ['balance'=>$after_balance];
        $WechatUser->setAttributes($setData, false);
        if(!$WechatUser->save()){
            throw_info(Json::encode($WechatUser->getErrors()));
        }
        $now_time = time();
        $setDataFlow = [
            'order_id' => $orderId,
            'balance_after' => $after_balance,
            'balance' => $changeMoney,
            'balance_now' => $before_balance,
            'status' => AgentUsersBalanceService::FLOW_CHECK_STATUS_REFUSE, # 下单、撤单默认通过
            'created_at' => $now_time,
            'updated_at' => $now_time,
        ];
        $flow = new AgentUsersBalanceFlows();
        $flow->setAttributes($setDataFlow, false);
        if(!$flow->save()){
            throw_info(Json::encode($flow->getErrors()));
        }

        return $WechatUser->attributes;
    }
}
