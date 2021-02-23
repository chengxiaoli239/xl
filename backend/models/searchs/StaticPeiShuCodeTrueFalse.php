<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\StaticPeiShuCodeTrueFalse as StaticPeiShuCodeTrueFalseModel;

/**
 * StaticPeiShuCodeTrueFalse represents the model behind the search form of `backend\models\StaticPeiShuCodeTrueFalse`.
 */
class StaticPeiShuCodeTrueFalse extends StaticPeiShuCodeTrueFalseModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'code_147_369', 'code_258_369', 'code_019_368', 'code_123_678', 'code_147_258', 'code_017_348', 'code_456_789', 'code_012_789', 'code_345_678', 'code_357_019', 'code_3b', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['date', 'qihao', 'kj_code', 'update_time'], 'safe'],
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
        $query = StaticPeiShuCodeTrueFalseModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]],
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
            'code_147_369' => $this->code_147_369,
            'code_258_369' => $this->code_258_369,
            'code_019_368' => $this->code_019_368,
            'code_123_678' => $this->code_123_678,
            'code_147_258' => $this->code_147_258,
            'code_017_348' => $this->code_017_348,
            'code_456_789' => $this->code_456_789,
            'code_012_789' => $this->code_012_789,
            'code_345_678' => $this->code_345_678,
            'code_357_019' => $this->code_357_019,
            'code_3b' => $this->code_3b,
            'lottery_type' => $this->lottery_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'date', $this->date])
            ->andFilterWhere(['like', 'qihao', $this->qihao])
            ->andFilterWhere(['like', 'kj_code', $this->kj_code]);

        return $dataProvider;
    }
}
