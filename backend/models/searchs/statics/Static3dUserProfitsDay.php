<?php

namespace backend\models\searchs\statics;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\statics\Static3dUserProfitsDay as Static3dUserProfitsDayModel;

/**
 * Static3dUserProfitsDay represents the model behind the search form of `backend\models\statics\Static3dUserProfitsDay`.
 */
class Static3dUserProfitsDay extends Static3dUserProfitsDayModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'wechat_user_id', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['date', 'update_time'], 'safe'],
            [['bet_money', 'bonus', 'profits'], 'number'],
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
        $query = Static3dUserProfitsDayModel::find();

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
            'bet_money' => $this->bet_money,
            'bonus' => $this->bonus,
            'profits' => $this->profits,
            'lottery_type' => $this->lottery_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'date', $this->date]);

        return $dataProvider;
    }
}
