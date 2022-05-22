<?php

namespace backend\models\sports;

use Yii;

/**
 * This is the model class for table "{{%sports_plates_games}}".
 *
 * @property int $id
 * @property string $plate_id 盘口id
 * @property string $plate_name 盘口名称
 * @property string $bet_url 下注明细页面链接
 * @property string $league_matches_id 比赛id
 * @property string $league_matches_name 比赛名称
 * @property string $name1 队员1
 * @property string $name1_path 元素1定位
 * @property string $name2 队员1
 * @property string $name2_path 元素2定位
 * @property string $score 比分
 * @property string $game_schedule 比赛进度
 * @property int $is_has_jq 是否有角球
 * @property string $event_id 项目id
 * @property string $desc 描述备注
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class SportsPlatesGames extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%sports_plates_games}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['desc'], 'string'],
            [['created_at', 'updated_at', 'is_has_jq'], 'integer'],
            [['update_time'], 'safe'],
            [['plate_id', 'plate_name', 'league_matches_id', 'league_matches_name', 'name1', 'name2', 'event_id', 'game_schedule', 'score'], 'string', 'max' => 64],
            [['bet_url', 'name1_path', 'name2_path'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'plate_id' => Yii::t('app', '盘口id'),
            'plate_name' => Yii::t('app', '盘口名称'),
            'bet_url' => Yii::t('app', '下注明细页面链接'),
            'league_matches_id' => Yii::t('app', '比赛id'),
            'league_matches_name' => Yii::t('app', '比赛名称'),
            'name1' => Yii::t('app', '主队'),
            'name1_path' => Yii::t('app', '元素1定位'),
            'name2' => Yii::t('app', '客队'),
            'name2_path' => Yii::t('app', '元素2定位'),
            'score' => Yii::t('app', '比分'),
            'event_id' => Yii::t('app', '项目id'),
            'is_has_jq' => Yii::t('app', '是否有角球'),
            'game_schedule' => Yii::t('app', '比赛进度'),
            'desc' => Yii::t('app', '描述备注'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return SportsPlatesGamesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SportsPlatesGamesQuery(get_called_class());
    }
}
