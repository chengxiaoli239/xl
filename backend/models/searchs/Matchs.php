<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Matchs as MatchsModel;

/**
 * Matchs represents the model behind the search form of `backend\models\Matchs`.
 */
class Matchs extends MatchsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'system_id', 'game_type', 'g_id', 'game_id', 'status', 'is_bind', 'bind_id', 'type', 'created_at'], 'integer'],
            [['game_type_name', 'game_name', 'player_1', 'player_2', 'updated_at'], 'safe'],
            [['player_1_water', 'player_2_water'], 'number'],
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
        $query = MatchsModel::find();

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
            'system_id' => $this->system_id,
            'game_type' => $this->game_type,
            'g_id' => $this->g_id,
            'game_id' => $this->game_id,
            'player_1_water' => $this->player_1_water,
            'player_2_water' => $this->player_2_water,
            'status' => $this->status,
            'is_bind' => $this->is_bind,
            'bind_id' => $this->bind_id,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'game_type_name', $this->game_type_name])
            ->andFilterWhere(['like', 'game_name', $this->game_name])
            ->andFilterWhere(['like', 'player_1', $this->player_1])
            ->andFilterWhere(['like', 'player_2', $this->player_2]);

        return $dataProvider;
    }
}
