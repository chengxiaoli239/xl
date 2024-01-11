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

    public $username;  // 代理账号
    public $wechatUserName;  // 微信id
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'agent_id', 'type', 'status', 'created_at', 'updated_at'], 'integer'],
            [['member_id', 'member_account', 'desc', 'update_time'], 'safe'],
            [['balance', 'balance_now', 'balance_after'], 'number'],
            [['wechatUserName', 'username'], 'string'],  // 微信好友微信账号
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
        $query = AgentUsersBalanceFlowsModel::find()->alias('a');
        $query->joinWith(['wechatUser']); // 'wechatUser'
        $query->joinWith(['proxy']); // lt_admin

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
            'a.id' => $this->id,
            'a.agent_id' => $this->agent_id,
            'a.type' => $this->type,
            'a.balance' => $this->balance,
            'a.balance_now' => $this->balance_now,
            'a.status' => $this->status,
            'a.created_at' => $this->created_at,
            'a.updated_at' => $this->updated_at,
            'a.update_time' => $this->update_time,
            'lt_admin.username' => trim($this->username),
        ]);

        $query->andFilterWhere(['like', 'member_id', $this->member_id])
            ->andFilterWhere(['like', 'member_account', $this->member_account])
            ->andFilterWhere(['like', 'desc', $this->desc]);
        $query->andFilterWhere(['like', 'lt_wechat_user.userName', $this->wechatUserName]);
        $query->addSelect(['a.*', 'lt_wechat_user.userName', 'lt_admin.username']);
        //$sql = $query->createCommand()->getRawSql(); p($sql);

        return $dataProvider;
    }
}
