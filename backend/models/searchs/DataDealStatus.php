<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\DataDealStatus as DataDealStatusModel;

/**
 * DataDealStatus represents the model behind the search form of `backend\models\DataDealStatus`.
 */
class DataDealStatus extends DataDealStatusModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'lottery_type', 'status', 'static4dPerDateProfits_status', 'updateDs_status', 'updateDsYL_status', 'update3NumYL_status', 'updateSdHzYL_status', 'opProfitsPlans_status', 'created_at', 'updated_at'], 'integer'],
            [['qihao', 'status_desc', 'static4dPerDateProfits_status_desc', 'updateDs_status_desc', 'updateDsYL_status_desc', 'update3NumYL_status_desc', 'updateSdHzYL_status_desc', 'opProfitsPlans_status_desc', 'update_time'], 'safe'],
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
        $query = DataDealStatusModel::find();

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
            'lottery_type' => $this->lottery_type,
            'status' => $this->status,
            'static4dPerDateProfits_status' => $this->static4dPerDateProfits_status,
            'updateDs_status' => $this->updateDs_status,
            'updateDsYL_status' => $this->updateDsYL_status,
            'update3NumYL_status' => $this->update3NumYL_status,
            'updateSdHzYL_status' => $this->updateSdHzYL_status,
            'opProfitsPlans_status' => $this->opProfitsPlans_status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'qihao', $this->qihao])
            ->andFilterWhere(['like', 'status_desc', $this->status_desc])
            ->andFilterWhere(['like', 'static4dPerDateProfits_status_desc', $this->static4dPerDateProfits_status_desc])
            ->andFilterWhere(['like', 'updateDs_status_desc', $this->updateDs_status_desc])
            ->andFilterWhere(['like', 'updateDsYL_status_desc', $this->updateDsYL_status_desc])
            ->andFilterWhere(['like', 'update3NumYL_status_desc', $this->update3NumYL_status_desc])
            ->andFilterWhere(['like', 'updateSdHzYL_status_desc', $this->updateSdHzYL_status_desc])
            ->andFilterWhere(['like', 'opProfitsPlans_status_desc', $this->opProfitsPlans_status_desc]);

        return $dataProvider;
    }
}
