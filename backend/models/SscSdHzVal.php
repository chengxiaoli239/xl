<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_sd_hz_val}}".
 *
 * @property int $id
 * @property string $val 和值范围
 * @property int $count 组数
 * @property int $status 是否显示0不显示1显示
 * @property int $static_nums 统计期数
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
            [['count', 'status', 'static_nums', 'created_at', 'updated_at'], 'integer'],
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
            'id' => 'ID',
            'val' => '和值范围',
            'count' => '组数',
            'status' => '是否显示0不显示1显示',
            'static_nums' => '统计期数',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
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
