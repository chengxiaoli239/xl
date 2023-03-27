<?php
namespace common\service\jobs\kj_data;

use backend\models\SystemConfig;
use backend\service\StaticService;
use common\service\jobs\CommonJob;

class StaticHzProfitsJob extends CommonJob {

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
        try {
            self::_init();
            $lottery_type = $params['lottery_type'];
            if(!self::$staticStatus) return ['status'=> 300, 'msg'=>'数据统计开关已关闭'];
            if($lottery_type == 23){
                $rst = '高频不处理该数据类型的统计';
            }else{
                $rst = StaticService::opStatic([$lottery_type]); # 和值、四定利润统计
            }
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return $rst;
    }

}