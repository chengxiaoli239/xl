<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\User as UserModel;

/**
 * User represents the model behind the search form of `backend\models\User`.
 */
class User extends UserModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'admin_id', 'user_type', 'status', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            [['username', 'account', 'email', 'tz_password', 'cookie', 'cookie2'], 'safe'],
            [['balance', 'simulate_balance'], 'number'],
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
        $query = UserModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['balance'=>SORT_DESC]],
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
            'admin_id' => $this->admin_id,
            'user_type' => $this->user_type,
            'balance' => $this->balance,
            'simulate_balance' => $this->simulate_balance,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'account', $this->account])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'tz_password', $this->tz_password])
            ->andFilterWhere(['like', 'cookie', $this->cookie])
            ->andFilterWhere(['like', 'cookie2', $this->cookie2]);

        return $dataProvider;
    }
}
