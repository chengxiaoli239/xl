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
     * @return array
     */
    public static function syncAddressList($uid=''){
        $addresses = LoginService::getAddressList($uid, $type='friends');

        $rst = WebotService::syncAddressData($uid, $addresses);
        return $rst;
    }

    /**
     * @desc 同步通讯录好友
     * @param $uid
     * @param $addresses
     */
    public static function syncAddressData($uid, $addresses){
        $rstData = FriendsService::getContactDetail($uid, $addresses);
        if($rstData['status'] != 200 OR empty($rstData['data'])) return ['status'=>300, 'msg'=>'数据为空'];

        $rst = ['status'=>200, 'msg'=>'操作成功'];
        $datas = $rstData['data'];
        foreach ($datas as $rstData){
            $setDatas = [];
            $WxFriends = WxFriends::findOne(['uid'=>$uid, 'UserName'=>$rstData['userName']]);
            if(empty($WxFriends)){
                $WxFriends = new WxFriends();
                $setDatas = array_merge($setDatas, [
                    'UserName' => $rstData['userName'],
                    'uid' => $uid,

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
            try{
                $WxFriends->save();
            }catch (\Exception $e){
                $rst['data'][$rstData['userName']] = ['status'=>300, 'msg'=>$e->getMessage()];
                $logArr = ['error'=>$e->getMessage(), 'setDatas'=>$setDatas];
                Tool_Common::log('/wx/'.__FUNCTION__.'_err', 'INFO', 'webot获取微信二维码', $logArr);
            }
        }

        return $rst;
    }


}