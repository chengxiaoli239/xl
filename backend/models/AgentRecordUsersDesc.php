<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%agent_record_users_desc}}".
 *
 * @property int $id
 * @property int $agent_id 代理id
 * @property int $member_id 用户id
 * @property string $member_account 用户账号
 * @property string $token 用户token
 * @property string $desc 文本
 * @property string $return 返回信息
 * @property int $type 业务类型:1上分2下分3查询开奖4投注
 * @property string $lottery_type 彩种5重启6新疆8幸运五
 * @property string $qihao 期号
 * @property int $status 结算状态0未结算1带结算2已结算
 * @property string $user_info 用户信息
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class AgentRecordUsersDesc extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%agent_record_users_desc}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['agent_id', 'member_id', 'type', 'status', 'created_at', 'updated_at'], 'integer'],
            [['user_info'], 'string'],
            [['update_time'], 'safe'],
            [['member_account', 'qihao'], 'string', 'max' => 32],
            [['token', 'desc', 'return'], 'string', 'max' => 255],
            [['lottery_type'], 'string', 'max' => 11],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'agent_id' => Yii::t('app', '代理id'),
            'member_id' => Yii::t('app', '用户id'),
            'member_account' => Yii::t('app', '用户账号'),
            'token' => Yii::t('app', '用户token'),
            'desc' => Yii::t('app', '文本'),
            'return' => Yii::t('app', '返回信息'),
            'type' => Yii::t('app', '业务类型:1上分2下分3查询开奖4投注'),
            'lottery_type' => Yii::t('app', '彩种5重启6新疆8幸运五'),
            'qihao' => Yii::t('app', '期号'),
            'status' => Yii::t('app', '结算状态0未结算1带结算2已结算'),
            'user_info' => Yii::t('app', '用户信息'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return AgentRecordUsersDescQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new AgentRecordUsersDescQuery(get_called_class());
    }
}
