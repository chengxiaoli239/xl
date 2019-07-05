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
            [['id', 'current_miss', 'last_time_miss', 'max_miss', 'history_max_miss', 'count', 'static_nums', 'today_nums', 'ytd_nums', 'lottery_type', 'type', 'status', 'created_at', 'updated_at'], 'integer'],
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

        $this->load($params);
        $queryWhere = [ 'query' => $query, ];
        if(in_array($params['SscStaticYl']['type'], [ 91, 92, 93])){
            $queryWhere = array_merge($queryWhere,[
                'pagination' => [
                    'pageSize' => 20,
                ],
            ]);
        }
        $dataProvider = new ActiveDataProvider($queryWhere);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $whereFilter = [
            'id' => $this->id,
            'current_miss' => $this->current_miss,
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
        ];
        if(in_array($this->type, [91, 92, 93])){
            $tmpArr = [91=>3, 92=>4, 93=>5];
            $whereFilter = ['type' => $tmpArr[$this->type]];
            $query->orderBy(['LENGTH(yl_records)'=>SORT_DESC]);
        }else{
            if($this->val)
                $query->andFilterWhere(['like', 'val', $this->val]);
        }

        $query->andFilterWhere($whereFilter);

        $query->andFilterWhere(['like', 'last_time_miss_range', $this->last_time_miss_range])
            ->andFilterWhere(['like', 'max_range', $this->max_range])
            ->andFilterWhere(['like', 'yl_records', $this->yl_records])
            ->andFilterWhere(['like', 'theory_nums_perdate', $this->theory_nums_perdate]);

        return $dataProvider;
    }
}
