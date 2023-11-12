<?php
namespace common\open\chaoti;

use common\tools\Tool_Common;
use Yii;
use common\exceptions\SystemException;

class Sign
{
    /**
     * 验签
     * @param  array    $headers  请求头
     * @return bool
     * @throws SystemException
     */
    public static function check($headers)
    {
        $timestamp = $headers['timestamp'][0] ?? 0;
        $appKey = $headers['app-key'][0] ?? '';
        $sign = $headers['sign'][0] ?? '';
        $version = $headers['version'][0] ?? '';

        if (empty($timestamp) || empty($appKey) || empty($sign) || empty($version)) {
            throw new SystemException('验签参数异常', 400);
        }

        $conf = SxThirdDBase::getConf();

        if ($appKey != $conf['appKey']) {
            throw new SystemException('appKey参数异常', 400);
        }

        $now = time();
        if (YII_ENV != 'dev' && ($now - intval($timestamp / 10000)) > 5 * 60) {
            throw new SystemException('签名异常', 400);
        }

        $newsign = self::sign([
            'appKey' => $appKey,
            'timestamp' => $timestamp,
            'version' => $version,
            'appSecret' => $conf['appSecret']
        ]);

        if ($newsign != $sign) {
            Tool_Common::log('/sign/'.__FUNCTION__, 'INFO', '验签', ['headers'=>$headers, 'newsign'=>$newsign, 'sign'=>$sign]);
            throw new SystemException('验签失败', 400);
        }

        return true;
    }

    /**
     * 获取签名串
     * @param  array    $param    参数
     * @return string
     */
    public static function getSignStr($params)
    {
        ksort($params);

        $signStrs = [];
        foreach ($params as $key => $value) {
            if ($key == 'sign' || $value === null || $value === '' || is_array($value)) {
                continue ;
            }

            $signStrs[] = $key .'='. $value;
        }

        $signStr = implode('&', $signStrs);

        return $signStr;
    }

    /**
     * 签名
     * @param  array      $params    参数
     * @return string
     */
    public static function sign(array $params) : string
    {
        $signStr = self::getSignStr([
            'appKey' => $params['appKey'],
            'timestamp' => $params['timestamp'],
            'version' => $params['version'],
            'appSecret' => $params['appSecret']
        ]);

        // MD5计算签名
        $sign = md5($signStr);

        return $sign;
    }

}
