<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Static3numArisePerdate as Static3numArisePerdateModel;

/**
 * Static3numArisePerdate represents the model behind the search form of `backend\models\Static3numArisePerdate`.
 */
class Static3numArisePerdate extends Static3numArisePerdateModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'codes_289', 'codes_046', 'codes_456', 'codes_125', 'codes_589', 'codes_025', 'codes_467', 'codes_256', 'codes_128', 'codes_347', 'codes_134', 'codes_258', 'codes_124', 'codes_014', 'codes_147', 'codes_345', 'codes_678', 'codes_238', 'codes_239', 'codes_028', 'codes_268', 'codes_389', 'codes_348', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['date', 'update_time'], 'safe'],
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
        $query = Static3numArisePerdateModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['date'=>SORT_DESC]],
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
            'codes_289' => $this->codes_289,
            'codes_046' => $this->codes_046,
            'codes_456' => $this->codes_456,
            'codes_125' => $this->codes_125,
            'codes_589' => $this->codes_589,
            'codes_025' => $this->codes_025,
            'codes_467' => $this->codes_467,
            'codes_256' => $this->codes_256,
            'codes_128' => $this->codes_128,
            'codes_347' => $this->codes_347,
            'codes_134' => $this->codes_134,
            'codes_258' => $this->codes_258,
            'codes_124' => $this->codes_124,
            'codes_014' => $this->codes_014,
            'codes_147' => $this->codes_147,
            'codes_345' => $this->codes_345,
            'codes_678' => $this->codes_678,
            'codes_238' => $this->codes_238,
            'codes_239' => $this->codes_239,
            'codes_028' => $this->codes_028,
            'codes_268' => $this->codes_268,
            'codes_389' => $this->codes_389,
            'codes_348' => $this->codes_348,
            'lottery_type' => $this->lottery_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'date', $this->date]);

        return $dataProvider;
    }
}
