<?php

namespace backend\models\wechat;

use Yii;

/**
 * This is the model class for table "lt_robot_user".
 *
 * @property int $id
 * @property int $user_id 管理员user.id
 * @property string $wcId 微信原始id，永久不变，微信原始id （首次登录平台的号传""，掉线后必须传值，否则会频繁掉线！！！） 第三步会返回此字段，记得入库保存
 * @property string $wId 登录实例标识 （本值非固定的，每次重新登录会返回新的，数据库记得实时更新wid）
 * @property string $uuid uuid
 * @property int $status 系统状态
 * @property int $wechat_status 微信状态0离线1在线
 * @property string $desc 备注信息
 * @property int $expire_time 到期时间
 * @property int $created_at
 * @property int $updated_at
 * @property string $update_at 更新时间
 */
class RobotUser extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lt_robot_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'status', 'wechat_status', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            [['created_at', 'updated_at'], 'required'],
            [['update_at'], 'safe'],
            [['wcId', 'wId', 'uuid', 'desc'], 'string', 'max' => 255],
            [['user_id'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => '用户ID',
            'wcId' => '微信ID',
            'wId' => '登录实例ID',
            'uuid' => 'Uuid',
            'status' => '状态',
            'wechat_status' => '微信登录状态',
            'desc' => '描述',
            'expire_time' => '过期时间',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'update_at' => '更新时间',
        ];
    }
}
