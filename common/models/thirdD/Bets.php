<?php

namespace common\models\thirdD;

use Yii;

/**
 * This is the model class for table "{{%bets}}".
 *
 * @property int $id
 * @property int $user_id 用户id,也是代理id
 * @property int $wechat_user_id 会员id, wechat_user.id`
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

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'wechat_user_id', 'order_id', 'play_method', 'count', 'status', 'push_status', 'cancel_status', 'is_simulate', 'lottery_type', 'is_profits_record', 'created_at', 'updated_at'], 'integer'],
            [['codes', 'bet_desc', 'created_at', 'updated_at'], 'required'],
            [['codes', 'bet_desc', 'api_code_datas'], 'string'],
            [['bet_money', 'bonus', 'single', 'ratio', 'profits'], 'number'],
            [['update_at'], 'safe'],
            [['qihao'], 'string', 'max' => 20],
            [['kj_codes'], 'string', 'max' => 24],
            [['push_desc'], 'string', 'max' => 40],
            [['lottery_name'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', '用户id,也是代理id'),
            'wechat_user_id' => Yii::t('app', '会员id, wechat_user.id`'),
            'order_id' => Yii::t('app', '订单id'),
            'play_method' => Yii::t('app', '玩法ID'),
            'codes' => Yii::t('app', '投注号码'),
            'bet_money' => Yii::t('app', '投注金额'),
            'bonus' => Yii::t('app', '中奖金额'),
            'single' => Yii::t('app', '倍(元)'),
            'count' => Yii::t('app', '号码数量'),
            'ratio' => Yii::t('app', '比率:奖金除于本金'),
            'profits' => Yii::t('app', '利润'),
            'qihao' => Yii::t('app', '期号'),
            'kj_codes' => Yii::t('app', '开奖号码'),
            'status' => Yii::t('app', '中奖状态：0:正常、1:中奖、2:未中奖、3已撤单'),
            'push_status' => Yii::t('app', '推送盘口状态'),
            'push_desc' => Yii::t('app', '推送结果'),
            'cancel_status' => Yii::t('app', '撤单状态：0未撤单1已撤单'),
            'is_simulate' => Yii::t('app', '是否模拟投注0否1是'),
            'lottery_name' => Yii::t('app', '彩种'),
            'lottery_type' => Yii::t('app', '彩种类型:26福彩27排列三'),
            'is_profits_record' => Yii::t('app', '是否计算盈利记录0否1是'),
            'bet_desc' => Yii::t('app', '下注文本'),
            'created_at' => Yii::t('app', '创建时间'),
            'api_code_datas' => Yii::t('app', 'api识别结果'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_at' => Yii::t('app', '更新时间'),
        ];
    }
}
