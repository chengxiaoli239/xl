<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%static_per_hz_perdate_profits}}".
 *
 * @property int $id
 * @property string $date 日期
 * @property string $codes_1 1(3组)
 * @property string $codes_2 2
 * @property string $codes_3 3
 * @property string $codes_4 4
 * @property string $codes_5 5
 * @property string $codes_6 6
 * @property string $codes_7 7
 * @property string $codes_8 8
 * @property string $codes_9 9
 * @property string $codes_10 10
 * @property string $codes_11 11
 * @property string $codes_12 12
 * @property string $codes_13 13
 * @property string $codes_14 14
 * @property string $codes_15 15
 * @property string $codes_16 16
 * @property string $codes_17 17
 * @property string $codes_18 18
 * @property string $codes_19 19
 * @property string $codes_20 20
 * @property string $codes_21 21
 * @property string $codes_22 22
 * @property string $codes_23 23
 * @property string $codes_24 24
 * @property string $codes_25 25
 * @property string $codes_26 26
 * @property string $codes_27 27
 * @property string $codes_28 28
 * @property string $codes_29 29
 * @property string $codes_30 30
 * @property string $codes_31 31
 * @property string $codes_32 32
 * @property string $codes_33 33
 * @property string $codes_34 34
 * @property string $codes_35 35
 * @property string $codes_36 36
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class StaticPerHzPerdateProfits extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_per_hz_perdate_profits}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['codes_1', 'codes_2', 'codes_3', 'codes_4', 'codes_5', 'codes_6', 'codes_7', 'codes_8', 'codes_9', 'codes_10', 'codes_11', 'codes_12', 'codes_13', 'codes_14', 'codes_15', 'codes_16', 'codes_17', 'codes_18', 'codes_19', 'codes_20', 'codes_21', 'codes_22', 'codes_23', 'codes_24', 'codes_25', 'codes_26', 'codes_27', 'codes_28', 'codes_29', 'codes_30', 'codes_31', 'codes_32', 'codes_33', 'codes_34', 'codes_35', 'codes_36'], 'number'],
            [['lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['date'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => '日期',
            'codes_1' => '1(3组)',
            'codes_2' => '2',
            'codes_3' => '3',
            'codes_4' => '4',
            'codes_5' => '5',
            'codes_6' => '6',
            'codes_7' => '7',
            'codes_8' => '8',
            'codes_9' => '9',
            'codes_10' => '10',
            'codes_11' => '11',
            'codes_12' => '12',
            'codes_13' => '13',
            'codes_14' => '14',
            'codes_15' => '15',
            'codes_16' => '16',
            'codes_17' => '17',
            'codes_18' => '18',
            'codes_19' => '19',
            'codes_20' => '20',
            'codes_21' => '21',
            'codes_22' => '22',
            'codes_23' => '23',
            'codes_24' => '24',
            'codes_25' => '25',
            'codes_26' => '26',
            'codes_27' => '27',
            'codes_28' => '28',
            'codes_29' => '29',
            'codes_30' => '30',
            'codes_31' => '31',
            'codes_32' => '32',
            'codes_33' => '33',
            'codes_34' => '34',
            'codes_35' => '35',
            'codes_36' => '36',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return StaticPerHzPerdateProfitsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new StaticPerHzPerdateProfitsQuery(get_called_class());
    }
}
