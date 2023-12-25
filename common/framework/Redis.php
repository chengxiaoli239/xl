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
