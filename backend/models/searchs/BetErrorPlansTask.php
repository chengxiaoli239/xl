<?php

namespace backend\models\searchs;

use common\tools\Tools;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\BetErrorPlansTask as BetErrorPlansTaskModel;

/**
 * BetErrorPlansTask represents the model behind the search form of `backend\models\BetErrorPlansTask`.
 */
class BetErrorPlansTask extends BetErrorPlansTaskModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'uid', 'agent_id', 'playway', 'tz_type', 'status', 'plan_id', 'is_local_bet', 'tz_system_id', 'lottery_type', 'updated_at', 'created_at'], 'integer'],
            [['codes', 'account', 'bet_url', 'bet_headers', 'post_datas', 'playway_name', 'qihao', 'kj_codes', 'sn', 'snid', 'lotteryclass', 'post_desc', 'error_desc', 'updated_time'], 'safe'],
            [['bet_money', 'single'], 'number'],
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
        $query = BetErrorPlansTaskModel::find();

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
        $filterWhere = [
            'id' => $this->id,
            'agent_id' => $this->agent_id,
            'playway' => $this->playway,
            'tz_type' => $this->tz_type,
            'bet_money' => $this->bet_money,
            'account' => $this->account,
            'single' => $this->single,
            'status' => $this->status,
            'plan_id' => $this->plan_id,
            'is_local_bet' => $this->is_local_bet,
            'tz_system_id' => $this->tz_system_id,
            'updated_time' => $this->updated_time,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];

        $uid = $params['BetErrorPlansTask']['uid'] ?? 1;
        if((int)$uid !== 1){
            $filterWhere['uid'] = $uid;
            $filterWhere['lottery_type'] = $params['BetErrorPlansTask']['lottery_type'] ?? null;
        }
        // grid filtering conditions
        $query->andFilterWhere($filterWhere);
        if(!empty($params['BetErrorPlansTask']['plan_ids'])){
            $ids = Tools::getQuerySplit($params['BetErrorPlansTask']['plan_ids']);
            $query->andWhere(['IN', 'plan_id', $ids]);
        }
        if(!empty($params['BetErrorPlansTask']['qihao'])){
            $ids = Tools::getQuerySplit($params['BetErrorPlansTask']['qihao']);
            $query->andWhere(['IN', 'qihao', $ids]);
        }

        $query->andFilterWhere(['like', 'codes', $this->codes])
            ->andFilterWhere(['like', 'bet_url', $this->bet_url])
            ->andFilterWhere(['like', 'bet_headers', $this->bet_headers])
            ->andFilterWhere(['like', 'post_datas', $this->post_datas])
            ->andFilterWhere(['like', 'playway_name', $this->playway_name])
            //->andFilterWhere(['like', 'qihao', $this->qihao])
            ->andFilterWhere(['like', 'kj_codes', $this->kj_codes])
            ->andFilterWhere(['like', 'sn', $this->sn])
            ->andFilterWhere(['like', 'snid', $this->snid])
            ->andFilterWhere(['like', 'lotteryclass', $this->lotteryclass])
            ->andFilterWhere(['like', 'post_desc', $this->post_desc])
            ->andFilterWhere(['like', 'error_desc', $this->error_desc]);

        return $dataProvider;
    }
}
