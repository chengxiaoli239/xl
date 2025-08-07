<?php
namespace common\service\jobs\kj_data;

use backend\service\TzService;
use common\service\jobs\CommonJob;

class OperateBetPlans extends CommonJob {

    public static function getName($params) {
        self::$name = '1处理下注计划';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lottery_type = $params['lottery_type'];
        $qihao = $params['qihao']?:''; # 要处理的开奖期号
        $ignore = $params['ignore']?:0; # 是否忽略缓存
        try {
            $rst = TzService::operateSystemBetPlans($lottery_type, $qihao, $ignore); # 处理系统投注计划，更新统计数据、
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return $rst;
    }

}