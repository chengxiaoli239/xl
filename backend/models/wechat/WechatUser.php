<?php

namespace backend\models\wechat;

use Yii;

/**
 * This is the model class for table "{{%wechat_user}}".
 *
 * @property int $id
 * @property int $user_id user.id,系统用户id
 * @property string $userName 微信id，唯一
 * @property string $nickName 昵称
 * @property string $aliasName 微信号
 * @property int $status 状态-0禁用1启用
 * @property string $balance 余额
 * @property int $is_tuo 托
 * @property int $is_chi 吃
 * @property int $is_private 私
 * @property int $is_cha 查
 * @property int $is_bind 绑定
 * @property int $is_need_confirm 是否需确认
 * @property int $reply_type 回类型
 * @property string $all_bet_money 投分
 * @property string $today_profits_loss 今盈亏
 * @property string $all_profits_loss 总盈亏
 * @property string $bet_url 链接
 * @property string $token token
 * @property int $is_credit 是否信用用户0否1是
 * @property string $bigHead 大头像
 * @property string $smallHead 小头像
 * @property string $labelList 标签列表
 * @property string $remark
 * @property int $expire_time 到期时间
 * @property int $created_at
 * @property int $updated_at
 * @property string $update_at 更新时间
 */
class WechatUser extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wechat_user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'status', 'is_tuo', 'is_chi', 'is_private', 'is_cha', 'is_bind', 'is_need_confirm', 'reply_type', 'is_credit', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            [['balance', 'all_bet_money', 'today_profits_loss', 'all_profits_loss'], 'number'],
            [['created_at', 'updated_at'], 'required'],
            [['update_at'], 'safe'],
            [['userName', 'nickName', 'aliasName', 'bet_url', 'labelList', 'remark'], 'string', 'max' => 255],
            [['token'], 'string', 'max' => 64],
            [['bigHead', 'smallHead'], 'string', 'max' => 640],
            [['user_id', 'userName'], 'unique', 'targetAttribute' => ['user_id', 'userName']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'user.id,系统用户id'),
            'userName' => Yii::t('app', '微信id，唯一'),
            'nickName' => Yii::t('app', '昵称'),
            'aliasName' => Yii::t('app', '微信号'),
            'status' => Yii::t('app', '状态-0禁用1启用'),
            'balance' => Yii::t('app', '余额'),
            'is_tuo' => Yii::t('app', '托'),
            'is_chi' => Yii::t('app', '吃'),
            'is_private' => Yii::t('app', '私'),
            'is_cha' => Yii::t('app', '查'),
            'is_bind' => Yii::t('app', '绑定'),
            'is_need_confirm' => Yii::t('app', '是否需确认'),
            'reply_type' => Yii::t('app', '回类型'),
            'all_bet_money' => Yii::t('app', '投分'),
            'today_profits_loss' => Yii::t('app', '今盈亏'),
            'all_profits_loss' => Yii::t('app', '总盈亏'),
            'bet_url' => Yii::t('app', '链接'),
            'token' => Yii::t('app', 'token'),
            'is_credit' => Yii::t('app', '是否信用用户0否1是'),
            'bigHead' => Yii::t('app', '大头像'),
            'smallHead' => Yii::t('app', '小头像'),
            'labelList' => Yii::t('app', '标签列表'),
            'remark' => Yii::t('app', 'Remark'),
            'expire_time' => Yii::t('app', '到期时间'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'update_at' => Yii::t('app', '更新时间'),
        ];
    }
}
