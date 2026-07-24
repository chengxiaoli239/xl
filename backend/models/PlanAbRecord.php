<?php

namespace backend\models;

/**
 * Per-period A signal and B betting display record for plan type 13.
 */
class PlanAbRecord extends \common\models\base\BaseModel
{
    const BET_STATUS_NONE = 0;
    const BET_STATUS_PENDING = 1;
    const BET_STATUS_WIN = 2;
    const BET_STATUS_LOSE = 3;

    public static function tableName()
    {
        return '{{%plan_ab_records}}';
    }

    public function rules()
    {
        return [
            [['plan_id', 'uid', 'lottery_type', 'a_hit', 'b_hit', 'is_bet', 'bet_record_id', 'bet_status', 'strategy_status_before', 'strategy_status_after', 'singles_key', 'created_at', 'updated_at'], 'integer'],
            [['single'], 'number'],
            [['kj_codes', 'qihao', 'strategy_action'], 'string'],
            [['bet_codes'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'qihao' => '期号',
            'kj_codes' => '开奖号码',
            'a_hit' => 'A判断',
            'b_hit' => 'B判断',
            'is_bet' => '是否下注',
            'bet_codes' => '实际投注B',
            'bet_status' => 'B投注结果',
            'strategy_action' => '策略动作',
            'single' => '倍数',
            'singles_key' => '倍数档位',
        ];
    }
}
