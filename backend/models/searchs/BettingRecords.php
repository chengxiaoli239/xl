<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\BettingRecords as BettingRecordsModel;

/**
 * BettingRecords represents the model behind the search form of `backend\models\BettingRecords`.
 */
class BettingRecords extends BettingRecordsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'lottery_type', 'playway', 'status', 'cancel_status', 'is_simulate', 'createtime'], 'integer'],
            [['codes', 'account', 'plan_id', 'playway_name', 'qihao', 'kj_codes', 'position', 'sn', 'snid', 'lotteryclass', 'create_time'], 'safe'],
            [['betting_money', 'bonus', 'single', 'profits'], 'number'],
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
        $query = BettingRecordsModel::find();

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
        $filterWhere = [
            'id' => $this->id,
            'playway' => $this->playway,
            'betting_money' => $this->betting_money,
            'bonus' => $this->bonus,
            'plan_id' => $this->plan_id,
            'single' => $this->single,
            'profits' => $this->profits,
            'status' => $this->status,
            'cancel_status' => $this->cancel_status,
            'lottery_type' => $this->lottery_type,
            'is_simulate' => $this->is_simulate,
            'createtime' => $this->createtime,
        ];
        if($params['BettingRecords']['uid'] !==1) $filterWhere['uid'] = $params['BettingRecords']['uid'];

        $query->andFilterWhere($filterWhere);

        $query->andFilterWhere(['like', 'codes', $this->codes])
            ->andFilterWhere(['like', 'account', $this->account])
            ->andFilterWhere(['like', 'playway_name', $this->playway_name])
            ->andFilterWhere(['like', 'qihao', $this->qihao])
            ->andFilterWhere(['like', 'kj_codes', $this->kj_codes])
            ->andFilterWhere(['like', 'position', $this->position])
            ->andFilterWhere(['like', 'sn', $this->sn])
            ->andFilterWhere(['like', 'snid', $this->snid])
            ->andFilterWhere(['like', 'lotteryclass', $this->lotteryclass])
            ->andFilterWhere(['like', 'create_time', $this->create_time]);

        return $dataProvider;
    }
}
