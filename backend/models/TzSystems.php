<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%tz_systems}}".
 *
 * @property int $id id
 * @property string $name 系统名称
 * @property int $system_type_id 系统类型id
 * @property string $ssc_domain 系统站点
 * @property int $status 系统开启状态
 * @property int $type 类型:1时时彩2网球
 * @property int $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class TzSystems extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tz_systems}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['system_type_id', 'status', 'type', 'created_at'], 'integer'],
            [['updated_at'], 'safe'],
            [['name'], 'string', 'max' => 64],
            [['ssc_domain'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'id'),
            'name' => Yii::t('app', '系统名称'),
            'system_type_id' => Yii::t('app', '系统类型id'),
            'ssc_domain' => Yii::t('app', '系统站点'),
            'status' => Yii::t('app', '系统开启状态'),
            'type' => Yii::t('app', '类型:1时时彩2网球'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return TzSystemsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new TzSystemsQuery(get_called_class());
    }
}
