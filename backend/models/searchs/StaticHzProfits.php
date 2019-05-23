<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\StaticHzProfits as StaticHzProfitsModel;

/**
 * StaticHzProfits represents the model behind the search form of `backend\models\StaticHzProfits`.
 */
class StaticHzProfits extends StaticHzProfitsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'created_at', 'updated_at'], 'integer'],
            [['month', 'update_time'], 'safe'],
            [['hz_0_4', 'hz_1_6', 'hz_5_10', 'hz_11_15', 'hz_16_19', 'hz_20_24', 'hz_25_29', 'hz_30_35'], 'number'],
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
        $query = StaticHzProfitsModel::find();

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
            'hz_0_4' => $this->hz_0_4,
            'hz_1_6' => $this->hz_1_6,
            'hz_5_10' => $this->hz_5_10,
            'hz_11_15' => $this->hz_11_15,
            'hz_16_19' => $this->hz_16_19,
            'hz_20_24' => $this->hz_20_24,
            'hz_25_29' => $this->hz_25_29,
            'hz_30_35' => $this->hz_30_35,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'month', $this->month]);

        return $dataProvider;
    }
}
