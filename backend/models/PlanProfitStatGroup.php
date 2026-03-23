<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $uid
 * @property int $lottery_type
 * @property string $name
 * @property int $created_at
 * @property int $updated_at
 */
class PlanProfitStatGroup extends ActiveRecord
{
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public static function tableName()
    {
        return '{{%plan_profit_stat_groups}}';
    }

    public function rules()
    {
        return [
            [['uid', 'lottery_type', 'name'], 'required'],
            [['uid', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 64],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => '用户',
            'lottery_type' => '彩种',
            'name' => '分组名称',
        ];
    }

    public function getMembers()
    {
        return $this->hasMany(PlanProfitStatGroupPlan::class, ['group_id' => 'id']);
    }
}
