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
    const FT_DA_ID = 99;
    const FT_XIAO_ID = 100;
    const FT_DAN_ID = 101;
    const FT_SHUANG_ID = 102;

    const TYPE_FT_OPTIONS = [
        self::FT_ZHENG_ID => '正',
        self::FT_FAN_ID => '番',
        self::FT_JIAO_ID => '角',
        self::FT_NIAN_ID => '念',
        self::FT_DA_ID => '大',
        self::FT_XIAO_ID => '小',
        self::FT_DAN_ID => '单',
        self::FT_SHUANG_ID => '双',
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
            case strpos($text, '番') !== false:
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
            case strpos($text, '大') !== false:
                $methodId = self::FT_DA_ID;
                break;
            case strpos($text, '小') !== false:
                $methodId = self::FT_XIAO_ID;
                break;
            case strpos($text, '单') !== false:
                $methodId = self::FT_DAN_ID;
                break;
            case strpos($text, '双') !== false:
                $methodId = self::FT_SHUANG_ID;
                break;
            default:
                $d = explode('/', $text);
                if(strlen($d[0]) == 1){
                    $methodId = self::FT_ZHENG_ID;
                }elseif($d[0] == '13'){
                    $methodId = self::FT_DAN_ID;
                }elseif($d[0] == '24'){
                    $methodId = self::FT_SHUANG_ID;
                }elseif(strlen($d[0]) == 2){
                    $methodId = self::FT_JIAO_ID;
                }
                break;
        }

        return [$methodId??0, SscMethod::TYPE_FT_OPTIONS[$methodId]];
    }
}
