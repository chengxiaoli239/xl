<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%static_4d_profits_day}}".
 *
 * @property int $id
 * @property string $date 日期
 * @property string $codes_1112 1112
 * @property string $codes_1121 1121
 * @property string $codes_1211 1211
 * @property string $codes_2111 2111
 * @property string $codes_1222 1222
 * @property string $codes_2122 2122
 * @property string $codes_2212 2212
 * @property string $codes_2221 2221
 * @property string $codes_1122 1122
 * @property string $codes_1212 1212
 * @property string $codes_1221 1221
 * @property string $codes_2112 2112
 * @property string $codes_2121 2121
 * @property string $codes_2211 2211
 * @property string $codes_1111 1111
 * @property string $codes_2222 2222
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class Static4dProfitsDay extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_4d_profits_day}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['codes_1112', 'codes_1121', 'codes_1211', 'codes_2111', 'codes_1222', 'codes_2122', 'codes_2212', 'codes_2221', 'codes_1122', 'codes_1212', 'codes_1221', 'codes_2112', 'codes_2121', 'codes_2211', 'codes_1111', 'codes_2222'], 'number'],
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
            'codes_1112' => '1112',
            'codes_1121' => '1121',
            'codes_1211' => '1211',
            'codes_2111' => '2111',
            'codes_1222' => '1222',
            'codes_2122' => '2122',
            'codes_2212' => '2212',
            'codes_2221' => '2221',
            'codes_1122' => '1122',
            'codes_1212' => '1212',
            'codes_1221' => '1221',
            'codes_2112' => '2112',
            'codes_2121' => '2121',
            'codes_2211' => '2211',
            'codes_1111' => '1111',
            'codes_2222' => '2222',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return Static4dProfitsDayQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new Static4dProfitsDayQuery(get_called_class());
    }
}
