<?php
namespace common\service\wechat\eyun\api;

use common\models\eyun\EYunMessage;
use common\service\wechat\eyun\EYunBaseService;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\tools\Tool_Common;
use yii\helpers\Json;

trait EventServiceTrait
{
    public static function eventHandler($data)
    {
        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '接收e云消息', ['data'=>$data]);
        $messageType = $data['messageType'];
        $params = $data['data'];
        $toUser = $params['toUser'];
        $msgId = $params['msgId'];
        $newMsgId = $params['newMsgId'];
        $where = ['toUser'=>$toUser, 'msgId'=>$msgId, 'newMsgId'=>$newMsgId];
        $EYunMessage = EYunMessage::findOne($where);
        if(!empty($EYunMessage)){
            return ['code'=>'1000', 'message'=>'消息接收成功'];
        }
        $now_time = time();
        $EYunMessage = new EYunMessage();
        $setData = [
            'user_id' => EYunBaseService::getUserIdByFromUser($data['fromUser']),
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
            return ['code'=>3000, 'message'=>Json::encode($EYunMessage->getErrors(), 320)];
        }
        switch ($messageType){
            case EYunMessageOperateService::MESSAGE_P_TEXT_CODE: # 私聊
                break;
            case EYunMessageOperateService::MESSAGE_P_TEXT_CANCEL: # 私聊
                break;
            case EYunMessageOperateService::MESSAGE_G_TEXT_CODE: # 群聊
                break;
            case EYunMessageOperateService::MESSAGE_G_TEXT_CANCEL: # 群聊
                break;
        }

        return ['code'=>'1000', 'message'=>'消息接收成功'];
    }
}
