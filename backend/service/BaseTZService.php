<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;

use backend\models\SystemConfig;

abstract class BaseTZService{
    protected $_nowTime = null;    // 当前时间戳
    protected $_operateTime = null;    // 当前时间戳的格式
    protected $_baseUrl = '';    // 当前时间戳的格式

    protected function __construct()
    {
        $this->_nowTime = time();
        $this->_operateTime = date('Y-m-d H:i:s', $this->_nowTime);
    }

    /**
     * @decription 投注
     *
     * @param $cookie
     * @param $playway
     * @param $code
     * @param $single
     * @param $qihao
     * @param $is_simulate
     * @return mixed
     */
    //abstract public function betting($cookie, $playway, $code, $single, $qihao, $is_simulate);

    /**
     * @desc 号码拆解
     * @param $codes ['8,9,9,9','9,8,9,9','9,9,8,9','9,9,9,8']
     * @param int $length
     * @return mixed
     */
    public static function splitCodes($codes, $length = 300){

        $codesArr = array_chunk($codes, $length);

        return $codesArr;
    }

    /**
     * @desc 获取每次下注号码量
     * @return int|string
     */
    public static function getBetNumsPer(){
        $nums = SystemConfig::findOne(['key'=>'tz_nums_per'])->value;
        if(!$nums) $nums = 1350;

        return $nums;
    }

}