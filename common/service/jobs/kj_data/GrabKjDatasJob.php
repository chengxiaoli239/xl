<?php
namespace common\service\jobs\kj_data;

use common\service\jobs\CommonJob;
use common\tools\KjDataGet;

class GrabKjDatasJob extends CommonJob {

    public static function getName($params) {
        self::$name = '0开奖数据抓取';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lottery_type = $params['lottery_type'];
        $qihao = $params['qihao']??'';
        $kjData = $params['kj_data']??[];
        try {
            $rst = KjDataGet::insertOneLotteryKjData($qihao, $kjData, $lottery_type);
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return $rst;
    }

}