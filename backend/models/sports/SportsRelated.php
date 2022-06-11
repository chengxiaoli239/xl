<?php

namespace backend\models\sports;

use Yii;

/**
 * This is the model class for table "{{%sports_related}}".
 *
 * @property int $id
 * @property int $uid 用户id
 * @property int $relate_A_game_id 关联A比赛场次id,
 * @property int $relate_B_game_id 关联B比赛场次id,
 * @property string $relate_type 关联类型:1比分网2下注盘口
 * @property string $relate_sport_type 体育类型:1足球2网球3篮球
 * @property int $plate_A_id A盘口id
 * @property string $plate_A_name A盘口名称
 * @property int $plate_B_id B盘口id
 * @property string $plate_B_name B盘口名称
 * @property string $base_url_A A网盘地址
 * @property string $base_url_B B网盘地址
 * @property string $plate_bet_url_A A盘口下注地址
 * @property string $plate_bet_url_B B盘口下注地址
 * @property int $status 状态
 * @property string $plate_bet_conditions 下注条件
 * @property string $desc 描述备注
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class SportsRelated extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%sports_related}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'relate_A_game_id', 'relate_B_game_id', 'plate_A_id', 'plate_B_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['desc'], 'string'],
            [['update_time'], 'safe'],
            [['relate_type', 'relate_sport_type', 'plate_A_name', 'plate_B_name'], 'string', 'max' => 64],
            [['base_url_A', 'base_url_B', 'plate_bet_url_A', 'plate_bet_url_B', 'plate_bet_conditions'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'uid' => Yii::t('app', '用户id'),
            'relate_A_game_id' => Yii::t('app', '关联A比赛场次id,'),
            'relate_B_game_id' => Yii::t('app', '关联B比赛场次id,'),
            'relate_type' => Yii::t('app', '关联类型:1比分网2下注盘口'),
            'relate_sport_type' => Yii::t('app', '体育类型:1足球2网球3篮球'),
            'plate_A_id' => Yii::t('app', 'A盘口id'),
            'plate_A_name' => Yii::t('app', 'A盘口名称'),
            'plate_B_id' => Yii::t('app', 'B盘口id'),
            'plate_B_name' => Yii::t('app', 'B盘口名称'),
            'base_url_A' => Yii::t('app', 'A网盘地址'),
            'base_url_B' => Yii::t('app', 'B网盘地址'),
            'plate_bet_url_A' => Yii::t('app', 'A盘口下注地址'),
            'plate_bet_url_B' => Yii::t('app', 'B盘口下注地址'),
            'status' => Yii::t('app', '状态'),
            'plate_bet_conditions' => Yii::t('app', '下注条件'),
            'desc' => Yii::t('app', '描述备注'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return SportsRelatedQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SportsRelatedQuery(get_called_class());
    }
}
