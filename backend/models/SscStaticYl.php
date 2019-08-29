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
 * @property int $max_miss 最大遗漏(近xx期)
 * @property string $max_range 最大遗漏范围(近200期)
 * @property string $yl_records 遗漏记录
 * @property int $history_max_miss 历史最大遗漏
 * @property int $count 组合总共组数
 * @property int $static_nums 默认统计期数
 * @property string $theory_nums_perdate 理论次数/天
 * @property int $today_nums 今日出现
 * @property int $ytd_nums 昨日出现次数
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property int $type 1和值2号码类型[例如:双双重、三重]3三字现带双重4四字现带双重5四字现不带双重
 * @property int $status 是否显示:0不显示1显示
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 * @property int $type_2 是否双重
 * @property int $type_22 是否双双重
 * @property int $type_3 是否三重
 * @property int $type_4 是否四重
 * @property int $type_2b 是否两兄弟
 * @property int $type_3b 是否三兄弟
 * @property int $type_4b 是否四兄弟
 * @property int $type_4d 是否四单
 * @property int $type_4s 是否四双
 * @property int $type_log 是否对数
 * @property int $type_4ds 是否四单四双
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
            [['current_miss', 'last_time_miss', 'max_miss', 'history_max_miss', 'count', 'static_nums', 'today_nums', 'ytd_nums', 'lottery_type', 'type', 'status', 'created_at', 'updated_at', 'type_2', 'type_22', 'type_3', 'type_4', 'type_2b', 'type_3b', 'type_4b', 'type_4d', 'type_4s', 'type_log', 'type_4ds'], 'integer'],
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
            'max_miss' => '最大遗漏(近xx期)',
            'max_range' => '最大遗漏范围(近200期)',
            'yl_records' => '遗漏记录',
            'history_max_miss' => '历史最大遗漏',
            'count' => '组合总共组数',
            'static_nums' => '默认统计期数',
            'theory_nums_perdate' => '理论次数/天',
            'today_nums' => '今日出现',
            'ytd_nums' => '昨日出现次数',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'type' => '1和值2号码类型[例如:双双重、三重]3三字现带双重4四字现带双重5四字现不带双重',
            'status' => '是否显示:0不显示1显示',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
            'type_2' => '是否双重',
            'type_22' => '是否双双重',
            'type_3' => '是否三重',
            'type_4' => '是否四重',
            'type_2b' => '是否两兄弟',
            'type_3b' => '是否三兄弟',
            'type_4b' => '是否四兄弟',
            'type_4d' => '是否四单',
            'type_4s' => '是否四双',
            'type_log' => '是否对数',
            'type_4ds' => '是否四单四双',
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
