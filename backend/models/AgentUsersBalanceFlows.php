<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%agent_users_balance_flows}}".
 *
 * @property int $id
 * @property int $agent_id 代理ID
 * @property string $member_id 用户id
 * @property string $member_account 用户账号
 * @property int $type 类型:1上分2下分
 * @property string $balance 变动积分
 * @property string $balance_now 当前剩余
 * @property string $balance_after 变动结果
 * @property string $desc 备注
 * @property int $status 审核状态(0未审核1通过2未通过)
 * @property string $check_time 审核时间
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class AgentUsersBalanceFlows extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%agent_users_balance_flows}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['agent_id', 'type', 'status', 'created_at', 'updated_at'], 'integer'],
            [['balance', 'balance_now', 'balance_after'], 'number'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['member_id', 'member_account', 'desc'], 'string', 'max' => 64],
            [['check_time'], 'string', 'max' => 12],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'agent_id' => Yii::t('app', '代理ID'),
            'member_id' => Yii::t('app', '用户id'),
            'member_account' => Yii::t('app', '用户账号'),
            'type' => Yii::t('app', '类型:1上分2下分'),
            'balance' => Yii::t('app', '变动积分'),
            'balance_now' => Yii::t('app', '当前剩余'),
            'balance_after' => Yii::t('app', '变动结果'),
            'desc' => Yii::t('app', '备注'),
            'status' => Yii::t('app', '审核状态(0未审核1通过2未通过)'),
            'check_time' => Yii::t('app', '审核时间'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return AgentUsersBalanceFlowsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new AgentUsersBalanceFlowsQuery(get_called_class());
    }
}
