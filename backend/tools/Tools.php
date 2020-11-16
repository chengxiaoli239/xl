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
    public static function getCaptchaCode($uid, $tz_system_id, $cookie_key, $code_type = 6001){
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
                if($code_type){
                    $codeType = $code_type;
                }else{
                    $codeType = in_array($tz_system_id, [5, 6]) ? '6001' : '1902';
                }
                $codeRst = CaptchaCodeService::chaojiying($filename, $codeType); # 超级鹰
                break;
            default:break;
        }

        return $codeRst;
    }


    /**
     * 使用PHP检测能否ping通IP或域名
     * @param $address
     * @return array
     */
    public static function pingAddress($address = '') {
        if(empty($address)) return [];
            $status = -1;
        if (strcasecmp(PHP_OS, 'WINNT') === 0) {
            // Windows 服务器下
            $result = exec("ping -n 1 {$address}", $outcome, $status);
        } elseif (strcasecmp(PHP_OS, 'Linux') === 0) {
            // Linux 服务器下
            $result = exec("ping -c 1 {$address}", $outcome, $status);
            //p(['address'=>$address, 'outcome'=>$outcome, 'status'=>$status]);
        }
        return ['address'=>$address, 'outcome'=>$outcome, 'status'=>$status, 'result'=>$result];
    }

    /**
     * @desc 获取ping域名对应的ip和延迟信息
     * @param string $address
     * @return array
     */
    public static function getPingAddressInfo($address = ''){
        $rst = Tools::pingAddress($address);
        //p($rst);
        $status = $rst['status'];
        if (0 != $status) {
            return [];
        }

        $outcome = $rst['outcome'];
        $d = $outcome[1];

        # 取IP
        preg_match("/\(.*?\)/i", $d, $matches1);
        $ip = str_replace(['(', ')'], '', $matches1[0]); # 域名对应的ip

        # 延迟
        $pos = strpos($d, "time=");
        $ms = substr($d, $pos+5);
        //p(['rst'=>$rst, 'd'=>$d, 'ms'=>$ms, 'ip'=>$ip]);

        return ['address'=>$rst['address'], 'ip'=>$ip, 'ms'=>$ms];
    }

    /**
     * 使用PHP检测能否telnet通IP或域名
     * @param $address
     * @return array
     */
    public static function telnetAddress($address = '') {
        if(empty($address)) return [];
        $status = -1;
        if (strcasecmp(PHP_OS, 'WINNT') === 0) {
            // Windows 服务器下
            $result = exec("telnet -n 1 {$address}", $outcome, $status);
        } elseif (strcasecmp(PHP_OS, 'Linux') === 0) {
            // Linux 服务器下
            $result = exec("telnet -c {$address}", $outcome, $status);
            //p(['address'=>$address, 'outcome'=>$outcome, 'status'=>$status, 'result'=>$result]);
        }
        return ['address'=>$address, 'outcome'=>$outcome, 'status'=>$status, 'result'=>$result];
    }

    /**
     * @desc 获取telnet域名对应的ip和延迟信息 - telnet 出现超时问题还未解决 - 未完待续2020.06.12
     * @param string $address
     * @return array
     */
    public static function getTelnetAddressInfo($address = ''){
        $rst = Tools::telnetAddress($address);
        p($rst);
        $status = $rst['status'];
        if (0 != $status) {
            return [];
        }

        $outcome = $rst['outcome'];
        $d = $outcome[1];

        # 取IP
        preg_match("/\(.*?\)/i", $d, $matches1);
        $ip = str_replace(['(', ')'], '', $matches1[0]); # 域名对应的ip

        # 延迟
        $pos = strpos($d, "time=");
        $ms = substr($d, $pos+5);
        //p(['rst'=>$rst, 'd'=>$d, 'ms'=>$ms, 'ip'=>$ip]);

        return ['address'=>$rst['address'], 'ip'=>$ip, 'ms'=>$ms];
    }

}