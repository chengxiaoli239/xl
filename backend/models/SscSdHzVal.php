<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_sd_hz_val}}".
 *
 * @property int $id
 * @property string $val 和值范围
 * @property int $status 是否显示0不显示1显示
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class SscSdHzVal extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_sd_hz_val}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status', 'created_at', 'updated_at'], 'integer'],
            [['update_time'], 'safe'],
            [['val'], 'string', 'max' => 120],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'val' => Yii::t('app', '和值范围'),
            'status' => Yii::t('app', '是否显示0不显示1显示'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return SscSdHzValQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SscSdHzValQuery(get_called_class());
    }
}
