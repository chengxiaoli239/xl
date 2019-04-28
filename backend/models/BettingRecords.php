<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%betting_records}}".
 *
 * @property int $id
 * @property string $codes 投注号码
 * @property int $uid 用户id
 * @property string $account
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
 * @property int $order_type 订单来源：1跟投订单 2大数据订单 3系统计划订单
 * @property int $tz_system_id 投注系统tz_systems.id
 * @property string $lotteryclass 彩种
 * @property int $createtime
 * @property string $create_time 投注时间
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class BettingRecords extends \common\models\base\BaseModel
{
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
            [['codes', 'snid'], 'string'],
            [['uid', 'playway', 'tz_type', 'status', 'cancel_status', 'plan_id', 'buy_type', 'is_simulate', 'order_type', 'tz_system_id', 'createtime', 'updated_at', 'created_at'], 'integer'],
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
            'id' => Yii::t('app', 'ID'),
            'codes' => Yii::t('app', '投注号码'),
            'uid' => Yii::t('app', '用户id'),
            'account' => Yii::t('app', 'Account'),
            'playway' => Yii::t('app', '投注方式：10定位胆'),
            'tz_type' => Yii::t('app', '投注类型'),
            'playway_name' => Yii::t('app', '投注方式'),
            'betting_money' => Yii::t('app', '投注金额'),
            'bonus' => Yii::t('app', '中奖金额'),
            'single' => Yii::t('app', '倍数(元)'),
            'profits' => Yii::t('app', '利润'),
            'qihao' => Yii::t('app', '期号'),
            'kj_codes' => Yii::t('app', '开奖号码'),
            'position' => Yii::t('app', '定位位置'),
            'status' => Yii::t('app', '中奖状态：0:正常、1:中奖、2:未中奖'),
            'cancel_status' => Yii::t('app', '撤单状态：0未撤单1已撤单'),
            'sn' => Yii::t('app', '方案号'),
            'snid' => Yii::t('app', '订单号'),
            'plan_id' => Yii::t('app', '计划id'),
            'buy_type' => Yii::t('app', '购买方向:0反买1正买'),
            'is_simulate' => Yii::t('app', '是否模拟投注'),
            'order_type' => Yii::t('app', '订单来源：1跟投订单 2大数据订单 3系统计划订单'),
            'tz_system_id' => Yii::t('app', '投注系统tz_systems.id'),
            'lotteryclass' => Yii::t('app', '彩种'),
            'createtime' => Yii::t('app', 'Createtime'),
            'create_time' => Yii::t('app', '投注时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'created_at' => Yii::t('app', '创建时间'),
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
