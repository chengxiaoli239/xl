<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Ssc2numsYl as Ssc2numsYlModel;

/**
 * Ssc2numsYl represents the model behind the search form of `backend\models\Ssc2numsYl`.
 */
class Ssc2numsYl extends Ssc2numsYlModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'current_miss', 'last_time_miss', 'max_miss', 'history_max_miss', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['val', 'last_time_miss_range', 'max_range', 'yl_records', 'update_time'], 'safe'],
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
        $query = Ssc2numsYlModel::find();

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
            'current_miss' => $this->current_miss,
            'last_time_miss' => $this->last_time_miss,
            'max_miss' => $this->max_miss,
            'history_max_miss' => $this->history_max_miss,
            'lottery_type' => $this->lottery_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'val', $this->val])
            ->andFilterWhere(['like', 'last_time_miss_range', $this->last_time_miss_range])
            ->andFilterWhere(['like', 'max_range', $this->max_range])
            ->andFilterWhere(['like', 'yl_records', $this->yl_records]);

        return $dataProvider;
    }
}
