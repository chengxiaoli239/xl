<?php

namespace backend\models\searchs\statics;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\statics\Ssc1numsYl as Ssc1numsYlModel;

/**
 * Ssc1numsYl represents the model behind the search form of `backend\models\statics\Ssc1numsYl`.
 */
class Ssc1numsYl extends Ssc1numsYlModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'position', 'today_current', 'current_miss', 'today_miss', 'week_miss', 'month_miss', 'lottery_type', 'created_at'], 'integer'],
            [['code', 'update_time'], 'safe'],
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
        $query = Ssc1numsYlModel::find();

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
            'position' => $this->position,
            'today_current' => $this->today_current,
            'current_miss' => $this->current_miss,
            'today_miss' => $this->today_miss,
            'week_miss' => $this->week_miss,
            'month_miss' => $this->month_miss,
            'lottery_type' => $this->lottery_type,
            'created_at' => $this->created_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'code', $this->code]);

        return $dataProvider;
    }
}
