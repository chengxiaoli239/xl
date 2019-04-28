<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use  yii;

class TestService extends BaseService {


    /**
     * @decription Yii 控制器初始化方法
     */
    public static function _init(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $time = date("H:i");
        if(\Yii::$app->params['ssc_kj_time_start'] < $time && $time < \Yii::$app->params['ssc_kj_time_start'] ){
            $rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
            return $rst;
        }
    }

    /**
     * @desc 获取每周二、五、日
     * @param string $year
     * @param int $s2
     * @param int $s5
     * @param int $s7
     * @return array
     * @author wyg
     */
    public static function getWeek257($year = '2018', $s2 = 1, $s5 = 4, $s7 = 6){
        # 每天晚上8点半
        $time_p_830 = 20 * 3600 + 30 * 60;
        $time2018 = strtotime($year.'-01-01 00:00:00');
        $time2018_s2 = $time2018 + $s2 * 24 * 3600 + $time_p_830; # 2018年第1个周二
        $time2018_s5 = $time2018 + $s5 * 24 * 3600 + $time_p_830; # 2018年第1个周五
        $time2018_s7 = $time2018 + $s7 * 24 * 3600 + $time_p_830; # 2018年第1个周日
        # 一周时间戳
        $time7 = 7 * 24 * 3600;
        $len = 53; # 一年52-53周
        $dateTimeArr = [];
        for ($i = 1; $i < $len; $i++){
            $ptime = $i * $time7;
            if($i == 1){
                $dateTimeArr[] = date('Y-m-d H:i:s', $time2018_s2);
                $dateTimeArr[] = date('Y-m-d H:i:s', $time2018_s5);
                $dateTimeArr[] = date('Y-m-d H:i:s', $time2018_s7);
            }else{
                $dateTimeArr[] = date('Y-m-d H:i:s', $time2018_s2 + $ptime);
                $dateTimeArr[] = date('Y-m-d H:i:s', $time2018_s5 + $ptime);
                $dateTimeArr[] = date('Y-m-d H:i:s', $time2018_s7 + $ptime);
            }
        }
        return $dateTimeArr;
    }
}