<?php

namespace common\service\wechat;

use common\models\wechat\WechatUser;
use common\service\BaseService;
use common\tools\Tool_Common;

class WechatUserService extends BaseService
{
    const STATUS_DISABLE = 0; # 禁用
    const STATUS_ACTIVE = 1; # 激活

    const WECHAT_STATUS_OFFLINE = 0;
    const WECHAT_STATUS_ONLINE = 1;

    public static $s = [
        'status' => [
            self::STATUS_DISABLE => '已禁用',
            self::STATUS_ACTIVE => '已激活',
        ],
        'wechat_status' => [
            self::WECHAT_STATUS_OFFLINE => '已掉线',
            self::WECHAT_STATUS_ONLINE => '在线',
        ],
    ];

    public static function getWechatUsersKey($user_id){
        return 'getWechatUsersKey_x0_'.$user_id;
    }

    /**
     * 获取代理微信好友
     * @param string $user_id
     * @param bool $useCache
     * @return array|mixed|\yii\db\ActiveRecord[]
     */
    public static function getWechatUsers($user_id='', $useCache=false){
        $m = \Yii::$app->cache;
        $mkey = self::getWechatUsersKey($user_id);
        $data = [];
        if(!$useCache && $data = $m->get($mkey)){
            $data = WechatUser::find()
                ->select(['id', 'user_id', 'userName', 'nickName', 'status', 'smallHead'])
                ->where(['=', 'user_id', $user_id])
                ->indexBy(['userName'])->asArray()->all();
            $m->set($mkey, $data, 600);
        }

        return $data;
    }

}
