<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_dw_hz}}".
 *
 * @property string $id
 * @property string $positions 开奖号码str
 * @property string $qihao 当前期号
 * @property int $periods 近多少期，20，50，100，150，200，300，500，1000，2000期
 * @property int $hz_0
 * @property int $hz_1 1
 * @property int $hz_2 2
 * @property int $hz_3 3
 * @property int $hz_4 4
 * @property int $hz_5 5
 * @property int $hz_6 6
 * @property int $hz_7 7
 * @property int $hz_8 8
 * @property int $hz_9 8
 * @property int $hz_10 10
 * @property int $hz_11 11
 * @property int $hz_12 12
 * @property int $hz_13 13
 * @property int $hz_14 14
 * @property int $hz_15 15
 * @property int $hz_16 16
 * @property int $hz_17 17
 * @property int $hz_18 18
 */
class SscDwHz extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_dw_hz}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['periods', 'current_miss', 'last_time_miss', 'hz_0', 'hz_1', 'hz_2', 'hz_3', 'hz_4', 'hz_5', 'hz_6', 'hz_7', 'hz_8', 'hz_9', 'hz_10', 'hz_11', 'hz_12', 'hz_13', 'hz_14', 'hz_15', 'hz_16', 'hz_17', 'hz_18'], 'integer'],
            [['positions'], 'string', 'max' => 8],
            [['qihao'], 'string', 'max' => 11],
            [['hz_11'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'positions' => Yii::t('app', '开奖号码str'),
            'qihao' => Yii::t('app', '当前期号'),
            'periods' => Yii::t('app', '近多少期，20，50，100，150，200，300，500，1000，2000期'),
            'hz_0' => Yii::t('app', 'Hz 0'),
            'hz_1' => Yii::t('app', '1'),
            'hz_2' => Yii::t('app', '2'),
            'hz_3' => Yii::t('app', '3'),
            'hz_4' => Yii::t('app', '4'),
            'hz_5' => Yii::t('app', '5'),
            'hz_6' => Yii::t('app', '6'),
            'hz_7' => Yii::t('app', '7'),
            'hz_8' => Yii::t('app', '8'),
            'hz_9' => Yii::t('app', '8'),
            'hz_10' => Yii::t('app', '10'),
            'hz_11' => Yii::t('app', '11'),
            'hz_12' => Yii::t('app', '12'),
            'hz_13' => Yii::t('app', '13'),
            'hz_14' => Yii::t('app', '14'),
            'hz_15' => Yii::t('app', '15'),
            'hz_16' => Yii::t('app', '16'),
            'hz_17' => Yii::t('app', '17'),
            'hz_18' => Yii::t('app', '18'),
        ];
    }

    /**
     * @inheritdoc
     * @return SscDwHzQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SscDwHzQuery(get_called_class());
    }
}
