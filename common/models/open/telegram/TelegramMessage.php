<?php

namespace common\models\open\telegram;

use Yii;

/**
 * This is the model class for table "{{%telegram_message}}".
 *
 * @property int $id
 * @property int $user_id user.id,本系统用户id
 * @property int $from_id 消息平台用户id
 * @property int $chat_id 消息来源id
 * @property int $is_bot 是否机器人0、1
 * @property string $name 用户或群名称
 * @property string $type 消息类型:private、group
 * @property string $message_id 消息Id
 * @property int $update_id 更新Id
 * @property string $text 消息内容
 * @property string $content 全部内容
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_at 更新时间
 */
class TelegramMessage extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%telegram_message}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'from_id', 'chat_id', 'is_bot', 'update_id', 'created_at', 'updated_at'], 'integer'],
            [['text', 'created_at', 'updated_at'], 'required'],
            [['text', 'content'], 'string'],
            [['update_at'], 'safe'],
            [['name'], 'string', 'max' => 32],
            [['type'], 'string', 'max' => 24],
            [['message_id'], 'string', 'max' => 64],
            [['user_id', 'message_id'], 'unique', 'targetAttribute' => ['user_id', 'message_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'user.id,本系统用户id',
            'from_id' => '消息平台用户id',
            'chat_id' => '消息来源id',
            'is_bot' => '是否机器人0、1',
            'name' => '用户或群名称',
            'type' => '消息类型:private、group',
            'message_id' => '消息Id',
            'update_id' => '更新Id',
            'text' => '消息内容',
            'content' => '全部内容',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_at' => '更新时间',
        ];
    }
}
