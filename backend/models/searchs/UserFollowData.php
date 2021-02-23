<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\UserFollowData as UserFollowDataModel;

/**
 * UserFollowData represents the model behind the search form of `backend\models\UserFollowData`.
 */
class UserFollowData extends UserFollowDataModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'codes_hezhi', 'playway', 'is_follow', 'is_simulate', 'status'], 'integer'],
            [['account', 'code', 'position', 'reference_codes', 'updated_at'], 'safe'],
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
        $query = UserFollowDataModel::find();

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
            'codes_hezhi' => $this->codes_hezhi,
            'account' => $this->account,
            'playway' => $this->playway,
            'is_follow' => $this->is_follow,
            'is_simulate' => $this->is_simulate,
            'status' => $this->status,
            'single' => $this->single,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'account', $this->account])
            ->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['like', 'position', $this->position])
            ->andFilterWhere(['like', 'reference_codes', $this->reference_codes]);

        return $dataProvider;
    }
}
