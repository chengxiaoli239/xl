<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\sports\EventsLiveDatas as EventsLiveDatasModel;

/**
 * EventsLiveDatas represents the model behind the search form of `backend\models\sports\EventsLiveDatas`.
 */
class EventsLiveDatas extends EventsLiveDatasModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'uid', 'group_id', 'event_time', 'event_id', 'clock_minute', 'clock_second', 'clock_minutesLeftInPeriod', 'clock_secondsLeftInMinute', 'clock_period', 'clock_running', 'score_home', 'score_away', 'created_at', 'updated_at'], 'integer'],
            [['group_name', 'event_name', 'event_name_en', 'event_name_cn', 'home_name_en', 'home_name_cn', 'way_name_en', 'way_name_cn', 'score_info', 'score_who', 'statics_football_home_yellowCards', 'statics_football_way_yellowCards', 'statics_football_home_redCards', 'statics_football_way_redCards', 'statics_football_home_corners', 'statics_football_way_corners', 'liveStatistics', 'update_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = EventsLiveDatasModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'uid' => $this->uid,
            'group_id' => $this->group_id,
            'event_time' => $this->event_time,
            'event_id' => $this->event_id,
            'clock_minute' => $this->clock_minute,
            'clock_second' => $this->clock_second,
            'clock_minutesLeftInPeriod' => $this->clock_minutesLeftInPeriod,
            'clock_secondsLeftInMinute' => $this->clock_secondsLeftInMinute,
            'clock_period' => $this->clock_period,
            'clock_running' => $this->clock_running,
            'score_home' => $this->score_home,
            'score_away' => $this->score_away,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'group_name', $this->group_name])
            ->andFilterWhere(['like', 'event_name', $this->event_name])
            ->andFilterWhere(['like', 'event_name_en', $this->event_name_en])
            ->andFilterWhere(['like', 'event_name_cn', $this->event_name_cn])
            ->andFilterWhere(['like', 'home_name_en', $this->home_name_en])
            ->andFilterWhere(['like', 'home_name_cn', $this->home_name_cn])
            ->andFilterWhere(['like', 'way_name_en', $this->way_name_en])
            ->andFilterWhere(['like', 'way_name_cn', $this->way_name_cn])
            ->andFilterWhere(['like', 'score_info', $this->score_info])
            ->andFilterWhere(['like', 'score_who', $this->score_who])
            ->andFilterWhere(['like', 'statics_football_home_yellowCards', $this->statics_football_home_yellowCards])
            ->andFilterWhere(['like', 'statics_football_way_yellowCards', $this->statics_football_way_yellowCards])
            ->andFilterWhere(['like', 'statics_football_home_redCards', $this->statics_football_home_redCards])
            ->andFilterWhere(['like', 'statics_football_way_redCards', $this->statics_football_way_redCards])
            ->andFilterWhere(['like', 'statics_football_home_corners', $this->statics_football_home_corners])
            ->andFilterWhere(['like', 'statics_football_way_corners', $this->statics_football_way_corners])
            ->andFilterWhere(['like', 'liveStatistics', $this->liveStatistics]);

        return $dataProvider;
    }
}