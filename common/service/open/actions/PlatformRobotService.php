<?php

namespace common\service\open\actions;

use backend\models\open\PlatformRobot;
use common\helpers\Platform;
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

        return ['status'=>200, 'msg'=>'登录失败'];
    }

}