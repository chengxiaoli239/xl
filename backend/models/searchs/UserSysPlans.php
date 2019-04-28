<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\UserSysPlans as UserSysPlansModel;

/**
 * UserSysPlans represents the model behind the search form of `backend\models\UserSysPlans`.
 */
class UserSysPlans extends UserSysPlansModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'uid', 'playway', 'status', 'tz_type', 'buy_type', 'nums', 'sel_same', 'is_custom', 'created_at', 'updated_at'], 'integer'],
            [['account', 'tz_sites', 'hz_Arr', 'update_time'], 'safe'],
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
        $query = UserSysPlansModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['playway'=>SORT_ASC]]
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
            'playway' => $this->playway,
            'status' => $this->status,
            'uid' => $params['UserSysPlans']['uid'],
            'status' => [0,1],
            'single' => $this->single,
            'tz_type' => $this->tz_type,
            'buy_type' => $this->buy_type,
            'nums' => $this->nums,
            'sel_same' => $this->sel_same,
            'is_custom' => $this->is_custom,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'account', $this->account])
            ->andFilterWhere(['like', 'tz_sites', $this->tz_sites])
            //->andFilterWhere(['in', 'status', [0, 1]])
            ->andFilterWhere(['like', 'hz_Arr', $this->hz_Arr]);

        return $dataProvider;
    }
}
