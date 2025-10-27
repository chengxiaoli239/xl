<?php
namespace common\service\jobs\kj_data;

use backend\tools\Tools;
use common\service\jobs\CommonJob;
use common\tools\KjDataGet;
use common\tools\Tool_Common;

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
            $dateHI = date('H:i:s');
            if($lottery_type==DEFAULT_LOTTERY_TYPE && '05:01:30'<$dateHI && $dateHI<'08:05:35'){
                return '幸运五非抓取数据时间节点';
            }
            $openCode = $params['opencode'];
            if($openCode == $qihao){
                return '推送开奖数据格式问题：期号和开奖号码一致';
            }
            $rst = KjDataGet::insertOneLotteryKjData($lottery_type, $qihao, $kjData);
        }catch (\Exception $e){
            return $e->getMessage().'_'.$e->getFile();
        }
        return $rst;
    }

}
