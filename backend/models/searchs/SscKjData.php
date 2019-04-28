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
            [['id', 'code1', 'code2', 'code3', 'code4', 'code5', 'code_1_2', 'code_1_3', 'code_1_4', 'code_2_3', 'code_2_4', 'code_3_4', 'codes_4nums_hz', 'codes_hz', 'qihao'], 'integer'],
            [['kj_code', 'code_str', 'date', 'update_time'], 'safe'],
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
            'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]]
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
            'code1' => $this->code1,
            'code2' => $this->code2,
            'code3' => $this->code3,
            'code4' => $this->code4,
            'code5' => $this->code5,
            'codes_hz' => $this->codes_hz,
            'codes_4nums_hz' => $this->codes_4nums_hz,
            'code_1_2' => $this->code_1_2,
            'code_1_3' => $this->code_1_3,
            'code_1_4' => $this->code_1_4,
            'code_2_3' => $this->code_2_3,
            'code_2_4' => $this->code_2_4,
            'code_3_4' => $this->code_3_4,
            'qihao' => $this->qihao,
            'date' => $this->date,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'kj_code', $this->kj_code])
            ->andFilterWhere(['like', 'code_str', $this->code_str]);

        return $dataProvider;
    }
}
