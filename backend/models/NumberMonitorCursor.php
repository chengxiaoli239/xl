<?php

namespace backend\models;

class NumberMonitorCursor extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%number_monitor_cursors}}';
    }
}
