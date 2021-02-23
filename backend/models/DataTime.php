<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%data_time}}".
 *
 * @property integer $id
 * @property integer $type
 * @property integer $actionNo
 * @property string $actionTime
 * @property string $stopTime
 */
class DataTime extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%data_time}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'actionNo', 'actionTime', 'stopTime'], 'required'],
            [['type', 'actionNo'], 'integer'],
            [['actionTime', 'stopTime'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => '投注种类，对应ssc_type.id',
            'actionNo' => '开奖期号(当天)',
            'actionTime' => '开奖时间',
            'stopTime' => 'Stop Time',
        ];
    }
}
