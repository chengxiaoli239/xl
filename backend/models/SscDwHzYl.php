<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_dw_hz_yl}}".
 *
 * @property int $id
 * @property string $positions 位置
 * @property int $zhi 和值
 * @property int $current_miss 本期遗漏
 * @property int $last_time_miss 上次遗漏
 * @property string $last_time_miss_range 上次遗漏范围
 * @property int $max_miss 最大遗漏(近200期)
 * @property string $max_range 最大遗漏范围(近200期)
 * @property string $yl_records 遗漏记录
 * @property int $history_max_miss 历史最大遗漏
 * @property int $updated_at 更新时间
 * @property string $update_time
 */
class SscDwHzYl extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_dw_hz_yl}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['zhi', 'current_miss', 'last_time_miss', 'max_miss', 'history_max_miss', 'updated_at'], 'integer'],
            [['update_time'], 'safe'],
            [['positions'], 'string', 'max' => 8],
            [['last_time_miss_range', 'max_range'], 'string', 'max' => 64],
            [['yl_records'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'positions' => '位置',
            'zhi' => '和值',
            'current_miss' => '本期遗漏',
            'last_time_miss' => '上次遗漏',
            'last_time_miss_range' => '上次遗漏范围',
            'max_miss' => '最大遗漏(近200期)',
            'max_range' => '最大遗漏范围(近200期)',
            'yl_records' => '遗漏记录',
            'history_max_miss' => '历史最大遗漏',
            'updated_at' => '更新时间',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * @inheritdoc
     * @return SscDwHzYlQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SscDwHzYlQuery(get_called_class());
    }
}
