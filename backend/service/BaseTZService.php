<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;

use backend\models\SystemConfig;
use common\tools\Tool_Common;

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

    /**
     * @desc 设置全局代理
     * @param $ch
     * @return bool
     */
    public static function setPoxy($ch, $url='', $uid = 0){
        $poxy_addr = PoxyIPService::getPoxyIp();
        if(strpos($url, 'ww662889') === false){
            //$poxy_addr = '218.85.247.70:20000';
        }
        Tool_Common::log('setPoxy', 'INFO', '设置全局代理', ['url'=>$url, 'poxy_addr'=>$poxy_addr, 'uid'=>$uid]);
        $POXY_USER_IDS = BetService::getConfig('POXY_USER_IDS');
        $uids = explode(',', $POXY_USER_IDS);
        if(empty($uids) OR !in_array($uid, $uids) OR !$uid){
            return [];
        }

        if(!empty($poxy_addr)){
            //设置代理
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            curl_setopt($ch, CURLOPT_PROXY, $poxy_addr);
            //设置代理用户名密码（私密代理/独享代理）
            //如果是开放代理，请注释掉下面两句
            $username = \Yii::$app->params['KUAI_USERNAME'];
            $password = \Yii::$app->params['KUAI_PASSWORD'];
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$username}:{$password}");
        }

        return $poxy_addr;
    }

}