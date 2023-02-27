<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%plan_static_profits}}".
 *
 * @property int $id
 * @property int $plan_id 计划id
 * @property int $uid 用户id,默认为系统
 * @property string $current_qihao 当前期号
 * @property string $cut_profits 截止当前期收益
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 * @property string $update_time 更新时间
 */
class PlanStaticProfits extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%plan_static_profits}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['plan_id', 'uid', 'updated_at', 'created_at'], 'integer'],
            [['cut_profits'], 'number'],
            [['update_time'], 'safe'],
            [['current_qihao'], 'string', 'max' => 64],
            [['plan_id', 'current_qihao'], 'unique', 'targetAttribute' => ['plan_id', 'current_qihao']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'plan_id' => 'Plan ID',
            'uid' => 'Uid',
            'current_qihao' => 'Current Qihao',
            'cut_profits' => 'Cut Profits',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'update_time' => 'Update Time',
        ];
    }
}
