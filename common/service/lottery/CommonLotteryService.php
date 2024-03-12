<?php

namespace common\service\lottery;

use backend\models\TzSystems;
use backend\models\TzSystemsUsers;
use common\models\thirdD\LocalToSiteMethod;
use common\service\BaseService;

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

}
