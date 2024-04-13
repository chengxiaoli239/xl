<?php
namespace common\service\message;

use backend\models\open\PlatformRobot;
use common\helpers\Platform;
use common\models\wechat\WechatUser;
use common\service\jobs\robots\message\WechatPrivateMsgReceiveJobs;
use common\service\jobs\telegram\MessageReceiveJobs;
use common\tools\Tool_Common;

class Send
{
    public $platformRobot;
    public $userId;
    public $robotAdmin;
    public function __construct($userId)
    {
        $this->platformRobot = PlatformRobot::find()->where(['user_id'=>$userId])->one();
        $this->userId = $userId;
        $this->robotAdmin = WechatUser::find()->where(['user_id'=>$userId, 'is_admin'=>1])->asArray()->limit(1)->one();
    }

    public function replyAfterRecharge($platformUser, $targetId, $replyTxt): bool
    {
        Tool_Common::log('/message/'.__FUNCTION__, 'INFO', '消息回复', ['platform_id'=>$this->platformRobot->platform_id, 'targetId'=>$targetId, 'replyTxt'=>$replyTxt]);
        if($this->platformRobot->platform_id == Platform::TELEGRAM){
            $messageData = ['targetId'=>$targetId, 'token'=>$this->platformRobot->token];
            Tool_Common::log('/message/'.__FUNCTION__, 'INFO', '消息回复01-用户', ['messageData'=>$messageData]);
            $replyUserTxt = '【内容】'.$replyTxt;
            # 1、给用户发信息
            MessageReceiveJobs::reply($this->userId,  [$replyUserTxt], $messageData);
            //todo 2、审核之后给用户和管理员同时发消息，目前只有用户收到，管理员未处理
            # 2、给管理员发信息
            $replyAdminTxt = '【内容】"'.$platformUser->nickName.'" '.$replyTxt;
            $messageData = ['targetId'=>$this->robotAdmin['userName'], 'token'=>$this->platformRobot->token];
            Tool_Common::log('/message/'.__FUNCTION__, 'INFO', '消息回复02-管理员', ['messageData'=>$messageData]);
            MessageReceiveJobs::reply($this->userId,  [$replyAdminTxt], $messageData);
        }else{
            $replyUserTxt = '【内容】'.$replyTxt;
            WechatPrivateMsgReceiveJobs::reply($this->userId, [$replyUserTxt], ['fromUser' => $platformUser->userName]); # 回复消息
        }

        return true;
    }

}
