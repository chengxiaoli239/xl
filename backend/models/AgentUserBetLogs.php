<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "lt_agent_user_bet_logs".
 *
 * @property int $id
 * @property string $access_token 用户access_token
 * @property int $uid 系统用户id
 * @property int $member_id 用户id
 * @property string $account 用户账号
 * @property string $bet_logs 下注日志
 * @property string $bet_codes 下注号码
 * @property int $bet_codes_counts 下注号码组数
 * @property string $bet_codes_op 下注反买号码
 * @property int $bet_codes_op_counts 下注反买号码组数
 * @property int $bet_type 下注类型：1反买2正买
 * @property int $planway 下注类型：1二定2三定3四定
 * @property string $desc 描述
 * @property string $lottery_type 彩种5重启6新疆8幸运五
 * @property string $qihao 期号
 * @property int $status 下注状态0等待下注2下注成功3下注失败
 * @property int $tz_system_id 系统id
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class AgentUserBetLogs extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lt_agent_user_bet_logs';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'member_id', 'bet_codes_counts', 'bet_codes_op_counts', 'bet_type', 'planway', 'status', 'tz_system_id', 'created_at', 'updated_at'], 'integer'],
            [['bet_logs', 'bet_codes', 'bet_codes_op'], 'string'],
            [['update_time'], 'safe'],
            [['access_token'], 'string', 'max' => 40],
            [['account', 'qihao'], 'string', 'max' => 32],
            [['desc'], 'string', 'max' => 255],
            [['lottery_type'], 'string', 'max' => 11],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'access_token' => 'Access Token',
            'uid' => 'Uid',
            'member_id' => 'Member ID',
            'account' => 'Account',
            'bet_logs' => 'Bet Logs',
            'bet_codes' => 'Bet Codes',
            'bet_codes_counts' => 'Bet Codes Counts',
            'bet_codes_op' => 'Bet Codes Op',
            'bet_codes_op_counts' => 'Bet Codes Op Counts',
            'bet_type' => 'Bet Type',
            'planway' => 'Planway',
            'desc' => 'Desc',
            'lottery_type' => 'Lottery Type',
            'qihao' => 'Qihao',
            'status' => 'Status',
            'tz_system_id' => 'Tz System ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'update_time' => 'Update Time',
        ];
    }
}
