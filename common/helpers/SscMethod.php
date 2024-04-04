<?php
namespace common\helpers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class SscMethod
{
    const FT_ZHENG_ID = 95;
    const FT_FAN_ID = 96;
    const FT_JIAO_ID = 97;
    const FT_NIAN_ID = 98;
    const FT_DS_ID = 99;
    const FT_DX_ID = 100;

    const TYPE_FT_OPTIONS = [
        self::FT_ZHENG_ID => '正',
        self::FT_FAN_ID => '番',
        self::FT_JIAO_ID => '角',
        self::FT_NIAN_ID => '念',
        self::FT_DS_ID => '单双',
        self::FT_DX_ID => '大小',
    ];

    const TYPE_DS_OPTIONS = [
        '13' => '单',
        '24' => '双',
    ];

    public static function getName($lottery_type=DEFAULT_LOTTERY_TYPE): string
    {
        return self::TYPE_FT_OPTIONS[$lottery_type]??'未知玩法';
    }


    /**
     * 匹配玩法
     * @param $text - 单个下注文本
     * @return array
     */
    public static function getMethod($text): array
    {
        switch (true){
            case strpos($text, '番') !== false OR strpos($text, '高') !== false:
                $methodId = self::FT_FAN_ID;
                break;
            case strpos($text, '正') !== false:
                $methodId = self::FT_ZHENG_ID;
                break;
            case strpos($text, '念') !== false:
                $methodId = self::FT_NIAN_ID;
                break;
            case strpos($text, '角') !== false:
                $methodId = self::FT_JIAO_ID;
                break;
            case strpos($text, '单') !== false OR strpos($text, '双') !== false:
                $methodId = self::FT_DS_ID;
                break;
            case strpos($text, '大') !== false OR strpos($text, '小') !== false:
                $methodId = self::FT_DX_ID;
                break;
            default:
                $d = explode('/', $text);
                if(strlen($d[0]) == 1){
                    $methodId = self::FT_ZHENG_ID;
                }elseif($d[0] == '13' OR $d[0] == '24'){
                    $methodId = self::FT_DS_ID;
                }elseif(strlen($d[0]) == 2){
                    $methodId = self::FT_JIAO_ID;
                }
                break;
        }

        return [$methodId??0, SscMethod::TYPE_FT_OPTIONS[$methodId]];
    }
}
