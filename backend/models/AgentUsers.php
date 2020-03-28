<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%agent_users}}".
 *
 * @property int $id
 * @property int $agent_id 代理ID
 * @property string $name 账号名称
 * @property string $desc 备注
 * @property string $images 头像
 * @property string $balance 用户积分
 * @property int $is_tuo 托
 * @property int $is_chi 吃
 * @property int $is_cha 查
 * @property int $is_bind 绑定
 * @property string $all_bet_money 投分
 * @property string $today_profits_loss 今日盈亏
 * @property string $all_profits_loss 总盈亏
 * @property string $bet_url 游戏链接
 * @property string $token token
 * @property int $status 系统开启状态
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class AgentUsers extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%agent_users}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['agent_id', 'is_tuo', 'is_chi', 'is_cha', 'is_bind', 'status', 'created_at', 'updated_at'], 'integer'],
            [['balance', 'all_bet_money', 'today_profits_loss', 'all_profits_loss'], 'number'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['name', 'desc', 'token'], 'string', 'max' => 64],
            [['images'], 'string', 'max' => 250],
            [['bet_url'], 'string', 'max' => 255],
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
            'name' => Yii::t('app', '账号名称'),
            'desc' => Yii::t('app', '备注'),
            'images' => Yii::t('app', '头像'),
            'balance' => Yii::t('app', '用户积分'),
            'is_tuo' => Yii::t('app', '托'),
            'is_chi' => Yii::t('app', '吃'),
            'is_cha' => Yii::t('app', '查'),
            'is_bind' => Yii::t('app', '绑定'),
            'all_bet_money' => Yii::t('app', '投分'),
            'today_profits_loss' => Yii::t('app', '今日盈亏'),
            'all_profits_loss' => Yii::t('app', '总盈亏'),
            'bet_url' => Yii::t('app', '游戏链接'),
            'token' => Yii::t('app', 'token'),
            'status' => Yii::t('app', '系统开启状态'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return AgentUsersQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new AgentUsersQuery(get_called_class());
    }
}
