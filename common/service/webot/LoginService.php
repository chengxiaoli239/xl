<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace common\service\webot;
use backend\service\Lucky5\Lucky5Service;
use common\service\chat\Tool_Common;
use common\tools\Util;

class LoginService extends BaseService
{
    /**
     * @decription webot开放平台登陆操作
     * @param $uid
     * @return bool|string
     */
    static public function loginWebot($uid) {
        self::__init($uid);
        $config = self::$webotConfigs;
        $url = $config->base_url.'/member/login';

        $headers = [
            'Content-Type: application/json; charset=utf-8'
        ];
        $post_datas = [
            'account' => $config->account,
            'password' => $config->password,
        ];
        $rst = BaseService::sendCurlPost($url, $headers, $post_datas);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'post_datas'=>$post_datas, 'rst'=>$rst];
        Tool_Common::log('/wx/'.__FUNCTION__, 'INFO', 'webot开放平台登陆操作', $logArr);
        if($rst['code'] == 1000 && isset($rst['data']['Authorization'])){
            $config->authorization = $rst['data']['Authorization'];
            $config->save();
        }

        return $rst;
    }


    /**
     * @desciption webot获取微信二维码
     * @param $uid
     * @param $wcId
     * @param int $type
     * @return bool|string
     */
    public static function getLoginQrCode($uid, $type=2){
        self::__init($uid);
        $config = self::$webotConfigs;
        $url = $config->base_url.'/iPadLogin';

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: '.$config['authorization'],
        ];
        $post_datas = [
            'wcId' => $config->wcId,
            'type' => $type,
        ];
        $rst = BaseService::sendCurlPost($url, $headers, $post_datas);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'post_datas'=>$post_datas, 'rst'=>$rst];
        Tool_Common::log('/wx/'.__FUNCTION__, 'INFO', 'webot获取微信二维码', $logArr);
        if($rst['code'] == 1000 && isset($rst['data']['wId'])){
            $config->wId = $rst['data']['wId'];
            $config->save();
        }

        return $rst;
    }

    /**
     * @desciption webot执行微信登陆
     * @param $uid
     * @param $wcId
     * @param int $type
     * @return bool|string
     */
    public static function getIPadLoginInfo($uid, $type=2){
        self::__init($uid);
        $config = self::$webotConfigs;
        $url = $config->base_url.'/iPadLogin';

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: '.$config['authorization'],
        ];
        $post_datas = [
            'wcId' => $config->wcId,
            'type' => $type,
        ];
        $rst = BaseService::sendCurlPost($url, $headers, $post_datas);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'post_datas'=>$post_datas, 'rst'=>$rst];
        Tool_Common::log('/wx/'.__FUNCTION__, 'INFO', 'webot执行微信登陆', $logArr);

        return $rst;
    }
}