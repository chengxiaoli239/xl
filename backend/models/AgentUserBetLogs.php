<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "lt_agent_user_bet_logs".
 *
 * @property int $id
 * @property string $access_token 用户access_token
 * @property int $uid 系统用户id
 * @property int $wp_record_id 网盘日志记录id
 * @property int $member_id 网盘用户id
 * @property string $account 用户账号
 * @property string $bet_logs 下注原始日志
 * @property string $bet_logs_n 下注转换后日志
 * @property string $bet_codes 下注号码
 * @property string $bet_logs_codes_hz 下注codes_hz
 * @property int $bet_counts 下注号码组数
 * @property string $bet_single 下注倍数
 * @property string $bet_money 金额
 * @property string $bet_codes_op 下注反买号码
 * @property int $bet_op_counts 下注反买号码组数
 * @property string $bet_op_single 跟投倍数
 * @property string $bet_op_money 反买金额
 * @property int $bet_type 下注类型：1反买2正买
 * @property int $playway 下注类型：1二定2三定3四定
 * @property string $from_type 日志来源类型：kuaixuan、kuaiyi
 * @property string $from 来源：page、api
 * @property string $desc 描述
 * @property int $lottery_type 彩种5重启6新疆8幸运五
 * @property string $qihao 期号
 * @property int $status 下注状态0等待下注2下注成功3下注失败
 * @property string $member_bet_time 目标用户下注时间
 * @property int $tz_system_id 系统id
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 * @property string $operation_content_md5 operation_content的MD5值，用于联合索引
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
            [['uid', 'wp_record_id', 'member_id', 'bet_counts', 'bet_op_counts', 'bet_type', 'playway', 'lottery_type', 'status', 'tz_system_id', 'created_at', 'updated_at'], 'integer'],
            [['bet_logs', 'bet_logs_n', 'bet_logs_codes_hz', 'bet_codes', 'bet_codes_op', 'from_type', 'from'], 'string'],
            [['bet_single', 'bet_money', 'bet_op_single', 'bet_op_money'], 'number'],
            [['member_bet_time', 'update_time'], 'safe'],
            [['access_token', 'account', 'qihao'], 'string', 'max' => 32],
            [['desc'], 'string', 'max' => 255],
            [['operation_content_md5'], 'string', 'max' => 32],
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
            'wp_record_id' => 'Wp Record ID',
            'member_id' => 'Member ID',
            'account' => 'Account',
            'bet_logs' => 'Bet Logs',
            'bet_logs_n' => 'Bet Logs N',
            'bet_logs_codes_hz' => 'Bet Logs Codes Hz',
            'bet_codes' => 'Bet Codes',
            'bet_counts' => 'Bet Counts',
            'bet_single' => 'Bet Single',
            'bet_money' => 'Bet Money',
            'bet_codes_op' => 'Bet Codes Op',
            'bet_op_counts' => 'Bet Op Counts',
            'bet_op_single' => 'Bet Op Single',
            'bet_op_money' => 'Bet Op Money',
            'bet_type' => 'Bet Type',
            'playway' => 'Playway',
            'from_type' => 'From Type',
            'from' => 'From',
            'desc' => 'Desc',
            'lottery_type' => 'Lottery Type',
            'qihao' => 'Qihao',
            'status' => 'Status',
            'member_bet_time' => 'Member Bet Time',
            'tz_system_id' => 'Tz System ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'update_time' => 'Update Time',
            'operation_content_md5' => 'Operation Content MD5',
        ];
    }
}