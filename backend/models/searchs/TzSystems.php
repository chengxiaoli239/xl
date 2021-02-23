<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\TzSystems as TzSystemsModel;

/**
 * TzSystems represents the model behind the search form of `backend\models\TzSystems`.
 */
class TzSystems extends TzSystemsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'system_type_id', 'status', 'type', 'created_at'], 'integer'],
            [['name', 'ssc_domain', 'tz_types', 'updated_at'], 'safe'],
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
        $query = TzSystemsModel::find();

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
            'system_type_id' => $this->system_type_id,
            'status' => $this->status,
            'type' => $this->type,
            'tz_types' => $this->tz_types,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'ssc_domain', $this->ssc_domain]);

        return $dataProvider;
    }
}
