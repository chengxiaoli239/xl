<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%bt_crontabs}}".
 *
 * @property int $id
 * @property int $uid 用户id
 * @property int $p_id 计划id
 * @property string $name 计划名称
 * @property string $sName 系统名称
 * @property string $sType 脚本类型:toShell
 * @property string $account 平台账号
 * @property string $password 平台密码
 * @property int $status 开启状态
 * @property string $domain 地址
 * @property string $where_minute 分钟频率
 * @property string $where_hour 小时频率
 * @property string $where1 1分钟
 * @property string $sBody 脚本内容
 * @property string $type_name 脚本内容
 * @property string $cookie 登陆cookie
 * @property string $user_agent 浏览器代理
 * @property string $urladdress url地址
 * @property string $addtime 更新时间
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class BtCrontabs extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%bt_crontabs}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'p_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['cookie'], 'string'],
            [['addtime', 'update_time'], 'safe'],
            [['updated_at'], 'required'],
            [['name', 'sName', 'account', 'domain'], 'string', 'max' => 64],
            [['sType', 'where_minute', 'where_hour', 'where1'], 'string', 'max' => 12],
            [['password', 'type_name'], 'string', 'max' => 20],
            [['sBody', 'urladdress'], 'string', 'max' => 250],
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
            'p_id' => Yii::t('app', '计划id'),
            'name' => Yii::t('app', '计划名称'),
            'sName' => Yii::t('app', '系统名称'),
            'sType' => Yii::t('app', '脚本类型:toShell'),
            'account' => Yii::t('app', '平台账号'),
            'password' => Yii::t('app', '平台密码'),
            'status' => Yii::t('app', '开启状态'),
            'domain' => Yii::t('app', '地址'),
            'where_minute' => Yii::t('app', '分钟频率'),
            'where_hour' => Yii::t('app', '小时频率'),
            'where1' => Yii::t('app', '1分钟'),
            'sBody' => Yii::t('app', '脚本内容'),
            'type_name' => Yii::t('app', '脚本内容'),
            'cookie' => Yii::t('app', '登陆cookie'),
            'user_agent' => Yii::t('app', '浏览器代理'),
            'urladdress' => Yii::t('app', 'url地址'),
            'addtime' => Yii::t('app', '更新时间'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return BtCrontabsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new BtCrontabsQuery(get_called_class());
    }
}
