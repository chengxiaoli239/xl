<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\StaticProfits as StaticProfitsModel;

/**
 * StaticProfits represents the model behind the search form of `backend\models\StaticProfits`.
 */
class StaticProfits extends StaticProfitsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'plan_id', 'uid', 'playway', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['static_time', 'qihao', 'kj_code', 'tz_time', 'update_time'], 'safe'],
            [['tz_money', 'profits', 'zj_bouns', 'cut_profits'], 'number'],
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
        $query = StaticProfitsModel::find();

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
            'plan_id' => $this->plan_id,
            'uid' => $this->uid,
            'playway' => $this->playway,
            'tz_money' => $this->tz_money,
            'profits' => $this->profits,
            'zj_bouns' => $this->zj_bouns,
            'cut_profits' => $this->cut_profits,
            'lottery_type' => $this->lottery_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'static_time', $this->static_time])
            ->andFilterWhere(['like', 'qihao', $this->qihao])
            ->andFilterWhere(['like', 'kj_code', $this->kj_code])
            ->andFilterWhere(['like', 'tz_time', $this->tz_time]);

        return $dataProvider;
    }
}
