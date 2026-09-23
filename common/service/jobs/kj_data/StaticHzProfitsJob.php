<?php
namespace common\service\jobs\kj_data;

use backend\models\SystemConfig;
use backend\service\StaticService;
use common\service\jobs\CommonJob;

class StaticHzProfitsJob extends CommonJob {

    public $retryCount = 1;

    public function getTtr(): int
    {
        return 180;
    }

    public static function getName($params) {
        self::$name = '23四定和值利润';
        return self::$name;
    }

    private static $staticStatus = 0;
    private static function _init() {
        self::$staticStatus = SystemConfig::findOne(['key'=>'static_status'])->value;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        self::_init();
        $lottery_type = (int)$params['lottery_type'];
        $qihao = (string)$params['qihao'];
        if(!self::$staticStatus) return '数据统计开关已关闭';
        if($lottery_type == 23){
            return '高频不处理该数据类型的统计';
        }

        $lockKey = 'static-hz-profits-job:'.$lottery_type.':'.$qihao;
        if (!\Yii::$app->cache->add($lockKey, 1, 300)) {
            return '四定和值利润统计正在执行，本次跳过';
        }

        try {
            return StaticService::opStatic($lottery_type, $qihao); # 和值、四定利润统计
        } finally {
            \Yii::$app->cache->delete($lockKey);
        }
    }

}
