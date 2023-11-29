<?php
namespace common\models\eyun;

use Yii;

/**
 * This is the model class for table "{{%eyun_auth}}".
 *
 * @property int $id
 * @property int $type 平台类型1:E云
 * @property string $account 平台账号
 * @property string $password 平台密码
 * @property int $status 状态
 * @property string $authorization eyun授权key
 * @property string $callback_url 消息回调url
 * @property string $base_url E云平台接口域名
 * @property string $desc 备注信息
 * @property int $created_at
 * @property int $updated_at
 * @property string $update_at 更新时间
 */
class EyunAuth extends \common\models\base\BaseModel
{
   /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%eyun_auth}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'status', 'created_at', 'updated_at'], 'integer'],
            [['authorization', 'desc'], 'string'],
            [['created_at', 'updated_at'], 'required'],
            [['update_at'], 'safe'],
            [['account'], 'string', 'max' => 32],
            [['password', 'callback_url', 'base_url'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'type' => Yii::t('app', '平台类型1:E云'),
            'account' => Yii::t('app', '平台账号'),
            'password' => Yii::t('app', '平台密码'),
            'status' => Yii::t('app', '状态'),
            'authorization' => Yii::t('app', 'eyun授权key'),
            'callback_url' => Yii::t('app', '消息回调url'),
            'base_url' => Yii::t('app', 'E云平台接口域名'),
            'desc' => Yii::t('app', '备注信息'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'update_at' => Yii::t('app', '更新时间'),
        ];
    }
}
