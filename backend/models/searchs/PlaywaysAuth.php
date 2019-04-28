<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\PlaywaysAuth as PlaywaysAuthModel;

/**
 * PlaywaysAuth represents the model behind the search form of `backend\models\PlaywaysAuth`.
 */
class PlaywaysAuth extends PlaywaysAuthModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'uid', 'created_at', 'updated_at'], 'integer'],
            [['playway_list', 'playway_name', 'update_time'], 'safe'],
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
        $query = PlaywaysAuthModel::find();

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
            'uid' => $this->uid,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'playway_list', $this->playway_list])
            ->andFilterWhere(['like', 'playway_name', $this->playway_name]);

        return $dataProvider;
    }
}
