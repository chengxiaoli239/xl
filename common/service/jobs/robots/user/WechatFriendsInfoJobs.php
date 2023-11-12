<?php
namespace common\service\jobs\robots\user;

use common\models\wechat\WechatUser;
use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;

class WechatFriendsInfoJobs extends CommonJob {

    public static function getName($params) {
        self::$name = '好友信息变更同步';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        try {
            $user_id = $params['user_id']; # 机器人系统user_id
            $data = $params['data'];
            $userName = $data['userName']; # 微信id
            $wcId = $params['wcId']; # 微信原始id
            $WechatUser = WechatUser::findOne(['user_id'=>$user_id, 'userName'=>$userName]);
            if(empty($WechatUser)){
                throw_info('wechat_user找不到记录');
            }
            $WechatUser->nickName = $data['nickName'];
            $WechatUser->remark = $data['remarkName'];
            $WechatUser->bigHead = $data['bigHead'];
            $WechatUser->smallHead = $data['smallHeadImgUrl']??$data['smallHead'];
            if(!$WechatUser->save()){
                throw_info(current($WechatUser->getFirstErrors()));
            }
        }catch (\Exception $e){
            Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', '微信用户信息变更01', ['params'=>$params, 'err_msg'=>$e->getMessage()]);
            return $e->getMessage();
        }

        Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', '微信用户信息变更02', ['params'=>$params]);

        return '微信好友信息同步成功:';
    }

}
