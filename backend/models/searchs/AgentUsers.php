<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\AgentUsers as AgentUsersModel;

/**
 * AgentUsers represents the model behind the search form of `backend\models\AgentUsers`.
 */
class AgentUsers extends AgentUsersModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'agent_id', 'is_tuo', 'is_chi', 'is_private', 'is_cha', 'is_bind', 'status', 'created_at', 'updated_at'], 'integer'],
            [['name', 'desc', 'images', 'bet_url', 'token', 'update_time'], 'safe'],
            [['balance', 'all_bet_money', 'today_profits_loss', 'all_profits_loss'], 'number'],
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
        $query = AgentUsersModel::find();

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
            'agent_id' => $this->agent_id,
            'balance' => $this->balance,
            'is_tuo' => $this->is_tuo,
            'is_chi' => $this->is_chi,
            'is_private' => $this->is_private,
            'is_cha' => $this->is_cha,
            'is_bind' => $this->is_bind,
            'all_bet_money' => $this->all_bet_money,
            'today_profits_loss' => $this->today_profits_loss,
            'all_profits_loss' => $this->all_profits_loss,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'desc', $this->desc])
            ->andFilterWhere(['like', 'images', $this->images])
            ->andFilterWhere(['like', 'bet_url', $this->bet_url])
            ->andFilterWhere(['like', 'token', $this->token]);

        return $dataProvider;
    }
}
