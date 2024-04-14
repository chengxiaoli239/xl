<?php

namespace common\service\open\actions;

use common\helpers\Platform;
use common\open\telegram\api\WebHookApi;
use common\tools\Tool_Common;

class PlatformRobotService
{
    public static function getClass($platformId)
    {

    }

    /**
     * 平台登录
     * @param object $platformRobotModel
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public static function login(object $platformRobotModel): bool
    {
        $platformId = $platformRobotModel->platform_id;
        $token = $platformRobotModel->token;
        $url = \Yii::$app->params['SELF_DOMAIN'].'/api/telegram/callback?token='.$token;
        switch ($platformId){
            case Platform::TELEGRAM:
                $result = WebHookApi::setWebHook(['url'=>$url]);
                if($result != 'ok'){
                    Tool_Common::log('/platform/'.__FUNCTION__, 'INFO', '平台机器人设置', ['result'=>$result, 'token'=>$token]);
                    return false;
                }
                break;
        }

        return true;
    }

}