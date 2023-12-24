<?php
namespace common\service\cache;

use common\service\BaseService;
use common\service\cache\keys\message\MessageCacheKeyTrait;
use common\service\cache\keys\message\MessageReplyCacheKeyTrait;

class CacheKeyService extends BaseService
{
    use MessageReplyCacheKeyTrait;
    use MessageCacheKeyTrait;

    /**
     * 获取缓存
     *
     * @param string $key
     * @return mixed
     */
    public static function getCache(string $key)
    {
        if(empty($key)){
            return null;
        }
        return \Yii::$app->cache->get($key);
    }


    /**
     * 设置缓存
     *
     * @param string $key
     * @param mixed $data
     * @param integer $expire
     * @return void
     */
    public static function setCache(string $key, $data, int $expire=86400)
    {
        if(empty($key)){
            return;
        }

        \Yii::$app->cache->set($key, $data, $expire);
    }


}
