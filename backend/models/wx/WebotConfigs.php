<?php

namespace backend\models\wx;

use Yii;

/**
 * This is the model class for table "{{%webot_configs}}".
 *
 * @property int $id
 * @property int $uid 本系统uid
 * @property int $account 开发者账号
 * @property string $password 开发者密码
 * @property string $base_url webot接口域名
 * @property string $authorization 企业认证信息
 * @property string $wcId 微信原始id
 * @property string $wId 微信实例id(获取二维码接口返回,本值非固定的)
 * @property int $status 系统开启状态
 * @property string $user_agent 浏览器代理
 * @property string $desc 盘口状态
 * @property int $expire_time 到期时间
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class WebotConfigs extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%webot_configs}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'account', 'status', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['password'], 'string', 'max' => 1],
            [['base_url'], 'string', 'max' => 255],
            [['authorization', 'wcId', 'wId'], 'string', 'max' => 64],
            [['user_agent', 'desc'], 'string', 'max' => 640],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'uid' => Yii::t('app', '本系统uid'),
            'account' => Yii::t('app', '开发者账号'),
            'password' => Yii::t('app', '开发者密码'),
            'base_url' => Yii::t('app', 'webot接口域名'),
            'authorization' => Yii::t('app', '企业认证信息'),
            'wcId' => Yii::t('app', '微信原始id'),
            'wId' => Yii::t('app', '微信实例id(获取二维码接口返回,本值非固定的)'),
            'status' => Yii::t('app', '系统开启状态'),
            'user_agent' => Yii::t('app', '浏览器代理'),
            'desc' => Yii::t('app', '盘口状态'),
            'expire_time' => Yii::t('app', '到期时间'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return WebotConfigsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new WebotConfigsQuery(get_called_class());
    }
}
