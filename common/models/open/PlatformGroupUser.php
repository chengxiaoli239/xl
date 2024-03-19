<?php

namespace common\models\open;

use Yii;

/**
 * This is the model class for table "{{%platform_group_user}}".
 *
 * @property int $id
 * @property int $user_id user.id,系统用户id
 * @property string $group_id 群id
 * @property string $platform_user_id 平台用户id
 * @property string $username 用户昵称
 * @property int $status 状态-0禁用1启用
 * @property string $remark 备注
 * @property int $created_at
 * @property int $updated_at
 * @property string $update_at 更新时间
 */
class PlatformGroupUser extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%platform_group_user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['created_at', 'updated_at'], 'required'],
            [['update_at'], 'safe'],
            [['group_id', 'platform_user_id', 'remark'], 'string', 'max' => 255],
            [['username'], 'string', 'max' => 32],
            [['user_id', 'username', 'group_id'], 'unique', 'targetAttribute' => ['user_id', 'username', 'group_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'user.id,系统用户id',
            'group_id' => '群id',
            'platform_user_id' => '平台用户id',
            'username' => '用户昵称',
            'status' => '状态-0禁用1启用',
            'remark' => '备注',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'update_at' => '更新时间',
        ];
    }
}
