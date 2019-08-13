<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%admin_log}}".
 *
 * @property int $id
 * @property string $route
 * @property string $url
 * @property string $user_agent
 * @property string $gets
 * @property string $posts
 * @property int $admin_id
 * @property string $admin_email
 * @property string $ip
 * @property int $created_at
 * @property int $updated_at
 * @property string $update_time 更新时间
 */
class AdminLog extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%admin_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['route', 'url', 'user_agent', 'posts', 'admin_id', 'admin_email', 'ip', 'created_at', 'updated_at'], 'required'],
            [['gets', 'posts'], 'string'],
            [['admin_id', 'created_at', 'updated_at'], 'integer'],
            [['update_time'], 'safe'],
            [['route', 'url', 'user_agent', 'admin_email', 'ip'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'route' => 'Route',
            'url' => 'Url',
            'user_agent' => 'User Agent',
            'gets' => 'Gets',
            'posts' => 'Posts',
            'admin_id' => 'Admin ID',
            'admin_email' => 'Admin Email',
            'ip' => 'Ip',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return AdminLogQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new AdminLogQuery(get_called_class());
    }
}
