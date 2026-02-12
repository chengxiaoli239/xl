<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * 计划每期盈利记录表（每期开奖后一条）
 *
 * @property int $id
 * @property int $plan_id 计划id
 * @property string $qihao 期号
 * @property string $profit_before 开奖前累计盈利金额
 * @property string $profit_change 本期盈亏金额（正数=盈利，负数=亏损）
 * @property string $profit_after 开奖后累计盈利金额
 * @property string $period_bet_amount 本期投注金额（每计划每期一条记录，取该条金额）
 * @property int $period_group_count 本期组数（号码个数，如 1234 3452 为 2 组）
 * @property string $period_multiple 本期倍数
 * @property int $uid 用户id
 * @property int $lottery_type 彩种
 * @property int $created_at 创建时间
 */
class PlanPeriodProfits extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%plan_period_profits}}';
    }

    /**
     * 仅自动维护 created_at，表无 updated_at 字段
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['plan_id', 'uid', 'lottery_type', 'created_at', 'period_group_count'], 'integer'],
            [['profit_before', 'profit_change', 'profit_after', 'period_bet_amount', 'period_multiple'], 'number'],
            [['qihao'], 'string', 'max' => 64],
            [['plan_id', 'qihao'], 'unique', 'targetAttribute' => ['plan_id', 'qihao']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'plan_id' => '计划ID',
            'qihao' => '期号',
            'profit_before' => '开奖前盈利金额',
            'profit_change' => '本期盈亏（正=盈利，负=亏损）',
            'profit_after' => '开奖后盈利金额',
            'period_bet_amount' => '本期投注金额',
            'period_group_count' => '本期组数(号码个数)',
            'period_multiple' => '本期倍数',
            'uid' => 'Uid',
            'lottery_type' => '彩种',
            'created_at' => 'Created At',
        ];
    }

    /**
     * 写入一条每期盈利记录（仅在本期统计成功提交后调用，失败不影响主流程）
     * @param int $plan_id
     * @param string $qihao
     * @param float $profit_before 开奖前累计盈利
     * @param float $profit_change 本期盈亏
     * @param float $profit_after 开奖后累计盈利
     * @param int $uid
     * @param int $lottery_type
     * @param float $period_bet_amount 本期投注金额（单条记录）
     * @param int $period_group_count 本期组数（如 1234 3452 为 2 组）
     * @param float $period_multiple 本期倍数
     * @return bool
     */
    public static function addRecord($plan_id, $qihao, $profit_before, $profit_change, $profit_after, $uid, $lottery_type = 8, $period_bet_amount = 0, $period_group_count = 0, $period_multiple = 0)
    {
        $now = time();
        $model = new self();
        $model->setAttributes([
            'plan_id' => (int) $plan_id,
            'qihao' => $qihao,
            'profit_before' => round((float) $profit_before, 2),
            'profit_change' => round((float) $profit_change, 2),
            'profit_after' => round((float) $profit_after, 2),
            'uid' => (int) $uid,
            'lottery_type' => (int) $lottery_type,
            'period_bet_amount' => round((float) $period_bet_amount, 2),
            'period_group_count' => (int) $period_group_count,
            'period_multiple' => round((float) $period_multiple, 2),
            'created_at' => $now,
        ], false);
        return $model->save(false);
    }
}
