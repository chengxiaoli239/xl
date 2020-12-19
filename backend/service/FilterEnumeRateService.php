<?php
/**
 * Desc 枚举类型service
 * Date: 2018/12/10
 * Time: 17:28
 */

namespace backend\service;

use yii\helpers\ArrayHelper;

class FilterEnumeRateService extends BaseService {

    /**
     * @desc 类型名称
     * @param $type
     * @return mixed
     */
    public static function getPlayWayTxt($type){
        $types = self::getPlayWays();

        return $types[$type];
    }

    /**
     * @desc 定位类型
     * @return array
     */
    public static function getPlayWays(){

        $datas = [
            1 => '二定',
            2 => '三定',
            3 => '四定',
        ];

        return $datas;
    }

}