<?php
namespace common\open\telegram\api;

use common\open\telegram\TelegramBase;
use Yii;
use yii\base\InvalidConfigException;

class WebHookApi extends TelegramBase
{
    # 用户信息接口
    const API_SET_WEB_HOOK = '/setWebHook';
    const API_DELETE_WEB_HOOK = '/deleteWebhook';
    const API_GET_HOOK_INFO = '/getWebhookInfo';
    const API_GET_UPDATES = '/getUpdates';

    /**
     * 设置机器人回调地址
     * @param array $queryParams
     * @param array $headers
     * @param array $params
     * @return array
     * @throws InvalidConfigException
     */
    public static function setWebHook(array $queryParams, array $headers=[], array $params=[]): array
    {
        $object = self::createObject();
        $token = $queryParams['token'];
        $callbackUrl = $queryParams['callbackUrl'];

        $path = '/bot'.$token.'/'.self::API_SET_WEB_HOOK.'?url='.$callbackUrl.'?'.$token;
        $res = $object->get($path, $params, $headers);

        return $res;
    }

    /**
     * 获取机器人信息
     * @param array $queryParams
     * @param array $headers
     * @param array $params
     * @return array
     * @throws InvalidConfigException
     */
    public static function getWebHookInfo(array $queryParams, array $headers=[], array $params=[]): array
    {
        $object = self::createObject();
        $token = $queryParams['token'];

        $path = '/bot'.$token.'/'.self::API_GET_HOOK_INFO;
        $result = $object->post($path, $params, $headers);

        return $result;
    }

    /**
     * 删除机器人
     * @param array $queryParams
     * @param array $headers
     * @param array $params
     * @return array
     * @throws InvalidConfigException
     */
    public static function deleteWebHookInfo(array $queryParams, array $headers=[], array $params=[]): array
    {
        $object = self::createObject();
        $token = $queryParams['token'];

        $path = '/bot'.$token.'/'.self::API_DELETE_WEB_HOOK;
        $result = $object->post($path, $params, $headers);

        return $result;
    }

    /**
     * 获取机器人更新
     * @param array $queryParams
     * @param array $headers
     * @param array $params
     * @return array
     * @throws InvalidConfigException
     */
    public static function getUpdates(array $queryParams, array $headers=[], array $params=[]): array
    {
        $object = self::createObject();
        $token = $queryParams['token'];

        $path = '/bot'.$token.'/'.self::API_GET_UPDATES;
        $res = $object->get($path, $params, $headers);

        return $res;
    }
}
