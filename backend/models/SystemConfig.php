<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%system_config}}".
 *
 * @property int $id
 * @property string $name 配置名称
 * @property string $key 配置key
 * @property string $value 配置值
 * @property string $desc 描述
 * @property string $extend 扩展
 * @property int $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class SystemConfig extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%system_config}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['desc'], 'string'],
            [['created_at'], 'integer'],
            [['updated_at'], 'safe'],
            [['name'], 'string', 'max' => 64],
            [['key', 'value', 'extend'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', '配置名称'),
            'key' => Yii::t('app', '配置key'),
            'value' => Yii::t('app', '配置值'),
            'desc' => Yii::t('app', '描述'),
            'extend' => Yii::t('app', '扩展'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return SystemConfigQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SystemConfigQuery(get_called_class());
    }
}
