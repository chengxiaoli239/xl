<?php
namespace common\tools;
use Yii;

/**
 *  Redis锁操作类
 *  Date:   2016-06-30
 *  Ver:    1.0
 *
 *  Func:
 *  public  lock    获取锁
 *  public  unlock  释放锁
 *  private connect 连接
 */
class RedisLock { // class start

    public $_redis;

    /**
     * 初始化
     */
    public function __construct(){
        $this->_redis = \Yii::$app->redis;
    }

    /**
     * 获取锁
     * @param String $key    锁标识
     * @param Int $expire 锁过期时间（秒）
     * @return Boolean
     */
    public function lock(string $key, int $expire=5): bool
    {
        // 使用原子操作 SET key value EX expire NX 确保锁会自动过期
        // 这样即使进程卡死，锁也会在expire秒后自动释放
        $lockValue = time() + $expire;
        
        // 尝试使用原子操作获取锁（SET NX EX）
        // 如果key不存在，设置key并设置过期时间
        $is_lock = $this->_redis->executeCommand('SET', [$key, $lockValue, 'NX', 'EX', $expire]);
        
        // 如果获取锁失败，检查锁是否已过期（兼容旧逻辑）
        if(!$is_lock){
            $lock_time = $this->_redis->get($key);
            
            // 锁已过期，删除锁，重新获取
            if($lock_time && time() > $lock_time){
                $this->unlock($key);
                // 再次尝试获取锁
                $is_lock = $this->_redis->executeCommand('SET', [$key, time() + $expire, 'NX', 'EX', $expire]);
            }
        } else {
            // 获取锁成功，确保设置了过期时间（双重保险）
            $this->_redis->expire($key, $expire);
        }

        return (bool)$is_lock;
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
        return $this->_redis->sadd($key, $val);
    }

    public function srem($key, $val){
        return $this->_redis->srem($key, $val);
    }

} // class end
