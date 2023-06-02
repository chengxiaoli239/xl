<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\AgentUserBetLogs as AgentUserBetLogsModel;

/**
 * AgentUserBetLogs represents the model behind the search form of `backend\models\AgentUserBetLogs`.
 */
class AgentUserBetLogs extends AgentUserBetLogsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'uid', 'wp_record_id', 'member_id', 'bet_counts', 'bet_op_counts', 'bet_type', 'playway', 'status', 'tz_system_id', 'created_at', 'updated_at'], 'integer'],
            [['access_token', 'account', 'bet_logs', 'bet_logs_n', 'bet_logs_codes_hz', 'bet_codes', 'bet_codes_op', 'desc', 'lottery_type', 'qihao', 'member_bet_time', 'update_time'], 'safe'],
            [['bet_single', 'bet_money', 'bet_op_single', 'bet_op_money'], 'number'],
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
        $query = AgentUserBetLogsModel::find();

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
            'uid' => $this->uid,
            'wp_record_id' => $this->wp_record_id,
            'member_id' => $this->member_id,
            'bet_counts' => $this->bet_counts,
            'bet_single' => $this->bet_single,
            'bet_money' => $this->bet_money,
            'bet_op_counts' => $this->bet_op_counts,
            'bet_op_single' => $this->bet_op_single,
            'bet_op_money' => $this->bet_op_money,
            'bet_type' => $this->bet_type,
            'playway' => $this->playway,
            'status' => $this->status,
            'member_bet_time' => $this->member_bet_time,
            'tz_system_id' => $this->tz_system_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'access_token', $this->access_token])
            ->andFilterWhere(['like', 'account', $this->account])
            ->andFilterWhere(['like', 'bet_logs', $this->bet_logs])
            ->andFilterWhere(['like', 'bet_logs_n', $this->bet_logs_n])
            ->andFilterWhere(['like', 'bet_logs_codes_hz', $this->bet_logs_codes_hz])
            ->andFilterWhere(['like', 'bet_codes', $this->bet_codes])
            ->andFilterWhere(['like', 'bet_codes_op', $this->bet_codes_op])
            ->andFilterWhere(['like', 'desc', $this->desc])
            ->andFilterWhere(['like', 'lottery_type', $this->lottery_type])
            ->andFilterWhere(['like', 'qihao', $this->qihao]);

        return $dataProvider;
    }
}