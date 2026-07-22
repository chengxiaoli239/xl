<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\TzSystemsUsers as TzSystemsUsersModel;

/**
 * TzSystemsUsers represents the model behind the search form of `backend\models\TzSystemsUsers`.
 */
class TzSystemsUsers extends TzSystemsUsersModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'uid', 'is_agent', 'tz_system_id', 'status', 'is_auto_login', 'kj_num', 'follow_status', 'tz_sort', 'is_auto_bet', 'is_use_proxy', 'is_proxy_login', 'is_proxy_bet', 'is_local_bet', 'proxy_type', 'user_type', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            [['username', 'sys_name', 'account', 'password', 'ssc_domain', 'cookie', 'user_agent', 'cookie_wx_web', 'access_token', 'flow_wp_accounts', 'flow_op_accounts', 'warn_val', 'desc', 'update_time'], 'safe'],
            [['balance', 'flow_wp_player_bs', 'flow_op_player_bs', 'odds_2x', 'odds_3x', 'odds_4x', 'odds_2d', 'odds_3d', 'odds_4d', 'take_profits', 'stop_loss', 'current_profits'], 'number'],
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
        $query = TzSystemsUsersModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['created_at'=>SORT_DESC]]
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
            'is_agent' => $this->is_agent,
            'tz_system_id' => $this->tz_system_id,
            'balance' => $this->balance,
            'status' => $this->status,
            'is_auto_login' => $this->is_auto_login,
            'kj_num' => $this->kj_num,
            'flow_status' => $this->follow_status,
            'tz_sort' => $this->tz_sort,
            'flow_wp_player_bs' => $this->flow_wp_player_bs,
            'flow_op_player_bs' => $this->flow_op_player_bs,
            'odds_2x' => $this->odds_2x,
            'odds_3x' => $this->odds_3x,
            'odds_4x' => $this->odds_4x,
            'odds_2d' => $this->odds_2d,
            'odds_3d' => $this->odds_3d,
            'odds_4d' => $this->odds_4d,
            'is_auto_bet' => $this->is_auto_bet,
            'is_use_proxy' => $this->is_use_proxy,
            'is_proxy_login' => $this->is_proxy_login,
            'is_proxy_bet' => $this->is_proxy_bet,
            'is_local_bet' => $this->is_local_bet,
            'access_token' => $this->access_token,
            'proxy_type' => $this->proxy_type,
            'user_type' => $this->user_type,
            'expire_time' => $this->expire_time,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'sys_name', $this->sys_name])
            ->andFilterWhere(['like', 'account', $this->account])
            ->andFilterWhere(['like', 'flow_wp_accounts', $this->flow_wp_accounts])
            ->andFilterWhere(['like', 'flow_op_accounts', $this->flow_op_accounts])
            ->andFilterWhere(['like', 'password', $this->password])
            ->andFilterWhere(['like', 'ssc_domain', $this->ssc_domain])
            ->andFilterWhere(['like', 'cookie', $this->cookie])
            ->andFilterWhere(['like', 'user_agent', $this->user_agent])
            ->andFilterWhere(['like', 'cookie_wx_web', $this->cookie_wx_web])
            ->andFilterWhere(['like', 'access_token', $this->access_token])
            ->andFilterWhere(['like', 'warn_val', $this->warn_val])
            ->andFilterWhere(['like', 'desc', $this->desc]);

        return $dataProvider;
    }
}
