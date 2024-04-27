<?php
namespace common\service\message;

use backend\models\open\PlatformRobot;
use common\helpers\Platform;
use common\models\wechat\WechatUser;
use common\service\jobs\robots\message\WechatPrivateMsgReceiveJobs;
use common\service\jobs\telegram\MessageReceiveJobs;
use common\service\open\telegram\MessageOperateService;
use common\tools\Tool_Common;

class Send
{
    const ACTION_BALANCE = 'balance';
    const ACTION_BET = 'bet';
    const ACTION_AWARD = 'award';
    const ACTION_OPTIONS = [
        self::ACTION_BALANCE => '余额变动',
        self::ACTION_BET => '下注',
        self::ACTION_AWARD => '派奖',
    ];
    public $platformRobot;
    public $userId;
    public $robotAdmin;
    public function __construct($userId)
    {
        $this->platformRobot = PlatformRobot::find()->where(['user_id'=>$userId])->one();
        $this->userId = $userId;
        $this->robotAdmin = WechatUser::find()->where(['user_id'=>$userId, 'is_admin'=>1])->asArray()->limit(1)->one();
    }

    /**
     * @param $platformUser
     * @param $replyTxt ['给用户发的消息', '给管理员发的消息']
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public function replyAfterAction($platformUser, $replyTxt, $action='recharge'): bool
    {
        $targetId=  $platformUser['userName'];
        $messageService = new MessageOperateService($platformUser['user_id'], $targetId);
        Tool_Common::log('/message/'.__FUNCTION__, 'INFO', '消息回复', ['platform_id'=>$this->platformRobot->platform_id, 'targetId'=>$targetId, 'replyTxt'=>$replyTxt]);
        list($replyTxt1, $replyTxt2) = $replyTxt; # 分别为：用户消息、管理员消息
        if($this->platformRobot->platform_id == Platform::TELEGRAM){
            $robotId = $platformUser['robot_wechat'];
            $platformRobot = PlatformRobot::find()->where(['platform_robot_id'=>$robotId])->limit(1)->one();
            $messageToUserData = ['targetId'=>$targetId, 'token'=>$platformRobot['token']];
            $replyUserTxt = '【内容】'.$replyTxt1;
            # 1、给用户发信息
            $messageService->reply($this->userId, $replyUserTxt, $messageToUserData);
            //todo 2、审核之后给用户和管理员同时发消息，目前只有用户收到，管理员未处理
            # 2、给管理员发信息
            $replyAdminTxt = '【内容】"'.$platformUser['nickName'].'" '.$replyTxt2;
            $messageData = ['targetId'=>$this->robotAdmin['userName'], 'token'=>$platformRobot['token']];
            $messageService->reply($this->userId, $replyAdminTxt, $messageData);
            Tool_Common::log('/message/'.__FUNCTION__, 'INFO', '消息回复afterAction', ['messageToUserData'=>$messageToUserData, 'messageDataToAdmin'=>$messageData, 'action'=>Send::ACTION_OPTIONS[$action]]);
        }else{
            $replyUserTxt = '【内容】'.$replyTxt1;
            WechatPrivateMsgReceiveJobs::reply($this->userId, [$replyUserTxt], ['fromUser' => $platformUser['userName']]); # 回复消息
        }

        return true;
    }

}
