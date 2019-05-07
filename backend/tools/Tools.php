<?php


namespace backend\tools;

use backend\models\SystemConfig;
use common\service\CaptchaCodeService;
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

    /**
     * @desc 调用验证码接口
     * @param $uid
     * @param $tz_system_id
     * @param $cookie_key
     * @return mixed
     */
    public static function getCaptchaCode($uid, $tz_system_id, $cookie_key){
        $captcha_code_api = SystemConfig::findOne(['key'=>'captcha_code_api'])->value;
        $filename = Yii::$app->basePath . "/runtime/captcha/".$uid."_".$tz_system_id.'_'.$cookie_key.".png";
        switch ($captcha_code_api){
            case 1:
                $codeRst = CaptchaCodeService::juHe($filename); # 聚合接口
                break;
            case 2:
                $codeRst = CaptchaCodeService::showApi($filename); # 万维易源
                break;
            case 3:
                $codeRst = CaptchaCodeService::jianjiao($filename); # 尖叫数据
                break;
            case 4:
                $codeType = $tz_system_id == 5 ? '6001' : '1902';
                $codeRst = CaptchaCodeService::chaojiying($filename, $codeType); # 超级鹰
                break;
            default:break;
        }

        return $codeRst;
    }


}