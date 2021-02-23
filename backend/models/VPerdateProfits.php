<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%v_perdate_profits}}".
 *
 * @property string $tz_num
 * @property string $tz_money
 * @property string $profits
 * @property string $zj_money
 * @property string $tz_date
 * @property int $is_simulate 是否模拟投注
 * @property string $update_time 投注时间
 * @property string $tz_qs
 * @property int $playway 投注方式：10定位胆
 */
class VPerdateProfits extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%v_perdate_profits}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tz_num', 'is_simulate', 'tz_qs', 'playway'], 'integer'],
            [['tz_money', 'profits', 'zj_money'], 'number'],
            [['tz_date'], 'string', 'max' => 10],
            [['update_time'], 'string', 'max' => 32],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'tz_num' => Yii::t('app', 'Tz Num'),
            'tz_money' => Yii::t('app', 'Tz Money'),
            'profits' => Yii::t('app', 'Profits'),
            'zj_money' => Yii::t('app', 'Zj Money'),
            'tz_date' => Yii::t('app', 'Tz Date'),
            'is_simulate' => Yii::t('app', '是否模拟投注'),
            'update_time' => Yii::t('app', '投注时间'),
            'tz_qs' => Yii::t('app', 'Tz Qs'),
            'playway' => Yii::t('app', '投注方式：10定位胆'),
        ];
    }

    /**
     * @inheritdoc
     * @return VPerdateProfitsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new VPerdateProfitsQuery(get_called_class());
    }
}
