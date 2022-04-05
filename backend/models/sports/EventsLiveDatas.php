<?php

namespace backend\models\sports;

use Yii;

/**
 * This is the model class for table "{{%events_live_datas}}".
 *
 * @property int $id
 * @property int $uid 用户id
 * @property int $event_id 比赛项目id
 * @property int $clock_minute 比赛进行分钟数
 * @property int $clock_second 当前分钟秒数
 * @property int $clock_minutesLeftInPeriod 场次剩余分钟
 * @property int $clock_secondsLeftInMinute 当前分钟剩余秒数
 * @property int $clock_period 当前节数
 * @property int $clock_running 是否在进行
 * @property int $score_home 主队得分
 * @property int $score_away 客队得分
 * @property string $score_info 比分情况
 * @property string $score_who 得分方
 * @property string $statics_football_home_yellowCards 主队黄牌数
 * @property string $statics_football_way_yellowCards 主队黄牌数
 * @property string $statics_football_home_redCards 主队红牌数
 * @property string $statics_football_way_redCards 客队红牌数
 * @property string $statics_football_home_corners 主队角球数
 * @property string $statics_football_way_corners 客队角球数
 * @property string $liveStatistics 直播数据统计
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class EventsLiveDatas extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%events_live_datas}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'event_id', 'clock_minute', 'clock_second', 'clock_minutesLeftInPeriod', 'clock_secondsLeftInMinute', 'clock_period', 'clock_running', 'score_home', 'score_away', 'created_at', 'updated_at'], 'integer'],
            [['liveStatistics'], 'string'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['score_info', 'score_who', 'statics_football_home_yellowCards', 'statics_football_way_yellowCards', 'statics_football_home_redCards', 'statics_football_way_redCards', 'statics_football_home_corners', 'statics_football_way_corners'], 'string', 'max' => 64],
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
            'event_id' => Yii::t('app', '比赛项目id'),
            'clock_minute' => Yii::t('app', '比赛进行分钟数'),
            'clock_second' => Yii::t('app', '当前分钟秒数'),
            'clock_minutesLeftInPeriod' => Yii::t('app', '场次剩余分钟'),
            'clock_secondsLeftInMinute' => Yii::t('app', '当前分钟剩余秒数'),
            'clock_period' => Yii::t('app', '当前节数'),
            'clock_running' => Yii::t('app', '是否在进行'),
            'score_home' => Yii::t('app', '主队得分'),
            'score_away' => Yii::t('app', '客队得分'),
            'score_info' => Yii::t('app', '比分情况'),
            'score_who' => Yii::t('app', '得分方'),
            'statics_football_home_yellowCards' => Yii::t('app', '主队黄牌数'),
            'statics_football_way_yellowCards' => Yii::t('app', '主队黄牌数'),
            'statics_football_home_redCards' => Yii::t('app', '主队红牌数'),
            'statics_football_way_redCards' => Yii::t('app', '客队红牌数'),
            'statics_football_home_corners' => Yii::t('app', '主队角球数'),
            'statics_football_way_corners' => Yii::t('app', '客队角球数'),
            'liveStatistics' => Yii::t('app', '直播数据统计'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return EventsLiveDatasQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new EventsLiveDatasQuery(get_called_class());
    }
}
