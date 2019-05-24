<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_static_yl}}".
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
 * @property int $count 组合总共组数
 * @property int $static_nums 默认统计期数
 * @property string $theory_nums_perdate 理论次数/天
 * @property int $today_nums 今日出现
 * @property int $ytd_nums 昨日出现次数
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property int $status 是否显示:0不显示1显示
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class SscStaticYl extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_static_yl}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['current_miss', 'last_time_miss', 'max_miss', 'history_max_miss', 'count', 'static_nums', 'today_nums', 'ytd_nums', 'lottery_type', 'status', 'created_at', 'updated_at'], 'integer'],
            [['yl_records'], 'string'],
            [['update_time'], 'safe'],
            [['val', 'last_time_miss_range', 'max_range'], 'string', 'max' => 64],
            [['theory_nums_perdate'], 'string', 'max' => 8],
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
            'count' => '组合总共组数',
            'static_nums' => '默认统计期数',
            'theory_nums_perdate' => '理论次数/天',
            'today_nums' => '今日出现',
            'ytd_nums' => '昨日出现次数',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'status' => '是否显示:0不显示1显示',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return SscStaticYlQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SscStaticYlQuery(get_called_class());
    }
}
