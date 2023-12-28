<?php

namespace backend\models\wechat;

use Yii;

/**
 * This is the model class for table "{{%bets}}".
 *
 * @property int $id
 * @property int $user_id 用户id,也是代理id
 * @property int $wechat_user_id 会员id, wechat_user.id
 * @property int $order_id 订单id
 * @property int $play_method 玩法ID
 * @property string $codes 投注号码
 * @property string $bet_money 投注金额
 * @property string $bonus 中奖金额
 * @property double $single 倍(元)
 * @property int $count 号码数量
 * @property string $ratio 比率:奖金除于本金
 * @property string $profits 利润
 * @property string $qihao 期号
 * @property string $kj_codes 开奖号码
 * @property int $status 中奖状态：0:正常、1:中奖、2:未中奖、3已撤单
 * @property int $push_status 推送盘口状态
 * @property string $push_desc 推送结果
 * @property int $cancel_status 撤单状态：0未撤单1已撤单
 * @property string $new_msg_id 消息ID
 * @property int $is_need_confirm 是否需确认
 * @property int $reply_type 回复类型：0即时1打包
 * @property int $has_reply 回复状态0:未回复2成功3失败4无需回复
 * @property string $reply_content 回复内容
 * @property int $is_simulate 是否模拟投注0否1是
 * @property string $lottery_name 彩种
 * @property int $lottery_type 彩种类型:26福彩27排列三
 * @property int $is_profits_record 是否计算盈利记录0否1是
 * @property string $bet_desc 下注文本
 * @property int $created_at 创建时间
 * @property string $api_code_datas api识别结果
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
        return '{{%bets}}';
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
            [['user_id', 'wechat_user_id', 'order_id', 'play_method', 'count', 'status', 'push_status', 'cancel_status', 'is_need_confirm', 'reply_type', 'has_reply', 'is_simulate', 'lottery_type', 'is_profits_record', 'created_at', 'updated_at'], 'integer'],
            [['codes', 'bet_desc', 'created_at', 'updated_at'], 'required'],
            [['codes', 'push_desc', 'reply_content', 'bet_desc', 'api_code_datas'], 'string'],
            [['bet_money', 'bonus', 'single', 'ratio', 'profits'], 'number'],
            [['update_at'], 'safe'],
            [['qihao'], 'string', 'max' => 20],
            [['kj_codes', 'new_msg_id'], 'string', 'max' => 24],
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
            //'user_id' => '用户id,也是代理id',
            'wechat_user_id' => '会员id, wechat_user.id',
            'order_id' => '订单id',
            'play_method' => '玩法ID',
            'codes' => '投注号码',
            'bet_money' => '投注金额',
            'bonus' => '中奖金额',
            'single' => '倍(元)',
            'count' => '号码数量',
            'ratio' => '比率:奖金除于本金',
            'profits' => '利润',
            'qihao' => '期号',
            'kj_codes' => '开奖号码',
            'status' => '中奖状态：0:正常、1:中奖、2:未中奖、3已撤单',
            'push_status' => '推送盘口状态',
            'push_desc' => '推送结果',
            'cancel_status' => '撤单状态：0未撤单1已撤单',
            'new_msg_id' => '消息ID',
            'is_need_confirm' => '是否需确认',
            'reply_type' => '回复类型：0即时1打包',
            'has_reply' => '回复状态0:未回复2成功3失败4无需回复',
            'reply_content' => '回复内容',
            'is_simulate' => '是否模拟投注0否1是',
            'lottery_name' => '彩种',
            'lottery_type' => '彩种类型:26福彩27排列三',
            'is_profits_record' => '是否计算盈利记录0否1是',
            'bet_desc' => '下注文本',
            'created_at' => '创建时间',
            'api_code_datas' => 'api识别结果',
            'updated_at' => '更新时间',
            'update_at' => '更新时间',
        ];
    }
}
