<?php

namespace common\models\statics;

use Yii;

/**
 * This is the model class for table "{{%ssc_1nums_yl}}".
 *
 * @property int $id
 * @property int $position 位置
 * @property string $code 号码
 * @property int $today_current 今日出现
 * @property int $current_miss 当前遗漏
 * @property int $today_miss 今日遗漏
 * @property int $week_miss 本周遗漏
 * @property int $month_miss 本月遗漏
 * @property int $lottery_type 彩种类型
 * @property int $created_at 创建时间
 * @property string $update_time 更新时间
 */
class Ssc1numsYl extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_1nums_yl}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['position', 'today_current', 'current_miss', 'today_miss', 'week_miss', 'month_miss', 'lottery_type', 'created_at'], 'integer'],
            [['update_time'], 'safe'],
            [['code'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'position' => '位置',
            'code' => '号码',
            'today_current' => '今出',
            'current_miss' => '当前遗漏',
            'today_miss' => '今日遗漏',
            'week_miss' => '本周遗漏',
            'month_miss' => '本月遗漏',
            'lottery_type' => '彩种类型',
            'created_at' => '创建时间',
            'update_time' => '更新时间',
        ];
    }
}
