<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%static_pei_shu_code_true_false}}".
 *
 * @property int $id
 * @property string $date 日期
 * @property string $qihao 期号
 * @property string $kj_code 号码
 * @property int $code_147_369 147_369
 * @property int $code_258_369 258_369
 * @property int $code_019_368 019_368
 * @property int $code_123_678 123_678
 * @property int $code_147_258 147_258
 * @property int $code_017_348 017_348
 * @property int $code_456_789 456_789
 * @property int $code_012_789 012_789
 * @property int $code_345_678 345_678
 * @property int $code_357_019 357_019
 * @property int $code_3b 3b
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc 7:北京快乐 8:幸运五星
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class StaticPeiShuCodeTrueFalse extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_pei_shu_code_true_false}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code_147_369', 'code_258_369', 'code_019_368', 'code_123_678', 'code_147_258', 'code_017_348', 'code_456_789', 'code_012_789', 'code_345_678', 'code_357_019', 'code_3b', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['date'], 'string', 'max' => 10],
            [['qihao', 'kj_code'], 'string', 'max' => 24],
            [['date', 'qihao', 'lottery_type'], 'unique', 'targetAttribute' => ['date', 'qihao', 'lottery_type']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'date' => Yii::t('app', '日期'),
            'qihao' => Yii::t('app', '期号'),
            'kj_code' => Yii::t('app', '号码'),
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
     * @return StaticPeiShuCodeTrueFalseQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new StaticPeiShuCodeTrueFalseQuery(get_called_class());
    }
}
