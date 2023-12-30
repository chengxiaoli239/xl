<?php

namespace backend\models\thirdD;

use common\models\thirdD\Bets;

class BetsBackend extends Bets
{
    const LOTTERY_FUCAI = 26;
    const LOTTERY_PL3 = 27;
    const LOTTERYS = [
        self::LOTTERY_FUCAI => '福',
        self::LOTTERY_PL3 => '排',
    ];

    const PUSH_STATUS_WAIT = 0;
    const PUSH_STATUS_SUCCESS = 2;
    const PUSH_STATUS_FAIL = 3;
    const PUSH_STATUS_CANNOT = 4;
    const PUSH_STATUS_OPTIONS = [
        self::PUSH_STATUS_WAIT => '待推',
        self::PUSH_STATUS_SUCCESS => '成功',
        self::PUSH_STATUS_FAIL => '失败',
        self::PUSH_STATUS_CANNOT => '不可推',
    ];
    const PUSH_STATUS_CLASSES = [
        self::PUSH_STATUS_WAIT => 'grey',
        self::PUSH_STATUS_SUCCESS => 'green',
        self::PUSH_STATUS_FAIL => 'red',
        self::PUSH_STATUS_CANNOT => '#71125a',
    ];

    const HAS_REPLY_NO = 0;
    const HAS_REPLY_YES = 2;
    const HAS_REPLY_FAIL = 3;
    const HAS_REPLY_IGNORE = 4;
    const HAS_REPLY_YES_RE = 5;
    const HAS_REPLY_OPTIONS = [
        self::HAS_REPLY_NO => '未回复',
        self::HAS_REPLY_YES => '成功',
        self::HAS_REPLY_FAIL => '失败',
        self::HAS_REPLY_IGNORE => '忽略',
        self::HAS_REPLY_YES_RE => '确认', # 成功，再次打包确认
    ];

    const REPLY_TYPE_QUICK = 0;
    const REPLY_TYPE_PACKAGE = 1;
    const REPLY_TYPE_OPTIONS = [
        self::REPLY_TYPE_QUICK => '即时',
        self::REPLY_TYPE_PACKAGE => '打包',
    ];

    # 是否需要确认
    const NEED_CONFIRM_NO = 0;
    const NEED_CONFIRM_YES = 1;
    const NEED_CONFIRM_OPTIONS = [
        self::NEED_CONFIRM_NO => '无需',
        self::NEED_CONFIRM_YES => '需',
    ];
}
