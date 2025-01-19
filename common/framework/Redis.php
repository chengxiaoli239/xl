<?php

namespace common\framework;

use yii\helpers\Inflector;
use yii\redis\Connection;

class Redis extends Connection
{
    const checkCommands = [
        'set',
        'setex',
        'setnx',
        'getset',
        'hmset',
        'hset',
        'hsetnx',
        'lset',
        'psetex',
        'setbit',
        'incr',
        'incrby',
        'zincrby',
        'incrbyfloat',
        'hincrby',
        'hincrbyfloat',
        'decr',
        'decrby',
        'lpush',
        'lpushx',
        'rpush',
        'rpushx',
        'lset',
        'linsert',
        'sadd',
        'zadd',
    ];

    //均已空数组结尾
    const MODULES = [
        //公共模块
        'gg'=>[
            'config'=>[

            ],
            //其他
            'qt' => [

            ],
            //长时间的
            'csj' => [

            ],
            //分布式锁
            'lock' => [

            ],
            'widget' => [
    
            ]
        ],
    ];
    public function __call($name, $params)
    {
        $redisCommand = strtoupper(Inflector::camel2words($name, false));
        if (in_array($redisCommand, self::checkCommands)) {
            if (!$this->checkKey($params[0])) {
                return;
            }
        }

        return parent::__call($name, $params);
    }

    public function get($key)
    {
        $value = parent::get($key);
        $result = unserialize($value);

        return $result;
    }

    public function set($key, $value, ...$options)
    {
        if (!$this->checkKey($key)) {
            return;
        }
        $value = serialize($value);
        return parent::set($key, $value, $options);
    }

    public function setex($key, $seconds, $value)
    {
        $value = serialize($value);
        return parent::setex($key, $seconds, $value);
    }

    public function setnx($key, $value)
    {
        $value = serialize($value);
        return parent::setnx($key, $value);
    }

    public function hset($key, $field, $value)
    {
        $value = serialize($value);
        return parent::hset($key, $field, $value);
    }

    public function hget($key, $field)
    {
        $value = parent::hget($key, $field);
        $result = unserialize($value);
//        if (!empty($value) && empty($result)) {
//            $result = $value;
//        }

        return $result;
    }

    /**
     * redis 锁
     * @return bool
     */
    public static function lock($cacheKey, $time = 5, $isThrowException = true)
    {
        $now = time();
        $expireTime = $now + $time;
        $setResult = (new Redis)->setnx($cacheKey, $expireTime);
        if ($setResult) {
            (new Redis)->expire($cacheKey, $time);
        }
        $result = false;
        if ($setResult || ((new Redis)->get($cacheKey) < $now && (new Redis)->getset($cacheKey, $expireTime) < $now)) {
            $result = true;
        }

        if (! $result && $isThrowException) {
            throw_info('业务处理中，请稍后再试...', 10001);
        }

        return $result;
    }

    /**
     * 锁是否存在.
     * @param string $cacheKey 键
     * @return bool
     */
    public static function exists($cacheKey)
    {
        $exTime = (new Redis)->get($cacheKey);
        if (empty($exTime) || $exTime < time()) {
            return false;
        }

        return true;
    }

    /**
     * 释放锁
     * @param mixed $cacheKey
     */
    public static function clearLock($cacheKey)
    {
        (new Redis)->del($cacheKey);
    }

    public static function lockAndWait($cacheKey, $isThrowException = true, $timeout = 5)
    {
        $count = 0;
        if (!Redis::lock($cacheKey, $isThrowException, $timeout)) {
            while (true) {
                if ($count > $timeout + 2) {
                    return false;
                }
                if (Redis::exists($cacheKey)) {
                    ++$count;
                    sleep(1);
                    continue;
                }
                break;
            }
        }

        return true;
    }

    private function checkKey($key): bool
    {
        return true;
        try {
            //格式校验, 字母
            $arr = explode(':', $key);
            $count = count($arr);
            if ($count <= 2) {
                throw_info('缓存键名不合法');
            }
            if (strlen($key) > 64) {
                throw_info('缓存键名长度不能大于64位');
            }
            $modules = self::MODULES;
            foreach ($arr as $k => $v) {
                if (($k+1) == $count) {
                    continue;
                }
                if (!isset($modules[$v])) {
                    throw_info('缓存键名未配置');
                }
                $modules = $modules[$v];
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
