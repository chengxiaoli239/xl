<?php

namespace backend\models\sports;

use Yii;

/**
 * This is the model class for table "{{%sports_in_play}}".
 *
 * @property int $id
 * @property string $league_matches_id 联赛id
 * @property string $league_matches_name 联赛名称
 * @property string $event_id 比赛场次id,
 * @property int $play_type 玩法类型：1:角球2:1X2 3让球4大小
 * @property int $game_court 全场类型：1全场2上半场3下半场
 * @property int $plate_id A盘口id
 * @property string $home_name 主队名称
 * @property string $away_name 客队名称
 * @property string $home_score 主队得分
 * @property string $away_score 客队得分
 * @property string $plate_1X2_odds_1 1X2赔率1
 * @property string $plate_1X2_odds_2 1X2赔率2
 * @property string $plate_1X2_odds_3 1X2赔率3
 * @property string $plate_rolling_home 让球主队赔率
 * @property string $plate_rolling_away 让球客队赔率
 * @property string $bet_url 下注入口地址
 * @property string $plate_bet_conditions 下注条件
 * @property string $desc 描述备注
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class SportsInPlay extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%sports_in_play}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['play_type', 'game_court', 'plate_id', 'created_at', 'updated_at'], 'integer'],
            [['plate_rolling_home', 'plate_rolling_away'], 'number'],
            [['desc'], 'string'],
            [['update_time'], 'safe'],
            [['league_matches_id', 'league_matches_name', 'event_id', 'home_name', 'away_name', 'home_score', 'away_score'], 'string', 'max' => 64],
            [['plate_1X2_odds_1', 'plate_1X2_odds_2', 'plate_1X2_odds_3', 'bet_url', 'plate_bet_conditions'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'league_matches_id' => Yii::t('app', '联赛id'),
            'league_matches_name' => Yii::t('app', '联赛名称'),
            'event_id' => Yii::t('app', '比赛场次id,'),
            'play_type' => Yii::t('app', '玩法类型：1:角球2:1X2 3让球4大小'),
            'game_court' => Yii::t('app', '全场类型：1全场2上半场3下半场'),
            'plate_id' => Yii::t('app', 'A盘口id'),
            'home_name' => Yii::t('app', '主队名称'),
            'away_name' => Yii::t('app', '客队名称'),
            'home_score' => Yii::t('app', '主队得分'),
            'away_score' => Yii::t('app', '客队得分'),
            'plate_1X2_odds_1' => Yii::t('app', '1X2赔率1'),
            'plate_1X2_odds_2' => Yii::t('app', '1X2赔率2'),
            'plate_1X2_odds_3' => Yii::t('app', '1X2赔率3'),
            'plate_rolling_home' => Yii::t('app', '让球主队赔率'),
            'plate_rolling_away' => Yii::t('app', '让球客队赔率'),
            'bet_url' => Yii::t('app', '下注入口地址'),
            'plate_bet_conditions' => Yii::t('app', '下注条件'),
            'desc' => Yii::t('app', '描述备注'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return SportsInPlayQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SportsInPlayQuery(get_called_class());
    }
}
