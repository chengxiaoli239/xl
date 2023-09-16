<?php
namespace common\models\wechat;

use common\models\base\BaseModel;

class WechatUser extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wechat_user}}';
    }

}
