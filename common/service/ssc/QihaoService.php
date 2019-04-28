<?php
namespace common\service\ssc;
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/05/06
 * Time: 09:40
 */
use common\service\CommonService;


class QihaoService extends CommonService
{


    /**
     * @description 获取开始日期到今天的所有期号
     * @param string $date_start
     * @return array
     */
    public static function getQihaos($date_start = '20180101'){
        $qihaos = [];
        $date_end = date('Ymd');
        $dateArr = CommonService::genDateArr($split = '-', $date_start, $date_end);
        foreach ($dateArr as $date){
            $tmpDateQihaos = [];
            for( $qihao = $date.'001'; $qihao <= $date.'120'; $qihao++ ){
                $tmpDateQihaos[] = str_replace('-','',$qihao);
            }
            $qihaos[$date] = $tmpDateQihaos;
        }

        return $qihaos;
    }

}