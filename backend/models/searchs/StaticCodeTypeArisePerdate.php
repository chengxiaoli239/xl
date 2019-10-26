<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\StaticCodeTypeArisePerdate as StaticCodeTypeArisePerdateModel;

/**
 * StaticCodeTypeArisePerdate represents the model behind the search form of `backend\models\StaticCodeTypeArisePerdate`.
 */
class StaticCodeTypeArisePerdate extends StaticCodeTypeArisePerdateModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'type_2', 'type_3', 'type_22', 'type_2b', 'type_3b', 'type_4b', 'type_2_type_2b', 'type_2_type_3b', 'type_3n_2b', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
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
        $query = StaticCodeTypeArisePerdateModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['date'=>SORT_DESC]],
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
            'type_2' => $this->type_2,
            'type_3' => $this->type_3,
            'type_22' => $this->type_22,
            'type_2b' => $this->type_2b,
            'type_3b' => $this->type_3b,
            'type_4b' => $this->type_4b,
            'type_2_type_2b' => $this->type_2_type_2b,
            'type_2_type_3b' => $this->type_2_type_3b,
            'type_3n_2b' => $this->type_3n_2b,
            'lottery_type' => $this->lottery_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'date', $this->date]);

        return $dataProvider;
    }
}
