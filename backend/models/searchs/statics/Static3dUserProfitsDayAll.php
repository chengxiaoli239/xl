<?php

namespace backend\models\searchs\statics;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\statics\Static3dUserProfitsDayAll as Static3dUserProfitsDayAllModel;

/**
 * Static3dUserProfitsDayAll represents the model behind the search form of `backend\models\statics\Static3dUserProfitsDayAll`.
 */
class Static3dUserProfitsDayAll extends Static3dUserProfitsDayAllModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'wechat_user_id', 'created_at', 'updated_at'], 'integer'],
            [['date', 'wechat_user_name', 'update_time'], 'safe'],
            [['bet_money', 'bonus', 'up_money', 'down_money', 'profits'], 'number'],
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
        $query = Static3dUserProfitsDayAllModel::find();

        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder'=>['id'=>SORT_DESC]],
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
            'date' => $this->date,
            'user_id' => $this->user_id,
            'wechat_user_id' => $this->wechat_user_id,
            'bet_money' => $this->bet_money,
            'bonus' => $this->bonus,
            'up_money' => $this->up_money,
            'down_money' => $this->down_money,
            'profits' => $this->profits,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'wechat_user_name', $this->wechat_user_name]);

        return $dataProvider;
    }
}
