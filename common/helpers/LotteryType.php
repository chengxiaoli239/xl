<?php
namespace common\helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class LotteryType
{
    const HN_SEVEN = 1;
    const LUCKY_5 = 8;
    const PL_5 = 17;
    const JS_SEVEN = 25;
    const FC_3D = 26;
    const PL_3D = 27;
    const AZ_LUCKY_5 = 28;

    const TYPE_OPTIONS = [
        self::HN_SEVEN => '七星',
        self::LUCKY_5 => '幸运五',
        self::PL_5 => '排列五',
        self::FC_3D => '福',
        self::PL_3D => '排',
        self::JS_SEVEN => '七位数',
        self::AZ_LUCKY_5 => '澳洲五',
    ];

    public static function getName($lottery_type=DEFAULT_LOTTERY_TYPE): string
    {
        return self::TYPE_OPTIONS[$lottery_type]??'未知彩种';

    }
}
