<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%matchs}}".
 *
 * @property int $id
 * @property int $system_id 站点id,lt_tz_systems.id
 * @property int $game_type 球赛类型:3棒球29足球33网球
 * @property string $game_type_name 球赛类型名称
 * @property int $g_id 比赛场次id
 * @property int $game_id 比赛记录id
 * @property string $game_name 比赛场次名称
 * @property string $player_1 选手1
 * @property string $player_2 选手2
 * @property string $player_1_water 选手1水位
 * @property string $player_2_water 选手2水位
 * @property int $status 系统开启状态
 * @property int $is_bind 是否绑定
 * @property int $bind_id 绑定关联id
 * @property int $type 类型:1时时彩2网球
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time
 */
class Matchs extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%matchs}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['system_id', 'game_type', 'g_id', 'game_id', 'status', 'is_bind', 'bind_id', 'type', 'created_at', 'updated_at'], 'integer'],
            [['player_1_water', 'player_2_water'], 'number'],
            [['update_time'], 'safe'],
            [['game_type_name'], 'string', 'max' => 64],
            [['game_name', 'player_1', 'player_2'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'system_id' => Yii::t('app', '站点id,lt_tz_systems.id'),
            'game_type' => Yii::t('app', '球赛类型:3棒球29足球33网球'),
            'game_type_name' => Yii::t('app', '球赛类型名称'),
            'g_id' => Yii::t('app', '比赛场次id'),
            'game_id' => Yii::t('app', '比赛记录id'),
            'game_name' => Yii::t('app', '比赛场次名称'),
            'player_1' => Yii::t('app', '选手1'),
            'player_2' => Yii::t('app', '选手2'),
            'player_1_water' => Yii::t('app', '选手1水位'),
            'player_2_water' => Yii::t('app', '选手2水位'),
            'status' => Yii::t('app', '系统开启状态'),
            'is_bind' => Yii::t('app', '是否绑定'),
            'bind_id' => Yii::t('app', '绑定关联id'),
            'type' => Yii::t('app', '类型:1时时彩2网球'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', 'Update Time'),
        ];
    }

    /**
     * @inheritdoc
     * @return MatchsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new MatchsQuery(get_called_class());
    }
}
