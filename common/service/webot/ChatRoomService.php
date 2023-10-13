<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace common\service\webot;
use common\service\chat\Tool_Common;

class ChatRoomService extends BaseService
{
    public static function getChatRoomIds($uid){
        $addresses = LoginService::getAddressList($uid, $type='chatrooms');
    }

    /**
     * @desc 1.1 webot获取群成员列表 - 基本够用
     * @param $uid
     * @param $chatRoomId
     * @return bool|string
     */
    public static function getChatRoomMember($uid, $chatRoomId){
        self::__init($uid);
        $config = self::$webotConfigs;
        $RobotUser = self::$RobotUser;
        $url = $config->base_url.'/getChatRoomMember';

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: '.$config['authorization'],
        ];
        $post_datas = [
            'wId' => $RobotUser->wId,
            'chatRoomId' => $chatRoomId,
        ];
        $rst = BaseService::sendCurlPost($url, $headers, $post_datas);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'post_datas'=>$post_datas, 'rst'=>$rst];
        Tool_Common::log('/wx/'.__FUNCTION__, 'INFO', 'webot获取群成员列表', $logArr);

        return $rst;
    }

    /**
     * @desciption 1.2 webot获取群成员详情 比较细，跟微信好友的一样内容
     * @param $uid
     * @param $wId
     * @param string $wxid - 群成员微信id,仅支持单个微信id
     * @return array
     */
    public static function getChatRoomMemberInfo($uid, $chatRoomId='', $wxid=''){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        self::__init($uid);
        $config = self::$webotConfigs;
        $RobotUser = self::$RobotUser;
        $url = $config->base_url.'/getChatRoomMemberInfo';

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: '.$config['authorization'],
        ];
        $rst['data'] = [];
        $post_datas = [
            'wId' => $RobotUser->wId, # 登录实例id
            'chatRoomId' => $chatRoomId, # 群id
            'wcId' => $wxid, # 微信好友id
        ];
        $rstData = BaseService::sendCurlPost($url, $headers, $post_datas);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'post_datas'=>$post_datas, 'rst'=>$rst];
        Tool_Common::log('/wx/'.__FUNCTION__, 'INFO', 'webot获取微信二维码', $logArr);
        if($rstData['code'] == 1000 && !empty($rstData['data'])){
            $rst['data'] = array_merge($rst['data'], $rstData['data']);
        }

        return $rst;
    }
}
