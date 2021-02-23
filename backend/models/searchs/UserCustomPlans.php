<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\UserCustomPlans as UserCustomPlansModel;

/**
 * UserCustomPlans represents the model behind the search form of `backend\models\UserCustomPlans`.
 */
class UserCustomPlans extends UserCustomPlansModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'playway', 'status', 'threshold_open', 'threshold_close', 'periods_open', 'periods_close', 'is_simulate', 'created_at', 'updated_at'], 'integer'],
            [['account', 'hezhis', 'positions'], 'safe'],
            [['single'], 'number'],
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
        $query = UserCustomPlansModel::find();

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
            'playway' => $this->playway,
            'status' => $this->status,
            'single' => $this->single,
            'threshold_open' => $this->threshold_open,
            'threshold_close' => $this->threshold_close,
            'periods_close' => $this->periods_close,
            'periods_open' => $this->periods_open,
            'is_simulate' => $this->is_simulate,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'account', $this->account])
            ->andFilterWhere(['like', 'hezhis', $this->hezhis])
            ->andFilterWhere(['like', 'positions', $this->positions]);

        return $dataProvider;
    }
}
