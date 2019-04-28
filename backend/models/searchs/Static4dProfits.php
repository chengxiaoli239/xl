<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Static4dProfits as Static4dProfitsModel;

/**
 * Static4dProfits represents the model behind the search form of `backend\models\Static4dProfits`.
 */
class Static4dProfits extends Static4dProfitsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'codes_1_nums', 'codes_2_nums', 'created_at', 'updated_at'], 'integer'],
            [['month', 'update_time'], 'safe'],
            [['codes_4d_all', 'codes_13_31', 'codes_22_22', 'codes_1111_2222', 'codes_13', 'codes_31', 'codes_13_2222', 'codes_31_1111', 'codes_31_2222', 'codes_13_1111', 'codes_31_2222_1111', 'codes_13_1111_2222', 'codes_2222', 'codes_1111'], 'number'],
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
        $query = Static4dProfitsModel::find();

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
            'codes_4d_all' => $this->codes_4d_all,
            'codes_13_31' => $this->codes_13_31,
            'codes_22_22' => $this->codes_22_22,
            'codes_1111_2222' => $this->codes_1111_2222,
            'codes_13' => $this->codes_13,
            'codes_31' => $this->codes_31,
            'codes_13_2222' => $this->codes_13_2222,
            'codes_31_1111' => $this->codes_31_1111,
            'codes_31_2222' => $this->codes_31_2222,
            'codes_13_1111' => $this->codes_13_1111,
            'codes_31_2222_1111' => $this->codes_31_2222_1111,
            'codes_13_1111_2222' => $this->codes_13_1111_2222,
            'codes_2222' => $this->codes_2222,
            'codes_1111' => $this->codes_1111,
            'codes_1_nums' => $this->codes_1_nums,
            'codes_2_nums' => $this->codes_2_nums,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'month', $this->month]);

        return $dataProvider;
    }
}
