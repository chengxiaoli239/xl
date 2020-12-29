<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%bt_system_configs}}".
 *
 * @property int $id
 * @property int $uid 用户id
 * @property string $sys_name 系统名称
 * @property string $account 平台账号
 * @property string $password 平台密码
 * @property int $status 开启状态
 * @property string $domain 地址
 * @property string $cookie 登陆cookie
 * @property string $user_agent 浏览器代理
 * @property string $version 版本号
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class BtSystemConfigs extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%bt_system_configs}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'status', 'created_at', 'updated_at'], 'integer'],
            [['cookie'], 'string'],
            [['update_time'], 'safe'],
            [['sys_name', 'account', 'domain', 'version'], 'string', 'max' => 64],
            [['password'], 'string', 'max' => 20],
            [['user_agent'], 'string', 'max' => 640],
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
            'sys_name' => Yii::t('app', '系统名称'),
            'account' => Yii::t('app', '平台账号'),
            'password' => Yii::t('app', '平台密码'),
            'status' => Yii::t('app', '开启状态'),
            'domain' => Yii::t('app', '地址'),
            'cookie' => Yii::t('app', '登陆cookie'),
            'user_agent' => Yii::t('app', '浏览器代理'),
            'version' => Yii::t('app', '版本号'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return BtSystemConfigsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new BtSystemConfigsQuery(get_called_class());
    }
}
