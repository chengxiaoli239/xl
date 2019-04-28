<?php
/**
 * Created by PhpStorm.
 * User: mjf
 * Date: 2017/5/17
 * Time: 10:00
 */

class CacheOp {
    public static $KEY_RECOMD = '123';

    /*
     * 设置缓存
     */
    public function setSysCache($key,$data,$cachetime = 3600){
        if(YII_ENV == 'prod')//只有在线上,才生效.测试状态失效
            Yii::$app->cache->set($key, $data,$cachetime);
    }

    /*
     * 获取缓存
     */
    public function getSysCache($key){
        $re = Yii::$app->cache->get($key);
        return $re;
    }

    /*
     *删除系统缓存
     */
    public function delSysCache($key){
        $re = Yii::$app->cache->delete($key);
        return $re;
    }

    /*
     *删除所有的系统缓存
     */
    public function delAllSysCache(){
        $re = Yii::$app->cache->flush();
        return $re;
    }




}