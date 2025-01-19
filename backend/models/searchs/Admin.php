<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Admin as AdminModel;

/**
 * Admin represents the model behind the search form of `backend\models\Admin`.
 */
class Admin extends AdminModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'status', 'user_type', 'is_can_op_bet', 'created_at', 'updated_at'], 'integer'],
            [['username', 'auth_key', 'password_hash', 'password_reset_token', 'email', 'pay_time', 'desc'], 'safe'],
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
        $query = AdminModel::find()->alias('u');

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
            'u.id' => $this->id,
            'u.pay_time' => $this->pay_time,
            'u.user_type' => $this->user_type,
            'u.status' => $this->status,
            'u.created_at' => $this->created_at,
            'u.updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'u.username', $this->username])
            ->andFilterWhere(['like', 'u.auth_key', $this->auth_key])
            ->andFilterWhere(['like', 'u.password_hash', $this->password_hash])
            ->andFilterWhere(['like', 'u.password_reset_token', $this->password_reset_token])
            ->andFilterWhere(['like', 'u.email', $this->email])
            ->andFilterWhere(['like', 'u.desc', $this->desc]);
        //$sql = $query->createCommand()->getRawSql();p($sql);

        return $dataProvider;
    }
}
