<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace common\service\webot;
use backend\models\WxFriends;
use common\service\chat\Tool_Common;

class WebotService extends BaseService {

    /**
     * @desc 同步webot 微信通讯录到本地
     * @param string $uid
     * @param string $type chatrooms群组列表、friends好友列表、ghs公众号列表、others微信其他相关
     * @return array
     */
    public static function syncAddressList($uid='', $type='friends'){
        $addresses = LoginService::getAddressList($uid, $type);

        if($type == 'friends'){
            $rst = WebotService::syncAddressData($uid, $addresses);
        }else{
            $rst = WebotService::syncAddressChatRoomData($uid, $addresses);
        }
        return $rst;
    }

    /**
     * @desc 同步通讯录好友
     * @param $uid
     * @param $addresses - 好友id集合
     * @param string $type chatrooms群组列表、friends好友列表、ghs公众号列表、others微信其他相关
     */
    public static function syncAddressData($uid, $addresses){
        $rstData = FriendsService::getContactDetail($uid, $addresses);
        if($rstData['status'] != 200 OR empty($rstData['data'])) return ['status'=>300, 'msg'=>'数据为空'];

        $rst = ['status'=>200, 'msg'=>'操作成功'];
        $datas = $rstData['data'];
        foreach ($datas as $rstData){
            $setDatas = [];
            $WxFriends = WxFriends::findOne(['uid'=>$uid, 'UserName'=>$rstData['userName'], 'is_friend'=>1]);
            if(empty($WxFriends)){
                $WxFriends = new WxFriends();
                $setDatas = array_merge($setDatas, [
                    'UserName' => $rstData['userName'],
                    'uid' => $uid,
                    'is_friend' => 1,
                ]);
            }
            $setDatas = array_merge($setDatas, [
                'NickName' => urlencode($rstData['nickName']),
                'HeadImgUrl' => $rstData['bigHead'],
                'Alias' => $rstData['aliasName'],
                'Province' => $rstData['province'],
                'City' => $rstData['city'],
            ]);
            $WxFriends->setAttributes($setDatas);
            $rst['data'][$rstData['userName']] = ['status'=>200, 'msg'=>'成功'];
            $logArr = [];
            try{
                $flag = $WxFriends->save();
            }catch (\Exception $e){
                $rst['data'][$rstData['userName']] = ['status'=>300, 'msg'=>$e->getMessage()];
                $logArr = ['error'=>$e->getMessage(), 'setDatas'=>$setDatas];
                Tool_Common::log('/wx/'.__FUNCTION__.'_err', 'INFO', 'webot微信好友保存错误', $logArr);
            }
            $logArr = array_merge($logArr, ['flag'=>$flag, 'wx_id'=>$rstData['userName']]);
            Tool_Common::log('/wx/'.__FUNCTION__, 'INFO', 'webot微信好友信息保存', $logArr);
        }

        return $rst;
    }

