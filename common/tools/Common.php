<?php
/**
 * Description
 *
 *
 * Datetime: 2021-09-03 14:28
 */

namespace common\tools;


use common\models\Cache;
use common\services\config\ConfigService;
use common\services\jobs\cache\ClearCacheJob;
use common\services\jobs\ProgramMiniMessageJob;

class Common
{
    public static $disableBrandIds = [];

    public static function jsonSuccess($data, $message = 'ok', $code = 0)
    {
        $result = $data;
        $result['msg'] = $message;
        $result['code'] = $code;

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $result;
    }

    public static function jsonError($data, $message = 'ok', $code = 400)
    {
        $result = $data;
        $result['msg'] = $message;
        $result['code'] = $code;

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $result;
    }

    public static function getPageParams($params, $defaultPageSize = 20)
    {
        if (!empty($params['limit'])) {
            $params['page_size'] = $params['limit'];
        }
        $page = intval($params['page']);
        $page = $page > 0 ? $page : 1;
        $pageSize = intval($params['page_size']);
        $pageSize = $pageSize > 0 ? $pageSize : $defaultPageSize;

        $offset = ($page - 1) * $pageSize;

        return [$page, $pageSize, $offset];
    }

    public static function getWithCache($cacheKey, callable $callback, $seconds = 120, $isUpdate = false)
    {
        $cache = \Yii::$app->cache;

        if (!$cache->exists($cacheKey) || empty($cache->get($cacheKey)) || $isUpdate) {
            $string = call_user_func($callback);
            if (!is_string($string)) {
                $string = json_encode($string, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            }
            $cache->set($cacheKey, $string, $seconds);
        }

        $data = $cache->get($cacheKey);

        return $data;
    }

//    /**
//     * 获取redis对象
//     * @param int $database
//     * @return \Redis
//     */
//    public static function redis($database = 0)
//    {
//        $client = new \Redis();
//        $client->connect(\Yii::$app->params['REDIS']['hostname'], \Yii::$app->params['REDIS']['port']);
//        $client->auth(\Yii::$app->params['REDIS']['password']);
//
//        return $client;
//    }

    public static function buildStrToArr($param)
    {
        $param = trim($param);
        if (strpos($param, "\r\n") !== false) {
            $arr = explode("\r\n", $param);
        } else {
            $arr = explode("\n",$param);
        }

        foreach ($arr as $k => $v) {
            $v = trim($v);
            if (empty($v)) {
                unset($arr[$k]);
                continue;
            }
        }
        $arr = array_values($arr);

        return $arr;
    }

    public static function convertUrlQuery($query)
    {
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }

    public static function getPlatformId()
    {
        $platformId = '';
        if (method_exists(\Yii::$app->request, 'post')) {
            $platformId = \Yii::$app->request->post('platformId');
            if (empty($platformId)) {
                $platformId = \Yii::$app->request->get('platformId');
            }
        }

        if (empty($platformId)) {
            $platformId = $_REQUEST['platformId'] ?? '';
        }


        return $platformId;
    }

    public static function deleteCacheWithPlatform($key)
    {
        foreach (\Yii::$app->params['PLATFROMID'] as $pfid => $name) {
            if (!in_array($pfid, [1,2,4,5,6,8])) {
                continue;
            }
            $newKey = $key . '_' . $pfid;
            \Yii::$app->cache->delete($newKey);
        }
    }

    public static function resetCacheWithPlatform($callback)
    {
        foreach (\Yii::$app->params['PLATFROMID'] as $pfid => $name) {
            if (!in_array($pfid, [1,2,4,5,6,8])) {
                continue;
            }
            $_REQUEST['platformId'] = $pfid;
            call_user_func($callback, $pfid);
        }
    }

    public static function clearCache($params)
    {
        push_queue(ClearCacheJob::class, $params);
    }

    /**
     * 发送小程序消息
     * @param $params
     */
    public static function sendProgramMiniMessage($params)
    {
        push_queue(ProgramMiniMessageJob::class, $params);
    }

    /**
     * 获取平台名称
     * @param $platformId
     * @return string
     */
    public static function getPlatformName($platformIds)
    {
        $platforms = [
            1=>'H5',
            2=>'微信',
            4=>'安卓',
            5=>'IOS',
            6=>'支付宝',
            8=>'京东',
            99 => '抖店',
            100 => '开放平台',
            101 => '快手',
        ];
        if (!is_array($platformIds)) {
            $platformIds = [$platformIds];
        }

        $names = [];
        foreach ($platformIds as $platformId) {
            $names[] = $platforms[$platformId] ?? '';
        }

        return join(',', $names);
    }

    /**
     * 获取促销活动平台名称
     * @param $platformId
     * @return string
     */
    public static function getCuxiaoPlatformName($platformId)
    {
        $name = '全平台';
        if ($platformId == 2) {
            $name = '京东';
        }
        if ($platformId == 6) {
            $name = '支付宝';
        }

        return $name;
    }

    /**
     * 获取促销活动类型
     * @param $type
     * @return mixed|string
     */
    public static function getActivityTypeName($type)
    {
        $types = [
            1 =>'折扣',
            2 =>'满减',
            3 =>'满件折',
            4 =>'每满减',
            5 =>'一口价',
            6 =>'预售',
            7 => '满返',
            8 => '每满返',
            9 => '满额赠',
            -1 =>'无活动商品',
            -2 =>'预付商品',
        ];

        return $types[$type] ?? '';
    }

    public static function addGoodsCoverWithLogo($mainImage)
    {
        $logo = __DIR__ . '/../../backend/web/images/cover2.png';
        $mainImage = \Yii::$app->params['IMG_DOMAIN'] . $mainImage;
        $logoImgObj = imagecreatefrompng($logo);
        $mainImageObj = imagecreatefrompng($mainImage);
        $filename = '/tmp/' .get_unique_id() . '.png';

        $finalImg = imagecreatetruecolor(800, 800);
        imagealphablending($finalImg, true);
        imagesavealpha($finalImg, true);
        imagecopy($finalImg, $mainImageObj, 0, 0, 0, 0, 800, 800);
        imagecopy($finalImg, $logoImgObj, 0, 0, 0, 0, 800, 800);

        imagepng($finalImg, $filename);
        imagedestroy($finalImg);
        imagedestroy($logoImgObj);
        imagedestroy($mainImageObj);

        $destFile = \common\services\Util::strname() . "." . 'png';
        $result = \common\services\Util::doalioss($destFile, $filename);
        if (!$result[0]) {
            return '';
        }
        unlink($filename);

        return \api\tools\Util::resizeImg($destFile);
    }

    public static function cacheWithTable($key, $value, $expireTime)
    {
        $insertData = [];
        $insertData['key'] = $key;
        $insertData['value'] = $value;
        $insertData['expire_time'] = date('Y-m-d H:i:s', time()+$expireTime);

        Cache::deleteAll(['key'=>$key]);
        \Yii::$app->db->createCommand()->insert('cache', $insertData)->execute();
    }

    public static function getCacheWithTable($key, $callback, $expireTime)
    {
        $data = Cache::find()->andWhere(['key'=>$key])
            ->andWhere(['>', 'expire_time', date('Y-m-d H:i:s')])
            ->select('value')->scalar();
        if (empty($data)) {
            $data = call_user_func($callback);
            if (!is_string($data)) {
                $data = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            }
            self::cacheWithTable($key, $data, $expireTime);
        }

        return $data;
    }

    public static function checkLock($cacheKey, $isThrowException = true)
    {
        $now = time();
        $expireTime = $now + 20;
        $redis = \Yii::$app->redis;
        $setResult = $redis->setnx($cacheKey, $expireTime);
        $isLocked = true;
        if ($setResult || ($redis->get($cacheKey) < $now && $redis->getset($cacheKey, $expireTime) < $now)) {
            $isLocked = false;
        }

        if ($isLocked && $isThrowException) {
            throw_info('业务处理中，请稍后再试...');
        }

        return $isLocked;
    }

    public static function filterEmoji($str)
    {
        $str = preg_replace_callback('/./u', function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        }, $str);
        return $str;
    }

