<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%static_3num_arise_perdate}}".
 *
 * @property int $id
 * @property string $date 日期
 * @property int $codes_289 289
 * @property int $codes_046 046
 * @property int $codes_456 456
 * @property int $codes_125 125
 * @property int $codes_589 589
 * @property int $codes_025 025
 * @property int $codes_467 789
 * @property int $codes_256 256
 * @property int $codes_128 128
 * @property int $codes_347 347
 * @property int $codes_134 134
 * @property int $codes_258 258
 * @property int $codes_124 124
 * @property int $codes_014 014
 * @property int $codes_147 147
 * @property int $codes_345 345
 * @property int $codes_678 678
 * @property int $codes_238 238
 * @property int $codes_239 239
 * @property int $codes_028 028
 * @property int $codes_268 268
 * @property int $codes_389 389
 * @property int $codes_348 348
 * @property int $created_at 创建时间
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class Static3numArisePerdate extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_3num_arise_perdate}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['codes_289', 'codes_046', 'codes_456', 'codes_125', 'codes_589', 'codes_025', 'codes_467', 'codes_256', 'codes_128', 'codes_347', 'codes_134', 'codes_258', 'codes_124', 'codes_014', 'codes_147', 'codes_345', 'codes_678', 'codes_238', 'codes_239', 'codes_028', 'codes_268', 'codes_389', 'codes_348', 'created_at', 'lottery_type', 'updated_at'], 'integer'],
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
            'codes_289' => '289',
            'codes_046' => '046',
            'codes_456' => '456',
            'codes_125' => '125',
            'codes_589' => '589',
            'codes_025' => '025',
            'codes_467' => '789',
            'codes_256' => '256',
            'codes_128' => '128',
            'codes_347' => '347',
            'codes_134' => '134',
            'codes_258' => '258',
            'codes_124' => '124',
            'codes_014' => '014',
            'codes_147' => '147',
            'codes_345' => '345',
            'codes_678' => '678',
            'codes_238' => '238',
            'codes_239' => '239',
            'codes_028' => '028',
            'codes_268' => '268',
            'codes_389' => '389',
            'codes_348' => '348',
            'created_at' => '创建时间',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'updated_at' => '更新时间',
            'update_time' => '时间',
        ];
    }

    /**
     * @inheritdoc
     * @return Static3numArisePerdateQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new Static3numArisePerdateQuery(get_called_class());
    }
}
