<?php
namespace common\service\wechat\eyun\api;

use common\models\eyun\EYunMessage;
use common\open\thirdD\api\SiteOrderApi;
use common\service\cache\CacheKeyService;
use common\service\jobs\log\ErrorLogStaticsJobs;
use common\service\jobs\robots\message\WechatPrivateMsgReceiveJobs;
use common\service\jobs\robots\user\WechatFriendsInfoJobs;
use common\service\jobs\robots\user\WechatUserStatusJobs;
use common\service\wechat\eyun\EYunBaseService;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\tools\Tool_Common;
use yii\helpers\Json;

trait EventServiceTrait
{
    public static function buildLogKey(){
        return __FUNCTION__.'_e_x1';
    }
    public static function eventHandler($data): array
    {
        $messageType = $data['messageType'];
        $wcId = $data['wcId'];
        list($code, $dd, $msg) = self::saveMessage($data);
        if($code == SiteOrderApi::IGNORE_CODE){
            return ['code'=>'1000', 'message'=>'消息接收成功'];
        }
        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '接收e云消息', ['messageType'=>$messageType, 'dd'=>$dd, 'data'=>$data]);
        $user_id = $dd['user_id'];

        $data['user_id'] = $user_id;
        $data['business_id'] = $wcId;
        switch ($messageType){
            case EYunMessageOperateService::MESSAGE_P_TEXT_CODE: # 私聊
            case EYunMessageOperateService::MESSAGE_G_TEXT_CODE: # 群聊
                if($wcId != $data['toUser']){ # 只有别人发给机器人的才处理
                    push_queue_open(WechatPrivateMsgReceiveJobs::class, $data);
                }
                break;
            case EYunMessageOperateService::MESSAGE_P_TEXT_CANCEL: # 私聊撤回
                break;
            case EYunMessageOperateService::MESSAGE_G_TEXT_CANCEL: # 群聊
                break;
            case EYunMessageOperateService::MESSAGE_FRIEND_INFO_CODE: # 好友信息变更
                push_queue_open(WechatFriendsInfoJobs::class, $data);
                break;
            case EYunMessageOperateService::MESSAGE_OFFLINE_CODE: # 离线通知
                push_queue_open(WechatUserStatusJobs::class, $data);
                break;
        }

        return ['code'=>'1000', 'message'=>'消息接收成功'];
    }

    /**
     * 消息记录表
     * @param array $data
     * @return array|string[]
     */
    private static function saveMessage(array $data=[]): array
    {
        try {
            $messageType = $data['messageType'];
            $params = $data['data'];
            $fromUser = $params['fromUser'];
            $toUser = $params['toUser']??'';
            $RobotWechatId = $data['wcId'];
            $user_id = EYunBaseService::getRobotUserIdByWechatId($RobotWechatId);
            if(empty($toUser) && !in_array($messageType, EYunMessageOperateService::MESSAGE_SYNC_OPTIONS)){
                throw_info('非正常聊天消息不记录,messageType:'.$messageType.'=content:'.$data['data']['content']);
            }
            $msgId = $params['msgId'];
            $newMsgId = $params['newMsgId'];
            $fromGroup = $params['fromGroup']??'';
            $where = ['toUser'=>$toUser, 'msgId'=>$msgId, 'newMsgId'=>$newMsgId];
            $mkey = CacheKeyService::eyun($toUser, $msgId, $newMsgId);
            $num = \Yii::$app->commonRedis->incr($mkey);
            if($num>1){
                throw_info('消息接收成功', SiteOrderApi::IGNORE_CODE);
            }
            \Yii::$app->commonRedis->expire($mkey, 5);
            $EYunMessage = EYunMessage::findOne($where);
            if(!empty($EYunMessage)){
                return ['code'=>'1000', 'message'=>'消息接收成功'];
            }
            $now_time = time();
            $EYunMessage = new EYunMessage();
            $wechatUserId = EYunBaseService::getWechatUserId($fromUser, $user_id);
            if(empty($user_id)){
                throw_info('机器人系统user_id不能为空');
            }
            $data['description'] = \common\tools\Common::filterEmoji($data['description']);
            $setData = [
                'user_id' => $user_id,
                'wechat_user_id' => $wechatUserId,
                'toUser'=>$toUser,
                'fromGroup'=>$fromGroup,
                'msg_type' => $messageType,
                'msgId'=>$msgId??'MSG'.get_unique_id(),
                'newMsgId'=>$newMsgId??'',
                'status' => EYunMessage::STATUS_WAIT,
                'data' => Json::encode($data, 320),
                'created_at' => $now_time,
                'updated_at' => $now_time,
            ];
            $EYunMessage->setAttributes($setData, false);
            //p($EYunMessage->attributes);
            if(!$EYunMessage->save()){
                throw_info(Json::encode($EYunMessage->getErrors(), 320));
            }

        }catch (\Exception $e){
            Tool_Common::log('/eyun/'.__FUNCTION__, 'ERR', '消息内容保存异常', ['data'=>$data, 'err_msg'=>$e->getMessage()]);
            if($e->getCode()==SiteOrderApi::IGNORE_CODE){
                push_queue(ErrorLogStaticsJobs::class, ['err_msg'=>'消息内容保存异常：'.$e->getMessage()]);
            }
            return [$e->getCode()==SiteOrderApi::IGNORE_CODE ? SiteOrderApi::IGNORE_CODE : 30001, [], $e->getMessage()];
        }
        $dd = [
            'params' => $params,
            'user_id' => $user_id,
            'messageType' => $messageType,
        ];

        return [0, $dd, '记录成功'];
    }
}
