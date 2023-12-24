<?php
/**
 * Created by PhpStorm.
 *   
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;

abstract class BaseBetService{
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
     * @return mixed
     */
    abstract public function betting($playway, $code, $single, $qihao);


}
