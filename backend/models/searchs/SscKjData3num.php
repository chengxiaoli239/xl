<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\SscKjData3num as SscKjData3numModel;

/**
 * SscKjData3num represents the model behind the search form of `backend\models\SscKjData3num`.
 */
class SscKjData3num extends SscKjData3numModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'qihao', 'created_at', 'updated_at'], 'integer'],
            [['code_str', 'code_3n', 'date', 'update_time'], 'safe'],
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
        $query = SscKjData3numModel::find();

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
            'qihao' => $this->qihao,
            'date' => $this->date,
            'created_at' => $this->created_at,
            'update_time' => $this->update_time,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'code_str', $this->code_str])
            ->andFilterWhere(['like', 'code_3n', $this->code_3n]);

        return $dataProvider;
    }
}
