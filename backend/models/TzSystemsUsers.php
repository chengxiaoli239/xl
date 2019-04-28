<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%tz_systems_users}}".
 *
 * @property int $id
 * @property int $uid 用户id
 * @property int $tz_system_id 系统类型id，lt_tz_systems.id
 * @property string $sys_name 系统名称
 * @property string $account 投注账号
 * @property string $password 网盘密码
 * @property string $balance 系统余额
 * @property int $status 系统开启状态
 * @property string $ssc_domain 网盘地址
 * @property string $cookie 登陆cookie
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class TzSystemsUsers extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tz_systems_users}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'tz_system_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['balance'], 'number'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['sys_name', 'account', 'ssc_domain'], 'string', 'max' => 64],
            [['password'], 'string', 'max' => 20],
            [['cookie'], 'string', 'max' => 640],
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
            'tz_system_id' => Yii::t('app', '系统类型id，lt_tz_systems.id'),
            'sys_name' => Yii::t('app', '系统名称'),
            'account' => Yii::t('app', '投注账号'),
            'password' => Yii::t('app', '网盘密码'),
            'balance' => Yii::t('app', '系统余额'),
            'status' => Yii::t('app', '系统开启状态'),
            'ssc_domain' => Yii::t('app', '网盘地址'),
            'cookie' => Yii::t('app', '登陆cookie'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return TzSystemsUsersQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new TzSystemsUsersQuery(get_called_class());
    }
}
