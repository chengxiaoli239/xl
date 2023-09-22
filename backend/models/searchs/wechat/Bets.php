<?php

namespace backend\models\searchs\wechat;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\wechat\Bets as BetsModel;

/**
 * Bets represents the model behind the search form of `backend\models\wechat\Bets`.
 */
class Bets extends BetsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'wechat_user_id', 'order_id', 'play_method', 'status', 'cancel_status', 'is_simulate', 'lottery_type', 'is_profits_record', 'created_at', 'updated_at'], 'integer'],
            [['codes', 'qihao', 'kj_codes', 'lottery_name', 'bet_desc', 'update_at'], 'safe'],
            [['bet_money', 'bonus', 'single', 'ratio', 'profits'], 'number'],
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
        $query = BetsModel::find();

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
            'user_id' => $this->user_id,
            'wechat_user_id' => $this->wechat_user_id,
            'order_id' => $this->order_id,
            'play_method' => $this->play_method,
            'bet_money' => $this->bet_money,
            'bonus' => $this->bonus,
            'single' => $this->single,
            'ratio' => $this->ratio,
            'profits' => $this->profits,
            'status' => $this->status,
            'cancel_status' => $this->cancel_status,
            'is_simulate' => $this->is_simulate,
            'lottery_type' => $this->lottery_type,
            'is_profits_record' => $this->is_profits_record,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_at' => $this->update_at,
        ]);

        $query->andFilterWhere(['like', 'codes', $this->codes])
            ->andFilterWhere(['like', 'qihao', $this->qihao])
            ->andFilterWhere(['like', 'kj_codes', $this->kj_codes])
            ->andFilterWhere(['like', 'lottery_name', $this->lottery_name])
            ->andFilterWhere(['like', 'bet_desc', $this->bet_desc]);

        return $dataProvider;
    }
}
