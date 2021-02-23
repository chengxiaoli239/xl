<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Static4dProfitsMonth as Static4dProfitsMonthModel;

/**
 * Static4dProfitsMonth represents the model behind the search form of `backend\models\Static4dProfitsMonth`.
 */
class Static4dProfitsMonth extends Static4dProfitsMonthModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['month', 'update_time'], 'safe'],
            [['codes_1112', 'codes_1121', 'codes_1211', 'codes_2111', 'codes_1222', 'codes_2122', 'codes_2212', 'codes_2221', 'codes_1122', 'codes_1212', 'codes_1221', 'codes_2112', 'codes_2121', 'codes_2211', 'codes_1111', 'codes_2222'], 'number'],
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
        $query = Static4dProfitsMonthModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['month'=>SORT_DESC]],
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
            'codes_1112' => $this->codes_1112,
            'codes_1121' => $this->codes_1121,
            'codes_1211' => $this->codes_1211,
            'codes_2111' => $this->codes_2111,
            'codes_1222' => $this->codes_1222,
            'codes_2122' => $this->codes_2122,
            'codes_2212' => $this->codes_2212,
            'codes_2221' => $this->codes_2221,
            'codes_1122' => $this->codes_1122,
            'codes_1212' => $this->codes_1212,
            'codes_1221' => $this->codes_1221,
            'codes_2112' => $this->codes_2112,
            'codes_2121' => $this->codes_2121,
            'codes_2211' => $this->codes_2211,
            'codes_1111' => $this->codes_1111,
            'codes_2222' => $this->codes_2222,
            'lottery_type' => $this->lottery_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'month', $this->month]);

        return $dataProvider;
    }
}
