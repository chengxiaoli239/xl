<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\sports\SportsInPlay as SportsInPlayModel;

/**
 * SportsInPlay represents the model behind the search form of `backend\models\sports\SportsInPlay`.
 */
class SportsInPlay extends SportsInPlayModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'play_type', 'game_court', 'plate_id', 'created_at', 'updated_at'], 'integer'],
            [['league_matches_id', 'league_matches_name', 'event_id', 'home_name', 'away_name', 'home_score', 'away_score', 'plate_1X2_odds_1', 'plate_1X2_odds_2', 'plate_1X2_odds_3', 'bet_url', 'plate_bet_conditions', 'desc', 'update_time'], 'safe'],
            [['plate_rolling_home', 'plate_rolling_away'], 'number'],
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
        $query = SportsInPlayModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
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
            'play_type' => $this->play_type,
            'game_court' => $this->game_court,
            'plate_id' => $this->plate_id,
            'plate_rolling_home' => $this->plate_rolling_home,
            'plate_rolling_away' => $this->plate_rolling_away,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'league_matches_id', $this->league_matches_id])
            ->andFilterWhere(['like', 'league_matches_name', $this->league_matches_name])
            ->andFilterWhere(['like', 'event_id', $this->event_id])
            ->andFilterWhere(['like', 'home_name', $this->home_name])
            ->andFilterWhere(['like', 'away_name', $this->away_name])
            ->andFilterWhere(['like', 'home_score', $this->home_score])
            ->andFilterWhere(['like', 'away_score', $this->away_score])
            ->andFilterWhere(['like', 'plate_1X2_odds_1', $this->plate_1X2_odds_1])
            ->andFilterWhere(['like', 'plate_1X2_odds_2', $this->plate_1X2_odds_2])
            ->andFilterWhere(['like', 'plate_1X2_odds_3', $this->plate_1X2_odds_3])
            ->andFilterWhere(['like', 'bet_url', $this->bet_url])
            ->andFilterWhere(['like', 'plate_bet_conditions', $this->plate_bet_conditions])
            ->andFilterWhere(['like', 'desc', $this->desc]);

        return $dataProvider;
    }
}
