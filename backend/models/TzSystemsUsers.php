<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%tz_systems_users}}".
 *
 * @property int $id
 * @property int $uid 用户id
 * @property int $is_agent 是否代理
 * @property string $username 账号名称
 * @property int $tz_system_id 系统类型id，lt_tz_systems.id
 * @property string $sys_name 系统名称
 * @property string $account 投注账号
 * @property string $password 网盘密码
 * @property string $balance 系统余额
 * @property int $status 系统开启状态
 * @property string $ssc_domain 网盘地址
 * @property string $cookie 登陆cookie
 * @property string $user_agent 浏览器代理
 * @property int $tz_sort 投注排序:从小到大
 * @property string $odds_2x 代理二现赔率
 * @property string $odds_3x 代理三现赔率
 * @property string $odds_4x 代理四现赔率
 * @property string $odds_2d 二定赔率
 * @property string $odds_3d 三定赔率
 * @property string $odds_4d 四定赔率
 * @property string $warn_val 预警值
 * @property string $desc 盘口状态
 * @property int $expire_time 到期时间
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
            [['uid', 'is_agent', 'tz_system_id', 'status', 'tz_sort', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            [['balance', 'odds_2x', 'odds_3x', 'odds_4x', 'odds_2d', 'odds_3d', 'odds_4d'], 'number'],
            [['cookie'], 'string'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['username', 'sys_name', 'account', 'ssc_domain'], 'string', 'max' => 64],
            [['password'], 'string', 'max' => 20],
            [['user_agent', 'desc'], 'string', 'max' => 640],
            [['warn_val'], 'string', 'max' => 11],
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
            'is_agent' => Yii::t('app', '是否代理'),
            'username' => Yii::t('app', '账号名称'),
            'tz_system_id' => Yii::t('app', '系统类型id，lt_tz_systems.id'),
            'sys_name' => Yii::t('app', '系统名称'),
            'account' => Yii::t('app', '投注账号'),
            'password' => Yii::t('app', '网盘密码'),
            'balance' => Yii::t('app', '系统余额'),
            'status' => Yii::t('app', '系统开启状态'),
            'ssc_domain' => Yii::t('app', '网盘地址'),
            'cookie' => Yii::t('app', '登陆cookie'),
            'user_agent' => Yii::t('app', '浏览器代理'),
            'tz_sort' => Yii::t('app', '投注排序:从小到大'),
            'odds_2x' => Yii::t('app', '代理二现赔率'),
            'odds_3x' => Yii::t('app', '代理三现赔率'),
            'odds_4x' => Yii::t('app', '代理四现赔率'),
            'odds_2d' => Yii::t('app', '二定赔率'),
            'odds_3d' => Yii::t('app', '三定赔率'),
            'odds_4d' => Yii::t('app', '四定赔率'),
            'warn_val' => Yii::t('app', '预警值'),
            'desc' => Yii::t('app', '盘口状态'),
            'expire_time' => Yii::t('app', '到期时间'),
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
