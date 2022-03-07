<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%import_plan_codes}}".
 *
 * @property int $id
 * @property int $uid 类型名称
 * @property int $plan_id 投注/购买类型
 * @property string $plan_id_sort_key 计划号码组序号
 * @property string $codes 导入号码
 * @property int $status 状态
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class ImportPlanCodes extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%import_plan_codes}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'plan_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['codes', 'plan_id_sort_key'], 'string'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => '类型名称',
            'plan_id' => '投注/购买类型',
            'plan_id_sort_key' => '计划号码组序号',
            'codes' => '导入号码',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return ImportPlanCodesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ImportPlanCodesQuery(get_called_class());
    }
}
