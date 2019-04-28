<?php


namespace backend\tools;

use Yii;
use yii\caching\Cache;

class Tools
{

    /*
     * 过滤数组字段
     * @param array $arr 需要过滤的数组
     * @param array $filterfields 保留的字段
     * @return array
     * */
    public static function dofilter($arr, $filterfields){
        if(is_array($arr) && !empty($arr)){
            foreach($arr as $key => $val){
                if(!in_array($key, $filterfields)) unset($arr[$key]);
            }
        }
        return $arr;
    }

    /**
     * @description 获取下一天的日期
     * @param $date 2018-01-01 或者 20180101
     * @param string $split 日期分隔符 ：-或/或''
     * @return mixed
     */
    public static function getNextDate($date, $split = ''){
        $date_str = $date.' '.'00:00:00';
        $nextDate_time = strtotime($date_str)+ 24 * 3600;
        $nextDate = date('Y'.$split.'m'.$split.'d', $nextDate_time);
        if($nextDate == '1970'.$split.'01'.$split.'01') return $date;

        return $nextDate;
    }


}