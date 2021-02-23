<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_2nums_yl}}".
 *
 * @property int $id
 * @property string $val 和值范围
 * @property int $current_miss 本期遗漏
 * @property int $last_time_miss 上次遗漏
 * @property string $last_time_miss_range 上次遗漏范围
 * @property int $max_miss 最大遗漏(近200期)
 * @property string $max_range 最大遗漏范围(近200期)
 * @property string $yl_records 遗漏记录
 * @property int $history_max_miss 历史最大遗漏
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class Ssc2numsYl extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_2nums_yl}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['current_miss', 'last_time_miss', 'max_miss', 'history_max_miss', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['yl_records'], 'string'],
            [['update_time'], 'safe'],
            [['val'], 'string', 'max' => 8],
            [['last_time_miss_range', 'max_range'], 'string', 'max' => 64],
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
            'current_miss' => '本期遗漏',
            'last_time_miss' => '上次遗漏',
            'last_time_miss_range' => '上次遗漏范围',
            'max_miss' => '最大遗漏(近200期)',
            'max_range' => '最大遗漏范围(近200期)',
            'yl_records' => '遗漏记录',
            'history_max_miss' => '历史最大遗漏',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return Ssc2numsYlQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new Ssc2numsYlQuery(get_called_class());
    }
}
