<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\SscDwHz as SscDwHzModel;

/**
 * SscDwHz represents the model behind the search form of `backend\models\SscDwHz`.
 */
class SscDwHz extends SscDwHzModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'periods', 'current_miss', 'last_time_miss', 'hz_0', 'hz_1', 'hz_2', 'hz_3', 'hz_4', 'hz_5', 'hz_6', 'hz_7', 'hz_8', 'hz_9', 'hz_10', 'hz_11', 'hz_12', 'hz_13', 'hz_14', 'hz_15', 'hz_16', 'hz_17', 'hz_18'], 'integer'],
            [['positions', 'qihao'], 'safe'],
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
        $query = SscDwHzModel::find();

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
            'hz_0' => $this->hz_0,
            'hz_1' => $this->hz_1,
            'hz_2' => $this->hz_2,
            'hz_3' => $this->hz_3,
            'hz_4' => $this->hz_4,
            'hz_5' => $this->hz_5,
            'hz_6' => $this->hz_6,
            'hz_7' => $this->hz_7,
            'hz_8' => $this->hz_8,
            'hz_9' => $this->hz_9,
            'hz_10' => $this->hz_10,
            'hz_11' => $this->hz_11,
            'hz_12' => $this->hz_12,
            'hz_13' => $this->hz_13,
            'hz_14' => $this->hz_14,
            'hz_15' => $this->hz_15,
            'hz_16' => $this->hz_16,
            'hz_17' => $this->hz_17,
            'hz_18' => $this->hz_18,
        ]);

        $query->andFilterWhere(['like', 'positions', $this->positions])
            ->andFilterWhere(['like', 'qihao', $this->qihao]);

        return $dataProvider;
    }
}
