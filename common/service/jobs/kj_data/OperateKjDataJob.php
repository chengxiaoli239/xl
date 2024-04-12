<?php
namespace common\service\jobs\kj_data;

use common\service\jobs\CommonJob;
use common\tools\KjDataGet;
use common\tools\Tool_Common;

class OperateKjDataJob extends CommonJob {

    public static function getName($params): string
    {
        self::$name = '30-接收开奖数据后处理';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lotteryType = $params['lottery_type'];
        $kjData = $params['kj_data'];
        try {
            $qiHao = $kjData['expect'];
            $kjCode = $kjData['opencode'];
            $openTime = $kjData['opentime'];

            $rst = KjDataGet::insertKjData($qiHao, $lotteryType, $kjCode, $openTime);
            Tool_Common::log('/kj_data/'.self::class_basename(__CLASS__), 'INFO', '开奖数据接收', [
                'kjData' => $kjData,
                'lottery_type' => $lotteryType,
                'rst' => $rst,
            ]);
            if($rst['status'] != 200){
                throw_info('非最新开奖数据不处理业务');
            }
            KjDataGet::afterKj($lotteryType); # 处理系统投注计划，更新统计数据
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return true;
    }

}