<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\SscDsStatic as SscDsStaticModel;

/**
 * SscDsStatic represents the model behind the search form of `backend\models\SscDsStatic`.
 */
class SscDsStatic extends SscDsStaticModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'periods', 'DS', 'SD', 'DD', 'SS', 'updated_at'], 'integer'],
            [['positions', 'qihao', 'update_time'], 'safe'],
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
        $query = SscDsStaticModel::find();

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
            'periods' => $this->periods,
            'DS' => $this->DS,
            'SD' => $this->SD,
            'DD' => $this->DD,
            'SS' => $this->SS,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'positions', $this->positions])
            ->andFilterWhere(['like', 'qihao', $this->qihao]);

        return $dataProvider;
    }
}
