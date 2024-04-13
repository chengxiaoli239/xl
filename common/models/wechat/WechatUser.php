<?php
namespace common\models\wechat;

use common\models\base\BaseModel;

class WechatUser extends BaseModel
{
    const MEMBER_TYPE_USER = 0;
    const MEMBER_TYPE_ADMIN = 1;
    const MEMBER_TYPE_OPTIONS = [
        self::MEMBER_TYPE_USER => '用户',
        self::MEMBER_TYPE_ADMIN => '管理员',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wechat_user}}';
    }

}
