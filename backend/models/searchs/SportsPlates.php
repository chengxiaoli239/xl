<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\sports\SportsPlates as SportsPlatesModel;

/**
 * SportsPlates represents the model behind the search form of `backend\models\sports\SportsPlates`.
 */
class SportsPlates extends SportsPlatesModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'created_at', 'updated_at'], 'integer'],
            [['plate_name', 'base_url', 'football_url', 'tennis_url', 'basketball_url', 'desc', 'update_time'], 'safe'],
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
        $query = SportsPlatesModel::find();

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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'plate_name', $this->plate_name])
            ->andFilterWhere(['like', 'base_url', $this->base_url])
            ->andFilterWhere(['like', 'football_url', $this->football_url])
            ->andFilterWhere(['like', 'tennis_url', $this->tennis_url])
            ->andFilterWhere(['like', 'basketball_url', $this->basketball_url])
            ->andFilterWhere(['like', 'desc', $this->desc]);

        return $dataProvider;
    }
}
