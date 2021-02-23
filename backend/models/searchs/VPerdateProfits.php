<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\VPerdateProfits as VPerdateProfitsModel;

/**
 * BettingRecords represents the model behind the search form of `backend\models\BettingRecords`.
 */
class VPerdateProfits extends VPerdateProfitsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'playway', 'status', 'is_simulate', 'createtime'], 'integer'],
            [['codes', 'account', 'playway_name', 'qihao', 'kj_codes', 'position', 'sn', 'snid', 'lotteryclass', 'create_time'], 'safe'],
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
        $query = VPerdateProfitsModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['tz_date'=>SORT_DESC]]
        ]);

        $this->load($params);

        /*
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        */

        // grid filtering conditions
        $query->andFilterWhere([
            'playway' => $this->playway,
            'tz_num' => $this->tz_num,
            //'profits' => $this->profits,
            'tz_money' => $this->tz_money,
            'tz_date' => $this->tz_date,
            'is_simulate' => $this->is_simulate,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['=', 'playway', $this->playway])
            ->andFilterWhere(['like', 'tz_num', $this->tz_num])
            //->andFilterWhere(['like', 'profits', $this->profits])
            ->andFilterWhere(['like', 'tz_money', $this->tz_money])
            ->andFilterWhere(['like', 'tz_date', $this->tz_date])
            ->andFilterWhere(['like', 'is_simulate', $this->is_simulate])
            ->andFilterWhere(['like', 'update_time', $this->update_time]);

        return $dataProvider;
    }
}
