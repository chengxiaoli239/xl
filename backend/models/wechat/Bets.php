<?php

namespace backend\models\wechat;

use Yii;

/**
 * This is the model class for table "lt_bets".
 *
 * @property int $id
 * @property int $user_id 用户id,也是代理id
 * @property int $wechat_user_id 会员id, wechat_user.id`
 * @property int $order_id 订单id
 * @property int $play_method 玩法ID
 * @property string $codes 投注号码
 * @property string $bet_money 投注金额
 * @property string $bonus 中奖金额
 * @property double $single 倍数(元)
 * @property int $count 号码数量
 * @property string $ratio 比率:奖金除于本金
 * @property string $profits 利润
 * @property string $qihao 期号
 * @property string $kj_codes 开奖号码
 * @property int $status 中奖状态：0:正常、1:中奖、2:未中奖
 * @property int $push_status 中奖状态：0:待推、2:成功 3 失败
 * @property string $push_desc 推送结果文案
 * @property int $cancel_status 撤单状态：0未撤单1已撤单
 * @property int $is_simulate 是否模拟投注0否1是
 * @property string $lottery_name 彩种
 * @property int $lottery_type 彩种类型:26福彩27排列三
 * @property int $is_profits_record 是否计算盈利记录0否1是
 * @property string $bet_desc 下注文本
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_at 更新时间
 */
class Bets extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lt_bets';
    }

    // 定义与wechat_user表的关联关系
    public function getWechatUser()
    {
        return $this->hasOne(WechatUser::class, ['id' => 'wechat_user_id']);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'wechat_user_id', 'order_id', 'play_method', 'count', 'status', 'push_status', 'cancel_status', 'is_simulate', 'lottery_type', 'is_profits_record', 'created_at', 'updated_at'], 'integer'],
            [['codes', 'bet_desc', 'created_at', 'updated_at'], 'required'],
            [['codes', 'push_desc', 'bet_desc'], 'string'],
            [['bet_money', 'bonus', 'single', 'ratio', 'profits'], 'number'],
            [['update_at'], 'safe'],
            [['qihao'], 'string', 'max' => 20],
            [['kj_codes'], 'string', 'max' => 24],
            [['lottery_name'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            //'user_id' => '用户ID',
            'wechat_user_id' => '微信ID',
            'order_id' => '单号',
            'play_method' => '玩法',
            'codes' => '号码',
            'bet_money' => '总投[元]',
            'bonus' => '中奖',
            'single' => '倍[元]',
            'count' => '数量',
            'ratio' => 'Ratio',
            'profits' => '利润',
            'qihao' => '期号',
            'kj_codes' => '开奖',
            'status' => '状态',
            'push_status' => '推送状态',
            'push_desc' => '推送描述',
            'cancel_status' => 'Cancel Status',
            'is_simulate' => '模拟',
            'lottery_name' => '类型',
            'lottery_type' => 'Lottery Type',
            'is_profits_record' => 'Is Profits Record',
            'bet_desc' => '文本',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'update_at' => '更新时间',
        ];
    }
}
