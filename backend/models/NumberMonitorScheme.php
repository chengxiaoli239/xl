<?php

namespace backend\models;

class NumberMonitorScheme extends \yii\db\ActiveRecord
{
    const STATUS_NONE = 0;
    const STATUS_HIT = 1;
    const STATUS_MISS = 2;

    public static function tableName()
    {
        return '{{%number_monitor_schemes}}';
    }

    public function rules()
    {
        return [
            [['file_id', 'uid', 'lottery_type', 'line_no', 'fixed_count', 'pattern_count', 'started_draw_id', 'last_draw_id', 'current_status', 'streak_count', 'created_at', 'updated_at'], 'integer'],
            [['title', 'last_qihao'], 'string', 'max' => 80],
            [['sample'], 'string', 'max' => 160],
            [['file_id', 'uid', 'lottery_type', 'title'], 'required'],
        ];
    }
}
