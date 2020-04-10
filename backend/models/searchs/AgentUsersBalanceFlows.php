<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\AgentUsersBalanceFlows as AgentUsersBalanceFlowsModel;

/**
 * AgentUsersBalanceFlows represents the model behind the search form of `backend\models\AgentUsersBalanceFlows`.
 */
class AgentUsersBalanceFlows extends AgentUsersBalanceFlowsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'agent_id', 'type', 'status', 'created_at', 'updated_at'], 'integer'],
            [['member_id', 'member_account', 'desc', 'update_time'], 'safe'],
            [['balance', 'balance_now', 'balance_after'], 'number'],
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
        $query = AgentUsersBalanceFlowsModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]]
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
            'agent_id' => $this->agent_id,
            'type' => $this->type,
            'balance' => $this->balance,
            'balance_now' => $this->balance_now,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'member_id', $this->member_id])
            ->andFilterWhere(['like', 'member_account', $this->member_account])
            ->andFilterWhere(['like', 'desc', $this->desc]);

        return $dataProvider;
    }
}
