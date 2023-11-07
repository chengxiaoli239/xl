<?php

namespace backend\models\statics;

use Yii;

/**
 * This is the model class for table "{{%static_3d_user_profits_day}}".
 *
 * @property int $id
 * @property string $date 日期
 * @property int $user_id 系统用户id(代理id)
 * @property int $wechat_user_id 微信用户表id
 * @property string $wechat_user_name 微信id
 * @property string $bet_money 日投注金额
 * @property string $bonus 中奖金额
 * @property string $profits 利润
 * @property int $lottery_type 彩种类型26福彩27排三
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class Static3dUserProfitsDay extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_3d_user_profits_day}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['date', 'update_time'], 'safe'],
            [['user_id', 'wechat_user_id', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['bet_money', 'bonus', 'profits'], 'number'],
            [['updated_at'], 'required'],
            [['wechat_user_name'], 'string', 'max' => 32],
            [['date', 'user_id', 'lottery_type'], 'unique', 'targetAttribute' => ['date', 'user_id', 'lottery_type']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'date' => Yii::t('app', '日期'),
            'user_id' => Yii::t('app', '系统用户id(代理id)'),
            'wechat_user_id' => Yii::t('app', '微信用户表id'),
            'wechat_user_name' => Yii::t('app', '微信id'),
            'bet_money' => Yii::t('app', '日投注金额'),
            'bonus' => Yii::t('app', '中奖金额'),
            'profits' => Yii::t('app', '利润'),
            'lottery_type' => Yii::t('app', '彩种类型26福彩27排三'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }
}
