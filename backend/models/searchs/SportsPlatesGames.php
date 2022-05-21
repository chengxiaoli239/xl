<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\sports\SportsPlatesGames as SportsPlatesGamesModel;

/**
 * SportsPlatesGames represents the model behind the search form of `backend\models\sports\SportsPlatesGames`.
 */
class SportsPlatesGames extends SportsPlatesGamesModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'created_at', 'updated_at'], 'integer'],
            [['plate_id', 'plate_name', 'bet_url', 'league_matches_id', 'league_matches_name', 'name1', 'name1_path', 'name2', 'name2_path', 'event_id', 'score', 'desc', 'update_time'], 'safe'],
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
        $query = SportsPlatesGamesModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]],
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'plate_id', $this->plate_id])
            ->andFilterWhere(['like', 'plate_name', $this->plate_name])
            ->andFilterWhere(['like', 'bet_url', $this->bet_url])
            ->andFilterWhere(['like', 'league_matches_id', $this->league_matches_id])
            ->andFilterWhere(['like', 'league_matches_name', $this->league_matches_name])
            ->andFilterWhere(['like', 'name1', $this->name1])
            ->andFilterWhere(['like', 'name1_path', $this->name1_path])
            ->andFilterWhere(['like', 'name2', $this->name2])
            ->andFilterWhere(['like', 'name2_path', $this->name2_path])
            ->andFilterWhere(['like', 'event_id', $this->event_id])
            ->andFilterWhere(['like', 'score', $this->score])
            ->andFilterWhere(['like', 'desc', $this->desc]);

        return $dataProvider;
    }
}
