<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%wx_msg_types}}".
 *
 * @property int $id
 * @property int $uid 用户id
 * @property string $msg 消息内容
 * @property int $status 开启状态
 * @property int $is_name 消息是否加入名字
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class WxMsgTypes extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wx_msg_types}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'status', 'is_name', 'created_at', 'updated_at'], 'integer'],
            [['msg'], 'string'],
            [['update_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'uid' => Yii::t('app', '用户id'),
            'msg' => Yii::t('app', '消息内容'),
            'status' => Yii::t('app', '开启状态'),
            'is_name' => Yii::t('app', '消息是否加入名字'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return WxMsgTypesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new WxMsgTypesQuery(get_called_class());
    }
}
