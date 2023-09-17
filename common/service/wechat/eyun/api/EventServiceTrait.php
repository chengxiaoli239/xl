<?php
namespace common\service\wechat\eyun\api;

use common\models\eyun\EYunMessage;
use common\service\jobs\robots\user\WechatUserStatusJobs;
use common\service\wechat\eyun\EYunBaseService;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\tools\Tool_Common;
use yii\helpers\Json;

trait EventServiceTrait
{
    public static function eventHandler($data)
    {
        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '接收e云消息', ['data'=>$data]);
        list($code, $dd, $msg) = self::saveMessage($data);
        $params = $dd['params'];
        $user_id = $dd['user_id'];
        $messageType = $dd['messageType'];


        $wcId = $data['wcId'];

        $data['business_id'] = $wcId;
        $MessageService = new EYunMessageOperateService($user_id);
        switch ($messageType){
            case EYunMessageOperateService::MESSAGE_P_TEXT_CODE: # 私聊
                break;
            case EYunMessageOperateService::MESSAGE_P_TEXT_CANCEL: # 私聊
                break;
            case EYunMessageOperateService::MESSAGE_G_TEXT_CODE: # 群聊
                break;
            case EYunMessageOperateService::MESSAGE_G_TEXT_CANCEL: # 群聊
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
    private static function saveMessage($data=[]){
        try {
            $messageType = $data['messageType'];
            $params = $data['data'];
            $toUser = $params['toUser'];
            if(empty($toUser)){
                throw_info('非正常聊天消息不记录,messageType:'.$messageType.'=content:'.$data['data']['content']);
            }
            $msgId = $params['msgId'];
            $newMsgId = $params['newMsgId'];
            $where = ['toUser'=>$toUser, 'msgId'=>$msgId, 'newMsgId'=>$newMsgId];
            $EYunMessage = EYunMessage::findOne($where);
            if(!empty($EYunMessage)){
                return ['code'=>'1000', 'message'=>'消息接收成功'];
            }
            $now_time = time();
            $EYunMessage = new EYunMessage();
            $user_id = EYunBaseService::getUserIdByFromUser($params['fromUser']);
            $setData = [
                'user_id' => $user_id,
                'toUser'=>$toUser,
                'msgId'=>$msgId,
                'newMsgId'=>$newMsgId,
                'status' => EYunMessage::STATUS_WAIT,
                'data' => Json::encode($data, 320),
                'created_at' => $now_time,
                'updated_at' => $now_time,
            ];
            $EYunMessage->setAttributes($setData, false);
            if(!$EYunMessage->save()){
                throw_info(Json::encode($EYunMessage->getErrors(), 320));
            }

        }catch (\Exception $e){
            return [30001, [], $e->getMessage()];
        }
        $dd = [
            'params' => $params,
            'user_id' => $user_id,
        ];

        return [0, $dd, '记录成功'];
    }
}
