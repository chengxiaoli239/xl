<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\StaticPerHzProfits as StaticPerHzProfitsModel;

/**
 * StaticPerHzProfits represents the model behind the search form of `backend\models\StaticPerHzProfits`.
 */
class StaticPerHzProfits extends StaticPerHzProfitsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['month', 'update_time'], 'safe'],
            [['codes_1', 'codes_2', 'codes_3', 'codes_4', 'codes_5', 'codes_6', 'codes_7', 'codes_8', 'codes_9', 'codes_10', 'codes_11', 'codes_12', 'codes_13', 'codes_14', 'codes_15', 'codes_16', 'codes_17', 'codes_18', 'codes_19', 'codes_20', 'codes_21', 'codes_22', 'codes_23', 'codes_24', 'codes_25', 'codes_26', 'codes_27', 'codes_28', 'codes_29', 'codes_30', 'codes_31', 'codes_32', 'codes_33', 'codes_34', 'codes_35', 'codes_36'], 'number'],
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
        $query = StaticPerHzProfitsModel::find();

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
            'codes_1' => $this->codes_1,
            'codes_2' => $this->codes_2,
            'codes_3' => $this->codes_3,
            'codes_4' => $this->codes_4,
            'codes_5' => $this->codes_5,
            'codes_6' => $this->codes_6,
            'codes_7' => $this->codes_7,
            'codes_8' => $this->codes_8,
            'codes_9' => $this->codes_9,
            'codes_10' => $this->codes_10,
            'codes_11' => $this->codes_11,
            'codes_12' => $this->codes_12,
            'codes_13' => $this->codes_13,
            'codes_14' => $this->codes_14,
            'codes_15' => $this->codes_15,
            'codes_16' => $this->codes_16,
            'codes_17' => $this->codes_17,
            'codes_18' => $this->codes_18,
            'codes_19' => $this->codes_19,
            'codes_20' => $this->codes_20,
            'codes_21' => $this->codes_21,
            'codes_22' => $this->codes_22,
            'codes_23' => $this->codes_23,
            'codes_24' => $this->codes_24,
            'codes_25' => $this->codes_25,
            'codes_26' => $this->codes_26,
            'codes_27' => $this->codes_27,
            'codes_28' => $this->codes_28,
            'codes_29' => $this->codes_29,
            'codes_30' => $this->codes_30,
            'codes_31' => $this->codes_31,
            'codes_32' => $this->codes_32,
            'codes_33' => $this->codes_33,
            'codes_34' => $this->codes_34,
            'codes_35' => $this->codes_35,
            'codes_36' => $this->codes_36,
            'lottery_type' => $this->lottery_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'month', $this->month]);

        return $dataProvider;
    }
}
