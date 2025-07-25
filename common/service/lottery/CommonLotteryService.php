<?php

namespace common\service\lottery;

use backend\models\LotteryType;
use common\service\BaseService;
use common\service\cache\CacheKeyService;

class CommonLotteryService extends BaseService
{
    const LOTTERY_TYPE_LUCKY5 = 8;
    const LOTTERY_TYPE_FUCAI = 26;
    const LOTTERY_TYPE_PL3 = 27;
    const LOTTERY_TYPE_AOZHOU5 = 28;
    const THIRDD_LOTTERY_TYPES = [
        self::LOTTERY_TYPE_FUCAI,
        self::LOTTERY_TYPE_PL3,
    ];
    const THIRDD_LOTTERY_OPTIONS = [
        self::LOTTERY_TYPE_FUCAI => '福',
        self::LOTTERY_TYPE_PL3 => '排',
    ];

    const LOTTERY_TYPE_OPTIONS = [
        self::LOTTERY_TYPE_LUCKY5 => '幸运五',
        self::LOTTERY_TYPE_FUCAI => '福',
        self::LOTTERY_TYPE_PL3 => '排',
        self::LOTTERY_TYPE_AOZHOU5 => '澳洲幸运五',
    ];

    # 共用的状态值
    const STATUS_LT_WAIT = 0;
    const STATUS_LT_SUCCESS = 1;
    const STATUS_LT_FAIL = 2;

    const STATUS_LT_CANCEL = 3;
    const STATUS_OPTIONS = [
        self::STATUS_LT_WAIT => '待处理',
        self::STATUS_LT_SUCCESS => '已中奖',
        self::STATUS_LT_FAIL => '未中奖',
        self::STATUS_LT_CANCEL => '已撤单',
    ];

    const VALID_STATUS = [
        self::STATUS_LT_WAIT,
        self::STATUS_LT_SUCCESS,
        self::STATUS_LT_FAIL,
    ];

    public static function getLotteryBaseInfo($lotteryType, $useCache=1)
    {
        $mKey = CacheKeyService::lotteryBaseInfo($lotteryType);
        $lotteryBaseInfo = commonRedis()->get($mKey);
        if(!$useCache OR empty($lotteryBaseInfo)){
            $lotteryBaseInfo = LotteryType::find()->where(['lottery_type'=>$lotteryType])->limit(1)->asArray()->one();
            commonRedis()->setnx($mKey, $lotteryBaseInfo);
            commonRedis()->expire($mKey, 3600);
        }

        return $lotteryBaseInfo;
    }

}
