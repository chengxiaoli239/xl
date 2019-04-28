<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%wx_msg_status}}".
 *
 * @property int $id
 * @property string $fid 好友id,lt_wx_friends.id
 * @property int $msg_type 消息类型
 * @property int $status 发送状态
 * @property int $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class WxMsgStatus extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wx_msg_status}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['msg_type', 'status', 'created_at'], 'integer'],
            [['updated_at'], 'safe'],
            [['fid'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'fid' => Yii::t('app', '好友id,lt_wx_friends.id'),
            'msg_type' => Yii::t('app', '消息类型'),
            'status' => Yii::t('app', '发送状态'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return WxMsgStatusQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new WxMsgStatusQuery(get_called_class());
    }
}
