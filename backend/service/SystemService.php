<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\SystemConfig;
use  yii;

class SystemService{

    /**
     * 彩种是否初始化
     * @param int $lottery_type
     * @return string
     */
    public static function getInitLotteryDataKey($lottery_type=DEFAULT_LOTTERY_TYPE){
        return 'getInitLotteryDataKey_x1_'.$lottery_type;
    }

    /**
     * @desc 数据统计key
     * @param $key
     * @param int $lottery_type
     * @return string
     */
    public static function initLottery($lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = SystemService::getInitLotteryDataKey($lottery_type);
        $flag = $m->get($mkey);
        if(!$flag){
            $m->set($mkey, 1, 3*3600);
        }else{
            $m->delete($mkey);
        }

        $rst = HN0898Service::insertDsYl($lottery_type);

        return $rst;
    }

    /**
     * @desc 删除下注数据
     * @param $key
     * @param int $lottery_type
     * @return string
     */
    public static function delBetRecord($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        $tables = [
            'lt_betting_records', 'lt_bet_error_plans_task'
        ];

        foreach ($tables as $table){
            $sql = 'DELETE FROM '.$table. ' WHERE lottery_type='.$lottery_type;
            $rst['data'][$table] = \Yii::$app->db->createCommand($sql)->execute();//p($rst);
        }

        return $rst;
    }

    /**
     * @desc 生成配置缓存key
     * @param $key
     */
    public static function buildSystemConfigCacheKey($key = 'getDataType'){
        return 'SYSTERM_CONFIG_TYPE_'.$key;
    }

    /**
     * @desc 清理配置缓存
     * @param $key
     */
    public static function clearConfigCache($key){
        $m = \Yii::$app->cache;
        $mkey = self::buildSystemConfigCacheKey($key);

        $m->delete($mkey);
    }

    /**
     * @desc 计算遗漏获取数据类型 1取本表数据做变更0扫表重新计算数据（比如：遗漏、数量等统计）
     * @return int
     */
    public static function getConfig($key = 'getDataType'){
        $m = \Yii::$app->cache;
        $mkey = SystemService::buildSystemConfigCacheKey($key);
        if($val = $m->get($mkey)) return $val;

        $val = SystemConfig::findOne(['key'=>$key])->value;
        $m->set($mkey, $val, \Yii::$app->params['BASE_DATA_CACHE_TIME']);

        return $val;
    }

}