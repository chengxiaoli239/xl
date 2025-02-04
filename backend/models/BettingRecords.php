<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%betting_records}}".
 *
 * @property int $id
 * @property string $codes 投注号码
 * @property int $uid 用户id,代理id
 * @property string $account
 * @property int $member_id 会员id
 * @property int $playway 投注方式：10定位胆
 * @property int $tz_type 投注类型
 * @property string $playway_name 投注方式
 * @property string $betting_money 投注金额
 * @property string $bonus 中奖金额
 * @property double $single 倍数(元)
 * @property string $profits 利润
 * @property string $qihao 期号
 * @property string $kj_codes 开奖号码
 * @property string $position 定位位置
 * @property int $status 中奖状态：0:正常、1:中奖、2:未中奖
 * @property int $cancel_status 撤单状态：0未撤单1已撤单
 * @property string $sn 方案号
 * @property string $snid 订单号
 * @property int $plan_id 计划id
 * @property int $buy_type 购买方向:0反买1正买
 * @property int $is_simulate 是否模拟投注
 * @property int $is_batch_simulate 是否批量模拟投注
 * @property int $order_type 订单来源：1跟投订单 2大数据订单 3系统计划订单
 * @property int $tz_system_id 投注系统tz_systems.id
 * @property string $lotteryclass 彩种
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property int $is_profits_record 是否计算盈利记录0否1是
 * @property int $is_area_profits 是否区间盈利记录0否1是
 * @property string $post_desc 下注文本
 * @property int $createtime
 * @property string $create_time 投注时间
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class BettingRecords extends \common\models\base\BaseModel
{
    const IS_SIMULATE_NO = 0;
    const IS_SIMULATE_YES = 1;
    const IS_SIMULATE_OPTION = [
        self::IS_SIMULATE_YES => '模拟',
        self::IS_SIMULATE_NO => '真实',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%betting_records}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['codes', 'snid', 'post_desc'], 'string'],
            [['uid', 'member_id', 'playway', 'tz_type', 'status', 'cancel_status', 'plan_id', 'buy_type', 'is_simulate', 'is_batch_simulate', 'order_type', 'tz_system_id', 'lottery_type', 'is_profits_record', 'is_area_profits', 'createtime', 'updated_at', 'created_at'], 'integer'],
            [['betting_money', 'bonus', 'single', 'profits'], 'number'],
            [['account', 'sn'], 'string', 'max' => 255],
            [['playway_name', 'create_time'], 'string', 'max' => 32],
            [['qihao'], 'string', 'max' => 20],
            [['kj_codes'], 'string', 'max' => 24],
            [['position'], 'string', 'max' => 128],
            [['lotteryclass'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'codes' => '投注号码',
            'uid' => '用户id,代理id',
            'account' => 'Account',
            'member_id' => '会员id',
            'playway' => '投注方式：10定位胆',
            'tz_type' => '投注类型',
            'playway_name' => '投注方式',
            'betting_money' => '投注金额',
            'bonus' => '中奖金额',
            'single' => '倍数(元)',
            'profits' => '利润',
            'qihao' => '期号',
            'kj_codes' => '开奖号码',
            'position' => '定位位置',
            'status' => '中奖状态：0:正常、1:中奖、2:未中奖',
            'cancel_status' => '撤单状态：0未撤单1已撤单',
            'sn' => '方案号',
            'snid' => '订单号',
            'plan_id' => '计划id',
            'buy_type' => '购买方向:0反买1正买',
            'is_simulate' => '是否模拟投注',
            'is_batch_simulate' => '是否批量模拟',
            'order_type' => '订单来源：1跟投订单 2大数据订单 3系统计划订单',
            'tz_system_id' => '投注系统tz_systems.id',
            'lotteryclass' => '彩种',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'is_profits_record' => '是否计算盈利记录0否1是',
            'is_area_profits' => '是否区间盈利记录0否1是',
            'post_desc' => '下注文本',
            'createtime' => 'Createtime',
            'create_time' => '投注时间',
            'updated_at' => '更新时间',
            'created_at' => '创建时间',
        ];
    }

    /**
     * @inheritdoc
     * @return BettingRecordsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new BettingRecordsQuery(get_called_class());
    }
}