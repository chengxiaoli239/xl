<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%static_pei_shu_code_month_profits}}".
 *
 * @property int $id
 * @property string $month 月
 * @property string $code_147_369 147_369
 * @property string $code_258_369 258_369
 * @property string $code_019_368 019_368
 * @property string $code_123_678 123_678
 * @property string $code_147_258 147_258
 * @property string $code_017_348 017_348
 * @property string $code_456_789 456_789
 * @property string $code_012_789 012_789
 * @property string $code_345_678 345_678
 * @property string $code_357_019 357_019
 * @property string $code_3b 3b
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc 7:北京快乐 8:幸运五星
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class StaticPeiShuCodeMonthProfits extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_pei_shu_code_month_profits}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code_147_369', 'code_258_369', 'code_019_368', 'code_123_678', 'code_147_258', 'code_017_348', 'code_456_789', 'code_012_789', 'code_345_678', 'code_357_019', 'code_3b'], 'number'],
            [['lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['month'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'month' => Yii::t('app', '月'),
            'code_147_369' => Yii::t('app', '147_369'),
            'code_258_369' => Yii::t('app', '258_369'),
            'code_019_368' => Yii::t('app', '019_368'),
            'code_123_678' => Yii::t('app', '123_678'),
            'code_147_258' => Yii::t('app', '147_258'),
            'code_017_348' => Yii::t('app', '017_348'),
            'code_456_789' => Yii::t('app', '456_789'),
            'code_012_789' => Yii::t('app', '012_789'),
            'code_345_678' => Yii::t('app', '345_678'),
            'code_357_019' => Yii::t('app', '357_019'),
            'code_3b' => Yii::t('app', '3b'),
            'lottery_type' => Yii::t('app', '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc 7:北京快乐 8:幸运五星'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return StaticPeiShuCodeMonthProfitsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new StaticPeiShuCodeMonthProfitsQuery(get_called_class());
    }
}
