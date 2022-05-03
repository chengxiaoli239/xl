<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\sports\SportsRelated as SportsRelatedModel;

/**
 * SportsRelated represents the model behind the search form of `backend\models\sports\SportsRelated`.
 */
class SportsRelated extends SportsRelatedModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'relate_A_game_id', 'relate_B_game_id', 'plate_A_id', 'plate_B_id', 'created_at', 'updated_at'], 'integer'],
            [['relate_type', 'relate_sport_type', 'plate_A_name', 'plate_B_name', 'base_url_A', 'base_url_B', 'plate_bet_url_A', 'plate_bet_url_B', 'plate_bet_conditions', 'desc', 'update_time'], 'safe'],
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
        $query = SportsRelatedModel::find();

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
            'relate_A_game_id' => $this->relate_A_game_id,
            'relate_B_game_id' => $this->relate_B_game_id,
            'plate_A_id' => $this->plate_A_id,
            'plate_B_id' => $this->plate_B_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'relate_type', $this->relate_type])
            ->andFilterWhere(['like', 'relate_sport_type', $this->relate_sport_type])
            ->andFilterWhere(['like', 'plate_A_name', $this->plate_A_name])
            ->andFilterWhere(['like', 'plate_B_name', $this->plate_B_name])
            ->andFilterWhere(['like', 'base_url_A', $this->base_url_A])
            ->andFilterWhere(['like', 'base_url_B', $this->base_url_B])
            ->andFilterWhere(['like', 'plate_bet_url_A', $this->plate_bet_url_A])
            ->andFilterWhere(['like', 'plate_bet_url_B', $this->plate_bet_url_B])
            ->andFilterWhere(['like', 'plate_bet_conditions', $this->plate_bet_conditions])
            ->andFilterWhere(['like', 'desc', $this->desc]);

        return $dataProvider;
    }
}
