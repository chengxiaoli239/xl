<?php

namespace common\service\thirdD;

use common\service\BaseService;

class CommonBaseService extends BaseService
{
    const CODE_FOR_USER = 33333;

    # lottery_type:26 福彩3d、27 排列三
    const LOTTERY_TYPE_FUCAI = 26;
    const LOTTERY_TYPE_PL3 = 27;
    const THIRDD_LOTTERY_TYPES = [
        self::LOTTERY_TYPE_FUCAI,
        self::LOTTERY_TYPE_PL3,
    ];
    const THIRDD_LOTTERY_OPTIONS = [
        self::LOTTERY_TYPE_FUCAI => '福',
        self::LOTTERY_TYPE_PL3 => '排',
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
