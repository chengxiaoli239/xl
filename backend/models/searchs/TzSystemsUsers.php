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
            [['id', 'uid', 'tz_system_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['username', 'sys_name', 'account', 'password', 'ssc_domain', 'cookie', 'user_agent', 'update_time'], 'safe'],
            [['balance'], 'number'],
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
            'tz_system_id' => $this->tz_system_id,
            'balance' => $this->balance,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'sys_name', $this->sys_name])
            ->andFilterWhere(['like', 'account', $this->account])
            ->andFilterWhere(['like', 'password', $this->password])
            ->andFilterWhere(['like', 'ssc_domain', $this->ssc_domain])
            ->andFilterWhere(['like', 'cookie', $this->cookie])
            ->andFilterWhere(['like', 'user_agent', $this->user_agent]);

        return $dataProvider;
    }
}
