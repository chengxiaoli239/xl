<?php

namespace common\models\open;

use Yii;

/**
 * This is the model class for table "{{%platform_robot}}".
 *
 * @property int $id
 * @property int $platform_robot_id 机器人平台ID
 * @property int $platform_id 平台ID
 * @property int $user_id user.id,系统用户id
 * @property string $name 机器人名称
 * @property int $status 状态-0禁用1启用
 * @property string $token 机器人token
 * @property string $remark 备注
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_at 更新时间
 */
class PlatformRobot extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%platform_robot}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['platform_robot_id', 'platform_id', 'user_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['created_at', 'updated_at'], 'required'],
            [['update_at'], 'safe'],
            [['name'], 'string', 'max' => 32],
            [['token'], 'string', 'max' => 64],
            [['remark'], 'string', 'max' => 255],
            [['platform_id', 'user_id', 'token'], 'unique', 'targetAttribute' => ['platform_id', 'user_id', 'token']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_robot_id' => '机器人平台ID',
            'platform_id' => '平台ID',
            'user_id' => 'user.id,系统用户id',
            'name' => '机器人名称',
            'status' => '状态-0禁用1启用',
            'token' => '机器人token',
            'remark' => '备注',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_at' => '更新时间',
        ];
    }
}