    public static function checkSign()
    {
        if (YII_ENV != 'prod') {
            return;
        }
        $headers = \Yii::$app->request->getHeaders()->toArray();
        if (\Yii::$app->request->isPost) {
            $params = \Yii::$app->request->post();
        } else {
            $params = \Yii::$app->request->get();
        }
        if (empty($headers['tf'][0]) || empty($headers['va'][0])) {
           throw_info('非法请求1', 40301);
        }

        $timestamp = $headers['tf'][0];
        $apiSign = $headers['va'][0];

        if (Common::getPlatformId() == 1) {
            # h5签名，这里传的是毫秒
            if (time() - $headers['tf'][0] > 40000) {
                throw_info('非法请求2', 40301);
            }
            $result = self::checkSignV2($params, $apiSign, $timestamp);
        } else {
            if (time() - $headers['tf'][0] > 40) {
                throw_info('非法请求2', 40301);
            }
            if (strpos($apiSign, '.') !== false) {
                # 是否是旧的签名方式
                $singArr = explode('.', $apiSign);
                $signKey = $singArr[0];
                $randomStr = substr($singArr[1], -12, 12);
                $realSign = substr($singArr[1], 0, 32);
                if (empty(\Yii::$app->params['SIGN_KEY'][$signKey])) {
                    throw_info('非法请求4', 40301);
                }
                if (!empty(\Yii::$app->cache->get($realSign))) {
                    throw_info('非法请求5', 40301);
                }
                $secret = \Yii::$app->params['SIGN_KEY'][$signKey];
                $mySign = md5($signKey.$secret.$randomStr.$timestamp);
                $result = $mySign == $realSign;
                if ($result) {
                    \Yii::$app->cache->set($realSign, 1, 60);
                }
            } else {
                # 兼容旧签名方式
                $sign = md5(\Yii::$app->params['PLATFORM_INFO']['APPID'] . \Yii::$app->params['PLATFORM_INFO']['SECRET'] . $headers['tf'][0]);
                $result = $sign == $apiSign;
            }
        }

        if (!$result) {
           throw_info('非法请求3', 40301);
        }
    }

