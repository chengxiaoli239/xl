<?php

namespace backend\models\searchs\statics;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\statics\StaticPositionTypeArisePerdate as StaticPositionTypeArisePerdateModel;

/**
 * StaticPositionTypeArisePerdate represents the model behind the search form of `backend\models\statics\StaticPositionTypeArisePerdate`.
 */
class StaticPositionTypeArisePerdate extends StaticPositionTypeArisePerdateModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'type', 'p1_1', 'p1_2', 'p2_1', 'p2_2', 'p3_1', 'p3_2', 'p4_1', 'p4_2', 'p5_1', 'p5_2', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['date', 'update_time'], 'safe'],
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
        $query = StaticPositionTypeArisePerdateModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder'=>['id'=>SORT_DESC]],
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
            'type' => $this->type,
            'p1_1' => $this->p1_1,
            'p1_2' => $this->p1_2,
            'p2_1' => $this->p2_1,
            'p2_2' => $this->p2_2,
            'p3_1' => $this->p3_1,
            'p3_2' => $this->p3_2,
            'p4_1' => $this->p4_1,
            'p4_2' => $this->p4_2,
            'p5_1' => $this->p5_1,
            'p5_2' => $this->p5_2,
            'lottery_type' => $this->lottery_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'date', $this->date]);

        return $dataProvider;
    }
}
