<?php


namespace common\tools;

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
     * 支持同时检索多个拆分成数组
     */
    public static function getQuerySplit( $value )
    {
        $values = preg_replace('/\s+/','#',$value);
        $values = str_replace(array("\r\n", "\r", "\n",",",'，'), "#", $values);

        return explode('#',$values);
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

    /**
     * @description 获取下一月月份
     * @param $month 2018-01 或者 201801
     * @param string $split 日期分隔符 ：-或/或''
     * @return mixed
     */
    public static function getNextMonth($month, $split = ''){
        $month_str = $month.'-01 '.'00:00:00';
        $nextMonth = date('Y'.$split.'m', strtotime($month_str.' +1 month'));
        if($nextMonth == '1970'.$split.'01') return $month;

        return $nextMonth;
    }

    /**
     * @description 获取上一天的日期
     * @param $date 2018-01-01 或者 20180101
     * @param string $split 日期分隔符 ：-或/或''
     * @return mixed
     */
    public static function getBeforeDate($date, $split = ''){
        $date_str = $date.' '.'00:00:00';
        $nextDate_time = strtotime($date_str) - 24 * 3600;
        $beforeDate = date('Y'.$split.'m'.$split.'d', $nextDate_time);
        if($beforeDate == '1970'.$split.'01'.$split.'01') return $date;

        return $beforeDate;
    }

    /**
     * @function 字符串替换：图片src没有域名时前面连接img域
     * @param $str
     * @param string $pattern
     * @param $srcJoinStr
     * @return mixed
     */
    public static function imgSrc_replace($str,$pattern ='<img.*?src="(.*?)">',$srcJoinStr = 'https://img.mianshui365.com'){
        if(!$str) return false;
        preg_match_all($pattern,$str,$matches);

        foreach ($matches[1] as $key=>$src) {
            if (strstr($src, 'https:') OR strstr($src, '@90q_1wh')) continue;
            # 1、格式：/upload/20150430/08514725025.jpg
            if (!strstr($src, 'http')) {
                $str = str_replace($src, $srcJoinStr . $src, $str);
            }
            # 2、http转https
            if (strstr($src, 'http:')) {
                $str = str_replace('http:', 'https:', $str);
            }
            # 3 image 转img
            if (strstr($src, 'images.')) {
                $str = str_replace('images.', 'img.', $str);
            }

            # 4 woaihoutai9507123 转img
            if (strstr($src, 'woaihoutai9507123.')) {
                $str = str_replace('woaihoutai9507123.', 'img.', $str);
            }

            # 5 www 转img
            if (strstr($src, 'www.')) {
                $str = str_replace('www.', 'img.', $str);
            }

            # 6、jpg 图后面加 @90q_1wh
            if (strstr($str, '.jpg') && !strstr($str, '@90q_1wh')) {
                $str = str_replace('.jpg', '.jpg@90q_1wh', $str);
            }
        }

        return $str;

    }


}