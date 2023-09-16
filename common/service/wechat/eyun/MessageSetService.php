<?php

namespace common\service\wechat\eyun;

use common\models\eyun\EyunAuth;
use common\models\eyun\RobotUser;
use common\service\BaseService;
use common\service\chat\Tool_Common;

class MessageSetService extends EYunBaseService
{

    /**
     * 设置http回调地址
     * @return bool|mixed|null
     */
    public function setHttpCallbackUrl(){
        $url = $this->base_url . '/setHttpCallbackUrl';
        $httpUrl = 'http://'.$_SERVER['SERVER_NAME'].'/eyunapi/index/callback';
        $params = [
            'httpUrl' => $httpUrl,
            'type' => EYunBaseService::MSG_TYPE_IMPROVE,
        ];
        $response = $this->request($url, $params, $this->headers);
        if($response['code'] == 1000){
            $EyunAuth = EyunAuth::findOne(1);
            if($EyunAuth){
                $EyunAuth->callback_url = $httpUrl;
                if(!$EyunAuth->save()){
                    return ['code'=>3001, 'message'=>$EyunAuth->getErrors()];
                }
            }
        }

        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '设置http回调地址', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }
}
