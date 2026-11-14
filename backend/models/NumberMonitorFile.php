<?php

namespace backend\models;

use Yii;

class NumberMonitorFile extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%number_monitor_files}}';
    }

    public function rules()
    {
        return [
            [['uid', 'lottery_type', 'status', 'scheme_count', 'created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 180],
            [['uid', 'lottery_type', 'name'], 'required'],
        ];
    }
}
