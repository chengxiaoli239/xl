<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%proxy_ip_records}}".
 *
 * @property int $id
 * @property string $ip_addr ip地址
 * @property string $ip_port 端口号
 * @property string $valid_time ip可用时长
 * @property int $status 状态：0不可用1可用
 * @property int $expire_time 到期时间
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class ProxyIpRecords extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%proxy_ip_records}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['ip_addr'], 'string', 'max' => 24],
            [['ip_port', 'valid_time'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'ip_addr' => Yii::t('app', 'ip地址'),
            'ip_port' => Yii::t('app', '端口号'),
            'valid_time' => Yii::t('app', 'ip可用时长'),
            'status' => Yii::t('app', '状态：0不可用1可用'),
            'expire_time' => Yii::t('app', '到期时间'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return ProxyIpRecordsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ProxyIpRecordsQuery(get_called_class());
    }
}
