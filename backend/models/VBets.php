<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%v_bets}}".
 *
 * @property string $qihao 期号
 * @property string $tz_num
 * @property string $tz_money
 * @property string $profits
 * @property string $zj_bonus
 * @property int $is_simulate 是否模拟投注
 * @property string $tz_time 投注时间
 */
class VBets extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%v_bets}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tz_num', 'tz_money', 'is_simulate'], 'integer'],
            [['profits', 'zj_bonus'], 'number'],
            [['qihao'], 'string', 'max' => 20],
            [['tz_time'], 'string', 'max' => 32],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'qihao' => Yii::t('app', '期号'),
            'tz_num' => Yii::t('app', 'Tz Num'),
            'tz_money' => Yii::t('app', 'Tz Money'),
            'profits' => Yii::t('app', 'Profits'),
            'zj_bonus' => Yii::t('app', 'Zj Bonus'),
            'is_simulate' => Yii::t('app', '是否模拟投注'),
            'tz_time' => Yii::t('app', '投注时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return VBetsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new VBetsQuery(get_called_class());
    }
}
