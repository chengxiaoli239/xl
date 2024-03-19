<?php

namespace common\models\open;

use Yii;
use common\models\base\BaseModel;

/**
 * This is the model class for table "{{%platform_group}}".
 *
 * @property int $id
 * @property string $group_leader_id 群主id
 * @property int $user_id user.id,系统用户id
 * @property string $group_id 群id
 * @property string $name 群名称
 * @property string $nickName 昵称
 * @property int $status 状态-0禁用1启用
 * @property int $user_num 群好友数量
 * @property string $token 群token
 * @property string $labelList 标签列表
 * @property string $remark 备注
 * @property int $created_at
 * @property int $updated_at
 * @property string $update_at 更新时间
 */
class PlatformGroup extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%platform_group}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'status', 'user_num', 'created_at', 'updated_at'], 'integer'],
            [['created_at', 'updated_at'], 'required'],
            [['update_at'], 'safe'],
            [['group_leader_id', 'name'], 'string', 'max' => 32],
            [['group_id', 'nickName', 'labelList', 'remark'], 'string', 'max' => 255],
            [['token'], 'string', 'max' => 64],
            [['user_id', 'group_id'], 'unique', 'targetAttribute' => ['user_id', 'group_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_leader_id' => '群主id',
            'user_id' => 'user.id,系统用户id',
            'group_id' => '群id',
            'name' => '群名称',
            'nickName' => '昵称',
            'status' => '状态-0禁用1启用',
            'user_num' => '群好友数量',
            'token' => '群token',
            'labelList' => '标签列表',
            'remark' => '备注',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'update_at' => '更新时间',
        ];
    }
}
