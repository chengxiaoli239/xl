<?php
namespace common\open\telegram\api;

use common\open\telegram\TelegramBase;
use Yii;
use yii\base\InvalidConfigException;

class MessageApi extends TelegramBase
{
    # 用户信息接口
    const API_SET_WEB_HOOK = '/sendMessage';


    /**
     * 发消息
     * @param array $queryParams
     * @param array $headers
     * @param array $params
     * @return array
     * @throws InvalidConfigException
     */
    public static function sendMessage(array $queryParams, array $headers=[], array $params=[]): array
    {
        $object = self::createObject();
        $token = $queryParams['token'];

        $path = '/bot'.$token.'/'.self::API_SET_WEB_HOOK;
        $result = $object->post($path, $params, $headers);

        return $result;
    }

}
