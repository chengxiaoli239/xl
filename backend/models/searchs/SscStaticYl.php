<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\SscStaticYl as SscStaticYlModel;

/**
 * SscStaticYl represents the model behind the search form of `backend\models\SscStaticYl`.
 */
class SscStaticYl extends SscStaticYlModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'codes_hz', 'current_miss', 'last_time_miss', 'max_miss', 'history_max_miss', 'count', 'static_nums', 'today_nums', 'ytd_nums', 'lottery_type', 'type', 'status', 'created_at', 'updated_at', 'type_2', 'type_22', 'type_3', 'type_4', 'type_2b', 'type_3b', 'type_4b', 'type_4d', 'type_4s', 'type_log', 'type_4ds'], 'integer'],
            [['val', 'last_time_miss_range', 'max_range', 'yl_records', 'theory_nums_perdate', 'update_time'], 'safe'],
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
        $query = SscStaticYlModel::find();

        // add conditions that should always apply here

        $queryData = [
            'query' => $query,
        ];
        //if($params['SscStaticYl']['is_hots']) $queryData['sort'] = ['defaultOrder' => ['LENGTH(yl_records)'=>SORT_DESC]];
        $dataProvider = new ActiveDataProvider($queryData);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'current_miss' => $this->current_miss,
            'codes_hz' => $this->codes_hz,
            'last_time_miss' => $this->last_time_miss,
            'max_miss' => $this->max_miss,
            'history_max_miss' => $this->history_max_miss,
            'count' => $this->count,
            'static_nums' => $this->static_nums,
            'today_nums' => $this->today_nums,
            'ytd_nums' => $this->ytd_nums,
            'lottery_type' => $this->lottery_type,
            'type' => $this->type,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
            'type_2' => $this->type_2,
            'type_22' => $this->type_22,
            'type_3' => $this->type_3,
            'type_4' => $this->type_4,
            'type_2b' => $this->type_2b,
            'type_3b' => $this->type_3b,
            'type_4b' => $this->type_4b,
            'type_4d' => $this->type_4d,
            'type_4s' => $this->type_4s,
            'type_log' => $this->type_log,
            'type_4ds' => $this->type_4ds,
        ]);
        if(($params['SscStaticYl']['is_hots'] ?? 0) == 1){
            $query->orderBy(['LENGTH(yl_records)'=>SORT_DESC]);
        }else{
            if($this->val)
                $query->andFilterWhere(['like', 'val', $this->val]);
        }

        $query->andFilterWhere(['like', 'val', $this->val])
            ->andFilterWhere(['like', 'last_time_miss_range', $this->last_time_miss_range])
            ->andFilterWhere(['like', 'max_range', $this->max_range])
            ->andFilterWhere(['like', 'yl_records', $this->yl_records])
            ->andFilterWhere(['like', 'theory_nums_perdate', $this->theory_nums_perdate]);

        return $dataProvider;
    }
}
