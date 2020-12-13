<?php

namespace backend\service;

use  yii;

class McLockService extends BaseService {
    const KEY_PREFIX = 'mc_lock';
    private $mc;

    public function __construct(){
        $this->mc = \Yii::$app->cache;
    }

    /**
     * 进行锁操作
     * @param [type]  $lock_id
     * @param integer $expire
     */
    public function Lock($lock_id, $expire=5){
        $mkey = self::KEY_PREFIX . $lock_id;
        for($i = 0; $i < 3; $i++){
            $flag = false;
            try{
                $flag = $this->mc->add($mkey, 1, $expire);
            }catch(\Exception $e){
                $flag = false;
                //log
            }
            if($flag){
                return true;
            }else{
                //wait for 0.3 seconds
                usleep(300000);
            }
        }
        return false;
    }

    /**
     * 判断锁状态
     * @param  [type]  $lock_id
     * @return boolean
     */
    public function isLock($lock_id){
        $mkey = self::KEY_PREFIX.$lock_id;
        $ret = $this->mc->get($mkey);
        if(empty($ret) || $ret === false){
            return false;
        }
        return true;
    }
}