<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property int $id
 * @property int $admin_id 管理员id
 * @property string $username 临时账号名
 * @property string $account 投注账号
 * @property string $balance 用户余额
 * @property string $simulate_balance
 * @property string $email
 * @property int $expire_time 账号到期时间
 * @property string $tz_password 网盘密码
 * @property string $ssc_domain 网盘地址
 * @property string $cookie 登陆cookie
 * @property string $cookie2
 * @property int $status 状态
 * @property int $created_at
 * @property int $updated_at
 */
class User extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['admin_id', 'expire_time', 'status', 'created_at', 'updated_at'], 'integer'],
            [['balance', 'simulate_balance'], 'number'],
            [['email', 'created_at', 'updated_at'], 'required'],
            [['username', 'account', 'email'], 'string', 'max' => 255],
            [['tz_password'], 'string', 'max' => 20],
            [['ssc_domain'], 'string', 'max' => 32],
            [['cookie', 'cookie2'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'admin_id' => Yii::t('app', '管理员id'),
            'username' => Yii::t('app', '临时账号名'),
            'account' => Yii::t('app', '投注账号'),
            'balance' => Yii::t('app', '用户余额'),
            'simulate_balance' => Yii::t('app', 'Simulate Balance'),
            'email' => Yii::t('app', 'Email'),
            'expire_time' => Yii::t('app', '账号到期时间'),
            'tz_password' => Yii::t('app', '网盘密码'),
            'ssc_domain' => Yii::t('app', '网盘地址'),
            'cookie' => Yii::t('app', '登陆cookie'),
            'cookie2' => Yii::t('app', 'Cookie2'),
            'status' => Yii::t('app', '状态'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @inheritdoc
     * @return UserQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UserQuery(get_called_class());
    }
}
