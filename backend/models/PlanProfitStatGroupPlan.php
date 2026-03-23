<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $group_id
 * @property int $plan_id
 * @property int $created_at
 */
class PlanProfitStatGroupPlan extends ActiveRecord
{
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public static function tableName()
    {
        return '{{%plan_profit_stat_group_plans}}';
    }

    public function rules()
    {
        return [
            [['group_id', 'plan_id'], 'required'],
            [['group_id', 'plan_id', 'created_at'], 'integer'],
        ];
    }

    public function getGroup()
    {
        return $this->hasOne(PlanProfitStatGroup::class, ['id' => 'group_id']);
    }
}
