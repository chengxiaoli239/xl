<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\AgentRecordUsersDesc as AgentRecordUsersDescModel;

/**
 * AgentRecordUsersDesc represents the model behind the search form of `backend\models\AgentRecordUsersDesc`.
 */
class AgentRecordUsersDesc extends AgentRecordUsersDescModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'agent_id', 'member_id', 'type', 'status', 'created_at', 'updated_at'], 'integer'],
            [['member_account', 'token', 'desc', 'return', 'lottery_type', 'qihao', 'user_info', 'update_time'], 'safe'],
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
        $query = AgentRecordUsersDescModel::find();

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
            'member_id' => $this->member_id,
            'type' => $this->type,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'member_account', $this->member_account])
            ->andFilterWhere(['like', 'token', $this->token])
            ->andFilterWhere(['like', 'desc', $this->desc])
            ->andFilterWhere(['like', 'return', $this->return])
            ->andFilterWhere(['like', 'lottery_type', $this->lottery_type])
            ->andFilterWhere(['like', 'qihao', $this->qihao])
            ->andFilterWhere(['like', 'user_info', $this->user_info]);

        return $dataProvider;
    }
}