    public static function checkSignV2($params, $sign, $timestamp)
    {
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                unset($params[$k]);
            }
        }

        $timestamp = intval($timestamp) + 86400;
        $randomStr = substr($sign, -12, 12);
        $temp = substr($sign, 0, strlen($sign)-12);
        $start = substr($temp, -2, 1);
        $realSign = substr($temp, $start, 32);

        if (!empty(\Yii::$app->cache->get($realSign))) {
            throw_info('非法请求4', 40301);
        }

        $params['onceString'] = $randomStr;
        ksort($params);
        $paramsStr = strtolower(urldecode(http_build_query($params))).\Yii::$app->params['PLATFORM_INFO_NEW']['SECRET'].$timestamp;
        $mySign = md5($paramsStr);

        $result = $mySign == $realSign;

        if ($result) {
            \Yii::$app->cache->set($realSign, 1, 60);
        }

        return $result;
    }

    public static function getDisableBrandIds($platformId)
    {
//        return [];
        if (!is_cli()) {
            $uris = [
                'order/order/buy',
                'order/order/add-cart',
                'cart/cart/toconform',
//                'goods/goods/get-goods-info',
                'goods/goods/get-promotion-info',
                'cart/cart/add',
                'cart/cart/cartlist',
                'cart/cart/count',
                'coupon/coupon/ordercoupon',
                'order/order/available-point',
                'bonus/bonus/get-bonus-valid-amount',
                'special/special/get-package-info',
            ];
            $uri = \Yii::$app->request->getPathInfo();
            if (in_array($uri, $uris)) {
                return [];
            }
            $specialUri = 'special/special/get-country-special';
            if ($uri == $specialUri) {
                $specialCodes = ConfigService::getConfigByKey('jingdong_show_special_code');
                if (!empty($specialCodes)) {
                    $specialCodes = explode(',', trim($specialCodes));
                }
                if (in_array(\Yii::$app->request->post('code'), $specialCodes)) {
                    return [];
                }
            }
        }

        if (isset(self::$disableBrandIds[$platformId])) {
            return self::$disableBrandIds[$platformId];
        }

        self::$disableBrandIds[$platformId] = [];
        if ($platformId == 8) {
            self::$disableBrandIds[$platformId] = [59,74,33,70,94,43,85,57,91,62,42,52,75];
        }
//        $value = ConfigService::getConfigByKey('platform_disable_brand');
//        $value = json_decode($value, true);

        return self::$disableBrandIds[$platformId];
    }
    public static function getPublicPP() {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.ipify.org');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $pp = curl_exec($curl);

        curl_close($curl);
        Tool_Common::log('/tools/'.__FUNCTION__, 'INFO', '获取新xp', ['pp'=>$pp]);
        return $pp;
    }
}
