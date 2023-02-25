<?php
namespace common\tools;
use Yii;

/**
 *  Redis锁操作类
 *  Date:   2016-06-30
 *  Author: fdipzone
 *  Ver:    1.0
 *
 *  Func:
 *  public  lock    获取锁
 *  public  unlock  释放锁
 *  private connect 连接
 */
class RedisLock { // class start

    private $_config;
    public $_redis;

    /**
     * 初始化
     * @param Array $config redis连接设定
     */
    public function __construct(){
        $this->_redis = \Yii::$app->redis;
    }

    /**
     * 获取锁
     * @param  String  $key    锁标识
     * @param  Int     $expire 锁过期时间
     * @return Boolean
     */
    public function lock($key, $expire=5){
        $is_lock = $this->_redis->setnx($key, time()+$expire);

        // 不能获取锁
        if(!$is_lock){
            // 判断锁是否过期
            $lock_time = $this->_redis->get($key);

            // 锁已过期，删除锁，重新获取
            if(time()>$lock_time){
                $this->unlock($key);
                $is_lock = $this->_redis->setnx($key, time()+$expire);
            }
        }

        return $is_lock? true : false;
    }

    /**
     * 释放锁
     * @param  String  $key 锁标识
     * @return Boolean
     */
    public function unlock($key){
        return $this->_redis->del($key);
    }

    public function sadd($key, $val){
        $is_lock = $this->_redis->sadd($key, $val);

        return $is_lock;
    }

    public function srem($key, $val){
        $is_lock = $this->_redis->srem($key, $val);

        return $is_lock;
    }

} // class end
