<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\SscKjData as SscKjDataModel;

/**
 * SscKjData represents the model behind the search form of `backend\models\SscKjData`.
 */
class SscKjData extends SscKjDataModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'index_id', 'codes_hz', 'codes_4nums_hz', 'code1', 'code2', 'code3', 'code4', 'code5', 'code_1_2', 'code_1_3', 'code_1_4', 'code_2_3', 'code_2_4', 'code_3_4', 'qihao', 'type_2', 'type_22', 'type_3', 'type_4', 'type_2b', 'type_3b', 'type_4b', 'type_4ds', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['kj_code', 'code_str', 'date', 'code_3n', 'code_4n', 'update_time'], 'safe'],
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
        $query = SscKjDataModel::find();

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
            'index_id' => $this->index_id,
            'codes_hz' => $this->codes_hz,
            'codes_4nums_hz' => $this->codes_4nums_hz,
            'code1' => $this->code1,
            'code2' => $this->code2,
            'code3' => $this->code3,
            'code4' => $this->code4,
            'code5' => $this->code5,
            'code_1_2' => $this->code_1_2,
            'code_1_3' => $this->code_1_3,
            'code_1_4' => $this->code_1_4,
            'code_2_3' => $this->code_2_3,
            'code_2_4' => $this->code_2_4,
            'code_3_4' => $this->code_3_4,
            'qihao' => $this->qihao,
            'date' => $this->date,
            'type_2' => $this->type_2,
            'type_22' => $this->type_22,
            'type_3' => $this->type_3,
            'type_4' => $this->type_4,
            'type_2b' => $this->type_2b,
            'type_3b' => $this->type_3b,
            'type_4b' => $this->type_4b,
            'type_4ds' => $this->type_4ds,
            'lottery_type' => $this->lottery_type,
            'created_at' => $this->created_at,
            'update_time' => $this->update_time,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'kj_code', $this->kj_code])
            ->andFilterWhere(['like', 'code_str', $this->code_str])
            ->andFilterWhere(['like', 'code_3n', $this->code_3n])
            ->andFilterWhere(['like', 'code_4n', $this->code_4n]);

        return $dataProvider;
    }
}
