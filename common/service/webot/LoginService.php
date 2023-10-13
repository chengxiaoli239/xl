<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace common\service\webot;
use common\service\chat\Tool_Common;

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
        $rst = BaseService::sendCurlPost($url, $headers, $post_datas);  # xxx
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
     * @param $wId
     * @return bool|string
     */
    public static function getIPadLoginInfo($uid){
        self::__init($uid);
        $config = self::$webotConfigs;
        $url = $config->base_url.'/getIPadLoginInfo';

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: '.$config['authorization'],
        ];
        $post_datas = [
            'wId' => $config->wId,
        ];
        $rst = BaseService::sendCurlPost($url, $headers, $post_datas);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'post_datas'=>$post_datas, 'rst'=>$rst];
        Tool_Common::log('/wx/'.__FUNCTION__, 'INFO', 'webot执行微信登陆', $logArr);

        return $rst;
    }

    /**
     * @desciption webot获取通讯录列表（好友/群）
     * @param string $type
     * @return bool|string
     */
    public static function getAddressList($uid, $type = 'friends', $is_auto=1){
        $m = \Yii::$app->cache;
        $mkey = 'getAddressList_'.$uid.'_'.$type;
        #if($is_auto != 1 OR $rst = $m->get($mkey)) return $rst;
        self::__init($uid);
        $config = self::$webotConfigs;
        $RobotUser = self::$RobotUser;
        #p(['config'=>$config, 'RobotUser'=>$RobotUser]);
        self::initAddressList();
        $url = $config->base_url.'/getAddressList';

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: '.$config['authorization'],
        ];
        $post_datas = [
            'wId' => $RobotUser->wId,
        ];
        $rst = BaseService::sendCurlPost($url, $headers, $post_datas);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'post_datas'=>$post_datas, 'rst'=>$rst];
        Tool_Common::log('/wx/'.__FUNCTION__, 'INFO', 'webot获取通讯录列表', $logArr);
        if($rst['code']==1000 && isset($rst['data'][$type])){
            $rst = $rst['data'][$type];
        }else{
            $rst = $rst['data'];
        }
        $m->set($mkey, $rst, 86400);

        return $rst;
    }

    /**
     * @desciption webot初始化通讯录
     * @param $wId
     * @return bool|string
     */
    public static function initAddressList(){
        $config = self::$webotConfigs;
        $url = $config->base_url.'/initAddressList';

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: '.$config['authorization'],
        ];
        $post_datas = [
            'wId' => $config->wId,
        ];
        $rst = BaseService::sendCurlPost($url, $headers, $post_datas);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'post_datas'=>$post_datas, 'rst'=>$rst];
        Tool_Common::log('/wx/'.__FUNCTION__, 'INFO', 'webot初始化通讯录', $logArr);

        return $rst;
    }

}
