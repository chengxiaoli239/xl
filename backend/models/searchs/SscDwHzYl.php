<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\SscDwHzYl as SscDwHzYlModel;

/**
 * SscDwHzYl represents the model behind the search form of `backend\models\SscDwHzYl`.
 */
class SscDwHzYl extends SscDwHzYlModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'zhi', 'current_miss', 'last_time_miss', 'max_miss', 'history_max_miss', 'updated_at'], 'integer'],
            [['positions', 'last_time_miss_range', 'max_range', 'update_time'], 'safe'],
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
        $query = SscDwHzYlModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $hezhis = $params['SscDwHzYl']['zhi'];
        $positions = $params['SscDwHzYl']['positions'];
        unset($params['SscDwHzYl']['zhi']);
        unset($params['SscDwHzYl']['positions']);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            //'zhi' => $this->zhi,
            'current_miss' => $this->current_miss,
            'last_time_miss' => $this->last_time_miss,
            'max_miss' => $this->max_miss,
            'history_max_miss' => $this->history_max_miss,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'positions', $this->positions])
            ->andFilterWhere(['like', 'last_time_miss_range', $this->last_time_miss_range])
            ->andFilterWhere(['like', 'max_range', $this->max_range]);
        if($hezhis)
            $query->andWhere(['zhi'=>$hezhis]);
        if($positions)
            $query->andWhere(['positions'=>$positions]);

        return $dataProvider;
    }
}