    /**
     * @desc 同步群好友信息 基本信息
     * @param $uid
     * @param $chatRoomIds - 群id集合
     * @param string $type chatrooms群组列表、friends好友列表、ghs公众号列表、others微信其他相关
     */
    public static function syncAddressChatRoomData($uid, $chatRoomIds=[]){
        if(empty($chatRoomIds)) return ['status'=>300, 'msg'=>'群id不能为空'];

        foreach ($chatRoomIds as $chatRoomId){
            $rstData = ChatRoomService::getChatRoomMember($uid, $chatRoomId);
            if($rstData['status'] != 200 OR empty($rstData['data'])) continue; #return ['status'=>300, 'msg'=>'数据为空'];
            $rst = ['status'=>200, 'msg'=>'操作成功'];
            $datas = $rstData['data'];
            foreach ($datas as $rstData){
                $setDatas = [];
                $WxFriends = WxFriends::findOne(['uid'=>$uid, 'UserName'=>$rstData['userName'], 'ChatRoomId'=>$chatRoomId]);
                if(empty($WxFriends)){
                    $WxFriends = new WxFriends();
                    $setDatas = array_merge($setDatas, [
                        'UserName' => $rstData['userName'],
                        'uid' => $uid,
                        'ChatRoomId' => $chatRoomId,

                    ]);
                }
                $setDatas = array_merge($setDatas, [
                    'NickName' => urlencode($rstData['nickName']), # 群成员昵称
                    'HeadImgUrl' => $rstData['bigHeadImgUrl'],
                    'Alias' => $rstData['displayName'], # 群成员修改后的昵称
                ]);
                $WxFriends->setAttributes($setDatas);
                $rst['data'][$rstData['userName']] = ['status'=>200, 'msg'=>'成功'];
                $logArr = [];
                try{
                    $flag = $WxFriends->save();
                }catch (\Exception $e){
                    $rst['data'][$rstData['userName']] = ['status'=>300, 'msg'=>$e->getMessage()];
                    $logArr = ['error'=>$e->getMessage(), 'setDatas'=>$setDatas];
                    Tool_Common::log('/wx/'.__FUNCTION__.'_err', 'INFO', 'webot微信好友保存错误', $logArr);
                }
                $logArr = array_merge($logArr, ['flag'=>$flag, 'wx_id'=>$rstData['userName']]);
                Tool_Common::log('/wx/'.__FUNCTION__, 'INFO', 'webot微信好友信息保存', $logArr);
            }
        }

        return $rst;
    }

    /**
     * @desc 同步群好友信息 基本信息
     * @param $uid
     * @param $chatRoomId - 群id
     * @param array $userList - 群好友id
     */
    public static function syncChatRoomDataFriendDetails($uid, $chatRoomId='', $wxid=''){
        if(empty($chatRoomId)) return ['status'=>300, 'msg'=>'群id不能为空'];
        if(empty($wxid)) return ['status'=>300, 'msg'=>'wxid不能为空'];

        $rstData = ChatRoomService::getChatRoomMemberInfo($uid, $chatRoomId, $wxid);
        if($rstData['status'] != 200 OR empty($rstData['data'])) return ['status'=>300, 'msg'=>'数据为空'];
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        $datas = $rstData['data'];
        foreach ($datas as $rstData){
            $setDatas = [];
            $WxFriends = WxFriends::findOne(['uid'=>$uid, 'UserName'=>$rstData['userName'], 'ChatRoomId'=>$chatRoomId]);
            if(empty($WxFriends)){
                $WxFriends = new WxFriends();
                $setDatas = array_merge($setDatas, [
                    'UserName' => $rstData['userName'],
                    'uid' => $uid,
                    'ChatRoomId' => $chatRoomId,
                ]);
            }
            $setDatas = array_merge($setDatas, [
                'NickName' => urlencode($rstData['nickName']), # 群成员昵称
                'HeadImgUrl' => $rstData['bigHeadImgUrl'],
                'Alias' => $rstData['displayName'], # 群成员修改后的昵称
            ]);
            $WxFriends->setAttributes($setDatas);
            $rst['data'][$rstData['userName']] = ['status'=>200, 'msg'=>'成功'];
            $logArr = [];
            try{
                $flag = $WxFriends->save();
            }catch (\Exception $e){
                $rst['data'][$rstData['userName']] = ['status'=>300, 'msg'=>$e->getMessage()];
                $logArr = ['error'=>$e->getMessage(), 'setDatas'=>$setDatas];
                Tool_Common::log('/wx/'.__FUNCTION__.'_err', 'INFO', 'webot微信好友保存错误', $logArr);
            }
            $logArr = array_merge($logArr, ['flag'=>$flag, 'wx_id'=>$rstData['userName']]);
            Tool_Common::log('/wx/'.__FUNCTION__, 'INFO', 'webot微信好友信息保存', $logArr);
        }

        return $rst;
    }
}