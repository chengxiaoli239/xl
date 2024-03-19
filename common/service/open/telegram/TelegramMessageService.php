<?php
namespace common\service\open\telegram;

use common\models\open\PlatformGroupUser;
use common\models\wechat\WechatUser;
use common\models\open\PlatformGroup;
use common\tools\Tool_Common;
use yii\helpers\Json;

class TelegramMessageService  extends TelegramBaseService
{

    /**
     * @param $post
     * 1、私聊：{"update_id":840228241,"message":{"message_id":27,"from":{"id":6830978835,"is_bot":false,"first_name":"破局","last_name":"Mr","language_code":"zh-hans"},"chat":{"id":6830978835,"first_name":"破局","last_name":"Mr","type":"private"},"date":1709564365,"text":"可不可以不想你"}}
     *
     * 2、群聊：{"update_id":840228248,"message":{"message_id":38,"from":{"id":6830978835,"is_bot":false,"first_name":"破局","last_name":"Mr","language_code":"zh-hans"},"chat":{"id":6830978835,"first_name":"破局","last_name":"Mr","type":"private"},"date":1709826476,"text":"/start","entities":[{"offset":0,"length":6,"type":"bot_command"}]}}
     *
     * 3、进群（获取群号）：{"update_id":840228252,"message":{"message_id":43,"from":{"id":7114082398,"is_bot":false,"first_name":"金钱龟","username":"oloo1688"},"chat":{"id":-4183690108,"title":"破局机器人讨论组","type":"group","all_members_are_administrators":true},"date":1710341284,"new_chat_participant":{"id":7114082398,"is_bot":false,"first_name":"金钱龟","username":"oloo1688"},"new_chat_member":{"id":7114082398,"is_bot":false,"first_name":"金钱龟","username":"oloo1688"},"new_chat_members":[{"id":7114082398,"is_bot":false,"first_name":"金钱龟","username":"oloo1688"}]}}
     * @return array
     */
    public function callbackMessage($params): array
    {
        $message = $params['message'];
        $chat = $message['chat'];
        $from = $message['from'];
        $userId = 0; # 有待确定如何取值
        Tool_Common::log('/telegram/'.__FUNCTION__, 'ERR', '消息处理异常', ['type'=>$chat['type']]);
        try {
            switch ($chat['type']){
                case self::CHAT_TYPE_GROUP:
                    # 群消息
                    list($platformUserId, $name, $info) = $this->saveGroupInfo($from, $chat, $userId);
                    break;
                case self::CHAT_TYPE_PRIVATE:
                    # 私聊
                    list($platformUserId, $name, $info) = $this->saveFriendInfo($from, $chat, $userId);
                    $text = $message['text'];

                    break;
            }
        }catch (\Exception $e){
            Tool_Common::log('/telegram/'.__FUNCTION__, 'ERR', '消息处理异常', ['params'=>$params, 'err_msg'=>$e->getMessage()]);
        }
        Tool_Common::log('/telegram/'.__FUNCTION__, 'ERR', '消息处理异常', ['params'=>$params, 'platformUserId'=>$platformUserId, 'name'=>$name, 'info'=>$info->attributes]);
        //p(['post'=>$post, 'get'=>$get, 'cdddd']);

        return [];
    }

    /**
     * 保存好友信息
     * @param array $from
     * @param array $chat
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public function saveFriendInfo(array $from=[], array $chat=[]): array
    {
        $platformUserId = $from['id'];
        $wechatUser = WechatUser::findOne(['userName'=>$platformUserId]);
        $now_time = time();
        if(empty($wechatUser)){
            $wechatUser = new WechatUser();
            $setData = [
                'user_id' => 0, # 本系统用户id
                'userName' => $platformUserId,
                'nickName' => $chat['first_name'].$chat['last_name'],
                'created_at' => $now_time,
            ];
        }
        $setData['updated_at'] = $now_time;
        $wechatUser->setAttributes($setData, false);
        if(!$wechatUser->save()){
            throw_info(Json::encode($wechatUser->getErrors()));
        }

        return [$platformUserId, $wechatUser->nickName, $wechatUser];
    }

    /**
     * 保存好友信息
     * @param array $from
     * @param array $chat
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public function saveGroupInfo(array $from=[], array $chat=[], $userId=0): array
    {
        $platformUserId = $from['id'];
        $groupId = $chat['id']; # 群id
        $group = PlatformGroup::findOne(['group_id'=>$groupId]);
        $now_time = time();
        if(empty($group)){
            $group = new PlatformGroup();
            $setData = [
                'user_id' => $userId, # 本系统用户id
                'group_id' => (string)$groupId,
                'name' => $chat['title'],
                'created_at' => $now_time,
            ];
        }
        $setData['updated_at'] = $now_time;
        $group->setAttributes($setData, false);
        if(!$group->save()){
            throw_info(Json::encode($group->getErrors()));
        }

        $setData = [];
        $groupUser = PlatformGroupUser::findOne(['group_id'=>$groupId, 'platform_user_id'=>$platformUserId]);
        if(empty($groupUser)){
            $groupUser = new PlatformGroupUser();
            $setData = [
                'user_id' => $userId,
                'group_id' => (string)$groupId,
                'platform_user_id' => (string)$platformUserId,
                'created_at' => $now_time,
            ];
        }
        $setData['username'] = $from['first_name'];
        $setData['updated_at'] = $now_time;
        $groupUser->setAttributes($setData, false);
        if(!$groupUser->save()){
            throw_info(Json::encode($groupUser->getErrors()));
        }

        return [$platformUserId, $group->name, $group];
    }
}
