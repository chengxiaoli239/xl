<?php

namespace common\service\open\actions;

use backend\models\open\PlatformRobot;
use common\helpers\Platform;
use common\models\open\PlatformGroup;
use common\open\telegram\api\WebHookApi;
use common\tools\Tool_Common;

class PlatformRobotService
{
    /**
     * 平台登录
     * @param object $platformRobotModel
     * @return array|false
     */
    public static function setWebHook(object $platformRobotModel)
    {
        $platformId = $platformRobotModel->platform_id;
        $token = $platformRobotModel->token;
        $url = \Yii::$app->params['SELF_DOMAIN'].'/api/telegram/callback';
        try {
            switch ($platformId){
                case Platform::TELEGRAM:
                    $result = WebHookApi::setWebHook(['callbackUrl'=>$url, 'token'=>$token]);
                    if(!$result['ok']){
                        Tool_Common::log('/platform/'.__FUNCTION__, 'INFO', '平台机器人设置', ['url'=>$url, 'result'=>$result, 'token'=>$token]);
                        throw_info('设置回调失败');
                    }
                    break;
            }
            $platformRobotModel->status = PlatformRobot::STATUS_ACTIVE;
            $platformRobotModel->updated_at = time();
            $platformRobotModel->save();
        }catch (\Exception $e){
            return ['status'=>301, 'msg'=>$e->getMessage()];
        }

        return ['status'=>200, 'msg'=>'设置成功'];
    }

    /**
     * 获取群聊信息，主要是群聊ID
     * @param object $platformRobotModel
     * @return array|false
     */
    public static function getUpdates(object $platformRobotModel)
    {
        $platformId = $platformRobotModel->platform_id;
        $token = $platformRobotModel->token;
        try {
            switch ($platformId){
                case Platform::TELEGRAM:
                    $result = WebHookApi::getUpdates(['token'=>$token]);
                    Tool_Common::log('/platform/'.__FUNCTION__, 'INFO', '平台机器人设置', ['result'=>$result, 'token'=>$token]);
                    if(!$result['ok']){
                        throw_info('获取群信息失败');
                    }
                    $groups = [];
                    foreach ($result['result'] as $item){
                        $r = $item['my_chat_member']??$item['message'];
                        if(!isset($r['chat']['type']) OR $r['chat']['type']!='group'){
                            continue;
                        }
                        $chat = $r['chat'];
                        $tmpData = [
                            'user_id' => $platformRobotModel->user_id,
                            'group_id' => (string)$chat['id'],
                            'name' => $chat['title'],
                            'nickName' => $chat['title'],
                            'updated_at' => time(),
                        ];
                        #$chatMember = $r['new_chat_member']['user']??$r['new_chat_member'];
                        #if(empty($chatMember)){
                        #    continue;
                        #}
                        #$tmpData['name'] = $chatMember['username'];
                        $groups[$chat['id']] = $tmpData;
                    }
                    foreach ($groups as $group_id=>$group){
                        $where = ['user_id'=>$platformRobotModel->user_id, 'group_id'=>$group_id];
                        if(!$platformGroup = PlatformGroup::findOne($where)){
                            $platformGroup = new PlatformGroup();
                            $group['created_at'] = time();
                        }
                        $platformGroup->setAttributes($group, false);
                        if(!$platformGroup->save()){
                            p($platformGroup->getErrors());
                        }
                    }
                    break;
            }
        }catch (\Exception $e){
            return ['status'=>301, 'msg'=>$e->getMessage()];
        }

        return ['status'=>200, 'msg'=>'获取更新成功'];
    }

}