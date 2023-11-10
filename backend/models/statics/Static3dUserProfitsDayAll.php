<?php

namespace backend\models\statics;

use Yii;

/**
 * This is the model class for table "{{%static_3d_user_profits_day_all}}".
 *
 * @property int $id
 * @property string $date 日期
 * @property int $user_id 系统用户id(代理id)
 * @property int $wechat_user_id 微信用户表id
 * @property string $wechat_user_name 微信id
 * @property string $bet_money 日投注金额
 * @property string $bonus 中奖金额
 * @property string $up_money 上分
 * @property string $down_money 下分
 * @property string $profits 利润
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class Static3dUserProfitsDayAll extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_3d_user_profits_day_all}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['date', 'update_time'], 'safe'],
            [['user_id', 'wechat_user_id', 'created_at', 'updated_at'], 'integer'],
            [['bet_money', 'bonus', 'up_money', 'down_money', 'profits'], 'number'],
            [['updated_at'], 'required'],
            [['wechat_user_name'], 'string', 'max' => 32],
            [['date', 'wechat_user_id'], 'unique', 'targetAttribute' => ['date', 'wechat_user_id']],
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
            'up_money' => Yii::t('app', '上分'),
            'down_money' => Yii::t('app', '下分'),
            'profits' => Yii::t('app', '利润'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }
}
