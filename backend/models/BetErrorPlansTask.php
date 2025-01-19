<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%bet_error_plans_task}}".
 *
 * @property int $id
 * @property string $codes 投注号码
 * @property int $uid 用户id
 * @property int $agent_id 代理id
 * @property string $account
 * @property string $bet_url
 * @property string $bet_headers
 * @property string $post_datas 请求数据
 * @property int $playway 投注方式：1二定2三定3四定
 * @property int $tz_type 投注类型
 * @property string $playway_name 投注方式
 * @property string $bet_money 投注金额
 * @property double $single 倍数(元)
 * @property string $qihao 期号
 * @property string $kj_codes 开奖号码
 * @property int $status 状态：0:正常1:处理成功2:处理失败
 * @property string $sn 方案号
 * @property string $snid 订单号，用于撤单
 * @property int $plan_id 计划id
 * @property int $is_local_bet 是否本地下注
 * @property int $bet_direct 下方向
 * @property int $tz_system_id 投注系统tz_systems.id
 * @property string $lotteryclass 彩种
 * @property int $lottery_type 彩种类型
 * @property int $bet_sort_key 分组key
 * @property string $post_desc 下注文本
 * @property string $error_desc 错误描述
 * @property string $updated_time 更新时间
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class BetErrorPlansTask extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%bet_error_plans_task}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['codes', 'bet_headers', 'post_datas', 'post_desc', 'error_desc'], 'string'],
            [['uid', 'agent_id', 'playway', 'tz_type', 'status', 'plan_id', 'is_local_bet', 'bet_direct', 'tz_system_id', 'lottery_type', 'bet_sort_key', 'updated_at', 'created_at'], 'integer'],
            [['bet_money', 'single'], 'number'],
            [['updated_time'], 'safe'],
            [['account', 'kj_codes'], 'string', 'max' => 24],
            [['bet_url', 'snid'], 'string', 'max' => 240],
            [['playway_name'], 'string', 'max' => 32],
            [['qihao'], 'string', 'max' => 20],
            [['sn'], 'string', 'max' => 255],
            [['lotteryclass'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'codes' => Yii::t('app', '投注号码'),
            'uid' => Yii::t('app', '用户id'),
            'agent_id' => Yii::t('app', '代理id'),
            'account' => Yii::t('app', 'Account'),
            'bet_url' => Yii::t('app', 'Bet Url'),
            'bet_headers' => Yii::t('app', 'Bet Headers'),
            'post_datas' => Yii::t('app', '请求数据'),
            'playway' => Yii::t('app', '投注方式：1二定2三定3四定'),
            'tz_type' => Yii::t('app', '投注类型'),
            'playway_name' => Yii::t('app', '投注方式'),
            'bet_money' => Yii::t('app', '投注金额'),
            'single' => Yii::t('app', '倍数(元)'),
            'qihao' => Yii::t('app', '期号'),
            'kj_codes' => Yii::t('app', '开奖号码'),
            'status' => Yii::t('app', '状态：0:正常1:处理成功2:处理失败'),
            'sn' => Yii::t('app', '方案号'),
            'snid' => Yii::t('app', '订单号，用于撤单'),
            'plan_id' => Yii::t('app', '计划id'),
            'is_local_bet' => Yii::t('app', '是否本地下'),
            'bet_direct' => Yii::t('app', '下方向'),
            'tz_system_id' => Yii::t('app', '投注系统tz_systems.id'),
            'lotteryclass' => Yii::t('app', '彩种'),
            'lottery_type' => Yii::t('app', '彩种类型'),
            'bet_sort_key' => Yii::t('app', '分组key'),
            'post_desc' => Yii::t('app', '下注文本'),
            'error_desc' => Yii::t('app', '错误描述'),
            'updated_time' => Yii::t('app', '更新时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'created_at' => Yii::t('app', '创建时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return BetErrorPlansTaskQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new BetErrorPlansTaskQuery(get_called_class());
    }
}
