<?php
/**
 *  核心配置
 * author: wangyegao
 * Date: 2018-02-16
 */
namespace backend\service;

use backend\models\LotteryType;

class Config_Base{
    public static $baseUrl = '';
    public static $playway = [
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8,
        9,
        10,
        11,
        12,
        13,
        14,
        15,
        16
    ];

    /**
     *  下拉筛选
     *  @column string 字段
     *  @value mix 字段对应的值，不指定则返回字段数组
     *  @return mix 返回某个值或者数组
     */
    public static function dropDown ($column, $value = null) {
        $dropDownList = [
            'is_delete'=> [
                '0'=>'显示',
                '1'=>'删除',
            ],
            'is_hot'=> [
                '0'=>'否',
                '1'=>'是',
            ],
            'enable'=> [
                '0'=>'已关闭',
                '1'=>'已开启',
            ],
            'grabDataStatus'=> [
                '0'=>'已关闭',
                '1'=>'已开启',
            ],
            //有新的字段要实现下拉规则，可像上面这样进行添加
            // ......
        ];
        if ($value !== null){
            //根据具体值显示对应的值
            return array_key_exists($column, $dropDownList) ? $dropDownList[$column][$value] : false;
        } else{
            //返回关联数组，用户下拉的filter实现
            return array_key_exists($column, $dropDownList) ? $dropDownList[$column] : false;
        }
    }

    /**
     * @desc 彩种列表
     * @return array
     */
    public static function lotteryTypeLists($l_type = null){
        $datas = [];
        $lottery_types = LotteryType::find()->asArray()->all();
        foreach ($lottery_types as $lottery_type){
            $datas[$lottery_type['lottery_type']] = $lottery_type['name'];
        }
        if($l_type != null){
            return isset($datas[$l_type]) && !empty($datas[$l_type]) ? $datas[$l_type] : false;
        }

        return $datas;
    }
}