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
 * @property int $is_auto_login 是否自动登陆
 * @property string $ssc_domain 网盘地址
 * @property string $cookie 登陆cookie
 * @property string $user_agent 浏览器代理
 * @property string $cookie_wx_web 微信web登录cookie
 * @property string $access_token 授权Token凭证
 * @property int $tz_sort 投注排序:从小到大
 * @property string $odds_2x 代理二现赔率
 * @property string $odds_3x 代理三现赔率
 * @property string $odds_4x 代理四现赔率
 * @property string $odds_2d 二定赔率
 * @property string $odds_3d 三定赔率
 * @property string $odds_4d 四定赔率
 * @property string $warn_val 预警值
 * @property string $desc 盘口状态
 * @property int $is_auto_bet 自动下注
 * @property int $is_use_proxy 使用代理
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
            [['uid', 'is_agent', 'tz_system_id', 'status', 'is_auto_login', 'tz_sort', 'is_auto_bet', 'is_use_proxy', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            [['balance', 'odds_2x', 'odds_3x', 'odds_4x', 'odds_2d', 'odds_3d', 'odds_4d'], 'number'],
            [['cookie', 'cookie_wx_web'], 'string'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['username', 'sys_name', 'account', 'ssc_domain', 'access_token'], 'string', 'max' => 64],
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
            'id' => 'ID',
            'uid' => '用户id',
            'is_agent' => '是否代理',
            'username' => '账号名称',
            'tz_system_id' => '系统类型id，lt_tz_systems.id',
            'sys_name' => '系统名称',
            'account' => '投注账号',
            'password' => '网盘密码',
            'balance' => '系统余额',
            'status' => '系统开启状态',
            'is_auto_login' => '是否自动登陆',
            'ssc_domain' => '网盘地址',
            'cookie' => '登陆cookie',
            'user_agent' => '浏览器代理',
            'cookie_wx_web' => '微信web登录cookie',
            'access_token' => '授权Token凭证',
            'tz_sort' => '投注排序:从小到大',
            'odds_2x' => '代理二现赔率',
            'odds_3x' => '代理三现赔率',
            'odds_4x' => '代理四现赔率',
            'odds_2d' => '二定赔率',
            'odds_3d' => '三定赔率',
            'odds_4d' => '四定赔率',
            'warn_val' => '预警值',
            'desc' => '盘口状态',
            'is_auto_bet' => '自动下注',
            'is_use_proxy' => '使用代理',
            'expire_time' => '到期时间',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
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
