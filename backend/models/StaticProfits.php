<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%static_profits}}".
 *
 * @property int $id
 * @property int $plan_id 计划id
 * @property string $static_time 统计时间
 * @property int $uid 用户id,默认为系统
 * @property string $qihao 期号
 * @property string $kj_code 开奖号码
 * @property int $playway 投注类型
 * @property string $tz_money 系统类型id，lt_tz_systems.id
 * @property string $profits 当期利润
 * @property string $zj_bouns 中奖金额
 * @property string $cut_profits 截止当前期收益
 * @property string $tz_time 投注时间
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class StaticProfits extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_profits}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['plan_id', 'uid', 'playway', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['tz_money', 'profits', 'zj_bouns', 'cut_profits'], 'number'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['static_time', 'kj_code', 'tz_time'], 'string', 'max' => 20],
            [['qihao'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'plan_id' => Yii::t('app', '计划id'),
            'static_time' => Yii::t('app', '统计时间'),
            'uid' => Yii::t('app', '用户id,默认为系统'),
            'qihao' => Yii::t('app', '期号'),
            'kj_code' => Yii::t('app', '开奖号码'),
            'playway' => Yii::t('app', '投注类型'),
            'tz_money' => Yii::t('app', '系统类型id，lt_tz_systems.id'),
            'profits' => Yii::t('app', '当期利润'),
            'zj_bouns' => Yii::t('app', '中奖金额'),
            'cut_profits' => Yii::t('app', '截止当前期收益'),
            'tz_time' => Yii::t('app', '投注时间'),
            'lottery_type' => Yii::t('app', '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return StaticProfitsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new StaticProfitsQuery(get_called_class());
    }
}
